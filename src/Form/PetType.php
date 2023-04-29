<?php

namespace App\Form;

use App\Entity\Pet;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\DBAL\Types\DateTimeImmutableType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('dateOfBirth', null, [
                'widget' => 'single_text'
            ])
            ->add('animal', ChoiceType::class, [
                'choices' => [
                    'Dog' => 'Dog',
                    'Cat' => 'Cat'
                ]])
            ->add('breed')
            ->add('owner');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Pet::class,
            'allow_extra_fields' => true
        ]);
    }
}
