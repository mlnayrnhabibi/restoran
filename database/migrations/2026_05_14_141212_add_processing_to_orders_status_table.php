<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite does not support modifying enum columns in-place.
        // We use a raw statement to recreate the column with the new allowed value.
        // For MySQL/PostgreSQL you can use: $table->enum(...)->change()
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status_new')->default('pending')->after('status');
        });

        // Copy existing data
        DB::statement('UPDATE orders SET status_new = status');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->renameColumn('status_new', 'status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status_new')->default('pending')->after('status');
        });

        DB::statement("UPDATE orders SET status_new = CASE WHEN status = 'processing' THEN 'pending' ELSE status END");

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->renameColumn('status_new', 'status');
        });
    }
};
