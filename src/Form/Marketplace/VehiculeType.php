<?php

namespace App\Form\Marketplace;

use App\Entity\Marketplace\Categorie;
use App\Entity\Marketplace\Vehicule;
use App\Repository\Marketplace\CategorieRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class VehiculeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Nom du véhicule'],
            ])
            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'label' => 'Catégorie',
                'query_builder' => fn(CategorieRepository $r) => $r->createQueryBuilder('c')
                    ->where('c.typeProduit = :t')->setParameter('t', 'vehicule')->orderBy('c.nom', 'ASC'),
                'placeholder' => '-- Choisir --',
            ])
            ->add('marque', TextType::class, [
                'label' => 'Marque',
                'required' => false,
            ])
            ->add('modele', TextType::class, [
                'label' => 'Modèle',
                'required' => false,
            ])
            ->add('immatriculation', TextType::class, [
                'label' => 'Immatriculation',
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('prixJour', MoneyType::class, [
                'label' => 'Prix / jour',
                'currency' => 'TND',
            ])
            ->add('prixSemaine', MoneyType::class, [
                'label' => 'Prix / semaine',
                'currency' => 'TND',
                'required' => false,
            ])
            ->add('prixMois', MoneyType::class, [
                'label' => 'Prix / mois',
                'currency' => 'TND',
                'required' => false,
            ])
            ->add('caution', MoneyType::class, [
                'label' => 'Caution',
                'currency' => 'TND',
                'required' => false,
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Formats acceptés : JPG, PNG, WEBP',
                    ]),
                ],
            ])
            ->add('disponible', CheckboxType::class, [
                'label' => 'Disponible',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vehicule::class,
        ]);
    }
}
