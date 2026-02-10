<?php

namespace App\Form;

use App\Entity\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;


class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => false,
                'attr' => [
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors outline-none resize-none',
                    'placeholder' => 'Écrivez votre commentaire',
                    'rows' => 4
                ],
                'constraints' => [
                    new NotBlank(message: 'Le commentaire ne peut pas etre nul'),
                    new Length(
                        min: 1,
                        max: 5000,
                        minMessage: 'Votre commentaire ne peut pas etre vide',
                        maxMessage: 'Vous avez dépasser la limite de caractère du commentaire {{ limit }} caractères',
                    )

                ]
            ])
            ->add('parentComment', HiddenType::class, [
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'value' => ''
                ]
            ]);
    }



    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
            'csrf_token_id' => 'submit', // utiliser le token_id configuré dans csrf_yaml
        ]);
    }
}
