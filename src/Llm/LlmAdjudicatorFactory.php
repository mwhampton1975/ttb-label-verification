<?php

class LlmAdjudicatorFactory
{
    public static function make(): LlmAdjudicatorInterface
    {
        $configPath = __DIR__ . '/../../config/local.php';

        $config = file_exists($configPath)
            ? require $configPath
            : [];

        if (empty($config['llm_enabled'])) {
            return new NullLlmAdjudicator();
        }

        $provider = $config['llm_provider'] ?? 'none';

        return match ($provider) {
            'bedrock' => new BedrockAdjudicator($config),
            default => new NullLlmAdjudicator(),
        };
    }
}