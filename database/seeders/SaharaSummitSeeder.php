<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Faker\Factory as Faker;
use Orchid\Platform\Models\Role;

// Make sure these Models exist in your App\Models namespace
use App\Models\EventSetting;
use App\Models\Company;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Conference;
use App\Models\Speaker;
use App\Models\Appointment;
use App\Models\Connection;
use App\Models\AwardCategory;
use App\Models\AwardNominee;

class SaharaSummitSeeder extends Seeder
{
    private $faker;
    private $usedEmails = []; // Array to track used emails globally

    public function run()
    {
        // Initialize Faker with French/Moroccan locale
        $this->faker = Faker::create('fr_FR');
        $this->usedEmails = [];

        $this->command->info('🧹 Cleaning database...');
        $this->cleanDatabase();

        $this->command->info('👥 Creating roles...');
        $this->createRoles();

        $this->command->info('⚙️  Creating event settings...');
        $this->createEventSettings();

        $this->command->info('📦 Creating product categories...');
        $productCategoryIds = $this->createProductCategories();

        $this->command->info('🏢 Creating companies & products...');
        // Returns [$companyIds, $exhibitorUserIds]
        [$companyIds, $exhibitorUserIds] = $this->createCompaniesAndProducts($productCategoryIds);

        $this->command->info('👥 Creating visitors...');
        $visitorIds = $this->createVisitors();

        $this->command->info('🎤 Creating speakers & conferences...');
        $this->createConferencesAndSpeakers($visitorIds);

        $this->command->info('📅 Creating appointments...');
        $this->createAppointments($visitorIds, $exhibitorUserIds);

        $this->command->info('🤝 Creating connections...');
        $this->createConnections($visitorIds, $exhibitorUserIds);

        $this->command->info('🏆 Creating awards...');
        $this->createAwards($companyIds);

        $this->command->info('🔔 Creating notifications...');
        $this->createNotifications($visitorIds, $exhibitorUserIds);

        $this->command->info('⭐ Creating favorites...');
        $this->createFavorites($visitorIds, $companyIds);

        $this->command->info('💬 Creating messages...');
        $this->createMessages($visitorIds, $exhibitorUserIds);

        $this->command->info('📞 Creating contact requests...');
        $this->createContactRequests($visitorIds);

        $this->command->info('✅ SEEDING COMPLETED SUCCESSFULLY!');
        $this->command->info('📧 All users password: password');
    }

    /**
     * Helper to generate a unique email address to avoid DB collision errors
     */
    private function generateUniqueEmail($name, $domain = 'gmail.com')
    {
        $slug = Str::slug($name, '.');
        $baseEmail = $slug . '@' . $domain;

        $email = $baseEmail;
        $counter = 1;

        // Check local array AND database
        while (in_array($email, $this->usedEmails) || User::where('email', $email)->exists()) {
            $email = $slug . $counter . '@' . $domain;
            $counter++;
        }

        $this->usedEmails[] = $email;
        return $email;
    }

    private function cleanDatabase()
    {
        Schema::disableForeignKeyConstraints();

        // Truncate tables in specific order to avoid constraint errors
        DB::table('messages')->truncate();
        DB::table('contact_requests')->truncate();
        DB::table('favorites')->truncate();
        DB::table('app_notifications')->truncate();
        DB::table('notifications')->truncate(); // Orchid core notifications

        Appointment::truncate();
        Connection::truncate();
        AwardNominee::truncate();
        AwardCategory::truncate();

        DB::table('conference_registrations')->truncate();
        DB::table('conference_speaker')->truncate();
        Conference::truncate();
        Speaker::truncate();

        Product::truncate();
        ProductCategory::truncate();

        DB::table('role_users')->truncate();
        User::truncate();
        Company::truncate();
        EventSetting::truncate();

        Schema::enableForeignKeyConstraints();
    }

    private function createRoles()
    {
        $roles = [
            ['slug' => 'visitor', 'name' => 'Visitor'],
            ['slug' => 'exhibitor', 'name' => 'Exhibitor'],
            ['slug' => 'admin', 'name' => 'Administrator'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['slug' => $role['slug']],
                ['name' => $role['name'], 'permissions' => []]
            );
        }
    }

