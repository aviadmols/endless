<?php

use App\Http\Middleware\EnsureAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureAdmin::class,
        ]);
        // Railway (and any other PaaS load balancer) terminates TLS in front of the app.
        $middleware->trustProxies(at: '*');
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard.index'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // An upload over post_max_size arrives with an empty body, so the CSRF token is
        // missing too. Both cases look like "nothing happened" without this.
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            return back()->withInput()->withErrors([
                'media' => 'הקבצים גדולים מדי לשליחה. העלו פחות קבצים בבת אחת, או קבצים קטנים יותר.',
            ]);
        });

        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            return back()->withInput()->withErrors([
                'form' => 'הטופס פג תוקף או שהקבצים היו גדולים מדי. רעננו את העמוד ונסו שוב.',
            ]);
        });
    })->create();
