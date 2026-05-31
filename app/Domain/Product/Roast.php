<?php
declare(strict_types=1);
namespace App\Domain\Product;

final class Roast
{
    public function __construct(
        public readonly string $level,
        public readonly string $roastedOn,
        public readonly string $roaster,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            level: $data['level'] ?? '',
            roastedOn: $data['roasted_on'] ?? '',
            roaster: $data['roaster'] ?? null,
        );
    }
}