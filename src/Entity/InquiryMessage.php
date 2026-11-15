<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'inquiry_messages')]
class InquiryMessage
{
    public const FROM_CUSTOMER = 'customer';
    public const FROM_ADMIN    = 'admin';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Inquiry $inquiry = null;

    #[ORM\Column(length: 10)]
    private string $sender = self::FROM_CUSTOMER;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Please type a message before sending.')]
    #[Assert\Length(max: 2000, maxMessage: 'The message is too long (max 2000 characters).')]
    private string $body = '';

    #[ORM\Column]
    private bool $isRead = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getInquiry(): ?Inquiry { return $this->inquiry; }
    public function setInquiry(?Inquiry $inquiry): static { $this->inquiry = $inquiry; return $this; }

    public function getSender(): string { return $this->sender; }
    public function setSender(string $sender): static { $this->sender = $sender; return $this; }

    public function getBody(): string { return $this->body; }
    public function setBody(?string $body): static { $this->body = trim((string) $body); return $this; }

    public function isRead(): bool { return $this->isRead; }
    public function setIsRead(bool $isRead): static { $this->isRead = $isRead; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}