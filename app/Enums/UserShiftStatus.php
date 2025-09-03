<?php

namespace App\Enums;

enum UserShiftStatus: string
{
    case SCHEDULED = 'scheduled';

    case ONGOING = 'ongoing';

    case COMPLETED = 'completed';

    case CANCELLED = 'cancelled';

    case MISSED = 'missed';

}
