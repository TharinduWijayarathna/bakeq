<?php

use App\Models\Cake;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

function customer(array $attributes = []): User
{
    return User::factory()->create($attributes);
}

function adminUser(array $attributes = []): User
{
    return User::factory()->admin()->create($attributes);
}

function cake(array $attributes = []): Cake
{
    return Cake::factory()->create($attributes);
}
