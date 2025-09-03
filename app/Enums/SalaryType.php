<?php

namespace App\Enums;

enum SalaryType: string
{
    case HOURLY = 'hourly';

    case DAILY = 'daily';

    case WEEKLY = 'weekly';

    case MONTHLY = 'monthly';

    case COMMISSION = 'commission';

    case CONTRACT = 'contract';

}
