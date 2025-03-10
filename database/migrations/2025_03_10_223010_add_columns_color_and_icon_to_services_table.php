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
        Schema::table('services', function (Blueprint $table) {
            $table->string('color_code')->nullable()->after('description')->nullable()->comment('Codice colore CSS per il servizio (es: #FF5733)');
            $table->string('icon_class')->nullable()->after('color_code')->nullable()->comment('Classe Font Awesome per l\'icona (es: fa-users)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('color_code');
            $table->dropColumn('icon_class');
        });
    }
};
