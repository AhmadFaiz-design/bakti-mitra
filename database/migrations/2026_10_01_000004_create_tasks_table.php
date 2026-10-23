<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->foreignId('period_id')->constrained('periods')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('wage_amount');
            $table->date('due_date')->nullable();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->enum('status', ['assigned', 'in_progress', 'submitted', 'revision', 'completed', 'cancelled'])->default('assigned');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['partner_id', 'period_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
