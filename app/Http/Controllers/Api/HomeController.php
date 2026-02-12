<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomeWidget;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // 1. Fetch all active widgets sorted by order
        $widgets = HomeWidget::where('is_active', true)
            ->with(['items' => fn($q) => $q->orderBy('order')])
            ->orderBy('order')
            ->get();

        // 2. Transform them into the format Flutter expects
        $layoutData = $widgets->map(function ($widget) {

            // MAP BACKEND TYPES TO FLUTTER APP_SECTIONS CONSTANTS
            $type = $this->mapWidgetTypeToAppSection($widget);

            // FETCH CONTENT (Manual Items OR Dynamic DB Data)
            $content = $this->getWidgetContent($widget);

            return [
                'id'       => $widget->id,
                'type'     => $type, // 'main_slider', 'exhibitors', 'sponsors', etc.
                'title'    => $widget->title,
                'identifier' => $widget->identifier,
                'content'  => $content,
            ];
        });

        return response()->json([
            'layout' => $layoutData
        ]);
    }

    /**
     * Maps database widget_types to the AppSections constants in Flutter
     */
    private function mapWidgetTypeToAppSection(HomeWidget $widget)
    {
        // 1. Handle Dynamic Lists based on Source
        if ($widget->widget_type === 'dynamic_list') {
            return match ($widget->data_source) {
                'companies' => 'exhibitors',
                'products'  => 'products',
                'speakers'  => 'speakers',
                default     => 'list',
            };
        }

        // 2. Handle Manual Types
        return match ($widget->widget_type) {
            'slider'        => 'main_slider',
            'menu_grid'     => 'menu_grid', // We'll handle this special case in Flutter
            'logo_cloud'    => 'sponsors',   // Default to sponsors, logic can vary
            'single_banner' => 'sponsor_banner',
            default         => 'generic_section',
        };
    }

    /**
     * Resolves content: either from `home_widget_items` or real DB tables
     */
    private function getWidgetContent(HomeWidget $widget)
    {
        // A. DYNAMIC CONTENT (Real Data)
        if ($widget->widget_type === 'dynamic_list') {
            if ($widget->data_source === 'companies') {
                return Company::where('is_featured', true)
                    ->take(6)
                    ->get()
                    ->map(fn($c) => [
                        'id' => $c->id,
                        'title' => $c->name,
                        'identifier' => $c->identifier,
                        'subtitle' => $c->booth_number ? "Booth " . $c->booth_number : null,
                        'image_url' => $c->logo ? asset($c->logo) : null,
                        'action_id' => $c->id,
                    ]);
            }
            if ($widget->data_source === 'products') {
                return Product::where('is_featured', true)
                    ->take(6)
                    ->get()
                    ->map(fn($p) => [
                        'id' => $p->id,
                        'title' => $p->name,
                        'identifier' => $p->identifier,
                        'subtitle' => $p->company->name ?? null,
                        'image_url' => $p->image ? asset($p->image) : null,
                        'action_id' => $p->id,
                    ]);
            }
        }

        // B. MANUAL CONTENT (From Admin Panel Items)
        return $widget->items->map(function ($item) {
            return [
                'id'         => $item->id,
                'title'      => $item->title,
                'identifier' => $item->identifier,
                'subtitle'   => $item->subtitle,
                'image_url'  => $item->image_url, // Accessor from Model
                'logo_url'   => $item->image_url, // Alias for logo clouds
                'icon'       => $item->icon,      // For menu grids
                'action_url' => $item->action_url,
            ];
        });
    }
}
