<?php

namespace App\Form;

use App\Entity\HealthRecord;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HealthRecordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('vet',EntityType::class,[
                'required'=>false,
                'class'=>User::class
            ])
            ->add('pet',null,[
                'required'=>false
            ])
            ->add('examination',null,[
                'required'=>false
            ])
            ->add('startedAt', DateTimeType::class, [
                'required' => false,
                'widget' => 'single_text'
            ])
            ->add('finishedAt', DateTimeType::class, [
                'required' => false,
                'widget' => 'single_text'
            ])
            ->add('comment')
            ->add('status')
            ->add('madeByVet');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HealthRecord::class,
            'allow_extra_fields' => true
        ]);
    }
}
