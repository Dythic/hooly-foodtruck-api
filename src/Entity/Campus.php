<?php

namespace App\Entity;

use App\Repository\CampusRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CampusRepository::class)]
class Campus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'campus')]
    private Collection $reservations;

    #[ORM\Column(type: "json")]
    private array $available_slots = [];

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setCampus($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            if ($reservation->getCampus() === $this) {
                $reservation->setCampus(null);
            }
        }

        return $this;
    }

    public function getAvailableSlots(): array
    {
        return $this->available_slots;
    }

    public function setAvailableSlots(array $available_slots): static
    {
        $this->available_slots = $available_slots;

        return $this;
    }

    public function getAvailableSlotsForDay(string $day): ?int
    {
        return $this->available_slots[$day] ?? null;
    }

    public function setAvailableSlotsForDay(string $day, int $slots): static
    {
        $this->available_slots[$day] = $slots;

        return $this;
    }
}
