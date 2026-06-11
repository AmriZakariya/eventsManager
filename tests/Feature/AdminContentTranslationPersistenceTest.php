<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Conference;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Speaker;
use App\Orchid\Screens\Company\CompanyEditScreen;
use App\Orchid\Screens\Conference\ConferenceEditScreen;
use App\Orchid\Screens\Product\ProductEditScreen;
use App\Orchid\Screens\ProductCategory\ProductCategoryEditScreen;
use App\Orchid\Screens\Speaker\SpeakerEditScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AdminContentTranslationPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_translation_fields_are_saved_and_reloaded(): void
    {
        $company = new Company();
        $request = Request::create('/admin/companies/create', 'POST', [
            'company' => [
                'name_translations' => [
                    'en' => 'English Company',
                    'fr' => 'Entreprise française',
                    'ar' => 'شركة عربية',
                ],
                'category_translations' => [
                    'en' => 'Technology, Healthcare',
                    'fr' => 'Technologie, Santé',
                    'ar' => 'التكنولوجيا، الصحة',
                ],
                'address_translations' => [
                    'en' => 'English address',
                    'fr' => 'Adresse française',
                    'ar' => 'عنوان عربي',
                ],
                'description_translations' => [
                    'en' => 'English about',
                    'fr' => 'À propos en français',
                    'ar' => 'نبذة عربية',
                ],
                'is_active' => true,
                'is_featured' => false,
            ],
        ]);

        (new CompanyEditScreen())->save($company, $request);

        $saved = Company::firstOrFail();

        $this->assertSame('English Company', $saved->name);
        $this->assertSame('Entreprise française', $saved->translationInput('name')['fr']);
        $this->assertSame('Technologie, Santé', $saved->translationInput('category')['fr']);
        $this->assertSame('Adresse française', $saved->translationInput('address')['fr']);
        $this->assertSame('À propos en français', $saved->translationInput('description')['fr']);
        $this->assertSame('نبذة عربية', $saved->translationInput('description')['ar']);
    }

    public function test_other_translated_admin_sections_are_saved(): void
    {
        $company = Company::create([
            'name' => 'Default Company',
            'is_active' => true,
        ]);

        $product = new Product();
        (new ProductEditScreen())->save($product, Request::create('/admin/products/create', 'POST', [
            'product' => [
                'company_id' => $company->id,
                'name_translations' => ['en' => 'Product', 'fr' => 'Produit', 'ar' => 'منتج'],
                'type_translations' => ['en' => 'Device', 'fr' => 'Appareil', 'ar' => 'جهاز'],
                'description_translations' => ['en' => '<p>English <strong>product</strong></p>', 'fr' => '<p>Produit français</p>', 'ar' => '<p>منتج عربي</p>'],
                'is_featured' => false,
            ],
        ]));

        $category = new ProductCategory();
        (new ProductCategoryEditScreen())->save($category, Request::create('/admin/categories/create', 'POST', [
            'category' => [
                'name_translations' => ['en' => 'Devices', 'fr' => 'Appareils', 'ar' => 'أجهزة'],
            ],
        ]));

        $speaker = new Speaker();
        (new SpeakerEditScreen())->save($speaker, Request::create('/admin/speakers/create', 'POST', [
            'speaker' => [
                'full_name' => 'Sara Ahmed',
                'job_title_translations' => ['en' => 'Doctor', 'fr' => 'Médecin', 'ar' => 'طبيبة'],
                'bio_translations' => ['en' => '<p>English bio</p>', 'fr' => '<p>Bio française</p>', 'ar' => '<p>سيرة عربية</p>'],
            ],
        ]));

        $conference = new Conference();
        (new ConferenceEditScreen())->save($conference, Request::create('/admin/conferences/create', 'POST', [
            'conference' => [
                'title_translations' => ['en' => 'Opening', 'fr' => 'Ouverture', 'ar' => 'الافتتاح'],
                'description_translations' => ['en' => '<p>English session</p>', 'fr' => '<p>Session française</p>', 'ar' => '<p>جلسة عربية</p>'],
                'type' => 'conference',
                'start_time' => '2026-06-15 09:00:00',
                'end_time' => '2026-06-15 10:00:00',
                'speakers' => [],
            ],
        ]));

        $savedProduct = Product::firstOrFail();

        $this->assertSame('Produit', $savedProduct->translationInput('name')['fr']);
        $this->assertSame('Produit français', $savedProduct->translationInput('description')['fr']);
        $this->assertSame('Appareils', ProductCategory::firstOrFail()->translationInput('name')['fr']);
        $this->assertSame('Médecin', Speaker::firstOrFail()->translationInput('job_title')['fr']);
        $this->assertSame('Bio française', Speaker::firstOrFail()->translationInput('bio')['fr']);
        $this->assertSame('Ouverture', Conference::firstOrFail()->translationInput('title')['fr']);
        $this->assertSame('Session française', Conference::firstOrFail()->translationInput('description')['fr']);
    }

    public function test_partial_company_update_does_not_delete_existing_arabic_content(): void
    {
        $company = Company::create([
            'name' => 'English Company',
            'name_translations' => [
                'en' => 'English Company',
                'fr' => 'Entreprise française',
                'ar' => 'شركة عربية',
            ],
            'category' => 'Technology',
            'category_translations' => [
                'en' => 'Technology',
                'fr' => 'Technologie',
                'ar' => 'التكنولوجيا',
            ],
            'address' => 'English address',
            'address_translations' => [
                'en' => 'English address',
                'fr' => 'Adresse française',
                'ar' => 'عنوان عربي',
            ],
            'description' => 'English about',
            'description_translations' => [
                'en' => 'English about',
                'fr' => 'À propos en français',
                'ar' => 'نبذة عربية',
            ],
            'is_active' => true,
        ]);

        $request = Request::create('/admin/companies/'.$company->id.'/edit', 'POST', [
            'company' => [
                'name_translations' => [
                    'en' => 'English Company',
                ],
                'category_translations' => [
                    'en' => 'Technology, testmultiple1, multiple2, multiple3',
                ],
                'is_active' => true,
                'is_featured' => false,
            ],
        ]);

        (new CompanyEditScreen())->save($company, $request);

        $saved = $company->refresh();

        $this->assertSame('Technology, testmultiple1, multiple2, multiple3', $saved->category);
        $this->assertSame('شركة عربية', $saved->translationInput('name')['ar']);
        $this->assertSame('التكنولوجيا', $saved->translationInput('category')['ar']);
        $this->assertSame('عنوان عربي', $saved->translationInput('address')['ar']);
        $this->assertSame('نبذة عربية', $saved->translationInput('description')['ar']);
    }
}
