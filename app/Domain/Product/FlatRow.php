<?php
declare(strict_types=1);
namespace App\Domain\Product;

final class FlatRow
{
    public function __construct(
        public readonly string $sku,
        public readonly string $name,
        public readonly string $originCountry,
        public readonly string $originRegion,
        public readonly string $originFarm,
        public readonly ?int $originAltitudeM,
        public readonly string $originProcess,
        public readonly ?float $originLat,
        public readonly ?float $originLng,
        public readonly string $roastLevel,
        public readonly string $roastedOn,
        public readonly string $roaster,
        public readonly ?string $flavorNotes,
        public readonly ?string $tags,
        public readonly int $tastingAcidity,
        public readonly int $tastingBody,
        public readonly int $tastingSweetness,
        public readonly int $tastingAroma,
        public readonly int $tastingBitterness,
        public readonly bool $inStock,
        public readonly ?string $variantSize,
        public readonly ?string $variantGrind,
        public readonly ?float $variantPriceEur,
        public readonly ?int $variantStock,
        public readonly ?string $variantSku,
        public readonly ?string $description,
    ) {
    }

    public function toArray(): array
    {
        return [
            'sku' => $this->sku,
            'name' => $this->name,
            'origin_country' => $this->originCountry,
            'origin_region' => $this->originRegion,
            'origin_farm' => $this->originFarm,
            'origin_altitude_m' => $this->originAltitudeM,
            'origin_process' => $this->originProcess,
            'origin_lat' => $this->originLat,
            'origin_lng' => $this->originLng,
            'roast_level' => $this->roastLevel,
            'roasted_on' => $this->roastedOn,
            'roaster' => $this->roaster,
            'flavor_notes' => $this->flavorNotes,
            'tags' => $this->tags,
            'tasting_acidity' => $this->tastingAcidity,
            'tasting_body' => $this->tastingBody,
            'tasting_sweetness' => $this->tastingSweetness,
            'tasting_aroma' => $this->tastingAroma,
            'tasting_bitterness' => $this->tastingBitterness,
            'in_stock' => $this->inStock ? 1 : 0,
            'variant_size' => $this->variantSize,
            'variant_grind' => $this->variantGrind,
            'variant_price_eur' => $this->variantPriceEur,
            'variant_stock' => $this->variantStock,
            'variant_sku' => $this->variantSku,
            'description' => $this->description,
        ];
    }
}