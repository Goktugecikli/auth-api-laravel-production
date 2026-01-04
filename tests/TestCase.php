<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    //
    protected function setUp(): void
    {
        parent::setUp();

        // Testlerde sadece Bearer token auth istiyoruz (cookie/session değil)
        config()->set('sanctum.stateful', []);
    }



}
