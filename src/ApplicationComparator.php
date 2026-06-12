<?php

class ApplicationComparator
{
    public function compare(array $expected, array $parsed): array
    {
        $results = [];

        $results['brand'] = $this->compareBrand(
            $expected['brand'] ?? null,
            $parsed
        );

        $results['class_type'] = $this->compareClassType(
            $expected['class_type'] ?? null,
            $parsed
        );

        $results['abv'] = $this->compareAbv(
            $expected['abv'] ?? null,
            $parsed['abv'] ?? null
        );

        $results['net_contents'] = $this->compareNetContents(
            $expected['net_contents'] ?? null,
            $parsed['net_contents'] ?? null
        );

        $results['government_warning'] = $this->compareGovernmentWarning($parsed);

        $overall = $this->overallStatus($results);

        return [
            'overall_status' => $overall,
            'fields' => $results,
            'parsed' => $parsed,
        ];
    }

    private function compareBrand(?string $expected, array $parsed): array
    {
        if (!$expected) {
            return [
                'expected' => null,
                'found' => $parsed['brand'] ?? null,
                'status' => 'review',
                'reason' => 'Brand name was not provided in application data.'
            ];
        }

        if (!empty($parsed['expected_brand_found'])) {
            $confidence = $parsed['expected_brand_confidence'] ?? 0;
            $matchedText = $parsed['expected_brand_matched_text'] ?? null;
            $matchType = $parsed['expected_brand_match_type'] ?? 'unknown';

            if ($confidence >= 90) {
                return [
                    'expected' => $expected,
                    'found' => $matchedText,
                    'status' => 'pass',
                    'reason' => "Expected brand was found in OCR text using {$matchType} match."
                ];
            }

            return [
                'expected' => $expected,
                'found' => $matchedText,
                'status' => 'review',
                'reason' => "Expected brand may be present, but confidence is {$confidence}. Human review recommended."
            ];
        }

        // Fallback to the parser's best guessed brand if expected-brand search failed.
        return $this->compareText(
            $expected,
            $parsed['brand'] ?? null,
            'Brand name'
        );
    }

    private function compareText(?string $expected, ?string $found, string $label): array
    {
        if (!$expected) {
            return [
                'expected' => null,
                'found' => $found,
                'status' => 'review',
                'reason' => "$label was not provided in application data."
            ];
        }

        if (!$found) {
            return [
                'expected' => $expected,
                'found' => null,
                'status' => 'fail',
                'reason' => "$label was not detected on the label."
            ];
        }

        $expectedNorm = $this->normalizeText($expected);
        $foundNorm = $this->normalizeText($found);

        if ($expectedNorm === $foundNorm) {
            return [
                'expected' => $expected,
                'found' => $found,
                'status' => 'pass',
                'reason' => 'Normalized text matches.'
            ];
        }

        similar_text($expectedNorm, $foundNorm, $percent);

        if ($percent >= 90) {
            return [
                'expected' => $expected,
                'found' => $found,
                'status' => 'review',
                'reason' => 'Text is very similar but not exact. Human review recommended.'
            ];
        }

        return [
            'expected' => $expected,
            'found' => $found,
            'status' => 'fail',
            'reason' => 'Text does not match application data.'
        ];
    }

    private function compareClassType(?string $expected, array $parsed): array
    {
        return [
            'expected' => $expected,
            'found' => $parsed['designation'] ?? $parsed['class'] ?? null,
            'status' => $parsed['class_type_status'] ?? 'review',
            'reason' => $parsed['class_type_reason'] ?? 'Class/type verification completed by parser ruleset.',
        ];
    }

    private function compareAbv(?string $expected, ?string $found): array
    {
        $expectedValue = $this->extractPercent($expected);
        $foundValue = $this->extractPercent($found);

        if ($expectedValue === null) {
            return [
                'expected' => $expected,
                'found' => $found,
                'status' => 'review',
                'reason' => 'Expected ABV was not provided or could not be parsed.'
            ];
        }

        if ($foundValue === null) {
            return [
                'expected' => $expected,
                'found' => $found,
                'status' => 'fail',
                'reason' => 'ABV was not detected on the label.'
            ];
        }

        if (abs($expectedValue - $foundValue) < 0.01) {
            return [
                'expected' => $expected,
                'found' => $found,
                'status' => 'pass',
                'reason' => 'ABV value matches.'
            ];
        }

        return [
            'expected' => $expected,
            'found' => $found,
            'status' => 'fail',
            'reason' => 'ABV value does not match application data.'
        ];
    }

