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
        // หน้าแรกเปลี่ยนเส้นทางไปยังแดชบอร์ด (และผู้ที่ยังไม่ล็อกอินจะถูกส่งไปหน้า login)
        $response = $this->get('/');

        $response->assertRedirect('/dashboard');
    }
}
