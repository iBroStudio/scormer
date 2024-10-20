<?php

namespace Tests;

use Illuminate\Support\Facades\Config;
use LaravelZero\Framework\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('app.env', 'testing');
    }
}
