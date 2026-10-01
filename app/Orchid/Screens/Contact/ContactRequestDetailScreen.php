<?php

namespace App\Orchid\Screens\Contact;

use App\Models\ContactRequest;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Orchid\Support\Color;

class ContactRequestDetailScreen extends Screen
{
    public $contact;

    public function query(ContactRequest $contact): iterable
    {
        return [
            'contact' => $contact->load('user'),
        ];
    }

    public function name(): ?string
    {
        return 'Message #' . ($this->contact->id ?? '');
    }

    public function description(): ?string
    {
        return $this->contact?->subject ?: 'Contact request detail';
    }

    public function commandBar(): iterable
    {
        $c = $this->contact;

        return [
            Link::make('Reply by email')
                ->icon('bs.reply')
                ->href($this->mailtoLink($c))
                ->target('_blank')
                ->canSee((bool) $c?->email)
                ->type(Color::PRIMARY()),

            Button::make($c?->is_handled ? 'Mark as New' : 'Mark as Handled')
                ->icon($c?->is_handled ? 'bs.arrow-counterclockwise' : 'bs.check2-circle')
                ->method('toggleHandled'),

            Button::make('Delete')
                ->icon('bs.trash3')
                ->confirm('Delete this message permanently? This cannot be undone.')
                ->method('remove')
                ->type(Color::DANGER()),

            Link::make('Back to Inbox')
                ->icon('bs.arrow-left')
                ->route('platform.contacts'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::legend('contact', [
                Sight::make('id', 'Reference')->render(fn (ContactRequest $c) => '#' . $c->id),

                Sight::make('name', 'From')->render(fn (ContactRequest $c) => sprintf(
                    '<strong>%s</strong>%s',
                    e($c->name ?: '—'),
                    $c->user_id ? ' <span class="badge bg-primary">App user</span>' : ''
                )),

                Sight::make('email', 'Email')->render(fn (ContactRequest $c) => $c->email
                    ? sprintf('<a href="%s">%s</a>', $this->mailtoLink($c), e($c->email))
                    : '<span class="text-muted">—</span>'),

                Sight::make('subject', 'Subject')->render(fn (ContactRequest $c) => e($c->subject ?: '(no subject)')),

                Sight::make('created_at', 'Received')->render(fn (ContactRequest $c) =>
                    $c->created_at->format('l, F j, Y · H:i')),

                Sight::make('is_handled', 'Status')->render(fn (ContactRequest $c) => $c->is_handled
                    ? '<span class="badge bg-success">Handled</span>'
                    : '<span class="badge bg-danger">New</span>'),

                Sight::make('message', 'Message')->render(fn (ContactRequest $c) =>
                    '<div class="p-3 bg-light rounded border" style="white-space:pre-wrap;line-height:1.6;">'
                    . e($c->message) . '</div>'),
            ])->title('Contact request'),
        ];
    }

    public function toggleHandled(ContactRequest $contact)
    {
        $contact->update(['is_handled' => ! $contact->is_handled]);

        Toast::info($contact->is_handled ? 'Marked as handled.' : 'Marked as new.');
    }

    public function remove(ContactRequest $contact)
    {
        $contact->delete();

        Toast::success('Message deleted.');

        return redirect()->route('platform.contacts');
    }

    private function mailtoLink(?ContactRequest $c): string
    {
        if (! $c || ! $c->email) {
            return '#';
        }

        $subject = $c->subject ? 'Re: ' . $c->subject : 'Re: your message';

        return 'mailto:' . $c->email . '?subject=' . rawurlencode($subject);
    }
}
