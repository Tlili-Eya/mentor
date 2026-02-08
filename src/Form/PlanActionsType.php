<?php

namespace App\Form;

use App\Entity\PlanActions;
use App\Enum\Statut;
use App\Enum\CategorieSortie; // IMPORT DU DEUXIÈME ENUM
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class PlanActionsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('decision', TextType::class, [
                'label' => 'Décision',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez la décision'
                ]
            ])
            
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Description détaillée du plan d\'action'
                ]
            ])
            
            // LISTE DÉROULANTE POUR LE STATUT (ENUM 1)
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => $this->getStatutChoices(),
                'placeholder' => 'Sélectionnez un statut',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            
            // LISTE DÉROULANTE POUR LA CATÉGORIE (ENUM 2)
            ->add('categorie', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => $this->getCategorieChoices(),
                'placeholder' => 'Sélectionnez une catégorie',
                'required' => false,
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            
            ->add('date', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control'
                ]
            ]);
    }
    
    /**
     * Convertit l'Enum Statut en tableau pour les choix du formulaire
     */
    private function getStatutChoices(): array
    {
        $choices = [];
        
        foreach (Statut::cases() as $case) {
            // Formatage du label : "En Attente" au lieu de "EN_ATTENTE"
            $label = ucfirst(strtolower(str_replace('_', ' ', $case->value)));
            $choices[$label] = $case;
        }
        
        return $choices;
    }
    
    /**
     * Convertit l'Enum CategorieSortie en tableau pour les choix du formulaire
     */
    private function getCategorieChoices(): array
    {
        $choices = [];
        
        foreach (CategorieSortie::cases() as $case) {
            // Formatage selon votre Enum CategorieSortie
            // Exemple: "PROFESSIONNELLE" -> "Professionnelle"
            $label = ucfirst(strtolower($case->value));
            $choices[$label] = $case;
        }
        
        return $choices;
    }
    
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PlanActions::class,
        ]);
    }
}