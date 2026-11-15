<?php

namespace App\Entity;

use App\Repository\InquiryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InquiryRepository::class)]
#[ORM\Table(name: 'inquiries')]
class Inquiry
{
    public const STATUS_OPEN   = 'Open';
    public const STATUS_CLOSED = 'Closed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $customerName = '';

    #[ORM\Column(length: 150)]
    private string $subject = '';

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_OPEN;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, InquiryMessage> */
    #[ORM\OneToMany(targetEntity: InquiryMessage::class, mappedBy: 'inquiry', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $messages;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
        $this->messages  = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getCustomerName(): string { return $this->customerName; }
    public function setCustomerName(string $customerName): static { $this->customerName = $customerName; return $this; }

    public function getSubject(): string { return $this->subject; }
    public function setSubject(string $subject): static { $this->subject = $subject; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    /** @return Collection<int, InquiryMessage> */
    public function getMessages(): Collection { return $this->messages; }

    public function addMessage(InquiryMessage $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setInquiry($this);
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getUnreadCount(): int
    {
        return $this->messages->filter(
            static fn (InquiryMessage $m) => $m->getSender() === InquiryMessage::FROM_CUSTOMER && !$m->isRead()
        )->count();
    }

    public function markAsRead(): void
    {
        foreach ($this->messages as $message) {
            if ($message->getSender() === InquiryMessage::FROM_CUSTOMER && !$message->isRead()) {
                $message->setIsRead(true);
            }
        }
    }
}