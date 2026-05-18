<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        // Clear Eloquent's static booted-model cache so that models boot fresh
        // against the current test's schema (avoids stale attribute drops when
        // a prior test class used a different table structure).
        Model::clearBootedModels();

        // Clear the guardable-columns static cache so that mass-assignment
        // column introspection reflects the current test's DB schema rather
        // than a schema from a prior test class that used a stripped table.
        $prop = (new \ReflectionProperty(Model::class, 'guardableColumns'));
        $prop->setAccessible(true);
        $prop->setValue(null, []);

        parent::setUp();
    }
}
