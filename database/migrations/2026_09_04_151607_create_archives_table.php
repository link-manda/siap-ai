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
        Schema::create('archives', function (Blueprint $table) {
            $table->id();
            $table->string('document_number')->nullable();
            $table->string('title');
            $table->date('document_date')->nullable();
            $table->string('file_path');
            $table->string('file_type');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('classification_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('extracted_text')->nullable();
            $table->text('summary')->nullable();
            $table->string('ai_model')->nullable();
            $table->decimal('ai_confidence_score', 5, 2)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archives');
    }
};
