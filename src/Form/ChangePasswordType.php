<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label'       => 'Current password',
                'mapped'      => false,
                'attr'        => ['class' => 'form-input', 'autocomplete' => 'current-password'],
                'constraints' => [
                    new NotBlank(message: 'Please enter your current password.'),
                    new UserPassword(message: 'Your current password is wrong.'),
                ],
            ])
            ->add('newPassword', RepeatedType::class, [
                'type'            => PasswordType::class,
                'invalid_message' => 'The two passwords do not match.',
                'first_options'   => [
                    'label' => 'New password',
                    'attr'  => ['class' => 'form-input', 'autocomplete' => 'new-password'],
                ],
                'second_options'  => [
                    'label' => 'Repeat new password',
                    'attr'  => ['class' => 'form-input', 'autocomplete' => 'new-password'],
                ],
                'constraints'     => [
                    new NotBlank(message: 'Please enter a new password.'),
                    new Length(min: 6, minMessage: 'The password must have at least 6 characters.'),
                ],
            ]);
    }
}