<?php
// src/Form/MaladieType.php

namespace App\Form;

use App\Entity\Maladie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MaladieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la maladie *',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Mildiou']
            ])
            ->add('nomScientifique', TextType::class, [
                'label' => 'Nom scientifique',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 5]
            ])
            ->add('symptomes', TextareaType::class, [
                'label' => 'Symptômes',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 5]
            ])
            ->add('niveauGravite', ChoiceType::class, [
                'label' => 'Niveau de gravité',
                'choices' => [
                    'Faible' => 'faible',
                    'Moyen' => 'moyen',
                    'Élevé' => 'eleve',
                    'Critique' => 'critique'
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('saisonFrequente', ChoiceType::class, [
                'label' => 'Saison fréquente',
                'choices' => [
                    'Printemps' => 'Printemps',
                    'Été' => 'Été',
                    'Automne' => 'Automne',
                    'Hiver' => 'Hiver',
                    'Printemps-Été' => 'Printemps-Été'
                ],
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Photo',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Maladie::class,
        ]);
    }
}