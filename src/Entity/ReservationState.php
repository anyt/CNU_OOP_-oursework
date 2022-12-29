<?php

namespace App\Entity;

enum ReservationState: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Paid = 'paid';
    case Completed = 'completed';
    case Canceled = 'canceled';
}
