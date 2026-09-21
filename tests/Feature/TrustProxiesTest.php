<?php

test('url generation uses https for requests coming through a trusted https proxy', function () {
    $this->withServerVariables([
        'HTTP_X_FORWARDED_FOR' => '203.0.113.10',
        'HTTP_X_FORWARDED_HOST' => 'mbg.test',
        'HTTP_X_FORWARDED_PORT' => '443',
        'HTTP_X_FORWARDED_PROTO' => 'https',
    ])->get('/login')
        ->assertOk();

    expect(request()->isSecure())->toBeTrue();
    expect(route('login'))->toBe('https://mbg.test/login');
});

test('url generation keeps http for direct localhost requests without forwarded headers', function () {
    $this->get('/login')
        ->assertOk();

    expect(request()->isSecure())->toBeFalse();
    expect(route('login'))->toStartWith('http://')->toEndWith('/login');
});
