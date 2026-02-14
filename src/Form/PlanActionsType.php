<?php

namespace App\Form;

use App\Entity\PlanActions;
use App\Entity\ReferenceArticle;
use App\Enum\Statut;
use App\Enum\CategorieSortie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Repository\ReferenceArticleRepository;


class PlanActionsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $builder
        ->add('decision', TextType::class, [
            'label' => 'Décision',
            'attr' => [
                'class' => 'form-control',
                'placeholder' => 'Ex: Mettre en place une nouvelle stratégie pédagogique'
            ]
        ])
        
        ->add('description', TextareaType::class, [
            'label' => 'Description détaillée',
            'required' => true,
            'attr' => [
                'class' => 'form-control',
                'rows' => 5,
                'placeholder' => 'Rédigez le contenu complet du plan d\'action...'
            ]
        ])
        
        ->add('statut', ChoiceType::class, [
            'label' => 'Statut',
            'choices' => $this->getStatutChoices(),
            'placeholder' => '-- Sélectionnez un statut --', // Placeholder
            'required' => true,
            'attr' => [
                'class' => 'form-select form-select-lg',
                'style' => 'border: 2px solid #9dbbce; border-radius: 10px; padding: 12px 16px;'
            ]
        ])
        
        ->add('categorie', ChoiceType::class, [
            'label' => 'Catégorie',
            'choices' => $this->getCategorieChoices(),
            'placeholder' => '-- Sélectionnez une catégorie --', // Placeholder
            'required' => true,
            'attr' => [
                'class' => 'form-select form-select-lg',
                'style' => 'border: 2px solid #9dbbce; border-radius: 10px; padding: 12px 16px;'
            ]
        ])
        
        ->add('date', DateType::class, [
    'label' => 'Date',
    'widget' => 'single_text',
    'html5' => true,
    'data' => new \DateTime(), // Date du jour
    'disabled' => true, // ← REND LE CHAMP NON MODIFIABLE
    'attr' => [
        'class' => 'form-control form-control-lg',
        'style' => 'border: 2px solid #9dbbce; border-radius: 10px; padding: 12px 16px; background-color: #f8f9fa;',
        'readonly' => true // Pour être sûr
    ]
])
        
        ->add('articles', EntityType::class, [
            'class' => ReferenceArticle::class,
            'choice_label' => function(ReferenceArticle $article) {
                $categorie = $article->getCategorie() ? $article->getCategorie()->getNomCategorie() : 'Sans catégorie';
                return $article->getTitre() . ' (' . $categorie . ')';
            },
            'label' => 'Articles liés',
            'multiple' => true,
            'expanded' => true,
            'attr' => [
                'class' => 'articles-checkboxes',
            ],
            'choice_attr' => function(ReferenceArticle $article) {
                $statut = $article->isPublished() ? 'Publié' : 'Brouillon';
                return [
                    'data-statut' => $statut,
                    'class' => 'article-checkbox'
                ];
            },
            'required' => false,
            'by_reference' => false,
        ]);
}

private function getStatutChoices(): array
{
    $choices = [];
    foreach (Statut::cases() as $case) {
        $label = match($case->value) {
            'EN_ATTENTE' => 'En Attente',
            'EN_COURS' => 'En Cours',
            'FINI' => 'Fini',
            'REJETE' => 'Rejeté',
            default => ucfirst(strtolower(str_replace('_', ' ', $case->value)))
        };
        $choices[$label] = $case;
    }
    return $choices;
}

private function getCategorieChoices(): array
{
    $choices = [];
    foreach (CategorieSortie::cases() as $case) {
        $label = match($case->value) {
            'PEDAGOGIQUE' => 'Pédagogique',
            'STRATEGIQUE' => 'Stratégique',
            'ADMINISTRATIVE' => 'Administrative',
            default => ucfirst(strtolower($case->value))
        };
        $choices[$label] = $case;
    }
    return $choices;
}
}