    private function createEventSettings()
    {
        EventSetting::create([
            'event_name' => 'HYGIE-CLEAN EXPO 2026',
            'tagline' => 'The Future of Hygiene and Business in Morocco',
            'description' => 'The premier international trade fair for the cleaning, hygiene, and chemical industries in Morocco.',
            'start_date' => '2026-11-05 10:00:00',
            'end_date'   => '2026-11-07 19:00:00',
            'location_name'    => 'ICEC Casablanca',
            'location_address' => '6 Bd de Makro, Aïn Sebaâ, Casablanca 20250, Morocco',
            'latitude'         => 33.5900,
            'longitude'        => -7.5300,
            'opening_hour' => '10:00',
            'closing_hour' => '19:00',
            'meeting_duration_minutes' => 30,
            'meeting_buffer_minutes'   => 10,
            'max_meetings_per_day'     => 12,
            'enable_meeting_requests'   => true,
            'auto_confirm_meetings'     => false,
            'enable_notifications'      => true,
            'enable_chat'               => true,
            'enable_qr_checkin'         => true,
            'enable_networking'         => true,
            'enable_exhibitor_scanning' => true,
            'show_attendee_list'        => true,
            'enable_offline_mode'       => true,
            'support_email' => 'contact@hygiecleanexpo.com',
            'support_phone' => '+212 520 946 054',
            'website_url'   => 'https://hygiecleanexpo.com',
            'facebook_url'  => 'https://facebook.com/hygiecleanexpo',
            'instagram_url' => 'https://instagram.com/hygiecleanexpo',
            'linkedin_url'  => 'https://linkedin.com/company/hygie-clean-expo',
            'timezone' => 'Africa/Casablanca',
            'language' => 'fr',
            'primary_color'   => '#1A365B',
            'secondary_color' => '#02CA67',
            'accent_color'    => '#00A1EC',
            'floor_plan_image'    => '/storage/floor_plan.png',
            'app_logo'    => '/storage/2026/02/24/f778eee06b92b9053d7b63bbbbda12edf48dc267.png',
        ]);
    }

    private function createProductCategories()
    {
        $categories = [
            'Software Solutions', 'Cloud & Infrastructure', 'Artificial Intelligence',
            'Cybersecurity', 'Fintech & Payments', 'IoT & Smart Devices',
            'Data Analytics', 'E-commerce Platforms', 'Mobile Applications',
            'Blockchain', 'EdTech Solutions', 'HealthTech',
        ];

        $ids = [];
        foreach ($categories as $name) {
            $cat = ProductCategory::create([
                'name' => $name,
                'slug' => Str::slug($name),
            ]);
            $ids[] = $cat->id;
        }

        return $ids;
    }

