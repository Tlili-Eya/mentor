<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class PdfExportService
{
    private $twig;
    
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }
    
    public function generateArticlePdf($article): Response
    {
        // Configure Dompdf
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        
        // Rendre le HTML avec Twig
        $html = $this->twig->render('back/reference_article/pdf_template.html.twig', [
            'article' => $article
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Retourner la réponse PDF
        $output = $dompdf->output();
        
        $response = new Response($output);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 
            'attachment; filename="article_' . $article->getId() . '_' . date('Y-m-d') . '.pdf"');
        
        return $response;
    }
    public function generateTablePdf(array $data, string $filename): Response
{
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    
    $dompdf = new Dompdf($options);
    
    $html = $this->twig->render('pdf/plans_table.html.twig', $data);
    
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape'); // Paysage pour les tableaux larges
    $dompdf->render();
    
    $output = $dompdf->output();
    
    $response = new Response($output);
    $response->headers->set('Content-Type', 'application/pdf');
    $response->headers->set('Content-Disposition', 
        'attachment; filename="' . $filename . '.pdf"');
    
    return $response;
}
/**
 * Génère un PDF pour la liste des plans d'actions avec leurs articles
 */
public function generatePlansListPdf(array $plans, array $filters = []): Response
{
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);

    // Compter le nombre total d'articles
    $totalArticles = 0;
    foreach ($plans as $plan) {
        $totalArticles += $plan->getArticles()->count();
    }

    $html = $this->twig->render('pdf/plans_table.html.twig', [
        'plans' => $plans,
        'filters' => $filters, // ← Important : passer les filtres
        'date' => new \DateTime(),
        'title' => 'Liste des Plans d\'Actions', 
        'total_plans' => count($plans),
        'total_articles' => $totalArticles
    ]);

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    $filename = 'plans_actions_' . date('Y-m-d_H-i-s') . '.pdf';

    return new Response($dompdf->output(), 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"'
    ]);
}
}