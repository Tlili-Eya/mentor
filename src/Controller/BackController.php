<?php

namespace App\Controller;

use App\Entity\Objectif;
use App\Entity\Programme;

use App\Repository\ObjectifRepository;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin', name: 'back_')]
final class BackController extends AbstractController
{
    #[Route('', name: 'home')]
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
public function events(ObjectifRepository $objectifRepository, Request $request): Response
{
    $titre = trim($request->query->get('titre', ''));

    // Construction de la requête
    $queryBuilder = $objectifRepository->createQueryBuilder('o');

    // Filtre uniquement par titre (si rempli)
    if ($titre !== '') {
        $queryBuilder
            ->andWhere('o.titre LIKE :titre')
            ->setParameter('titre', '%' . $titre . '%');
    }

    // Tri (par défaut : date début)
    $sort = $request->query->get('sort', 'datedebut');
    $orderBy = match ($sort) {
        'titre'     => 'o.titre',
        'datefin'   => 'o.datefin',
        'statut'    => 'o.statut',
        default     => 'o.datedebut',
    };

    $queryBuilder->orderBy($orderBy, 'ASC');
    $objectifs = $queryBuilder->getQuery()->getResult();

    // Calcul des compteurs
    $total = count($objectifs);
    $enCours   = 0;
    $atteints  = 0;
    $abandonnes = 0;

    foreach ($objectifs as $objectif) {
        $statut = strtolower($objectif->getStatut()?->value ?? '');

        if (str_contains($statut, 'en_cours') || str_contains($statut, 'encours')) {
            $enCours++;
        }
        elseif (str_contains($statut, 'validee') || str_contains($statut, 'atteint') || str_contains($statut, 'valid')) {
            $atteints++;
        }
        elseif (str_contains($statut, 'abandon') || str_contains($statut, 'abandonne') || str_contains($statut, 'abandonner')) {
            $abandonnes++;
        }
    }

    return $this->render('back/events.html.twig', [
        'objectifs'   => $objectifs,
        'total'       => $total,
        'enCours'     => $enCours,
        'atteints'    => $atteints,
        'abandonnes'  => $abandonnes,
    ]);
}

    #[Route('/events/{id}', name: 'objectif_show', methods: ['GET'])]
public function show(Objectif $objectif): Response
{
    return $this->render('back/objectif_show.html.twig', [
        'objectif' => $objectif,
    ]);
}
    #[Route('/programme/{id}', name: 'programme_show', methods: ['GET'])]
public function programmeShow(Programme $programme): Response
{
    return $this->render('back/programme_show.html.twig', [
        'programme' => $programme,
    ]);
}

    #[Route('/objectif/{id}', name: 'objectif_show', methods: ['GET'])]
    public function objectifShow(Objectif $objectif): Response
    {
        return $this->render('back/objectif_show.html.twig', [
            'objectif' => $objectif,
        ]);
    }
#[Route('/events/export/excel', name: 'events_export_excel')]
public function exportExcel(ObjectifRepository $repo): Response
{
    $objectifs = $repo->findAll();

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // En-têtes
    $sheet->setCellValue('A1', 'N°');
    $sheet->setCellValue('B1', 'ID Objectif');
    $sheet->setCellValue('C1', 'Titre');
    $sheet->setCellValue('D1', 'Date début');
    $sheet->setCellValue('E1', 'Date fin');
    $sheet->setCellValue('F1', 'Statut');

    $row = 2;
    foreach ($objectifs as $objectif) {
        $sheet->setCellValue('A'.$row, $row - 1);
        
        $sheet->setCellValue('C'.$row, $objectif->getTitre());
        $sheet->setCellValue('D'.$row, $objectif->getDatedebut() ? $objectif->getDatedebut()->format('d/m/Y') : '');
        $sheet->setCellValue('E'.$row, $objectif->getDatefin() ? $objectif->getDatefin()->format('d/m/Y') : '');
        $sheet->setCellValue('F'.$row, $objectif->getStatut() ? $objectif->getStatut()->value : '');
        $row++;
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $filename = 'objectifs_' . date('Y-m-d') . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit;
}

#[Route('/events/export/word', name: 'events_export_word')]
public function exportWord(ObjectifRepository $repo): Response
{
    $objectifs = $repo->findAll();

    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    $section = $phpWord->addSection();

    $section->addText('Liste des Objectifs', ['bold' => true, 'size' => 16]);
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
        
        $table->addCell(5000)->addText($objectif->getTitre());
        $table->addCell(3000)->addText($objectif->getDatedebut() ? $objectif->getDatedebut()->format('d/m/Y') : '');
        $table->addCell(3000)->addText($objectif->getDatefin() ? $objectif->getDatefin()->format('d/m/Y') : '');
        $table->addCell(2500)->addText($objectif->getStatut() ? $objectif->getStatut()->value : '');
    }

    $filename = 'objectifs_' . date('Y-m-d') . '.docx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $phpWord->save('php://output', 'Word2007');
    exit;
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
}
