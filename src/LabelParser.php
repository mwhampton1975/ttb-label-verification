<?php

class LabelParser {
    private array $brandBlacklist = [
        'GOVERNMENT',
        'WARNING',
        'SURGEON',
        'GENERAL',
        'IMPORTED',
        'IMPORTS',
        'PRODUCED',
        'BOTTLED',
        'DISTILLED',
        'ALC',
        'VOL',
        'PREGNANCY',
        'MACHINERY',
        'HEALTH',
        'PROBLEMS',
        'WWW.',
        '.COM',
        'LABEL',
        'FRONT',
        'BACK'
    ];

    private array $classificationRules = [

        [
            'keywords' => ['RUM','LIQUEUR'],
            'class' => 'LIQUEUR/CORDIAL',
            'type' => 'RUM LIQUEUR'
        ],

        [
            'keywords' => ['BOURBON'],
            'class' => 'WHISKY',
            'type' => 'BOURBON WHISKY'
        ],

        [
            'keywords' => ['RYE','WHISKY'],
            'class' => 'WHISKY',
            'type' => 'RYE WHISKY'
        ],

        [
            'keywords' => ['SCOTCH'],
            'class' => 'WHISKY',
            'type' => 'SCOTCH WHISKY'
        ],

        [
            'keywords' => ['VODKA'],
            'class' => 'VODKA',
            'type' => 'VODKA'
        ],

        [
            'keywords' => ['GIN'],
            'class' => 'GIN',
            'type' => 'GIN'
        ],

        [
            'keywords' => ['TEQUILA'],
            'class' => 'TEQUILA',
            'type' => 'TEQUILA'
        ],

        [
            'keywords' => ['COGNAC'],
            'class' => 'BRANDY',
            'type' => 'COGNAC'
        ]
    ];

    public function parse($text) {

        $result = [
            "brand" => null,
            "brand_confidence" => 0,

            "class" => null,
            "type" => null,
            "classification_confidence" => 0,

            "abv" => null,
            "net_contents" => null,
            "warning_found" => false
        ];

        $lines = array_map('trim', explode("\n", strtoupper($text)));

        // STEP 1: normalize + filter junk lines 
        $cleanLines = $this->filterNoise($lines);
        $cleanLines = $this->mergeLabelLines($cleanLines);
        $brandResult = $this->extractBrand($cleanLines);


        // STEP 2: extract structured fields
        $result["abv"] = $this->extractAbv($cleanLines);
        $result["net_contents"] = $this->extractNetContents($cleanLines);
        $classResult = $this->classifyProduct($cleanLines);
        $result["class"] = $classResult["class"];
        $result["type"] = $classResult["type"];
        $result["classification_confidence"] =
            $classResult["confidence"];
        $result["warning_found"] = $this->detectWarning($cleanLines);
        $result["brand"] = $brandResult["value"];
        $result["brand_confidence"] = $brandResult["confidence"];

        return $result;
    }

    private function filterNoise($lines) {
        return array_values(array_filter($lines, function($line) {
            return !(
                $line === '' ||
                str_contains($line, '← BACK') ||
                str_contains($line, 'BACK LABEL') ||
                preg_match('/^\d+$/', $line)
            );
        }));
    }

    private function extractAbv($lines) {
        foreach ($lines as $line) {
            if (preg_match('/(\d+(?:\.\d+)?)\s*%/', $line, $m)) {
                return $m[1] . "%";
            }
        }
        return null;
    }

    private function extractNetContents($lines) {
        foreach ($lines as $line) {
            if (preg_match('/(\d+\s*ML|\d+\s*FL\s*OZ|\d+\s*PINT)/', $line, $m)) {
                return trim($m[1]);
            }
        }
        return null;
    }

    private function classifyProduct($lines) {

        $text = implode(' ', $lines);

        foreach ($this->classificationRules as $rule) {

            $matched = true;

            foreach ($rule['keywords'] as $keyword) {

                if (!str_contains($text, $keyword)) {
                    $matched = false;
                    break;
                }
            }

            if ($matched) {

                return [
                    'class' => $rule['class'],
                    'type' => $rule['type'],
                    'confidence' => 95
                ];
            }
        }

        // fallback detection

        if (str_contains($text, 'IPA')) {

            return [
                'class' => 'MALT BEVERAGE',
                'type' => 'INDIA PALE ALE',
                'confidence' => 80
            ];
        }

        if (str_contains($text, 'ALE')) {

            return [
                'class' => 'MALT BEVERAGE',
                'type' => 'ALE',
                'confidence' => 80
            ];
        }

        if (str_contains($text, 'WINE')) {

            return [
                'class' => 'WINE',
                'type' => 'TABLE WINE',
                'confidence' => 70
            ];
        }

        return [
            'class' => null,
            'type' => null,
            'confidence' => 0
        ];
    }

    private function extractClass($lines) {
        foreach ($lines as $line) {
            if (preg_match('/(WHISK(E)?Y|RUM|VODKA|GIN|TEQUILA|COGNAC|BOURBON|SCOTCH|ALE|IPA|LAGER|PILSNER|PORTER|STOUT|SAISON|CIDER|WINE|MERLOT|CABERNET|CHARDONNAY|MUSCAT)/', $line)) {
                return $line;
            }
        }
        return null;
    }

    private function detectWarning($lines) {
        foreach ($lines as $line) {
            if (strpos($line, "GOVERNMENT WARNING") !== false) {
                return true;
            }
        }
        return false;
    }

    private function extractBrand($lines) {

        $bestCandidate = null;
        $bestScore = 0;

        foreach ($lines as $index => $line) {

            $score = 0;

            if (strlen($line) < 4) {
                continue;
            }

            if (preg_match('/[A-Z]/', $line)) {
                $score += 5;
            }

            if (!preg_match('/\d/', $line)) {
                $score += 3;
            }

            if (preg_match_all('/[A-Z]+/', $line) >= 2) {
                $score += 5;
            }

            // Higher on label = more likely brand
            if ($index < 10) {
                $score += 5;
            }

            foreach ($this->brandBlacklist as $badWord) {
                if (str_contains($line, $badWord)) {
                    $score -= 10;
                }
            }

            if (preg_match('/(WHISK(E)?Y|RUM|VODKA|GIN|ALE|IPA|WINE)/', $line)) {
                $score -= 5;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestCandidate = $line;
            }
        }

        return [
            "value" => $bestCandidate,
            "confidence" => min(100, $bestScore * 4)
        ];
    }

    private function mergeLabelLines($lines) {

        $merged = [];

        for ($i = 0; $i < count($lines); $i++) {

            $current = trim($lines[$i]);

            if (
                $i + 1 < count($lines)
                && strlen($current) < 30
                && strlen(trim($lines[$i + 1])) < 30
            ) {
                $merged[] = $current . ' ' . trim($lines[$i + 1]);
                $i++;
            } else {
                $merged[] = $current;
            }
        }

        return $merged;
    }
}