<?php

namespace App\Form;

use App\Entity\Parcours;
use App\Entity\Projet;
use App\Repository\ProjetRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParcoursType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type_parcours', TextType::class, [
                'label' => 'Type de Parcours (ex: Formation, Expérience)',
                'attr' => ['class' => 'form-control']
            ])
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'attr' => ['class' => 'form-control']
            ])
            ->add('etablissement', TextType::class, [
                'label' => 'Établissement / Organisme',
                'attr' => ['class' => 'form-control']
            ])
            ->add('diplome', TextType::class, [
                'label' => 'Diplôme / Certification',
                'attr' => ['class' => 'form-control']
            ])
            ->add('specialite', TextType::class, [
                'label' => 'Spécialité',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ])
            ->add('date_debut', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('date_fin', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('entreprise', TextType::class, [
                'label' => 'Entreprise',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('poste', TextType::class, [
                'label' => 'Poste',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('type_contrat', TextType::class, [
                'label' => 'Type de contrat',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('projets', EntityType::class, [
                'class' => Projet::class,
                'choice_label' => 'titre',
                'query_builder' => function (ProjetRepository $er) use ($options) {
                    $qb = $er->createQueryBuilder('p');
                    if ($options['user']) {
                        $qb->where('p.utilisateur = :user')
                           ->setParameter('user', $options['user']);
                    }
                    return $qb;
                },
                'multiple' => true,
                'expanded' => false,
                'by_reference' => false,
                'label' => 'Ajouter/Modifier les projets associés',
                'attr' => ['class' => 'form-select mb-3', 'style' => 'height: 150px;'],
                'help' => 'Maintenez Ctrl (ou Cmd) pour sélectionner plusieurs projets.'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Parcours::class,
            'user' => null,
        ]);
    }
}
