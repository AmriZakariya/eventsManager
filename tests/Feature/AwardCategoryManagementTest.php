<?php

namespace Tests\Feature;

use App\Models\AwardCategory;
use App\Models\AwardNominee;
use App\Models\Company;
use App\Orchid\Screens\Feature\AwardListScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AwardCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_category_keeps_nominees_uncategorized(): void
    {
        $category = AwardCategory::create([
            'name' => 'Best Product',
            'description' => 'Products shortlisted for the award.',
        ]);

        $nominee = AwardNominee::create([
            'award_category_id' => $category->id,
            'product_name' => 'Cleaning Robot',
            'is_winner' => true,
        ]);

        (new AwardListScreen())->deleteCategory(Request::create('/admin/awards', 'POST', [
            'id' => $category->id,
        ]));

        $this->assertDatabaseMissing('award_categories', [
            'id' => $category->id,
        ]);

        $this->assertDatabaseHas('award_nominees', [
            'id' => $nominee->id,
            'award_category_id' => null,
            'is_winner' => false,
        ]);
    }

    public function test_award_query_filters_grouped_nominees(): void
    {
        $company = Company::create([
            'name' => 'HCE Robotics',
            'is_active' => true,
        ]);

        $category = AwardCategory::create([
            'name' => 'Innovation',
        ]);

        AwardNominee::create([
            'award_category_id' => $category->id,
            'company_id' => $company->id,
            'product_name' => 'Autonomous Cleaning Robot',
            'is_winner' => true,
        ]);

        AwardNominee::create([
            'award_category_id' => $category->id,
            'product_name' => 'Manual Cleaning Kit',
            'is_winner' => false,
        ]);

        $data = (new AwardListScreen())->query(Request::create('/admin/awards', 'GET', [
            'search' => 'robot',
            'winner_status' => 'winner',
        ]));

        $this->assertSame(1, $data['awardGroups']->total());
        $this->assertSame('Innovation', $data['awardGroups']->first()->name);
        $this->assertCount(1, $data['awardGroups']->first()->nominees);
        $this->assertSame('Autonomous Cleaning Robot', $data['awardGroups']->first()->nominees->first()->product_name);
    }
}
