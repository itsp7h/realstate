<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Every route requires auth now, so tests act as a Super Admin by
     * default (full access, matching pre-auth behavior) unless a test
     * explicitly acts as a different role or logs out to test guest access.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }
}
