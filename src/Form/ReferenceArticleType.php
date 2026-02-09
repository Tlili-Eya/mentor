<?php

namespace App\Form;

use App\Entity\ReferenceArticle;
use App\Entity\CategorieArticle;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ReferenceArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de l\'article',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Les meilleures pratiques pédagogiques en 2025'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le titre est obligatoire']),
                    new Assert\Length([
                        'min' => 5,
                        'max' => 255,
                        'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères'
                    ]),
                ],
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu de l\'article',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 10,
                    'placeholder' => 'Rédigez le contenu complet de l\'article...'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le contenu est obligatoire']),
                    new Assert\Length([
                        'min' => 50,
                        'max' => 10000,
                        'minMessage' => 'Le contenu doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le contenu ne peut pas dépasser {{ limit }} caractères'
                    ]),
                ],
            ])
            ->add('categorie', EntityType::class, [
                'class' => CategorieArticle::class,
                'choice_label' => 'nomCategorie',
                'label' => 'Catégorie',
                'placeholder' => 'Sélectionnez une catégorie',
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new Assert\NotNull(['message' => 'La catégorie est obligatoire']),
                ],
            ])
            // SUPPRIMER LE CHAMP 'auteur' ICI
            ->add('published', CheckboxType::class, [
                'label' => 'Publier l\'article',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReferenceArticle::class,
        ]);
    }
}