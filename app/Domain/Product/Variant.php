<?php
declare(strict_types=1);
namespace App\Domain\Product;

final class Variant
{
    public function __construct(
        public readonly ?string $size,
        public readonly ?string $grind,
        public readonly ?float $priceEur,
        public readonly ?int $stock,
        public readonly ?string $sku,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            size: $data['size'] ?? null,
            grind: $data['grind'] ?? null,
            priceEur: isset($data['price_eur']) ? (float)$data['price_eur'] : null,
            stock: isset($data['stock']) ? (int)$data['stock'] : null,
            sku: $data['sku_variant'] ?? null,
        );
    }
}