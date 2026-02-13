<?php

namespace App\Controller;

use App\Entity\Objectif;
use App\Entity\Programme;
use App\Enum\Statutobj;
use App\Form\ObjectifType;
use App\Repository\ObjectifRepository;
use App\Service\ObjectifStatusService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

#[Route('/events', name: 'front_events_')]
class ObjectifController extends AbstractController
{
    private ObjectifStatusService $objectifStatusService;

    public function __construct(ObjectifStatusService $objectifStatusService)
    {
        $this->objectifStatusService = $objectifStatusService;
    }

    // Liste des objectifs + formulaire modal + filtres/tri
    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(
        ObjectifRepository $objectifRepository,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        $titre = trim($request->query->get('titre', ''));

        $queryBuilder = $objectifRepository->createQueryBuilder('o');

        if ($titre !== '') {
            $queryBuilder->andWhere('o.titre LIKE :titre')
                         ->setParameter('titre', '%' . $titre . '%');
        }

        $sort = $request->query->get('sort', 'datedebut');
        $orderBy = match ($sort) {
            'titre'     => 'titre',
            'datefin'   => 'datefin',
            'statut'    => 'statut',
            default     => 'datedebut',
        };

        $queryBuilder->orderBy('o.' . $orderBy, 'ASC');

        $objectifs = $queryBuilder->getQuery()->getResult();

        // Compteurs pour le cercle
        $total = count($objectifs);
        $atteints = 0;
        $enCours = 0;
        $abandonnes = 0;

        foreach ($objectifs as $objectif) {
            $statut = $objectif->getStatut()?->value;
            if ($statut === Statutobj::Atteint->value) {
                $atteints++;
            } elseif ($statut === Statutobj::EnCours->value) {
                $enCours++;
            } elseif ($statut === Statutobj::Abandonner->value) {
                $abandonnes++;
            }
        }

        // Formulaire modal ajout
        $objectif = new Objectif();
        $form = $this->createForm(ObjectifType::class, $objectif);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $programme = new Programme();
            $programme->setTitre($objectif->getTitre() ?? 'Programme auto ' . date('Y-m-d'));
            $programme->setDategeneration(new \DateTime());

            $entityManager->persist($programme);
            $objectif->setProgramme($programme);

            if (!$objectif->getStatut()) {
                $objectif->setStatut(Statutobj::EnCours);
            }

            $entityManager->persist($objectif);
            $entityManager->flush();

            // Mise à jour statut objectif basé sur score programme
            $this->objectifStatusService->updateStatusFromProgrammeScore($objectif);

            $this->addFlash('success', 'Objectif créé avec succès !');
            return $this->redirectToRoute('front_events_index');
        }

