<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Interaction\Networking;

use App\Models\Connection;
use App\Models\User;
use Illuminate\Http\Request;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Color;
use Orchid\Support\Facades\Toast;
use Illuminate\Support\Str;

class ConnectionRequestListScreen extends Screen
{
    public $name = 'Networking Moderation';
    public $description = 'Monitor and manage connection requests between event participants.';

    public function query(Request $request): array
    {
        $status = $request->get('status');

        // Eager load roles and companies to prevent N+1 issues
        $requests = Connection::with([
            'requester.company',
            'requester.roles',
            'target.company',
            'target.roles'
        ])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return [
            'requests' => $requests,
            'metrics' => [
                'pending'  => ['value' => number_format(Connection::where('status', 'pending')->count()), 'label' => 'Awaiting Action'],
                'accepted' => ['value' => number_format(Connection::where('status', 'accepted')->count()), 'label' => 'Total Matches'],
                'declined' => ['value' => number_format(Connection::where('status', 'declined')->count()), 'label' => 'Rejected'],
                'today'    => ['value' => number_format(Connection::whereDate('created_at', today())->count()), 'label' => 'Requests Today'],
            ],
        ];
    }

    public function commandBar(): array
    {
        return [
            Link::make('All Activity')->route('platform.networking.requests')->icon('bs.collection'),
            Link::make('Pending')->route('platform.networking.requests', ['status' => 'pending'])->icon('bs.hourglass-split')->type(Color::WARNING),
            Link::make('Accepted')->route('platform.networking.requests', ['status' => 'accepted'])->icon('bs.check-all')->type(Color::SUCCESS),
        ];
    }

    public function layout(): array
    {
        return [
            /* ================= Modern Metrics ================= */
            Layout::metrics([
                'Pending'   => 'metrics.pending',
                'Accepted'  => 'metrics.accepted',
                'Declined'  => 'metrics.declined',
                'New Today' => 'metrics.today',
            ]),

            /* ================= Requests Table ================= */
            Layout::table('requests', [

                TD::make('requester', 'Requester (From)')
                    ->width('350px')
                    ->render(fn ($r) => $this->renderUserPersona($r->requester)),

                TD::make('direction', '')
                    ->align(TD::ALIGN_CENTER)
                    ->width('50px')
                    ->render(fn() => '<div class="text-muted opacity-50"><i class="icon-arrow-right-circle" style="font-size: 1.5rem;"></i></div>'),

                TD::make('target', 'Recipient (To)')
                    ->width('350px')
                    ->render(fn ($r) => $this->renderUserPersona($r->target)),

                TD::make('status', 'Moderation State')
                    ->align(TD::ALIGN_CENTER)
                    ->render(fn ($r) => $this->renderStatusBadge($r->status)),

                TD::make('created_at', 'Timeline')
                    ->sort()
                    ->align(TD::ALIGN_RIGHT)
                    ->render(fn ($r) => sprintf(
                        '<div class="text-dark">%s</div><div class="small text-muted">%s</div>',
                        $r->created_at->diffForHumans(),
                        $r->created_at->format('M d, H:i')
                    )),

                TD::make(__('Actions'))
                    ->align(TD::ALIGN_RIGHT)
                    ->render(fn ($r) => $this->renderActionButtons($r)),
            ]),
        ];
    }

    /**
     * Helper to render a detailed User Persona Card
     */
    private function renderUserPersona(?User $user): string
    {
        if (!$user) return '<span class="text-muted">Deleted User</span>';

        $avatar = $user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=random';
        $company = optional($user->company)->name ?? 'No Company';

        // Role Logic: Exhibitor (Blue) vs Visitor (Green)
        $isExhibitor = $user->roles->contains('slug', 'exhibitor');
        $roleLabel = $isExhibitor ? 'EXHIBITOR' : 'VISITOR';
        $roleColor = $isExhibitor ? '#d4af37' : '#28a745'; // Gold for Exhibitor, Green for Visitor

        return sprintf(
            '<div class="d-flex align-items-center">
                <div class="position-relative">
                    <img src="%s" class="rounded-circle shadow-sm" style="width: 45px; height: 45px; object-fit: cover; border: 2px solid #fff;">
                    <span class="position-absolute bottom-0 end-0 badge rounded-pill" style="background-color:%s; width:12px; height:12px; border:2px solid #fff;" title="%s"></span>
                </div>
                <div class="ml-3" style="line-height: 1.2;">
                    <div class="font-weight-bold text-dark">%s</div>
                    <div class="small text-muted">%s</div>
                    <div class="mt-1"><span class="badge" style="background-color:%s15; color:%s; font-size: 0.65rem; border: 1px solid %s30;">%s</span></div>
                </div>
            </div>',
            $avatar,
            $roleColor,
            $roleLabel,
            e($user->name . ' ' . $user->last_name),
            e(Str::limit($user->job_title . ' @ ' . $company, 40)),
            $roleColor, $roleColor, $roleColor,
            $roleLabel
        );
    }

    private function renderStatusBadge(string $status): string
    {
        $style = match ($status) {
            'pending'  => 'background: #fff3cd; color: #856404; border: 1px solid #ffeeba;',
            'accepted' => 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;',
            'declined' => 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;',
            default    => 'background: #e2e3e5; color: #383d41;'
        };

        return sprintf(
            '<span class="badge rounded-pill" style="padding: 5px 12px; %s">%s</span>',
            $style,
            strtoupper($status)
        );
    }

    private function renderActionButtons($r): string
    {
        if ($r->status !== 'pending') {
            return '<span class="text-muted small">Processed</span>';
        }

        return '<div class="btn-group btn-group-sm"> ' .
            Button::make('')
                ->icon('bs.check-lg')
                ->type(Color::SUCCESS)
                ->confirm('Are you sure you want to manually accept this request?')
                ->method('forceAccept', ['id' => $r->id])
                ->render() .
            Button::make('')
                ->icon('bs.x-lg')
                ->type(Color::DANGER)
                ->confirm('Are you sure you want to manually decline this request?')
                ->method('forceDecline', ['id' => $r->id])
                ->render() .
            '</div>';
    }

    /* ================= Actions ================= */

    public function forceAccept(int $id)
    {
        Connection::whereKey($id)->update(['status' => 'accepted']);
        Toast::success('Connection request approved.');
    }

    public function forceDecline(int $id)
    {
        Connection::whereKey($id)->update(['status' => 'declined']);
        Toast::info('Connection request rejected.');
    }
}
