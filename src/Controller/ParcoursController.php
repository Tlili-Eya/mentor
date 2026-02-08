<?php

namespace App\Controller;

use App\Entity\Parcours;
use App\Repository\ParcoursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ParcoursController extends AbstractController
{
    #[Route('/parcours', name: 'front_parcours')]
    public function index(Request $request, EntityManagerInterface $entityManager, ParcoursRepository $parcoursRepository): Response
    {
        $errors = [];
        $formData = [];
        
        if ($request->isMethod('POST')) {
            // Récupération des données
            $typeParcours = trim($request->request->get('type_parcours', ''));
            $titre = trim($request->request->get('titre', ''));
            $description = trim($request->request->get('description', ''));
            $dateDebut = $request->request->get('date_debut', '');
            $dateFin = $request->request->get('date_fin', '');
            $etablissement = trim($request->request->get('etablissement', ''));
            $diplome = trim($request->request->get('diplome', ''));
            $specialite = trim($request->request->get('specialite', ''));
            $entreprise = trim($request->request->get('entreprise', ''));
            $poste = trim($request->request->get('poste', ''));
            $typeContrat = trim($request->request->get('type_contrat', ''));
            
            // Sauvegarder les données du formulaire
            $formData = [
                'type_parcours' => $typeParcours,
                'titre' => $titre,
                'description' => $description,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'etablissement' => $etablissement,
                'diplome' => $diplome,
                'specialite' => $specialite,
                'entreprise' => $entreprise,
                'poste' => $poste,
                'type_contrat' => $typeContrat,
            ];
            
            // Validation PHP
            // 1. Type de parcours
            if (empty($typeParcours)) {
                $errors['type_parcours'] = 'Le type de parcours est requis.';
            } elseif (!in_array($typeParcours, ['scolaire', 'professionnel', 'formation'])) {
                $errors['type_parcours'] = 'Le type de parcours est invalide.';
            }
            
            // 2. Titre
            if (empty($titre)) {
                $errors['titre'] = 'Le titre du parcours est requis.';
            } elseif (strlen($titre) < 3) {
                $errors['titre'] = 'Le titre doit contenir au moins 3 caractères.';
            } elseif (strlen($titre) > 255) {
                $errors['titre'] = 'Le titre ne doit pas dépasser 255 caractères.';
            }
            
            // 3. Description
            if (empty($description)) {
                $errors['description'] = 'La description du parcours est requise.';
            } elseif (strlen($description) < 10) {
                $errors['description'] = 'La description doit contenir au moins 10 caractères.';
            } elseif (strlen($description) > 2000) {
                $errors['description'] = 'La description ne doit pas dépasser 2000 caractères.';
            }
            
            // 4. Validation des dates
            $dateDebutObj = null;
            $dateFinObj = null;
            
            if (!empty($dateDebut)) {
                try {
                    $dateDebutObj = new \DateTime($dateDebut);
                } catch (\Exception $e) {
                    $errors['date_debut'] = 'La date de début est invalide.';
                }
            }
            
            if (!empty($dateFin)) {
                try {
                    $dateFinObj = new \DateTime($dateFin);
                } catch (\Exception $e) {
                    $errors['date_fin'] = 'La date de fin est invalide.';
                }
            }
            
            // Vérifier que la date de fin est après la date de début
            if (!empty($dateDebut) && !empty($dateFin) && $dateDebutObj && $dateFinObj) {
                if ($dateFinObj < $dateDebutObj) {
                    $errors['date_fin'] = 'La date de fin doit être après la date de début.';
                }
            }
            
            // 5. Validation conditionnelle selon le type de parcours
            if ($typeParcours === 'scolaire') {
                if (empty($etablissement)) {
                    $errors['etablissement'] = "L'établissement est requis pour un parcours scolaire.";
                } elseif (strlen($etablissement) > 255) {
                    $errors['etablissement'] = "Le nom de l'établissement ne doit pas dépasser 255 caractères.";
                }
                
                if (empty($diplome)) {
                    $errors['diplome'] = "Le diplôme est requis pour un parcours scolaire.";
                } elseif (strlen($diplome) > 255) {
                    $errors['diplome'] = "Le nom du diplôme ne doit pas dépasser 255 caractères.";
                }
                
                if (!empty($specialite) && strlen($specialite) > 255) {
                    $errors['specialite'] = "La spécialité ne doit pas dépasser 255 caractères.";
                }
                
            } elseif ($typeParcours === 'professionnel') {
                if (empty($entreprise)) {
                    $errors['entreprise'] = "Le nom de l'entreprise est requis pour un parcours professionnel.";
                } elseif (strlen($entreprise) > 255) {
                    $errors['entreprise'] = "Le nom de l'entreprise ne doit pas dépasser 255 caractères.";
                }
                
                if (empty($poste)) {
                    $errors['poste'] = "Le poste est requis pour un parcours professionnel.";
                } elseif (strlen($poste) > 255) {
                    $errors['poste'] = "Le nom du poste ne doit pas dépasser 255 caractères.";
                }
                
                if (!empty($typeContrat) && !in_array($typeContrat, ['CDI', 'CDD', 'Stage', 'Alternance', 'Freelance'])) {
                    $errors['type_contrat'] = "Le type de contrat est invalide.";
                }
                
            } elseif ($typeParcours === 'formation') {
                // Pour les formations, tous les champs sont optionnels mais avec validation de longueur
                if (!empty($etablissement) && strlen($etablissement) > 255) {
                    $errors['etablissement'] = "Le nom de l'organisme ne doit pas dépasser 255 caractères.";
                }
                
                if (!empty($diplome) && strlen($diplome) > 255) {
                    $errors['diplome'] = "Le nom de la certification ne doit pas dépasser 255 caractères.";
                }
                
                if (!empty($specialite) && strlen($specialite) > 255) {
                    $errors['specialite'] = "Le domaine de formation ne doit pas dépasser 255 caractères.";
                }
            }
            
            // Si pas d'erreurs, créer et sauvegarder le parcours
            if (empty($errors)) {
                try {
                    $parcours = new Parcours();
                    $parcours->setTypeParcours($typeParcours);
                    $parcours->setTitre($titre);
                    $parcours->setDescription($description);
                    $parcours->setDateCreation(new \DateTime());
                    
                    // Dates
                    if ($dateDebutObj) {
                        $parcours->setDateDebut($dateDebutObj);
                    }
                    if ($dateFinObj) {
                        $parcours->setDateFin($dateFinObj);
                    }
                    
                    // Champs spécifiques selon le type
                    if ($typeParcours === 'scolaire') {
                        $parcours->setEtablissement($etablissement);
                        $parcours->setDiplome($diplome);
                        
                        // Utiliser des chaînes vides au lieu de null si le setter ne l'accepte pas
                        $parcours->setSpecialite($specialite !== '' ? $specialite : '');
                        $parcours->setEntreprise('');
                        $parcours->setPoste('');
                        $parcours->setTypeContrat('');
                        
                    } elseif ($typeParcours === 'professionnel') {
                        $parcours->setEntreprise($entreprise);
                        $parcours->setPoste($poste);
                        $parcours->setTypeContrat($typeContrat !== '' ? $typeContrat : '');
                        
                        // Pour les champs non utilisés, utiliser des chaînes vides
                        $parcours->setEtablissement('');
                        $parcours->setDiplome('');
                        $parcours->setSpecialite('');
                        
                    } elseif ($typeParcours === 'formation') {
                        // Pour les formations, stocker les informations si fournies
                        $parcours->setEtablissement($etablissement !== '' ? $etablissement : '');
                        $parcours->setDiplome($diplome !== '' ? $diplome : '');
                        $parcours->setSpecialite($specialite !== '' ? $specialite : '');
                        
                        // Pour les champs non utilisés
                        $parcours->setEntreprise('');
                        $parcours->setPoste('');
                        $parcours->setTypeContrat('');
                    }
                    
                    // Ajouter l'utilisateur si connecté
                    if ($this->getUser()) {
                        $parcours->setUtilisateur($this->getUser());
                    }
                    
                    // Sauvegarder
                    $entityManager->persist($parcours);
                    $entityManager->flush();
                    
                    $this->addFlash('success', 'Parcours ajouté avec succès!');
                    return $this->redirectToRoute('front_parcours');
                    
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'enregistrement: ' . $e->getMessage());
                    // Pour debug :
                    // dump($e->getMessage());
                    // dump($e->getTraceAsString());
                }
            } else {
                // Afficher les erreurs
                foreach ($errors as $field => $error) {
                    // Message flash général
                    if ($field === 'type_parcours') {
                        $this->addFlash('error', 'Type de parcours: ' . $error);
                    } elseif ($field === 'titre') {
                        $this->addFlash('error', 'Titre: ' . $error);
                    } elseif ($field === 'description') {
                        $this->addFlash('error', 'Description: ' . $error);
                    } else {
                        $this->addFlash('error', $error);
                    }
                }
            }
        }
        
        $allParcours = $parcoursRepository->findAll();
        
        return $this->render('front/parcours.html.twig', [
            'parcours' => $allParcours,    // Liste des parcours existants
            'form_data' => $formData,      // Données du formulaire en cas d'erreur
            'errors' => $errors            // Erreurs de validation par champ
        ]);
    }
}