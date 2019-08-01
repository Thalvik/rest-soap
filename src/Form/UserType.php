<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

class UserType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('email', TextType::class, [
            'constraints' => [
                new NotBlank(),
                new Email()
            ]
        ]);

        $builder->add('plainPassword', TextType::class, [
            'constraints' => [
                new NotBlank()
            ]
        ]);

        $builder->add('firstName', TextType::class, [])
        ->add('lastName', TextType::class, [])
        ->add('street', TextType::class, [])
        ->add('country', TextType::class, []);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\User',
            'csrf_protection' => false,
            'constraints' => array(
                new UniqueEntity(array(
                    'fields' => 'email',
                    'message' => 'common.email_exists'
                ))
            )
        ));
    }
}
