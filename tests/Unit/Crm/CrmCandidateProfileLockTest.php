<?php

namespace Tests\Unit\Crm;

use App\Models\CrmCandidate;
use App\Models\User;
use Tests\TestCase;

class CrmCandidateProfileLockTest extends TestCase
{
    public function test_inscrit_profile_is_locked_for_non_super_admin(): void
    {
        $candidate = new CrmCandidate(['statut' => 'inscrit']);
        $advisor = new User(['role' => 'responsable_communication']);

        $this->assertTrue($candidate->isProfileLocked());
        $this->assertFalse($candidate->canEditProfile($advisor));
    }

    public function test_super_admin_can_edit_inscrit_profile(): void
    {
        $candidate = new CrmCandidate(['statut' => 'inscrit']);
        $superAdmin = new User(['role' => 'super_admin']);

        $this->assertTrue($candidate->isProfileLocked());
        $this->assertTrue($candidate->canEditProfile($superAdmin));
    }

    public function test_non_inscrit_profile_is_editable(): void
    {
        $candidate = new CrmCandidate(['statut' => 'prospect']);
        $advisor = new User(['role' => 'stagiaire']);

        $this->assertFalse($candidate->isProfileLocked());
        $this->assertTrue($candidate->canEditProfile($advisor));
    }
}
