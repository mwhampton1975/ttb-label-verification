<?php

class LabelParser {

    public function parse($text) {

        $result = [
            "brand" => null,
            "class" => null,
            "abv" => null,
            "net_contents" => null,
            "warning_found" => false
        ];

        $lines = array_map('trim', explode("\n", strtoupper($text)));

        // STEP 1: normalize + filter junk lines (VERY IMPORTANT)
        $cleanLines = $this->filterNoise($lines);

        // STEP 2: extract structured fields
        $result["abv"] = $this->extractAbv($cleanLines);
        $result["net_contents"] = $this->extractNetContents($cleanLines);
        $result["class"] = $this->extractClass($cleanLines);
        $result["warning_found"] = $this->detectWarning($cleanLines);
        $result["brand"] = $this->extractBrand($cleanLines);

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
            if (preg_match('/(\d{1,2})\s*%/', $line, $m)) {
                return $m[1] . "%";
            }
        }
        return null;
    }

    private function extractNetContents($lines) {
        foreach ($lines as $line) {
            if (preg_match('/\d{3}\s*ML/', $line, $m)) {
                return $m[0];
            }
        }
        return null;
    }

    private function extractClass($lines) {
        foreach ($lines as $line) {
            if (preg_match('/(WHISK(E)?Y|RUM|VODKA|GIN|TEQUILA|COGNAC)/', $line)) {
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

        $candidates = [];

        foreach ($lines as $line) {

            if (
                strlen($line) > 3 &&
                preg_match('/[A-Z]/', $line) &&
                !preg_match('/\d/', $line) &&
                !str_contains($line, 'WARNING') &&
                !str_contains($line, 'IMPORTS') &&
                !str_contains($line, 'PRODUCED') &&
                !str_contains($line, 'GOVERNMENT')
            ) {
                $candidates[] = $line;
            }
        }

        return $candidates[0] ?? null;
    }
}