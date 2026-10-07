<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'tinymce-upload-image',
        'tinymce-delete-image',
        // Google GIS supplies its own double-submit g_csrf_token. Android has no browser CSRF context.
        'auth/google/callback',
    ];
}
