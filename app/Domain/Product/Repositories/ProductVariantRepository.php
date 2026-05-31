<?php
declare(strict_types=1);
namespace App\Domain\Product\Repositories;

use App\Domain\Product\FlatRow;

interface ProductVariantRepository
{
    public function prepare(): void;
    public function save(FlatRow $flatRow): void;
    public function finalize(): void;
}