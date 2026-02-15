<?php

namespace App\Form;

use App\Entity\PlanActions;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PlanActionFeedbackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('feedbackEnseignant', TextareaType::class, [
                'label' => 'Votre feedback / réclamation',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 6,
                    'placeholder' => 'Donnez votre avis, signalez un problème ou posez une question concernant ce plan d\'action...'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le feedback est obligatoire']),
                    new Assert\Length([
                        'min' => 10,
                        'max' => 2000,
                        'minMessage' => 'Le feedback doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le feedback ne peut pas dépasser {{ limit }} caractères'
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PlanActions::class,
        ]);
    }
}