    private function createCompaniesAndProducts($productCategoryIds)
    {
        // Converted to associative array for better readability and added domains/types
        $companiesData = [
            ['name' => 'Orange Maroc', 'category' => 'Telecommunications', 'country' => 'Morocco', 'desc' => 'Leading telecom provider.', 'featured' => true, 'domain' => 'orange.ma', 'types' => ['SPONSOR']],
            ['name' => 'Microsoft Africa', 'category' => 'Cloud Computing', 'country' => 'USA', 'desc' => 'Global technology leader.', 'featured' => true, 'domain' => 'microsoft.com', 'types' => ['INSTITUTIONAL_PARTNER']],
            ['name' => 'OCP Group', 'category' => 'Industrial Technology', 'country' => 'Morocco', 'desc' => 'World leader in phosphate.', 'featured' => true, 'domain' => 'ocpgroup.ma', 'types' => ['SPONSOR', 'EXHIBITIONS_PARTNERS']],
            ['name' => 'Huawei Technologies', 'category' => 'Telecommunications', 'country' => 'China', 'desc' => 'Global ICT solutions provider.', 'featured' => true, 'domain' => 'huawei.com', 'types' => ['SPONSOR']],
            ['name' => 'Salesforce', 'category' => 'Enterprise Software', 'country' => 'USA', 'desc' => 'CRM and cloud solutions.', 'featured' => false, 'domain' => 'salesforce.com', 'types' => ['EXHIBITIONS_PARTNERS']],
            ['name' => 'HPS Worldwide', 'category' => 'Fintech', 'country' => 'Morocco', 'desc' => 'Global payment solutions.', 'featured' => true, 'domain' => 'hps-worldwide.com', 'types' => ['EXHIBITIONS_PARTNERS']],
            ['name' => 'Jumia Technologies', 'category' => 'E-commerce', 'country' => 'Nigeria', 'desc' => 'Africa leading e-commerce.', 'featured' => true, 'domain' => 'jumia.ma', 'types' => ['MEDIA PARTNERS']],
            ['name' => 'Maroc Telecom', 'category' => 'Telecommunications', 'country' => 'Morocco', 'desc' => 'Integrated telecom operator.', 'featured' => false, 'domain' => 'iam.ma', 'types' => ['SPONSOR']],
            ['name' => 'SAP Africa', 'category' => 'Enterprise Software', 'country' => 'Germany', 'desc' => 'Business software solutions.', 'featured' => false, 'domain' => 'sap.com', 'types' => ['EXHIBITIONS_PARTNERS']],
            ['name' => 'Schneider Electric', 'category' => 'Energy Management', 'country' => 'France', 'desc' => 'Digital automation solutions.', 'featured' => false, 'domain' => 'se.com', 'types' => ['SPONSOR']],
        ];

        $companyIds = [];
        $exhibitorUserIds = [];
        $roleExhibitor = Role::where('slug', 'exhibitor')->first();

        $moroccanFirstNames = ['Ahmed', 'Youssef', 'Mehdi', 'Omar', 'Karim', 'Hamza', 'Ayoub', 'Amine'];
        $moroccanLastNames = ['El Amrani', 'Benali', 'Alaoui', 'El Idrissi', 'Bennani', 'Chraibi'];

        foreach ($companiesData as $index => $c) {
            $companyName = $c['name'];
            $companyDomain = $c['domain'];
            $companyEmail = $this->generateUniqueEmail('contact', $companyDomain);

            // Uses Clearbit API to fetch the actual working logo of the company
            $logoUrl = 'https://logo.clearbit.com/' . $companyDomain;

            $company = Company::create([
                'name' => $companyName,
                'logo' => $logoUrl, // Added logo
                'booth_number' => 'Stand ' . chr(65 + floor($index / 10)) . sprintf('%02d', ($index % 10) + 1),
                'country' => $c['country'],
                'category' => $c['category'],
                'email' => $companyEmail,
                'phone' => '+212 5 ' . rand(20, 29) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                'website_url' => 'https://' . $companyDomain,
                'address' => $this->faker->address, // Added fake address
                'type' => $c['types'], // Added JSON types. (Remove json_encode if your model uses $casts = ['type' => 'array'])
                'catalog_file' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf', // Added dummy PDF catalog
                'description' => $c['desc'],
                'map_coordinates' => json_encode(['x' => rand(100, 800), 'y' => rand(100, 600)]), // Added JSON coordinates
                'is_featured' => $c['featured'],
                'is_active' => true,
            ]);

            $companyIds[] = $company->id;

            // Products (Unchanged Logic)
            $numProducts = rand(2, 4);
            for ($p = 0; $p < $numProducts; $p++) {
                Product::create([
                    'company_id' => $company->id,
                    'name' => 'Solution ' . $p . ' for ' . $companyName,
                    'type' => $c['category'],
                    'description' => 'Advanced solution designed for modern businesses.',
                    'is_featured' => $this->faker->boolean(30),
                    'category_id' => $productCategoryIds[array_rand($productCategoryIds)],
                ]);
            }

            // Exhibitors (Unchanged Logic)
            $numExhibitors = rand(2, 3);
            for ($i = 0; $i < $numExhibitors; $i++) {
                $firstName = $moroccanFirstNames[array_rand($moroccanFirstNames)];
                $lastName = $moroccanLastNames[array_rand($moroccanLastNames)];
                $exhibitorEmail = $this->generateUniqueEmail($firstName . ' ' . $lastName, $companyDomain);

                $user = User::create([
                    'name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $exhibitorEmail,
                    'password' => Hash::make('password'),
                    'company_id' => $company->id,
                    'job_title' => 'Representative',
                    'phone' => '+212 6' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                    'badge_code' => 'EX-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-' . str_pad($i + 1, 2, '0', STR_PAD_LEFT),
                    'is_visible' => true,
                    'bio' => 'Experienced professional.',
                ]);

                $user->addRole($roleExhibitor);
                $exhibitorUserIds[] = $user->id;
            }
        }

        return [$companyIds, $exhibitorUserIds];
    }

