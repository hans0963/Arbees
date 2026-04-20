<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date');
            $table->enum('period', ['daily', 'weekly', 'monthly']);
            $table->integer('total_orders');
            $table->decimal('total_revenue', 12, 2);
            $table->decimal('total_discount', 12, 2)->default(0);
            $table->integer('total_items_sold');
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_reports');
    }
};