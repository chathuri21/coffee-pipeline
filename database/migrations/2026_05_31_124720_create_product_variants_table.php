<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();

            // Basic product details
            $table->string('sku');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('in_stock')->default(false);

            // Origin details
            $table->string('origin_country');
            $table->string('origin_region');
            $table->string('origin_farm');
            $table->integer('origin_altitude_m')->nullable();
            $table->string('origin_process');
            $table->decimal('origin_lat', 10, 7)->nullable();
            $table->decimal('origin_lng', 10, 7)->nullable();

            // Roast details
            $table->string('roast_level');
            $table->date('roasted_on');
            $table->string('roaster');

            // Additional details
            $table->string('flavor_notes')->nullable();
            $table->string('tags')->nullable();

            // Tasting details
            $table->integer('tasting_acidity')->default(0);
            $table->integer('tasting_body')->default(0);
            $table->integer('tasting_sweetness')->default(0);
            $table->integer('tasting_aroma')->default(0);
            $table->integer('tasting_bitterness')->default(0);

            // Variant details
            $table->string('variant_sku')->unique()->nullable();
            $table->string('variant_size')->nullable();
            $table->string('variant_grind')->nullable();
            $table->string('variant_price_eur')->nullable();
            $table->string('variant_stock')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
