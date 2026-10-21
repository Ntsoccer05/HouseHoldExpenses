<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->post('/_test/state-change', fn () => 'ok');

        // CSRF ミドルウェアはテスト実行中は検証を丸ごとスキップするため、実際の検証を通すサブクラスに差し替える
        $this->app->bind(VerifyCsrfToken::class, fn ($app) => new class($app, $app['encrypter']) extends VerifyCsrfToken
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
    }

    public function test_session_uses_json_serialization(): void
    {
        $this->assertSame('json', config('session.serialization'));
    }

    public function test_cache_does_not_unserialize_arbitrary_classes(): void
    {
        $this->assertFalse(config('cache.serializable_classes'));
    }

    public function test_cross_site_request_without_csrf_token_is_rejected(): void
    {
        $response = $this->withHeaders(['Sec-Fetch-Site' => 'cross-site'])
            ->post('/_test/state-change');

        $this->assertSame(419, $response->getStatusCode());
    }

    public function test_same_origin_browser_request_is_verified_by_sec_fetch_site(): void
    {
        // Laravel 13 の PreventRequestForgery はブラウザが付与する Sec-Fetch-Site で同一オリジンを確認できればトークン不要
        $response = $this->withHeaders(['Sec-Fetch-Site' => 'same-origin'])
            ->post('/_test/state-change');

        $response->assertOk();
    }
}
