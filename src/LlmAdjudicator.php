<?php

class LlmAdjudicator
{
    private string $apiKey;
    private string $model;

    public function __construct(?string $apiKey = null, string $model = 'gpt-5.5')
    {
        $this->apiKey = $apiKey ?? getenv('OPENAI_API_KEY');
        $this->model = $model;
    }

    public function adjudicate(array $input): array
    {
        if (!$this->apiKey) {
            return [
                'error' => 'OPENAI_API_KEY is not configured.',
                'final_recommendation' => 'review',
                'human_review_required' => true,
                'review_notes' => ['LLM adjudication skipped because API key is missing.']
            ];
        }

        $payload = [
            'model' => $this->model,
            'input' => [
                [
                    'role' => 'system',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => $this->systemPrompt()
                        ]
                    ]
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => json_encode($input, JSON_PRETTY_PRINT)
                        ]
                    ]
                ]
            ],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'label_adjudication',
                    'schema' => $this->schema(),
                    'strict' => true
                ]
            ]
        ];

        $ch = curl_init('https://api.openai.com/v1/responses');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 20,
        ]);

        $raw = curl_exec($ch);

        if ($raw === false) {
            return [
                'error' => curl_error($ch),
                'final_recommendation' => 'review',
                'human_review_required' => true,
                'review_notes' => ['LLM request failed.']
            ];
        }

        curl_close($ch);

        $response = json_decode($raw, true);

        $text = $response['output'][0]['content'][0]['text'] ?? null;

        if (!$text) {
            return [
                'error' => 'No structured text returned from LLM.',
                'raw_response' => $response,
                'final_recommendation' => 'review',
                'human_review_required' => true,
                'review_notes' => ['LLM did not return parseable adjudication.']
            ];
        }

        $decoded = json_decode($text, true);

        return $decoded ?: [
            'error' => 'Could not decode LLM JSON.',
            'raw_text' => $text,
            'final_recommendation' => 'review',
            'human_review_required' => true,
            'review_notes' => ['LLM output was not valid JSON.']
        ];
    }

    private function systemPrompt(): string
    {
        return <<<PROMPT
You are assisting with alcohol beverage label verification.

You are not the final legal authority.
Use only the provided OCR text, parser output, comparison output, and rules summary.
Do not invent text that is not present.
If OCR evidence is unclear, recommend review.
If the required government warning is not exactly confirmed, do not mark it as pass.
Partial warning evidence should be review, not pass.
Return only JSON matching the requested schema.
PROMPT;
    }

    private function schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'final_recommendation' => [
                    'type' => 'string',
                    'enum' => ['pass', 'review', 'fail']
                ],
                'confidence' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'maximum' => 100
                ],
                'summary' => ['type' => 'string'],
                'field_results' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => [
                        'brand' => ['$ref' => '#/$defs/field_result'],
                        'class_type' => ['$ref' => '#/$defs/field_result'],
                        'abv' => ['$ref' => '#/$defs/field_result'],
                        'net_contents' => ['$ref' => '#/$defs/field_result'],
                        'government_warning' => ['$ref' => '#/$defs/field_result']
                    ],
                    'required' => [
                        'brand',
                        'class_type',
                        'abv',
                        'net_contents',
                        'government_warning'
                    ]
                ],
                'human_review_required' => ['type' => 'boolean'],
                'review_notes' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ]
            ],
            'required' => [
                'final_recommendation',
                'confidence',
                'summary',
                'field_results',
                'human_review_required',
                'review_notes'
            ],
            '$defs' => [
                'field_result' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => [
                        'status' => [
                            'type' => 'string',
                            'enum' => ['pass', 'review', 'fail']
                        ],
                        'reason' => ['type' => 'string'],
                        'evidence' => ['type' => 'string']
                    ],
                    'required' => ['status', 'reason', 'evidence']
                ]
            ]
        ];
    }
}