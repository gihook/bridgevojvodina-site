<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Club;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventMarkdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_description_is_rendered_as_markdown()
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
        
        $event = Event::create([
            'title' => 'Markdown Event',
            'date' => '2025',
            'club_id' => $club->id,
            'description' => '**Bold Text** and *Italic Text*',
        ]);

        $response = $this->get(route('events.show', $event));

        $response->assertStatus(200);
        $response->assertSee('<strong>Bold Text</strong>', false);
        $response->assertSee('<em>Italic Text</em>', false);
    }
}
