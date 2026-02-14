<?php

namespace App\Form;

use App\Entity\Ressource;
use App\Enum\TypeRessource;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RessourceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la ressource',
                'attr' => ['class' => 'form-control']
            ])
            ->add('urlRessource', UrlType::class, [
                'label' => 'URL de la ressource (ou lien)',
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('fichier', \Symfony\Component\Form\Extension\Core\Type\FileType::class, [
                'label' => 'Ou téléverser un fichier (Tous types : SQL, ZIP, PDF...)',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\File([
                        'maxSize' => '10M',
                        'mimeTypesMessage' => 'Le fichier est trop volumineux (Max 10Mo)',
                    ])
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description (optionnel)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('typeRessource', EnumType::class, [
                'class' => TypeRessource::class,
                'label' => 'Type de ressource',
                'attr' => ['class' => 'form-control']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ressource::class,
        ]);
    }
}
