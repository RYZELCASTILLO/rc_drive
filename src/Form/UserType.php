<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $passwordRules = [
            new Length(min: 6, minMessage: 'The password must have at least 6 characters.'),
        ];
        if (!$isEdit) {
            array_unshift($passwordRules, new NotBlank(message: 'Please enter a password.'));
        }

        $builder
            ->add('name', TextType::class, [
                'label' => 'Full name',
                'attr'  => ['class' => 'form-input', 'maxlength' => 100],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr'  => ['class' => 'form-input'],
            ])
            ->add('role', ChoiceType::class, [
                'label'   => 'Role',
                'choices' => ['Staff' => User::ROLE_STAFF, 'Admin' => User::ROLE_ADMIN],
                'attr'    => ['class' => 'form-input'],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type'            => PasswordType::class,
                'mapped'          => false,
                'required'        => !$isEdit,
                'invalid_message' => 'The two passwords do not match.',
                'first_options'   => [
                    'label' => $isEdit ? 'Reset password (leave blank to keep the current one)' : 'Password',
                    'attr'  => ['class' => 'form-input', 'autocomplete' => 'new-password'],
                ],
                'second_options'  => [
                    'label' => 'Repeat password',
                    'attr'  => ['class' => 'form-input', 'autocomplete' => 'new-password'],
                ],
                'constraints'     => $passwordRules,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit'    => false,
        ]);
        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}