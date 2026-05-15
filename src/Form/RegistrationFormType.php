<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'form.email',
                'attr' => ['class' => 'form-control', 'placeholder' => 'your@email.com'],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'form.password',
                    'attr' => ['class' => 'form-control', 'autocomplete' => 'new-password'],
                    'constraints' => [
                        new NotBlank(message: 'validation.password_required'),
                        new Length(min: 8, minMessage: 'validation.password_min_length'),
                    ],
                ],
                'second_options' => [
                    'label' => 'form.confirm_password',
                    'attr' => ['class' => 'form-control'],
                ],
                'invalid_message' => 'validation.passwords_mismatch',
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