    private function createVisitors()
    {
        $roleVisitor = Role::where('slug', 'visitor')->first();
        $visitorIds = [];

        $moroccanFirstNames = ['Ahmed', 'Youssef', 'Mehdi', 'Omar', 'Karim', 'Hamza', 'Ayoub', 'Amine', 'Saad', 'Reda', 'Fatima', 'Khadija'];
        $moroccanLastNames = ['El Amrani', 'Benali', 'Alaoui', 'El Idrissi', 'Bennani', 'Chraibi', 'Tazi', 'Fassi'];
        $domains = ['gmail.com', 'yahoo.com', 'outlook.com'];

        // Create 50 visitors
        for ($i = 0; $i < 50; $i++) {
            $firstName = $moroccanFirstNames[array_rand($moroccanFirstNames)];
            $lastName = $moroccanLastNames[array_rand($moroccanLastNames)];
            $domain = $domains[$i % count($domains)];
            $visitorEmail = $this->generateUniqueEmail($firstName . ' ' . $lastName, $domain);

            $visitor = User::create([
                'name' => $firstName,
                'last_name' => $lastName,
                'email' => $visitorEmail,
                'password' => Hash::make('password'),
                'job_title' => 'Visitor',
                'phone' => '+212 6' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                'badge_code' => 'VIS-' . str_pad(1000 + $i, 4, '0', STR_PAD_LEFT),
                'is_visible' => $this->faker->boolean(85),
                'company_id' => null,
            ]);

            $visitor->addRole($roleVisitor);
            $visitorIds[] = $visitor->id;
        }

        return $visitorIds;
    }

    private function createConferencesAndSpeakers($visitorIds)
    {
        $conferencesData = [
            [
                'title' => 'Opening Keynote: The Future of Hygiene',
                'start' => '2026-11-05 09:30:00',
                'end' => '2026-11-05 10:30:00',
                'location' => 'Main Auditorium',
                'type' => 'keynote',
                'description' => 'Join us for an inspiring opening keynote.',
                'speakers' => [['Dr. Amina Touré', 'Chief Innovation Officer', 'African Development Bank']]
            ],
            [
                'title' => 'Sustainable Cleaning Solutions',
                'start' => '2026-11-05 11:00:00',
                'end' => '2026-11-05 12:30:00',
                'location' => 'Tech Hall A',
                'type' => 'panel',
                'description' => 'Discover how green chemicals are solving real problems.',
                'speakers' => [['Dr. Karim Beguir', 'CEO', 'InstaDeep']]
            ],
        ];

        foreach ($conferencesData as $confData) {
            $conf = Conference::create([
                'title' => $confData['title'],
                'start_time' => $confData['start'],
                'end_time' => $confData['end'],
                'location' => $confData['location'],
                'type' => $confData['type'],
                'description' => $confData['description'],
            ]);

            foreach ($confData['speakers'] as $speakerData) {
                $speaker = Speaker::create([
                    'full_name' => $speakerData[0],
                    'job_title' => $speakerData[1],
                    'company_name' => $speakerData[2],
                    'bio' => 'Renowned expert in the field.',
                ]);

                // Assuming Conference has belongsToMany Speakers
                $conf->speakers()->attach($speaker->id);
            }

            // Register random attendees
//            $numAttendees = rand(5, 15);
//            $attendees = $this->faker->randomElements($visitorIds, min($numAttendees, count($visitorIds)));
//
//            foreach ($attendees as $attendeeId) {
//                DB::table('conference_registrations')->insert([
//                    'user_id' => $attendeeId,
//                    'conference_id' => $conf->id,
//                    'created_at' => now(),
//                    'updated_at' => now(), // Good practice to add updated_at
//                ]);
//            }
        }
    }

