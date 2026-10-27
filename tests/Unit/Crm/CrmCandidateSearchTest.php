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
            'statut' => 'intention_deposee',
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

    public function test_it_matches_local_and_international_madagascar_phone_formats(): void
    {
        $advisor = User::factory()->create([
            'role' => 'super_admin',
            'password' => 'password',
        ]);

        $local = CrmCandidate::create([
            'prenom' => 'Local',
            'nom' => 'Format',
            'telephone' => '0341970012',
            'statut' => 'prospect',
            'advisor_id' => $advisor->id,
            'abandon' => false,
        ]);
        $intl = CrmCandidate::create([
            'prenom' => 'Intl',
            'nom' => 'Format',
            'telephone' => '+261341970099',
            'statut' => 'prospect',
            'advisor_id' => $advisor->id,
            'abandon' => false,
        ]);

        $this->assertSame('341970012', CrmCandidate::phoneCore('+261341970012'));
        $this->assertSame('341970012', CrmCandidate::phoneCore('0341970012'));
        $this->assertSame('341970012', CrmCandidate::phoneCore('+261 34 19 700 12'));

        // Stored local, search international
        $foundLocal = CrmCandidate::query()->searchNameOrPhone('+261341970012')->pluck('id');
        $this->assertTrue($foundLocal->contains($local->id));
        $this->assertFalse($foundLocal->contains($intl->id));

        // Stored international, search local
        $foundIntl = CrmCandidate::query()->searchNameOrPhone('0341970099')->pluck('id');
        $this->assertTrue($foundIntl->contains($intl->id));
        $this->assertFalse($foundIntl->contains($local->id));

        // Partial national number
        $partial = CrmCandidate::query()->searchNameOrPhone('341970012')->pluck('id');
        $this->assertTrue($partial->contains($local->id));
    }
}
