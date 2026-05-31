<?php
declare(strict_types=1);
namespace App\Infrastructure\Persistence;

use App\Domain\Product\FlatRow;
use App\Domain\Product\Repositories\ProductVariantRepository;
use Illuminate\Support\Facades\DB;

final class SqliteProductVariantRepository implements ProductVariantRepository
{
    private array $batch = [];
    private const BATCH_SIZE = 500;

    public function prepare(): void
    {
        DB::table('product_variants')->truncate();
        DB::beginTransaction();
    }

    public function save(FlatRow $flatRow): void
    {
        $this->batch[] = $flatRow->toArray();

        if (count($this->batch) >= self::BATCH_SIZE) {
            $this->flush();
        }
    }

    public function finalize(): void
    {
        $this->flush();
        DB::commit();
    }

    private function flush(): void
    {
        if (!empty($this->batch)) {
            DB::table('product_variants')->insert($this->batch);
            $this->batch = [];  
        }
    }
}