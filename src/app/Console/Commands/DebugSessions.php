<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class DebugSessions extends Command
{
    protected $signature = 'diagnostics:sessions';
    protected $description = '【一時診断用】並行リクエストで401になる問題の調査用。sessionsテーブルの状態とDB接続状況を読み取り専用でダンプする';

    public function handle(): int
    {
        $this->info('=== sessions table summary ===');
        $total = DB::table('sessions')->count();
        $this->info("total_sessions: {$total}");

        $rows = DB::table('sessions')
            ->orderByDesc('last_activity')
            ->limit(10)
            ->get(['id', 'user_id', 'last_activity', 'payload']);

        foreach ($rows as $row) {
            $hasAuthKey = false;
            $authKeySample = null;
            try {
                $decoded = @unserialize(base64_decode($row->payload));
                if (is_array($decoded)) {
                    foreach ($decoded as $key => $value) {
                        if (str_starts_with((string) $key, 'login_web_')) {
                            $hasAuthKey = true;
                            $authKeySample = $key . '=' . (is_scalar($value) ? $value : gettype($value));
                        }
                    }
                }
            } catch (\Throwable $e) {
                $authKeySample = 'decode_error: ' . $e->getMessage();
            }

            $this->line(sprintf(
                'id=%s user_id=%s last_activity=%s (%s) payload_len=%d has_login_web_key=%s %s',
                substr($row->id, 0, 12) . '...',
                $row->user_id ?? 'NULL',
                $row->last_activity,
                date('Y-m-d H:i:s', $row->last_activity),
                strlen($row->payload),
                $hasAuthKey ? 'YES' : 'no',
                $authKeySample ? "({$authKeySample})" : ''
            ));
        }

        $this->info('=== DB connection status ===');
        foreach (['Threads_connected', 'Max_used_connections', 'Aborted_connects', 'Aborted_clients', 'Connections', 'Uptime'] as $var) {
            $result = DB::select("SHOW STATUS LIKE '{$var}'");
            if (!empty($result)) {
                $this->line("{$result[0]->Variable_name}: {$result[0]->Value}");
            }
        }
        $maxConn = DB::select("SHOW VARIABLES LIKE 'max_connections'");
        if (!empty($maxConn)) {
            $this->line("max_connections: {$maxConn[0]->Value}");
        }

        $this->info('=== cookie decrypt test (実際に届いたlaravel_session Cookieの生値を直接復号してみる) ===');
        $sampleCookie = 'eyJpdiI6InViam1FU1hZcndmUDBRcUZFRlpzU2c9PSIsInZhbHVlIjoiVExodEVYRTExandGTVRtcGU2Um5LSmwybWZxODVtNWJDVGZvUWhuUFBLTm96ZEV3RWZQdjFGN09IUjdzTVBCdGJNdm1KaTNHV1dUNjh1V2JicGRSTHhiWi9pOEozRXNBODRDNzliQ0dDVmt3Ymh5cTBteWsxY3dMNEdtZS8zVVMiLCJtYWMiOiJkMDc2NDQxNDI3OWZjMzkwZjUxMjJlMTUzNmI1Mjg0YTc3YWIzODU2YzU0NDA2MmZkYjk4NzhmMWU1MWI3NWE3IiwidGFnIjoiIn0=';

        try {
            $decryptedRaw = Crypt::decrypt($sampleCookie, false);
            $this->line('serialize=false decrypt OK: ' . var_export($decryptedRaw, true));
        } catch (\Throwable $e) {
            $this->line('serialize=false decrypt FAILED: ' . get_class($e) . ': ' . $e->getMessage());
        }

        try {
            $decryptedSerialized = @unserialize(Crypt::decrypt($sampleCookie, false));
            $this->line('manual unserialize of raw decrypt: ' . var_export($decryptedSerialized, true));
        } catch (\Throwable $e) {
            $this->line('manual unserialize FAILED: ' . get_class($e) . ': ' . $e->getMessage());
        }

        $this->line('APP_KEY prefix: ' . substr(config('app.key'), 0, 15) . '...');
        $this->line('cipher: ' . config('app.cipher'));

        $this->info('=== CookieValuePrefix hash comparison ===');
        $cookieName = config('session.cookie');
        $this->line('cookieName=' . $cookieName);
        $encrypterKey = app('encrypter')->getKey();
        $this->line('encrypterKey (hex, first 16 bytes)=' . bin2hex(substr($encrypterKey, 0, 16)));
        $expectedPrefix = \Illuminate\Cookie\CookieValuePrefix::create($cookieName, $encrypterKey);
        $this->line('expected prefix=' . $expectedPrefix);
        [, $decodedRawNoPrefixStrip] = [null, Crypt::decrypt($sampleCookie, false)];
        $this->line('actual decrypted value starts with expected prefix: ' . (str_starts_with($decodedRawNoPrefixStrip, $expectedPrefix) ? 'YES' : 'NO'));
        $this->line('actual decrypted value first 41 chars=' . substr($decodedRawNoPrefixStrip, 0, 41));

        $this->info('=== session config ===');
        $this->line('SESSION_DRIVER=' . config('session.driver'));
        $this->line('SESSION_LIFETIME=' . config('session.lifetime'));
        $this->line('CACHE_DRIVER=' . config('cache.default'));

        return Command::SUCCESS;
    }
}
