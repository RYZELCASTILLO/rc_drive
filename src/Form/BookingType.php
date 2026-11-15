<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\Vehicle;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('vehicle', EntityType::class, [
                'class'         => Vehicle::class,
                'label'         => 'Vehicle',
                'placeholder'   => 'Choose a vehicle',
                'choice_label'  => fn (Vehicle $v) => $v->getName() . ' - ₱' . number_format((float) $v->getPricePerDay(), 0) . '/day',
                'query_builder' => $isEdit ? null : fn (EntityRepository $er) => $er->createQueryBuilder('v')
                    ->where("v.availability = 'Available'")
                    ->orderBy('v.name', 'ASC'),
                'attr'          => ['class' => 'form-input'],
            ])
            ->add('customerName', TextType::class, [
                'label' => 'Customer name',
                'attr'  => ['class' => 'form-input', 'maxlength' => 100],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Phone number',
                'attr'  => ['class' => 'form-input', 'maxlength' => 20, 'inputmode' => 'tel'],
            ])
            ->add('pickupDate', DateType::class, [
                'label'       => 'Pickup date',
                'widget'      => 'single_text',
                'input'       => 'datetime_immutable',
                'attr'        => ['class' => 'form-input'],
                'constraints' => $isEdit ? [] : [
                    new GreaterThanOrEqual('today', message: 'The pickup date cannot be in the past.'),
                ],
            ])
            ->add('returnDate', DateType::class, [
                'label'  => 'Return date',
                'widget' => 'single_text',
                'input'  => 'datetime_immutable',
                'attr'   => ['class' => 'form-input'],
            ]);

        // Only admins can change the status of a booking
        if ($options['is_admin']) {
            $builder
                ->add('status', ChoiceType::class, [
                    'label'   => 'Booking status',
                    'choices' => array_combine(Booking::STATUSES, Booking::STATUSES),
                    'attr'    => ['class' => 'form-input'],
                ])
                ->add('paymentStatus', ChoiceType::class, [
                    'label'   => 'Payment status',
                    'choices' => array_combine(Booking::PAYMENT_STATUSES, Booking::PAYMENT_STATUSES),
                    'attr'    => ['class' => 'form-input'],
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
            'is_admin'   => false,
            'is_edit'    => false,
        ]);
        $resolver->setAllowedTypes('is_admin', 'bool');
        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}