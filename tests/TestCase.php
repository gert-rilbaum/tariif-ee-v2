<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Testid ei sõltu npm-buildist. Vastasel juhul kukuks CI, kus Node'i
        // ei jooksutata, ja iga arendaja peaks enne teste vite build'i tegema.
        $this->withoutVite();
    }
}
