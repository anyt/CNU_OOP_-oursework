<?php

namespace App\Entity;

use App\Constraints\ReservedDates;
use App\Repository\ReservationRepository;
use DateInterval;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ReservedDates]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50, enumType: ReservationState::class)]
    private ?ReservationState $state;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $client = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Resource $resource = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $startsAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $endsAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): ?User
    {
        return $this->client;
    }

    public function setClient(?User $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getResource(): ?Resource
    {
        return $this->resource;
    }

    public function setResource(?Resource $resource): self
    {
        $this->resource = $resource;

        return $this;
    }

    public function getStartsAt(): ?DateTimeInterface
    {
        return $this->startsAt;
    }

    public function setStartsAt(DateTimeInterface $startsAt): self
    {
        $this->startsAt = $startsAt;

        return $this;
    }

    public function getEndsAt(): ?DateTimeInterface
    {
        return $this->endsAt;
    }

    public function setEndsAt(?DateTimeInterface $endsAt): void
    {
        $this->endsAt = $endsAt;
    }


    public function getReservedFor(): ?DateInterval
    {
        return $this->startsAt->diff($this->endsAt);
    }

    public function getDates(): string
    {
        return $this->startsAt?->format('m/d/Y').' - '.$this->endsAt?->format('m/d/Y');
    }

    public function setDates(string $dates): self
    {
        [$start, $end] = explode(' - ', $dates);
        $this->startsAt = new DateTime($start, new DateTimeZone('UTC'));
        $this->endsAt = new DateTime($end, new DateTimeZone('UTC'));

        return $this;
    }

    public function getState(): ?ReservationState
    {
        return $this->state;
    }

    public function setState(?ReservationState $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getStateValue(): ?string
    {
        return $this->state->value;
    }

    public function setStateValue(string $place): self
    {
        $this->setState(ReservationState::from($place));

        return $this;
    }

    public function __toString(): string
    {
        return 'Reservation of "'.$this->getResource()->getName().'" for "'.$this->getClient()->getUsername(
            ).'" to '.$this->getDates();
    }
}
