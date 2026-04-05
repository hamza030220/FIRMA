<?php

namespace App\Controller\User;

use App\Repository\MaladieRepository;
use App\Repository\SolutionTraitementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/user')]
class UserMaladieController extends AbstractController
{
    #[Route('/maladies', name: 'user_maladie_index')]
    public function index(MaladieRepository $maladieRepository): Response
    {
        return $this->render('user/maladie/index.html.twig', [
            'maladies' => $maladieRepository->findAll(),
        ]);
    }

    #[Route('/maladie/{id}', name: 'user_maladie_show', requirements: ['id' => '\d+'])]
    public function show(int $id, MaladieRepository $maladieRepository): Response
    {
        $maladie = $maladieRepository->find($id);

        if (!$maladie) {
            throw $this->createNotFoundException('Maladie non trouvée.');
        }

        return $this->render('user/maladie/show.html.twig', [
            'maladie' => $maladie,
        ]);
    }

    #[Route('/maladie/feedback/{id}/{type}', name: 'user_feedback', requirements: ['id' => '\d+', 'type' => 'positif|negatif'], methods: ['POST'])]
    public function feedback(
        int $id,
        string $type,
        SolutionTraitementRepository $solutionRepo,
        EntityManagerInterface $em
    ): Response {
        $solution = $solutionRepo->find($id);

        if (!$solution) {
            throw $this->createNotFoundException('Solution non trouvée.');
        }

        if ($type === 'positif') {
            $solution->setFeedbackPositive($solution->getFeedbackPositive() + 1);
        } else {
            $solution->setFeedbackNegative($solution->getFeedbackNegative() + 1);
        }

        $solution->incrementUsageCount();
        $em->flush();

        $this->addFlash(
            'success',
            $type === 'positif'
                ? '👍 Merci pour votre retour positif !'
                : '👎 Merci pour votre retour !'
        );

        return $this->redirectToRoute('user_maladie_show', [
            'id' => $solution->getMaladie()->getId()
        ]);
    }

