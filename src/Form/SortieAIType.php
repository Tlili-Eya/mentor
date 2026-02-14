<?php

namespace App\Form;

use App\Entity\SortieAI;
use App\Enum\Cible;
use App\Enum\TypeSortie;
use App\Enum\Criticite;
use App\Enum\CategorieSortie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortieAIType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cible', EnumType::class, [
                'class' => Cible::class,
                'label' => 'Cible',
                'placeholder' => 'Sélectionnez une cible',
                'attr' => ['class' => 'form-select']
            ])
            ->add('typeSortie', EnumType::class, [
                'class' => TypeSortie::class,
                'label' => 'Type de sortie',
                'placeholder' => 'Sélectionnez un type',
                'attr' => ['class' => 'form-select']
            ])
            ->add('criticite', EnumType::class, [
                'class' => Criticite::class,
                'label' => 'Niveau de criticité',
                'placeholder' => 'Sélectionnez un niveau',
                'attr' => ['class' => 'form-select']
            ])
            ->add('categorieSortie', EnumType::class, [
                'class' => CategorieSortie::class,
                'label' => 'Catégorie',
                'placeholder' => 'Sélectionnez une catégorie',
                'attr' => ['class' => 'form-select']
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 6,
                    'placeholder' => 'Décrivez le contenu de la sortie IA...'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SortieAI::class,
        ]);
    }
}