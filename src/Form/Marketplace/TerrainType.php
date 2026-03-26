<?php

namespace App\Form\Marketplace;

use App\Entity\Marketplace\Categorie;
use App\Entity\Marketplace\Terrain;
use App\Repository\Marketplace\CategorieRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class TerrainType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'attr' => ['placeholder' => 'Titre du terrain'],
            ])
            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'label' => 'Catégorie',
                'query_builder' => fn(CategorieRepository $r) => $r->createQueryBuilder('c')
                    ->where('c.typeProduit = :t')->setParameter('t', 'terrain')->orderBy('c.nom', 'ASC'),
                'placeholder' => '-- Choisir --',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('superficieHectares', NumberType::class, [
                'label' => 'Superficie (hectares)',
                'scale' => 2,
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
            ])
            ->add('adresse', TextareaType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => ['rows' => 2],
            ])
            ->add('prixMois', MoneyType::class, [
                'label' => 'Prix / mois',
                'currency' => 'TND',
                'required' => false,
            ])
            ->add('prixAnnee', MoneyType::class, [
                'label' => 'Prix / année',
                'currency' => 'TND',
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
            'data_class' => Terrain::class,
        ]);
    }
}
