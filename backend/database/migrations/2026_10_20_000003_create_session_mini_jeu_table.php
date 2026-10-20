<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_mini_jeu', function (Blueprint $table) {
            $table->uuid('id_session')->primary();
            $table->unsignedInteger('id_etudiant');
            $table->unsignedInteger('id_mini_jeu');
            $table->string('question', 255);
            $table->string('reponse_attendue', 100);
            $table->timestamp('date_expiration');
            $table->timestamp('date_utilisation')->nullable();
            $table->foreign('id_etudiant')->references('id_utilisateur')->on('etudiant')->cascadeOnDelete();
            $table->foreign('id_mini_jeu')->references('id_mini_jeu')->on('mini_jeu')->cascadeOnDelete();
            $table->index(['id_etudiant', 'date_expiration'], 'ix_session_jeu_etudiant_expiration');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_mini_jeu');
    }
};
