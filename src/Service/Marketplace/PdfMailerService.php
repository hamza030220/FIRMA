<?php

namespace App\Service\Marketplace;

use App\Entity\Marketplace\Commande;
use App\Entity\Marketplace\Location;
use App\Entity\User\Utilisateur;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class PdfMailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private string $projectDir,
    ) {}

    /**
     * Reçu de paiement (Stripe) — commande équipements
     */
    public function sendRecuCommande(Commande $commande): void
    {
        $html = $this->twig->render('pdf/recu.html.twig', [
            'commande' => $commande,
            'logo_base64' => $this->getLogoBase64(),
        ]);

        $pdfContent = $this->generatePdf($html);
        $filename = 'Recu_FIRMA_' . $commande->getNumeroCommande() . '.pdf';

        $email = (new Email())
            ->from('FIRMA Marketplace <firmaagritech@gmail.com>')
            ->to($commande->getUtilisateur()->getEmail())
            ->subject('FIRMA — Reçu de paiement N° ' . $commande->getNumeroCommande())
            ->html($this->getEmailBody(
                $commande->getUtilisateur()->getPrenom(),
                'Votre paiement a été confirmé avec succès !',
                'Vous trouverez en pièce jointe le reçu de votre commande <strong>N° ' . $commande->getNumeroCommande() . '</strong>.',
                '#4aac33'
            ))
            ->attach($pdfContent, $filename, 'application/pdf');

        $this->mailer->send($email);
    }

    /**
     * Reçu de paiement (Stripe) — locations véhicules/terrains
     */
    public function sendRecuLocations(Utilisateur $user, array $locations): void
    {
        $html = $this->twig->render('pdf/recu_location.html.twig', [
            'user' => $user,
            'locations' => $locations,
            'logo_base64' => $this->getLogoBase64(),
        ]);

        $pdfContent = $this->generatePdf($html);
        $nums = array_map(fn(Location $l) => $l->getNumeroLocation(), $locations);
        $filename = 'Recu_Location_FIRMA_' . date('Ymd_His') . '.pdf';

        $email = (new Email())
            ->from('FIRMA Marketplace <firmaagritech@gmail.com>')
            ->to($user->getEmail())
            ->subject('FIRMA — Reçu de location(s) ' . implode(', ', $nums))
            ->html($this->getEmailBody(
                $user->getPrenom(),
                'Votre paiement de location a été confirmé !',
                'Vous trouverez en pièce jointe le reçu de vos <strong>' . count($locations) . ' location(s)</strong>.',
                '#4aac33'
            ))
            ->attach($pdfContent, $filename, 'application/pdf');

        $this->mailer->send($email);
    }

    /**
     * Alerte stock automatique — envoyée à l'admin quand le stock tombe sous le seuil après une commande
     */
    public function sendStockAlert(array $lowStockEquipements, Commande $commande): void
    {
        $html = $this->twig->render('pdf/alerte_stock.html.twig', [
            'equipements' => $lowStockEquipements,
            'commande' => $commande,
            'logo_base64' => $this->getLogoBase64(),
            'firma_base64' => $this->getFirmaBase64(),
        ]);

        $pdfContent = $this->generatePdf($html);
        $filename = 'Alerte_Stock_FIRMA_' . date('Ymd_His') . '.pdf';

        $count = count($lowStockEquipements);
        $email = (new Email())
            ->from('FIRMA Marketplace <firmaagritech@gmail.com>')
            ->to('hamza.slimani@esprit.tn')
            ->subject('⚠ FIRMA — Alerte stock critique (' . $count . ' équipement' . ($count > 1 ? 's' : '') . ')')
            ->html($this->getAdminEmailBody(
                '⚠ Alerte Stock Critique',
                $count . ' équipement' . ($count > 1 ? 's sont passés' : ' est passé')
                . ' en dessous du seuil d\'alerte suite à la commande <strong>' . $commande->getNumeroCommande() . '</strong>.'
                . '<br>Consultez le rapport PDF ci-joint pour les détails et contacts fournisseurs.',
                '#dc3545'
            ))
            ->attach($pdfContent, $filename, 'application/pdf');

        $this->mailer->send($email);
    }

    /**
     * Rapport d'analyse du stock — envoyé manuellement depuis l'admin
     */
    public function sendAnalyseStock(array $lowStockEquipements): void
    {
        // Build fournisseurs summary
        $fournisseursMap = [];
        foreach ($lowStockEquipements as $equip) {
            $f = $equip->getFournisseur();
            if ($f) {
                $fId = $f->getId();
                if (!isset($fournisseursMap[$fId])) {
                    $fournisseursMap[$fId] = ['entity' => $f, 'count' => 0];
                }
                $fournisseursMap[$fId]['count']++;
            }
        }

        $html = $this->twig->render('pdf/analyse_stock.html.twig', [
            'equipements' => $lowStockEquipements,
            'fournisseurs' => array_values($fournisseursMap),
            'logo_base64' => $this->getLogoBase64(),
            'firma_base64' => $this->getFirmaBase64(),
        ]);

        $pdfContent = $this->generatePdf($html);
        $filename = 'Analyse_Stock_FIRMA_' . date('Ymd_His') . '.pdf';

        $count = count($lowStockEquipements);
        $email = (new Email())
            ->from('FIRMA Marketplace <firmaagritech@gmail.com>')
            ->to('hamza.slimani@esprit.tn')
            ->subject('📊 FIRMA — Rapport d\'analyse de stock (' . $count . ' en alerte)')
            ->html($this->getAdminEmailBody(
                '📊 Rapport d\'Analyse de Stock',
                $count . ' équipement' . ($count > 1 ? 's sont' : ' est')
                . ' actuellement en dessous du seuil d\'alerte.'
                . '<br>Le rapport PDF ci-joint contient la liste complète avec les contacts fournisseurs.',
                '#e6a817'
            ))
            ->attach($pdfContent, $filename, 'application/pdf');

        $this->mailer->send($email);
    }

    /**
     * Compte-rendu (payer à la livraison) — équipements uniquement
     */
    public function sendCompteRendu(Commande $commande): void
    {
        $html = $this->twig->render('pdf/compte_rendu.html.twig', [
            'commande' => $commande,
            'logo_base64' => $this->getLogoBase64(),
        ]);

        $pdfContent = $this->generatePdf($html);
        $filename = 'CompteRendu_FIRMA_' . $commande->getNumeroCommande() . '.pdf';

        $email = (new Email())
            ->from('FIRMA Marketplace <firmaagritech@gmail.com>')
            ->to($commande->getUtilisateur()->getEmail())
            ->subject('FIRMA — Compte-rendu de livraison N° ' . $commande->getNumeroCommande())
            ->html($this->getEmailBody(
                $commande->getUtilisateur()->getPrenom(),
                'Votre commande a été enregistrée !',
                'Vous trouverez en pièce jointe le compte-rendu de livraison pour la commande <strong>N° ' . $commande->getNumeroCommande() . '</strong>.<br>'
                . 'Ce document devra être signé par vous et le livreur lors de la réception.',
                '#e6a817'
            ))
            ->attach($pdfContent, $filename, 'application/pdf');

        $this->mailer->send($email);
    }

    /**
     * Generate historique commande PDF — download (not emailed)
     */
    public function generateHistoriquePdf(Commande $commande): string
    {
        $html = $this->twig->render('pdf/historique_commande.html.twig', [
            'commande' => $commande,
            'logo_base64' => $this->getLogoBase64(),
            'firma_base64' => $this->getFirmaBase64(),
        ]);

        return $this->generatePdf($html);
    }

    private function generatePdf(string $html): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function getLogoBase64(): string
    {
        $logoPath = $this->projectDir . '/public/images/logofirma.jpg';
        if (file_exists($logoPath)) {
            $data = base64_encode(file_get_contents($logoPath));
            return 'data:image/jpeg;base64,' . $data;
        }
        return '';
    }

    private function getFirmaBase64(): string
    {
        $path = $this->projectDir . '/public/images/firma.png';
        if (file_exists($path)) {
            $data = base64_encode(file_get_contents($path));
            return 'data:image/png;base64,' . $data;
        }
        return '';
    }

    private function getAdminEmailBody(string $title, string $message, string $color): string
    {
        return <<<HTML
        <div style="font-family:Helvetica,Arial,sans-serif;max-width:560px;margin:0 auto;padding:30px 20px">
            <div style="text-align:center;margin-bottom:20px">
                <h1 style="color:#20452c;font-size:28px;margin:0">FIRMA</h1>
                <p style="color:#888;font-size:12px;margin:4px 0 0">Marketplace Agricole — Administration</p>
            </div>
            <div style="background:{$color};height:3px;border-radius:2px;margin-bottom:24px"></div>
            <h2 style="color:{$color};font-size:18px;margin:0 0 12px">{$title}</h2>
            <p style="font-size:13px;color:#555;line-height:1.6">{$message}</p>
            <div style="background:#f8f8f8;border-radius:8px;padding:14px;margin:20px 0;text-align:center">
                <p style="font-size:12px;color:#888;margin:0">📎 Le rapport PDF est joint à cet email</p>
            </div>
            <p style="font-size:12px;color:#888;margin-top:24px">
                — <strong style="color:#20452c">Système FIRMA</strong>
            </p>
            <div style="border-top:1px solid #eee;margin-top:24px;padding-top:12px;text-align:center">
                <p style="font-size:10px;color:#bbb">FIRMA Marketplace — Notification automatique</p>
            </div>
        </div>
        HTML;
    }

    private function getEmailBody(string $prenom, string $title, string $message, string $color): string
    {
        return <<<HTML
        <div style="font-family:Helvetica,Arial,sans-serif;max-width:560px;margin:0 auto;padding:30px 20px">
            <div style="text-align:center;margin-bottom:20px">
                <h1 style="color:#20452c;font-size:28px;margin:0">FIRMA</h1>
                <p style="color:#888;font-size:12px;margin:4px 0 0">Marketplace Agricole</p>
            </div>
            <div style="background:{$color};height:3px;border-radius:2px;margin-bottom:24px"></div>
            <p style="font-size:14px;color:#333">Bonjour <strong>{$prenom}</strong>,</p>
            <h2 style="color:{$color};font-size:18px;margin:16px 0 8px">{$title}</h2>
            <p style="font-size:13px;color:#555;line-height:1.6">{$message}</p>
            <div style="background:#f8f8f8;border-radius:8px;padding:14px;margin:20px 0;text-align:center">
                <p style="font-size:12px;color:#888;margin:0">📎 Le document PDF est joint à cet email</p>
            </div>
            <p style="font-size:12px;color:#888;margin-top:24px">
                Merci de votre confiance,<br>
                <strong style="color:#20452c">L'équipe FIRMA</strong>
            </p>
            <div style="border-top:1px solid #eee;margin-top:24px;padding-top:12px;text-align:center">
                <p style="font-size:10px;color:#bbb">FIRMA Marketplace — zusslimani001122@gmail.com</p>
            </div>
        </div>
        HTML;
    }
}
