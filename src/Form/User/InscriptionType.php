<?php

namespace App\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class InscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr'  => ['placeholder' => 'Votre nom'],
                'constraints' => [new Assert\NotBlank(message: 'Le nom est obligatoire.')],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr'  => ['placeholder' => 'Votre prénom'],
                'constraints' => [new Assert\NotBlank(message: 'Le prénom est obligatoire.')],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr'  => ['placeholder' => 'votre@email.com'],
                'constraints' => [
                    new Assert\NotBlank(message: 'L\'email est obligatoire.'),
                    new Assert\Email(message: 'Email invalide.'),
                ],
            ])
            ->add('telephone', TextType::class, [
                'label'    => 'Téléphone',
                'required' => false,
                'attr'     => ['placeholder' => 'Ex: 55123456'],
            ])
            ->add('adresse', TextType::class, [
                'label'    => 'Adresse',
                'required' => false,
                'attr'     => ['placeholder' => 'Votre adresse'],
            ])
            ->add('ville', TextType::class, [
                'label'    => 'Ville',
                'required' => false,
                'attr'     => ['placeholder' => 'Votre ville'],
            ])
            ->add('typeUser', ChoiceType::class, [
                'label'   => 'Type de compte',
                'choices' => [
                    'Utilisateur'  => 'client',
                    'Technicien'   => 'technicien',
                ],
                'expanded' => true,
                'multiple' => false,
                'data'     => 'client',
            ])
            ->add('motDePasse', RepeatedType::class, [
                'type'            => PasswordType::class,
                'first_options'   => [
                    'label' => 'Mot de passe',
                    'attr'  => ['placeholder' => '••••••••'],
                ],
                'second_options'  => [
                    'label' => 'Confirmer le mot de passe',
                    'attr'  => ['placeholder' => '••••••••'],
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'mapped'          => false,
                'constraints'     => [
                    new Assert\NotBlank(message: 'Le mot de passe est obligatoire.'),
                    new Assert\Length(min: 6, minMessage: 'Le mot de passe doit contenir au moins 6 caractères.'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
