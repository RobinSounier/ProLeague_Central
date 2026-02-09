<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

class ProfilType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse Email',
            ])
            ->add('pseudo', TextType::class, [
                'label' => 'Nom d\'utilisateur',
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'Ma Biographie',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Présentez-vous aux autres joueurs (jeux favoris, expérience, rank...)',
                    'class' => 'form-textarea'
                ]
            ])
            ->add('avatar', FileType::class, [
                'label' => 'Photo de profil',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg',
                    'accept' => 'image/*',
                ],
                'constraints' => [
                    new Assert\File(
                        maxSize: '5M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                        mimeTypesMessage: 'Veuillez uploader une image valide (JPEG, PNG, GIF, WebP)',
                    ),
                ],
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'constraints' => [
                new UniqueEntity(
                    fields: 'email',
                    message: 'Cet email est déjà utilisé.',
                ),
                new UniqueEntity(
                    fields: 'pseudo',
                    message: 'Ce pseudo est déjà utilisé.',
                ),
            ],
        ]);
    }
}
