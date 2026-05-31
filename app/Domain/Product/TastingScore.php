<?php
declare(strict_types=1);
namespace App\Domain\Product;

final class TastingScore
{
    public function __construct(
        public readonly int $acidity,
        public readonly int $body,
        public readonly int $sweetness,
        public readonly int $aroma,
        public readonly int $bitterness,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            acidity: (int)($data['acidity'] ?? 0),
            body: (int)($data['body'] ?? 0),
            sweetness: (int)($data['sweetness'] ?? 0),
            aroma: (int)($data['aroma'] ?? 0),
            bitterness: (int)($data['bitterness'] ?? 0),
        );
    }
}