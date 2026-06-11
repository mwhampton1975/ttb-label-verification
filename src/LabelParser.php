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

    public function parse($text) {

        $result = [
            "brand" => null,
            "brand_confidence" => 0,

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

            "status" => "review"
        ];

        $lines = array_map('trim', explode("\n", strtoupper((string) $text)));

        // STEP 1: normalize + filter junk lines
        $cleanLines = $this->filterNoise($lines);
        $cleanLines = array_map([$this, 'normalizeOcrLine'], $cleanLines);
        $cleanLines = $this->mergeLabelLines($cleanLines);

        $brandResult = $this->extractBrand($cleanLines);

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

        $result["warning_found"] = $this->detectWarning($cleanLines);
        $result["brand"] = $brandResult["value"];
        $result["brand_confidence"] = $brandResult["confidence"];

        $result["flags"] = $this->evaluateRegulatoryFlags($result);
        $result["flags"] = array_merge(
            $result["flags"],
            $this->evaluateFieldOfVision(
                $cleanLines,
                $result
            )
        );
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
            default => $base
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

        if (empty($result['warning_found'])) {
            $flags[] = 'Government warning not detected in OCR text.';
        }

        //
        // New compliance checks
        //

        if (in_array(
            'POSSIBLE_FIELD_OF_VISION_VIOLATION',
            $flags
        )) {
            $flags[] =
                'Mandatory information may not appear in same field of vision.';
        }

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

        if (!$result["warning_found"]) {
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

    private function findLineNumber(
        array $lines,
        string $search
    ): ?int
    {
        foreach ($lines as $i => $line) {

            if (str_contains($line, $search)) {
                return $i;
            }
        }

        return null;
    }

    private function evaluateFieldOfVision(
        array $lines,
        array $result
    ): array
    {
        $flags = [];

        $brandLine = null;
        $classLine = null;
        $abvLine = null;

        if (!empty($result["brand"])) {

            foreach ($lines as $i => $line) {

                if ($line === $result["brand"]) {
                    $brandLine = $i;
                    break;
                }
            }
        }

        if (!empty($result["type"])) {

            foreach ($lines as $i => $line) {

                if (str_contains($line, $result["type"])) {
                    $classLine = $i;
                    break;
                }
            }
        }

        foreach ($lines as $i => $line) {

            if (preg_match('/\d+(?:\.\d+)?\s*%/', $line)) {

                $abvLine = $i;
                break;
            }
        }

        $positions = array_filter([
            $brandLine,
            $classLine,
            $abvLine
        ], fn($v) => $v !== null);

        if (count($positions) >= 2) {

            $distance =
                max($positions) - min($positions);

            if ($distance > 10) {

                $flags[] =
                    "POSSIBLE_FIELD_OF_VISION_VIOLATION";
            }
        }

        return $flags;
    }
}