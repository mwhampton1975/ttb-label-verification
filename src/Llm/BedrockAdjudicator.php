<?php

use Aws\BedrockRuntime\BedrockRuntimeClient;
use Aws\Exception\AwsException;

class BedrockAdjudicator implements LlmAdjudicatorInterface
{
    private BedrockRuntimeClient $client;
    private string $modelId;

    public function __construct(array $config = [])
    {
        $region = $config['bedrock_region'] ?? getenv('AWS_REGION') ?: 'us-east-2';

        $this->modelId = $config['bedrock_model_id']
            ?? 'us.anthropic.claude-haiku-4-5-20251001-v1:0';

        $clientConfig = [
            'version' => 'latest',
            'region' => $region,
        ];

        /*
        * If credentials are not provided, the AWS SDK will fall back to its normal
        * credential provider chain: environment variables, shared credentials,
        * IAM role, etc.
        */
        if (
            !empty($config['aws_access_key_id']) &&
            !empty($config['aws_secret_access_key'])
        ) {
            $clientConfig['credentials'] = [
                'key' => $config['aws_access_key_id'],
                'secret' => $config['aws_secret_access_key'],
            ];

            if (!empty($config['aws_session_token'])) {
                $clientConfig['credentials']['token'] = $config['aws_session_token'];
            }
        }

        $this->client = new BedrockRuntimeClient($clientConfig);
    }

    public function adjudicate(array $input): array
    {
        $prompt = $this->buildPrompt($input);

        $body = [
            'anthropic_version' => 'bedrock-2023-05-31',
            'max_tokens' => 300,
            'temperature' => 0,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $prompt,
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = $this->client->invokeModel([
                'modelId' => $this->modelId,
                'contentType' => 'application/json',
                'accept' => 'application/json',
                'body' => json_encode($body),
            ]);

            $rawBody = (string) $response['body'];
            $decoded = json_decode($rawBody, true);

            $text = $decoded['content'][0]['text'] ?? null;

            if (!$text) {
                return $this->fallbackReview('Bedrock returned no text content.', $decoded);
            }

            $json = $this->extractJson($text);

            if (!$json) {
                return $this->fallbackReview('Bedrock response did not contain valid JSON.', [
                    'raw_text' => $text,
                ]);
            }

            $result = json_decode($json, true);

            if (!is_array($result)) {
                return $this->fallbackReview('Could not decode Bedrock JSON response.', [
                    'raw_text' => $text,
                ]);
            }

            $result['enabled'] = true;
            $result['provider'] = 'bedrock';
            $result['model_id'] = $this->modelId;

            return $result;

        } catch (AwsException $e) {
            return $this->fallbackReview('Bedrock request failed: ' . $e->getAwsErrorMessage());
        } catch (Throwable $e) {
            return $this->fallbackReview('Bedrock request failed: ' . $e->getMessage());
        }
    }

    private function buildPrompt(array $input): string
    {
        $json = json_encode($input, JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
You are reviewing OCR-tolerant soft-field failures in an alcohol label verification prototype.

Use only the provided data.
Do not invent label text.
Do not re-run OCR.
Do not make legal conclusions.

You may review only these soft fields:
- brand
- producer

You may not upgrade these hard fields:
- class_type
- abv
- net_contents
- government_warning
- country

Soft-field upgrade rules:
- Upgrade a soft field to pass only when the OCR evidence is clearly the same as the expected value despite minor OCR errors.
- Treat casing, line breaks, stray punctuation, slashes, and random short noise tokens as insignificant.
- Common OCR spelling errors may be tolerated when the value remains clearly identifiable.
- Use pass only if similarity is approximately 90% or higher.
- Use review if the match is plausible but uncertain.
- Use fail if the OCR evidence does not reasonably support the expected value.

Return only compact valid JSON:
{
  "final_recommendation": "pass|review|fail",
  "soft_field_overrides": {
    "brand": {
      "override": true,
      "status": "pass|review|fail",
      "confidence": 0,
      "reason": ""
    },
    "producer": {
      "override": true,
      "status": "pass|review|fail",
      "confidence": 0,
      "reason": ""
    }
  },
  "summary": "",
  "human_review_required": true
}

Input:
$json
PROMPT;
    }

    private function extractJson(string $text): ?string
    {
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        $decoded = json_decode($text, true);

        if (is_array($decoded)) {
            return $text;
        }

        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $candidate = substr($text, $start, $end - $start + 1);

        return json_decode($candidate, true) ? $candidate : null;
    }

    private function fallbackReview(string $message, mixed $debug = null): array
    {
        $result = [
            'enabled' => true,
            'provider' => 'bedrock',
            'model_id' => $this->modelId,
            'final_recommendation' => 'review',
            'confidence' => 0,
            'summary' => 'LLM adjudication could not be completed.',
            'field_results' => [],
            'human_review_required' => true,
            'review_notes' => [$message],
        ];

        if ($debug !== null) {
            $result['debug'] = $debug;
        }

        return $result;
    }
}