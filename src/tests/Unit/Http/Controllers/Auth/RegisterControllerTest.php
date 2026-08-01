<?php

namespace Tests\Unit\Http\Controllers\Auth;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

class RegisterControllerTest extends TestCase
{
    /**
     * formatRegister() は Validator::make() のルールをインラインで持っており
     * FormRequest化されていないため、ソースを直接検査する。
     * (intl拡張のSpoofcheckerに依存する ",spoof" は Bref/Lambda ランタイムに存在しないため使用禁止)
     */
    public function test_format_register_email_rule_does_not_use_spoof_check(): void
    {
        $reflection = new ReflectionClass(\App\Http\Controllers\Auth\RegisterController::class);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringNotContainsString('spoof', $source);
    }
}
