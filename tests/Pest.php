<?php

use Backstage\Announcements\Tests\Fixtures\User;
use Backstage\Announcements\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function login(): User
{
    $user = User::create([
        'name' => 'Test User',
        'email' => fake()->unique()->safeEmail(),
        'password' => bcrypt('password'),
    ]);

    test()->actingAs($user);

    return $user;
}
