<?php
declare(strict_types=1);
namespace App\Domain\Product;

final class Origin
{
    public function __construct(
        public readonly string $country,
        public readonly string $region,
        public readonly string $farm,
        public readonly ?int $altitudeM,
        public readonly string $process,
        public readonly ?float $lat,
        public readonly ?float $lng,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $coordinates = $data['coordinates'] ?? null;
        return new self(
            country: $data['country'] ?? '',
            region: $data['region'] ?? '',
            farm: $data['farm'] ?? '',
            altitudeM: isset($data['altitude_m']) ? (int)$data['altitude_m'] : null,
            process: $data['process'] ?? '',
            lat: isset($coordinates['lat']) ? (float)$coordinates['lat'] : null,
            lng: isset($coordinates['lng']) ? (float)$coordinates['lng'] : null,
        );
    }
}