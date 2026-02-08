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
use Symfony\Component\Validator\Constraints as Assert;

class SortieAIType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cible', EnumType::class, [
                'class' => Cible::class,
                'label' => 'Cible',
                'placeholder' => 'Sélectionnez une cible',
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new Assert\NotNull(['message' => 'La cible est obligatoire']),
                ],
            ])
            ->add('typeSortie', EnumType::class, [
                'class' => TypeSortie::class,
                'label' => 'Type de sortie',
                'placeholder' => 'Sélectionnez un type',
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new Assert\NotNull(['message' => 'Le type de sortie est obligatoire']),
                ],
            ])
            ->add('criticite', EnumType::class, [
                'class' => Criticite::class,
                'label' => 'Niveau de criticité',
                'placeholder' => 'Sélectionnez un niveau',
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new Assert\NotNull(['message' => 'Le niveau de criticité est obligatoire']),
                ],
            ])
            ->add('categorieSortie', EnumType::class, [
                'class' => CategorieSortie::class,
                'label' => 'Catégorie',
                'placeholder' => 'Sélectionnez une catégorie',
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new Assert\NotNull(['message' => 'La catégorie est obligatoire']),
                ],
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 6,
                    'placeholder' => 'Décrivez le contenu de la sortie IA...'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le contenu est obligatoire']),
                    new Assert\Length([
                        'min' => 10,
                        'max' => 5000,
                        'minMessage' => 'Le contenu doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le contenu ne peut pas dépasser {{ limit }} caractères'
                    ]),
                ],
            ]);
        // SUPPRIMER LE CHAMP 'article' - pas nécessaire lors de la création
    }

    public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults([
        'data_class' => SortieAI::class,
    ]);
}
}