<?php

namespace App\Controller;

use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin', name: 'back_')]
final class BackController extends AbstractController
{
    #[Route('/', name: 'home')]
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
    public function instructors(): Response
    {
        return $this->render('back/instructors.html.twig');
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

    #[Route('/administrateur', name: 'administrateur')]
    public function administrateur(UtilisateurRepository $utilisateurRepository): Response
    {
        $utilisateurs = $utilisateurRepository->findAll();
        
        $countActif = 0;
        $countInactif = 0;
        $registrations = [];

        foreach ($utilisateurs as $user) {
            // Status Logic
            $status = strtolower($user->getStatus() ?? '');
            if ($status === 'actif') {
                $countActif++;
            } elseif ($status === 'desactiver' || $status === 'blocked') {
                $countInactif++;
            }

            // Registration Date Logic
            $date = $user->getDateInscription();
            if ($date) {
                $dateString = $date->format('Y-m-d');
                if (!isset($registrations[$dateString])) {
                    $registrations[$dateString] = 0;
                }
                $registrations[$dateString]++;
            }
        }

        // Sort by date
        ksort($registrations);

        return $this->render('back/administrateur.html.twig', [
            'utilisateurs' => $utilisateurs,
            'countActif' => $countActif,
            'countInactif' => $countInactif,
            'registrationDates' => array_keys($registrations),
            'registrationCounts' => array_values($registrations),
        ]);
    }
}