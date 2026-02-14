<?php

namespace App\Form;

use App\Entity\Tache;
use App\Enum\Etat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class TacheType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de la tâche',
                'attr' => [
                    'placeholder' => 'Entrez le titre...',
                ],
            ])
            ->add('ordre', IntegerType::class, [
                'label' => 'Ordre',
                'attr' => [
                    'min' => 1,
                    'placeholder' => '1, 2, 3...',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Détails de la tâche...',
                ],
            ])
            ->add('etat', ChoiceType::class, [
                'label' => 'État de la tâche',
                'choices' => [
                    'Réalisée'   => Etat::realisee,
                    'En cours'   => Etat::encours,
                    'Abandonnée' => Etat::Abandonner,
                ],
                'expanded' => true,   // boutons radio
                'multiple' => false,
            ]);
      
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tache::class,
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
