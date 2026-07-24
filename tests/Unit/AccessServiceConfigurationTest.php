<?php

namespace Tests\Unit;

use Tests\TestCase;

class AccessServiceConfigurationTest extends TestCase
{
    public function test_access_service_configuration_has_valid_types(): void
    {
        $configuration = config('services.access');

        $this->assertIsArray($configuration);
        $this->assertArrayHasKey('url', $configuration);
        $this->assertArrayHasKey('system_key', $configuration);
        $this->assertArrayHasKey('timeout', $configuration);
        $this->assertIsString($configuration['url']);
        $this->assertNotSame('', $configuration['url']);
        $this->assertTrue(
            $configuration['system_key'] === null
            || is_string($configuration['system_key'])
        );
        $this->assertIsInt($configuration['timeout']);
        $this->assertGreaterThan(0, $configuration['timeout']);
    }

    public function test_example_environment_documents_the_required_variables_without_a_secret(): void
    {
        $example = file_get_contents(base_path('.env.example'));

        $this->assertIsString($example);
        $this->assertMatchesRegularExpression(
            '/^ACCESS_API_URL=https:\/\/accesos\.digitalneza\.com$/m',
            $example
        );
        $this->assertMatchesRegularExpression('/^ACCESS_SYSTEM_KEY=$/m', $example);
        $this->assertMatchesRegularExpression('/^ACCESS_TIMEOUT=10$/m', $example);
    }
}
