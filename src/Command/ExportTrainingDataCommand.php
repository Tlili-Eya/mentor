<?php
// src/Command/ExportTrainingDataCommand.php

namespace App\Command;

use App\Repository\SortieAIRepository;
use App\Repository\ReferenceArticleRepository;
use App\Repository\PlanActionsRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ExportTrainingDataCommand extends Command
{
    protected static $defaultName = 'app:export-training-data';

    public function __construct(
        private SortieAIRepository $sortieAIRepository,
        private ReferenceArticleRepository $articleRepository,
        private PlanActionsRepository $planRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Export des données pour fine-tuning');

        // 1. Exporter les sorties IA (alertes, prédictions, recommandations)
        $sorties = $this->sortieAIRepository->findAll();
        $io->info(sprintf('%d sorties IA trouvées', count($sorties)));

        $trainingData = [];
        
        foreach ($sorties as $sortie) {
            $instruction = $this->createInstructionFromSortie($sortie);
            $output_text = $sortie->getContenu();
            
            $trainingData[] = [
                'instruction' => $instruction,
                'output' => $output_text
            ];
        }

        // 2. Exporter les articles comme contexte
        $articles = $this->articleRepository->findAll();
        $io->info(sprintf('%d articles trouvés', count($articles)));

        // Sauvegarder au format JSONL (format pour fine-tuning)
        $file = fopen('training_data.jsonl', 'w');
        foreach ($trainingData as $item) {
            fwrite($file, json_encode($item, JSON_UNESCAPED_UNICODE) . "\n");
        }
        fclose($file);

        $io->success(sprintf('%d exemples exportés dans training_data.jsonl', count($trainingData)));
        
        return Command::SUCCESS;
    }

    private function createInstructionFromSortie($sortie): string
    {
        $type = $sortie->getTypeSortie()->value;
        $cible = $sortie->getCible()->value;
        $criticite = $sortie->getCriticite()->value;
        
        $templates = [
            'Alerte' => [
                "Génère une alerte de niveau {$criticite} pour {$cible}",
                "Une alerte sur un élève en difficulté",
                "Signaler un problème concernant {$cible}"
            ],
            'Prediction' => [
                "Prédiction sur la réussite scolaire",
                "Quels sont les chances de réussite ?",
                "Analyse prédictive pour {$cible}"
            ],
            'Recommandation' => [
                "Donne des recommandations pédagogiques",
                "Conseils pour améliorer l'enseignement",
                "Que faire pour {$cible} ?"
            ]
        ];

        return $templates[$type][array_rand($templates[$type])] ?? "Question pédagogique";
    }
}