<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Domain\Product\Product;
use App\Domain\Product\Services\ProductFlattenService;

class ProductFlattenServiceTest extends TestCase
{
    private ProductFlattenService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductFlattenService();
    }

    private function data(int $variantCount = 1): array
    {
        $variants = [];
        for ($i = 1; $i <= $variantCount; $i++) {
            $variants[] = [
                'sku_variant' => "BEAN-001-VAR{$i}",
                'size' => '250g',
                'grind' => 'Whole Bean',
                'price_eur' => 9.99,
                'stock' => 100,
            ];
        }

        return [
            'sku' => 'BEAN-001',
            'name' => 'Premium Coffee Beans',
            'description' => 'A bag of premium coffee beans.',
            'in_stock' => true,
            'origin' => [
                'country' => 'Ethiopia',
                'region' => 'Sidamo',
                'farm' => 'Sunrise Farm',
                'altitude_m' => 1500,
                'process' => 'Washed',
                'coordinates' => [
                    'lat' => 6.123456,
                    'lng' => 38.123456,
                ],
            ],
            'roast' => [
                'level' => 'Medium',
                'roasted_on' => '2024-05-01',
                'roaster' => 'RoastMaster Co.',
            ],
            'flavor_notes' => ['Citrus', 'Chocolate', 'Floral'],
            'tags' => ['organic', 'single-origin'],
            'tasting_score' => [
                'acidity' => 8,
                'body' => 7,
                'sweetness' => 6,
                'aroma' => 9,
                'bitterness' => 4,
            ],
            'variants' => $variants,
        ];
    }

    public function test_one_variantproduces_one_row(): void
    {
        $product = Product::fromArray($this->data(1));

        $flatRows = $this->service->flatten($product);

        $this->assertCount(1, $flatRows);
    }

    public function test_three_variants_produce_three_rows(): void
    {
        $product = Product::fromArray($this->data(3));

        $flatRows = $this->service->flatten($product);

        $this->assertCount(3, $flatRows);
    }

    public function test_zero_variants_produce_one_row_with_product_data(): void
    {
        $product = Product::fromArray($this->data(0));

        $flatRows = $this->service->flatten($product);

        $this->assertCount(1, $flatRows);
    }

    public function test_zero_variants_produce_one_row_with_null_variant_fields(): void
    {
        $product = Product::fromArray($this->data(0));

        $flatRows = $this->service->flatten($product);

        $this->assertCount(1, $flatRows);
        $row = $flatRows[0];
        $this->assertNull($row->variantSku);
        $this->assertNull($row->variantSize);
        $this->assertNull($row->variantGrind);
        $this->assertNull($row->variantPriceEur);
        $this->assertNull($row->variantStock);
    }

    public function test_product_fields_are_mapped_correctly(): void
    {
        $product = Product::fromArray($this->data(2));

        $flatRows = $this->service->flatten($product);

        foreach ($flatRows as $row) {
            $this->assertSame('BEAN-001', $row->sku);
            $this->assertSame('Premium Coffee Beans', $row->name);
            $this->assertSame('Ethiopia', $row->originCountry);
            $this->assertSame('Medium', $row->roastLevel);
        }
    }

    public function test_variant_fields_differ_across_rows(): void
    {
        $product = Product::fromArray($this->data(2));

        $flatRows = $this->service->flatten($product);

        $this->assertNotSame($flatRows[0]->variantSku, $flatRows[1]->variantSku);
    }

    public function test_in_stock_true_serializes_to_1(): void
    {
        $product = Product::fromArray(array_merge($this->data(), ['in_stock' => true]));

        $flatRow = $this->service->flatten($product);

        $this->assertSame(1, $flatRow[0]->toArray()['in_stock']);
    }

    public function test_in_stock_false_serializes_to_0(): void
    {
        $product = Product::fromArray(array_merge($this->data(), ['in_stock' => false]));

        $flatRow = $this->service->flatten($product);

        $this->assertSame(0, $flatRow[0]->toArray()['in_stock']);
    }

    public function test_null_altitude_preserved_in_flat_row(): void
    {
        $data = $this->data();
        unset($data['origin']['altitude_m']);

        $product = Product::fromArray($data);

        $flatRow = $this->service->flatten($product);

        $this->assertNull($flatRow[0]->originAltitudeM);

    }

    public function test_missing_coordinates_give_null_lat_lng(): void
    {
        $data = $this->data();
        unset($data['origin']['coordinates']);

        $product = Product::fromArray($data);

        $flatRow = $this->service->flatten($product);

        $this->assertNull($flatRow[0]->originLat);
        $this->assertNull($flatRow[0]->originLng);
    }

    public function test_flavour_notes_are_pipe_joined(): void
    {
        $data = $this->data();

        $product = Product::fromArray($data);

        $flatRow = $this->service->flatten($product);

        $this->assertSame('Citrus|Chocolate|Floral', $flatRow[0]->flavorNotes);
    }

    public function test_empty_flavour_notes_become_null(): void
    {
        $data = $this->data();
        unset($data['flavor_notes']);

        $product = Product::fromArray($data);

        $flatRow = $this->service->flatten($product);

        $this->assertNull($flatRow[0]->flavorNotes);
    }

    public function test_empty_tags_become_null(): void
    {
        $data = $this->data();
        unset($data['tags']);

        $product = Product::fromArray($data);

        $flatRow = $this->service->flatten($product);

        $this->assertNull($flatRow[0]->tags);
    }
}