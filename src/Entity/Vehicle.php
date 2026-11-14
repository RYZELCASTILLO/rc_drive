<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'vehicles')]
class Vehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name = '';

    #[ORM\Column(length: 100)]
    private string $brand = '';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $pricePerDay = '0.00';

    #[ORM\Column]
    private int $totalUnits = 0;

    #[ORM\Column(length: 20)]
    private string $availability = 'Available';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $image = null;

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getBrand(): string { return $this->brand; }
    public function setBrand(string $brand): static { $this->brand = $brand; return $this; }

    public function getPricePerDay(): string { return $this->pricePerDay; }
    public function setPricePerDay(string $pricePerDay): static { $this->pricePerDay = $pricePerDay; return $this; }

    public function getTotalUnits(): int { return $this->totalUnits; }
    public function setTotalUnits(int $totalUnits): static { $this->totalUnits = $totalUnits; return $this; }

    public function getAvailability(): string { return $this->availability; }
    public function setAvailability(string $availability): static { $this->availability = $availability; return $this; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): static { $this->image = $image; return $this; }
}