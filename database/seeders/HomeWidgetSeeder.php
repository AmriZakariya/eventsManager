<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HomeWidget;
use App\Models\HomeWidgetItem;

class HomeWidgetSeeder extends Seeder
{
    public function run()
    {
        // Clear old widgets
        HomeWidget::query()->delete();

        // ---------------------------------------------------------
        // 1. MAIN SLIDER (Hero Section)
        // ---------------------------------------------------------
        $slider = HomeWidget::create([
            'title' => 'Main Slider',
            'identifier' => 'home_hero_section',
            'widget_type' => 'slider',
            'order' => 10,
            'is_active' => true,
        ]);

        // Slide 1
        HomeWidgetItem::create([
            'home_widget_id' => $slider->id,
            'title' => 'Welcome to Sahara Summit',
            'identifier' => 'home_slide_1_title',
            'subtitle' => 'Connecting Innovation Across Africa',
            'image' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=1200&q=80', // Conference Hall
            'order' => 1
        ]);

        // Slide 2
        HomeWidgetItem::create([
            'home_widget_id' => $slider->id,
            'title' => 'Book Your Stand',
            'identifier' => 'home_slide_2_title',
            'subtitle' => 'Spaces are limited for 2026',
            'image' => 'https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&w=1200&q=80', // Empty Stage/Lights
            'order' => 2
        ]);


        // ---------------------------------------------------------
        // 2. SPONSOR BANNER
        // ---------------------------------------------------------
        $banner = HomeWidget::create([
            'title' => 'Sponsor Banner',
            'identifier' => 'home_sponsor_banner_section',
            'widget_type' => 'sponsor_banner',
            'order' => 20,
            'is_active' => true,
        ]);

        HomeWidgetItem::create([
            'home_widget_id' => $banner->id,
            'title' => 'Gold Sponsor',
            'identifier' => 'home_sponsor_gold_title',
            'image' => 'https://images.unsplash.com/photo-1557683311-eac922347aa1?auto=format&fit=crop&w=1200&q=80',
            'action_url' => 'https://sponsor-link.com',
            'order' => 1
        ]);

        // ---------------------------------------------------------
        // 3. MAIN MENU GRID (Bento Grid)
        // ---------------------------------------------------------
        $menuGrid = HomeWidget::create([
            'title' => 'Explore',
            'identifier' => 'home_menu_section',
            'widget_type' => 'menu_grid',
            'order' => 30,
            'is_active' => true,
        ]);

        $menuItems = [
            [
                'title' => 'Exhibitors',
                'identifier' => 'home_menu_exhibitors',
                'icon' => 'store',
                'action_url' => '/exhibitors',
                'image' => 'https://images.unsplash.com/photo-1556761175-5973dc0f32e7?auto=format&fit=crop&w=600&q=80' // Meeting
            ],
            [
                'title' => 'Products',
                'identifier' => 'home_menu_products',
                'icon' => 'inventory_2',
                'action_url' => '/products',
                'image' => 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=600&q=80' // Tech workspace
            ],
            [
                'title' => 'B2B Meetings',
                'identifier' => 'home_menu_b2b',
                'icon' => 'handshake',
                'action_url' => '/b2b',
                'image' => 'https://images.unsplash.com/photo-1557804506-669a67965ba0?auto=format&fit=crop&w=600&q=80' // Handshake
            ],
            [
                'title' => 'Conferences',
                'identifier' => 'home_menu_conferences',
                'icon' => 'event',
                'action_url' => '/conferences',
                'image' => 'https://images.unsplash.com/photo-1544531696-60c35eb683f9?auto=format&fit=crop&w=600&q=80' // Speaker
            ],
            [
                'title' => 'My Badge',
                'identifier' => 'home_menu_badge',
                'icon' => 'badge',
                'action_url' => '/badge',
                'image' => 'https://images.unsplash.com/photo-1626785774573-4b7993143d2d?auto=format&fit=crop&w=600&q=80' // ID Card / Badge (Fixed)
            ],
            [
                'title' => 'Floor Plan',
                'identifier' => 'home_menu_floorplan',
                'icon' => 'map',
                'action_url' => '/floor-plan',
                'image' => 'https://images.unsplash.com/photo-1577412647305-991150c7d163?auto=format&fit=crop&w=600&q=80' // Map/Abstract
            ],
            [
                'title' => 'Networking',
                'identifier' => 'home_menu_networking',
                'icon' => 'emoji_events',
                'action_url' => '/networking',
                'image' => 'https://images.unsplash.com/photo-1515187029135-18ee286d815b?auto=format&fit=crop&w=600&q=80' // Crowd talking
            ],
            [
                'title' => 'Speakers',
                'identifier' => 'home_menu_speakers',
                'icon' => 'groups',
                'action_url' => '/speakers',
                'image' => 'https://images.unsplash.com/photo-1475721027760-f75cfc879794?auto=format&fit=crop&w=600&q=80' // Mic
            ]
        ];

        foreach ($menuItems as $index => $item) {
            HomeWidgetItem::create([
                'home_widget_id' => $menuGrid->id,
                'title' => $item['title'],
                'identifier' => $item['identifier'],
                'icon' => $item['icon'],
                'action_url' => $item['action_url'],
                'image' => $item['image'],
                'order' => $index + 1
            ]);
        }

        // ---------------------------------------------------------
        // 4. DYNAMIC LISTS
        // ---------------------------------------------------------

        HomeWidget::create([
            'title' => 'Featured Exhibitors',
            'identifier' => 'home_featured_exhibitors',
            'widget_type' => 'dynamic_list',
            'data_source' => 'companies',
            'order' => 40,
            'is_active' => false,
        ]);

        HomeWidget::create([
            'title' => 'Latest Products',
            'identifier' => 'home_latest_products',
            'widget_type' => 'dynamic_list',
            'data_source' => 'products',
            'order' => 50,
            'is_active' => false,
        ]);

        // ---------------------------------------------------------
        // 5. PARTNERS & LOGOS (FIXED & POPULATED)
        // ---------------------------------------------------------

        // --- A. Institutional Partners (4 Logos) ---
        $instPartners = HomeWidget::create([
            'title' => 'Institutional Partners',
            'identifier' => 'home_partners_inst',
            'widget_type' => 'logo_cloud',
            'order' => 60,
            'is_active' => false,
        ]);

        $instLogos = [
            'https://upload.wikimedia.org/wikipedia/commons/e/e5/NASA_logo.svg',
            'https://upload.wikimedia.org/wikipedia/commons/2/2f/Google_2015_logo.svg',
            'https://upload.wikimedia.org/wikipedia/commons/b/b3/World_Bank_Group_logo.svg',
            'https://upload.wikimedia.org/wikipedia/commons/5/51/IBM_logo.svg',
        ];

        foreach($instLogos as $i => $logo) {
            HomeWidgetItem::create([
                'home_widget_id' => $instPartners->id,
                'title' => 'Inst Partner ' . ($i+1),
                'image' => $logo,
                'action_url' => '#',
                'order' => $i + 1
            ]);
        }

        // --- B. Sponsors (4 Logos) ---
        $sponsors = HomeWidget::create([
            'title' => 'Sponsors',
            'identifier' => 'home_partners_sponsors',
            'widget_type' => 'logo_cloud',
            'order' => 70,
            'is_active' => false,
        ]);

        $sponsorLogos = [
            'https://upload.wikimedia.org/wikipedia/commons/9/96/Microsoft_logo_%282012%29.svg',
            'https://upload.wikimedia.org/wikipedia/commons/a/a9/Amazon_logo.svg',
            'https://upload.wikimedia.org/wikipedia/commons/0/08/Cisco_logo_blue_2016.svg',
            'https://upload.wikimedia.org/wikipedia/commons/5/50/Oracle_logo.svg',
        ];

        foreach($sponsorLogos as $i => $logo) {
            HomeWidgetItem::create([
                'home_widget_id' => $sponsors->id,
                'title' => 'Sponsor ' . ($i+1),
                'image' => $logo,
                'action_url' => '#',
                'order' => $i + 1
            ]);
        }

        // --- C. Trademarks (4 Logos) ---
        $trademarks = HomeWidget::create([
            'title' => 'Trademarks',
            'identifier' => 'home_partners_trademarks',
            'widget_type' => 'logo_cloud',
            'order' => 80,
            'is_active' => false,
        ]);

        $trademarkLogos = [
            'https://upload.wikimedia.org/wikipedia/commons/2/24/Samsung_Logo.svg',
            'https://upload.wikimedia.org/wikipedia/commons/7/7d/Intel_logo_%282006-2020%29.svg',
            'https://upload.wikimedia.org/wikipedia/commons/b/bd/Tesla_Motors.svg',
            'https://upload.wikimedia.org/wikipedia/commons/8/82/Dell_Logo.png',
        ];

        foreach($trademarkLogos as $i => $logo) {
            HomeWidgetItem::create([
                'home_widget_id' => $trademarks->id,
                'title' => 'Trademark ' . ($i+1),
                'image' => $logo,
                'action_url' => '#',
                'order' => $i + 1
            ]);
        }
    }
}
