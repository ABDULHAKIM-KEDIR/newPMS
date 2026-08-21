<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

describe('session configuration', function () {
    it('creates the sessions table required by the database session driver', function () {
        expect(Schema::hasTable('sessions'))->toBeTrue();
    });
});
