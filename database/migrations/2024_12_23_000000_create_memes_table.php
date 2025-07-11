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
        Schema::create('memes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('image_path');
            $table->text('text_top')->nullable();
            $table->text('text_bottom')->nullable();
            $table->integer('font_size')->default(40);
            $table->string('font_color', 7)->default('#FFFFFF');
            $table->string('stroke_color', 7)->default('#000000');
            $table->integer('stroke_width')->default(2);
            $table->timestamps();

            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memes');
    }
}; 