<?php
declare(strict_types=1);
namespace App\Domain\Product\Services;

use App\Domain\Product\FlatRow;
use App\Domain\Product\Product;
use App\Domain\Product\Variant;

final class ProductFlattenService
{
    public function flatten(Product $product): array
    {
        // If there are no variants, we still want to return a single row for the product
        if (empty($product->variants)) {
            return [$this->buildRow($product)];
        }

        // For each variant, we create a separate flat row that includes both product and variant data
        return array_map(
            fn (Variant $variant) => $this->buildRow($product, $variant),
            $product->variants
        );
    }

    private function buildRow(Product $product, ?Variant $variant = null): FlatRow
    {
        return new FlatRow(
            sku: $product?->sku,
            name: $product->name,
            originCountry: $product->origin->country,
            originRegion: $product->origin->region,
            originFarm: $product->origin->farm,
            originAltitudeM: $product->origin->altitudeM,
            originProcess: $product->origin->process,
            originLat: $product->origin->lat,
            originLng: $product->origin->lng,
            roastLevel: $product->roast->level,
            roastedOn: $product->roast->roastedOn,
            roaster: $product->roast->roaster,
            flavorNotes: $this->serializeArray($product->flavorNotes),
            tags: $this->serializeArray($product->tags),
            tastingAcidity: $product->tastingScore->acidity,
            tastingBody: $product->tastingScore->body,
            tastingSweetness: $product->tastingScore->sweetness,
            tastingAroma: $product->tastingScore->aroma,
            tastingBitterness: $product->tastingScore->bitterness,
            inStock: $product->inStock,
            variantSize: $variant?->size,
            variantGrind: $variant?->grind,
            variantPriceEur: $variant?->priceEur,
            variantStock: $variant?->stock,
            variantSku: $variant?->sku,
            description: $product->description,
        );
    }

    private function serializeArray(array $data): ?string
    {
        return empty($data) ? null : implode('|', $data);
    }
}