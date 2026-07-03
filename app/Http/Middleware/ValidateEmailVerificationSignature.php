<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Symfony\Component\HttpFoundation\Response;

class ValidateEmailVerificationSignature
{
    /**
     * Accept either absolute (legacy) or relative signatures.
     *
     * Relative signatures work across hosts (localhost vs .test). Absolute
     * signatures remain valid when the request host matches APP_URL at send time.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasValidSignature(absolute: false) || $request->hasValidSignature(absolute: true)) {
            return $next($request);
        }

        throw new InvalidSignatureException;
    }
}
