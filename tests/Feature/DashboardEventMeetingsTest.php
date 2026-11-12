<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\EventSetting;
use App\Models\User;
use App\Orchid\Screens\PlatformScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardEventMeetingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $a;
    private User $b;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-12 10:00:00');

        $this->admin = User::factory()->create([
            'app_role'    => User::APP_ROLE_ADMIN,
            'permissions' => ['platform.index' => true],
        ]);
        $this->a = User::factory()->create(['app_role' => User::APP_ROLE_VISITOR]);
        $this->b = User::factory()->create(['app_role' => User::APP_ROLE_EXHIBITOR]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function meeting(string $at, string $status = 'confirmed'): void
    {
        Appointment::create([
            'booker_id'      => $this->a->id,
            'target_user_id' => $this->b->id,
            'scheduled_at'   => $at,
            'status'         => $status,
        ]);
    }

    private function eventMeetings(): array
    {
        // The page renders...
        $this->actingAs($this->admin)->get(route('platform.main'))->assertOk()
            ->assertSee('Meeting Volume');

        // ...and the screen hands the chart these numbers.
        return iterator_to_array((new PlatformScreen())->query())['eventMeetings'];
    }

    public function test_meetings_are_grouped_by_event_day_and_hour(): void
    {
        EventSetting::create([
            'event_name' => 'Expo',
            'start_date' => '2026-11-18 09:00:00',
            'end_date'   => '2026-11-20 18:00:00',
        ]);

        $this->meeting('2026-11-18 10:00:00', 'confirmed');
        $this->meeting('2026-11-18 10:30:00', 'pending');
        $this->meeting('2026-11-19 14:00:00', 'completed');
        $this->meeting('2026-11-19 15:00:00', 'cancelled'); // excluded
        $this->meeting('2026-11-20 09:00:00', 'declined');  // excluded
        $this->meeting('2026-11-01 11:00:00', 'confirmed'); // outside
        $this->meeting('2026-11-25 11:00:00', 'pending');   // outside
        $this->meeting('2026-11-25 12:00:00', 'cancelled'); // outside but inactive: not counted

        $data = $this->eventMeetings();

        $this->assertTrue($data['configured']);
        $this->assertCount(3, $data['days']);
        $this->assertSame('Day 1 · Wed 18 Nov', $data['days'][0]['label']);
        $this->assertSame([1, 1, 0, 2], [
            $data['days'][0]['confirmed'], $data['days'][0]['pending'],
            $data['days'][0]['completed'], $data['days'][0]['total'],
        ]);
        $this->assertSame(1, $data['days'][1]['completed']);
        $this->assertSame(0, $data['days'][2]['total']);
        $this->assertSame(3, $data['total']);
        $this->assertSame(2, $data['outside']);

        // Hourly view: 09:00..18:00, both day-1 meetings in the 10:00 slot.
        $this->assertSame('09:00', $data['hours'][0]);
        $this->assertSame('18:00', end($data['hours']));
        $this->assertSame(2, $data['days'][0]['hours'][1]);

        // Before the event the KPI shows day 1 bookings.
        $this->assertSame(['value' => 2, 'label' => 'booked for day 1'], $data['focus']);
    }

    public function test_kpi_shows_todays_meetings_during_the_event(): void
    {
        EventSetting::create([
            'event_name' => 'Expo',
            'start_date' => '2026-11-11 09:00:00',
            'end_date'   => '2026-11-13 18:00:00',
        ]);
        $this->meeting('2026-11-12 11:00:00');
        $this->meeting('2026-11-12 19:30:00'); // after usual hours: axis widens

        $data = $this->eventMeetings();

        $this->assertSame(2, $data['currentDay']);
        $this->assertSame(['value' => 2, 'label' => 'meetings today (day 2)'], $data['focus']);
        $this->assertSame('19:00', end($data['hours']));
    }

    public function test_dashboard_works_without_event_dates(): void
    {
        $this->meeting('2026-11-12 11:00:00');

        $this->actingAs($this->admin)
            ->get(route('platform.main'))
            ->assertOk()
            ->assertSee('Set the event start and end dates in Event Settings');
    }
}
