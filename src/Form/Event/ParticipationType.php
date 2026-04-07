<?php

namespace App\Form\Event;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ParticipationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nb_accompagnants', IntegerType::class, [
                'constraints' => [
                    new Assert\PositiveOrZero(),
                    new Assert\LessThanOrEqual(10),
                ],
            ])
            ->add('commentaire', TextareaType::class, ['required' => false])
            ->add('accomp_prenom', CollectionType::class, [
                'allow_add'    => true,
                'required'     => false,
                'entry_options' => ['required' => false],
            ])
            ->add('accomp_nom', CollectionType::class, [
                'allow_add'    => true,
                'required'     => false,
                'entry_options' => ['required' => false],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
