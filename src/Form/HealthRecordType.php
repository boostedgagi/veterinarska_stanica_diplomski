<?php

namespace App\Form;

use App\Entity\HealthRecord;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HealthRecordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('vet',null,[
                'required'=>true
            ])
            ->add('pet',null,[
                'required'=>true
            ])
            ->add('examination',null,[
                'required'=>true
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
