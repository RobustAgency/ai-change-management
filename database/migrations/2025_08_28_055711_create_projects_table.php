<?php

use App\Models\User;
use App\Enums\ProjectStatus;
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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->dateTime('launch_date')->nullable();
            $table->json('target_audiences')->nullable();
            $table->text('key_messages')->nullable();
            $table->text('benefits')->nullable();
            $table->text('timeline')->nullable();
            $table->string('status')->default(ProjectStatus::Draft);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
