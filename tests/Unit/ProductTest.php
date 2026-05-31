<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Domain\Product\Product;
use App\Domain\Product\Exceptions\InvalidProductDataException;

class ProductTest extends TestCase
{
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

    public function test_builds_successfully_from_valid_data()
    {
        $product = Product::fromArray($this->data());

        $this->assertSame('BEAN-001', $product->sku);
        $this->assertSame('Premium Coffee Beans', $product->name);
    }

    public function test_throws_when_sku_is_missing()
    {
        $this->expectException(InvalidProductDataException::class);
        Product::fromArray(array_merge($this->data(), ['sku' => '']));
    }

    public function test_throws_when_name_is_missing()
    {
        $this->expectException(InvalidProductDataException::class);
        Product::fromArray(array_merge($this->data(), ['name' => '']));
    }

    public function test_description_is_nullable()
    {
        $data = $this->data();
        unset($data['description']);

        $product = Product::fromArray($data);

        $this->assertNull($product->description);
    }

    public function test_in_stock_defaults_to_false()
    {
        $data = $this->data();
        unset($data['in_stock']);

        $product = Product::fromArray($data);

        $this->assertFalse($product->inStock);
    }

    public function test_null_altitude_is_handled()
    {
        $data = $this->data();
        unset($data['origin']['altitude_m']);

        $product = Product::fromArray($data);

        $this->assertNull($product->origin->altitudeM);
    }

    public function test_missing_coordinates_are_handled()
    {
        $data = $this->data();
        unset($data['origin']['coordinates']);

        $product = Product::fromArray($data);

        $this->assertNull($product->origin->lat);
        $this->assertNull($product->origin->lng);
    }
}