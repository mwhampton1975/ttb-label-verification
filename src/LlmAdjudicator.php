<?php

class LlmAdjudicator
{
    private ?string $apiKey;
    private string $model;
    private bool $enabled;

    public function __construct(?string $apiKey = null, string $model = 'gpt-5.5')
    {
        $configPath = __DIR__ . '/../config/local.php';

        $config = file_exists($configPath)
            ? require $configPath
            : [];

        $configuredKey = $apiKey
            ?? getenv('OPENAI_API_KEY')
            ?: ($config['openai_api_key'] ?? null);

        $this->apiKey = $configuredKey ?: null;
        $this->model = $model;

        $this->enabled = !empty($config['llm_enabled']) && !empty($this->apiKey);
    }

    public function adjudicate(array $input): array
    {
        if (!$this->enabled || !$this->apiKey) {
            return [
                'enabled' => false,
                'provider' => 'openai',
                'final_recommendation' => $input['comparison_result']['overall_status'] ?? 'review',
                'human_review_required' => ($input['comparison_result']['overall_status'] ?? 'review') !== 'pass',
                'review_notes' => [
                    'LLM adjudication skipped because it is disabled or no API key is configured.'
                ],
            ];
        }

        // Actual API call goes here later.
        return [
            'enabled' => true,
            'provider' => 'openai',
            'final_recommendation' => 'review',
            'human_review_required' => true,
            'review_notes' => [
                'LLM adjudication is enabled, but the API call has not been implemented yet.'
            ],
        ];
    }
}