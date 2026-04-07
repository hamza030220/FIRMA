<?php

namespace App\Service\Event;

use App\Entity\Event\Accompagnant;
use App\Entity\Event\Evenement;
use App\Entity\Event\Participation;
use App\Entity\User\Utilisateur;
use App\Repository\Event\EvenementRepository;
use App\Repository\Event\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
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
        $this->em->flush();

        // Email 2 : confirmation avec le code
        try {
            $this->sendConfirmedEmail($participation);
        } catch (\Throwable $e) {
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
        $confirmUrl = $this->urlGenerator->generate('user_participation_confirm', [
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
            ->from('selminaama73@gmail.com')
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

        $email = (new Email())
            ->from('selminaama73@gmail.com')
            ->to($user->getEmail())
            ->subject('FIRMA — Participation confirmée : ' . $evt->getTitre())
            ->html($html);

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
            $participation->addAccompagnant($acc);
        }

        $participation->setNombreAccompagnants($newNbAccompagnants);
        $participation->setCommentaire($commentaire);

        $this->em->flush();

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
}
