<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
#[Route('/', name: 'front_')]
class FrontController extends AbstractController
{
    #[Route('affiche', name: 'affiche')]
    public function affiche(): Response
    {
        return $this->render('front/affiche.html.twig');
    }

    #[Route('home', name: 'home')]
    public function home(): Response
    {
        return $this->render('front/home.html.twig');
    }

    #[Route('about', name: 'about')]
    public function about(): Response
    {
        return $this->render('front/about.html.twig');
    }

    #[Route('parcours', name: 'parcours')]
    public function parcours(\Symfony\Component\HttpFoundation\Request $request, \App\Repository\ParcoursRepository $parcoursRepository, \Doctrine\ORM\EntityManagerInterface $entityManager): Response
    {
        $newParcours = new \App\Entity\Parcours();
        $form = $this->createForm(\App\Form\ParcoursType::class, $newParcours, ['user' => $this->getUser()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newParcours->setDateCreation(new \DateTime());
            $user = $this->getUser();
            if ($user) {
                $newParcours->setUtilisateur($user);
            }
            $entityManager->persist($newParcours);
            $entityManager->flush();

            $this->addFlash('success', 'Votre parcours a été ajouté avec succès !');
            return $this->redirectToRoute('front_parcours');
        }

        return $this->render('front/parcours.html.twig', [
            'parcoursList' => $parcoursRepository->findBy([], ['id' => 'DESC']),
            'form' => $form->createView(),
        ]);
    }

    #[Route('mesparcours', name: 'mesparcours')]
    public function mesparcours(\App\Repository\ParcoursRepository $parcoursRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $parcoursList = $parcoursRepository->findBy(['utilisateur' => $user], ['id' => 'DESC']);

        return $this->render('front/mesparcours.html.twig', [
            'parcoursList' => $parcoursList,
        ]);
    }

    #[Route('course-details', name: 'course_details')]
    public function courseDetails(): Response
    {
        return $this->render('front/course-details.html.twig');
    }

    #[Route('instructor-profile', name: 'instructor_profile')]
    public function instructorProfile(): Response
    {
        return $this->render('front/instructor-profile.html.twig');
    }

    #[Route('events', name: 'events')]
    public function events(): Response
    {
        return $this->render('front/events.html.twig');
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
