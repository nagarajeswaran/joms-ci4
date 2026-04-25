<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

if (! function_exists('upload_url')) {
    /**
     * Generate a URL for uploaded files that always matches
     * the current request protocol (http/https).
     */
    function upload_url(string $path): string
    {
        $url = base_url('uploads/' . $path);
        // If page is HTTPS, ensure the URL is also HTTPS
        if (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
            || (! empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (! empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
        ) {
            $url = str_replace('http://', 'https://', $url);
        }
        return $url;
    }
}
