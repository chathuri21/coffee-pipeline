<?php
declare(strict_types=1);
namespace App\Application\Commands;

use App\Domain\Product\Exceptions\InvalidProductDataException;
use App\Application\Commands\FeedResult;
use App\Application\Commands\FeedCommand;
use App\Domain\Product\Repositories\ProductVariantRepository;
use App\Infrastructure\Reader\JsonlReader;
use App\Domain\Product\Services\ProductFlattenService;
use Psr\Log\LoggerInterface;

final class FeedHandler
{
    public function __construct(
        public readonly JsonlReader $reader,
        public readonly ProductFlattenService $flattenService,
        public readonly ProductVariantRepository $repository,
        public readonly LoggerInterface $logger,
    ) {
    }

    public function handle(FeedCommand $command): FeedResult
    {
        $this->logger->info("Starting feed processing", ['feed_path' => $command->feedPath]);

        $productsRead = 0;
        $rowsWritten = 0;
        $errors = 0;

        $this->repository->prepare();

        foreach ($this->reader->readLine($command->feedPath) as $product) {
            $productsRead++;

            try {
                $flatRows = $this->flattenService->flatten($product);

                if (empty($flatRows)) {
                    $this->logger->warning("No variants found for product", ['sku' => $product->sku]);
                }

                foreach ($flatRows as $flatRow) {
                    $this->repository->save($flatRow);
                    $rowsWritten++;
                }
            } catch (\Exception $e) {
                $this->logger->error("Error processing product", [
                    'sku' => $product->sku,
                    'error' => $e->getMessage(),
                ]);
                $errors++;
            }
        }

        $this->repository->finalize();
        $this->logger->info("Finished feed processing", [
            'products_read' => $productsRead,
            'rows_written' => $rowsWritten,
            'errors' => $errors,
        ]);
        
        return new FeedResult($productsRead, $rowsWritten, $errors);
    }
}