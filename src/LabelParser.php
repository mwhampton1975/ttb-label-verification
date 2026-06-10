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

        foreach ($lines as $line) {

            $brandCandidates = [];

            foreach ($lines as $line) {

                if (
                    strlen($line) > 3 &&
                    preg_match('/[A-Z]/', $line) &&
                    !preg_match('/\d/', $line) &&
                    !str_contains($line, 'WARNING') &&
                    !str_contains($line, 'IMPORTS') &&
                    !str_contains($line, 'PRODUCED')
                ) {
                    $brandCandidates[] = $line;
                }
            }

            $result["brand"] = $brandCandidates[0] ?? null;

            // ABV
            if (preg_match('/(\d{1,2})\s*%/', $line, $m)) {
                $result["abv"] = $m[1] . "%";
            }

            // Class/type (whisky/rye/etc heuristic)
            if (preg_match('/(WHISK(E)?Y|RUM|VODKA|GIN|TEQUILA|COGNAC)/', $line, $m)) {
                $result["class"] = trim($line);
            }

            // Net contents
            if (preg_match('/\d{3}\s*ML/', $line, $m)) {
                $result["net_contents"] = $m[0];
            }

            // Warning detection
            if (strpos($line, "GOVERNMENT WARNING") !== false) {
                $result["warning_found"] = true;
            }
        }

        return $result;
    }
}