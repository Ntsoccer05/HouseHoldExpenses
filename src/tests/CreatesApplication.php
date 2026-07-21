<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // docker-compose.yml が DB_DATABASE をコンテナの実環境変数として注入しており
        // $_ENV/$_SERVER に乗ってしまうため、putenv()ベースの上書き(.env.testing /
        // phpunit.xmlのforce="true")では効かない。ここで直接configを上書きして
        // 開発用DBと分離する（無いとRefreshDatabaseが開発用DBを消す）。
        config(['database.connections.mysql.database' => 'householdExpensesApp_testing']);

        return $app;
    }
}
