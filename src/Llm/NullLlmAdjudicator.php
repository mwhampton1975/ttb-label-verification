<?php

class NullLlmAdjudicator implements LlmAdjudicatorInterface
{
    public function adjudicate(array $input): array
    {
        $status = $input['comparison_result']['overall_status'] ?? 'review';

        return [
            'enabled' => false,
            'provider' => 'none',
            'final_recommendation' => $status,
            'human_review_required' => $status !== 'pass',
            'review_notes' => [
                'LLM adjudication is disabled. Rule-based result returned.'
            ],
        ];
    }
}