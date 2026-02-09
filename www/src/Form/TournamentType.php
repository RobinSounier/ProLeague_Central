<?php

namespace App\Form;

use App\Entity\Game;
use App\Entity\Tournament;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Url;

class TournamentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du tournoi',
                'attr' => [
                    'placeholder' => 'Ex: Tournoi League of Legends - Hiver 2026',
                    'class' => 'form-input',
                ],
                'constraints' => [
                    new NotBlank(message: "Le titre ne peut pas être vide."),
                    new Length(
                        min: 3,
                        max: 100,
                        minMessage: "Le titre doit contenir au moins {{ limit }} caractères",
                        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères"
                    )
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description du tournoi',
                'attr' => [
                    'class' => 'form-textarea',
                    'rows' => 6,
                    'placeholder' => "Décrivez votre tournoi : règles, format, récompenses..."
                ],
                'constraints' => [
                    new NotBlank(message: "La description ne peut pas être vide."),
                    new Length(
                        max: 5000,
                        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères"
                    )
                ]
            ])
            ->add('deadline', DateTimeType::class, [
                'label' => "Date de fin du tournoi",
                'required' => true,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-input',
                ],
                'constraints' => [
                    new NotNull(message: "La date de fin est obligatoire.")
                ]
            ])
            ->add('deadlineJoin', DateTimeType::class, [
                'label' => "Date limite d'inscription (optionnel)",
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-input',
                ],
                'help' => 'Laissez vide si les inscriptions sont ouvertes jusqu\'à la fin du tournoi'
            ])
            ->add('link', UrlType::class, [
                'label' => 'Lien externe (Discord, site web, etc.)',
                'attr' => [
                    'placeholder' => 'https://discord.gg/votre-serveur',
                    'class' => 'form-input',
                ],
                'constraints' => [
                    new NotBlank(message: "Le lien ne peut pas être vide."),
                    new Url(message: "Le lien doit être une URL valide.")
                ]
            ])
            ->add('game', EntityType::class, [
                'label' => 'Jeu',
                'class' => Game::class,
                'choice_label' => 'label',
                'placeholder' => "Sélectionnez un jeu",
                'attr' => [
                    'class' => 'form-select',
                ],
                'constraints' => [
                    new NotNull(message: "Le jeu est obligatoire.")
                ]
            ])
            ->add('files', FileType::class, [
                'label' => 'Images du tournoi (optionnel)',
                'multiple' => true,
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-input',
                    'accept' => 'image/*',
                ],
                'help' => 'Vous pouvez télécharger plusieurs images (5 Mo max par image)',
                'constraints' => [
                    new All(
                        constraints: [
                            new File(
                                maxSize: '5M',
                                mimeTypes: [
                                    'image/jpeg',
                                    'image/png',
                                    'image/gif',
                                    'image/webp',
                                    'image/svg+xml',
                                    'image/avif'
                                ],
                                mimeTypesMessage: 'Le fichier doit être une image valide (JPEG, PNG, GIF, WebP, SVG, AVIF)',
                                maxSizeMessage: 'L\'image ne doit pas dépasser {{ limit }} {{ suffix }}'
                            )
                        ]
                    )
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tournament::class,
        ]);
    }
}
