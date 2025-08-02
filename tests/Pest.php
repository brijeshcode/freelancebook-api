<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the amount of code you write.
|
*/

function something()
{
    // ..
}

// Custom helpers for API testing
function actingAsUser($user = null)
{
    $user = $user ?: \App\Models\User::factory()->create();
    return test()->actingAs($user, 'sanctum');
}

function createAuthenticatedRequest()
{
    $user = \App\Models\User::factory()->create();
    return [
        'user' => $user,
        'headers' => [
            'Authorization' => 'Bearer ' . $user->createToken('test')->plainTextToken,
            'Accept' => 'application/json',
        ]
    ];
}


function directSanctumLogin(){
    $authUser = User::factory()->create();
    Sanctum::actingAs($authUser);
    return $authUser;
}

function login($user = null)
{
    return test()->actingAs($user ?? User::factory()->create(), 'sanctum');
}

function logout()
{
    // test()->post(route('v1.admin.logout'));
}

function asAdmin($user = null)
{
    // adminPermission();
    return login($user);
}
