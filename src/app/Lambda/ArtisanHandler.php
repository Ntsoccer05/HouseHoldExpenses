<?php

namespace App\Lambda;

use Bref\Context\Context;
use Bref\Event\Handler;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * artisanコマンドをサブプロセスを使わずLaravelコンテナ経由でin-process実行するBref Handler。
 *
 * Bref 3.x の "php-82" レイヤーは /opt/bootstrap が RUNTIME_CLASS を
 * "Bref\FunctionRuntime\Main" に無条件でexportしており、ConsoleRuntimeへの切替が機能しない
 * (実機検証で確認済み)。artisanファイルを直接requireするとファイル末尾のexit()で
 * ランタイムごと終了してしまいハングするため、Handlerインターフェースを実装したクラスとして
 * 実行することでこれを回避する。
 */
class ArtisanHandler implements Handler
{
    public function __construct(private Kernel $kernel)
    {
    }

    public function handle($event, Context $context): array
    {
        $output = new BufferedOutput();
        $cli = is_array($event) ? ($event['cli'] ?? '') : '';

        try {
            $exitCode = $this->kernel->call($cli, [], $output);
        } catch (CommandNotFoundException $e) {
            return [
                'exitCode' => 1,
                'output' => $output->fetch() . $e->getMessage(),
            ];
        }

        return [
            'exitCode' => $exitCode,
            'output' => $output->fetch(),
        ];
    }
}
