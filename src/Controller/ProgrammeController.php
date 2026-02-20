<?php

namespace App\Controller;

use App\Entity\Motivation;
use App\Entity\Programme;
use App\Entity\Tache;
use App\Enum\Etat;
use App\Enum\Statutobj;

use App\Form\TacheType;

use App\Service\ObjectifStatusService;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/programme', name: 'front_programme_')]
class ProgrammeController extends AbstractController
{
    private ObjectifStatusService $objectifStatusService;

    public function __construct(ObjectifStatusService $objectifStatusService)
    {
        $this->objectifStatusService = $objectifStatusService;
    }

   private function updateProgrammeStats(Programme $programme, EntityManagerInterface $entityManager): void
{
    $taches = $programme->getTache()->toArray();
    usort($taches, fn($a, $b) => $a->getOrdre() <=> $b->getOrdre());
    $total = count($taches);

    if ($total === 0) {
        $programme->setScorePourcentage(0);
        $programme->setMeilleureMedaille(null);
        if ($programme->getObjectif()) {
            $programme->getObjectif()->setStatut(Statutobj::Abandonner);
        }
        $entityManager->flush();
        return;
    }

    $realisees = 0;

    foreach ($taches as $tache) {
        if (in_array($tache->getEtat()->value, [Etat::realisee->value])) {
            $realisees++;
        }
    }

    // Calcul du score
    $score = (int) round(($realisees / $total) * 100);

    // ✅ Attribution de médaille basée sur le SCORE (pas sur les tâches)
    $meilleureMedaille = null;
    if ($score > 0) {
        if ($score >= 90) {
            $meilleureMedaille = \App\Enum\Medaille::Or;
        } elseif ($score >= 60) {
            $meilleureMedaille = \App\Enum\Medaille::Argent;
        } elseif ($score >= 30) {
            $meilleureMedaille = \App\Enum\Medaille::Bronze;
        }
    }

    $programme->setScorePourcentage($score);
    $programme->setMeilleureMedaille($meilleureMedaille);
    $entityManager->flush();

    if ($programme->getObjectif()) {
        $this->objectifStatusService->updateStatusFromProgrammeScore($programme->getObjectif());
    }

    // ✅ Génération du message motivant avec Ollama
    try {
        $ollamaUrl = 'http://127.0.0.1:11434/api/generate';

        $objectifTitre = $programme->getObjectif()?->getTitre() ?? 'ton objectif';
        
        $prompt = "Tu es un mentor encourageant pour des étudiants. L'étudiant a un score de {$score}% sur son objectif '{$objectifTitre}'. 
Génère UN SEUL message motivant de 2-3 phrases maximum, adapté à ce score :
- Si score < 30% : encourage à commencer et à ne pas abandonner
- Si score 30-60% : félicite les efforts et encourage à continuer
- Si score 60-90% : félicite chaudement et motive pour la dernière ligne droite
- Si score > 90% : grande félicitation et fierté

Ton message doit être personnel, chaleureux et motivant. Retourne UNIQUEMENT le message, sans guillemets, sans préfixe.";

        $response = @file_get_contents($ollamaUrl, false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => json_encode([
                    'model' => 'llama3.1:8b',
                    'prompt' => $prompt,
                    'stream' => false,
                    'options' => ['temperature' => 0.8]
                ]),
                'timeout' => 30
            ]
        ]));

        if ($response === false) {
            throw new \Exception('Ollama non disponible');
        }

        $data = json_decode($response, true);
        $messageMotivant = trim($data['response'] ?? '');

        if (empty($messageMotivant)) {
            throw new \Exception('Réponse vide');
        }

        // Nettoyage du message
        $messageMotivant = preg_replace('/^["\']+|["\']+$/', '', $messageMotivant);
        $messageMotivant = trim($messageMotivant);

    } catch (\Exception $e) {
        // Fallback : message par défaut si Ollama ne répond pas
        $messageMotivant = match(true) {
            $score < 30 => "Chaque grand voyage commence par un premier pas. Tu as {$score}% — c'est un début ! Continue, tu es sur la bonne voie.",
            $score < 60 => "Bravo pour tes efforts ! {$score}% de réalisé, tu progresses bien. Garde ce rythme, le succès approche !",
            $score < 90 => "Excellent travail ! {$score}% accompli, tu es presque au bout ! Dernière ligne droite, tu vas y arriver !",
            default => "🎉 Incroyable ! {$score}% de réussite ! Tu as tout donné et ça paie. Félicitations, tu peux être fier de toi !"
        };
    }

    // Sauvegarde du message motivant
    $motivation = new Motivation();
    $motivation->setMessagemotivant($messageMotivant);
    $motivation->setDategeneratiomm(new \DateTime());
    $motivation->setProgramme($programme);

    $entityManager->persist($motivation);
    $entityManager->flush();

    $this->addFlash('success', 'Score mis à jour : ' . $score . '% !');
}

    #[Route('/{id}', name: 'show', methods: ['GET', 'POST'])]
    public function show(
        Request $request,
        Programme $programme,
        EntityManagerInterface $entityManager
    ): Response {
        $tache = new Tache();
        $tache->setProgramme($programme);

        $formTache = $this->createForm(TacheType::class, $tache);
        $formTache->handleRequest($request);

        if ($formTache->isSubmitted() && $formTache->isValid()) {
            $entityManager->persist($tache);
            $entityManager->flush();

            // Mise à jour stats
            $this->updateProgrammeStats($programme, $entityManager);

            $this->addFlash('success', 'Tâche ajoutée avec succès !');
            return $this->redirectToRoute('front_programme_show', ['id' => $programme->getId()]);
        }

        return $this->render('front/programme_show.html.twig', [
            'programme' => $programme,
            'formTache' => $formTache->createView(),
        ]);
    }
   #[Route('/{id}/generate', name: 'generate_ai', methods: ['POST'])]
