<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * web.php の /{any} キャッチオールは元々クロージャルートだった。
 * route:cache導入のためコントローラー化する。挙動は変えない
 * (本番ではCloudFrontのデフォルトビヘイビアがS3のSPAに直接ルーティングするため、
 * この経路自体は到達しない可能性が高いが、削除の指示は受けていないためリファクタのみ行う)。
 */
class SpaFallbackRouteTest extends TestCase
{
    public function test_unmatched_web_path_returns_index_view(): void
    {
        $response = $this->get('/some/unmatched/path');

        $response->assertStatus(200);
        $response->assertViewIs('index');
    }
}
