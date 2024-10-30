<?php

// src/Form/FigureType.php

namespace App\Form;

use App\Entity\Figure;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class FigureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la figure',
                'required' => true,
                'attr' => [
                    'class' => 'form__input',
                    'required' => true,
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'attr' => [
                    'class' => 'form__input',
                    'required' => true,
                ],
            ])
            ->add('categories', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices'  => [
                    'Ollie' => 'ollie',
                    'Nollie' => 'nollie',
                    'Kickflip' => 'kickflip',
                    'Heelflip' => 'heelflip',
                    'Shuvit' => 'shuvit',
                    'Pop Shuvit' => 'pop_shuvit',
                    'Frontside 180' => 'frontside_180',
                    'Backside 180' => 'backside_180',
                    'Caboose' => 'caboose',
                    'Mute Grab' => 'mute_grab',
                ],
                'expanded' => false,
                'multiple' => false,
                'required' => true,
                'attr' => [
                    'class' => 'form__select',
                    'required' => true,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Figure::class,
        ]);
    }
}