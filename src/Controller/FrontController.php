<?php

namespace App\Controller;

use App\Enum\Statutobj;
use App\Entity\Objectif;
use App\Entity\Programme;
use App\Form\ObjectifType;
use App\Repository\ObjectifRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/', name: 'front_')]
final class FrontController extends AbstractController
{
    #[Route('', name: 'home')]
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
public function events(
    ObjectifRepository $objectifRepository,
    Request $request,
    EntityManagerInterface $entityManager
): Response
{
    // Récupère les paramètres GET
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
        'titre'     => 'titre',
        'datefin'   => 'datefin',
        'statut'    => 'statut',
        default     => 'datedebut',
    };

    $queryBuilder->orderBy('o.' . $orderBy, 'ASC');

    // Résultat final
    $objectifs = $queryBuilder->getQuery()->getResult();
// Compteurs 
$total = count($objectifs);
$atteints = 0;
$enCours = 0;
$abandonnes = 0;

foreach ($objectifs as $objectif) {
    $statut = $objectif->getStatut() ? strtolower($objectif->getStatut()->value) : null;

    if (str_contains($statut, 'valide') || str_contains($statut, 'valid')) {
        $atteints++;
    } elseif (str_contains($statut, 'cours') || str_contains($statut, 'en cours')) {
        $enCours++;
    } elseif (str_contains($statut, 'abandon') || str_contains($statut, 'abandonne') || str_contains($statut, 'abandonner')) {
        $abandonnes++;
    }
}

    // Formulaire pour le modal 
    $objectif = new Objectif();
    $form = $this->createForm(ObjectifType::class, $objectif);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
    // Création automatique d'un Programme associé
    $programme = new Programme();
    $programme->setTitre($objectif->getTitre() ?? 'Programme auto ' . date('Y-m-d'));
    $programme->setDategeneration(new \DateTime());

    $entityManager->persist($programme);

    // Liaison obligatoire
    $objectif->setProgramme($programme);

    // Statut par défaut si absent
    if (!$objectif->getStatut()) {
        $objectif->setStatut(Statutobj::EnCours);
    }

    $entityManager->persist($objectif);
    $entityManager->flush();

    $this->addFlash('success', 'Objectif créé avec succès !');

    return $this->redirectToRoute('front_events');
}

    return $this->render('front/events.html.twig', [
        'objectifs' => $objectifs,
        'form'      => $form->createView(),
        'showModal' => $form->isSubmitted() && !$form->isValid(),
        'total'         => $total,        
        'atteints'      => $atteints,     
        'enCours'       => $enCours,      
        'abandonnes'    => $abandonnes,
    ]);
}

#[Route('/events/{id}', name: 'objectif_show', methods: ['GET'])]
public function show(Objectif $objectif): Response
{
    return $this->render('front/objectif_show.html.twig', [
        'objectif' => $objectif,
    ]);
}

#[Route('/events/{id}', name: 'objectif_delete', methods: ['POST'])]
public function delete(Request $request, Objectif $objectif, EntityManagerInterface $entityManager): Response
{
    if ($this->isCsrfTokenValid('delete' . $objectif->getId(), $request->request->get('_token'))) {
        
        $entityManager->remove($objectif);
        $entityManager->flush();

        $this->addFlash('success', 'Objectif supprimé avec succès !');
    }

    return $this->redirectToRoute('front_events');
}
#[Route('/events/reset', name: 'events_reset', methods: ['POST'])]
public function reset(EntityManagerInterface $entityManager): Response
{
    $entityManager->createQuery('DELETE FROM App\Entity\Objectif o')->execute();
    $this->addFlash('success', 'Tous les objectifs ont été supprimés !');
    return $this->redirectToRoute('front_events');
}
    #[Route('/programme/{id}', name: 'programme_show', methods: ['GET'])]
public function programmeShow(Programme $programme): Response
{
    return $this->render('front/programme_show.html.twig', [
        'programme' => $programme,
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