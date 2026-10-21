<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Contraintes d'intégrité PostgreSQL (CHECK, index partiels) utilisées par les migrations.
 *
 * Elles remplacent les « domaines » du script SQL d'origine : les règles de valeurs
 * (statuts autorisés, montants positifs…) restent garanties par la base, quelle que
 * soit l'application qui y écrit.
 */
class DbConstraints
{
    public static function check(string $table, string $name, string $expression): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(sprintf('ALTER TABLE "%s" ADD CONSTRAINT "%s" CHECK (%s)', $table, $name, $expression));
    }

    /** Liste de valeurs autorisées : in('statut', ['a', 'b']) => statut IN ('a','b') */
    public static function in(string $table, string $column, array $values): void
    {
        $list = implode(',', array_map(fn ($v) => "'".str_replace("'", "''", $v)."'", $values));
        self::check($table, "chk_{$table}_{$column}", "{$column} IN ({$list})");
    }

    public static function statement(string $sql): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement($sql);
        }
    }
}
