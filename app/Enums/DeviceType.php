<?php

namespace App\Enums;

enum DeviceType: string
{
    case ANDROID = 'android';

    case IOS = 'ios';

    case WEB = 'web';

    case LINUX = 'linux';

    case WINDOWS = 'windows';

    case MAC = 'mac';

    case OTHER = 'other';

}
