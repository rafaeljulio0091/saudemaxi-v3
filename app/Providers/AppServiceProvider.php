<?php

namespace App\Providers;

use App\AI\Contracts\AssistantIntentProvider;
use App\AI\Contracts\AssistantReplyProvider;
use App\AI\Contracts\ConversationalAIProvider;
use App\AI\Contracts\DecisionAIProvider;
use App\AI\Providers\JevAssistantProvider;
use App\AI\Providers\JevProvider;
use App\AI\Providers\OpenAIAssistantProvider;
use App\AI\Providers\OpenAIProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ConversationalAIProvider::class, OpenAIProvider::class);
        $this->app->bind(DecisionAIProvider::class, JevProvider::class);
        $this->app->bind(AssistantIntentProvider::class, JevAssistantProvider::class);
        $this->app->bind(AssistantReplyProvider::class, OpenAIAssistantProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        RateLimiter::for('max-messages', fn (Request $request) => Limit::perMinute(12)
            ->by((string) ($request->user()?->id ?? $request->ip())));

        RateLimiter::for('triage-start', fn (Request $request) => Limit::perHour(5)
            ->by((string) ($request->user()?->id ?? $request->ip())));

        RateLimiter::for('triage-messages', function (Request $request) {
            $session = $request->route('triageSession');
            $sessionId = is_object($session) ? $session->getRouteKey() : $session;

            return Limit::perMinute(12)
                ->by(($request->user()?->id ?? $request->ip()).':'.$sessionId);
        });
    }
}
