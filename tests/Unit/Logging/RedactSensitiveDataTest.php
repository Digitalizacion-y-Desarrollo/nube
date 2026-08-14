<?php

namespace Tests\Unit\Logging;

use App\Logging\RedactSensitiveData;
use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

class RedactSensitiveDataTest extends TestCase
{
    public function test_sensitive_values_are_redacted_recursively_from_context_and_extra(): void
    {
        $record = new LogRecord(
            datetime: new DateTimeImmutable,
            channel: 'testing',
            level: Level::Info,
            message: 'Solicitud fallida',
            context: [
                'email' => 'usuario@example.test',
                'access_token' => 'token-real',
                'remote' => [
                    'authorization' => 'Bearer secreto',
                    'status' => 422,
                ],
            ],
            extra: [
                'cookie' => 'session=secreta',
                'request_id' => 'req-123',
            ],
        );

        $processed = (new RedactSensitiveData)($record);

        $this->assertSame('usuario@example.test', $processed->context['email']);
        $this->assertSame('[REDACTED]', $processed->context['access_token']);
        $this->assertSame('[REDACTED]', $processed->context['remote']['authorization']);
        $this->assertSame(422, $processed->context['remote']['status']);
        $this->assertSame('[REDACTED]', $processed->extra['cookie']);
        $this->assertSame('req-123', $processed->extra['request_id']);
    }
}
