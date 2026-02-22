<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PreferenceController extends AbstractController
{
    #[Route('/preferences/save', name: 'app_preferences_save', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function save(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->json(['success' => false, 'message' => 'Utilisateur non trouvé'], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['success' => false, 'message' => 'Données invalides'], 400);
        }

        $user->setPreferences($data);
        $em->flush();

        return $this->json(['success' => true]);
    }
}
