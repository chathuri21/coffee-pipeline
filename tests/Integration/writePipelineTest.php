<?php

namespace Tests\Integration;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Application\Commands\FeedCommand;
use App\Application\Commands\FeedHandler;
use App\Application\Commands\FeedResult;
use App\Domain\Product\Services\ProductFlattenService;
use App\Infrastructure\Persistence\SqliteProductVariantRepository;
use App\Infrastructure\Reader\JsonlReader;
use Psr\Log\NullLogger;

class writePipelineTest extends TestCase
{
    use RefreshDatabase;

    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir() . '/ingest_test';
        if (!file_exists($this->tmpDir)) {
            mkdir($this->tmpDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up temp directory after tests
        array_map('unlink', glob($this->tmpDir . '/*'));
        rmdir($this->tmpDir);

        parent::tearDown();
    }

    private function runPipeline(string $feedPath): FeedResult
    {
        $handler = new FeedHandler(
            reader: new JsonlReader(new NullLogger()),
            flattenService: new ProductFlattenService(),
            repository: new SqliteProductVariantRepository(),
            logger: new NullLogger(),
        );
        return $handler->handle(new FeedCommand(feedPath: $feedPath));
    }

    private function writeFeed(array $products): string
    {
        $content = implode("\n", array_map('json_encode', $products)) . "\n";
        return $this->writeRaw($content);
    }

    private function writeRaw(string $content): string
    {
        $path = $this->tmpDir . '/feed.jsonl';
        file_put_contents($path, $content);
        return $path;
    }

    private function product(string $sku, int $variantCount = 1): array
    {
        $variants = [];
        for ($i = 1; $i <= $variantCount; $i++) {
            $variants[] = [
                'sku_variant' => "{$sku}-VAR{$i}",
                'size' => '250g',
                'grind' => 'Whole Bean',
                'price_eur' => 9.99,
                'stock' => 100,
            ];
        }

        return [
            'sku' => $sku,
            'name' => "Product Bean",
            'in_stock' => true,
            'origin' => [
                'country' => 'Ethiopia',
                'region' => 'Sidamo',
                'farm' => 'Sunrise Farm',
                'altitude_m' => 1500,
                'process' => 'Washed',
            ],
            'roast' => [
                'level' => 'Medium',
                'roasted_on' => '2024-05-01',
                'roaster' => 'RoastMaster Co.',
            ],
            'flavor_notes' => [],
            'tags' => [],
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

    private function knownProduct(): array
    {
        return [
            'sku' => 'BEAN-KV',
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
            'variants' => [
                [
                    'sku_variant' => "BEAN-001-VAR1",
                    'size' => '250g',
                    'grind' => 'Whole Bean',
                    'price_eur' => 9.99,
                    'stock' => 100,
                ],
            ],
        ];
    }

    public function test_correct_row_count_written_to_database()
    {
        $products = [
            $this->product('BEAN-001', 2), // 2 variants
            $this->product('BEAN-002', 3), // 3 variants
            $this->product('BEAN-003', 1), // 1 variant
        ];
        $feedPath = $this->writeFeed($products);

        $result = $this->runPipeline($feedPath);

        $this->assertSame(3, $result->productsRead);
        $this->assertSame(6, $result->rowsWritten);
        $this->assertSame(0, $result->errors);

        $this->assertDatabaseCount('product_variants', 6);
    }

    public function test_malformed_json_line_is_skipped_pipeline_continues()
    {
        $products = [
            
        ];
        $feedPath = $this->writeRaw(
            json_encode($this->product('BEAN-001', 2)) . "\n" .
            "THIS IS NOT JSON\n" .
            json_encode($this->product('BEAN-002', 3)) . "\n"
        );

        $result = $this->runPipeline($feedPath);

        $this->assertSame(2, $result->productsRead);

        $this->assertDatabaseCount('product_variants', 5);
    }

    public function test_product_with_no_variants_is_logged_as_error_but_pipeline_continues()
    {
        $products = [
            $this->product('BEAN-001', 0), // No variants
            $this->product('BEAN-002', 2), // 2 variants
        ];
        $feedPath = $this->writeFeed($products);

        $result = $this->runPipeline($feedPath);

        $this->assertSame(2, $result->productsRead);
        $this->assertSame(3, $result->rowsWritten);
        $this->assertSame(0, $result->errors);

        $this->assertDatabaseCount('product_variants', 3);
    }

    public function test_product_missing_sku_is_skipped()
    {
        $products = [
            array_merge($this->product('BEAN-A'), ['sku' => '']), // Invalid product
            $this->product('BEAN-002', 2), // Valid product
        ];

        $feedPath = $this->writeFeed($products);
        $result = $this->runPipeline($feedPath);

        $this->assertSame(1, $result->productsRead);

        $this->assertDatabaseCount('product_variants', 2);
    }

    public function test_second_run_replaces_data_not_Appends()
    {
        $feedPath1 = $this->writeFeed([
            $this->product('BEAN-001', 2),
        ]);
        $this->runPipeline($feedPath1);

        $feedPath2 = $this->writeFeed([
            $this->product('BEAN-002', 3),
        ]);
        $result = $this->runPipeline($feedPath2);

        $this->assertDatabaseCount('product_variants', 3);
    }

    public function test_field_values_written_to_database()
    {
        $product = $this->knownProduct();

        $feedPath = $this->writeFeed([$product]);
        $result = $this->runPipeline($feedPath);

        $this->assertDatabaseHas('product_variants', [
            'sku' => 'BEAN-KV',
            'name' => 'Premium Coffee Beans',
            'in_stock' => true,
            'origin_country' => 'Ethiopia',
            'roast_level' => 'Medium',
            'variant_sku' => 'BEAN-001-VAR1',
            'flavor_notes' => 'Citrus|Chocolate|Floral',
            'tags' => 'organic|single-origin',
            'tasting_acidity' => 8,
        ]);
    }

    public function test_null_altitude_stored_as_null_in_database()
    {
        $product = $this->knownProduct();
        unset($product['origin']['altitude_m']);

        $feedPath = $this->writeFeed([$product]);
        $this->runPipeline($feedPath);

        $this->assertDatabaseHas('product_variants', [
            'sku' => 'BEAN-KV',
            'origin_altitude_m' => null,
        ]);
    }
}

