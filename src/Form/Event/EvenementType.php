<?php

namespace App\Form\Event;

use App\Entity\Event\Evenement;
use App\Entity\Event\TypeEvenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class)
            ->add('description', TextareaType::class, ['required' => false])
            ->add('organisateur', TextType::class)
            ->add('type_evenement', ChoiceType::class, [
                'property_path' => 'typeEvenement',
                'choices' => array_combine(
                    array_map(fn(TypeEvenement $t) => $t->label(), TypeEvenement::cases()),
                    array_map(fn(TypeEvenement $t) => $t->value, TypeEvenement::cases()),
                ),
            ])
            ->add('lieu', TextType::class, ['required' => false])
            ->add('adresse', TextType::class, ['required' => false])
            ->add('capacite_max', IntegerType::class, ['property_path' => 'capaciteMax'])
            ->add('image_url', TextType::class, ['required' => false, 'property_path' => 'imageUrl'])
            ->add('date_debut', DateType::class, [
                'widget' => 'single_text',
                'input'  => 'datetime',
                'property_path' => 'dateDebut',
            ])
            ->add('date_fin', DateType::class, [
                'widget' => 'single_text',
                'input'  => 'datetime',
                'property_path' => 'dateFin',
            ])
            ->add('horaire_debut', TimeType::class, [
                'widget' => 'single_text',
                'input'  => 'datetime',
                'property_path' => 'horaireDebut',
            ])
            ->add('horaire_fin', TimeType::class, [
                'widget' => 'single_text',
                'input'  => 'datetime',
                'property_path' => 'horaireFin',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'         => Evenement::class,
            'csrf_protection'    => false,
            'allow_extra_fields' => true,
        ]);
    }

    /**
     * HTML field names are flat (e.g. name="titre"), not prefixed.
     */
    public function getBlockPrefix(): string
    {
        return '';
    }
}
