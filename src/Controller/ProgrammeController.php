<?php

namespace App\Controller;

use App\Entity\Motivation;
use App\Entity\Programme;
use App\Entity\Tache;
use App\Enum\Etat;
use App\Enum\Statutobj;

use App\Form\TacheType;
use App\Service\AiTaskGenerator;
use App\Service\ObjectifStatusService;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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
    $meilleureMedaille = null;

    foreach ($taches as $tache) {
        if (in_array($tache->getEtat()->value, [Etat::realisee->value])) {
            $realisees++;

            $medaille = $tache->getMedaille();
            if ($medaille !== null && ($meilleureMedaille === null || $medaille->value > $meilleureMedaille->value)) {
                $meilleureMedaille = $medaille;
            }
        }
    }

    $score = (int) round(($realisees / $total) * 100);

    $programme->setScorePourcentage($score);
    $programme->setMeilleureMedaille($meilleureMedaille);
    $entityManager->flush();

    if ($programme->getObjectif()) {
        $this->objectifStatusService->updateStatusFromProgrammeScore($programme->getObjectif());
    }

    // DEBUG 1 : on arrive bien ici
    $this->addFlash('info', 'Debug 1 : updateProgrammeStats exécuté - score = ' . $score . '%');

    // Génération message motivant
    $motivationPrompt = "Score actuel : {$score}%. Écris un message motivant court (2-4 phrases), positif et encourageant.";

    try {
        $ollamaUrl = 'http://127.0.0.1:11434/api/generate';

        $motivationResponse = file_get_contents($ollamaUrl, false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => json_encode([
                    'model' => 'llama3.1:8b',
                    'prompt' => $motivationPrompt,
                    'stream' => false,
                    'options' => ['temperature' => 0.9]
                ])
            ]
        ]));

        // DEBUG 2 : on a appelé Ollama
        $this->addFlash('info', 'Debug 2 : Appel Ollama effectué');

        if ($motivationResponse === false) {
            $this->addFlash('danger', 'Debug : Impossible de contacter Ollama (vérifie ollama serve)');
            return;
        }

        $motivationData = json_decode($motivationResponse, true);

$messageMotivant = null;

// Format classique Ollama
if (isset($motivationData['response'])) {
    $messageMotivant = trim($motivationData['response']);
}

// Format alternatif
elseif (isset($motivationData['message']['content'])) {
    $messageMotivant = trim($motivationData['message']['content']);
}

if (!$messageMotivant) {
    $this->addFlash('danger', 'Debug : Réponse Ollama vide');
    return;
}

        $messageMotivant = trim($motivationData['response']);

        $motivation = new Motivation();
        $motivation->setMessagemotivant($messageMotivant);
        $motivation->setDategeneratiomm(new \DateTime());
        $motivation->setProgramme($programme);

        $entityManager->persist($motivation);
        $entityManager->flush();

        $this->addFlash('success', 'Debug 3 : Message motivant créé et sauvegardé !');

    } catch (\Exception $e) {
        $this->addFlash('danger', 'Debug erreur : ' . $e->getMessage());
    }
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