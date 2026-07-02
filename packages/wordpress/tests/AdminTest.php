<?php

declare(strict_types=1);

namespace Veribenim\WordPress\Tests;

use PHPUnit\Framework\TestCase;
use Veribenim_Admin;
use VeribenimWpTestState;

class AdminTest extends TestCase
{
    private Veribenim_Admin $admin;

    protected function setUp(): void
    {
        VeribenimWpTestState::reset();
        $this->admin = new Veribenim_Admin();
    }

    public function test_sanitize_token_accepts_valid_token(): void
    {
        $token = 'abcDEF1234567890_-abcDEF12345678';

        $this->assertSame($token, $this->admin->sanitize_token($token));
        $this->assertSame([], VeribenimWpTestState::$settingsErrors);
    }

    public function test_sanitize_token_trims_whitespace(): void
    {
        $token = str_repeat('x', 32);

        $this->assertSame($token, $this->admin->sanitize_token("  {$token}\n"));
    }

    public function test_sanitize_token_empty_clears_value(): void
    {
        $this->assertSame('', $this->admin->sanitize_token(''));
        $this->assertSame('', $this->admin->sanitize_token('   '));
    }

    public function test_sanitize_token_rejects_short_token_and_keeps_old_value(): void
    {
        VeribenimWpTestState::$options['veribenim_token'] = str_repeat('o', 32);

        $result = $this->admin->sanitize_token('kisa-token');

        $this->assertSame(str_repeat('o', 32), $result);
        $this->assertCount(1, VeribenimWpTestState::$settingsErrors);
        $this->assertSame('invalid_format', VeribenimWpTestState::$settingsErrors[0]['code']);
    }

    public function test_sanitize_token_rejects_invalid_characters(): void
    {
        VeribenimWpTestState::$options['veribenim_token'] = str_repeat('o', 32);

        $result = $this->admin->sanitize_token('token!with@invalid#chars$1234567890');

        $this->assertSame(str_repeat('o', 32), $result);
        $this->assertNotEmpty(VeribenimWpTestState::$settingsErrors);
    }

    public function test_sanitize_token_rejects_overlong_token(): void
    {
        VeribenimWpTestState::$options['veribenim_token'] = '';

        $result = $this->admin->sanitize_token(str_repeat('a', 129));

        $this->assertSame('', $result);
        $this->assertNotEmpty(VeribenimWpTestState::$settingsErrors);
    }
}
