<?php

namespace App\Controller;

use App\Repository\ReferenceArticleRepository;
use App\Repository\CategorieArticleRepository;
use App\Entity\ReferenceArticle;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArticlesFrontController extends AbstractController
{
    /**
     * Page de liste des articles (vue publique/enseignant)
     */
    #[Route('/articles', name: 'app_articles_front', methods: ['GET'])]
    public function index(
        Request $request,
        ReferenceArticleRepository $articleRepository,
        CategorieArticleRepository $categorieRepository
    ): Response {
        $search = $request->query->get('search', '');
        $categorieId = $request->query->get('categorie', '');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 9;

        $qb = $articleRepository->createQueryBuilder('a')
            ->leftJoin('a.categorie', 'c')
            ->where('a.published = :published OR a.published IS NULL')
            ->setParameter('published', true);

        if (!empty($search)) {
            $qb->andWhere('a.titre LIKE :search OR a.contenu LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if (!empty($categorieId)) {
            $qb->andWhere('c.id = :categorie')
               ->setParameter('categorie', $categorieId);
        }

        $qb->orderBy('a.createdAt', 'DESC');

        $total = count($qb->getQuery()->getResult());
        $articles = $qb->setFirstResult(($page - 1) * $limit)
                      ->setMaxResults($limit)
                      ->getQuery()
                      ->getResult();

        $totalPages = ceil($total / $limit);
        $categories = $categorieRepository->findAll();

        // Compter les articles récents (7 derniers jours)
        $recentArticlesCount = $articleRepository->countRecentArticles(7);

        return $this->render('front/enseignant/articles.html.twig', [
            'articles' => $articles,
            'categories' => $categories,
            'search' => $search,
            'categorie' => $categorieId,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'recentArticlesCount' => $recentArticlesCount,
        ]);
    }

    /**
     * Page de détail d'un article
     */
    #[Route('/article/{id}', name: 'app_article_detail', methods: ['GET'])]
    public function detail(ReferenceArticle $article): Response
    {
        if (!$article->isPublished() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException('Cet article n\'est pas disponible.');
        }

        return $this->render('front/enseignant/article_detail.html.twig', [
            'article' => $article,
        ]);
    }

    /**
     * Page articles pour enseignants (vue back office)
     */
    #[Route('/enseignant/articles', name: 'app_enseignant_articles', methods: ['GET'])]
    public function enseignantView(
    Request $request,
    ReferenceArticleRepository $articleRepository,
    CategorieArticleRepository $categorieRepository
): Response {
    $search = $request->query->get('search', '');
    $categorieId = $request->query->get('categorie', '');
    $page = max(1, $request->query->getInt('page', 1));
    $limit = 6;

    // REQUÊTE SIMPLIFIÉE POUR TEST
    $qb = $articleRepository->createQueryBuilder('a')
        ->leftJoin('a.categorie', 'c');
    
    // FILTRE published - Version robuste
    $qb->andWhere($qb->expr()->orX(
        $qb->expr()->eq('a.published', ':published'),
        $qb->expr()->isNull('a.published')
    ))
    ->setParameter('published', true);

    if (!empty($search)) {
        $qb->andWhere('a.titre LIKE :search OR a.contenu LIKE :search')
           ->setParameter('search', '%' . $search . '%');
    }

    if (!empty($categorieId)) {
        $qb->andWhere('c.id = :categorie')
           ->setParameter('categorie', $categorieId);
    }

    $qb->orderBy('a.createdAt', 'DESC');

    // DEBUG: Voir la requête SQL
    $query = $qb->getQuery();
    dump('SQL:', $query->getSQL());
    dump('Parameters:', $query->getParameters()->toArray());

    $total = count($query->getResult());
    $articles = $qb->setFirstResult(($page - 1) * $limit)
                  ->setMaxResults($limit)
                  ->getQuery()
                  ->getResult();

    dump('Articles trouvés:', count($articles));
    foreach ($articles as $article) {
        dump([
            'id' => $article->getId(),
            'titre' => $article->getTitre(),
            'published' => $article->isPublished(),
            'categorie' => $article->getCategorie() ? $article->getCategorie()->getId() : null,
        ]);
    }

    $totalPages = ceil($total / $limit);
    $categories = $categorieRepository->findAll();

    // Compter les articles récents
    try {
        $recentArticlesCount = $articleRepository->countRecentArticles(7);
    } catch (\Exception $e) {
        $recentArticlesCount = 0;
    }

   return $this->render('front/enseignant/articles.html.twig', [
    'articles' => $articles, // Envoie TOUS les articles
    'categories' => $categories,
    'search' => '',
    'categorieId' => '',
    'currentPage' => 1,
    'totalPages' => 1,
    'total' => count($articles),
    'recentArticlesCount' => count($articles), // Simple count
]);
}
}