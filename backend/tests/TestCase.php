<?php

namespace Tests;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Requests to /api/v1/auth/* only get a session (needed to log a user in)
     * when Sanctum's EnsureFrontendRequestsAreStateful middleware recognizes
     * the request as coming from a stateful SPA origin. Real browsers send
     * this automatically on every cross-origin request; the test client does
     * not, so it's set here to match how the SPA actually calls the API.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Referer', config('app.frontend_url'));

        if (in_array(RefreshDatabase::class, class_uses_recursive(static::class))) {
            $this->seed(RoleSeeder::class);
        }
    }
}
