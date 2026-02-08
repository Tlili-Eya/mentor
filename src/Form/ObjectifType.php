<?php

namespace App\Form;

use App\Entity\Objectif;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ObjectifType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de l’objectif',
                'attr' => ['placeholder' => 'Ex : Perdre 5 kg en 3 mois'],
               
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description détaillée',
                'required' => true,
            
                'attr' => ['rows' => 5, 'placeholder' => 'Expliquez votre objectif...'],
            ])
            ->add('datedebut', DateType::class, [
                'widget' => 'single_text',
                'required' => true,
                
            
            ])
            ->add('datefin', DateType::class, [
                'widget' => 'single_text',
                'required' => true,
               
               
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Objectif::class]);
    }
}