        return $this->render('front/events.html.twig', [
            'objectifs' => $objectifs,
            'form'      => $form->createView(),
            'showModal' => $form->isSubmitted() && !$form->isValid(),
            'total'     => $total,
            'atteints'  => $atteints,
            'enCours'   => $enCours,
            'abandonnes'=> $abandonnes,
        ]);
    }

    // Détails objectif
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Objectif $objectif): Response
    {
        return $this->render('front/objectif_show.html.twig', [
            'objectif' => $objectif,
        ]);
    }

    // Suppression
    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function deleteobjectif(
        Request $request,
        Objectif $objectif,
        EntityManagerInterface $entityManager
    ): Response
    {
        if ($this->isCsrfTokenValid('delete' . $objectif->getId(), $request->request->get('_token'))) {
            $entityManager->remove($objectif);
            $entityManager->flush();

            $this->addFlash('success', 'Objectif supprimé avec succès !');
        }

        return $this->redirectToRoute('front_events_index');
    }

    // Édition
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function editerObjectif(
        Request $request,
        Objectif $objectif,
        EntityManagerInterface $entityManager
    ): Response
    {
        $form = $this->createForm(ObjectifType::class, $objectif);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($objectif->getProgramme()) {
                $objectif->getProgramme()->setTitre($objectif->getTitre());
            }

            $entityManager->flush();

            // Mise à jour statut objectif basé sur score programme
            $this->objectifStatusService->updateStatusFromProgrammeScore($objectif);

            $this->addFlash('success', 'Modification enregistrée avec succès !');
            return $this->redirectToRoute('front_events_index');
        }

        return $this->render('front/objectif_edit.html.twig', [
            'objectif' => $objectif,
            'form'     => $form->createView(),
        ]);
    }

    // Réinitialiser tous
    #[Route('/reset', name: 'reset', methods: ['POST'])]
    public function reset(EntityManagerInterface $entityManager): Response
    {
        $entityManager->createQuery('DELETE FROM App\Entity\Objectif o')->execute();
        $this->addFlash('success', 'Tous les objectifs ont été supprimés !');
        return $this->redirectToRoute('front_events_index');
    }

    // Export Excel
    #[Route('/export/excel', name: 'export_excel')]
    public function exportExcel(ObjectifRepository $repo): Response
    {
        $objectifs = $repo->findAll();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'N°');
        $sheet->setCellValue('B1', 'ID Objectif');
        $sheet->setCellValue('C1', 'Titre');
        $sheet->setCellValue('D1', 'Date début');
        $sheet->setCellValue('E1', 'Date fin');
        $sheet->setCellValue('F1', 'Statut');

        $row = 2;
        foreach ($objectifs as $index => $objectif) {
            $sheet->setCellValue('A'.$row, $row - 1);
            $sheet->setCellValue('B'.$row, $objectif->getId() ?? 'PPD' . $objectif->getId());
            $sheet->setCellValue('C'.$row, $objectif->getTitre());
            $sheet->setCellValue('D'.$row, $objectif->getDatedebut() ? $objectif->getDatedebut()->format('d/m/Y') : '');
            $sheet->setCellValue('E'.$row, $objectif->getDatefin() ? $objectif->getDatefin()->format('d/m/Y') : '');
            $sheet->setCellValue('F'.$row, $objectif->getStatut() ? $objectif->getStatut()->value : '');
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'objectifs_' . date('Y-m-d') . '.xlsx';
        $tempFile = sys_get_temp_dir() . '/' . $filename;

        $writer->save($tempFile);

        $response = new BinaryFileResponse($tempFile);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);

        return $response;
    }

    // Export Word
    #[Route('/export/word', name: 'export_word')]
    public function exportWord(ObjectifRepository $repo): Response
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $section->addTitle('Liste des Objectifs', 1);
        $section->addTextBreak(1);

        $table = $section->addTable();
        $table->addRow();
        $table->addCell(2000)->addText('N°', ['bold' => true]);
        $table->addCell(3000)->addText('ID Objectif', ['bold' => true]);
        $table->addCell(5000)->addText('Titre', ['bold' => true]);
        $table->addCell(3000)->addText('Date début', ['bold' => true]);
        $table->addCell(3000)->addText('Date fin', ['bold' => true]);
        $table->addCell(2500)->addText('Statut', ['bold' => true]);

        $i = 1;
        foreach ($repo->findAll() as $objectif) {
            $table->addRow();
            $table->addCell(2000)->addText($i++);
            $table->addCell(3000)->addText($objectif->getId() ?? 'PPD' . $objectif->getId());
            $table->addCell(5000)->addText($objectif->getTitre());
            $table->addCell(3000)->addText($objectif->getDatedebut() ? $objectif->getDatedebut()->format('d/m/Y') : '');
            $table->addCell(3000)->addText($objectif->getDatefin() ? $objectif->getDatefin()->format('d/m/Y') : '');
            $table->addCell(2500)->addText($objectif->getStatut() ? $objectif->getStatut()->value : '');
        }

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $filename = 'objectifs_' . date('Y-m-d') . '.docx';
        $tempFile = sys_get_temp_dir() . '/' . $filename;

        $writer->save($tempFile);

        $response = new BinaryFileResponse($tempFile);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);

        return $response;
    }
}