<?php
declare(strict_types=1);
namespace App\Domain\Product;

use App\Domain\Product\Exceptions\InvalidProductDataException;

final class Product
{
    public function __construct(
        public readonly string $sku,
        public readonly string $name,
        public readonly Origin $origin,
        public readonly Roast $roast,
        public readonly array $flavorNotes,
        public readonly array $tags,
        public readonly TastingScore $tastingScore,
        public readonly bool $inStock,
        public readonly array $variants,
        public readonly ?string $description,
    ) {
    }

    public static function fromArray(array $data): self
    {
        if (empty($data['sku'])) {
            throw InvalidProductDataException::missingField('sku');
        }

        if (empty($data['name'])) {
            throw InvalidProductDataException::missingField('name');
        }

        return new self(
            sku: $data['sku'],
            name: $data['name'],
            origin: Origin::fromArray($data['origin'] ?? []),
            roast: Roast::fromArray($data['roast'] ?? []),
            flavorNotes: $data['flavor_notes'] ?? [],
            tags: $data['tags'] ?? [],
            tastingScore: TastingScore::fromArray($data['tasting_score'] ?? []),
            inStock: (bool)($data['in_stock'] ?? false),
            variants: array_map(fn($v) => Variant::fromArray($v), $data['variants'] ?? []),
            description: $data['description'] ?? null,
        );
    }
}