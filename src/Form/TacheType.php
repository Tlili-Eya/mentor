<?php

namespace App\Form;

use App\Entity\Tache;
use App\Enum\Etat;
use App\Repository\TacheRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class TacheType extends AbstractType
{
    private TacheRepository $tacheRepository;

    public function __construct(TacheRepository $tacheRepository)
    {
        $this->tacheRepository = $tacheRepository;
    }

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
                'expanded' => true,
                'multiple' => false,
            ]);

        // Bloque si l'ordre est déjà présent dans le même programme
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($options) {
            $tache = $event->getData();
            $form = $event->getForm();

            $ordre = $tache->getOrdre();
            $programme = $options['programme'] ?? null;

            if ($ordre === null || !$programme instanceof \App\Entity\Programme) {
                return;
            }

            // Cherche une autre tâche avec le même ordre
            $existing = $this->tacheRepository->createQueryBuilder('t')
                ->where('t.programme = :programme')
                ->andWhere('t.ordre = :ordre')
                ->andWhere('t.id != :currentId') // Ignore la tâche actuelle en édition
                ->setParameter('programme', $programme)
                ->setParameter('ordre', $ordre)
                ->setParameter('currentId', $tache->getId() ?? 0)
                ->getQuery()
                ->getOneOrNullResult();

            if ($existing) {
                $form->get('ordre')->addError(new FormError(
                    'Cet ordre est déjà utilisé par une autre tâche dans ce programme.'
                ));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tache::class,
            'attr' => ['novalidate' => 'novalidate'],
            'programme' => null, // Doit être passé depuis le contrôleur
        ]);
    }
}