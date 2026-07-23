<?php

test('the root url redirects guests to the login page', function () {
    $this->get('/')->assertRedirect('/login');
});

test('the login route is named so redirects can resolve it', function () {
    expect(route('login', absolute: false))->toBe('/login');
});
