<?php
namespace App\Controller;
use App\Entity\Objectif;
use App\Repository\ObjectifRepository;
use App\Enum\Statutobj;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class BackObjectifController extends AbstractController
{
    #[Route('/admin/events', name: 'back_events', methods: ['GET'])]
public function index(ObjectifRepository $repo, Request $request): Response
{
    // Filtre par titre
    $titre = trim($request->query->get('titre', ''));

    $queryBuilder = $repo->createQueryBuilder('o');

    if ($titre !== '') {
        $queryBuilder->andWhere('o.titre LIKE :titre')
            ->setParameter('titre', '%' . $titre . '%');
    }

    // Tri
    $sort = $request->query->get('sort', 'datedebut');
    $orderBy = match ($sort) {
        'titre'     => 'titre',
        'datefin'   => 'datefin',
        'statut'    => 'statut',
        default     => 'datedebut',
    };

    $queryBuilder->orderBy('o.' . $orderBy, 'ASC');

    $objectifs = $queryBuilder->getQuery()->getResult();

    // Compteurs
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

    // 🔴 FORCER LES VARIABLES POUR TEST
    $variables = [
        'objectifs'  => $objectifs,
        'total'      => $total,
        'atteints'   => $atteints,
        'enCours'    => $enCours,
        'abandonnes' => $abandonnes,
    ];
    
    // 🔴 DUMP POUR VÉRIFIER
    dump('VARIABLES ENVOYÉES:', $variables);
    
    return $this->render('back/events.html.twig', $variables);
}
    
    // 📊 Export Excel ADMIN (tous les objectifs)
   #[Route('/admin/events/export/excel', name: 'back_events_export_excel')]
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
        foreach ($objectifs as $objectif) {
            $sheet->setCellValue('A'.$row, $row - 1);
            $sheet->setCellValue('B'.$row, 'PPD' . $objectif->getId());
            $sheet->setCellValue('C'.$row, $objectif->getTitre());
            $sheet->setCellValue('D'.$row, $objectif->getDatedebut()?->format('d/m/Y') ?? '');
            $sheet->setCellValue('E'.$row, $objectif->getDatefin()?->format('d/m/Y') ?? '');
            $sheet->setCellValue('F'.$row, $objectif->getStatut()?->value ?? '');
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'objectifs_admin_' . date('Y-m-d') . '.xlsx';
        $tempFile = sys_get_temp_dir() . '/' . $filename;
        $writer->save($tempFile);

        $response = new BinaryFileResponse($tempFile);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);

        return $response;
    }

    // 📄 Export Word ADMIN
    #[Route('/admin/events/export/word', name: 'back_events_export_word')]
public function exportWord(ObjectifRepository $repo): Response
{
        $objectifs = $repo->findAll();

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $section->addTitle('Liste des Objectifs (Admin)', 1);
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
        foreach ($objectifs as $objectif) {
            $table->addRow();
            $table->addCell(2000)->addText($i++);
            $table->addCell(3000)->addText('PPD' . $objectif->getId());
            $table->addCell(5000)->addText($objectif->getTitre());
            $table->addCell(3000)->addText($objectif->getDatedebut()?->format('d/m/Y') ?? '');
            $table->addCell(3000)->addText($objectif->getDatefin()?->format('d/m/Y') ?? '');
            $table->addCell(2500)->addText($objectif->getStatut()?->value ?? '');
        }

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $filename = 'objectifs_admin_' . date('Y-m-d') . '.docx';
        $tempFile = sys_get_temp_dir() . '/' . $filename;
        $writer->save($tempFile);

        $response = new BinaryFileResponse($tempFile);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);

        return $response;
    }

    // 🔎 Détail objectif (lecture seule)
    #[Route('/admin/events/{id}', name: 'back_events_show', methods: ['GET'])]
public function show(?Objectif $objectif): Response
{
        if (!$objectif) {
            $this->addFlash('danger', 'Objectif introuvable.');
            return $this->redirectToRoute('back_events');
        }

        return $this->render('back/objectif_show.html.twig', [
            'objectif' => $objectif,
        ]);
    }

}
