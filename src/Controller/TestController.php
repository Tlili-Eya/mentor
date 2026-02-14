<?php
// src/Controller/TestController.php

namespace App\Controller;

use App\Repository\ReferenceArticleRepository;
use App\Repository\CategorieArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route('/test-articles', name: 'test_articles')]
    public function testArticles(
        ReferenceArticleRepository $articleRepository,
        CategorieArticleRepository $categorieRepository
    ): Response {
        // 1. Récupérez TOUT
        $articles = $articleRepository->findAll();
        $categories = $categorieRepository->findAll();
        
        // 2. Dump pour voir
        dump('=== TEST CONTROLLER ===');
        dump('Articles count:', count($articles));
        dump('Categories count:', count($categories));
        
        foreach ($articles as $article) {
            dump([
                'id' => $article->getId(),
                'titre' => $article->getTitre(),
                'contenu' => substr($article->getContenu(), 0, 50) . '...',
                'published' => $article->isPublished(),
            ]);
        }
        
        // 3. Template ultra simple
        return $this->render('front/test/ultra_simple.html.twig', [
            'articles' => $articles,
            'categories' => $categories,
        ]);
    }
}