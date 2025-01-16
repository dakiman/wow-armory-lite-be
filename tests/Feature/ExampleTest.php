<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_mythics(): void
    {
        $response = $this->get('/api/mythics/eu/tarren-mill/Meeres');

        $response->assertStatus(200);
    }


    public function test_character(): void {
        $response = $this->get('/api/character/eu/the-maelstrom/Dáki');

        $response->assertStatus(200);
    }

    public function test_character_classic(): void {
        $response = $this->get('/api/character/eu/living-flame/Dakistan?isClassic=true');

        $response->assertStatus(200);
    }

    public function test_realms()
    {
        $response = $this->get('/api/realms');

        $response->assertStatus(200);
    }
}
