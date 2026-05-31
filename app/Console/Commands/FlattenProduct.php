<?php

namespace App\Console\Commands;

use App\Application\Commands\FeedCommand;
use App\Application\Commands\FeedHandler;
use App\Domain\Product\Repositories\ProductVariantRepository;
use App\Infrastructure\Reader\JsonlReader;
use App\Domain\Product\Services\ProductFlattenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FlattenProduct extends Command
{
    // The name and signature of the console command.
    protected $signature = 'write:products
        {--path= : The path to the product feed file (defaults to .env FEED_PATH or data/coffee_feed.jsonl)}';
    protected $description = 'Write product variants to the database';
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Determine the feed path from the command option, environment variable, or default value
        $feedPath = $this->option('path') ?? env('FEED_PATH', base_path('data/coffee_feed.jsonl'));

        // Validate that the feed path is provided and points to a valid file, logging an error and returning a non-zero exit code if not
        if (!$feedPath) {
            $this->error("Feed path is not specified. Please provide a valid feed path using the --path option or set it in the .env file.");
            return 1; // Return a non-zero code to indicate an error
        }

        $this->info("Starting to process feed: {$feedPath}");
        $this->info("Output will be written to: " . env('DB_DATABASE', 'output/products.sqlite'));

        $handler = new FeedHandler(
            reader: new JsonlReader(Log::channel('stack')),
            flattenService: new ProductFlattenService(),
            repository: app(ProductVariantRepository::class),
            logger: Log::channel('stack'),
        );

        $result = $handler->handle(new FeedCommand(feedPath: $feedPath));

        if($result->errors > 0) {
            $this->warn("Finished processing feed with some errors. Products read: {$result->productsRead}, Rows written: {$result->rowsWritten}, Errors: {$result->errors}");
            return self::FAILURE; // Return a non-zero code to indicate that there were errors during processing
        }
            
        $this->info("Finished processing feed successfully. Products read: {$result->productsRead}, Rows written: {$result->rowsWritten}");
        return self::SUCCESS; // Return zero to indicate successful processing
    }
}
