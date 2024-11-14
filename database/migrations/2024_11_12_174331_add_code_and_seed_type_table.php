<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter la colonne 'code' en la rendant nullable pour éviter les erreurs de "not null violation"
        Schema::table('type', function (Blueprint $table) {
            $table->string('code')->unique()->nullable()->after('libelle');
        });

        // Mise à jour des enregistrements existants avec des valeurs par défaut pour 'code'
        DB::table('type')->whereNull('code')->update([
            'code' => DB::raw("concat('TYPE_', id)")
        ]);

        // Insertion ou mise à jour des types de base uniquement si le code n'existe pas
        $types = [
            [
                'libelle' => 'Transfert Simple',
                'code' => 'TRANSFERT_SIMPLE',
                'description' => 'Transfert d\'argent entre deux utilisateurs',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'libelle' => 'Transfert Multiple',
                'code' => 'TRANSFERT_MULTIPLE',
                'description' => 'Transfert d\'argent vers plusieurs bénéficiaires',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'libelle' => 'Transfert Planifié',
                'code' => 'TRANSFERT_PLANIFIE',
                'description' => 'Transfert d\'argent programmé',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'libelle' => 'Paiement Marchand',
                'code' => 'PAIEMENT_MARCHAND',
                'description' => 'Paiement auprès d\'un marchand',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        foreach ($types as $type) {
            DB::table('type')->updateOrInsert(
                ['code' => $type['code']], // Vérification d'existence basée sur 'code'
                $type                       // Valeurs à insérer ou à mettre à jour
            );
        }

        // Rendre la colonne 'code' non nullable après mise à jour
        Schema::table('type', function (Blueprint $table) {
            $table->string('code')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        // Supprimer la colonne 'code' en cas de rollback
        Schema::table('type', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