public function generateProgramme(
    Programme $programme,
    EntityManagerInterface $em,
    Request $request
): Response
{
    if (!$this->isCsrfTokenValid('generate_ai_' . $programme->getId(), $request->request->get('_token'))) {
        $this->addFlash('danger', 'Token invalide.');
        return $this->redirectToRoute('front_programme_show', ['id' => $programme->getId()]);
    }

    $objectif = $programme->getObjectif();
    if (!$objectif) {
        $this->addFlash('danger', 'Aucun objectif.');
        return $this->redirectToRoute('front_programme_show', ['id' => $programme->getId()]);
    }

    $description = $objectif->getDescription() ?? 'Objectif sans description';

    try {
        $ollamaUrl = 'http://127.0.0.1:11434/api/generate';

        $prompt = "Objectif : $description.  
Génère 6 à 10 tâches progressives et concrètes.  
Retourne UNIQUEMENT un JSON valide : {\"taches\": [{\"ordre\":1,\"titre\":\"...\",\"description\":\"...\",\"etat\":\"Abandonner\"}]}";

        $response = file_get_contents($ollamaUrl, false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => json_encode([
                    'model' => 'llama3.1:8b',
                    'prompt' => $prompt,
                    'stream' => false,
                    'options' => ['temperature' => 0.7]
                ])
            ]
        ]));

        if ($response === false) {
            throw new \Exception('Impossible de contacter Ollama – vérifie qu’il tourne (tape "ollama serve" dans un terminal)');
        }

        $data = json_decode($response, true);

        if (!$data || !isset($data['response']) || trim($data['response']) === '') {
            throw new \Exception('Réponse Ollama vide');
        }

        $text = trim($data['response']);

        // Nettoyage ultra-agressif pour tous les artefacts courants
        $text = preg_replace('/^```json\s*/i', '', $text);
        $text = preg_replace('/^```/i', '', $text);
        $text = preg_replace('/```$/i', '', $text);
        $text = preg_replace('/^json\s*/i', '', $text);
        $text = preg_replace('/\s*```json\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);
        $text = preg_replace('/[\r\n]+/', ' ', $text); // supprime les sauts de ligne inutiles
        $text = trim($text);

        // Si le texte commence par { ou [ , on essaie de parser directement
        if (strpos($text, '{') === 0 || strpos($text, '[') === 0) {
            $json = json_decode($text, true);
        } else {
            // Sinon, on cherche le premier { valide
            $start = strpos($text, '{');
            $end = strrpos($text, '}');
            if ($start !== false && $end !== false) {
                $jsonText = substr($text, $start, $end - $start + 1);
                $json = json_decode($jsonText, true);
            } else {
                $json = null;
            }
        }

        if (json_last_error() !== JSON_ERROR_NONE || !isset($json['taches']) || !is_array($json['taches']) || empty($json['taches'])) {
            // Fallback : tâches par défaut si JSON invalide ou vide
            $this->addFlash('warning', 'Ollama n\'a pas généré de JSON valide. Tâches par défaut utilisées.');
            $json = ['taches' => [
                ['ordre' => 1, 'titre' => 'Analyser l’objectif', 'description' => 'Lire la description 3 fois', 'etat' => 'Abandonner'],
                ['ordre' => 2, 'titre' => 'Découper en étapes', 'description' => 'Faire une liste de 5 petites actions', 'etat' => 'Abandonner'],
                ['ordre' => 3, 'titre' => 'Planifier le temps', 'description' => 'Réserver 1h par jour dans ton agenda', 'etat' => 'Abandonner'],
                ['ordre' => 4, 'titre' => 'Commencer aujourd’hui', 'description' => 'Faire la première action dès maintenant', 'etat' => 'Abandonner'],
                ['ordre' => 5, 'titre' => 'Suivre les progrès', 'description' => 'Noter chaque avancement quotidien', 'etat' => 'Abandonner'],
            ]];
        }

        // Supprimer anciennes tâches (optionnel – commente si tu veux garder les anciennes)
        foreach ($programme->getTache() as $old) {
            $em->remove($old);
        }

        // Créer nouvelles tâches
        foreach ($json['taches'] as $t) {
            $tache = new Tache();
            $tache->setOrdre((int) ($t['ordre'] ?? 1));
            $tache->setTitre($t['titre'] ?? 'Tâche sans titre');
            $tache->setDescription($t['description'] ?? 'Description manquante');
            $tache->setEtat(Etat::Abandonner);
            $tache->setProgramme($programme);
            $em->persist($tache);
        }

        $programme->setDategeneration(new \DateTime());
        $em->flush();

        $this->updateProgrammeStats($programme, $em);
        

        $this->addFlash('success', 'Programme généré par IA locale (Ollama) !');

    } catch (\Exception $e) {
        $this->addFlash('danger', 'Erreur lors de la génération : ' . $e->getMessage());
    }

    return $this->redirectToRoute('front_programme_show', ['id' => $programme->getId()]);
}
}