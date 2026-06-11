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

    public function parse($text) {

        $result = [
            "brand" => null,
            "brand_confidence" => 0,
            "class" => null,
            "abv" => null,
            "net_contents" => null,
            "warning_found" => false
        ];

        $lines = array_map('trim', explode("\n", strtoupper($text)));

        // STEP 1: normalize + filter junk lines (VERY IMPORTANT)
        $cleanLines = $this->filterNoise($lines);
        $brandResult = $this->extractBrand($cleanLines);


        // STEP 2: extract structured fields
        $result["abv"] = $this->extractAbv($cleanLines);
        $result["net_contents"] = $this->extractNetContents($cleanLines);
        $result["class"] = $this->extractClass($cleanLines);
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

            if (str_word_count($line) >= 2) {
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
}