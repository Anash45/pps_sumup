<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sample_pdfs', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Title of PDF
            $table->string('path');  // Path in storage
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sample_pdfs');
    }
};
