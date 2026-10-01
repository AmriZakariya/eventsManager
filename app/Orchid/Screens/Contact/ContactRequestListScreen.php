<?php

namespace App\Orchid\Screens\Contact;

use App\Models\ContactRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Screen\Sight;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Group;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Orchid\Support\Color;

class ContactRequestListScreen extends Screen
{
    public function name(): ?string { return 'Contact Inbox'; }

    public function description(): ?string { return 'Support messages and inquiries from the mobile app.'; }

    public function query(Request $request): iterable
    {
        $base = ContactRequest::query();

        // ── Stats (counted before filters) ──
        $total   = (clone $base)->count();
        $new     = (clone $base)->where('is_handled', false)->count();
        $handled = (clone $base)->where('is_handled', true)->count();
        $today   = (clone $base)->whereDate('created_at', today())->count();

        // ── Filtered list ──
        $list = ContactRequest::with('user')->orderBy('created_at', 'desc');

        if ($search = $request->get('search')) {
            $list->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('subject', 'like', "%$search%")
                    ->orWhere('message', 'like', "%$search%");
            });
        }

        if (($status = $request->get('status')) !== null && $status !== '') {
            $list->where('is_handled', $status === 'handled');
        }

        return [
            'contacts' => $list->paginate(20),
            'metrics'  => [
                'total'   => ['value' => number_format($total)],
                'new'     => ['value' => number_format($new)],
                'handled' => ['value' => number_format($handled)],
                'today'   => ['value' => number_format($today)],
            ],
        ];
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        return [
            Layout::metrics([
                'Total'   => 'metrics.total',
                'New'     => 'metrics.new',
                'Handled' => 'metrics.handled',
                'Today'   => 'metrics.today',
            ]),

            // ── Filters ──
            Layout::rows([
                Group::make([
                    Input::make('search')
                        ->title('Search')
                        ->placeholder('Name, email, subject, message...')
                        ->value(request('search')),

                    Select::make('status')
                        ->title('Status')
                        ->empty('All', '')
                        ->options([
                            'new'     => 'New',
                            'handled' => 'Handled',
                        ])
                        ->value(request('status')),
                ]),

                Group::make([
                    Button::make('Apply')
                        ->icon('bs.funnel-fill')
                        ->method('applyFilters')
                        ->type(Color::PRIMARY()),

                    Button::make('Clear')
                        ->icon('bs.x-circle')
                        ->method('clearFilters')
                        ->type(Color::DEFAULT()),
                ])->autoWidth(),
            ]),

            Layout::table('contacts', [
                TD::make('created_at', 'Date')
                    ->width('130px')
                    ->sort()
                    ->render(fn (ContactRequest $c) => sprintf(
                        '<div class="fw-semibold" style="font-size:0.8rem;">%s</div><small class="text-muted">%s</small>',
                        $c->created_at->format('M d, Y'),
                        $c->created_at->format('H:i')
                    )),

                TD::make('name', 'Sender')
                    ->render(function (ContactRequest $c) {
                        $initial = strtoupper(mb_substr($c->name ?: ($c->email ?: '?'), 0, 1));
                        $appBadge = $c->user_id
                            ? '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle ms-1" style="font-size:0.6rem;">APP USER</span>'
                            : '';

                        return sprintf(
                            '<div class="d-flex align-items-center">
                                <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center me-2 fw-bold" style="width:34px;height:34px;font-size:14px;">%s</div>
                                <div>
                                    <div class="fw-semibold">%s %s</div>
                                    <small class="text-muted">%s</small>
                                </div>
                            </div>',
                            $initial,
                            e($c->name ?: '—'),
                            $appBadge,
                            e($c->email ?: 'no email')
                        );
                    }),

                TD::make('subject', 'Subject & Message')
                    ->width('420px')
                    ->render(fn (ContactRequest $c) => sprintf(
                        '<div class="fw-semibold">%s</div><div class="text-muted" style="font-size:0.82rem;">%s</div>',
                        e($c->subject ?: '(no subject)'),
                        e(Str::limit($c->message, 90))
                    )),

                TD::make('is_handled', 'Status')
                    ->width('110px')
                    ->alignCenter()
                    ->sort()
                    ->render(fn (ContactRequest $c) => $c->is_handled
                        ? '<span class="badge bg-success bg-opacity-10 text-success border border-success-subtle">✔ Handled</span>'
                        : '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle">● New</span>'),

                TD::make('Actions')
                    ->alignRight()
                    ->width('80px')
                    ->render(function (ContactRequest $c) {
                        $actions = [];

                        $actions[] = Link::make('Open page')
                            ->icon('bs.box-arrow-up-right')
                            ->route('platform.contacts.detail', $c);

                        $actions[] = ModalToggle::make('Quick view')
                            ->icon('bs.envelope-open')
                            ->modal('viewMessageModal')
                            ->modalTitle('Message from ' . ($c->name ?: $c->email ?: 'visitor'))
                            ->asyncParameters(['contact' => $c->id]);

                        if ($c->email) {
                            $actions[] = Link::make('Reply by email')
                                ->icon('bs.reply')
                                ->href($this->mailtoLink($c))
                                ->target('_blank');
                        }

                        $actions[] = Button::make($c->is_handled ? 'Mark as New' : 'Mark as Handled')
                            ->icon($c->is_handled ? 'bs.arrow-counterclockwise' : 'bs.check2-circle')
                            ->method('toggleHandled', ['id' => $c->id]);

                        $actions[] = Button::make('Delete')
                            ->icon('bs.trash3')
                            ->confirm('Delete this message permanently? This cannot be undone.')
                            ->method('remove', ['id' => $c->id]);

                        return DropDown::make()
                            ->icon('bs.three-dots-vertical')
                            ->list($actions);
                    }),
            ]),

            // ── Detail modal ──
            Layout::modal('viewMessageModal', Layout::legend('contact', [
                Sight::make('name', 'From')->render(fn (ContactRequest $c) => sprintf(
                    '<strong>%s</strong>%s',
                    e($c->name ?: '—'),
                    $c->user_id ? ' <span class="badge bg-primary">App user</span>' : ''
                )),
                Sight::make('email', 'Email')->render(fn (ContactRequest $c) => $c->email
                    ? sprintf('<a href="%s">%s</a>', $this->mailtoLink($c), e($c->email))
                    : '<span class="text-muted">—</span>'),
                Sight::make('subject', 'Subject')->render(fn (ContactRequest $c) => e($c->subject ?: '(no subject)')),
                Sight::make('created_at', 'Received')->render(fn (ContactRequest $c) => $c->created_at->format('l, F j, Y · H:i')),
                Sight::make('is_handled', 'Status')->render(fn (ContactRequest $c) => $c->is_handled
                    ? '<span class="badge bg-success">Handled</span>'
                    : '<span class="badge bg-danger">New</span>'),
                Sight::make('message', 'Message')->render(fn (ContactRequest $c) =>
                    '<div class="p-3 bg-light rounded border" style="white-space:pre-wrap;">' . e($c->message) . '</div>'),
            ]))
                ->title('Message details')
                ->async('asyncGetContact')
                ->withoutApplyButton()
                ->closeButton('Close'),
        ];
    }

    public function asyncGetContact(ContactRequest $contact): array
    {
        return ['contact' => $contact->load('user')];
    }

    public function applyFilters(Request $request)
    {
        return redirect()->route('platform.contacts', array_filter([
            'search' => $request->get('search'),
            'status' => $request->get('status'),
        ]));
    }

    public function clearFilters()
    {
        return redirect()->route('platform.contacts');
    }

    public function toggleHandled(Request $request)
    {
        $contact = ContactRequest::findOrFail($request->get('id'));
        $contact->update(['is_handled' => ! $contact->is_handled]);

        Toast::info($contact->is_handled ? 'Marked as handled.' : 'Marked as new.');
    }

    public function remove(Request $request)
    {
        ContactRequest::where('id', $request->get('id'))->delete();

        Toast::success('Message deleted.');
    }

    private function mailtoLink(ContactRequest $c): string
    {
        $subject = $c->subject ? 'Re: ' . $c->subject : 'Re: your message';

        return 'mailto:' . $c->email . '?subject=' . rawurlencode($subject);
    }
}
