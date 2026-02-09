<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

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
        
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}