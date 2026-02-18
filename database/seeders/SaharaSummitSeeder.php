<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Faker\Factory as Faker;
use Orchid\Platform\Models\Role;

// Models
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
        $this->faker = Faker::create('fr_FR');
        $this->usedEmails = []; // Reset email tracker

        $this->command->info('🧹 Cleaning database...');
        $this->cleanDatabase();

        $this->command->info('👥 Creating roles...');
        $this->createRoles();

        $this->command->info('⚙️  Creating event settings...');
        $this->createEventSettings();

        $this->command->info('📦 Creating product categories...');
        $productCategoryIds = $this->createProductCategories();

        $this->command->info('🏢 Creating companies & products...');
        // Note: We capture company IDs and exhibitor User IDs for later relationships
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
     * Helper to generate a unique email address
     */
    private function generateUniqueEmail($name, $domain = 'gmail.com')
    {
        // Convert name to slug (e.g., "John Doe" -> "john.doe")
        $slug = Str::slug($name, '.');
        $baseEmail = $slug . '@' . $domain;

        $email = $baseEmail;
        $counter = 1;

        // Check if email is already in our local tracking array or DB
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

        DB::table('messages')->truncate();
        DB::table('contact_requests')->truncate();
        DB::table('favorites')->truncate();
        DB::table('app_notifications')->truncate();
        DB::table('notifications')->truncate(); // Orchid notifications
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
            'description' => 'The premier international trade fair for the cleaning, hygiene, and chemical industries in Morocco. A strategic platform bringing together industry leaders, innovators, and decision-makers to showcase the latest technologies and sustainable solutions.',

            // Verified Dates: November 5-7, 2026
            'start_date' => '2026-11-05 10:00:00',
            'end_date'   => '2026-11-07 19:00:00',

            // Venue: ICEC Casablanca (International Corporate Events Center)
            'location_name'    => 'ICEC Casablanca',
            'location_address' => '6 Bd de Makro, Aïn Sebaâ, Casablanca 20250, Morocco',
            'latitude'         => 33.5900,
            'longitude'        => -7.5300,

            // Official Opening Hours: 10:00 AM - 7:00 PM
            'opening_hour' => '10:00',
            'closing_hour' => '19:00',

            'meeting_duration_minutes' => 30,
            'meeting_buffer_minutes'   => 10,
            'max_meetings_per_day'     => 12,

            // Features
            'enable_meeting_requests'   => true,
            'auto_confirm_meetings'     => false,
            'enable_notifications'      => true,
            'enable_chat'               => true,
            'enable_qr_checkin'         => true,
            'enable_networking'         => true,
            'enable_exhibitor_scanning' => true,
            'show_attendee_list'        => true,
            'enable_offline_mode'       => true,

            // Contact Info from Jala Agency / Event Site
            'support_email' => 'contact@hygiecleanexpo.com',
            'support_phone' => '+212 520 946 054',
            'website_url'   => 'https://hygiecleanexpo.com',

            // Social Media (Standard handles for the event)
            'facebook_url'  => 'https://facebook.com/hygiecleanexpo',
            'twitter_url'   => null, // Not prominently featured
            'instagram_url' => 'https://instagram.com/hygiecleanexpo',
            'linkedin_url'  => 'https://linkedin.com/company/hygie-clean-expo',

            'timezone' => 'Africa/Casablanca',
            'language' => 'fr', // Primary business language in Morocco/Event is French/English

            // Official Brand Colors from Media Kit
            'primary_color'   => '#1A365B', // Navy Blue
            'secondary_color' => '#02CA67', // Hygiene Green
            'accent_color'    => '#00A1EC', // Bright Blue
        ]);
    }

    private function createProductCategories()
    {
        $categories = [
            'Software Solutions',
            'Cloud & Infrastructure',
            'Artificial Intelligence & Machine Learning',
            'Cybersecurity',
            'Fintech & Payments',
            'IoT & Smart Devices',
            'Data Analytics & Business Intelligence',
            'E-commerce Platforms',
            'Mobile Applications',
            'Blockchain & Cryptocurrency',
            'EdTech Solutions',
            'HealthTech',
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
        $companiesData = [
            ['Orange Maroc', 'Telecommunications', 'Morocco', 'Leading telecom provider in Morocco with 5G and IoT solutions', true],
            ['Microsoft Africa', 'Cloud Computing', 'USA', 'Global technology leader empowering African businesses with Azure', true],
            ['OCP Group', 'Industrial Technology', 'Morocco', 'World leader in phosphate and digital transformation', true],
            ['Huawei Technologies', 'Telecommunications', 'China', 'Global ICT solutions provider with African innovation centers', true],
            ['Salesforce', 'Enterprise Software', 'USA', 'Customer relationship management and cloud solutions', false],
            ['HPS Worldwide', 'Fintech', 'Morocco', 'Global payment solutions and digital banking platform', true],
            ['Jumia Technologies', 'E-commerce', 'Nigeria', 'Africa\'s leading e-commerce and logistics platform', true],
            ['Maroc Telecom', 'Telecommunications', 'Morocco', 'Integrated telecom operator across Africa', false],
            ['SAP Africa', 'Enterprise Software', 'Germany', 'Business software solutions for African enterprises', false],
            ['Schneider Electric', 'Energy Management', 'France', 'Digital automation and energy management solutions', false],
            ['Injaz Morocco', 'Fintech', 'Morocco', 'Mobile payment and digital wallet solutions', false],
            ['InstaDeep', 'Artificial Intelligence', 'Tunisia', 'AI-powered decision-making systems', true],
            ['Andela', 'Technology Services', 'Nigeria', 'Distributed engineering teams and tech talent platform', false],
            ['Flutterwave', 'Fintech', 'Nigeria', 'Payment infrastructure for Africa', true],
            ['M-Pesa', 'Mobile Money', 'Kenya', 'Revolutionary mobile money transfer service', false],
        ];

        $companyIds = [];
        $exhibitorUserIds = [];
        $roleExhibitor = Role::where('slug', 'exhibitor')->first();

        // Data pool for random generation
        $moroccanFirstNames = ['Ahmed', 'Youssef', 'Mehdi', 'Omar', 'Karim', 'Hamza', 'Ayoub', 'Amine', 'Saad', 'Reda', 'Fatima', 'Khadija', 'Amina', 'Salma', 'Leila'];
        $moroccanLastNames = ['El Amrani', 'Benali', 'Alaoui', 'El Idrissi', 'Bennani', 'Chraibi', 'Tazi', 'Fassi', 'Ouazzani', 'Berrada'];

        foreach ($companiesData as $index => $c) {
            $companyName = $c[0];
            $companyDomain = Str::slug($companyName) . '.com';

            // Generate unique email for company
            $companyEmail = $this->generateUniqueEmail('contact', $companyDomain);

            $company = Company::create([
                'name' => $companyName,
                'category' => $c[1],
                'country' => $c[2],
                'booth_number' => 'Stand ' . chr(65 + floor($index / 10)) . sprintf('%02d', ($index % 10) + 1),
                'email' => $companyEmail,
                'phone' => '+212 5 ' . rand(20, 29) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                'website_url' => 'https://' . $companyDomain,
                'description' => $c[3],
                'is_featured' => $c[4],
                'is_active' => true,
            ]);

            $companyIds[] = $company->id;

            // Create 2-4 products per company
            $numProducts = rand(2, 4);
            for ($p = 0; $p < $numProducts; $p++) {
                $productNames = [
                    'Enterprise Cloud Platform', 'Smart Analytics Dashboard', 'Secure Payment Gateway',
                    'AI-Powered CRM System', 'Mobile Banking App', 'IoT Device Manager',
                    'Cybersecurity Suite', 'E-commerce Platform', 'Business Intelligence Tool',
                    'Digital Wallet Solution', 'Inventory Management System', 'Customer Engagement Platform',
                    'Data Visualization Engine', 'Automated Workflow Builder', 'Real-time Monitoring System'
                ];

                $productName = $productNames[array_rand($productNames)] . ' ' . ($p > 0 ? 'Pro' : 'Enterprise');

                Product::create([
                    'company_id' => $company->id,
                    'name' => $productName,
                    'type' => $c[1],
                    'description' => 'Advanced ' . strtolower($productName) . ' designed for modern businesses in Africa.',
                    'is_featured' => $this->faker->boolean(30),
                    'category_id' => $productCategoryIds[array_rand($productCategoryIds)],
                ]);
            }

            // Create 2-3 exhibitor users per company
            $numExhibitors = rand(2, 3);
            for ($i = 0; $i < $numExhibitors; $i++) {
                $firstName = $moroccanFirstNames[array_rand($moroccanFirstNames)];
                $lastName = $moroccanLastNames[array_rand($moroccanLastNames)];
                $positions = ['Sales Director', 'Technical Lead', 'Business Development Manager', 'Product Manager', 'Solutions Architect'];

                // Generate guaranteed unique email for exhibitor
                $exhibitorEmail = $this->generateUniqueEmail($firstName . ' ' . $lastName, $companyDomain);

                $user = User::create([
                    'name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $exhibitorEmail,
                    'password' => Hash::make('password'),
                    'company_id' => $company->id,
                    'job_title' => $positions[$i % count($positions)],
                    'phone' => '+212 6' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                    'badge_code' => 'EX-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-' . str_pad($i + 1, 2, '0', STR_PAD_LEFT),
                    'is_visible' => true,
                    'bio' => 'Experienced professional with ' . rand(5, 15) . '+ years in ' . strtolower($c[1]) . '. Passionate about innovation and technology in Africa.',
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

        $moroccanFirstNames = ['Ahmed', 'Youssef', 'Mehdi', 'Omar', 'Karim', 'Hamza', 'Ayoub', 'Amine', 'Saad', 'Reda', 'Fatima', 'Khadija', 'Amina', 'Salma', 'Leila', 'Zineb', 'Nadia', 'Rachid', 'Hassan', 'Mustapha'];
        $moroccanLastNames = ['El Amrani', 'Benali', 'Alaoui', 'El Idrissi', 'Bennani', 'Chraibi', 'Tazi', 'Fassi', 'Ouazzani', 'Berrada', 'Lahlou', 'Kettani', 'Squalli', 'Filali'];

        $jobTitles = [
            'CEO', 'CTO', 'CIO', 'Business Development Manager', 'Product Manager',
            'IT Director', 'Digital Transformation Lead', 'Innovation Manager',
            'Startup Founder', 'Entrepreneur', 'Investor', 'Technology Consultant',
            'Software Engineer', 'Data Scientist', 'UX Designer', 'Marketing Director'
        ];

        $domains = ['techco.ma', 'innovation.ma', 'startup.ma', 'digital.ma', 'business.ma', 'gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'proton.me'];

        for ($i = 0; $i < 50; $i++) {
            $firstName = $moroccanFirstNames[array_rand($moroccanFirstNames)];
            $lastName = $moroccanLastNames[array_rand($moroccanLastNames)];
            $domain = $domains[$i % count($domains)];

            // Generate guaranteed unique email for visitor
            $visitorEmail = $this->generateUniqueEmail($firstName . ' ' . $lastName, $domain);

            $visitor = User::create([
                'name' => $firstName,
                'last_name' => $lastName,
                'email' => $visitorEmail,
                'password' => Hash::make('password'),
                'job_title' => $jobTitles[array_rand($jobTitles)],
                'phone' => '+212 6' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                'badge_code' => 'VIS-' . str_pad(1000 + $i, 4, '0', STR_PAD_LEFT),
                'is_visible' => $this->faker->boolean(85),
                'bio' => $this->faker->boolean(60) ? 'Tech enthusiast interested in innovation and digital transformation in Africa.' : null,
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
            // Day 1 - November 20
            [
                'title' => 'Opening Keynote: The Future of Technology in Africa',
                'start' => '2026-11-20 09:30:00',
                'end' => '2026-11-20 10:30:00',
                'location' => 'Main Auditorium',
                'type' => 'keynote',
                'description' => 'Join us for an inspiring opening keynote exploring the transformative power of technology across the African continent.',
                'speakers' => [
                    ['Dr. Amina Touré', 'Chief Innovation Officer', 'African Development Bank'],
                ]
            ],
            [
                'title' => 'AI & Machine Learning: Practical Applications for African Markets',
                'start' => '2026-11-20 11:00:00',
                'end' => '2026-11-20 12:30:00',
                'location' => 'Tech Hall A',
                'type' => 'panel',
                'description' => 'Discover how AI and ML are solving real problems in healthcare, agriculture, and finance across Africa.',
                'speakers' => [
                    ['Dr. Karim Beguir', 'CEO & Co-Founder', 'InstaDeep'],
                    ['Sarah Mensah', 'Head of AI Research', 'Google Africa'],
                ]
            ],
            [
                'title' => 'Fintech Revolution: Digital Payments & Financial Inclusion',
                'start' => '2026-11-20 14:00:00',
                'end' => '2026-11-20 15:30:00',
                'location' => 'Innovation Stage',
                'type' => 'panel',
                'description' => 'How fintech is transforming banking and bringing financial services to millions of unbanked Africans.',
                'speakers' => [
                    ['Olugbenga Agboola', 'CEO', 'Flutterwave'],
                    ['Mohamed Dabo', 'VP Product', 'Wave Mobile Money'],
                ]
            ],
            [
                'title' => 'Cybersecurity in the Digital Age: Protecting African Businesses',
                'start' => '2026-11-20 16:00:00',
                'end' => '2026-11-20 17:00:00',
                'location' => 'Security Forum',
                'type' => 'workshop',
                'description' => 'Learn best practices for protecting your organization from cyber threats in an increasingly connected world.',
                'speakers' => [
                    ['Dr. Hassan El Hajj', 'CISO', 'OCP Group'],
                ]
            ],
            // Day 2 & 3 would follow similar pattern
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
                    'bio' => 'Renowned expert in technology and innovation with extensive experience leading transformative initiatives across Africa and globally.',
                ]);

                $conf->speakers()->attach($speaker->id);
            }

            // Register random attendees
            $numAttendees = rand(20, 40);
            $attendees = $this->faker->randomElements($visitorIds, min($numAttendees, count($visitorIds)));

            foreach ($attendees as $attendeeId) {
                DB::table('conference_registrations')->insert([
                    'user_id' => $attendeeId,
                    'conference_id' => $conf->id,
                    'created_at' => now(),
                ]);
            }
        }
    }

    private function createAppointments($visitorIds, $exhibitorUserIds)
    {
        $statuses = ['pending', 'confirmed', 'cancelled', 'declined', 'completed'];
        $tables = ['Table A1', 'Table A2', 'Table B1', 'Table B2', 'Table C1', 'Meeting Room 1', 'Meeting Room 2'];

        for ($i = 0; $i < 90; $i++) {
            $status = $statuses[array_rand($statuses)];
            $day = rand(20, 22);
            $hour = rand(10, 17);
            $minute = [0, 30][array_rand([0, 30])];

            Appointment::create([
                'booker_id' => $visitorIds[array_rand($visitorIds)],
                'target_user_id' => $exhibitorUserIds[array_rand($exhibitorUserIds)],
                'scheduled_at' => "2026-11-{$day} {$hour}:{$minute}:00",
                'duration_minutes' => 30,
                'table_location' => $tables[array_rand($tables)],
                'status' => $status,
                'notes' => $this->faker->boolean(70) ? 'Interested in your solutions.' : null,
                'rating' => $status === 'completed' && $this->faker->boolean(80) ? rand(3, 5) : null,
                'feedback' => $status === 'completed' && $this->faker->boolean(40) ? 'Great meeting!' : null,
            ]);
        }
    }

    private function createConnections($visitorIds, $exhibitorUserIds)
    {
        $allUserIds = array_merge($visitorIds, $exhibitorUserIds);
        $createdPairs = [];

        for ($i = 0; $i < 110; $i++) {
            $requester = $allUserIds[array_rand($allUserIds)];
            $target = $allUserIds[array_rand($allUserIds)];

            // Ensure unique directional pair for tracking
            $pair = $requester < $target ? "{$requester}-{$target}" : "{$target}-{$requester}";

            if ($requester !== $target && !in_array($pair, $createdPairs)) {
                Connection::create([
                    'requester_id' => $requester,
                    'target_id' => $target,
                    'status' => $this->faker->randomElement(['pending', 'accepted', 'accepted', 'declined']),
                ]);

                $createdPairs[] = $pair;
            }
        }
    }

    private function createAwards($companyIds)
    {
        $awardCategories = [
            ['name' => 'Best Innovation 2026', 'description' => 'Recognizing groundbreaking technological innovation'],
            ['name' => 'Best Fintech Solution', 'description' => 'Excellence in financial technology'],
            ['name' => 'Best AI Application', 'description' => 'Outstanding use of artificial intelligence'],
            ['name' => 'Best Cybersecurity Product', 'description' => 'Superior security solution'],
            ['name' => 'Best Startup', 'description' => 'Most promising new technology company'],
        ];

        foreach ($awardCategories as $catData) {
            $category = AwardCategory::create($catData);

            $numNominees = rand(3, 5);
            $selectedCompanies = $this->faker->randomElements($companyIds, $numNominees);

            foreach ($selectedCompanies as $index => $companyId) {
                AwardNominee::create([
                    'award_category_id' => $category->id,
                    'company_id' => $companyId,
                    'product_name' => 'Innovative Solution ' . ($index + 1),
                    'is_winner' => $index === 0,
                ]);
            }
        }
    }

    private function createNotifications($visitorIds, $exhibitorUserIds)
    {
        $allUserIds = array_merge($visitorIds, $exhibitorUserIds);
        $notifications = [
            ['Welcome to Sahara Tech Summit', 'Don\'t miss the opening keynote.', 'info'],
            ['Your meeting has been confirmed', 'Your appointment is confirmed.', 'appointment'],
            ['New connection request', 'Someone wants to connect.', 'connection'],
        ];

        foreach ($allUserIds as $userId) {
            $numNotifs = rand(2, 4);
            for ($i = 0; $i < $numNotifs; $i++) {
                $notif = $notifications[array_rand($notifications)];
                DB::table('app_notifications')->insert([
                    'user_id' => $userId,
                    'title' => $notif[0],
                    'body' => $notif[1],
                    'type' => $notif[2],
                    'is_read' => $this->faker->boolean(40),
                    'created_at' => now()->subHours(rand(1, 48)),
                    'updated_at' => now()->subHours(rand(1, 48)),
                ]);
            }
        }
    }

    private function createFavorites($visitorIds, $companyIds)
    {
        $createdFavorites = [];
        foreach ($visitorIds as $visitorId) {
            if ($this->faker->boolean(70)) {
                $numFavorites = rand(1, 3);
                $favoriteCompanies = $this->faker->randomElements($companyIds, $numFavorites);
                foreach ($favoriteCompanies as $companyId) {
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
    }

    private function createMessages($visitorIds, $exhibitorUserIds)
    {
        for ($i = 0; $i < 35; $i++) {
            $sender = $visitorIds[array_rand($visitorIds)];
            $receiver = $exhibitorUserIds[array_rand($exhibitorUserIds)];
            $numMessages = rand(2, 5);
            for ($j = 0; $j < $numMessages; $j++) {
                DB::table('messages')->insert([
                    'sender_id' => $j % 2 === 0 ? $sender : $receiver,
                    'receiver_id' => $j % 2 === 0 ? $receiver : $sender,
                    'content' => $this->faker->sentence(rand(8, 20)),
                    'read_at' => $this->faker->boolean(60) ? now()->subHours(rand(1, 24)) : null,
                    'created_at' => now()->subHours(rand(1, 48)),
                    'updated_at' => now()->subHours(rand(1, 48)),
                ]);
            }
        }
    }

    private function createContactRequests($visitorIds)
    {
        $subjects = ['Partnership inquiry', 'Speaker slot', 'Booth booking', 'Support'];
        for ($i = 0; $i < 15; $i++) {
            DB::table('contact_requests')->insert([
                'user_id' => $this->faker->boolean(70) ? $visitorIds[array_rand($visitorIds)] : null,
                'name' => $this->faker->boolean(70) ? null : $this->faker->name,
                'email' => $this->faker->boolean(70) ? null : $this->faker->safeEmail,
                'subject' => $subjects[array_rand($subjects)],
                'message' => $this->faker->paragraph(3),
                'is_handled' => $this->faker->boolean(40),
                'created_at' => now()->subDays(rand(1, 7)),
                'updated_at' => now()->subDays(rand(1, 7)),
            ]);
        }
    }
}
