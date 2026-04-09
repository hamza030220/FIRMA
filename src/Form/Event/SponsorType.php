<?php

namespace App\Form\Event;

use App\Entity\Event\SecteurActivite;
use App\Entity\Event\Sponsor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SponsorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class)
            ->add('email_contact', TextType::class, ['required' => false, 'property_path' => 'emailContact'])
            ->add('telephone', TextType::class, ['required' => false])
            ->add('site_web', TextType::class, ['required' => false, 'property_path' => 'siteWeb'])
            ->add('description', TextareaType::class, ['required' => false])
            ->add('montant_contribution', TextType::class, ['required' => false, 'property_path' => 'montantContribution'])
            ->add('secteur_activite', ChoiceType::class, [
                'choices' => array_combine(
                    array_map(fn(SecteurActivite $s) => $s->label(), SecteurActivite::cases()),
                    array_map(fn(SecteurActivite $s) => $s->value, SecteurActivite::cases()),
                ),
                'property_path' => 'secteurActivite',
            ])
            ->add('logo_url', TextType::class, ['required' => false, 'property_path' => 'logoUrl'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'      => Sponsor::class,
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
