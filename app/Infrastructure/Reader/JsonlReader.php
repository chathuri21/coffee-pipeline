<?php
declare(strict_types=1);
namespace App\Infrastructure\Reader;

use App\Domain\Product\Exceptions\InvalidProductDataException;
use App\Domain\Product\Product;
use Psr\Log\LoggerInterface;

final class JsonlReader
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function readLine(string $filePath): iterable
    {
        $this->checkFileExists($filePath);

        $handle = $this->openFile($filePath);

        $lineNumber = 0;

        try {
            while (($line = fgets($handle)) !== false) {
                $lineNumber++;

                // Trim the line and skip it if it's empty
                $line = trim($line);
                if ($line === '') {
                    continue; // Skip empty lines
                }

                // Decode the JSON line and validate it, logging any issues and skipping invalid lines
                $data = $this->decodeLine($line, $lineNumber);
                if ($data === null) {
                    continue;
                }

                // Attempt to build a Product object from the decoded data, 
                //logging detailed information about any validation errors or unexpected exceptions that occur during the process. 
                // Skip any products that cannot be built successfully.
                $product = $this->buildProduct($data, $lineNumber);
                if ($product === null) {
                    continue;
                }

                yield $product;
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Checks if the file exists and is readable, throwing an exception if not.
     */
    private function checkFileExists(string $filePath): void
    {
        // Check if the file exists
        if (!file_exists($filePath)) {
            $this->logger->error("File not found: {$filePath}");
            throw new \RuntimeException("File not found: {$filePath}");
        }

        // Check if the file is readable
        if (!is_readable($filePath)) {
            $this->logger->error("File is not readable: {$filePath}");
            throw new \RuntimeException("File is not readable: {$filePath}");
        }
    }

    /**
     * Opens the file and returns the handle, throwing an exception if it fails.
     */
    private function openFile(string $filePath)
    {
        $handle = fopen($filePath, 'r');

        // Check if the file was opened successfully
        if ($handle === false) {
            $this->logger->error("Failed to open file: {$filePath}");
            throw new \RuntimeException("Failed to open file: {$filePath}");
        }

        return $handle;
    }

    /**
     * Decodes a JSON line into an associative array, logging and returning null if the line is malformed or not an array.
     */
    private function decodeLine(string $line, int $lineNumber): ?array
    {
        // Decode the JSON line into an associative array
        $data = json_decode($line, associative: true);

        // Check for JSON decoding errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error("Skipping malformed JSON line", [
                'line_number' => $lineNumber,
                'error' => json_last_error_msg(),
                'line_content' => $line,
            ]);

            return null ;
        }

        // Check if the decoded data is an array
        if (!is_array($data)) {
            $this->logger->error("Skipping non-array JSON line", [
                'line_number' => $lineNumber,
                'line_content' => $line,
            ]);

            return null;
        }

        return $data;
    }

    /**
     * Attempts to build a Product object from the given data array, logging detailed information about any validation errors or unexpected exceptions that occur during the process. Returns null if the product data is invalid or if an unexpected error occurs.
     */
    private function buildProduct(array $data, int $lineNumber): ?Product
    {
        try {
            // Attempt to build a Product object from the data array
            return Product::fromArray($data);

        } catch (InvalidProductDataException $e) {
            // Log the specific reason for the invalid product data, along with the line number and SKU if available
            $this->logger->warning("Skipping invalid product data", [
                'line_number' => $lineNumber,
                'reason' => $e->getMessage(),
                'sku' => $data ['sku'] ?? 'unknown',
            ]);

            return null;

        } catch (\Throwable $e) {
            // Log unexpected errors with more context, including the stack trace
            $this->logger->error("Unexpected error while building product", [
                'line_number' => $lineNumber,
                'error' => $e->getMessage(),
                'sku' => $data ['sku'] ?? 'unknown',
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }
}