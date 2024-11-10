<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 50)->nullable();
            $table->string('prenom', 50)->nullable();
            $table->string('telephone', 20)->unique();
            $table->string('photo')->nullable(); // Colonne photo
            $table->string('email', 100)->unique();
            $table->decimal('solde', 10, 2)->nullable();
            $table->string('code', 255)->nullable();
            $table->decimal('promo', 5, 2)->nullable();
            $table->string('carte', 255)->nullable();
            $table->boolean('etatcarte')->default(false);
            $table->string('adresse', 255);
            $table->date('date_naissance');
            $table->string('secret')->nullable();
            $table->foreignId('role_id')->constrained('role')->onDelete('no action')->onUpdate('no action');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
