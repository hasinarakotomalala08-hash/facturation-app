<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number', 50);
            $table->date('issue_date');
            $table->string('period', 120);
            $table->string('provider_name', 150);
            $table->string('provider_title', 120);
            $table->string('provider_address', 255);
            $table->string('provider_phone', 60)->nullable();
            $table->string('provider_email', 150)->nullable();
            $table->string('provider_tax_id', 100)->nullable();
            $table->string('client_name', 150);
            $table->string('client_contact', 150)->nullable();
            $table->string('client_address', 255);
            $table->string('payment_method', 100);
            $table->timestamps();

            $table->unique(['user_id', 'invoice_number']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description', 180);
            $table->text('details')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 40);
            $table->unsignedBigInteger('amount');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
