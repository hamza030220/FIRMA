<?php

namespace App\Service\Event;

use App\Entity\Event\Accompagnant;
use App\Entity\Event\Evenement;
use App\Entity\Event\Participation;
use App\Entity\User\Utilisateur;
use App\Repository\Event\EvenementRepository;
use App\Repository\Event\ParticipationRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Color\Color;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ParticipationService
{
    private const HMAC_SECRET = 'firma_participation_confirm_2026';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ParticipationRepository $participationRepo,
        private readonly EvenementRepository $evenementRepo,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RequestStack $requestStack,
    ) {}

    /** Génère un token HMAC à partir de l'id et du code (pas de colonne DB). */
    public function generateToken(Participation $participation): string
    {
        return hash_hmac('sha256', $participation->getIdParticipation() . '|' . $participation->getCodeParticipation(), self::HMAC_SECRET);
    }

    /** Vérifie un token HMAC. */
    public function verifyToken(Participation $participation, string $token): bool
    {
        return hash_equals($this->generateToken($participation), $token);
    }

    /**
     * Inscrit un utilisateur à un événement avec ses accompagnants.
     *
     * @param Accompagnant[] $accompagnants
     * @throws \RuntimeException
     */
    public function inscrire(
        Evenement $evenement,
        Utilisateur $utilisateur,
        int $nbAccompagnants,
        array $accompagnants = [],
        ?string $commentaire = null,
    ): Participation {
        // Vérifier doublon
        if ($this->participationRepo->isUserAlreadyParticipating($utilisateur->getId(), $evenement->getIdEvenement())) {
            throw new \RuntimeException('Vous êtes déjà inscrit à cet événement.');
        }

        // Réserver les places (1 participant + accompagnants)
        $totalPlaces = 1 + $nbAccompagnants;
        if (!$this->evenementRepo->reserverPlaces($evenement->getIdEvenement(), $totalPlaces)) {
            throw new \RuntimeException('Pas assez de places disponibles.');
        }

        // Créer la participation
        $participation = new Participation();
        $participation->setEvenement($evenement);
        $participation->setUtilisateur($utilisateur);
        $participation->setNombreAccompagnants($nbAccompagnants);
        $participation->setCommentaire($commentaire);
        $participation->setCodeParticipation(Participation::genererCode());
        $participation->setStatut('en_attente');
        $participation->setDateInscription(new \DateTime());

        // Ajouter les accompagnants
        foreach ($accompagnants as $acc) {
            $acc->setParticipation($participation);
            $participation->addAccompagnant($acc);
        }

        $this->em->persist($participation);
        $this->em->flush();

        // Email 1 : demande de confirmation (sans le code)
        $this->sendPendingEmail($participation);

        return $participation;
    }

    /** Confirme une participation (EN_ATTENTE → CONFIRME) et envoie le code par email. */
    public function confirmer(Participation $participation): void
    {
        if ($participation->getStatut() !== 'en_attente') {
            throw new \RuntimeException('Cette participation ne peut pas être confirmée.');
        }

        $participation->setStatut('confirme');

        // Generate codes for accompagnants
        foreach ($participation->getAccompagnants() as $acc) {
            if (!$acc->getCodeAccompagnant()) {
                $acc->setCodeAccompagnant(Accompagnant::genererCode());
            }
        }

        $this->em->flush();

        // Email 2 : confirmation avec le code
        try {
            $this->sendConfirmedEmail($participation);
        } catch (\Throwable $e) {
            // Le statut est mis à jour même si l'email échoue
            // Le statut est mis à jour même si l'email échoue
        }
    }

    // ═══════════════════════════════════════════
    //  EMAIL 1 — Demande de confirmation
    // ═══════════════════════════════════════════
    private function sendPendingEmail(Participation $participation): void
    {
        $user = $participation->getUtilisateur();
        $evt  = $participation->getEvenement();

        $token = $this->generateToken($participation);
        $confirmUrl = $this->urlGenerator->generate('public_participation_confirm', [
            'id'    => $participation->getIdParticipation(),
            'token' => $token,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        // Accompagnants HTML
        $accompHtml = '';
        foreach ($participation->getAccompagnants() as $i => $acc) {
            $accompHtml .= '<tr><td style="padding:6px 12px;border-bottom:1px solid #e8ede9;color:#5a6e5f;font-size:14px">'
                . ($i + 1) . '. ' . htmlspecialchars($acc->getPrenom() . ' ' . $acc->getNom()) . '</td></tr>';
        }

        $html = $this->buildEmailLayout(
            'Confirmez votre participation',
            '
            <p style="font-size:16px;color:#2d2d2d;margin:0 0 8px">Bonjour <strong>' . htmlspecialchars($user->getPrenom() . ' ' . $user->getNom()) . '</strong>,</p>
            <p style="font-size:15px;color:#5a6e5f;margin:0 0 24px">Votre demande de participation a été enregistrée. Veuillez confirmer en cliquant sur le bouton ci-dessous.</p>

            <!-- Event card -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f8f5;border-radius:12px;margin-bottom:24px">
                <tr><td style="padding:20px">
                    <p style="margin:0 0 4px;font-family:\'Playfair Display\',Georgia,serif;font-size:20px;font-weight:700;color:#1a3a24">' . htmlspecialchars($evt->getTitre()) . '</p>
                    <table cellpadding="0" cellspacing="0" style="margin-top:12px">
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;width:30px;vertical-align:top">📅</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . $evt->getDateDebut()?->format('d/m/Y') . ' — ' . $evt->getDateFin()?->format('d/m/Y') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;vertical-align:top">⏰</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . $evt->getHoraireDebut()?->format('H:i') . ' – ' . $evt->getHoraireFin()?->format('H:i') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;vertical-align:top">📍</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . htmlspecialchars($evt->getLieu() ?? '') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;vertical-align:top">👥</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . $participation->getTotalPersonnes() . ' personne(s) (vous + ' . $participation->getNombreAccompagnants() . ' accompagnant(s))</td>
                        </tr>
                    </table>
                </td></tr>
            </table>

            ' . ($accompHtml ? '
            <!-- Accompagnants -->
            <p style="font-size:14px;font-weight:600;color:#1a3a24;margin:0 0 8px">Accompagnants :</p>
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;background:#fafcfa;border-radius:8px;overflow:hidden">
                ' . $accompHtml . '
            </table>' : '') . '

            <!-- Status badge -->
            <table cellpadding="0" cellspacing="0" style="margin-bottom:24px">
                <tr><td style="background:#fff3cd;color:#856404;padding:8px 16px;border-radius:20px;font-size:13px;font-weight:600">
                    ⏳ En attente de confirmation
                </td></tr>
            </table>

            <!-- CTA button -->
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr><td align="center" style="padding:8px 0 24px">
                    <a href="' . htmlspecialchars($confirmUrl) . '" style="display:inline-block;background:#20452c;color:#ffffff;padding:14px 40px;border-radius:8px;font-size:16px;font-weight:600;text-decoration:none;letter-spacing:.3px">
                        ✅ Confirmer ma participation
                    </a>
                </td></tr>
            </table>

            <p style="font-size:13px;color:#8a9a8e;text-align:center;margin:0">Votre code de participation vous sera envoyé après confirmation.</p>
            '
        );

        $email = (new Email())
            ->from('FIRMA <firmaagritech@gmail.com>')
            ->to($user->getEmail())
            ->subject('FIRMA — Confirmez votre participation : ' . $evt->getTitre())
            ->html($html);

        $this->mailer->send($email);
    }

    // ═══════════════════════════════════════════
    //  EMAIL 2 — Participation confirmée + code
    // ═══════════════════════════════════════════
    private function sendConfirmedEmail(Participation $participation): void
    {
        $user = $participation->getUtilisateur();
        $evt  = $participation->getEvenement();

        $accompHtml = '';
        foreach ($participation->getAccompagnants() as $i => $acc) {
            $accompHtml .= '<tr><td style="padding:6px 12px;border-bottom:1px solid #e8ede9;color:#5a6e5f;font-size:14px">'
                . ($i + 1) . '. ' . htmlspecialchars($acc->getPrenom() . ' ' . $acc->getNom()) . '</td></tr>';
        }

        $html = $this->buildEmailLayout(
            'Participation confirmée !',
            '
            <p style="font-size:16px;color:#2d2d2d;margin:0 0 8px">Bonjour <strong>' . htmlspecialchars($user->getPrenom() . ' ' . $user->getNom()) . '</strong>,</p>
            <p style="font-size:15px;color:#5a6e5f;margin:0 0 24px">Votre participation a été <strong style="color:#27ae60">confirmée</strong> avec succès !</p>

            <!-- Code card -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#1a3a24;border-radius:12px;margin-bottom:24px">
                <tr><td style="padding:24px;text-align:center;background:#1a3a24;border-radius:12px">
                    <p style="margin:0 0 6px;color:#fffade;font-size:13px;text-transform:uppercase;letter-spacing:1.5px;font-weight:600">Votre code de participation</p>
                    <p style="margin:0;font-family:\'Courier New\',monospace;font-size:32px;font-weight:700;color:#fffade;letter-spacing:4px">' . htmlspecialchars($participation->getCodeParticipation()) . '</p>
                    <p style="margin:8px 0 0;color:#a8c5b0;font-size:12px">Conservez ce code, il vous sera demandé à l\'entrée</p>
                </td></tr>
            </table>

            <!-- Confirmed badge -->
            <table cellpadding="0" cellspacing="0" style="margin-bottom:20px">
                <tr><td style="background:#d4edda;color:#155724;padding:8px 16px;border-radius:20px;font-size:13px;font-weight:600">
                    ✅ Confirmé
                </td></tr>
            </table>

            <!-- Event details -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f8f5;border-radius:12px;margin-bottom:24px">
                <tr><td style="padding:20px">
                    <p style="margin:0 0 4px;font-family:\'Playfair Display\',Georgia,serif;font-size:20px;font-weight:700;color:#1a3a24">' . htmlspecialchars($evt->getTitre()) . '</p>
                    <table cellpadding="0" cellspacing="0" style="margin-top:12px">
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;width:30px;vertical-align:top">📅</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . $evt->getDateDebut()?->format('d/m/Y') . ' — ' . $evt->getDateFin()?->format('d/m/Y') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;vertical-align:top">⏰</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . $evt->getHoraireDebut()?->format('H:i') . ' – ' . $evt->getHoraireFin()?->format('H:i') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;vertical-align:top">📍</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . htmlspecialchars($evt->getLieu() ?? '') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;vertical-align:top">👥</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . $participation->getTotalPersonnes() . ' personne(s)</td>
                        </tr>
                    </table>
                </td></tr>
            </table>

            ' . ($accompHtml ? '
            <p style="font-size:14px;font-weight:600;color:#1a3a24;margin:0 0 8px">Accompagnants :</p>
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;background:#fafcfa;border-radius:8px;overflow:hidden">
                ' . $accompHtml . '
            </table>' : '') . '

            <p style="font-size:13px;color:#8a9a8e;text-align:center;margin:0">À bientôt à l\'événement !</p>
            '
        );

        // Generate PDF tickets
        $pdfContent = $this->generateTicketsPdf($participation);

        $email = (new Email())
            ->from('FIRMA <firmaagritech@gmail.com>')
            ->to($user->getEmail())
            ->subject('FIRMA — Participation confirmée : ' . $evt->getTitre())
            ->html($html)
            ->attach($pdfContent, 'Tickets_FIRMA_' . $participation->getCodeParticipation() . '.pdf', 'application/pdf');

        $this->mailer->send($email);
    }

    private function getTicketBaseUrl(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $base = $request ? $request->getSchemeAndHttpHost() : 'http://127.0.0.1:8000';

        if (PHP_OS_FAMILY === 'Windows' && $request && in_array($request->getHost(), ['127.0.0.1', 'localhost', '::1'])) {
            $ipOutput = shell_exec('ipconfig');
            if ($ipOutput && preg_match_all('/IPv4[^:]*:\s*([\d.]+)/', $ipOutput, $matches)) {
                foreach ($matches[1] as $ip) {
                    if (str_starts_with($ip, '127.') || str_starts_with($ip, '169.254.')) continue;
                    if (str_starts_with($ip, '192.168.56.')) continue;
                    if (preg_match('/^192\.168\.(1[3-9]\d|2\d\d)\./', $ip)) continue;
                    $base = $request->getScheme() . '://' . $ip . ':' . $request->getPort();
                    break;
                }
            }
        }

        return $base . '/ticket/';
    }

    private function generateQrSvg(string $data): string
    {
        $builder = new Builder(
            writer: new SvgWriter(),
            data: $data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 120,
            margin: 6,
            foregroundColor: new Color(26, 58, 36),
            backgroundColor: new Color(255, 255, 255),
        );

        return 'data:image/svg+xml;base64,' . base64_encode($builder->build()->getString());
    }

    // ═══════════════════════════════════════════
    //  PDF ticket generation (same structure as client-side)
    // ═══════════════════════════════════════════
    private function generateTicketsPdf(Participation $participation): string
    {
        $evt  = $participation->getEvenement();
        $user = $participation->getUtilisateur();

        $ticketBaseUrl = $this->getTicketBaseUrl();
        $cards = '';

        // Main participant ticket
        $cards .= $this->buildTicketCardHtml(
            $evt,
            $user->getPrenom() . ' ' . $user->getNom(),
            'Participant principal',
            $participation->getCodeParticipation(),
            'Participant',
            $ticketBaseUrl
        );

        // Accompagnant tickets
        foreach ($participation->getAccompagnants() as $i => $acc) {
            $cards .= $this->buildTicketCardHtml(
                $evt,
                $acc->getPrenom() . ' ' . $acc->getNom(),
                'Accompagnant #' . ($i + 1),
                $acc->getCodeAccompagnant(),
                'Accompagnant',
                $ticketBaseUrl
            );
        }

        $html = '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:DejaVu Sans,Helvetica,Arial,sans-serif;font-size:12px;color:#2d2d2d;padding:20px}
.tkt-card{background:#fff;border:2px dashed #1a3a24;border-radius:16px;padding:28px 24px 24px;margin-bottom:20px;position:relative;overflow:hidden;page-break-inside:avoid}
.tkt-top-bar{height:8px;background:linear-gradient(90deg,#1a3a24,#2d6b3f,#3e8a52);margin:-28px -24px 16px -24px;border-radius:14px 14px 0 0}
.tkt-header{margin-bottom:16px}
.tkt-header table{width:100%}
.tkt-event-name{font-size:18px;font-weight:700;color:#1a3a24;margin:0 0 4px}
.tkt-event-org{font-size:12px;color:#5a6e5f;margin:0}
.tkt-badge{background:#1a3a24;color:#fffade;font-size:10px;font-weight:700;padding:4px 12px;border-radius:20px;text-transform:uppercase;letter-spacing:1px}
.tkt-body table{width:100%}
.tkt-person-label{font-size:10px;color:#8a9a8e;text-transform:uppercase;letter-spacing:.5px;margin:0 0 2px}
.tkt-person{font-size:16px;font-weight:600;color:#2d2d2d;margin:0 0 10px}
.tkt-detail-row{padding:4px 0;font-size:12px;color:#2d2d2d}
.tkt-detail-icon{color:#5a6e5f;font-size:12px;padding-right:6px;vertical-align:middle}
.tkt-qr-box{text-align:center}
.tkt-qr-box img{border:2px solid #1a3a24;border-radius:8px;padding:4px;width:120px;height:120px}
.tkt-code-box{margin-top:16px;background:#f4f8f5;border-radius:12px;padding:14px;text-align:center}
.tkt-code-label{font-size:10px;color:#5a6e5f;text-transform:uppercase;letter-spacing:1px;margin:0 0 4px}
.tkt-code{font-family:DejaVu Sans Mono,Courier New,monospace;font-size:22px;font-weight:700;color:#1a3a24;letter-spacing:3px;margin:0}
.tkt-divider{border:none;border-top:1px dashed #c8d6cb;margin:16px 0}
.tkt-footer{font-size:11px;color:#8a9a8e}
.tkt-footer table{width:100%}
</style></head><body>' . $cards . '</body></html>';

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function buildTicketCardHtml(
        Evenement $evt,
        string $personName,
        string $personLabel,
        string $code,
        string $ticketType,
        string $ticketBaseUrl,
    ): string {
        $e = fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $qrSvg = $this->generateQrSvg($ticketBaseUrl . $code);

        $dateStr = $evt->getDateDebut()?->format('d/m/Y') ?? '';
        if ($evt->getDateFin() && $evt->getDateFin()->format('Y-m-d') !== $evt->getDateDebut()?->format('Y-m-d')) {
            $dateStr .= ' — ' . $evt->getDateFin()->format('d/m/Y');
        }
        $timeStr = ($evt->getHoraireDebut()?->format('H:i') ?? '') . ' — ' . ($evt->getHoraireFin()?->format('H:i') ?? '');
        $locStr  = $e($evt->getLieu() ?? '');
        if ($evt->getAdresse()) {
            $locStr .= ' — ' . $e($evt->getAdresse());
        }

        return '
<div class="tkt-card">
    <div class="tkt-top-bar"></div>
    <div class="tkt-header">
        <table><tr>
            <td style="vertical-align:top">
                <p class="tkt-event-name">' . $e($evt->getTitre()) . '</p>
                ' . ($evt->getOrganisateur() ? '<p class="tkt-event-org">Par ' . $e($evt->getOrganisateur()) . '</p>' : '') . '
            </td>
            <td style="text-align:right;vertical-align:top;width:120px">
                <span class="tkt-badge">' . $e($ticketType) . '</span>
            </td>
        </tr></table>
    </div>
    <div class="tkt-body">
        <table><tr>
            <td style="vertical-align:top;width:60%">
                <p class="tkt-person-label">' . $e($personLabel) . '</p>
                <p class="tkt-person">' . $e($personName) . '</p>
                <div class="tkt-detail-row"><span class="tkt-detail-icon">📅</span> ' . $dateStr . '</div>
                <div class="tkt-detail-row"><span class="tkt-detail-icon">⏰</span> ' . $timeStr . '</div>
                <div class="tkt-detail-row"><span class="tkt-detail-icon">📍</span> ' . $locStr . '</div>
            </td>
            <td class="tkt-qr-box" style="vertical-align:middle;text-align:center;width:40%">
                <img src="' . $qrSvg . '" alt="QR" />
            </td>
        </tr></table>
    </div>
    <div class="tkt-code-box">
        <p class="tkt-code-label">Code ' . $e($ticketType) . '</p>
        <p class="tkt-code">' . $e($code) . '</p>
    </div>
    <hr class="tkt-divider">
    <div class="tkt-footer">
        <table><tr>
            <td>FIRMA — Événements & Salons</td>
            <td style="text-align:right">Présentez ce ticket à l\'entrée</td>
        </tr></table>
    </div>
</div>';
    }

    // ═══════════════════════════════════════════
    //  EMAIL 3 — Participation modifiée + nouveaux tickets
    // ═══════════════════════════════════════════
    private function sendModificationEmail(Participation $participation): void
    {
        $user = $participation->getUtilisateur();
        $evt  = $participation->getEvenement();

        $accompHtml = '';
        foreach ($participation->getAccompagnants() as $i => $acc) {
            $accompHtml .= '<tr><td style="padding:6px 12px;border-bottom:1px solid #e8ede9;color:#5a6e5f;font-size:14px">'
                . ($i + 1) . '. ' . htmlspecialchars($acc->getPrenom() . ' ' . $acc->getNom()) . '</td></tr>';
        }

        $html = $this->buildEmailLayout(
            'Participation modifiée',
            '
            <p style="font-size:16px;color:#2d2d2d;margin:0 0 8px">Bonjour <strong>' . htmlspecialchars($user->getPrenom() . ' ' . $user->getNom()) . '</strong>,</p>
            <p style="font-size:15px;color:#5a6e5f;margin:0 0 24px">Votre participation a été <strong style="color:#2980b9">modifiée</strong> avec succès. Vous trouverez vos nouveaux tickets en pièce jointe.</p>

            <!-- Code card -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#1a3a24;border-radius:12px;margin-bottom:24px">
                <tr><td style="padding:24px;text-align:center;background:#1a3a24;border-radius:12px">
                    <p style="margin:0 0 6px;color:#fffade;font-size:13px;text-transform:uppercase;letter-spacing:1.5px;font-weight:600">Votre code de participation</p>
                    <p style="margin:0;font-family:\'Courier New\',monospace;font-size:32px;font-weight:700;color:#fffade;letter-spacing:4px">' . htmlspecialchars($participation->getCodeParticipation()) . '</p>
                </td></tr>
            </table>

            <!-- Modified badge -->
            <table cellpadding="0" cellspacing="0" style="margin-bottom:20px">
                <tr><td style="background:#d1ecf1;color:#0c5460;padding:8px 16px;border-radius:20px;font-size:13px;font-weight:600">
                    ✏️ Modification effectuée
                </td></tr>
            </table>

            <!-- Event details -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f8f5;border-radius:12px;margin-bottom:24px">
                <tr><td style="padding:20px">
                    <p style="margin:0 0 4px;font-family:\'Playfair Display\',Georgia,serif;font-size:20px;font-weight:700;color:#1a3a24">' . htmlspecialchars($evt->getTitre()) . '</p>
                    <table cellpadding="0" cellspacing="0" style="margin-top:12px">
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;width:30px;vertical-align:top">📅</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . $evt->getDateDebut()?->format('d/m/Y') . ' — ' . $evt->getDateFin()?->format('d/m/Y') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;vertical-align:top">⏰</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . $evt->getHoraireDebut()?->format('H:i') . ' – ' . $evt->getHoraireFin()?->format('H:i') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;vertical-align:top">📍</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . htmlspecialchars($evt->getLieu() ?? '') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;vertical-align:top">👥</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . $participation->getTotalPersonnes() . ' personne(s) (vous + ' . $participation->getNombreAccompagnants() . ' accompagnant(s))</td>
                        </tr>
                    </table>
                </td></tr>
            </table>

            ' . ($accompHtml ? '
            <p style="font-size:14px;font-weight:600;color:#1a3a24;margin:0 0 8px">Accompagnants :</p>
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;background:#fafcfa;border-radius:8px;overflow:hidden">
                ' . $accompHtml . '
            </table>' : '') . '

            <p style="font-size:13px;color:#8a9a8e;text-align:center;margin:0">Vos nouveaux tickets sont en pièce jointe de cet email.</p>
            '
        );

        // Generate updated PDF tickets
        $pdfContent = $this->generateTicketsPdf($participation);

        $email = (new Email())
            ->from('FIRMA <firmaagritech@gmail.com>')
            ->to($user->getEmail())
            ->subject('FIRMA — Participation modifiée : ' . $evt->getTitre())
            ->html($html)
            ->attach($pdfContent, 'Tickets_FIRMA_' . $participation->getCodeParticipation() . '.pdf', 'application/pdf');

        $this->mailer->send($email);
    }

    // ═══════════════════════════════════════════
    //  HTML email layout wrapper
    // ═══════════════════════════════════════════
    private function buildEmailLayout(string $title, string $bodyContent): string
    {
        return '<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#eef2ef;font-family:\'Plus Jakarta Sans\',\'Segoe UI\',Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#eef2ef;padding:32px 16px">
    <tr><td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%">

            <!-- Header -->
            <tr><td style="background:#1a3a24;padding:24px 32px;border-radius:12px 12px 0 0;text-align:center">
                <p style="margin:0;font-family:\'Playfair Display\',Georgia,serif;font-size:28px;font-weight:700;color:#fffade;letter-spacing:.5px">FIRMA</p>
                <p style="margin:4px 0 0;font-size:12px;color:#a8c5b0;text-transform:uppercase;letter-spacing:2px">Événements & Salons</p>
            </td></tr>

            <!-- Title bar -->
            <tr><td style="background:#20452c;padding:14px 32px;text-align:center">
                <p style="margin:0;font-size:16px;font-weight:600;color:#fffade">' . htmlspecialchars($title) . '</p>
            </td></tr>

            <!-- Body -->
            <tr><td style="background:#ffffff;padding:32px;border-radius:0 0 12px 12px">
                ' . $bodyContent . '
            </td></tr>

            <!-- Footer -->
            <tr><td style="padding:20px 32px;text-align:center">
                <p style="margin:0;font-size:12px;color:#8a9a8e">© ' . date('Y') . ' FIRMA — Tous droits réservés</p>
                <p style="margin:4px 0 0;font-size:11px;color:#aab5ac">Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
            </td></tr>

        </table>
    </td></tr>
</table>
</body>
</html>';
    }

    /**
     * Met à jour une participation (accompagnants + commentaire).
     *
     * @param Accompagnant[] $newAccompagnants
     */
    public function update(
        Participation $participation,
        int $newNbAccompagnants,
        array $newAccompagnants = [],
        ?string $commentaire = null,
    ): Participation {
        $oldTotal = $participation->getTotalPersonnes();
        $newTotal = 1 + $newNbAccompagnants;
        $diff     = $newTotal - $oldTotal;

        // Ajuster les places si le nombre change
        if ($diff > 0) {
            if (!$this->evenementRepo->reserverPlaces($participation->getEvenement()->getIdEvenement(), $diff)) {
                throw new \RuntimeException('Pas assez de places disponibles pour cet ajustement.');
            }
        } elseif ($diff < 0) {
            $this->evenementRepo->libererPlaces($participation->getEvenement()->getIdEvenement(), abs($diff));
        }

        // Remplacer les accompagnants
        foreach ($participation->getAccompagnants()->toArray() as $old) {
            $participation->removeAccompagnant($old);
        }
        foreach ($newAccompagnants as $acc) {
            $acc->setParticipation($participation);
            // Generate code for new accompagnants (needed for tickets)
            if (!$acc->getCodeAccompagnant()) {
                $acc->setCodeAccompagnant(Accompagnant::genererCode());
            }
            $participation->addAccompagnant($acc);
        }

        $participation->setNombreAccompagnants($newNbAccompagnants);
        $participation->setCommentaire($commentaire);

        $this->em->flush();

        // Send modification confirmation email with new tickets (only if confirmed)
        if ($participation->getStatut() === 'confirme') {
            try {
                $this->sendModificationEmail($participation);
            } catch (\Throwable $e) {
                error_log('FIRMA Modification Email FAILED: ' . $e->getMessage());
            }
        }

        return $participation;
    }

    /** Annule une participation et libère les places. */
    public function annuler(Participation $participation): void
    {
        $total = $participation->getTotalPersonnes();
        $evtId = $participation->getEvenement()->getIdEvenement();

        $this->em->remove($participation);
        $this->em->flush();

        $this->evenementRepo->libererPlaces($evtId, $total);
    }

    // ── Lecture ──

    public function getById(int $id): ?Participation
    {
        return $this->participationRepo->find($id);
    }

    public function getByCode(string $code): ?Participation
    {
        return $this->participationRepo->findByCode($code);
    }

    /** Participations actives d'un utilisateur. */
    public function getByUser(int $userId): array
    {
        return $this->participationRepo->findActiveByUser($userId);
    }

    /** Annule toutes les participations actives d'un événement. */
    public function cancelAllForEvent(int $evenementId): int
    {
        return $this->participationRepo->cancelAllByEvent($evenementId);
    }

    /** Participations d'un événement (admin). */
    public function getByEvenement(int $evenementId): array
    {
        return $this->participationRepo->findByEvenement($evenementId);
    }

    /** Détails complets (participation + user) pour l'admin. */
    public function getParticipantsDetails(int $evenementId): array
    {
        return $this->participationRepo->findParticipantsDetailsByEvent($evenementId);
    }

    public function isUserAlreadyParticipating(int $userId, int $evenementId): bool
    {
        return $this->participationRepo->isUserAlreadyParticipating($userId, $evenementId);
    }

    public function findByUserAndEvent(int $userId, int $evenementId): ?Participation
    {
        return $this->participationRepo->findByUserAndEvent($userId, $evenementId);
    }

    /** Nombre de participations confirmées pour un événement. */
    public function countConfirmedByEvent(int $evenementId): int
    {
        return $this->participationRepo->countConfirmedByEvent($evenementId);
    }

    /** Total des personnes (participants + accompagnants confirmés). */
    public function countTotalPersonnesByEvent(int $evenementId): int
    {
        return $this->participationRepo->countTotalPersonnesByEvent($evenementId);
    }

    // ═══════════════════════════════════════════
    //  EMAIL 3 — Événement modifié
    // ═══════════════════════════════════════════

    /**
     * Notifie tous les participants actifs qu'un événement a été modifié.
     */
    public function notifyEventModified(Evenement $evt): void
    {
        $participations = $this->participationRepo->findActiveByEvent($evt->getIdEvenement());

        foreach ($participations as $participation) {
            try {
                $this->sendEventModifiedEmail($participation);
            } catch (\Throwable $e) {
                // Continue sending to other participants even if one fails
            }
        }
    }

    private function sendEventModifiedEmail(Participation $participation): void
    {
        $user = $participation->getUtilisateur();
        $evt  = $participation->getEvenement();

        $html = $this->buildEmailLayout(
            'Événement mis à jour',
            '
            <p style="font-size:16px;color:#2d2d2d;margin:0 0 8px">Bonjour <strong>' . htmlspecialchars($user->getPrenom() . ' ' . $user->getNom()) . '</strong>,</p>
            <p style="font-size:15px;color:#5a6e5f;margin:0 0 24px">L\'événement auquel vous participez a été <strong style="color:#e67e22">modifié</strong>. Voici les nouveaux détails :</p>

            <!-- Updated badge -->
            <table cellpadding="0" cellspacing="0" style="margin-bottom:20px">
                <tr><td style="background:#fef3e2;color:#e67e22;padding:8px 16px;border-radius:20px;font-size:13px;font-weight:600">
                    🔄 Détails mis à jour
                </td></tr>
            </table>

            <!-- Event card -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f8f5;border-radius:12px;margin-bottom:24px">
                <tr><td style="padding:20px">
                    <p style="margin:0 0 4px;font-family:\'Playfair Display\',Georgia,serif;font-size:20px;font-weight:700;color:#1a3a24">' . htmlspecialchars($evt->getTitre()) . '</p>
                    ' . ($evt->getDescription() ? '<p style="margin:8px 0 12px;font-size:14px;color:#5a6e5f">' . htmlspecialchars($evt->getDescription()) . '</p>' : '') . '
                    <table cellpadding="0" cellspacing="0" style="margin-top:12px">
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;width:30px;vertical-align:top">📅</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . $evt->getDateDebut()?->format('d/m/Y') . ' — ' . $evt->getDateFin()?->format('d/m/Y') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;vertical-align:top">⏰</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . $evt->getHoraireDebut()?->format('H:i') . ' – ' . $evt->getHoraireFin()?->format('H:i') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;vertical-align:top">📍</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . htmlspecialchars($evt->getLieu() ?? '') . ($evt->getAdresse() ? ' — ' . htmlspecialchars($evt->getAdresse()) : '') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;vertical-align:top">🏷️</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . htmlspecialchars($evt->getOrganisateur() ?? '') . '</td>
                        </tr>
                    </table>
                </td></tr>
            </table>

            <p style="font-size:14px;color:#5a6e5f;margin:0 0 8px;text-align:center">Votre participation reste valide. Aucune action n\'est requise de votre part.</p>
            <p style="font-size:13px;color:#8a9a8e;text-align:center;margin:0">Si vous avez des questions, n\'hésitez pas à nous contacter.</p>
            '
        );

        $email = (new Email())
            ->from('FIRMA <firmaagritech@gmail.com>')
            ->to($user->getEmail())
            ->subject('FIRMA — Événement modifié : ' . $evt->getTitre())
            ->html($html);

        $this->mailer->send($email);
    }

    // ═══════════════════════════════════════════
    //  EMAIL 4 — Événement annulé
    // ═══════════════════════════════════════════

    /**
     * Notifie tous les participants actifs qu'un événement a été annulé.
     */
    public function notifyEventCancelled(Evenement $evt): void
    {
        $participations = $this->participationRepo->findActiveByEvent($evt->getIdEvenement());

        foreach ($participations as $participation) {
            try {
                $this->sendEventCancelledEmail($participation);
            } catch (\Throwable $e) {
                // Continue sending to other participants even if one fails
            }
        }
    }

    private function sendEventCancelledEmail(Participation $participation): void
    {
        $user = $participation->getUtilisateur();
        $evt  = $participation->getEvenement();

        $html = $this->buildEmailLayout(
            'Événement annulé',
            '
            <p style="font-size:16px;color:#2d2d2d;margin:0 0 8px">Bonjour <strong>' . htmlspecialchars($user->getPrenom() . ' ' . $user->getNom()) . '</strong>,</p>
            <p style="font-size:15px;color:#5a6e5f;margin:0 0 24px">Nous avons le regret de vous informer que l\'événement suivant a été <strong style="color:#e74c3c">annulé</strong>.</p>

            <!-- Cancelled badge -->
            <table cellpadding="0" cellspacing="0" style="margin-bottom:20px">
                <tr><td style="background:#fde8e8;color:#e74c3c;padding:8px 16px;border-radius:20px;font-size:13px;font-weight:600">
                    ❌ Événement annulé
                </td></tr>
            </table>

            <!-- Event card -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f8f5;border-radius:12px;margin-bottom:24px">
                <tr><td style="padding:20px">
                    <p style="margin:0 0 4px;font-family:\'Playfair Display\',Georgia,serif;font-size:20px;font-weight:700;color:#1a3a24;text-decoration:line-through">' . htmlspecialchars($evt->getTitre()) . '</p>
                    <table cellpadding="0" cellspacing="0" style="margin-top:12px">
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;width:30px;vertical-align:top">📅</td>
                            <td style="padding:4px 0;color:#999;font-size:14px;text-decoration:line-through">' . $evt->getDateDebut()?->format('d/m/Y') . ' — ' . $evt->getDateFin()?->format('d/m/Y') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;vertical-align:top">📍</td>
                            <td style="padding:4px 0;color:#999;font-size:14px;text-decoration:line-through">' . htmlspecialchars($evt->getLieu() ?? '') . '</td>
                        </tr>
                    </table>
                </td></tr>
            </table>

            <!-- Apology message -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#fff8f0;border-left:4px solid #e67e22;border-radius:0 8px 8px 0;margin-bottom:24px">
                <tr><td style="padding:16px 20px">
                    <p style="margin:0 0 8px;font-size:15px;font-weight:600;color:#2d2d2d">Nous sommes sincèrement désolés</p>
                    <p style="margin:0;font-size:14px;color:#5a6e5f;line-height:1.6">Nous comprenons votre déception et nous nous excusons pour ce désagrément. Notre équipe travaille activement pour organiser de nouveaux événements qui, nous l\'espérons, sauront vous satisfaire. Restez connecté pour découvrir nos prochaines dates !</p>
                </td></tr>
            </table>

            <p style="font-size:13px;color:#8a9a8e;text-align:center;margin:0">Votre participation a été automatiquement annulée. Merci pour votre compréhension.</p>
            '
        );

        $email = (new Email())
            ->from('FIRMA <firmaagritech@gmail.com>')
            ->to($user->getEmail())
            ->subject('FIRMA — Événement annulé : ' . $evt->getTitre())
            ->html($html);

        $this->mailer->send($email);
    }
}
