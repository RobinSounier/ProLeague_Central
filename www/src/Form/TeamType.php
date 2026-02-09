<?php

namespace App\Form;

use App\Entity\Game;
use App\Entity\Team;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TeamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de l\'équipe',
                'attr' => ['placeholder' => 'Ex: Vitality, Karmine Corp...']
            ])
            
            ->add('game', EntityType::class, [
                'class' => Game::class,
                'choice_label' => 'label',  
                'label' => 'Jeu principal',
                'placeholder' => 'Sélectionnez votre jeu de prédilection',
                'expanded' => false,
                'multiple' => false,
                'required' => true, 
                'query_builder' => function (\Doctrine\ORM\EntityRepository $er) {
                    return $er->createQueryBuilder('g')
                        ->orderBy('g.label', 'ASC');
                },
            ])
            
            ->add('description', TextareaType::class, [
                'label' => 'Présentation',
                'required' => false,
                'attr' => ['rows' => 5, 'placeholder' => 'Décrivez votre équipe...']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Team::class,
        ]);
    }
}