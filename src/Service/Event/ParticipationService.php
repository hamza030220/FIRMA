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
use Endroid\QrCode\Writer\PngWriter;
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

    /** GÃ©nÃ¨re un token HMAC Ã  partir de l'id et du code (pas de colonne DB). */
    public function generateToken(Participation $participation): string
    {
        return hash_hmac('sha256', $participation->getIdParticipation() . '|' . $participation->getCodeParticipation(), self::HMAC_SECRET);
    }

    /** VÃ©rifie un token HMAC. */
    public function verifyToken(Participation $participation, string $token): bool
    {
        return hash_equals($this->generateToken($participation), $token);
    }

    /**
     * Inscrit un utilisateur Ã  un Ã©vÃ©nement avec ses accompagnants.
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
        $userId = $utilisateur->getId();
        $evtId  = $evenement->getIdEvenement();
        if (null === $userId || null === $evtId) {
            throw new \RuntimeException('Utilisateur ou Ã©vÃ©nement non persistÃ©.');
        }

        // VÃ©rifier doublon
        if ($this->participationRepo->isUserAlreadyParticipating($userId, $evtId)) {
            throw new \RuntimeException('Vous Ãªtes dÃ©jÃ  inscrit Ã  cet Ã©vÃ©nement.');
        }

        // RÃ©server les places (1 participant + accompagnants)
        $totalPlaces = 1 + $nbAccompagnants;
        if (!$this->evenementRepo->reserverPlaces($evtId, $totalPlaces)) {
            throw new \RuntimeException('Pas assez de places disponibles.');
        }

        // CrÃ©er la participation
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

    /** Confirme une participation (EN_ATTENTE â†’ CONFIRME) et envoie le code par email. */
    public function confirmer(Participation $participation): void
    {
        if ($participation->getStatut() !== 'en_attente') {
            throw new \RuntimeException('Cette participation ne peut pas Ãªtre confirmÃ©e.');
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
            // Le statut est mis Ã  jour mÃªme si l'email Ã©choue
            // Le statut est mis Ã  jour mÃªme si l'email Ã©choue
        }
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    //  EMAIL 1 â€” Demande de confirmation
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    private function sendPendingEmail(Participation $participation): void
    {
        $user = $participation->getUtilisateur();
        $evt  = $participation->getEvenement();
        if (null === $user || null === $evt || null === $user->getEmail()) {
            return;
        }

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
            <p style="font-size:15px;color:#5a6e5f;margin:0 0 24px">Votre demande de participation a Ã©tÃ© enregistrÃ©e. Veuillez confirmer en cliquant sur le bouton ci-dessous.</p>

            <!-- Event card -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f8f5;border-radius:12px;margin-bottom:24px">
                <tr><td style="padding:20px">
                    <p style="margin:0 0 4px;font-family:\'Playfair Display\',Georgia,serif;font-size:20px;font-weight:700;color:#1a3a24">' . htmlspecialchars((string) $evt->getTitre()) . '</p>
                    <table cellpadding="0" cellspacing="0" style="margin-top:12px">
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;width:30px;vertical-align:top">ðŸ“…</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . $evt->getDateDebut()?->format('d/m/Y') . ' â€” ' . $evt->getDateFin()?->format('d/m/Y') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;vertical-align:top">â°</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . $evt->getHoraireDebut()?->format('H:i') . ' â€“ ' . $evt->getHoraireFin()?->format('H:i') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;vertical-align:top">ðŸ“</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . htmlspecialchars($evt->getLieu() ?? '') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;vertical-align:top">ðŸ‘¥</td>
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
                    â³ En attente de confirmation
                </td></tr>
            </table>

            <!-- CTA button -->
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr><td align="center" style="padding:8px 0 24px">
                    <a href="' . htmlspecialchars($confirmUrl) . '" style="display:inline-block;background:#20452c;color:#ffffff;padding:14px 40px;border-radius:8px;font-size:16px;font-weight:600;text-decoration:none;letter-spacing:.3px">
                        âœ… Confirmer ma participation
                    </a>
                </td></tr>
            </table>

            <p style="font-size:13px;color:#8a9a8e;text-align:center;margin:0">Votre code de participation vous sera envoyÃ© aprÃ¨s confirmation.</p>
            '
        );

        $email = (new Email())
            ->from('FIRMA <firmaagritech@gmail.com>')
            ->to($user->getEmail())
            ->subject('FIRMA â€” Confirmez votre participation : ' . $evt->getTitre())
            ->html($html);

        $this->mailer->send($email);
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    //  EMAIL 2 â€” Participation confirmÃ©e + code
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    private function sendConfirmedEmail(Participation $participation): void
    {
        $user = $participation->getUtilisateur();
        $evt  = $participation->getEvenement();
        if (null === $user || null === $evt || null === $user->getEmail()) {
            return;
        }

        $accompHtml = '';
        foreach ($participation->getAccompagnants() as $i => $acc) {
            $accompHtml .= '<tr><td style="padding:6px 12px;border-bottom:1px solid #e8ede9;color:#5a6e5f;font-size:14px">'
                . ($i + 1) . '. ' . htmlspecialchars($acc->getPrenom() . ' ' . $acc->getNom()) . '</td></tr>';
        }

        $html = $this->buildEmailLayout(
            'Participation confirmÃ©e !',
            '
            <p style="font-size:16px;color:#2d2d2d;margin:0 0 8px">Bonjour <strong>' . htmlspecialchars($user->getPrenom() . ' ' . $user->getNom()) . '</strong>,</p>
            <p style="font-size:15px;color:#5a6e5f;margin:0 0 24px">Votre participation a Ã©tÃ© <strong style="color:#27ae60">confirmÃ©e</strong> avec succÃ¨s !</p>

            <!-- Code card -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#1a3a24;border-radius:12px;margin-bottom:24px">
                <tr><td style="padding:24px;text-align:center;background:#1a3a24;border-radius:12px">
                    <p style="margin:0 0 6px;color:#fffade;font-size:13px;text-transform:uppercase;letter-spacing:1.5px;font-weight:600">Votre code de participation</p>
                    <p style="margin:0;font-family:\'Courier New\',monospace;font-size:32px;font-weight:700;color:#fffade;letter-spacing:4px">' . htmlspecialchars((string) $participation->getCodeParticipation()) . '</p>
                    <p style="margin:8px 0 0;color:#a8c5b0;font-size:12px">Conservez ce code, il vous sera demandÃ© Ã  l\'entrÃ©e</p>
                </td></tr>
            </table>

            <!-- Confirmed badge -->
            <table cellpadding="0" cellspacing="0" style="margin-bottom:20px">
                <tr><td style="background:#d4edda;color:#155724;padding:8px 16px;border-radius:20px;font-size:13px;font-weight:600">
                    âœ… ConfirmÃ©
                </td></tr>
            </table>

            <!-- Event details -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f8f5;border-radius:12px;margin-bottom:24px">
                <tr><td style="padding:20px">
                    <p style="margin:0 0 4px;font-family:\'Playfair Display\',Georgia,serif;font-size:20px;font-weight:700;color:#1a3a24">' . htmlspecialchars((string) $evt->getTitre()) . '</p>
                    <table cellpadding="0" cellspacing="0" style="margin-top:12px">
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;width:30px;vertical-align:top">ðŸ“…</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . $evt->getDateDebut()?->format('d/m/Y') . ' â€” ' . $evt->getDateFin()?->format('d/m/Y') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;vertical-align:top">â°</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . $evt->getHoraireDebut()?->format('H:i') . ' â€“ ' . $evt->getHoraireFin()?->format('H:i') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;vertical-align:top">ðŸ“</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . htmlspecialchars($evt->getLieu() ?? '') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;vertical-align:top">ðŸ‘¥</td>
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

            <p style="font-size:13px;color:#8a9a8e;text-align:center;margin:0">Ã€ bientÃ´t Ã  l\'Ã©vÃ©nement !</p>
            '
        );

        // Generate PDF tickets
        $pdfContent = $this->generateTicketsPdf($participation);

        $email = (new Email())
            ->from('FIRMA <firmaagritech@gmail.com>')
            ->to($user->getEmail())
            ->subject('FIRMA â€” Participation confirmÃ©e : ' . $evt->getTitre())
            ->html($html)
            ->attach($pdfContent, 'Tickets_FIRMA_' . $participation->getCodeParticipation() . '.pdf', 'application/pdf');

        $this->mailer->send($email);
    }

    private function getTicketBaseUrl(): string
    {
        // 1) PrioritÃ© Ã  la config explicite (recommandÃ© pour scan mobile)
        //    DÃ©finir TICKET_BASE_URL dans .env.local, ex: TICKET_BASE_URL=http://192.168.1.42:8000
        $envBase = $_ENV['TICKET_BASE_URL'] ?? $_SERVER['TICKET_BASE_URL'] ?? getenv('TICKET_BASE_URL');
        if (is_string($envBase) && $envBase !== '') {
            return rtrim($envBase, '/') . '/ticket/';
        }

        // 2) Fall back to current request host (only same-network devices will reach it)
        $request = $this->requestStack->getCurrentRequest();
        $base = $request ? $request->getSchemeAndHttpHost() : 'http://127.0.0.1:8000';

        return $base . '/ticket/';
    }

    private function generateQrSvg(string $data): string
    {
        $builder = new Builder(
            writer: new PngWriter(),
            data: $data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 240,
            margin: 8,
            foregroundColor: new Color(26, 58, 36),
            backgroundColor: new Color(255, 255, 255),
        );

        return 'data:image/png;base64,' . base64_encode($builder->build()->getString());
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    //  PDF ticket generation (same structure as client-side)
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    private function generateTicketsPdf(Participation $participation): string
    {
        $evt  = $participation->getEvenement();
        $user = $participation->getUtilisateur();
        if (null === $evt || null === $user) {
            return '';
        }

        $ticketBaseUrl = $this->getTicketBaseUrl();
        $cards = '';

        // Main participant ticket
        $cards .= $this->buildTicketCardHtml(
            $evt,
            $user->getPrenom() . ' ' . $user->getNom(),
            'Participant principal',
            (string) $participation->getCodeParticipation(),
            'Participant',
            $ticketBaseUrl
        );

        // Accompagnant tickets
        foreach ($participation->getAccompagnants() as $i => $acc) {
            $cards .= $this->buildTicketCardHtml(
                $evt,
                $acc->getPrenom() . ' ' . $acc->getNom(),
                'Accompagnant #' . ($i + 1),
                (string) $acc->getCodeAccompagnant(),
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
        $e = fn(?string $s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        $qrSvg = $this->generateQrSvg($ticketBaseUrl . $code);

        $dateStr = $evt->getDateDebut()?->format('d/m/Y') ?? '';
        if ($evt->getDateFin() && $evt->getDateFin()->format('Y-m-d') !== $evt->getDateDebut()?->format('Y-m-d')) {
            $dateStr .= ' â€” ' . $evt->getDateFin()->format('d/m/Y');
        }
        $timeStr = ($evt->getHoraireDebut()?->format('H:i') ?? '') . ' â€” ' . ($evt->getHoraireFin()?->format('H:i') ?? '');
        $locStr  = $e($evt->getLieu() ?? '');
        if ($evt->getAdresse()) {
            $locStr .= ' â€” ' . $e($evt->getAdresse());
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
                <div class="tkt-detail-row"><span class="tkt-detail-icon">ðŸ“…</span> ' . $dateStr . '</div>
                <div class="tkt-detail-row"><span class="tkt-detail-icon">â°</span> ' . $timeStr . '</div>
                <div class="tkt-detail-row"><span class="tkt-detail-icon">ðŸ“</span> ' . $locStr . '</div>
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
            <td>FIRMA â€” Ã‰vÃ©nements & Salons</td>
            <td style="text-align:right">PrÃ©sentez ce ticket Ã  l\'entrÃ©e</td>
        </tr></table>
    </div>
</div>';
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    //  EMAIL 3 â€” Participation modifiÃ©e + nouveaux tickets
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    private function sendModificationEmail(Participation $participation): void
    {
        $user = $participation->getUtilisateur();
        $evt  = $participation->getEvenement();
        if (null === $user || null === $evt || null === $user->getEmail()) {
            return;
        }

        $accompHtml = '';
        foreach ($participation->getAccompagnants() as $i => $acc) {
            $accompHtml .= '<tr><td style="padding:6px 12px;border-bottom:1px solid #e8ede9;color:#5a6e5f;font-size:14px">'
                . ($i + 1) . '. ' . htmlspecialchars($acc->getPrenom() . ' ' . $acc->getNom()) . '</td></tr>';
        }

        $html = $this->buildEmailLayout(
            'Participation modifiÃ©e',
            '
            <p style="font-size:16px;color:#2d2d2d;margin:0 0 8px">Bonjour <strong>' . htmlspecialchars($user->getPrenom() . ' ' . $user->getNom()) . '</strong>,</p>
            <p style="font-size:15px;color:#5a6e5f;margin:0 0 24px">Votre participation a Ã©tÃ© <strong style="color:#2980b9">modifiÃ©e</strong> avec succÃ¨s. Vous trouverez vos nouveaux tickets en piÃ¨ce jointe.</p>

            <!-- Code card -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#1a3a24;border-radius:12px;margin-bottom:24px">
                <tr><td style="padding:24px;text-align:center;background:#1a3a24;border-radius:12px">
                    <p style="margin:0 0 6px;color:#fffade;font-size:13px;text-transform:uppercase;letter-spacing:1.5px;font-weight:600">Votre code de participation</p>
                    <p style="margin:0;font-family:\'Courier New\',monospace;font-size:32px;font-weight:700;color:#fffade;letter-spacing:4px">' . htmlspecialchars((string) $participation->getCodeParticipation()) . '</p>
                </td></tr>
            </table>

            <!-- Modified badge -->
            <table cellpadding="0" cellspacing="0" style="margin-bottom:20px">
                <tr><td style="background:#d1ecf1;color:#0c5460;padding:8px 16px;border-radius:20px;font-size:13px;font-weight:600">
                    âœï¸ Modification effectuÃ©e
                </td></tr>
            </table>

            <!-- Event details -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f8f5;border-radius:12px;margin-bottom:24px">
                <tr><td style="padding:20px">
                    <p style="margin:0 0 4px;font-family:\'Playfair Display\',Georgia,serif;font-size:20px;font-weight:700;color:#1a3a24">' . htmlspecialchars((string) $evt->getTitre()) . '</p>
                    <table cellpadding="0" cellspacing="0" style="margin-top:12px">
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;width:30px;vertical-align:top">ðŸ“…</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . $evt->getDateDebut()?->format('d/m/Y') . ' â€” ' . $evt->getDateFin()?->format('d/m/Y') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;vertical-align:top">â°</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . $evt->getHoraireDebut()?->format('H:i') . ' â€“ ' . $evt->getHoraireFin()?->format('H:i') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;vertical-align:top">ðŸ“</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . htmlspecialchars($evt->getLieu() ?? '') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;vertical-align:top">ðŸ‘¥</td>
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

            <p style="font-size:13px;color:#8a9a8e;text-align:center;margin:0">Vos nouveaux tickets sont en piÃ¨ce jointe de cet email.</p>
            '
        );

        // Generate updated PDF tickets
        $pdfContent = $this->generateTicketsPdf($participation);

        $email = (new Email())
            ->from('FIRMA <firmaagritech@gmail.com>')
            ->to($user->getEmail())
            ->subject('FIRMA â€” Participation modifiÃ©e : ' . $evt->getTitre())
            ->html($html)
            ->attach($pdfContent, 'Tickets_FIRMA_' . $participation->getCodeParticipation() . '.pdf', 'application/pdf');

        $this->mailer->send($email);
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    //  HTML email layout wrapper
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
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
                <p style="margin:4px 0 0;font-size:12px;color:#a8c5b0;text-transform:uppercase;letter-spacing:2px">Ã‰vÃ©nements & Salons</p>
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
                <p style="margin:0;font-size:12px;color:#8a9a8e">Â© ' . date('Y') . ' FIRMA â€” Tous droits rÃ©servÃ©s</p>
                <p style="margin:4px 0 0;font-size:11px;color:#aab5ac">Cet email a Ã©tÃ© envoyÃ© automatiquement, merci de ne pas y rÃ©pondre.</p>
            </td></tr>

        </table>
    </td></tr>
</table>
</body>
</html>';
    }

    /**
     * Met Ã  jour une participation (accompagnants + commentaire).
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

        $evt   = $participation->getEvenement();
        $evtId = $evt?->getIdEvenement();
        if (null === $evtId) {
            throw new \RuntimeException('Ã‰vÃ©nement non persistÃ©.');
        }

        // Ajuster les places si le nombre change
        if ($diff > 0) {
            if (!$this->evenementRepo->reserverPlaces($evtId, $diff)) {
                throw new \RuntimeException('Pas assez de places disponibles pour cet ajustement.');
            }
        } elseif ($diff < 0) {
            $this->evenementRepo->libererPlaces($evtId, abs($diff));
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

    /** Annule une participation et libÃ¨re les places. */
    public function annuler(Participation $participation): void
    {
        $total = $participation->getTotalPersonnes();
        $evtId = $participation->getEvenement()?->getIdEvenement();

        $this->em->remove($participation);
        $this->em->flush();

        if (null !== $evtId) {
            $this->evenementRepo->libererPlaces($evtId, $total);
        }
    }

    // â”€â”€ Lecture â”€â”€

    public function getById(int $id): ?Participation
    {
        return $this->participationRepo->find($id);
    }

    public function getByCode(string $code): ?Participation
    {
        return $this->participationRepo->findByCode($code);
    }

    /**
     * Participations actives d'un utilisateur.
     *
     * @return Participation[]
     */
    public function getByUser(int $userId): array
    {
        return $this->participationRepo->findActiveByUser($userId);
    }

    /** Annule toutes les participations actives d'un Ã©vÃ©nement. */
    public function cancelAllForEvent(int $evenementId): int
    {
        return $this->participationRepo->cancelAllByEvent($evenementId);
    }

    /**
     * Participations d'un Ã©vÃ©nement (admin).
     *
     * @return Participation[]
     */
    public function getByEvenement(int $evenementId): array
    {
        return $this->participationRepo->findByEvenement($evenementId);
    }

    /**
     * DÃ©tails complets (participation + user) pour l'admin.
     *
     * @return array<int, mixed>
     */
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

    /** Nombre de participations confirmÃ©es pour un Ã©vÃ©nement. */
    public function countConfirmedByEvent(int $evenementId): int
    {
        return $this->participationRepo->countConfirmedByEvent($evenementId);
    }

    /** Total des personnes (participants + accompagnants confirmÃ©s). */
    public function countTotalPersonnesByEvent(int $evenementId): int
    {
        return $this->participationRepo->countTotalPersonnesByEvent($evenementId);
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    //  EMAIL 3 â€” Ã‰vÃ©nement modifiÃ©
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

    /**
     * Notifie tous les participants actifs qu'un Ã©vÃ©nement a Ã©tÃ© modifiÃ©.
     */
    public function notifyEventModified(Evenement $evt): void
    {
        $evtId = $evt->getIdEvenement();
        if (null === $evtId) {
            return;
        }
        $participations = $this->participationRepo->findActiveByEvent($evtId);

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
        if (null === $user || null === $evt || null === $user->getEmail()) {
            return;
        }

        $html = $this->buildEmailLayout(
            'Ã‰vÃ©nement mis Ã  jour',
            '
            <p style="font-size:16px;color:#2d2d2d;margin:0 0 8px">Bonjour <strong>' . htmlspecialchars($user->getPrenom() . ' ' . $user->getNom()) . '</strong>,</p>
            <p style="font-size:15px;color:#5a6e5f;margin:0 0 24px">L\'Ã©vÃ©nement auquel vous participez a Ã©tÃ© <strong style="color:#e67e22">modifiÃ©</strong>. Voici les nouveaux dÃ©tails :</p>

            <!-- Updated badge -->
            <table cellpadding="0" cellspacing="0" style="margin-bottom:20px">
                <tr><td style="background:#fef3e2;color:#e67e22;padding:8px 16px;border-radius:20px;font-size:13px;font-weight:600">
                    ðŸ”„ DÃ©tails mis Ã  jour
                </td></tr>
            </table>

            <!-- Event card -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f8f5;border-radius:12px;margin-bottom:24px">
                <tr><td style="padding:20px">
                    <p style="margin:0 0 4px;font-family:\'Playfair Display\',Georgia,serif;font-size:20px;font-weight:700;color:#1a3a24">' . htmlspecialchars((string) $evt->getTitre()) . '</p>
                    ' . ($evt->getDescription() ? '<p style="margin:8px 0 12px;font-size:14px;color:#5a6e5f">' . htmlspecialchars($evt->getDescription()) . '</p>' : '') . '
                    <table cellpadding="0" cellspacing="0" style="margin-top:12px">
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;width:30px;vertical-align:top">ðŸ“…</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . $evt->getDateDebut()?->format('d/m/Y') . ' â€” ' . $evt->getDateFin()?->format('d/m/Y') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;vertical-align:top">â°</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . $evt->getHoraireDebut()?->format('H:i') . ' â€“ ' . $evt->getHoraireFin()?->format('H:i') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;vertical-align:top">ðŸ“</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . htmlspecialchars($evt->getLieu() ?? '') . ($evt->getAdresse() ? ' â€” ' . htmlspecialchars($evt->getAdresse()) : '') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;vertical-align:top">ðŸ·ï¸</td>
                            <td style="padding:4px 0;color:#2d2d2d;font-size:14px">' . htmlspecialchars($evt->getOrganisateur() ?? '') . '</td>
                        </tr>
                    </table>
                </td></tr>
            </table>

            <p style="font-size:14px;color:#5a6e5f;margin:0 0 8px;text-align:center">Votre participation reste valide. Aucune action n\'est requise de votre part.</p>
            <p style="font-size:13px;color:#8a9a8e;text-align:center;margin:0">Si vous avez des questions, n\'hÃ©sitez pas Ã  nous contacter.</p>
            '
        );

        $email = (new Email())
            ->from('FIRMA <firmaagritech@gmail.com>')
            ->to($user->getEmail())
            ->subject('FIRMA â€” Ã‰vÃ©nement modifiÃ© : ' . $evt->getTitre())
            ->html($html);

        $this->mailer->send($email);
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    //  EMAIL 4 â€” Ã‰vÃ©nement annulÃ©
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

    /**
     * Notifie tous les participants actifs qu'un Ã©vÃ©nement a Ã©tÃ© annulÃ©.
     */
    public function notifyEventCancelled(Evenement $evt): void
    {
        $evtId = $evt->getIdEvenement();
        if (null === $evtId) {
            return;
        }
        $participations = $this->participationRepo->findActiveByEvent($evtId);

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
        if (null === $user || null === $evt || null === $user->getEmail()) {
            return;
        }

        $html = $this->buildEmailLayout(
            'Ã‰vÃ©nement annulÃ©',
            '
            <p style="font-size:16px;color:#2d2d2d;margin:0 0 8px">Bonjour <strong>' . htmlspecialchars($user->getPrenom() . ' ' . $user->getNom()) . '</strong>,</p>
            <p style="font-size:15px;color:#5a6e5f;margin:0 0 24px">Nous avons le regret de vous informer que l\'Ã©vÃ©nement suivant a Ã©tÃ© <strong style="color:#e74c3c">annulÃ©</strong>.</p>

            <!-- Cancelled badge -->
            <table cellpadding="0" cellspacing="0" style="margin-bottom:20px">
                <tr><td style="background:#fde8e8;color:#e74c3c;padding:8px 16px;border-radius:20px;font-size:13px;font-weight:600">
                    âŒ Ã‰vÃ©nement annulÃ©
                </td></tr>
            </table>

            <!-- Event card -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f8f5;border-radius:12px;margin-bottom:24px">
                <tr><td style="padding:20px">
                    <p style="margin:0 0 4px;font-family:\'Playfair Display\',Georgia,serif;font-size:20px;font-weight:700;color:#1a3a24;text-decoration:line-through">' . htmlspecialchars((string) $evt->getTitre()) . '</p>
                    <table cellpadding="0" cellspacing="0" style="margin-top:12px">
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;width:30px;vertical-align:top">ðŸ“…</td>
                            <td style="padding:4px 0;color:#999;font-size:14px;text-decoration:line-through">' . $evt->getDateDebut()?->format('d/m/Y') . ' â€” ' . $evt->getDateFin()?->format('d/m/Y') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;color:#5a6e5f;font-size:14px;vertical-align:top">ðŸ“</td>
                            <td style="padding:4px 0;color:#999;font-size:14px;text-decoration:line-through">' . htmlspecialchars($evt->getLieu() ?? '') . '</td>
                        </tr>
                    </table>
                </td></tr>
            </table>

            <!-- Apology message -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#fff8f0;border-left:4px solid #e67e22;border-radius:0 8px 8px 0;margin-bottom:24px">
                <tr><td style="padding:16px 20px">
                    <p style="margin:0 0 8px;font-size:15px;font-weight:600;color:#2d2d2d">Nous sommes sincÃ¨rement dÃ©solÃ©s</p>
                    <p style="margin:0;font-size:14px;color:#5a6e5f;line-height:1.6">Nous comprenons votre dÃ©ception et nous nous excusons pour ce dÃ©sagrÃ©ment. Notre Ã©quipe travaille activement pour organiser de nouveaux Ã©vÃ©nements qui, nous l\'espÃ©rons, sauront vous satisfaire. Restez connectÃ© pour dÃ©couvrir nos prochaines dates !</p>
                </td></tr>
            </table>

            <p style="font-size:13px;color:#8a9a8e;text-align:center;margin:0">Votre participation a Ã©tÃ© automatiquement annulÃ©e. Merci pour votre comprÃ©hension.</p>
            '
        );

        $email = (new Email())
            ->from('FIRMA <firmaagritech@gmail.com>')
            ->to($user->getEmail())
            ->subject('FIRMA â€” Ã‰vÃ©nement annulÃ© : ' . $evt->getTitre())
            ->html($html);

        $this->mailer->send($email);
    }
}
