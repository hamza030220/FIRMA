<?php
// src/Form/SolutionTraitementType.php

namespace App\Form;

use App\Entity\SolutionTraitement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SolutionTraitementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du traitement *',
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description du traitement *',
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ])
            ->add('etapes', TextareaType::class, [
                'label' => 'Étapes à suivre',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 5]
            ])
            ->add('produits', TextareaType::class, [
                'label' => 'Produits recommandés',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('prevention', TextareaType::class, [
                'label' => 'Conseils de prévention',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('duree', TextType::class, [
                'label' => 'Durée du traitement',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: 7-10 jours']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SolutionTraitement::class,
        ]);
    }
}