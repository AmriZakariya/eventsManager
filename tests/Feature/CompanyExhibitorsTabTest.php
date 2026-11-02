<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyExhibitorsTabTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'app_role'    => User::APP_ROLE_ADMIN,
            'permissions' => ['platform.index' => true, 'platform.companies' => true],
        ]);
    }

    public function test_company_page_lists_only_its_own_exhibitors(): void
    {
        $company = Company::create(['name' => 'Acme']);
        $other = Company::create(['name' => 'Other Co']);
        User::factory()->create(['name' => 'Linked', 'email' => 'linked@example.com', 'company_id' => $company->id, 'app_role' => User::APP_ROLE_EXHIBITOR]);
        User::factory()->create(['name' => 'Elsewhere', 'email' => 'elsewhere@example.com', 'company_id' => $other->id]);

        $this->actingAs($this->admin())
            ->get(route('platform.companies.edit', $company))
            ->assertOk()
            ->assertSee('Exhibitors (1)')
            ->assertSee('linked@example.com')
            ->assertDontSee('elsewhere@example.com');
    }

    public function test_company_without_people_shows_empty_state(): void
    {
        $company = Company::create(['name' => 'Empty Co']);

        $this->actingAs($this->admin())
            ->get(route('platform.companies.edit', $company))
            ->assertOk()
            ->assertSee('Exhibitors (0)')
            ->assertSee('No exhibitors yet.');
    }

    public function test_create_page_has_no_exhibitors_tab(): void
    {
        $this->actingAs($this->admin())
            ->get(route('platform.companies.create'))
            ->assertOk()
            ->assertDontSee('Exhibitors (');
    }
}
