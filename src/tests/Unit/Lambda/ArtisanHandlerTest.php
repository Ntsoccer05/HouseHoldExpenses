<?php

namespace Tests\Unit\Lambda;

use App\Lambda\ArtisanHandler;
use Bref\Context\Context;
use Tests\TestCase;

/**
 * Bref 3.x(layers.jsonの"php-82"レイヤー)は /opt/bootstrap が RUNTIME_CLASS を
 * "Bref\FunctionRuntime\Main" に無条件で export しており、Lambda関数側で
 * RUNTIME_CLASS=Bref\ConsoleRuntime\Main を設定しても無視される(実機で確認済み)。
 * そのため artisan コマンドは、HTTPと同様にBref標準のHandlerインターフェースを実装した
 * クラスとして、Laravelコンテナ経由でin-process実行する。
 */
class ArtisanHandlerTest extends TestCase
{
    public function test_handle_runs_artisan_command_and_returns_output(): void
    {
        $handler = app(ArtisanHandler::class);

        $result = $handler->handle(['cli' => 'route:list'], Context::fake());

        $this->assertSame(0, $result['exitCode']);
        $this->assertStringContainsString('health', $result['output']);
    }

    public function test_handle_returns_nonzero_exit_code_for_unknown_command(): void
    {
        $handler = app(ArtisanHandler::class);

        $result = $handler->handle(['cli' => 'this-command-does-not-exist'], Context::fake());

        $this->assertNotSame(0, $result['exitCode']);
    }
}
