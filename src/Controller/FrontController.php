<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/', name: 'front_')]
final class FrontController extends AbstractController
{
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

    #[Route('authentifier', name: 'authentifier')]
    public function authentifier(): Response
    {
        return $this->render('front/authentifier.html.twig');
    }
     #[Route('base', name: 'base')]
    public function base(): Response
    {
        return $this->render('front/base.html.twig');
    }



/*
    ////////////////////users//////////////////////////
    #[Route('/authentifier', name: 'authentifier')]
    public function authentifier(AuthenticationUtils $authenticationUtils): Response
    {
        // Récupère l'erreur d'authentification si la connexion a échoué
        $error = $authenticationUtils->getLastAuthenticationError();

        // Dernier nom d'utilisateur saisi (pour pré-remplir le champ email)
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('front/authentifier.html.twig', [          // ← change en 'front/authentifier.html.twig' si besoin
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }
*/


}

