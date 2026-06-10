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

            // Brand (very naive rule: contains DISTILLERY)
            if (strpos($line, "DISTILLERY") !== false) {
                $result["brand"] = $line;
            }

            // ABV
            if (preg_match('/(\d{1,2})\s*%/', $line, $m)) {
                $result["abv"] = $m[1] . "%";
            }

            // Class/type (whisky/rye/etc heuristic)
            if (strpos($line, "WHISKY") !== false || strpos($line, "WHISKEY") !== false) {
                $result["class"] = $line;
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