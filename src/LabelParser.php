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
        'BACK',
        'NET CONTENTS',
        'CONTAINS',
        'COLOR',
        'ARTIFICIAL',
        'NATURAL FLAVOR',
    ];

    private array $classTypeRules = [
        'LIQUEUR_CORDIAL' => [
            'display' => 'LIQUEUR/CORDIAL',
            'aliases' => [
                'LIQUEUR',
                'CORDIAL',
                'LIQUEUR/CORDIAL',
                'LIQUEUR CORDIAL',
            ],
            'patterns' => [
                '/\bLIQUEUR\b/',
                '/\bCORDIAL\b/',
            ],
            'min_abv' => null,
        ],

        'MEZCAL' => [
            'display' => 'MESCAL/MEZCAL',
            'aliases' => [
                'MESCAL',
                'MEZCAL',
                'MESCAL/MEZCAL',
                'MESCAL MEZCAL',
            ],
            'patterns' => [
                '/\bMESCAL\b/',
                '/\bMEZCAL\b/',
            ],
            'min_abv' => 40,
        ],

        'RUM' => [
            'display' => 'RUM',
            'aliases' => ['RUM'],
            'patterns' => [
                '/\bRUM\b/',
            ],
            'min_abv' => 40,
        ],

        'WHISKY' => [
            'display' => 'WHISKY',
            'aliases' => [
                'WHISKY',
                'WHISKEY',
            ],
            'patterns' => [
                '/\bWHISKY\b/',
                '/\bWHISKEY\b/',
            ],
            'min_abv' => 40,
        ],

        'STRAIGHT_RYE_WHISKY' => [
            'display' => 'STRAIGHT RYE WHISKY',
            'aliases' => [
                'STRAIGHT RYE WHISKY',
                'STRAIGHT RYE WHISKEY',
            ],
            'patterns' => [
                '/\bSTRAIGHT\s+RYE\s+WHISKY\b/',
                '/\bSTRAIGHT\s+RYE\s+WHISKEY\b/',
            ],
            'min_abv' => 40,
        ],

        'GIN' => [
            'display' => 'GIN',
            'aliases' => [
                'GIN',
                'LONDON DRY GIN',
                'DISTILLED GIN',
                'REDISTILLED GIN',
                'COMPOUNDED GIN',
            ],
            'patterns' => [
                '/\bLONDON\s+DRY\s+GIN\b/',
                '/\bDISTILLED\s+GIN\b/',
                '/\bREDISTILLED\s+GIN\b/',
                '/\bCOMPOUNDED\s+GIN\b/',
                '/\bGIN\b/',
            ],
            'min_abv' => 40,
        ],

        'BEER' => [
            'display' => 'BEER',
            'aliases' => [
                'BEER',
                'MALT BEVERAGE',
                'MALT BEVERAGES',
            ],
            'patterns' => [
                '/\bBEER\b/',
                '/\bMALT\s+BEVERAGE\b/',
                '/\bMALT\s+BEVERAGES\b/',
            ],
            'compatible_with' => [
                'ALE',
                'LAGER',
                'STOUT',
                'PORTER',
                'IPA',
                'INDIA_PALE_ALE',
                'PILSNER',
                'WHEAT_BEER',
                'WHEAT_ALE',
                'PALE_ALE',
                'BROWN_ALE',
                'HONEY_ALE',
                'RASPBERRY_ALE',
                'RUSSIAN_IMPERIAL_STOUT',
            ],
            'min_abv' => null,
        ],

        'ALE' => [
            'display' => 'ALE',
            'aliases' => [
                'ALE',
            ],
            'patterns' => [
                '/\bALE\b/',
            ],
            'compatible_with' => [
                'IPA',
                'INDIA_PALE_ALE',
                'PALE_ALE',
                'BROWN_ALE',
                'HONEY_ALE',
                'RASPBERRY_ALE',
            ],
            'min_abv' => null,
        ],

        'IPA' => [
            'display' => 'INDIA PALE ALE',
            'aliases' => [
                'IPA',
                'I P A',
                'I.P.A.',
                'INDIA PALE ALE',
            ],
            'patterns' => [
                '/\bI\s*P\s*A\b/',
                '/\bINDIA\s+PALE\s+ALE\b/',
            ],
            'min_abv' => null,
        ],

        'PALE_ALE' => [
            'display' => 'PALE ALE',
            'aliases' => [
                'PALE ALE',
            ],
            'patterns' => [
                '/\bPALE\s+ALE\b/',
            ],
            'compatible_with' => [
                'ALE',
                'IPA',
                'INDIA_PALE_ALE',
            ],
            'min_abv' => null,
        ],

        'LAGER' => [
            'display' => 'LAGER',
            'aliases' => [
                'LAGER',
            ],
            'patterns' => [
                '/\bLAGER\b/',
            ],
            'compatible_with' => [
                'BEER',
                'PILSNER',
            ],
            'min_abv' => null,
        ],

        'PILSNER' => [
            'display' => 'PILSNER',
            'aliases' => [
                'PILSNER',
                'PILSENER',
            ],
            'patterns' => [
                '/\bPILSNER\b/',
                '/\bPILSENER\b/',
            ],
            'compatible_with' => [
                'BEER',
                'LAGER',
            ],
            'min_abv' => null,
        ],

        'STOUT' => [
            'display' => 'STOUT',
            'aliases' => [
                'STOUT',
            ],
            'patterns' => [
                '/\bSTOUT\b/',
            ],
            'compatible_with' => [
                'BEER',
                'RUSSIAN_IMPERIAL_STOUT',
            ],
            'min_abv' => null,
        ],

        'RUSSIAN_IMPERIAL_STOUT' => [
            'display' => 'RUSSIAN IMPERIAL STOUT',
            'aliases' => [
                'RUSSIAN IMPERIAL STOUT',
                'IMPERIAL STOUT',
            ],
            'patterns' => [
                '/\bRUSSIAN\s+IMPERIAL\s+STOUT\b/',
                '/\bIMPERIAL\s+STOUT\b/',
            ],
            'compatible_with' => [
                'STOUT',
                'BEER',
            ],
            'min_abv' => null,
        ],

        'PORTER' => [
            'display' => 'PORTER',
            'aliases' => [
                'PORTER',
            ],
            'patterns' => [
                '/\bPORTER\b/',
            ],
            'compatible_with' => [
                'BEER',
            ],
            'min_abv' => null,
        ],

        'WHEAT_BEER' => [
            'display' => 'WHEAT BEER',
            'aliases' => [
                'WHEAT BEER',
            ],
            'patterns' => [
                '/\bWHEAT\s+BEER\b/',
            ],
            'compatible_with' => [
                'BEER',
                'WHEAT_ALE',
            ],
            'min_abv' => null,
        ],

        'WHEAT_ALE' => [
            'display' => 'WHEAT ALE',
            'aliases' => [
                'WHEAT ALE',
            ],
            'patterns' => [
                '/\bWHEAT\s+ALE\b/',
            ],
            'compatible_with' => [
                'ALE',
                'WHEAT_BEER',
            ],
            'min_abv' => null,
        ],

        'HONEY_ALE' => [
            'display' => 'HONEY ALE',
            'aliases' => [
                'HONEY ALE',
            ],
            'patterns' => [
                '/\bHONEY\s+ALE\b/',
                '/\bALE\s+WITH\s+HONEY\b/',
            ],
            'compatible_with' => [
                'ALE',
            ],
            'min_abv' => null,
        ],

        'RASPBERRY_ALE' => [
            'display' => 'RASPBERRY ALE',
            'aliases' => [
                'RASPBERRY ALE',
            ],
            'patterns' => [
                '/\bRASPBERRY\s+ALE\b/',
                '/\bALE\s+WITH\s+RASPBERRY\b/',
            ],
            'compatible_with' => [
                'ALE',
            ],
            'min_abv' => null,
        ],

        'WINE' => [
            'display' => 'WINE',
            'aliases' => [
                'WINE',
                'GRAPE WINE',
            ],
            'patterns' => [
                '/\bGRAPE\s+WINE\b/',
                '/\bWINE\b/',
            ],
            'min_abv' => null,
        ],

        'TABLE_WINE' => [
            'display' => 'TABLE WINE',
            'aliases' => [
                'TABLE WINE',
                'LIGHT WINE',
            ],
            'patterns' => [
                '/\bTABLE\s+WINE\b/',
                '/\bLIGHT\s+WINE\b/',
            ],
            'min_abv' => 7,
        ],

        'DESSERT_WINE' => [
            'display' => 'DESSERT WINE',
            'aliases' => [
                'DESSERT WINE',
            ],
            'patterns' => [
                '/\bDESSERT\s+WINE\b/',
            ],
            'min_abv' => 14,
        ],

        'RED_WINE' => [
            'display' => 'RED WINE',
            'aliases' => [
                'RED WINE',
            ],
            'patterns' => [
                '/\bRED\s+WINE\b/',
            ],
            'min_abv' => null,
        ],

        'WHITE_WINE' => [
            'display' => 'WHITE WINE',
            'aliases' => [
                'WHITE WINE',
            ],
            'patterns' => [
                '/\bWHITE\s+WINE\b/',
            ],
            'min_abv' => null,
        ],

        'ROSE_WINE' => [
            'display' => 'ROSE WINE',
            'aliases' => [
                'ROSE WINE',
                'ROSÉ WINE',
                'ROSE',
                'ROSÉ',
            ],
            'patterns' => [
                '/\bROSE\s+WINE\b/',
                '/\bROSE\b/',
            ],
            'min_abv' => null,
        ],

        'PINK_WINE' => [
            'display' => 'PINK WINE',
            'aliases' => [
                'PINK WINE',
            ],
            'patterns' => [
                '/\bPINK\s+WINE\b/',
            ],
            'min_abv' => null,
        ],

        'AMBER_WINE' => [
            'display' => 'AMBER WINE',
            'aliases' => [
                'AMBER WINE',
            ],
            'patterns' => [
                '/\bAMBER\s+WINE\b/',
            ],
            'min_abv' => null,
        ],

        'SPARKLING_WINE' => [
            'display' => 'SPARKLING WINE',
            'aliases' => [
                'SPARKLING WINE',
                'CHAMPAGNE',
                'CRACKLING WINE',
                'PETILLANT WINE',
                'FRIZZANTE WINE',
                'CREMANT WINE',
                'PERLANT WINE',
            ],
            'patterns' => [
                '/\bSPARKLING\s+WINE\b/',
                '/\bCHAMPAGNE\b/',
                '/\bCRACKLING\s+WINE\b/',
                '/\bPETILLANT\s+WINE\b/',
                '/\bFRIZZANTE\s+WINE\b/',
                '/\bCREMANT\s+WINE\b/',
                '/\bPERLANT\s+WINE\b/',
            ],
            'min_abv' => null,
        ],

        'MUSCAT_MOSCATO' => [
            'display' => 'MUSCAT/MOSCATO',
            'aliases' => [
                'MUSCAT',
                'MOSCATO',
                'MUSCAT/MOSCATO',
                'MUSCAT MOSCATO',
            ],
            'patterns' => [
                '/\bMUSCAT\b/',
                '/\bMOSCATO\b/',
            ],
            'min_abv' => null,
        ],

        'MUSCATEL' => [
            'display' => 'MUSCATEL',
            'aliases' => [
                'MUSCATEL',
                'LIGHT MUSCATEL',
            ],
            'patterns' => [
                '/\bMUSCATEL\b/',
                '/\bLIGHT\s+MUSCATEL\b/',
            ],
            'min_abv' => null,
        ],

        'CHARDONNAY' => [
            'display' => 'CHARDONNAY',
            'aliases' => [
                'CHARDONNAY',
            ],
            'patterns' => [
                '/\bCHARDONNAY\b/',
            ],
            'min_abv' => null,
        ],

        'CABERNET_SAUVIGNON' => [
            'display' => 'CABERNET SAUVIGNON',
            'aliases' => [
                'CABERNET SAUVIGNON',
                'CABERNET',
            ],
            'patterns' => [
                '/\bCABERNET\s+SAUVIGNON\b/',
                '/\bCABERNET\b/',
            ],
            'min_abv' => null,
        ],

        'PINOT_NOIR' => [
            'display' => 'PINOT NOIR',
            'aliases' => [
                'PINOT NOIR',
            ],
            'patterns' => [
                '/\bPINOT\s+NOIR\b/',
            ],
            'min_abv' => null,
        ],

        'MERLOT' => [
            'display' => 'MERLOT',
            'aliases' => [
                'MERLOT',
            ],
            'patterns' => [
                '/\bMERLOT\b/',
            ],
            'min_abv' => null,
        ],

        'SAUVIGNON_BLANC' => [
            'display' => 'SAUVIGNON BLANC',
            'aliases' => [
                'SAUVIGNON BLANC',
            ],
            'patterns' => [
                '/\bSAUVIGNON\s+BLANC\b/',
            ],
            'min_abv' => null,
        ],

        'RIESLING' => [
            'display' => 'RIESLING',
            'aliases' => [
                'RIESLING',
            ],
            'patterns' => [
                '/\bRIESLING\b/',
            ],
            'min_abv' => null,
        ],

        'PINOT_GRIGIO' => [
            'display' => 'PINOT GRIGIO',
            'aliases' => [
                'PINOT GRIGIO',
                'PINOT GRIS',
            ],
            'patterns' => [
                '/\bPINOT\s+GRIGIO\b/',
                '/\bPINOT\s+GRIS\b/',
            ],
            'min_abv' => null,
        ],

        'ZINFANDEL' => [
            'display' => 'ZINFANDEL',
            'aliases' => [
                'ZINFANDEL',
            ],
            'patterns' => [
                '/\bZINFANDEL\b/',
            ],
            'min_abv' => null,
        ],
    ];

    public function parse($text, array $expected = []) {

        $result = [
            "brand" => null,
            "brand_confidence" => 0,

            "expected_brand_found" => null,
            "expected_brand_confidence" => 0,
            "expected_brand_match_type" => null,
            "expected_brand_matched_text" => null,

            "class" => null,
            "type" => null,
            "designation" => null,
            "matched_text" => null,
            "classification_confidence" => 0,
            "class_type_status" => "review",
            "class_type_reason" => null,
            "class_type_source" => null,
            "expected_class_type_rule_key" => null,
            "expected_class_type_display" => null,

            "needs_review" => false,

            "flags" => [],

            "abv" => null,
            "net_contents" => null,
            "warning_found" => false,
            "warning_exact_found" => false,
            "warning_partial_found" => false,
            "warning_status" => "fail",
            "warning_confidence" => 0,
            "warning_matched_fragments" => [],
            "warning_matched_text" => null,
            "warning_debug" => null,

            "producer_status" => "review",
            "producer_expected" => null,
            "producer_found" => null,
            "producer_confidence" => 0,
            "producer_reason" => null,
            "producer_debug_window" =>null,

            "country_status" => "review",
            "country_expected" => null,
            "country_found" => null,
            "country_confidence" => 0,
            "country_reason" => null,

            "status" => "review"
        ];

        $lines = array_map('trim', explode("\n", strtoupper((string) $text)));
        $brandSearchLines = array_values(array_filter($lines, function ($line) {
            return trim($line) !== '';
        }));

        // STEP 1: normalize + filter junk lines
        $cleanLines = $this->filterNoise($lines);
        $cleanLines = array_map([$this, 'normalizeOcrLine'], $cleanLines);
        $cleanLines = $this->mergeLabelLines($cleanLines);

        $expectedBrand = $expected['brand'] ?? null;
        $expectedBrandResult = $this->findExpectedBrand(
            $expected['brand'] ?? null,
            $brandSearchLines
        );

        if (!empty($expectedBrand)) {
            $brandResult = [
                'value' => $expectedBrandResult['found']
                    ? $expectedBrandResult['matched_text']
                    : null,
                'confidence' => $expectedBrandResult['confidence'],
            ];
        } else {
            $brandResult = $this->extractBrand($cleanLines);
        }
        $result["expected_brand_found"] = $expectedBrandResult["found"];
        $result["expected_brand_confidence"] = $expectedBrandResult["confidence"];
        $result["expected_brand_match_type"] = $expectedBrandResult["match_type"];
        $result["expected_brand_matched_text"] = $expectedBrandResult["matched_text"];

        $countrySearchLines = array_values(array_filter($lines, function ($line) {
            return trim($line) !== '';
        }));
        $countryResult = $this->verifyCountryOfOrigin(
            $expected['country'] ?? null,
            $countrySearchLines
        );

        $result["country_status"] = $countryResult["status"];
        $result["country_expected"] = $countryResult["expected"];
        $result["country_found"] = $countryResult["found"];
        $result["country_confidence"] = $countryResult["confidence"];
        $result["country_reason"] = $countryResult["reason"];

        if (!empty($countryResult["flag"])) {
            $result["flags"][] = $countryResult["flag"];
        }

        $producerResult = $this->verifyProducerAddress(
            $expected['producer'] ?? null,
            $brandSearchLines
        );

        $result["producer_status"] = $producerResult["status"];
        $result["producer_expected"] = $producerResult["expected"];
        $result["producer_found"] = $producerResult["found"];
        $result["producer_confidence"] = $producerResult["confidence"];
        $result["producer_reason"] = $producerResult["reason"];
        $result["producer_debug_window"] = $producerResult["debug_window"] ?? null;

        if (!empty($producerResult["flag"])) {
            $result["flags"][] = $producerResult["flag"];
        }

        // STEP 2: extract structured fields
        $result["abv"] = $this->extractAbv($cleanLines);
        $result["net_contents"] = $this->extractNetContents($cleanLines);

        $classTypeSearchLines = array_values(array_filter($lines, function ($line) {
            return trim($line) !== '';
        }));
        $classResult = $this->verifyExpectedClassType(
            $expected['class_type'],
            $classTypeSearchLines,
            $result['abv']
        );

        $result["class"] = $classResult["class"] ?? null;
        $result["type"] = $classResult["type"] ?? null;
        $result["designation"] = $classResult["designation"] ?? $classResult["type"] ?? $classResult["class"] ?? null;
        $result["matched_text"] = $classResult["matched_text"] ?? null;
        $result["classification_confidence"] = $classResult["confidence"] ?? 0;
        $result["needs_review"] = $classResult["needs_review"] ?? false;
        if (!empty($classResult["flags"])) {
            $result["flags"] = array_merge($result["flags"], $classResult["flags"]);
        }

        $result["class_type_status"] = $classResult["status"] ?? "review";
        $result["class_type_reason"] = $classResult["reason"] ?? null;
        $result["class_type_source"] = $classResult["classification_source"] ?? null;

        $result["expected_class_type_rule_key"] = $classResult["expected_rule_key"] ?? null;
        $result["expected_class_type_display"] = $classResult["expected_rule_display"] ?? null;

        $warningLines = array_values(array_filter($lines, function ($line) {
            return trim($line) !== '';
        }));
        $warningResult = $this->detectWarningDetailed($warningLines);
        $result["warning_found"] = $warningResult["found"];
        $result["warning_exact_found"] = $warningResult["exact_found"];
        $result["warning_partial_found"] = $warningResult["partial_found"];
        $result["warning_status"] = $warningResult["status"];
        $result["warning_confidence"] = $warningResult["confidence"];
        $result["warning_matched_fragments"] = $warningResult["matched_fragments"];
        $result["warning_matched_text"] = $warningResult["matched_text"];
        $result["warning_debug"] = $warningResult["debug_warning"] ?? null;
        if (!empty($warningResult["flag"])) {
            $result["flags"][] = $warningResult["flag"];
        }

        $result["brand"] = $brandResult["value"];
        $result["brand_confidence"] = $brandResult["confidence"];

        $result["flags"] = $this->evaluateRegulatoryFlags($result);
        $result["status"] = $this->evaluateStatus($result);

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

    private function normalizeOcrLine(string $line): string {
        $line = strtoupper($line);

        $replacements = [
            'WHISKEY' => 'WHISKY',
            'WH1SKY' => 'WHISKY',
            'WHlSKY' => 'WHISKY',
            'V0DKA' => 'VODKA',
            'B0URBON' => 'BOURBON',
            'TEQU1LA' => 'TEQUILA',
            'L1QUEUR' => 'LIQUEUR',
            'CORD1AL' => 'CORDIAL',
            'FLAV0RED' => 'FLAVORED',
            'FLAVOURED' => 'FLAVORED',
            'FLAVOUR' => 'FLAVOR',
            'FLAVOURS' => 'FLAVORS',
            'MESCAL' => 'MEZCAL',
            'CURAÇAO' => 'CURACAO',
            'CRÈME' => 'CREME',
        ];

        $line = strtr($line, $replacements);

        // Keep letters, numbers, percent, slash, dash and period.
        $line = preg_replace('/[^A-Z0-9%\.\/\-\s]/', ' ', $line);
        $line = preg_replace('/\s+/', ' ', $line);

        return trim($line);
    }

    private function extractAbv(array $lines): ?string
    {
        $text = strtoupper(implode(' ', $lines));
        $text = preg_replace('/\s+/', ' ', $text);

        /*
        * Wine/common format:
        * ALC. 13.5% BY VOL.
        * ALCOHOL 13.5% BY VOLUME
        */
        if (preg_match('/\b(?:ALC|ALCOHOL)\.?\s*(\d+(?:\.\d+)?)\s*%\s*BY\s*(?:VOL|VOLUME)\.?/', $text, $m)) {
            return $m[1] . '%';
        }

        /*
        * Standard format:
        * 40% ALC/VOL
        * 40 % ALC. / VOL.
        */
        if (preg_match('/(\d+(?:\.\d+)?)\s*%\s*(?:ALC|ALCOHOL)\.?\s*\/?\s*(?:VOL|VOLUME)\.?/', $text, $m)) {
            return $m[1] . '%';
        }

        /*
        * Standard text format:
        * 13.5% ALC BY VOL
        * 13.5% ALCOHOL BY VOLUME
        */
        if (preg_match('/(\d+(?:\.\d+)?)\s*%\s*(?:ALC|ALCOHOL)\.?\s*BY\s*(?:VOL|VOLUME)\.?/', $text, $m)) {
            return $m[1] . '%';
        }

        /*
        * Reverse slash format:
        * ALC/VOL 40%
        * ALCOHOL/VOLUME 40%
        */
        if (preg_match('/(?:ALC|ALCOHOL)\.?\s*\/?\s*(?:VOL|VOLUME)\.?\s*(\d+(?:\.\d+)?)\s*%/', $text, $m)) {
            return $m[1] . '%';
        }

        /*
        * ABV format:
        * 40% ABV
        */
        if (preg_match('/(\d+(?:\.\d+)?)\s*%\s*ABV\b/', $text, $m)) {
            return $m[1] . '%';
        }

        /*
        * Reverse ABV format:
        * ABV 40%
        */
        if (preg_match('/\bABV\s*(\d+(?:\.\d+)?)\s*%/', $text, $m)) {
            return $m[1] . '%';
        }

        /*
        * Proof pattern:
        * 80 PROOF = 40%
        */
        if (preg_match('/(\d+(?:\.\d+)?)\s*PROOF\b/', $text, $m)) {
            $abv = ((float)$m[1]) / 2;
            return rtrim(rtrim((string)$abv, '0'), '.') . '%';
        }

        return null;
    }

    private function extractNetContents($lines) {
        foreach ($lines as $line) {
            if (preg_match('/\b(\d+(?:\.\d+)?)\s*(ML|M L|L|LITER|LITRE|LITERS|LITRES|FL\s*OZ|PINT|PINTS)\b/', $line, $m)) {
                return trim($m[1] . ' ' . str_replace(' ', '', $m[2]));
            }
        }

        return null;
    }

    private function findProducerEvidenceWindow(array $lines, ?string $expectedProducer = null): ?string
    {
        $lines = array_values(array_filter($lines, function ($line) {
            return trim((string)$line) !== '';
        }));

        if (empty($lines)) {
            return null;
        }

        $expectedNorm = $this->normalizeProducerText((string)$expectedProducer);
        $expectedTokens = $this->importantProducerTokens($expectedNorm);

        $bestWindow = null;
        $bestScore = 0;

        $count = count($lines);

        for ($start = 0; $start < $count; $start++) {
            for ($length = 2; $length <= 6; $length++) {
                if ($start + $length > $count) {
                    continue;
                }

                $windowLines = array_slice($lines, $start, $length);
                $windowText = implode(' ', $windowLines);
                $windowNorm = $this->normalizeProducerText($windowText);

                $score = $this->scoreProducerEvidenceWindow(
                    $windowNorm,
                    $expectedTokens
                );

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestWindow = implode("\n", $windowLines);
                }
            }
        }

        /*
        * If token scoring found something useful, return it.
        */
        if ($bestScore >= 25 && $bestWindow !== null) {
            return $bestWindow;
        }

        /*
        * Fallback: look for producer-related label language.
        */
        $keywords = [
            'PRODUCED',
            'BOTTLED',
            'DISTILLED',
            'BREWED',
            'VINTNER',
            'VINTNERS',
            'WINERY',
            'BREWERY',
            'DISTILLERY',
            'CELLARS',
            'ESTATE',
        ];

        for ($i = 0; $i < $count; $i++) {
            $lineNorm = $this->normalizeProducerText($lines[$i]);

            foreach ($keywords as $keyword) {
                if (str_contains($lineNorm, $keyword)) {
                    $start = max(0, $i - 1);
                    $length = min($count - $start, 6);

                    return implode("\n", array_slice($lines, $start, $length));
                }
            }
        }

        return null;
    }

    private function scoreProducerEvidenceWindow(string $windowNorm, array $expectedTokens): int
    {
        if (empty($expectedTokens)) {
            return 0;
        }

        $score = 0;
        $windowTokens = preg_split('/\s+/', $windowNorm);

        foreach ($expectedTokens as $expectedToken) {
            if (str_contains($windowNorm, $expectedToken)) {
                $score += 30;
                continue;
            }

            foreach ($windowTokens as $windowToken) {
                if (strlen($expectedToken) < 4 || strlen($windowToken) < 4) {
                    continue;
                }

                /*
                * Allows OCR errors like:
                * KINGSTON -> KINCSTON
                * VINTNERS -> VINTNERS
                */
                similar_text($expectedToken, $windowToken, $percent);

                if ($percent >= 82) {
                    $score += 18;
                    break;
                }
            }
        }

        /*
        * Give a small boost for producer-context words.
        */
        $contextWords = [
            'PRODUCED',
            'BOTTLED',
            'VINTNER',
            'VINTNERS',
            'WINERY',
            'BREWERY',
            'DISTILLERY',
            'CELLARS',
        ];

        foreach ($contextWords as $word) {
            if (str_contains($windowNorm, $word)) {
                $score += 8;
            }
        }

        return $score;
    }

    private function detectWarningDetailed(array $lines): array
    {
        $text = implode(' ', $lines);
        $normalizedText = $this->normalizeWarningText($text);

        $exactWarningResult = $this->detectExactGovernmentWarning($normalizedText);

        $resultDebug = [
            'normalized_warning_text' => $normalizedText,
            'exact_warning_found' => $exactWarningResult['found'],
            'exact_warning_matched_text' => $exactWarningResult['matched_text'],
        ];

        if ($exactWarningResult['found']) {
            return [
                'found' => true,
                'exact_found' => true,
                'partial_found' => false,
                'status' => 'pass',
                'confidence' => 100,
                'matched_fragments' => ['FULL_WARNING_EXACT_MATCH'],
                'matched_text' => $exactWarningResult['matched_text'],
                'debug_warning' => $resultDebug,
                'flag' => null,
            ];
        }

        $fragments = [];

        if (preg_match('/GOVERNMENT\s+WARNING/', $normalizedText)) {
            $fragments[] = 'GOVERNMENT WARNING';
        }

        if (preg_match('/SURGEON\s+GENERAL/', $normalizedText)) {
            $fragments[] = 'SURGEON GENERAL';
        }

        if (preg_match('/WOMEN|PREGNANCY|BIRTH|DEFECTS?/', $normalizedText)) {
            $fragments[] = 'PREGNANCY/BIRTH DEFECT LANGUAGE';
        }

        if (preg_match('/ALCOHOLIC\s+BEVERAGES|ALCOHOL|BEVERAGES/', $normalizedText)) {
            $fragments[] = 'ALCOHOLIC BEVERAGES LANGUAGE';
        }

        if (preg_match('/DRIVE|CAR|OPERATE|MACHINERY|MACH/', $normalizedText)) {
            $fragments[] = 'DRIVING/MACHINERY LANGUAGE';
        }

        if (preg_match('/HEALTH\s+PROBLEMS|PROBLEMS/', $normalizedText)) {
            $fragments[] = 'HEALTH PROBLEMS LANGUAGE';
        }

        $partialFound = count($fragments) > 0;

        return [
            'found' => false,
            'exact_found' => false,
            'partial_found' => $partialFound,
            'status' => $partialFound ? 'review' : 'fail',
            'confidence' => $partialFound ? 50 : 0,
            'matched_fragments' => $fragments,
            'matched_text' => $partialFound ? $this->extractWarningWindow($lines) : null,
            'debug_warning' => $resultDebug,
            'flag' => $partialFound
                ? 'WARNING_PARTIAL_OR_UNREADABLE'
                : 'WARNING_NOT_FOUND',
        ];
    }

    private function normalizeWarningText(string $text): string
    {
        $text = strtoupper($text);

        $replacements = [
            "GOV'T" => 'GOVERNMENT',
            'GOVT' => 'GOVERNMENT',
            'G0VERNMENT' => 'GOVERNMENT',
            'GOVERNMNT' => 'GOVERNMENT',

            'WARN1NG' => 'WARNING',
            'SURGE0N' => 'SURGEON',
            'W0MEN' => 'WOMEN',
            'ALC0HOL' => 'ALCOHOL',
            'ALCOH0L' => 'ALCOHOL',
            'B1RTH' => 'BIRTH',
            'DEFECT5' => 'DEFECTS',
            'DR1VE' => 'DRIVE',
            'MACH1NERY' => 'MACHINERY',
        ];

        $text = strtr($text, $replacements);

        // Keep letters, numbers, spaces, parentheses, colon, comma, and period.
        $text = preg_replace('/[^A-Z0-9\s\(\)\:\,\.]/', ' ', $text);

        // Normalize spaces around punctuation.
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/\s+([:,.])/', '$1', $text);
        $text = preg_replace('/([:,.])(?=[A-Z0-9(])/', '$1 ', $text);

        // Normalize clause markers like "( 1 )" to "(1)".
        $text = preg_replace('/\(\s*1\s*\)/', '(1)', $text);
        $text = preg_replace('/\(\s*2\s*\)/', '(2)', $text);

        return trim($text);
    }

    private function extractWarningWindow(array $lines): ?string
    {
        $warningLines = [];

        foreach ($lines as $line) {
            $normalized = $this->normalizeWarningText($line);

            if (preg_match('/GOVERNMENT|WARNING|SURGEON|GENERAL|PREGNAN|WOMEN|BIRTH|DEFECT|ALCOHOL|BEVERAGES|DRIVE|CAR|OPERATE|MACH|HEALTH|PROBLEMS/', $normalized)) {
                $warningLines[] = $line;
            }
        }

        if (empty($warningLines)) {
            return null;
        }

        return trim(implode(' ', $warningLines));
    }

    private function detectExactGovernmentWarning(string $normalizedText): array
    {
        /*
        * Accepts either punctuation-preserved OCR:
        * GOVERNMENT WARNING: (1) ...
        *
        * Or punctuation-stripped OCR:
        * GOVERNMENT WARNING 1 ...
        *
        * Still requires the warning language in the correct order.
        * Allows flexible whitespace and allows OCR to read "machinery, and" as
        * either "machinery, and" or "machinery. and".
        * Does not care if OCR junk appears after "health problems".
        */
        $pattern = '/
            GOVERNMENT\s+WARNING\s*:?\s*
            \(?\s*1\s*\)?\s*
            ACCORDING\s+TO\s+THE\s+SURGEON\s+GENERAL\s*,?\s*
            WOMEN\s+SHOULD\s+NOT\s+DRINK\s+ALCOHOLIC\s+BEVERAGES\s+
            DURING\s+PREGNANCY\s+BECAUSE\s+OF\s+THE\s+RISK\s+OF\s+
            BIRTH\s+DEFECTS\s*\.?\s*
            \(?\s*2\s*\)?\s*
            CONSUMPTION\s+OF\s+ALCOHOLIC\s+BEVERAGES\s+
            IMPAIRS\s+YOUR\s+ABILITY\s+TO\s+DRIVE\s+A\s+CAR\s+OR\s+
            OPERATE\s+MACHINERY\s*[,\.]?\s*
            AND\s+MAY\s+CAUSE\s+HEALTH\s+PROBLEMS\s*\.?
        /x';

        if (preg_match($pattern, $normalizedText, $m)) {
            return [
                'found' => true,
                'matched_text' => trim($m[0]),
            ];
        }

        return [
            'found' => false,
            'matched_text' => null,
        ];
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

            // Fixed bug from original:
            // preg_match_all() needs a match array argument.
            if (preg_match_all('/[A-Z]+/', $line, $matches) >= 2) {
                $score += 5;
            }

            // Higher on label = more likely brand.
            if ($index < 10) {
                $score += 5;
            }

            foreach ($this->brandBlacklist as $badWord) {
                if (str_contains($line, $badWord)) {
                    $score -= 10;
                }
            }

            if (preg_match('/(WHISKY|RUM|VODKA|GIN|TEQUILA|MEZCAL|ALE|IPA|WINE|LIQUEUR|CORDIAL|BRANDY|COGNAC)/', $line)) {
                $score -= 5;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestCandidate = $line;
            }
        }

        return [
            "value" => $bestCandidate,
            "confidence" => min(100, max(0, $bestScore * 4)),
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

    private function evaluateStatus(array $result): string
    {
        $flags = $result['flags'] ?? [];

        //
        // HARD FAILS
        //

        if (($result['class_type_status'] ?? null) === 'fail') {
            return 'fail';
        }

        if (($result['class_type_status'] ?? null) === 'review') {
            $flags[] = 'Class/type designation was not confirmed by OCR.';
        }

        //
        // REVIEW CONDITIONS
        //

        if (empty($result['abv'])) {
            $flags[] = 'Missing alcohol content.';
        }

        if (empty($result['brand'])) {
            $flags[] = 'Missing brand.';
        }

        if (!empty($result['needs_review'])) {
            $flags[] = 'Classification requires review.';
        }

        if (($result['classification_confidence'] ?? 0) < 85) {
            $flags[] = 'Classification confidence below 85.';
        }

        if (($result['warning_status'] ?? null) === 'fail') {
            return 'fail';
        }

        if (($result['warning_status'] ?? null) === 'review') {
            $flags[] = 'Government warning appears partially present but exact required text was not confirmed.';
        }

        //
        //  compliance checks
        //

        if (
            in_array(
                'LOW_BRAND_CONFIDENCE',
                $flags
            )
        ) {
            $flags[] =
                'Brand extraction confidence is low.';
        }

        if (
            in_array(
                'LOW_CLASSIFICATION_CONFIDENCE',
                $flags
            )
        ) {
            $flags[] =
                'Classification confidence is low.';
        }

        //
        // Persist expanded messages
        //

        $result['flags'] = array_unique($flags);

        //
        // REVIEW
        //

        if (count($flags) > 0) {
            return 'review';
        }

        //
        // PASS
        //

        return 'pass';
    }

    private function evaluateRegulatoryFlags(array $result): array
    {
        $flags = [];

        if (!empty($result["warning_exact_found"])) {
            // Exact warning found. No warning flag needed.
        } elseif (!empty($result["warning_partial_found"])) {
            $flags[] = "WARNING_PARTIAL_OR_UNREADABLE";
        } else {
            $flags[] = "WARNING_NOT_FOUND";
        }

        if (empty($result["abv"])) {
            $flags[] = "ABV_NOT_FOUND";
        }

        if (empty($result["net_contents"])) {
            $flags[] = "NET_CONTENTS_NOT_FOUND";
        }

        if (empty($result["class"])) {
            $flags[] = "CLASSIFICATION_FAILED";
        }

        if ($result["brand_confidence"] < 60) {
            $flags[] = "LOW_BRAND_CONFIDENCE";
        }

        if ($result["classification_confidence"] < 70) {
            $flags[] = "LOW_CLASSIFICATION_CONFIDENCE";
        }

        return $flags;
    }

    private function findExpectedBrand(?string $expectedBrand, array $lines): array
    {
        if (!$expectedBrand || trim($expectedBrand) === '') {
            return [
                'found' => null,
                'confidence' => 0,
                'match_type' => null,
                'matched_text' => null,
            ];
        }

        $expectedRaw = $this->normalizeOcrLine($expectedBrand);
        $expectedNormalized = $this->normalizeComparableText($expectedRaw);

        if ($expectedNormalized === '') {
            return [
                'found' => null,
                'confidence' => 0,
                'match_type' => null,
                'matched_text' => null,
            ];
        }

        $best = [
            'found' => false,
            'confidence' => 0,
            'match_type' => 'not_found',
            'matched_text' => null,
        ];

        /*
        * Build search windows from raw-ish OCR lines.
        * This allows:
        *
        * 12345
        * IMPORTS
        *
        * to match expected brand "12345 IMPORTS".
        */
        $searchWindows = $this->buildBrandSearchWindows($lines);

        foreach ($searchWindows as $window) {
            $candidateRaw = $window['text'];
            $candidateNormalized = $this->normalizeComparableText(
                $this->normalizeOcrLine($candidateRaw)
            );

            if ($candidateNormalized === '') {
                continue;
            }

            // 1. Exact normalized match.
            if ($candidateNormalized === $expectedNormalized) {
                return [
                    'found' => true,
                    'confidence' => 100,
                    'match_type' => $window['line_count'] > 1
                        ? 'exact_normalized_across_lines'
                        : 'exact_normalized',
                    'matched_text' => $candidateRaw,
                ];
            }

            // 2. Expected brand appears inside a longer OCR window.
            if (str_contains($candidateNormalized, $expectedNormalized)) {
                $score = 95;

                // Reduce confidence if the window is much longer than the expected brand.
                // This prevents legal/address copy from being treated as a clean brand match.
                if (strlen($candidateNormalized) > strlen($expectedNormalized) + 40) {
                    $score -= 10;
                }

                if ($score > $best['confidence']) {
                    $best = [
                        'found' => true,
                        'confidence' => $score,
                        'match_type' => $window['line_count'] > 1
                            ? 'contained_across_lines'
                            : 'contained_in_line',
                        'matched_text' => $candidateRaw,
                    ];
                }

                continue;
            }

            // 3. Token containment: all important brand words appear.
            $tokenScore = $this->brandTokenScore($expectedNormalized, $candidateNormalized);

            if ($tokenScore >= 90 && $tokenScore > $best['confidence']) {
                $best = [
                    'found' => true,
                    'confidence' => $tokenScore,
                    'match_type' => $window['line_count'] > 1
                        ? 'token_match_across_lines'
                        : 'token_match',
                    'matched_text' => $candidateRaw,
                ];
            }

            // 4. Fuzzy similarity for OCR damage.
            // Only use fuzzy matching on reasonably short windows so we do not match
            // random paragraphs or warning text.
            if (strlen($candidateNormalized) <= strlen($expectedNormalized) + 20) {
                similar_text($expectedNormalized, $candidateNormalized, $percent);

                if ($percent >= 88 && $percent > $best['confidence']) {
                    $best = [
                        'found' => true,
                        'confidence' => (int) round($percent),
                        'match_type' => $window['line_count'] > 1
                            ? 'fuzzy_match_across_lines'
                            : 'fuzzy_match',
                        'matched_text' => $candidateRaw,
                    ];
                }
            }
        }

        if ($best['confidence'] >= 75) {
            $best['found'] = true;
        } else {
            $best['found'] = false;
        }

        return $best;
    }

    private function buildBrandSearchWindows(array $lines): array
    {
        $clean = [];

        foreach ($lines as $line) {
            $line = trim((string) $line);

            if ($line === '') {
                continue;
            }

            // Keep the raw-ish line, but normalize obvious spacing.
            $line = preg_replace('/\s+/', ' ', $line);

            if ($line === '') {
                continue;
            }

            $clean[] = $line;
        }

        $windows = [];
        $seen = [];

        $count = count($clean);

        for ($i = 0; $i < $count; $i++) {
            /*
            * Brand names are often split across 1–4 OCR lines.
            * Example:
            * 12345
            * IMPORTS
            */
            for ($size = 1; $size <= 4; $size++) {
                if ($i + $size > $count) {
                    break;
                }

                $parts = array_slice($clean, $i, $size);
                $text = trim(implode(' ', $parts));

                if ($text === '') {
                    continue;
                }

                $key = $this->normalizeComparableText(
                    $this->normalizeOcrLine($text)
                );

                if ($key === '' || isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;

                $windows[] = [
                    'text' => $text,
                    'line_count' => $size,
                    'start_line' => $i,
                ];
            }
        }

        return $windows;
    }
    
    private function normalizeComparableText(string $value): string {
        $value = strtoupper($value);

        // Normalize common OCR/punctuation variations.
        $value = str_replace(['’', '`', '‘'], "'", $value);
        $value = str_replace(['&'], ' AND ', $value);

        // Remove punctuation that should not cause mismatch.
        $value = preg_replace('/[^A-Z0-9\s]/', ' ', $value);

        // Remove common corporate endings that may appear inconsistently.
        $value = preg_replace('/\b(LLC|L L C|INC|CORP|CORPORATION|COMPANY|CO|LTD|LIMITED)\b/', ' ', $value);

        // Remove weak brand noise words only for matching.
        $value = preg_replace('/\b(DISTILLERY|BREWERY|WINERY|CELLARS|VINEYARDS|ESTATE)\b/', ' ', $value);

        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }

    private function brandTokenScore(string $expectedNormalized, string $candidateNormalized): int {
        $expectedTokens = $this->importantTokens($expectedNormalized);
        $candidateTokens = $this->importantTokens($candidateNormalized);

        if (count($expectedTokens) === 0 || count($candidateTokens) === 0) {
            return 0;
        }

        $matched = 0;

        foreach ($expectedTokens as $expectedToken) {
            foreach ($candidateTokens as $candidateToken) {
                if ($expectedToken === $candidateToken) {
                    $matched++;
                    break;
                }

                // Small OCR-tolerance for a token.
                similar_text($expectedToken, $candidateToken, $percent);
                if ($percent >= 88) {
                    $matched++;
                    break;
                }
            }
        }

        $coverage = $matched / count($expectedTokens);

        // Penalize if candidate has many extra tokens.
        $extraTokenPenalty = max(0, count($candidateTokens) - count($expectedTokens)) * 3;

        $score = (int) round(($coverage * 100) - $extraTokenPenalty);

        return max(0, min(100, $score));
    }

    private function importantTokens(string $value): array {
        $tokens = preg_split('/\s+/', trim($value));

        $stopWords = [
            'THE',
            'A',
            'AN',
            'OF',
            'AND',
            'BY',
            'FROM',
            'PRODUCT',
            'BRAND',
            'LABEL',
        ];

        $tokens = array_filter($tokens, function($token) use ($stopWords) {
            return strlen($token) >= 2 && !in_array($token, $stopWords, true);
        });

        return array_values(array_unique($tokens));
    }

    private function verifyExpectedClassType(?string $expectedClassType, array $lines, ?string $abv): array
    {
        if (!$expectedClassType || trim($expectedClassType) === '') {
            return [
                'class' => null,
                'type' => null,
                'designation' => null,
                'matched_text' => null,
                'confidence' => 0,
                'needs_review' => true,
                'flags' => ['No class/type was provided in application data.'],
                'status' => 'review',
                'reason' => 'Class/type verification requires application data.',
            ];
        }

        $expectedRuleKey = $this->findClassTypeRuleKey($expectedClassType);

        if (!$expectedRuleKey) {
            return [
                'class' => null,
                'type' => null,
                'designation' => null,
                'matched_text' => null,
                'confidence' => 0,
                'needs_review' => true,
                'flags' => ['Expected class/type is not included in the prototype ruleset.'],
                'status' => 'review',
                'reason' => 'Prototype ruleset does not contain this class/type yet.',
            ];
        }

        $rule = $this->classTypeRules[$expectedRuleKey];
        $text = $this->normalizeClassTypeText(implode(' ', $lines));
        $abvPercent = $this->parseAbvPercent($abv);

        $matchedText = $this->matchClassTypeRuleEvidence($rule, $text);

        if ($matchedText !== null) {
            $abvCheck = $this->checkMinimumAbv($rule, $abvPercent);

            if ($abvCheck['status'] === 'fail') {
                return [
                    'class' => $rule['display'],
                    'type' => null,
                    'designation' => $rule['display'],
                    'matched_text' => $matchedText,
                    'confidence' => 70,
                    'needs_review' => true,
                    'flags' => [$abvCheck['reason']],
                    'status' => 'fail',
                    'reason' => 'Class/type wording was found, but ABV does not support the expected designation.',
                    'classification_source' => 'expected_class_type_direct_match_abv_fail',
                ];
            }

            return [
                'class' => $rule['display'],
                'type' => null,
                'designation' => $rule['display'],
                'matched_text' => $matchedText,
                'confidence' => 95,
                'needs_review' => false,
                'flags' => [],
                'status' => 'pass',
                'reason' => 'Application class/type is supported by equivalent OCR evidence in the prototype ruleset.',
                'classification_source' => 'expected_class_type_direct_match',
            ];
        }

        $compatibleKeys = $rule['compatible_with'] ?? [];

        foreach ($compatibleKeys as $compatibleKey) {
            if (empty($this->classTypeRules[$compatibleKey])) {
                continue;
            }

            $compatibleRule = $this->classTypeRules[$compatibleKey];

            $compatibleMatchedText = $this->matchClassTypeRuleEvidence($compatibleRule, $text);

            if ($compatibleMatchedText !== null) {
                return [
                    'class' => $rule['display'],
                    'type' => $compatibleRule['display'],
                    'designation' => $compatibleRule['display'],
                    'matched_text' => $compatibleMatchedText,
                    'confidence' => 90,
                    'needs_review' => false,
                    'flags' => [],
                    'status' => 'pass',
                    'reason' => 'Application class/type is supported by compatible malt beverage designation evidence in OCR text.',
                    'classification_source' => 'expected_class_type_compatible_ruleset',
                ];
            }
        }

        return [
            'class' => null,
            'type' => null,
            'designation' => null,
            'matched_text' => null,

            'expected_rule_key' => $expectedRuleKey,
            'expected_rule_display' => $rule['display'],

            'confidence' => 40,
            'needs_review' => true,
            'flags' => ['Expected class/type was not found in OCR text.'],
            'status' => 'review',
            'reason' => 'OCR did not confirm the expected class/type designation.',
            'classification_source' => 'expected_class_type_not_confirmed',
        ];
    }

    private function parseAbvPercent(?string $abv): ?float
    {
        if (!$abv) {
            return null;
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*%?/', $abv, $m)) {
            return (float) $m[1];
        }

        return null;
    }

    private function normalizeClassTypeText(string $text): string
    {
        $text = strtoupper($text);

        $text = str_replace(['É', 'È', 'Ê', 'Ë'], 'E', $text);

        $text = str_replace('WHISKEY', 'WHISKY', $text);
        $text = str_replace('CORDIALS', 'CORDIAL', $text);
        $text = str_replace('LIQUEURS', 'LIQUEUR', $text);

        // Make LIQUEUR/CORDIAL and LIQUEUR CORDIAL comparable.
        $text = str_replace('/', ' ', $text);

        $text = preg_replace('/[^A-Z0-9\s]/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function findClassTypeRuleKey(string $value): ?string
    {
        $normalized = $this->normalizeClassTypeText($value);

        foreach ($this->classTypeRules as $key => $rule) {
            if ($normalized === $this->normalizeClassTypeText($key)) {
                return $key;
            }

            foreach (($rule['aliases'] ?? []) as $alias) {
                if ($normalized === $this->normalizeClassTypeText($alias)) {
                    return $key;
                }
            }
        }

        return null;
    }

    private function matchClassTypeRuleEvidence(array $rule, string $text): ?string
    {
        /*
        * Check normalized aliases first.
        * This lets one rule treat IPA, I P A, I.P.A., and INDIA PALE ALE
        * as equivalent evidence.
        */
        foreach (($rule['aliases'] ?? []) as $alias) {
            $aliasNorm = $this->normalizeClassTypeText($alias);

            if ($aliasNorm === '') {
                continue;
            }

            $aliasPattern = '/(^|\s)' . preg_quote($aliasNorm, '/') . '(\s|$)/';

            if (preg_match($aliasPattern, $text, $m)) {
                return trim($aliasNorm);
            }
        }

        /*
        * Then check explicit regex patterns.
        */
        foreach (($rule['patterns'] ?? []) as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                return trim($m[0]);
            }
        }

        return null;
    }

    private function checkMinimumAbv(array $rule, ?float $abvPercent): array
    {
        if ($abvPercent === null || empty($rule['min_abv'])) {
            return [
                'status' => 'pass',
                'reason' => null,
            ];
        }

        if ($abvPercent < $rule['min_abv']) {
            return [
                'status' => 'fail',
                'reason' => sprintf(
                    '%s requires at least %s%% ABV; detected ABV is %s%%.',
                    $rule['display'],
                    $rule['min_abv'],
                    $abvPercent
                ),
            ];
        }

        return [
            'status' => 'pass',
            'reason' => null,
        ];
    }

    private function verifyCountryOfOrigin(?string $expectedCountry, array $lines): array
    {
        $fullText = $this->normalizeCountryText(implode(' ', $lines));
        $expectedCountry = trim((string) $expectedCountry);

        $importLanguageDetected = $this->detectImportLanguage($fullText);

        /*
        * If the user left Country blank, assume domestic unless the label
        * clearly indicates import language.
        */
        if ($expectedCountry === '') {
            if ($importLanguageDetected) {
                return [
                    'expected' => null,
                    'found' => null,
                    'status' => 'fail',
                    'confidence' => 90,
                    'reason' => 'Country of origin was not provided in application data, but OCR detected import language on the label.',
                    'flag' => 'IMPORT_COUNTRY_REQUIRED',
                ];
            }

            return [
                'expected' => 'United States',
                'found' => 'United States assumed',
                'status' => 'pass',
                'confidence' => 80,
                'reason' => 'Country of origin was left blank and no import language was detected, so domestic origin is assumed.',
                'flag' => null,
            ];
        }

        $expectedNorm = $this->normalizeCountryText($expectedCountry);

        if ($expectedNorm === '') {
            return [
                'expected' => $expectedCountry,
                'found' => null,
                'status' => 'review',
                'confidence' => 0,
                'reason' => 'Country of origin was provided but could not be normalized.',
                'flag' => 'COUNTRY_ORIGIN_REVIEW',
            ];
        }

        /*
        * Direct country match.
        */
        if (str_contains($fullText, $expectedNorm)) {
            return [
                'expected' => $expectedCountry,
                'found' => $expectedCountry,
                'status' => 'pass',
                'confidence' => 100,
                'reason' => 'Country of origin was found in OCR text.',
                'flag' => null,
            ];
        }

        /*
        * Common country aliases.
        */
        $aliases = $this->countryAliases($expectedNorm);

        foreach ($aliases as $alias) {
            if (str_contains($fullText, $alias)) {
                return [
                    'expected' => $expectedCountry,
                    'found' => $alias,
                    'status' => 'pass',
                    'confidence' => 95,
                    'reason' => 'Country of origin was found in OCR text using a recognized country alias.',
                    'flag' => null,
                ];
            }
        }

        return [
            'expected' => $expectedCountry,
            'found' => null,
            'status' => 'fail',
            'confidence' => 0,
            'reason' => 'Country of origin was provided in application data but was not found in OCR text.',
            'flag' => 'COUNTRY_ORIGIN_NOT_FOUND',
        ];
    }

    private function detectImportLanguage(string $normalizedText): bool
    {
        return preg_match(
            '/\b(IMPORTED|IMPORT|IMPORTED\s+BY|IMPORTER|PRODUCT\s+OF|PRODUCED\s+IN|MADE\s+IN)\b/',
            $normalizedText
        ) === 1;
    }

    private function normalizeCountryText(string $text): string
    {
        $text = strtoupper($text);

        $text = str_replace('&', ' AND ', $text);

        $text = preg_replace('/[^A-Z0-9\s]/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function countryAliases(string $countryNorm): array
    {
        $aliases = [
            'UNITED STATES' => ['USA', 'U S A', 'US', 'U S', 'UNITED STATES OF AMERICA', 'AMERICA'],
            'UNITED STATES OF AMERICA' => ['USA', 'U S A', 'US', 'U S', 'UNITED STATES', 'AMERICA'],
            'CANADA' => ['CANADIAN'],
            'MEXICO' => ['MEXICAN'],
            'ENGLAND' => ['UK', 'U K', 'UNITED KINGDOM', 'GREAT BRITAIN'],
            'UNITED KINGDOM' => ['UK', 'U K', 'GREAT BRITAIN', 'ENGLAND', 'SCOTLAND', 'WALES'],
            'SCOTLAND' => ['SCOTCH', 'UNITED KINGDOM', 'UK', 'U K'],
            'IRELAND' => ['IRISH'],
        ];

        return $aliases[$countryNorm] ?? [];
    }

    private function verifyProducerAddress(?string $expectedProducer, array $lines): array
    {
        $expectedProducer = trim((string)$expectedProducer);
        $evidenceWindow = $this->findProducerEvidenceWindow($lines, $expectedProducer);

        if ($expectedProducer === '') {
            return [
                'expected' => null,
                'found' => null,
                'status' => 'review',
                'confidence' => 0,
                'debug_window' => $evidenceWindow,
                'reason' => 'Producer/bottler address was not provided in application data.',
                'flag' => 'PRODUCER_NOT_PROVIDED',
            ];
        }

        $ocrText = $this->normalizeProducerText(implode(' ', $lines));
        $expectedText = $this->normalizeProducerText($expectedProducer);

        if ($expectedText === '') {
            return [
                'expected' => $expectedProducer,
                'found' => null,
                'status' => 'review',
                'confidence' => 0,
                'debug_window' => $evidenceWindow,
                'reason' => 'Producer/bottler address could not be normalized.',
                'flag' => 'PRODUCER_REVIEW',
            ];
        }

        // Exact normalized containment.
        if (str_contains($ocrText, $expectedText)) {
            return [
                'expected' => $expectedProducer,
                'found' => $expectedProducer,
                'status' => 'pass',
                'confidence' => 100,
                'debug_window' => $evidenceWindow,
                'reason' => 'Producer/bottler address was found in OCR text.',
                'flag' => null,
            ];
        }

        // Token coverage fallback for OCR spacing errors like LAVERSTOKEMILL vs LAVERSTOKE MILL.
        $expectedTokens = $this->importantProducerTokens($expectedText);
        $matchedTokens = [];

        foreach ($expectedTokens as $token) {
            if (str_contains($ocrText, $token)) {
                $matchedTokens[] = $token;
            }
        }

        $coverage = count($expectedTokens) > 0
            ? count($matchedTokens) / count($expectedTokens)
            : 0;

        if ($coverage >= 0.75) {
            return [
                'expected' => $expectedProducer,
                'found' => implode(', ', $matchedTokens),
                'status' => 'pass',
                'confidence' => (int)round($coverage * 100),
                'debug_window' => $evidenceWindow,
                'reason' => 'Producer/bottler address was substantially found in OCR text.',
                'flag' => null,
            ];
        }

        if ($coverage >= 0.45) {
            return [
                'expected' => $expectedProducer,
                'found' => implode(', ', $matchedTokens),
                'status' => 'review',
                'confidence' => (int)round($coverage * 100),
                'debug_window' => $evidenceWindow,
                'reason' => 'Producer/bottler address was partially found in OCR text. Human review recommended.',
                'flag' => 'PRODUCER_PARTIAL_MATCH',
            ];
        }

        return [
            'expected' => $expectedProducer,
            'found' => null,
            'status' => 'fail',
            'confidence' => 0,
                'debug_window' => $evidenceWindow,
            'reason' => 'Producer/bottler address was provided in application data but was not found in OCR text.',
            'flag' => 'PRODUCER_NOT_FOUND',
        ];
    }

    private function normalizeProducerText(string $text): string
    {
        $text = strtoupper($text);
        $text = str_replace('&', ' AND ', $text);
        $text = preg_replace('/[^A-Z0-9\s]/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function importantProducerTokens(string $text): array
    {
        $tokens = preg_split('/\s+/', $text);

        $stopWords = [
            'THE', 'A', 'AN', 'BY', 'OF', 'AND',
            'BOTTLED', 'PRODUCED', 'DISTILLED',
            'COMPANY', 'CO', 'INC', 'LLC', 'LTD',
        ];

        $important = [];

        foreach ($tokens as $token) {
            $token = trim($token);

            if ($token === '' || strlen($token) < 3) {
                continue;
            }

            if (in_array($token, $stopWords, true)) {
                continue;
            }

            $important[] = $token;
        }

        return array_values(array_unique($important));
    }
}