    #[Route('/maladie/{id}/pdf', name: 'user_maladie_pdf', requirements: ['id' => '\d+'])]
    public function pdf(int $id, MaladieRepository $maladieRepository): Response
    {
        $maladie = $maladieRepository->find($id);

        if (!$maladie) {
            throw $this->createNotFoundException('Maladie non trouvée.');
        }

        // Helper: encoder proprement pour éviter l'erreur iconv/dompdf
        $e = function (?string $str): string {
            if ($str === null) return '';

            $str = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
            $str = iconv('UTF-8', 'UTF-8//IGNORE', $str);

            return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        };

        $nl = function (?string $str) use ($e): string {
            return nl2br($e($str));
        };

        // Stats globales
        $totalUsages   = 0;
        $totalLikes    = 0;
        $totalDislikes = 0;

        foreach ($maladie->getSolutionTraitements() as $s) {
            $totalUsages   += $s->getUsageCount();
            $totalLikes    += $s->getFeedbackPositive();
            $totalDislikes += $s->getFeedbackNegative();
        }

        $tauxGlobal = $totalUsages > 0 ? round($totalLikes * 100 / $totalUsages) : 0;

        // Couleur gravité
        $graviteColors = [
            'critique' => ['bg' => '#fdeaea', 'text' => '#c62828', 'border' => '#e57373'],
            'eleve'    => ['bg' => '#fff3e0', 'text' => '#e65100', 'border' => '#ffb74d'],
            'moyen'    => ['bg' => '#e3f2fd', 'text' => '#1565c0', 'border' => '#64b5f6'],
            'faible'   => ['bg' => '#e8f5e9', 'text' => '#2e7d32', 'border' => '#81c784'],
        ];

        $gc   = $graviteColors[$maladie->getNiveauGravite()] ?? $graviteColors['moyen'];
        $date = (new \DateTime())->format('d/m/Y');

        // HTML XHTML plus stable pour dompdf
        $html = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; color: #212121; font-size: 11px; background: white; }

    .header { background: #0f5c2f; color: white; padding: 24px 32px; }
    .header-brand { font-size: 9px; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 6px; opacity: 0.7; }
    .header-title { font-size: 22px; font-weight: bold; margin-bottom: 4px; }
    .header-sci { font-style: italic; opacity: 0.8; font-size: 12px; }
    .header-date { font-size: 9px; opacity: 0.6; text-align: right; }
    .header-badge { display: inline-block; margin-top: 10px; padding: 4px 12px; background: rgba(255,255,255,0.2); border-radius: 12px; font-size: 10px; font-weight: bold; }

    .stats-bar { background: #f8f9fa; border-bottom: 2px solid #e0ebe0; padding: 12px 32px; }
    .stats-table { width: 100%; border-collapse: collapse; }
    .stats-table td { text-align: center; padding: 4px 12px; border-right: 1px solid #e0e0e0; }
    .stats-table td:last-child { border-right: none; }
    .stat-num { font-size: 18px; font-weight: bold; color: #0f5c2f; display: block; }
    .stat-num-green { color: #27ae60; }
    .stat-num-red { color: #e74c3c; }
    .stat-num-blue { color: #1e6f9f; }
    .stat-lbl { font-size: 8px; color: #757575; text-transform: uppercase; letter-spacing: 0.5px; }

    .content { padding: 20px 32px; }
    .gravite-banner { padding: 10px 16px; border-radius: 6px; border-left: 4px solid ' . $gc['border'] . '; background: ' . $gc['bg'] . '; color: ' . $gc['text'] . '; font-weight: bold; font-size: 11px; margin-bottom: 16px; }

    .section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #0f5c2f; border-bottom: 1.5px solid #0f5c2f; padding-bottom: 4px; margin-bottom: 8px; margin-top: 16px; }
    .section-content { background: #f8f9fa; border-radius: 6px; padding: 10px 12px; line-height: 1.6; color: #424242; font-size: 10.5px; }

    .traitement-card { border: 1px solid #e0e0e0; border-radius: 8px; margin-bottom: 14px; page-break-inside: avoid; }
    .traitement-head { background: #1e6f9f; color: white; padding: 9px 14px; font-weight: bold; font-size: 11px; border-radius: 7px 7px 0 0; }
    .traitement-body { padding: 12px 14px; background: white; border-radius: 0 0 7px 7px; }

    .field-lbl { font-size: 8.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #757575; margin-bottom: 3px; margin-top: 8px; }
    .field-val { font-size: 10px; color: #424242; line-height: 1.5; background: #f8f9fa; padding: 6px 8px; border-radius: 4px; }

    .trt-stats-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .trt-stats-table td { text-align: center; padding: 6px 4px; background: #f8f9fa; border: 1px solid #eeeeee; }
    .t-stat-num { font-size: 13px; font-weight: bold; color: #212121; display: block; }
    .t-stat-lbl { font-size: 7.5px; color: #757575; }

    .footer { margin-top: 24px; padding-top: 10px; border-top: 1px solid #e0e0e0; font-size: 8px; color: #bdbdbd; }
</style>
</head>
<body>

<div class="header">
    <table style="width:100%;border-collapse:collapse;">
        <tr>
            <td>
                <div class="header-brand">FIRMA — Plateforme Agricole</div>
                <div class="header-title">' . $e($maladie->getNom()) . '</div>
                ' . ($maladie->getNomScientifique() ? '<div class="header-sci">' . $e($maladie->getNomScientifique()) . '</div>' : '') . '
                <div class="header-badge">Fiche maladie complete</div>
            </td>
            <td style="text-align:right;vertical-align:top;">
                <div class="header-date">Genere le ' . $date . '</div>
            </td>
        </tr>
    </table>
</div>

<div class="stats-bar">
    <table class="stats-table">
        <tr>
            <td>
                <span class="stat-num stat-num-blue">' . count($maladie->getSolutionTraitements()) . '</span>
                <div class="stat-lbl">Traitements</div>
            </td>
            <td>
                <span class="stat-num">' . $totalUsages . '</span>
                <div class="stat-lbl">Utilisations</div>
            </td>
            <td>
                <span class="stat-num stat-num-green">' . $totalLikes . '</span>
                <div class="stat-lbl">Avis positifs</div>
            </td>
            <td>
                <span class="stat-num stat-num-red">' . $totalDislikes . '</span>
                <div class="stat-lbl">Avis negatifs</div>
            </td>
            <td>
                <span class="stat-num ' . ($tauxGlobal >= 70 ? 'stat-num-green' : ($tauxGlobal >= 40 ? 'stat-num-blue' : 'stat-num-red')) . '">' . $tauxGlobal . '%</span>
                <div class="stat-lbl">Taux de succes</div>
            </td>
        </tr>
    </table>
</div>

<div class="content">
    <div class="gravite-banner">
        Gravite : ' . $e(ucfirst($maladie->getNiveauGravite())) . '
        ' . ($maladie->getSaisonFrequente() ? ' &nbsp;|&nbsp; Saison : ' . $e($maladie->getSaisonFrequente()) : '') . '
    </div>

    ' . ($maladie->getDescription() ? '
    <div class="section-title">Description</div>
    <div class="section-content">' . $nl($maladie->getDescription()) . '</div>
    ' : '') . '

    ' . ($maladie->getSymptomes() ? '
    <div class="section-title">Symptomes</div>
    <div class="section-content">' . $nl($maladie->getSymptomes()) . '</div>
    ' : '') . '

    <div class="section-title">Traitements et Solutions</div>';

        if (count($maladie->getSolutionTraitements()) === 0) {
            $html .= '<div class="section-content">Aucun traitement disponible pour le moment.</div>';
        } else {
            foreach ($maladie->getSolutionTraitements() as $s) {

                $rate = $s->getUsageCount() > 0
                    ? round($s->getFeedbackPositive() * 100 / $s->getUsageCount())
                    : 0;

                $html .= '
    <div class="traitement-card">
        <div class="traitement-head">
            ' . $e($s->getTitre()) . '
            ' . ($s->getDureeTraitement() ? ' &nbsp;|&nbsp; Duree : ' . $e($s->getDureeTraitement()) : '') . '
        </div>

        <div class="traitement-body">
            ' . ($s->getSolution() ? '<div class="field-lbl">Solution</div><div class="field-val">' . $nl($s->getSolution()) . '</div>' : '') . '
            ' . ($s->getEtapes() ? '<div class="field-lbl">Etapes</div><div class="field-val">' . $nl($s->getEtapes()) . '</div>' : '') . '
            ' . ($s->getProduitsRecommandes() ? '<div class="field-lbl">Produits recommandes</div><div class="field-val">' . $nl($s->getProduitsRecommandes()) . '</div>' : '') . '
            ' . ($s->getConseilsPrevention() ? '<div class="field-lbl">Conseils de prevention</div><div class="field-val">' . $nl($s->getConseilsPrevention()) . '</div>' : '') . '

            <table class="trt-stats-table">
                <tr>
                    <td style="width:25%;">
                        <span class="t-stat-num">' . $s->getUsageCount() . '</span>
                        <div class="t-stat-lbl">Utilisations</div>
                    </td>
                    <td style="width:25%;">
                        <span class="t-stat-num">' . $s->getFeedbackPositive() . '</span>
                        <div class="t-stat-lbl">Positifs</div>
                    </td>
                    <td style="width:25%;">
                        <span class="t-stat-num">' . $s->getFeedbackNegative() . '</span>
                        <div class="t-stat-lbl">Negatifs</div>
                    </td>
                    <td style="width:25%;">
                        <span class="t-stat-num">' . $rate . '%</span>
                        <div class="t-stat-lbl">Succes</div>
                    </td>
                </tr>
            </table>

        </div>
    </div>';
            }
        }

        $html .= '
    <div class="footer">
        FIRMA — Plateforme Agricole | Fiche generee le ' . $date . ' | ' . $e($maladie->getNom()) . '
    </div>

</div>
</body>
</html>';

        // Dompdf options
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);

        // important pour éviter problème iconv
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'fiche-maladie-' . strtolower(str_replace(' ', '-', $maladie->getNom())) . '.pdf';

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }
}