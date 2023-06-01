<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email')
            ->add('password')
            ->add('firstName')
            ->add('lastName')
            ->add('plainPassword',null,[
                'required'=>false
            ])
            ->add('typeOfUser',null,[
                'required'=>false
            ])
            ->add('image')
            ->add('phone')
            ->add('latitude')
            ->add('longitude')
            ->add('vet', null,[
                'required'=>false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'allow_extra_fields'=>true
        ]);
    }
}
