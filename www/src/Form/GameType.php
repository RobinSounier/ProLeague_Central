<?php

namespace App\Form;

use App\Entity\Game;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class GameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'Nom du jeu',
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Entrez le nom du jeu',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Le nom du jeu ne peut pas être vide.',
                    ),
                    new Length(
                        max: 255,
                        maxMessage: 'Le nom du jeu ne peut pas dépasser {{ limit }} caractères.'
                    )
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Game::class,
        ]);
    }
}
