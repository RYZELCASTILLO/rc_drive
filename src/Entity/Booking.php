<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\Table(name: 'bookings')]
class Booking
{
    public const STATUSES = ['Pending', 'Approved', 'Picked Up', 'Cancel Requested', 'Returned', 'Rejected', 'Cancelled'];
    public const PAYMENT_STATUSES = ['Unpaid', 'Pending Verification', 'Partial', 'Paid'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Please choose a vehicle.')]
    private ?Vehicle $vehicle = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Please enter the customer name.')]
    #[Assert\Length(max: 100, maxMessage: 'The name is too long (max 100 characters).')]
    private string $customerName = '';

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Please enter a phone number.')]
    #[Assert\Regex(pattern: '/^[0-9+\-\s()]{7,20}$/', message: 'Please enter a valid phone number (digits, spaces, + - ( ) only).')]
    private string $phone = '';

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull(message: 'Please choose a pickup date.')]
    private ?\DateTimeImmutable $pickupDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull(message: 'Please choose a return date.')]
    private ?\DateTimeImmutable $returnDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalFee = '0.00';

    #[ORM\Column(length: 30)]
    private string $status = 'Pending';

    #[ORM\Column(length: 30)]
    private string $paymentStatus = 'Unpaid';

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $paymentType = null;

    #[ORM\Column(length: 60, nullable: true)]
    private ?string $paymentReference = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $amountPaid = '0.00';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cancelReason = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $lateFee = '0.00';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[Assert\Callback]
    public function validateDates(ExecutionContextInterface $context): void
    {
        if ($this->pickupDate && $this->returnDate && $this->returnDate < $this->pickupDate) {
            $context->buildViolation('The return date cannot be earlier than the pickup date.')
                ->atPath('returnDate')
                ->addViolation();
        }
    }

    public function getRentalDays(): int
    {
        if (!$this->pickupDate || !$this->returnDate) {
            return 0;
        }

        return max(1, (int) $this->pickupDate->diff($this->returnDate)->days);
    }

    public function recalculateTotal(): void
    {
        if (!$this->vehicle) {
            return;
        }

        $this->totalFee = number_format($this->getRentalDays() * (float) $this->vehicle->getPricePerDay(), 2, '.', '');
    }

    public function getId(): ?int { return $this->id; }

    public function getVehicle(): ?Vehicle { return $this->vehicle; }
    public function setVehicle(?Vehicle $vehicle): static { $this->vehicle = $vehicle; return $this; }

    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): static { $this->createdBy = $createdBy; return $this; }

    public function getCustomerName(): string { return $this->customerName; }
    public function setCustomerName(?string $customerName): static { $this->customerName = trim((string) $customerName); return $this; }

    public function getPhone(): string { return $this->phone; }
    public function setPhone(?string $phone): static { $this->phone = trim((string) $phone); return $this; }

    public function getPickupDate(): ?\DateTimeImmutable { return $this->pickupDate; }
    public function setPickupDate(?\DateTimeImmutable $pickupDate): static { $this->pickupDate = $pickupDate; return $this; }

    public function getReturnDate(): ?\DateTimeImmutable { return $this->returnDate; }
    public function setReturnDate(?\DateTimeImmutable $returnDate): static { $this->returnDate = $returnDate; return $this; }

    public function getTotalFee(): string { return $this->totalFee; }
    public function setTotalFee(string $totalFee): static { $this->totalFee = $totalFee; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getPaymentStatus(): string { return $this->paymentStatus; }
    public function setPaymentStatus(string $paymentStatus): static { $this->paymentStatus = $paymentStatus; return $this; }

    public function getPaymentType(): ?string { return $this->paymentType; }
    public function setPaymentType(?string $paymentType): static { $this->paymentType = $paymentType; return $this; }

    public function getPaymentReference(): ?string { return $this->paymentReference; }
    public function setPaymentReference(?string $paymentReference): static { $this->paymentReference = $paymentReference; return $this; }

    public function getAmountPaid(): string { return $this->amountPaid; }
    public function setAmountPaid(string $amountPaid): static { $this->amountPaid = $amountPaid; return $this; }

    public function getCancelReason(): ?string { return $this->cancelReason; }
    public function setCancelReason(?string $cancelReason): static { $this->cancelReason = $cancelReason; return $this; }

    public function getLateFee(): string { return $this->lateFee; }
    public function setLateFee(string $lateFee): static { $this->lateFee = $lateFee; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}