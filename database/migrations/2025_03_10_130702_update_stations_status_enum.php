<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE stations MODIFY COLUMN status ENUM('active', 'busy', 'paused', 'closed') NOT NULL DEFAULT 'closed'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE stations MODIFY COLUMN status ENUM('active', 'paused', 'closed') NOT NULL DEFAULT 'closed'");
    }
};
