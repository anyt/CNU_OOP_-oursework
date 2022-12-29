<?php

namespace App\Constraints;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ReservedDatesValidator extends ConstraintValidator
{

    public function __construct(private readonly ReservationRepository $reservationRepository)
    {
    }

    /**
     * @inheritDoc
     * @param Reservation $value
     */
    public function validate(mixed $value, Constraint $constraint)
    {
        $resource = $value->getResource();
        $startsAt = $value->getStartsAt();
        $endsAt = $value->getEndsAt();

        $reservedDates = $this->reservationRepository->getReservedDates($resource);
        foreach ($reservedDates as $reservedDate) {
            if ($reservedDate >= $startsAt && $reservedDate <= $endsAt) {
                $this->context->buildViolation('Вибрані дати недоступні. Будь ласкаб спробуйте інші.')
                    ->addViolation();

                return;

            }
        }
    }
}