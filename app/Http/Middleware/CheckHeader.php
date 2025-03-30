<?php

namespace App\Http\Middleware;

use App\Http\Responses\TheOneResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckHeader
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $valueHeader = 'FootballStadiumApp_asd123!@#';
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-cache', 'no-store');

        $checkHeader = $request->hasHeader('Football-Stadium-App');
        $newHeader = $request->header('Football-Stadium-App');

        if (!$checkHeader) {
            return response()->json(['error' => 'Header not already set'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($newHeader != $valueHeader) {
            return response()->json(['error' => 'Header not already set'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $response;
    }
}
