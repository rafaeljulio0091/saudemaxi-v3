<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->index(
                ['tenant_id', 'addressable_type', 'latitude', 'longitude'],
                'addr_tenant_type_geo_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropIndex('addr_tenant_type_geo_idx');
        });
    }
};
