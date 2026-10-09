<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Orchid\Filters\ConversationDateFilter;
use App\Orchid\Screens\Interaction\ConversationListScreen;
use Illuminate\Http\Request;
use Tests\TestCase;

class ConversationHistoryPresentationTest extends TestCase
{
    public function test_span_labels_do_not_imply_recent_activity(): void
    {
        $screen = new ConversationListScreen;
        $render = new \ReflectionMethod($screen, 'renderStats');

        foreach ([0 => 'Less than 1 day', 1 => '1 day', 6 => '6 days'] as $days => $label) {
            $html = $render->invoke($screen, (object) [
                'user_1_id' => 1, 'user_2_id' => 2,
                'p1' => null, 'p2' => null,
                'first_message_at' => '2026-09-01 10:00:00',
                'last_message_at' => '2026-09-07 10:00:00',
                'duration_days' => $days, 'total_messages' => 1,
            ]);
            $this->assertStringContainsString('Conversation span: '.$label, $html);
            $this->assertStringContainsString('1 message</span>', $html);
            $this->assertStringNotContainsString('Today', $html);
            $this->assertStringNotContainsString('ago', $html);
        }
    }

    public function test_empty_state_and_visible_filters_render(): void
    {
        $html = view('admin.conversations-history', [
            'styles' => '',
            'metrics' => ['total_convos' => 0, 'total_messages' => 0, 'active_today' => 0, 'avg_messages' => 0],
            'conversations' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15),
        ])->render();

        $this->assertStringContainsString('No conversations found', $html);
        $this->assertStringContainsString('form="filters"', $html);
        $this->assertStringContainsString('Participant name or email', $html);
        $this->assertStringContainsString('Latest activity', $html);
    }

    public function test_activity_filter_matches_aggregate_without_truncating_messages(): void
    {
        $filter = new ConversationDateFilter;
        $filter->request = new Request(['activity' => 'week']);
        $query = $filter->run(Message::query()->selectRaw('MAX(created_at) as last_message_at')->groupBy('sender_id', 'receiver_id'));

        $this->assertStringContainsString('having MAX(created_at) >= ?', $query->toSql());
        $this->assertEmpty($query->getQuery()->wheres);
        $this->assertCount(1, $query->getBindings());
    }
}
