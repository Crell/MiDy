<?php
declare(strict_types=1);

namespace Crell\MiDy\Services;

enum MimeFormat: string
{
    case Html = 'text/html';
    case Json = 'application/json';
}