    private function compareNetContents(?string $expected, ?string $found): array
    {
        $expectedMl = $this->normalizeVolumeToMl($expected);
        $foundMl = $this->normalizeVolumeToMl($found);

        if ($expectedMl === null) {
            return [
                'expected' => $expected,
                'found' => $found,
                'status' => 'review',
                'reason' => 'Expected net contents were not provided or could not be parsed.'
            ];
        }

        if ($foundMl === null) {
            return [
                'expected' => $expected,
                'found' => $found,
                'status' => 'fail',
                'reason' => 'Net contents were not detected on the label.'
            ];
        }

        if (abs($expectedMl - $foundMl) < 1) {
            return [
                'expected' => $expected,
                'found' => $found,
                'status' => 'pass',
                'reason' => 'Net contents match.'
            ];
        }

        return [
            'expected' => $expected,
            'found' => $found,
            'status' => 'fail',
            'reason' => 'Net contents do not match application data.'
        ];
    }

    private function normalizeText(?string $value): string
    {
        $value = strtoupper((string) $value);
        $value = str_replace(['’', "'", '.', ',', ':', ';', '-', '–', '—'], '', $value);
        $value = preg_replace('/\bWHISKEY\b/', 'WHISKY', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }

    private function extractPercent(?string $value): ?float
    {
        if (!$value) {
            return null;
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*%/', $value, $m)) {
            return (float) $m[1];
        }

        return null;
    }

    private function normalizeVolumeToMl(?string $value): ?float
    {
        if (!$value) {
            return null;
        }

        $value = strtoupper($value);

        if (preg_match('/(\d+(?:\.\d+)?)\s*ML/', $value, $m)) {
            return (float) $m[1];
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*L\b/', $value, $m)) {
            return (float) $m[1] * 1000;
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*FL\s*OZ/', $value, $m)) {
            return (float) $m[1] * 29.5735;
        }

        return null;
    }

    private function overallStatus(array $fieldResults): string
    {
        $statuses = array_column($fieldResults, 'status');

        if (in_array('fail', $statuses, true)) {
            return 'fail';
        }

        if (in_array('review', $statuses, true)) {
            return 'review';
        }

        return 'pass';
    }

    private function compareGovernmentWarning(array $parsed): array
    {
        if (!empty($parsed['warning_exact_found'])) {
            return [
                'expected' => 'Exact required government warning',
                'found' => 'Exact warning found',
                'status' => 'pass',
                'reason' => 'The required government warning text was detected exactly after normalization.',
                'evidence' => $parsed['warning_matched_text'] ?? null,
            ];
        }

        if (!empty($parsed['warning_partial_found'])) {
            return [
                'expected' => 'Exact required government warning',
                'found' => 'Partial warning evidence found',
                'status' => 'review',
                'reason' => 'Warning-like text was detected, but the exact required warning was not confirmed by OCR.',
                'evidence' => $parsed['warning_matched_text'] ?? null,
                'fragments' => $parsed['warning_matched_fragments'] ?? [],
            ];
        }

        return [
            'expected' => 'Exact required government warning',
            'found' => 'Not found',
            'status' => 'fail',
            'reason' => 'Government warning was not detected in OCR text.',
            'evidence' => null,
        ];
    }

    private function canonicalClassTypeKey(?string $value): ?string
    {
        if (!$value || trim($value) === '') {
            return null;
        }

        $value = strtoupper($value);

        $value = str_replace('WHISKEY', 'WHISKY', $value);
        $value = str_replace('MESCAL', 'MEZCAL', $value);

        $value = str_replace(['/', '-', ','], ' ', $value);
        $value = preg_replace('/[^A-Z0-9\s]/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        $value = trim($value);

        $aliases = [
            'LIQUEUR' => 'LIQUEUR_CORDIAL',
            'CORDIAL' => 'LIQUEUR_CORDIAL',
            'LIQUEUR CORDIAL' => 'LIQUEUR_CORDIAL',

            'MEZCAL' => 'MEZCAL',
            'MESCAL' => 'MEZCAL',

            'WHISKY' => 'WHISKY',
            'WHISKEY' => 'WHISKY',

            'RUM' => 'RUM',
            'GIN' => 'GIN',
            'VODKA' => 'VODKA',
            'BRANDY' => 'BRANDY',
            'TEQUILA' => 'TEQUILA',
        ];

        return $aliases[$value] ?? str_replace(' ', '_', $value);
    }
}