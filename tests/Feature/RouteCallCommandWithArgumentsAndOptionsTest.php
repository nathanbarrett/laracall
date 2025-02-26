<?php


test('it can make GET request to messages index endpoint by calling route uri', function () {

    $result = $this->artisan('route:call /messages');

    $result->assertExitCode(0)
        ->expectsOutputToContain("Calling route 'messages' with method 'GET'...")
        ->expectsOutputToContain("Response: \e[32m200\e[39m")
        ->expectsOutputToContain('"message": "index"');

})->group('command-with-arguments');

test('it can make a GET request to messages index endpoint by calling route name', function () {

    $result = $this->artisan('route:call messages.index');

    $result->assertExitCode(0)
        ->expectsOutputToContain("Calling route 'messages' with method 'GET'...")
        ->expectsOutputToContain("Response: \e[32m200\e[39m")
        ->expectsOutputToContain('"message": "index"');

})->group('command-with-arguments');

test('it should error when calling a non-existent route uri', function () {

    $result = $this->artisan('route:call /non-existent');

    $result->assertExitCode(1)
        ->expectsOutputToContain("Route with name `/non-existent` not found");

})->group('command-with-arguments');

test('it should error when calling a non-existent route name', function () {

    $result = $this->artisan('route:call non-existent');

    $result->assertExitCode(1)
        ->expectsOutputToContain("Route with name `non-existent` not found");

})->group('command-with-arguments');

test('it should error when calling a route with a method that is not allowed', function () {

    $result = $this->artisan('route:call messages.index --method=POST');

    $result->assertExitCode(1)
        ->expectsOutputToContain("Route with name `messages.index` does not allow method `POST`");

})->group('command-with-arguments');

test('it can make a POST request to messages store endpoint by calling route name and using the method of the route', function () {

    $result = $this->artisan('route:call messages.store');

    $result->assertExitCode(0)
        ->expectsOutputToContain("Calling route 'messages' with method 'POST'...")
        ->expectsOutputToContain("Response: \e[32m200\e[39m")
        ->expectsOutputToContain('"message": "store"');

})->group('command-with-arguments');

test('it can make a POST request to messages store endpoint by calling route uri and specifying the method', function () {

    $result = $this->artisan('route:call /messages --method=POST --body={"name":"test"} --output-raw');

    $result->assertExitCode(0)
        ->expectsOutputToContain("Calling route 'messages' with method 'POST'...")
        ->expectsOutputToContain("Response: \e[32m200\e[39m")
        ->expectsOutputToContain('{"message":"store","body":{"name":"test"}}');

})->group('command-with-arguments', 'testing');
