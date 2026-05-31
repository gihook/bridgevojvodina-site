<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Club;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_events_are_ordered_from_most_recent_to_oldest()
    {
        $club = Club::create([
            'name' => 'Test Club',
            'city' => 'Test City',
            'address' => 'Test Address',
            'representative' => 'Test Rep',
            'email' => 'test@example.com',
            'phone' => '123456',
            'status' => 'Active'
        ]);
        
        $oldEvent = Event::create([
            'title' => 'Old Event',
            'date' => '2020',
            'club_id' => $club->id,
            'created_at' => now()->subDays(2),
        ]);

        $newEvent = Event::create([
            'title' => 'New Event',
            'date' => '2025',
            'club_id' => $club->id,
            'created_at' => now(),
        ]);

        $response = $this->get(route('events.index'));

        $response->assertStatus(200);
        
        // Check if New Event appears before Old Event
        $response->assertSeeInOrder(['New Event', 'Old Event']);
    }
}
