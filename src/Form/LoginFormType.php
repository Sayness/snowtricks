<?php
// src/Form/LoginForm.php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

class LoginFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('_username', EmailType::class, [
            'label' => 'Email',
            'attr' => [
                'class' => 'form__input',
                'required' => true,
            ],
        ])
        ->add('_password', PasswordType::class, [
            'label' => 'Mot de passe',
            'attr' => [
                'class' => 'form__input',
                'required' => true,
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([]);
    }
}
