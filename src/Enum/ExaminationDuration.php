<?php

namespace App\Enum;

enum ExaminationDuration: string
{
    case LONG = 'Long';

    case MEDIUM = 'Medium';

    case SHORT = 'Short';

    case MINI = 'Mini';
}
