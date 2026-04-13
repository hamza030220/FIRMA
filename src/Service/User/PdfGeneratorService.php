<?php

namespace App\Service\User;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfGeneratorService
{
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Génère un PDF à partir d'un template Twig
     */
    public function generatePdf(string $template, array $data, string $filename): string
    {
        // Configuration de Dompdf
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        
        // Générer le HTML depuis Twig
        $html = $this->twig->render($template, $data);
        
        // Charger le HTML
        $dompdf->loadHtml($html);
        
        // Configuration du papier (A4 portrait)
        $dompdf->setPaper('A4', 'portrait');
        
        // Rendre le PDF
        $dompdf->render();
        
        // Créer le dossier s'il n'existe pas
        $pdfDir = __DIR__ . '/../../public/uploads/pdfs';
        if (!file_exists($pdfDir)) {
            mkdir($pdfDir, 0777, true);
        }
        
        // Sauvegarder le fichier
        $outputPath = $pdfDir . '/' . $filename;
        file_put_contents($outputPath, $dompdf->output());
        
        return $filename;
    }

    /**
     * Génère un PDF pour une maladie avec ses traitements
     */
    public function generateMaladiePdf($maladie, array $traitements): string
    {
        $filename = 'maladie_' . $maladie->getId() . '_' . date('Ymd_His') . '.pdf';
        
        return $this->generatePdf('pdf/maladie.html.twig', [
            'maladie' => $maladie,
            'traitements' => $traitements,
            'generated_at' => date('d/m/Y H:i:s')
        ], $filename);
    }

    /**
     * Génère un PDF de diagnostic personnalisé pour l'utilisateur
     */
    public function generateDiagnosticPdf($maladie, $traitement, string $userDescription): string
    {
        $filename = 'diagnostic_' . date('Ymd_His') . '.pdf';
        
        return $this->generatePdf('pdf/diagnostic.html.twig', [
            'maladie' => $maladie,
            'traitement' => $traitement,
            'userDescription' => $userDescription,
            'generated_at' => date('d/m/Y H:i:s')
        ], $filename);
    }
}