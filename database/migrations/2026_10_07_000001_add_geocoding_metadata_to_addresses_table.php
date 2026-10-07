<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->string('geocoding_provider', 50)->nullable()->after('longitude');
            $table->timestamp('geocoding_attempted_at')->nullable()->after('geocoding_provider');
            $table->timestamp('geocoded_at')->nullable()->after('geocoding_attempted_at');
            $table->index(
                ['tenant_id', 'addressable_type', 'geocoding_attempted_at'],
                'addresses_geocoding_queue_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropIndex('addresses_geocoding_queue_index');
            $table->dropColumn([
                'geocoding_provider',
                'geocoding_attempted_at',
                'geocoded_at',
            ]);
        });
    }
};
