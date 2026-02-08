<?php

namespace App\Controller;

use App\Enum\Statutobj;
use App\Entity\Objectif;
use App\Entity\Programme;
use App\Form\ObjectifType;
use App\Repository\ObjectifRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/', name: 'front_')]
final class FrontController extends AbstractController
{
    #[Route('', name: 'home')]
    public function home(): Response
    {
        return $this->render('front/home.html.twig');
    }

    #[Route('about', name: 'about')]
    public function about(): Response
    {
        return $this->render('front/about.html.twig');
    }

    #[Route('courses', name: 'courses')]
    public function courses(): Response
    {
        return $this->render('front/courses.html.twig');
    }

    #[Route('course-details', name: 'course_details')]
    public function courseDetails(): Response
    {
        return $this->render('front/course-details.html.twig');
    }

    #[Route('instructors', name: 'instructors')]
    public function instructors(): Response
    {
        return $this->render('front/instructors.html.twig');
    }

    #[Route('instructor-profile', name: 'instructor_profile')]
    public function instructorProfile(): Response
    {
        return $this->render('front/instructor-profile.html.twig');
    }

  #[Route('/events', name: 'events')]
public function events(
    ObjectifRepository $objectifRepository,
    Request $request,
    EntityManagerInterface $entityManager
): Response
{
    // ===== TRI PHP =====
    $sort = $request->query->get('sort', 'datedebut'); // tri par défaut

    $orderBy = match ($sort) {
        'datefin'  => ['datefin' => 'ASC'],
        'statut'   => ['statut' => 'ASC'],
        'titre'    => ['titre' => 'ASC'],
        default    => ['datedebut' => 'ASC'],
    };

    $objectifs = $objectifRepository->findBy([], $orderBy);

    // ===== FORMULAIRE MODAL =====
    $objectif = new Objectif();
    $form = $this->createForm(ObjectifType::class, $objectif);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        // Création du programme associé
        $programme = new Programme();
        $programme->setTitre($objectif->getTitre());
        $programme->setDategeneration(new \DateTime());

        $entityManager->persist($programme);

        $objectif->setProgramme($programme);

        // Statut par défaut
        if (!$objectif->getStatut()) {
            $objectif->setStatut(Statutobj::EnCours);
        }

        $entityManager->persist($objectif);
        $entityManager->flush();

        $this->addFlash('success', 'Objectif créé avec succès !');

        return $this->redirectToRoute('front_events');
    }

    return $this->render('front/events.html.twig', [
        'objectifs' => $objectifs,
        'form' => $form->createView(),
        'showModal' => $form->isSubmitted() && !$form->isValid(),
    ]);
}


#[Route('/events/{id}', name: 'objectif_show', methods: ['GET'])]
public function show(Objectif $objectif): Response
{
    return $this->render('front/objectif_show.html.twig', [
        'objectif' => $objectif,
    ]);
}

#[Route('/events/{id}', name: 'objectif_delete', methods: ['POST'])]
public function delete(Request $request, Objectif $objectif, EntityManagerInterface $entityManager): Response
{
    if ($this->isCsrfTokenValid('delete' . $objectif->getId(), $request->request->get('_token'))) {
        // Supprime aussi le programme associé si tu veux (orphanRemoval le fait déjà)
        $entityManager->remove($objectif);
        $entityManager->flush();

        $this->addFlash('success', 'Objectif supprimé avec succès !');
    }

    return $this->redirectToRoute('front_events');
}
    #[Route('/programme/{id}', name: 'programme_show', methods: ['GET'])]
public function programmeShow(Programme $programme): Response
{
    return $this->render('front/programme_show.html.twig', [
        'programme' => $programme,
    ]);
}

    #[Route('pricing', name: 'pricing')]
    public function pricing(): Response
    {
        return $this->render('front/pricing.html.twig');
    }

    #[Route('privacy', name: 'privacy')]
    public function privacy(): Response
    {
        return $this->render('front/privacy.html.twig');
    }

    #[Route('terms', name: 'terms')]
    public function terms(): Response
    {
        return $this->render('front/terms.html.twig');
    }

    #[Route('blog', name: 'blog')]
    public function blog(): Response
    {
        return $this->render('front/blog.html.twig');
    }

    #[Route('blog-details', name: 'blog_details')]
    public function blogDetails(): Response
    {
        return $this->render('front/blog-details.html.twig');
    }

    #[Route('contact', name: 'contact')]
    public function contact(): Response
    {
        return $this->render('front/contact.html.twig');
    }

    #[Route('enroll', name: 'enroll')]
    public function enroll(): Response
    {
        return $this->render('front/enroll.html.twig');
    }

    #[Route('starter', name: 'starter')]
    public function starter(): Response
    {
        return $this->render('front/starter-page.html.twig');
    }

    #[Route('404', name: '404')]
    public function error404(): Response
    {
        return $this->render('front/404.html.twig');
    }
}