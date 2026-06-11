<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Conference;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Speaker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContentTranslationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_endpoint_uses_requested_public_language(): void
    {
        Company::create([
            'name' => 'English Company',
            'name_translations' => [
                'en' => 'English Company',
                'fr' => 'Entreprise française',
                'ar' => 'شركة عربية',
            ],
            'category' => 'Technology, Healthcare',
            'category_translations' => [
                'en' => 'Technology, Healthcare',
                'fr' => 'Technologie, Santé',
            ],
            'description' => '<p>English <strong>description</strong></p>',
            'description_translations' => [
                'en' => '<p>English <strong>description</strong></p>',
                'fr' => '<p>Description <strong>française</strong></p>',
            ],
            'address' => 'English address',
            'address_translations' => [
                'en' => 'English address',
                'fr' => 'Adresse française',
            ],
            'is_active' => true,
        ]);

        $this->withHeader('Accept-Language', 'fr-FR,fr;q=0.9')
            ->getJson('/api/companies')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Entreprise française')
            ->assertJsonPath('data.0.category', 'Technologie, Santé')
            ->assertJsonPath('data.0.categories.0.label', 'Technologie')
            ->assertJsonPath('data.0.categories.0.value', 'Technology')
            ->assertJsonPath('data.0.categories.1.label', 'Santé')
            ->assertJsonPath('data.0.categories.1.value', 'Healthcare')
            ->assertJsonPath('data.0.description', 'Description française')
            ->assertJsonPath('data.0.address', 'Adresse française');

        $this->getJson('/api/companies?category=Healthcare')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Entreprise française');
    }

    public function test_authenticated_content_endpoints_use_user_locale_with_english_fallback(): void
    {
        $user = User::factory()->create(['locale' => 'ar']);
        Sanctum::actingAs($user);

        $company = Company::create([
            'name' => 'Medical Partner',
            'name_translations' => ['en' => 'Medical Partner', 'ar' => 'شريك طبي'],
            'is_active' => true,
        ]);

        $category = ProductCategory::create([
            'name' => 'Devices',
            'name_translations' => ['en' => 'Devices', 'ar' => 'أجهزة'],
            'slug' => 'devices',
        ]);

        Product::create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'name' => 'Scanner',
            'name_translations' => ['en' => 'Scanner', 'ar' => 'ماسح'],
            'type' => 'Hardware',
            'type_translations' => ['en' => 'Hardware'],
            'description' => '<p>English product description</p>',
            'description_translations' => ['en' => '<p>English product description</p>', 'ar' => '<p>وصف المنتج</p>'],
        ]);

        $speaker = Speaker::create([
            'full_name' => 'Sara Ahmed',
            'job_title' => 'Doctor',
            'job_title_translations' => ['en' => 'Doctor', 'ar' => 'طبيبة'],
            'bio' => '<p>English bio</p>',
            'bio_translations' => ['en' => '<p>English bio</p>', 'ar' => '<p>سيرة عربية</p>'],
        ]);

        $conference = Conference::create([
            'title' => 'Opening Session',
            'title_translations' => ['en' => 'Opening Session', 'ar' => 'الجلسة الافتتاحية'],
            'description' => '<p>English session description</p>',
            'description_translations' => ['en' => '<p>English session description</p>', 'ar' => '<p>وصف الجلسة</p>'],
            'start_time' => '2026-06-15 09:00:00',
            'end_time' => '2026-06-15 10:00:00',
            'type' => 'conference',
        ]);
        $conference->speakers()->attach($speaker);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'ماسح')
            ->assertJsonPath('data.0.type', 'Hardware')
            ->assertJsonPath('data.0.description', 'وصف المنتج')
            ->assertJsonPath('data.0.category.name', 'أجهزة')
            ->assertJsonPath('data.0.company.name', 'شريك طبي');

        $this->getJson('/api/products/categories')
            ->assertOk()
            ->assertJsonPath('0.name', 'أجهزة');

        $this->getJson('/api/speakers')
            ->assertOk()
            ->assertJsonPath('data.0.job_title', 'طبيبة')
            ->assertJsonPath('data.0.bio', 'سيرة عربية');

        $this->getJson('/api/conferences')
            ->assertOk()
            ->assertJsonPath('0.title', 'الجلسة الافتتاحية')
            ->assertJsonPath('0.description', 'وصف الجلسة')
            ->assertJsonPath('0.speakers.0.job_title', 'طبيبة');
    }
}
