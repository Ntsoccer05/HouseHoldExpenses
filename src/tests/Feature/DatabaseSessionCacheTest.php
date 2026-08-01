<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Lambda(Bref)は複数の実行インスタンスが並行して立ち上がりローカルファイルを共有できないため、
 * 本番のSESSION_DRIVER/CACHE_DRIVERは file から database に変更する必要がある(移行設計書 4章リスク2)。
 * database ドライバが実際に使えるようにするための sessions/cache テーブルの存在を保証する。
 */
class DatabaseSessionCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_sessions_table_exists_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('sessions'));
        $this->assertTrue(Schema::hasColumns('sessions', [
            'id', 'user_id', 'ip_address', 'user_agent', 'payload', 'last_activity',
        ]));
    }

    public function test_cache_table_exists_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('cache'));
        $this->assertTrue(Schema::hasColumns('cache', ['key', 'value', 'expiration']));
    }

    public function test_cache_locks_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('cache_locks'));
    }
}
