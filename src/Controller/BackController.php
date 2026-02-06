<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Entity\Ressource;
use App\Repository\ProjetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin', name: 'back_')]
final class BackController extends AbstractController
{
    #[Route('', name: 'home')]
    public function home(): Response
    {
        return $this->render('back/home.html.twig');
    }

    #[Route('/about', name: 'about')]
    public function about(): Response
    {
        return $this->render('back/about.html.twig');
    }

    #[Route('/courses', name: 'courses')]
    public function courses(): Response
    {
        return $this->render('back/courses.html.twig');
    }

    #[Route('/course-details', name: 'course_details')]
    public function courseDetails(): Response
    {
        return $this->render('back/course-details.html.twig');
    }

    #[Route('/instructors', name: 'instructors')]
    public function instructors(ProjetRepository $projetRepository): Response
    {
        $projets = $projetRepository->findAll();
        return $this->render('back/instructors.html.twig', [
            'projets' => $projets,
        ]);
    }

    #[Route('/instructor-profile', name: 'instructor_profile')]
    public function instructorProfile(): Response
    {
        return $this->render('back/instructor-profile.html.twig');
    }

    #[Route('/events', name: 'events')]
    public function events(): Response
    {
        return $this->render('back/events.html.twig');
    }

    #[Route('/pricing', name: 'pricing')]
    public function pricing(): Response
    {
        return $this->render('back/pricing.html.twig');
    }

    #[Route('/blog', name: 'blog')]
    public function blog(): Response
    {
        return $this->render('back/blog.html.twig');
    }

    #[Route('/blog-details', name: 'blog_details')]
    public function blogDetails(): Response
    {
        return $this->render('back/blog-details.html.twig');
    }

    #[Route('/contact', name: 'contact')]
    public function contact(): Response
    {
        return $this->render('back/contact.html.twig');
    }

    #[Route('/update-projet/{id}', name: 'update_projet', methods: ['POST'])]
    public function updateProjet(Request $request, Projet $projet, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $projet->setTitre($data['titre'] ?? $projet->getTitre());
        $projet->setType($data['type'] ?? $projet->getType());
        $projet->setDescription($data['description'] ?? $projet->getDescription());
        $projet->setTechnologies($data['technologies'] ?? $projet->getTechnologies());
        $entityManager->flush();

        return new JsonResponse(['status' => 'Projet mis à jour avec succès']);
    }

    #[Route('/delete-projet/{id}', name: 'delete_projet', methods: ['DELETE'])]
    public function deleteProjet(Projet $projet, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($projet);
        $entityManager->flush();

        return new JsonResponse(['status' => 'Projet supprimé avec succès']);
    }

    #[Route('/update-ressource/{id}', name: 'update_ressource', methods: ['POST'])]
    public function updateRessource(Request $request, Ressource $ressource, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $ressource->setNom($data['nom'] ?? $ressource->getNom());
        $ressource->setDescription($data['description'] ?? $ressource->getDescription());
        $ressource->setUrlRessource($data['urlRessource'] ?? $ressource->getUrlRessource());
        $entityManager->flush();

        return new JsonResponse(['status' => 'Ressource mise à jour avec succès']);
    }

    #[Route('/delete-ressource/{id}', name: 'delete_ressource', methods: ['DELETE'])]
    public function deleteRessource(Ressource $ressource, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($ressource);
        $entityManager->flush();

        return new JsonResponse(['status' => 'Ressource supprimée avec succès']);
    }
}
