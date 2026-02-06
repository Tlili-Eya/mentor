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
use App\Entity\Parcours;
use App\Repository\ParcoursRepository;

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


    // BackController.php
#[Route('/parcours', name: 'parcours')]
public function parcours(ParcoursRepository $parcoursRepository): Response
{
    $parcours = $parcoursRepository->findAll();
    return $this->render('back/courses.html.twig', [
        'parcours' => $parcours,
    ]);
}

#[Route('/update-parcours/{id}', name: 'update_parcours', methods: ['POST'])]
public function updateParcours(Request $request, Parcours $parcours, EntityManagerInterface $entityManager): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    
    // Mettre à jour les champs de base
    $parcours->setTitre($data['titre'] ?? $parcours->getTitre());
    $parcours->setTypeParcours($data['type_parcours'] ?? $parcours->getTypeParcours());
    $parcours->setDescription($data['description'] ?? $parcours->getDescription());
    
    // Mettre à jour les champs spécifiques
    if (isset($data['etablissement'])) {
        $parcours->setEtablissement($data['etablissement']);
    }
    if (isset($data['diplome'])) {
        $parcours->setDiplome($data['diplome']);
    }
    if (isset($data['specialite'])) {
        $parcours->setSpecialite($data['specialite']);
    }
    if (isset($data['entreprise'])) {
        $parcours->setEntreprise($data['entreprise']);
    }
    if (isset($data['poste'])) {
        $parcours->setPoste($data['poste']);
    }
    if (isset($data['type_contrat'])) {
        $parcours->setTypeContrat($data['type_contrat']);
    }
    
    // Mettre à jour les dates
    if (isset($data['date_debut']) && !empty($data['date_debut'])) {
        try {
            $parcours->setDateDebut(new \DateTime($data['date_debut']));
        } catch (\Exception $e) {
            // Gérer l'erreur si nécessaire
        }
    }
    
    if (isset($data['date_fin']) && !empty($data['date_fin'])) {
        try {
            $parcours->setDateFin(new \DateTime($data['date_fin']));
        } catch (\Exception $e) {
            // Gérer l'erreur si nécessaire
        }
    }
    
    $entityManager->flush();

    return new JsonResponse(['status' => 'Parcours mis à jour avec succès']);
}

#[Route('/delete-parcours/{id}', name: 'delete_parcours', methods: ['DELETE'])]
public function deleteParcours(Parcours $parcours, EntityManagerInterface $entityManager): JsonResponse
{
    $entityManager->remove($parcours);
    $entityManager->flush();

    return new JsonResponse(['status' => 'Parcours supprimé avec succès']);
}
}
