<?php

namespace App\Form;

use App\Entity\OnCall;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OnCallType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('vet')
            ->add('finishedAt', null,
                [
                    "required" => false,
//                    "format" => "yyyy-MM-dd HH:mm",
                    "widget" => "single_text"
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OnCall::class,
        ]);
    }
}
