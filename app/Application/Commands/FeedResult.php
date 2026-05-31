<?php
declare(strict_types=1);
namespace App\Application\Commands;

final class FeedResult
{
    public function __construct(
        public readonly int $productsRead,
        public readonly int $rowsWritten,
        public readonly int $errors,
    ) {
    }
}