<?php

return [

    /*
    |--------------------------------------------------------------------------
    | V2 authentication (JWT + multi-company)
    |--------------------------------------------------------------------------
    |
    | When false, /api/v2/auth/* returns 403 and clients should use legacy V1.
    | When true, V2 login and JWT routes are active (with V1 fallback inside login).
    |
    */

    'use_v2_auth' => filter_var(env('USE_V2_AUTH', false), FILTER_VALIDATE_BOOLEAN),

];
