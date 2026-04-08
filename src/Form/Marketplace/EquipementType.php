<?php

namespace App\Form\Marketplace;

use App\Entity\Marketplace\Categorie;
use App\Entity\Marketplace\Equipement;
use App\Entity\Marketplace\Fournisseur;
use App\Repository\Marketplace\CategorieRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EquipementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => "Nom de l'équipement"],
            ])
            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'label' => 'Catégorie',
                'query_builder' => fn(CategorieRepository $r) => $r->createQueryBuilder('c')
                    ->where('c.typeProduit = :t')->setParameter('t', 'equipement')->orderBy('c.nom', 'ASC'),
                'placeholder' => '-- Choisir --',
            ])
            ->add('fournisseur', EntityType::class, [
                'class' => Fournisseur::class,
                'label' => 'Fournisseur',
                'choice_label' => 'nomEntreprise',
                'placeholder' => '-- Choisir --',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('prixAchat', MoneyType::class, [
                'label' => "Prix d'achat",
                'currency' => 'TND',
            ])
            ->add('prixVente', MoneyType::class, [
                'label' => 'Prix de vente',
                'currency' => 'TND',
            ])
            ->add('quantiteStock', IntegerType::class, [
                'label' => 'Quantité en stock',
            ])
            ->add('seuilAlerte', IntegerType::class, [
                'label' => "Seuil d'alerte",
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
            'data_class' => Equipement::class,
        ]);
    }
}
