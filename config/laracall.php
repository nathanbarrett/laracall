<?php declare(strict_types=1);

// config for NathanBarrett/LaraCall
return [

    /*
    |--------------------------------------------------------------------------
    | App Debug
    |--------------------------------------------------------------------------
    |
    | Whether to turn on debugging for the app. This value will only be used
    | for the command's request. It does not affect the app's debug setting.
    |
    */

    'debug' => true,

    /*
    |--------------------------------------------------------------------------
    | Allowed Environments
    |--------------------------------------------------------------------------
    |
    | The environments in which the app is allowed to run. If * is specified
    | the app will run in all environments.
    |
    */

    'allowed_environments' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Acting As Entity Class
    |--------------------------------------------------------------------------
    |
    | If you are using Laravel's built-in authentication, you can specify the
    | entity that should be used for authentication. If none is specified,
    | the default entity from the Auth Provider will be used. This default is
    | usually the User model. Use the fully qualified class name.
    |
    */

    'acting_as_entity' => null,

    /*
    |--------------------------------------------------------------------------
    | Acting As Identifiers
    |--------------------------------------------------------------------------
    |
    | It will check the value of the auth value against the following array
    | of column names of the acting as entity class
    |
    */

    'acting_as_identifiers' => ['id', 'email'],

    /*
    |--------------------------------------------------------------------------
    | Confirm Before Running
    |--------------------------------------------------------------------------
    |
    | View request details and confirm before running the request
    |
    */

    'confirm_before_running' => true,
];
