<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomepageTest extends TestCase
{
    public function test_homepage_can_be_rendered(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }
}
