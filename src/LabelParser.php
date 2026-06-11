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

    /**
     * Exact class/type designation phrase rules.
     *
     * Order matters:
     * - More specific phrases first.
     * - Generic base classes last.
     * - WHISKEY is normalized to WHISKY before these rules run.
     */
    private array $ttbDesignationRules = [
        // Whisky - most specific first
        ['pattern' => '/\bBOTTLED\s+IN\s+BOND\s+STRAIGHT\s+BOURBON\s+WHISKY\b/', 'class' => 'WHISKY', 'type' => 'BOTTLED IN BOND STRAIGHT BOURBON WHISKY', 'score' => 100],
        ['pattern' => '/\bSTRAIGHT\s+BOURBON\s+WHISKY\b/', 'class' => 'WHISKY', 'type' => 'STRAIGHT BOURBON WHISKY', 'score' => 100],
        ['pattern' => '/\bBOURBON\s+WHISKY\b/', 'class' => 'WHISKY', 'type' => 'BOURBON WHISKY', 'score' => 95],

        ['pattern' => '/\bSTRAIGHT\s+RYE\s+WHISKY\b/', 'class' => 'WHISKY', 'type' => 'STRAIGHT RYE WHISKY', 'score' => 100],
        ['pattern' => '/\bRYE\s+WHISKY\b/', 'class' => 'WHISKY', 'type' => 'RYE WHISKY', 'score' => 95],

        ['pattern' => '/\bSTRAIGHT\s+WHEAT\s+WHISKY\b/', 'class' => 'WHISKY', 'type' => 'STRAIGHT WHEAT WHISKY', 'score' => 100],
        ['pattern' => '/\bWHEAT\s+WHISKY\b/', 'class' => 'WHISKY', 'type' => 'WHEAT WHISKY', 'score' => 95],

        ['pattern' => '/\bSTRAIGHT\s+MALT\s+WHISKY\b/', 'class' => 'WHISKY', 'type' => 'STRAIGHT MALT WHISKY', 'score' => 100],
        ['pattern' => '/\bMALT\s+WHISKY\b/', 'class' => 'WHISKY', 'type' => 'MALT WHISKY', 'score' => 95],

        ['pattern' => '/\bSTRAIGHT\s+CORN\s+WHISKY\b/', 'class' => 'WHISKY', 'type' => 'STRAIGHT CORN WHISKY', 'score' => 100],
        ['pattern' => '/\bCORN\s+WHISKY\b/', 'class' => 'WHISKY', 'type' => 'CORN WHISKY', 'score' => 95],

        ['pattern' => '/\bSCOTCH\s+WHISKY\b/', 'class' => 'WHISKY', 'type' => 'SCOTCH WHISKY', 'score' => 95],
        ['pattern' => '/\bIRISH\s+WHISKY\b/', 'class' => 'WHISKY', 'type' => 'IRISH WHISKY', 'score' => 95],
        ['pattern' => '/\bCANADIAN\s+WHISKY\b/', 'class' => 'WHISKY', 'type' => 'CANADIAN WHISKY', 'score' => 95],
        ['pattern' => '/\bBLENDED\s+WHISKY\b|\bWHISKY\s*-\s*A\s+BLEND\b/', 'class' => 'WHISKY', 'type' => 'BLENDED WHISKY', 'score' => 95],
        ['pattern' => '/\bWHISKY\b/', 'class' => 'WHISKY', 'type' => 'WHISKY', 'score' => 75],

        // Neutral spirits
        ['pattern' => '/\bVODKA\b/', 'class' => 'NEUTRAL SPIRITS', 'type' => 'VODKA', 'score' => 90],
        ['pattern' => '/\bGRAIN\s+SPIRITS\b/', 'class' => 'NEUTRAL SPIRITS', 'type' => 'GRAIN SPIRITS', 'score' => 90],
        ['pattern' => '/\bNEUTRAL\s+SPIRITS\b|\bALCOHOL\b/', 'class' => 'NEUTRAL SPIRITS OR ALCOHOL', 'type' => null, 'score' => 80],

        // Gin
        ['pattern' => '/\bDISTILLED\s+GIN\b/', 'class' => 'GIN', 'type' => 'DISTILLED GIN', 'score' => 95],
        ['pattern' => '/\bREDISTILLED\s+GIN\b/', 'class' => 'GIN', 'type' => 'REDISTILLED GIN', 'score' => 95],
        ['pattern' => '/\bCOMPOUNDED\s+GIN\b/', 'class' => 'GIN', 'type' => 'COMPOUNDED GIN', 'score' => 95],
        ['pattern' => '/\bGIN\b/', 'class' => 'GIN', 'type' => 'GIN', 'score' => 85],

        // Brandy
        ['pattern' => '/\bCOGNAC\b/', 'class' => 'BRANDY', 'type' => 'COGNAC', 'score' => 95],
        ['pattern' => '/\bARMAGNAC\b/', 'class' => 'BRANDY', 'type' => 'ARMAGNAC', 'score' => 95],
        ['pattern' => '/\bCALVADOS\b/', 'class' => 'BRANDY', 'type' => 'CALVADOS', 'score' => 95],
        ['pattern' => '/\bPISCO\b/', 'class' => 'BRANDY', 'type' => 'PISCO', 'score' => 95],
        ['pattern' => '/\bAPPLEJACK\b|\bAPPLE\s+BRANDY\b/', 'class' => 'BRANDY', 'type' => 'APPLE BRANDY', 'score' => 90],
        ['pattern' => '/\bGRAPPA\b/', 'class' => 'BRANDY', 'type' => 'GRAPPA', 'score' => 90],
        ['pattern' => '/\bBRANDY\b/', 'class' => 'BRANDY', 'type' => 'BRANDY', 'score' => 80],

        // Classes with no separate type in the older BAM chart.
        // Current eCFR groups tequila/mezcal under agave spirits, but this keeps practical label output useful.
        ['pattern' => '/\bRUM\b/', 'class' => 'RUM', 'type' => null, 'score' => 85],
        ['pattern' => '/\bTEQUILA\b/', 'class' => 'AGAVE SPIRITS', 'type' => 'TEQUILA', 'score' => 90],
        ['pattern' => '/\b(MEZCAL|MESCAL)\b/', 'class' => 'AGAVE SPIRITS', 'type' => 'MEZCAL', 'score' => 90],
    ];

    private array $ttbLiqueurRules = [
        ['pattern' => '/\bRUM\s+(LIQUEUR|CORDIAL)\b/', 'type' => 'RUM LIQUEUR'],
        ['pattern' => '/\bGIN\s+(LIQUEUR|CORDIAL)\b/', 'type' => 'GIN LIQUEUR'],
        ['pattern' => '/\bBRANDY\s+(LIQUEUR|CORDIAL)\b/', 'type' => 'BRANDY LIQUEUR'],
        ['pattern' => '/\bBOURBON\s+(LIQUEUR|CORDIAL)\b/', 'type' => 'BOURBON LIQUEUR'],
        ['pattern' => '/\bRYE\s+(LIQUEUR|CORDIAL)\b/', 'type' => 'RYE LIQUEUR'],
        ['pattern' => '/\bSLOE\s+GIN\b/', 'type' => 'SLOE GIN'],
        ['pattern' => '/\bROCK\s+AND\s+RYE\b/', 'type' => 'ROCK AND RYE'],
        ['pattern' => '/\bROCK\s+AND\s+BOURBON\b/', 'type' => 'ROCK AND BOURBON'],
        ['pattern' => '/\bROCK\s+AND\s+BRANDY\b/', 'type' => 'ROCK AND BRANDY'],
        ['pattern' => '/\bROCK\s+AND\s+RUM\b/', 'type' => 'ROCK AND RUM'],
        ['pattern' => '/\bAMARETTO\b/', 'type' => 'AMARETTO'],
        ['pattern' => '/\bTRIPLE\s+SEC\b/', 'type' => 'TRIPLE SEC'],
        ['pattern' => '/\bCURACAO|CURAÇAO\b/', 'type' => 'CURACAO'],
        ['pattern' => '/\bSAMBUCA\b/', 'type' => 'SAMBUCA'],
        ['pattern' => '/\bOUZO\b/', 'type' => 'OUZO'],
        ['pattern' => '/\bANISETTE?\b/', 'type' => 'ANISETTE'],
        ['pattern' => '/\bPEPPERMINT\s+SCHNAPPS\b/', 'type' => 'PEPPERMINT SCHNAPPS'],
        ['pattern' => '/\bCR[EÈ]ME\s+DE\s+([A-Z]+)/', 'type' => null],
    ];

    private array $cocktails = [
        'MARGARITA',
        'MARTINI',
        'VODKA MARTINI',
        'MANHATTAN',
        'OLD FASHIONED',
        'SCREWDRIVER',
        'DAIQUIRI',
        'BLOODY MARY',
        'BLACK RUSSIAN',
        'WHITE RUSSIAN',
        'TOM COLLINS',
        'WHISKY SOUR',
        'MINT JULEP',
        'MAI TAI',
        'GIMLET',
        'COLLINS',
        'SLOE GIN FIZZ',
        'WALLBANGER',
        'GRASSHOPPER',
        'PINK SQUIRREL',
    ];

    private array $flavorWords = [
        'ORANGE',
        'LEMON',
        'LIME',
        'CHERRY',
        'PEACH',
        'APPLE',
        'APRICOT',
        'COCONUT',
        'PINEAPPLE',
        'MANGO',
        'VANILLA',
        'CINNAMON',
        'PEPPERMINT',
        'CHOCOLATE',
        'COFFEE',
        'RASPBERRY',
        'STRAWBERRY',
        'BLACKBERRY',
        'BLUEBERRY',
        'BUTTERSCOTCH',
        'HONEY',
        'MAPLE',
        'CARAMEL',
        'MINT',
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

            "status" => "review"
        ];

        $lines = array_map('trim', explode("\n", strtoupper((string) $text)));

        // STEP 1: normalize + filter junk lines
        $cleanLines = $this->filterNoise($lines);
        $cleanLines = array_map([$this, 'normalizeOcrLine'], $cleanLines);
        $cleanLines = $this->mergeLabelLines($cleanLines);

        $brandResult = $this->extractBrand($cleanLines);

        $expectedBrandResult = $this->findExpectedBrand(
            $expected['brand'] ?? null,
            $cleanLines
        );
        $result["expected_brand_found"] = $expectedBrandResult["found"];
        $result["expected_brand_confidence"] = $expectedBrandResult["confidence"];
        $result["expected_brand_match_type"] = $expectedBrandResult["match_type"];
        $result["expected_brand_matched_text"] = $expectedBrandResult["matched_text"];

        // STEP 2: extract structured fields
        $result["abv"] = $this->extractAbv($cleanLines);
        $result["net_contents"] = $this->extractNetContents($cleanLines);

        $classResult = $this->classifyProduct($cleanLines);

        $result["class"] = $classResult["class"] ?? null;
        $result["type"] = $classResult["type"] ?? null;
        $result["designation"] = $classResult["designation"] ?? $classResult["type"] ?? $classResult["class"] ?? null;
        $result["matched_text"] = $classResult["matched_text"] ?? null;
        $result["classification_confidence"] = $classResult["confidence"] ?? 0;
        $result["needs_review"] = $classResult["needs_review"] ?? false;
        $result["flags"] = $classResult["flags"] ?? [];

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

    private function extractAbv($lines) {
        foreach ($lines as $line) {
            if (preg_match('/\b(\d{1,3}(?:\.\d+)?)\s*%\s*(?:ALC(?:OHOL)?\.?\s*)?(?:BY\s+VOL(?:UME)?\.?|ABV)?\b/', $line, $m)) {
                return $m[1] . "%";
            }

            if (preg_match('/\bALC\.?\s*(\d{1,3}(?:\.\d+)?)\s*%\s*VOL\.?\b/', $line, $m)) {
                return $m[1] . "%";
            }
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

    /**
     * Main classification pipeline.
     *
     * Key change from your original:
     * We do not classify from global keyword presence.
     * We first identify likely designation candidate lines,
     * then run exact phrase logic from most specific to least specific.
     */
    private function classifyProduct($lines) {

        $candidates = $this->getDesignationCandidates($lines);
        $candidateText = implode(' ', array_column($candidates, 'line'));
        $fullText = implode(' ', $lines);

        // 1. Liqueurs first because they contain base spirit words.
        if ($result = $this->detectLiqueur($candidateText)) {
            return $result;
        }

        // 2. Flavored spirits before plain base spirits.
        if ($result = $this->detectFlavoredSpirit($candidateText)) {
            return $result;
        }

        // 3. Recognized cocktails before plain base spirits.
        if ($result = $this->detectCocktail($candidateText)) {
            return $result;
        }

        // 4. Exact designation phrase rules.
        foreach ($this->ttbDesignationRules as $rule) {
            foreach ($candidates as $candidate) {
                if (preg_match($rule['pattern'], $candidate['line'])) {
                    $confidence = min(100, $rule['score'] + (int) floor($candidate['score'] / 10));

                    return [
                        'class' => $rule['class'],
                        'type' => $rule['type'],
                        'designation' => $rule['type'] ?? $rule['class'],
                        'matched_text' => $candidate['line'],
                        'confidence' => $confidence,
                        'needs_review' => false,
                        'flags' => [],
                    ];
                }
            }
        }

        // 5. Distilled spirits specialty clues.
        // Current eCFR says the statement of composition and distinctive/fanciful name
        // can serve as the class/type designation for DSS products.
        if (preg_match('/\b(SPICED|WITH\s+NATURAL\s+FLAVORS|WITH\s+ARTIFICIAL\s+FLAVORS|ARTIFICIAL\s+FLAVOR|DISTILLED\s+SPIRITS\s+SPECIALTY)\b/', $fullText)) {
            return [
                'class' => 'DISTILLED SPIRITS SPECIALTY',
                'type' => null,
                'designation' => $this->extractStatementOfComposition($fullText),
                'matched_text' => null,
                'confidence' => 65,
                'needs_review' => true,
                'flags' => ['Possible distilled spirits specialty or statement-of-composition product. Review formula/label statement.'],
            ];
        }

        // 6. Beer/wine fallback from your original logic.
        // Note: this class is mostly distilled-spirit oriented, so these should stay review-weighted.
        if (str_contains($fullText, 'IPA')) {
            return [
                'class' => 'MALT BEVERAGE',
                'type' => 'INDIA PALE ALE',
                'designation' => 'INDIA PALE ALE',
                'matched_text' => 'IPA',
                'confidence' => 75,
                'needs_review' => true,
                'flags' => ['Detected malt beverage term in distilled spirits parser.'],
            ];
        }

        if (preg_match('/\bALE\b/', $fullText)) {
            return [
                'class' => 'MALT BEVERAGE',
                'type' => 'ALE',
                'designation' => 'ALE',
                'matched_text' => 'ALE',
                'confidence' => 70,
                'needs_review' => true,
                'flags' => ['Detected malt beverage term in distilled spirits parser.'],
            ];
        }

        if (preg_match('/\bWINE\b/', $fullText)) {
            return [
                'class' => 'WINE',
                'type' => 'TABLE WINE',
                'designation' => 'TABLE WINE',
                'matched_text' => 'WINE',
                'confidence' => 65,
                'needs_review' => true,
                'flags' => ['Detected wine term in distilled spirits parser.'],
            ];
        }

        return [
            'class' => null,
            'type' => null,
            'designation' => null,
            'matched_text' => null,
            'confidence' => 0,
            'needs_review' => true,
            'flags' => ['No class/type designation confidently detected.'],
        ];
    }

    private function getDesignationCandidates(array $lines): array {
        $candidates = [];

        foreach ($lines as $i => $line) {
            $score = 0;

            if (preg_match('/\b(WHISKY|BOURBON|RYE|VODKA|GIN|RUM|TEQUILA|MEZCAL|BRANDY|COGNAC|LIQUEUR|CORDIAL|SCHNAPPS|TRIPLE\s+SEC|CURACAO)\b/', $line)) {
                $score += 30;
            }

            if (preg_match('/\b(STRAIGHT|BLENDED|DISTILLED|REDISTILLED|COMPOUNDED|FLAVORED|SPICED|BOTTLED\s+IN\s+BOND|AGED)\b/', $line)) {
                $score += 15;
            }

            if (preg_match('/\b(WITH|MADE\s+WITH|NATURAL\s+FLAVOR|NATURAL\s+FLAVORS|ARTIFICIAL\s+FLAVOR|ARTIFICIAL\s+FLAVORS|STATEMENT\s+OF\s+COMPOSITION)\b/', $line)) {
                $score += 10;
            }

            // Penalize warning/importer/address/ABV/container lines.
            if (preg_match('/\b(GOVERNMENT\s+WARNING|SURGEON\s+GENERAL|PREGNANCY|OPERATE\s+MACHINERY|IMPORTED\s+BY|BOTTLED\s+BY|PRODUCED\s+BY|DISTILLED\s+BY|ALC|VOL|ML|PROOF|NET\s+CONTENTS)\b/', $line)) {
                $score -= 25;
            }

            // Often near the top or center label, though OCR order is imperfect.
            if ($i < 12) {
                $score += 5;
            }

            // Very long lines are often legal copy.
            if (strlen($line) > 80) {
                $score -= 10;
            }

            if ($score > 0) {
                $candidates[] = [
                    'line' => $line,
                    'index' => $i,
                    'score' => $score,
                ];
            }
        }

        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);

        return $candidates;
    }

    private function detectLiqueur(string $text): ?array {
        foreach ($this->ttbLiqueurRules as $rule) {
            if (preg_match($rule['pattern'], $text, $m)) {
                $type = $rule['type'];

                if (!$type && str_contains($m[0], 'CREME')) {
                    $type = trim($m[0]);
                }

                return [
                    'class' => 'LIQUEUR/CORDIAL',
                    'type' => $type,
                    'designation' => $type,
                    'matched_text' => trim($m[0]),
                    'confidence' => 95,
                    'needs_review' => false,
                    'flags' => [],
                ];
            }
        }

        // Generic liqueur/cordial catch.
        if (preg_match('/\b(LIQUEUR|CORDIAL)\b/', $text, $m)) {
            return [
                'class' => 'LIQUEUR/CORDIAL',
                'type' => null,
                'designation' => $m[1],
                'matched_text' => $m[1],
                'confidence' => 70,
                'needs_review' => true,
                'flags' => ['Generic liqueur/cordial detected without a specific type.'],
            ];
        }

        return null;
    }

    private function detectFlavoredSpirit(string $text): ?array {
        $basePattern = '(VODKA|GIN|RUM|WHISKY|BRANDY)';
        $flavorPattern = implode('|', array_map('preg_quote', $this->flavorWords));

        // Example: ORANGE FLAVORED VODKA
        if (preg_match('/\b(' . $flavorPattern . ')\s+FLAVORED\s+' . $basePattern . '\b/', $text, $m)) {
            $flavor = $m[1];
            $base = $this->normalizeBaseSpirit($m[2]);

            return [
                'class' => 'FLAVORED ' . $base,
                'type' => null,
                'designation' => $flavor . ' FLAVORED ' . $base,
                'matched_text' => trim($m[0]),
                'confidence' => 96,
                'needs_review' => false,
                'flags' => [],
            ];
        }

        // Example: VODKA WITH NATURAL ORANGE FLAVOR
        if (preg_match('/\b' . $basePattern . '\s+WITH\s+(?:NATURAL\s+)?(' . $flavorPattern . ')\s+FLAVOR(?:S)?\b/', $text, $m)) {
            $base = $this->normalizeBaseSpirit($m[1]);
            $flavor = $m[2];

            return [
                'class' => 'FLAVORED ' . $base,
                'type' => null,
                'designation' => $flavor . ' FLAVORED ' . $base,
                'matched_text' => trim($m[0]),
                'confidence' => 88,
                'needs_review' => true,
                'flags' => ['Detected “with flavor” phrasing. Review whether this should be flavored class/type or distilled spirits specialty statement of composition.'],
            ];
        }

        // Example: SPICED RUM, commonly needs statement-of-composition context.
        if (preg_match('/\bSPICED\s+(RUM|WHISKY|VODKA|BRANDY|GIN)\b/', $text, $m)) {
            $base = $this->normalizeBaseSpirit($m[1]);

            return [
                'class' => 'DISTILLED SPIRITS SPECIALTY',
                'type' => null,
                'designation' => 'SPICED ' . $base,
                'matched_text' => trim($m[0]),
                'confidence' => 80,
                'needs_review' => true,
                'flags' => ['Spiced product detected. Verify required statement of composition and formula treatment.'],
            ];
        }

        return null;
    }

    private function detectCocktail(string $text): ?array {
        foreach ($this->cocktails as $cocktail) {
            if (preg_match('/\b' . preg_quote($cocktail, '/') . '\b/', $text, $m)) {
                $designation = $cocktail;
                $confidence = 75;
                $needsReview = true;
                $flags = ['Cocktail name detected. Verify distilled spirits component declaration.'];

                if (preg_match('/\b' . preg_quote($cocktail, '/') . '\s+MADE\s+WITH\s+([A-Z\s]+?)(?:$|\s{2,}|,|\.)/', $text, $madeWith)) {
                    $component = trim($madeWith[1]);
                    $designation = $cocktail . ' MADE WITH ' . $component;
                    $confidence = 90;
                    $needsReview = false;
                    $flags = [];
                }

                return [
                    'class' => 'RECOGNIZED COCKTAIL',
                    'type' => $cocktail,
                    'designation' => $designation,
                    'matched_text' => trim($m[0]),
                    'confidence' => $confidence,
                    'needs_review' => $needsReview,
                    'flags' => $flags,
                ];
            }
        }

        return null;
    }

    private function extractStatementOfComposition(string $text): ?string {
        // Tries to pull a useful phrase when DSS clues are present.
        // Keep this conservative. If uncertain, it should trigger review.
        $patterns = [
            '/\b[A-Z\s]+WITH\s+NATURAL\s+FLAVORS?\b/',
            '/\b[A-Z\s]+WITH\s+ARTIFICIAL\s+FLAVORS?\b/',
            '/\bSPICED\s+[A-Z]+\b/',
            '/\bDISTILLED\s+SPIRITS\s+SPECIALTY\b/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                return trim(preg_replace('/\s+/', ' ', $m[0]));
            }
        }

        return null;
    }

    private function normalizeBaseSpirit(string $base): string {
        $base = strtoupper($base);

        return match ($base) {
            'WHISKEY' => 'WHISKY',
            default => $base,
        };
    }

    /**
     * Kept from your original, but now mostly superseded by classifyProduct().
     */
    private function extractClass($lines) {
        foreach ($lines as $line) {
            if (preg_match('/(WHISKY|RUM|VODKA|GIN|TEQUILA|MEZCAL|COGNAC|BOURBON|SCOTCH|ALE|IPA|LAGER|PILSNER|PORTER|STOUT|SAISON|CIDER|WINE|MERLOT|CABERNET|CHARDONNAY|MUSCAT)/', $line)) {
                return $line;
            }
        }

        return null;
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

        if (
            empty($result['class']) &&
            empty($result['type']) &&
            empty($result['designation'])
        ) {
            return 'fail';
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

    private function findExpectedBrand(?string $expectedBrand, array $lines): array {
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

        $searchWindows = $this->buildSearchWindows($lines);

        foreach ($searchWindows as $window) {
            $candidateRaw = $window['text'];
            $candidateNormalized = $this->normalizeComparableText($candidateRaw);

            if ($candidateNormalized === '') {
                continue;
            }

            // 1. Exact normalized match.
            if ($candidateNormalized === $expectedNormalized) {
                return [
                    'found' => true,
                    'confidence' => 100,
                    'match_type' => 'exact_normalized',
                    'matched_text' => $candidateRaw,
                ];
            }

            // 2. Expected brand appears inside a longer OCR line.
            if (str_contains($candidateNormalized, $expectedNormalized)) {
                $score = 95;

                // Slightly reduce confidence if line is very long, because it may be legal copy.
                if (strlen($candidateNormalized) > strlen($expectedNormalized) + 40) {
                    $score -= 10;
                }

                if ($score > $best['confidence']) {
                    $best = [
                        'found' => true,
                        'confidence' => $score,
                        'match_type' => 'contained_in_line',
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
                    'match_type' => 'token_match',
                    'matched_text' => $candidateRaw,
                ];
            }

            // 4. Fuzzy similarity for OCR damage.
            similar_text($expectedNormalized, $candidateNormalized, $percent);

            if ($percent >= 88 && $percent > $best['confidence']) {
                $best = [
                    'found' => true,
                    'confidence' => (int) round($percent),
                    'match_type' => 'fuzzy_match',
                    'matched_text' => $candidateRaw,
                ];
            }
        }

        // Confidence interpretation:
        // 90+ pass-worthy
        // 75-89 review-worthy
        // below 75 not found
        if ($best['confidence'] >= 75) {
            $best['found'] = true;
        } else {
            $best['found'] = false;
        }

        return $best;
    }

    private function buildSearchWindows(array $lines): array {
        $windows = [];

        foreach ($lines as $i => $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $windows[] = [
                'text' => $line,
                'start_line' => $i,
                'end_line' => $i,
            ];

            // Two-line window: useful when OCR splits brand across lines.
            if (isset($lines[$i + 1])) {
                $twoLine = trim($line . ' ' . $lines[$i + 1]);

                $windows[] = [
                    'text' => $twoLine,
                    'start_line' => $i,
                    'end_line' => $i + 1,
                ];
            }

            // Three-line window: useful for stacked brand names.
            if (isset($lines[$i + 1], $lines[$i + 2])) {
                $threeLine = trim($line . ' ' . $lines[$i + 1] . ' ' . $lines[$i + 2]);

                $windows[] = [
                    'text' => $threeLine,
                    'start_line' => $i,
                    'end_line' => $i + 2,
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

    private function evaluateExpectedBrandStatus(array $brandMatch): string {
        if ($brandMatch['found'] === null) {
            return 'review';
        }

        if ($brandMatch['confidence'] >= 90) {
            return 'pass';
        }

        if ($brandMatch['confidence'] >= 75) {
            return 'review';
        }

        return 'fail';
    }
}