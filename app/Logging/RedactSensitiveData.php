<?php

namespace App\Logging;

use Monolog\LogRecord;

class RedactSensitiveData
{
    private const SENSITIVE_KEY_PATTERN = '/password|passwd|token|authorization|cookie|secret|system[_-]?key|api[_-]?key/i';

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            context: $this->redact($record->context),
            extra: $this->redact($record->extra),
        );
    }

    /**
     * @param  array<mixed>  $values
     * @return array<mixed>
     */
    private function redact(array $values): array
    {
        foreach ($values as $key => $value) {
            if (is_string($key) && preg_match(self::SENSITIVE_KEY_PATTERN, $key) === 1) {
                $values[$key] = '[REDACTED]';

                continue;
            }

            if (is_array($value)) {
                $values[$key] = $this->redact($value);
            }
        }

        return $values;
    }
}
