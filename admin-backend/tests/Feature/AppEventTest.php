<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\AppEvents;

class AppEventTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testExample()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
    public function test_create_event()
    {
        $response = $this->json('POST', 'api/event', [
            'name' => 'Test/Event',
            'isActive' => 1
        ]);

        // Write the response to the log 
        \Log::info(1, [$response->getContent()]);

        $response->assertStatus(200);
    }
    public function test_update_event()
    {
        $response = $this->json('PUT', 'api/event/6', [
            'name' => 'Test/Update/Event',
            'isActive' => 1
        ]);

        // Write the response to the log 
        \Log::info(1, [$response->getContent()]);

        $response->assertStatus(200);
    }
    public function test_find_event()
    {

        $response = $this->json('GET', 'api/event/6');

        // Write the response to the log 
        \Log::info(1, [$response->getContent()]);

        $response->assertStatus(200);
    }
    public function test_get_all_events()
    {
        $response = $this->json('GET', 'api/event');

        // Write the response to the log 
        \Log::info(1, [$response->getContent()]);

        $response->assertStatus(200);
    }
    public function test_delete_event()
    {
        $response = $this->json('DELETE', 'api/event/6');

        // Write the response to the log 
        \Log::info(1, [$response->getContent()]);

        $response->assertStatus(200);
    }
}
