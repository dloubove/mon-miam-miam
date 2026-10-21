<?php

namespace Tests\Feature\Auth;

use App\Models\Etudiant;
use App\Models\Personnel;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GereLesConnexions;
use Tests\TestCase;

/** Règle métier : un utilisateur est SOIT étudiant, SOIT membre du personnel. */
class ExclusiviteProfilTest extends TestCase
{
    use GereLesConnexions, RefreshDatabase;

    protected bool $seed = true;

    public function test_un_etudiant_ne_peut_pas_devenir_membre_du_personnel(): void
    {
        $this->expectException(DomainException::class);

        Personnel::create([
            'id_utilisateur' => $this->compte('etudiant@ucac-icam.com')->id_utilisateur,
            'matricule' => 'EMP-999', 'date_embauche' => now()->toDateString(),
            'id_role' => 1, 'id_restaurant' => 1,
        ]);
    }

    public function test_un_membre_du_personnel_ne_peut_pas_devenir_etudiant(): void
    {
        $this->expectException(DomainException::class);

        Etudiant::create([
            'id_utilisateur' => $this->compte('paul@zeduc.com')->id_utilisateur,
            'code_parrainage' => 'ZEDUC-ZZZZZ', 'solde_points' => 0,
        ]);
    }
}
