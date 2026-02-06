<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pseudo', TextType::class, [
                'label' => 'Pseudo',
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Entrez votre pseudo',
                    'autocomplete' => 'username'
                ],
            ])

            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'votre@email.com',
                    'autocomplete' => 'email'
                ],
            ])

            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options'  => [
                    'label' => 'Mot de passe',
                    'attr' => [
                        'class' => 'form-input',
                        'placeholder' => 'Minimum 6 caractères',
                        'autocomplete' => 'new-password'
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmez le mot de passe',
                    'attr' => [
                        'class' => 'form-input',
                        'placeholder' => 'Répétez le mot de passe',
                        'autocomplete' => 'new-password'
                    ],
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'constraints' => [
                    new NotBlank(
                        message : 'Veuillez entrer un mot de passe',
                    ),

                    new Length(
                        min : 6,
                        minMessage : 'Votre mot de passe doit contenir au moins {{ limit }} caractères',

                        max : 255,
                    ),
                    
                ]
            ])

            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => 'J\'accepte les conditions d\'utilisation',
                'constraints' => [
                    new IsTrue(message: 'Vous devez accepter les conditions d\'utilisation.')
                ]

            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
