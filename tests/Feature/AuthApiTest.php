<?php

use App\Models\User;
use function Pest\Laravel\{getJson, postJson};

test('can register a user and receive a token', function () {
    $response = postJson('/api/register', [
        'name' => 'Jane Reader',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token', 'token_type'])
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonMissingPath('user.password');

    expect(User::where('email', 'jane@example.com')->exists())->toBeTrue();
});

test('register validates input', function () {
    postJson('/api/register', [
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'short',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

test('register rejects duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    postJson('/api/register', [
        'name' => 'Someone',
        'email' => 'taken@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertStatus(422)->assertJsonValidationErrors(['email']);
});

test('can login with valid credentials', function () {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => bcrypt('password123'),
    ]);

    postJson('/api/login', [
        'email' => 'jane@example.com',
        'password' => 'password123',
    ])->assertStatus(200)
        ->assertJsonStructure(['user', 'token', 'token_type']);
});

test('login rejects wrong password', function () {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => bcrypt('password123'),
    ]);

    postJson('/api/login', [
        'email' => 'jane@example.com',
        'password' => 'wrong-password',
    ])->assertStatus(422)->assertJsonValidationErrors(['email']);
});

test('authenticated user can fetch own profile with bearer token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/user')
        ->assertStatus(200)
        ->assertJsonPath('id', $user->id)
        ->assertJsonMissingPath('password');
});

test('user endpoint returns 401 json when unauthenticated', function () {
    getJson('/api/user')->assertStatus(401);
});

test('user endpoint returns 401 json without Accept header', function () {
    $this->get('/api/user')->assertStatus(401)->assertHeader('Content-Type', 'application/json');
});

test('user endpoint rejects an invalid token', function () {
    $this->withHeader('Authorization', 'Bearer invalid-token')
        ->getJson('/api/user')
        ->assertStatus(401);
});

test('can login right after registering with the same password', function () {
    postJson('/api/register', [
        'name' => 'Jane Reader',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertStatus(201);

    postJson('/api/login', [
        'email' => 'jane@example.com',
        'password' => 'password123',
    ])->assertStatus(200);
});

test('login is rate limited', function () {
    foreach (range(1, 10) as $i) {
        postJson('/api/login', ['email' => 'x@example.com', 'password' => 'nope'])->assertStatus(422);
    }

    postJson('/api/login', ['email' => 'x@example.com', 'password' => 'nope'])->assertStatus(429);
});

test('expired token is rejected', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test');
    $token->accessToken->forceFill(['created_at' => now()->subDays(31)])->save();

    $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
        ->getJson('/api/user')
        ->assertStatus(401);
});

test('logout revokes the current token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/logout')
        ->assertStatus(200);

    expect($user->tokens()->count())->toBe(0);
});
