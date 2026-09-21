<?php

namespace App\Services;

use RuntimeException;

class AbsensiScanException extends RuntimeException
{
    public static function reason(string $message): self
    {
        return new self($message);
    }
}
