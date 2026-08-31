<?php

namespace Tests\Unit\Crm;

use App\Models\CrmCandidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmCandidateSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_finds_candidates_by_name_or_phone(): void
    {
        $advisor = User::factory()->create([
            'role' => 'super_admin',
            'password' => 'password',
        ]);

        $jean = CrmCandidate::create([
            'prenom' => 'Jean',
            'nom' => 'Rakoto',
            'telephone' => '034 12 345 67',
            'statut' => 'prospect',
            'advisor_id' => $advisor->id,
            'abandon' => false,
        ]);
        CrmCandidate::create([
            'prenom' => 'Marie',
            'nom' => 'Rabe',
            'telephone' => '032 98 765 43',
            'statut' => 'decouverte',
            'advisor_id' => $advisor->id,
            'abandon' => false,
        ]);

        $byLastName = CrmCandidate::query()->searchNameOrPhone('Rakoto')->pluck('id');
        $this->assertEquals([$jean->id], $byLastName->all());

        $byFullName = CrmCandidate::query()->searchNameOrPhone('Jean Rakoto')->pluck('id');
        $this->assertEquals([$jean->id], $byFullName->all());

        $byPhone = CrmCandidate::query()->searchNameOrPhone('0341234567')->pluck('id');
        $this->assertEquals([$jean->id], $byPhone->all());
    }
}
