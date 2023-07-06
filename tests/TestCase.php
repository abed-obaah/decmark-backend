<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use SetUpAuthentication;

    public function tearDown(): void
    {
        parent::tearDown();
    }
}
