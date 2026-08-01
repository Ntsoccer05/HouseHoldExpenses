<?php

namespace Tests\Unit\Requests\Auth;

use App\Http\Requests\Auth\RegisterRequest;
use PHPUnit\Framework\TestCase;

class RegisterRequestTest extends TestCase
{
    public function test_email_rule_does_not_use_spoof_check(): void
    {
        $rules = (new RegisterRequest())->rules();

        $this->assertStringNotContainsString(
            'spoof',
            $rules['email'],
            'email validation rule must not depend on the intl Spoofchecker, '.
            'which is unavailable on the Bref/Lambda runtime.'
        );
    }
}
