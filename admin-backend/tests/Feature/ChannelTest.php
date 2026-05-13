<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\NotificationChannels;


class ChannelTest extends TestCase
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

    public function test_create_channel()
    {
        $response = $this->json('POST','api/channel',[
            'name'=>'Test Channel',
            'isActive'=>1
        ]);

        // Write the response to the log 
        \Log::info(1,[$response->getContent()]);

        $response->assertStatus(200);
    }
    public function test_update_channel()
    {
        $response = $this->json('PUT','api/channel/6',[
            'name'=>'Test Update Channel',
            'isActive'=>1
        ]);

        // Write the response to the log 
        \Log::info(1,[$response->getContent()]);

        $response->assertStatus(200);
    }
    public function test_find_channel(){
      
        $response = $this->json('GET','api/channel/6');

        // Write the response to the log 
        \Log::info(1,[$response->getContent()]);

        $response->assertStatus(200);
    }    
    public function test_get_all_channel()
    {
        $response = $this->json('GET','api/channel');

        // Write the response to the log 
        \Log::info(1,[$response->getContent()]);

        $response->assertStatus(200);
    }
    public function test_delete_channel()
    {
        $response = $this->json('DELETE','api/channel/6');

        // Write the response to the log 
        \Log::info(1,[$response->getContent()]);

        $response->assertStatus(200);
    }
}
