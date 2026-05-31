<?php
declare(strict_types=1);
namespace App\Domain\Product\Exceptions;

use RuntimeException;

final class InvalidProductDataException extends RuntimeException
{
    public static function missingField(string $field): self
    {
        return new self("Missing required field: {$field}");
    }
}