    private function createAppointments($visitorIds, $exhibitorUserIds)
    {
        $statuses = ['pending', 'confirmed', 'cancelled', 'declined', 'completed'];
        $tables = ['Table A1', 'Table A2', 'Table B1'];

        for ($i = 0; $i < 50; $i++) {
            $status = $statuses[array_rand($statuses)];
            $day = rand(5, 7); // Days 5, 6, 7 of November
            $hour = rand(10, 17);
            $minute = [0, 30][array_rand([0, 30])];

            // Ensure formatting is correct (e.g. 2026-11-05 09:30:00)
            $formattedDate = sprintf("2026-11-%02d %02d:%02d:00", $day, $hour, $minute);

            Appointment::create([
                'booker_id' => $visitorIds[array_rand($visitorIds)],
                'target_user_id' => $exhibitorUserIds[array_rand($exhibitorUserIds)],
                'scheduled_at' => $formattedDate,
                'duration_minutes' => 30,
                'table_location' => $tables[array_rand($tables)],
                'status' => $status,
                'notes' => $this->faker->boolean(70) ? 'Interested in your solutions.' : null,
            ]);
        }
    }

    private function createConnections($visitorIds, $exhibitorUserIds)
    {
        $allUserIds = array_merge($visitorIds, $exhibitorUserIds);
        $createdPairs = [];

        for ($i = 0; $i < 60; $i++) {
            $requester = $allUserIds[array_rand($allUserIds)];
            $target = $allUserIds[array_rand($allUserIds)];

            // Create a unique key to prevent duplicate connections
            $pair = $requester < $target ? "{$requester}-{$target}" : "{$target}-{$requester}";

            if ($requester !== $target && !in_array($pair, $createdPairs)) {
                Connection::create([
                    'requester_id' => $requester,
                    'target_id' => $target,
                    'status' => $this->faker->randomElement(['pending', 'accepted', 'accepted']),
                ]);

                $createdPairs[] = $pair;
            }
        }
    }

    private function createAwards($companyIds)
    {
        $awardCategories = [
            ['name' => 'Best Innovation 2026', 'description' => 'Best tech innovation'],
            ['name' => 'Best Sustainable Product', 'description' => 'Eco-friendly excellence'],
        ];

        foreach ($awardCategories as $catData) {
            $category = AwardCategory::create($catData);

            $numNominees = rand(3, 5);
            $selectedCompanies = $this->faker->randomElements($companyIds, $numNominees);

            foreach ($selectedCompanies as $index => $companyId) {
                AwardNominee::create([
                    'award_category_id' => $category->id,
                    'company_id' => $companyId,
                    'product_name' => 'Solution ' . ($index + 1),
                    'is_winner' => $index === 0,
                ]);
            }
        }
    }

    private function createNotifications($visitorIds, $exhibitorUserIds)
    {
        $allUserIds = array_merge($visitorIds, $exhibitorUserIds);
        $notifications = [
            ['Welcome', 'Don\'t miss the opening keynote.', 'info'],
            ['Meeting Confirmed', 'Your appointment is confirmed.', 'appointment'],
        ];

        foreach ($allUserIds as $userId) {
            $numNotifs = rand(1, 3);
            for ($i = 0; $i < $numNotifs; $i++) {
                $notif = $notifications[array_rand($notifications)];
                DB::table('app_notifications')->insert([
                    'user_id' => $userId,
                    'title' => $notif[0],
                    'body' => $notif[1],
                    'type' => $notif[2],
                    'is_read' => $this->faker->boolean(40),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function createFavorites($visitorIds, $companyIds)
    {
        $createdFavorites = [];
        foreach ($visitorIds as $visitorId) {
            if ($this->faker->boolean(50)) {
                $companyId = $companyIds[array_rand($companyIds)];
                $key = "{$visitorId}-{$companyId}";

                if (!in_array($key, $createdFavorites)) {
                    DB::table('favorites')->insert([
                        'user_id' => $visitorId,
                        'favoritable_type' => 'App\\Models\\Company',
                        'favoritable_id' => $companyId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $createdFavorites[] = $key;
                }
            }
        }
    }

    private function createMessages($visitorIds, $exhibitorUserIds)
    {
        for ($i = 0; $i < 20; $i++) {
            $sender = $visitorIds[array_rand($visitorIds)];
            $receiver = $exhibitorUserIds[array_rand($exhibitorUserIds)];

            DB::table('messages')->insert([
                'sender_id' => $sender,
                'receiver_id' => $receiver,
                'content' => $this->faker->sentence(10),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function createContactRequests($visitorIds)
    {
        for ($i = 0; $i < 10; $i++) {
            DB::table('contact_requests')->insert([
                'user_id' => $visitorIds[array_rand($visitorIds)],
                'subject' => 'Inquiry',
                'message' => $this->faker->paragraph(2),
                'is_handled' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
