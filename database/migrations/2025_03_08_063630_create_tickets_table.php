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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number');
            $table->enum('type', ['info', 'service']);
            $table->enum('status', ['waiting', 'called', 'in_progress', 'completed', 'abandoned'])->default('waiting');
            $table->foreignId('service_id')->constrained();
            $table->foreignId('current_service_id')->constrained('services');
            $table->timestamp('first_called_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indice per le ricerche frequenti
            $table->index(['status', 'current_service_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
