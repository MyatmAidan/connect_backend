<?php

namespace App\Enums;

enum EventRegistrationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
