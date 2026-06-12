<?php
$processStartedAt = microtime(true);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . "/../vendor/autoload.php";

require_once __DIR__ . "/../src/Llm/LlmAdjudicatorInterface.php";
require_once __DIR__ . "/../src/Llm/NullLlmAdjudicator.php";
require_once __DIR__ . "/../src/Llm/BedrockAdjudicator.php";
require_once __DIR__ . "/../src/Llm/LlmAdjudicatorFactory.php";

    function shouldUseLlm(array $parsed, array $comparison): bool
    {
        if (($parsed['classification_confidence'] ?? 0) < 80) {
            return true;
        }

        if (($parsed['warning_status'] ?? null) !== 'pass') {
            return true;
        }

        if (!empty($parsed['needs_review'])) {
            return true;
        }

        if (!empty($parsed['flags'])) {
            return true;
        }

        if (($comparison['overall_status'] ?? null) !== 'pass') {
            return true;
        }

        return false;
    }

    function buildCompactLlmInput(array $expected, array $parsed, array $comparison): array
    {
        $fields = $comparison['fields'] ?? [];

        return [
            'application_data' => [
                'brand' => $expected['brand'] ?? null,
                'class_type' => $expected['class_type'] ?? null,
                'abv' => $expected['abv'] ?? null,
                'net_contents' => $expected['net_contents'] ?? null,
                'producer' => $expected['producer'] ?? null,
                'country' => $expected['country'] ?? null,
            ],

            'rule_based_result' => [
                'overall_status' => $comparison['overall_status'] ?? 'review',
                'fields' => $comparison['fields'] ?? [],
            ],

            'parser_evidence' => [
                'brand' => [
                    'matched_text' => $parsed['expected_brand_matched_text'] ?? null,
                    'confidence' => $parsed['expected_brand_confidence'] ?? null,
                    'match_type' => $parsed['expected_brand_match_type'] ?? null,
                ],
                'class_type' => [
                    'designation' => $parsed['designation'] ?? null,
                    'matched_text' => $parsed['matched_text'] ?? null,
                    'confidence' => $parsed['classification_confidence'] ?? null,
                    'status' => $parsed['class_type_status'] ?? null,
                    'reason' => $parsed['class_type_reason'] ?? null,
                ],
                'abv' => [
                    'found' => $parsed['abv'] ?? null,
                ],
                'net_contents' => [
                    'found' => $parsed['net_contents'] ?? null,
                ],
                'producer' => [
                    'expected' => $expected['producer'] ?? null,
                    'found' => $parsed['producer_found'] ?? null,
                    'status' => $fields['producer']['status'] ?? null,
                    'reason' => $fields['producer']['reason'] ?? null,
                    'ocr_nearby_text' => $parsed['producer_debug_window'] ?? null,
                ],
                'country' => [
                    'found' => $parsed['country_found'] ?? null,
                    'confidence' => $parsed['country_confidence'] ?? null,
                    'reason' => $parsed['country_reason'] ?? null,
                ],
                'government_warning' => [
                    'status' => $parsed['warning_status'] ?? null,
                    'exact_found' => $parsed['warning_exact_found'] ?? null,
                    'partial_found' => $parsed['warning_partial_found'] ?? null,
                    'matched_text' => $parsed['warning_matched_text'] ?? null,
                    'confidence' => $parsed['warning_confidence'] ?? null,
                ],
                'flags' => $parsed['flags'] ?? [],
            ],

            'adjudication_rules' => [
                'Do not invent label text.',
                'Do not re-run OCR.',
                'Use the existing field results and parser evidence only.',
                'If any field is fail, final recommendation should be fail unless the failure is clearly a parser limitation.',
                'If any field is review and none fail, final recommendation should be review.',
                'Only return pass when all required fields pass.',
                'Government warning requires exact confirmation to pass.',
            ],
        ];
    }

    function statusClass(?string $status): string
    {
        $status = strtolower((string) $status);

        return in_array($status, ['pass', 'review', 'fail'], true)
            ? $status
            : 'review';
    }

    function renderValue(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            return htmlspecialchars(print_r($value, true));
        }

        return htmlspecialchars((string) $value);
    }

    function applyLlmSoftFieldOverrides(array $comparison, array $llmResult): array
    {
        $fieldsToCheck = ['brand', 'producer'];

        foreach ($fieldsToCheck as $field) {
            if (!isset($comparison['fields'][$field])) {
                continue;
            }

            $statusKey = $field . '_status';
            $confidenceKey = $field . '_confidence';
            $reasonKey = $field . '_reason';
            $overrideKey = $field . '_override';

            $newStatus = $llmResult[$statusKey] ?? null;
            $confidence = (int)($llmResult[$confidenceKey] ?? 0);
            $reason = $llmResult[$reasonKey] ?? 'LLM soft-field adjudication applied.';

            if (!in_array($newStatus, ['pass', 'review', 'fail'], true)) {
                continue;
            }

            $originalStatus = $comparison['fields'][$field]['status'] ?? 'review';

            /*
            * Apply if the model explicitly requested an override,
            * or if the model recommends a different soft-field status.
            */
            $shouldApply = !empty($llmResult[$overrideKey]) || $newStatus !== $originalStatus;

            if (!$shouldApply) {
                continue;
            }

            /*
            * Require high confidence for pass.
            * Lower-confidence pass becomes review.
            */
            if ($newStatus === 'pass' && $confidence < 90) {
                $newStatus = 'review';
                $reason .= ' Confidence was below the pass threshold, so the field was set to review instead of pass.';
            }

            /*
            * Do not let the LLM downgrade an existing pass.
            */
            if ($originalStatus === 'pass' && $newStatus !== 'pass') {
                continue;
            }

            $comparison['fields'][$field]['status'] = $newStatus;
            $comparison['fields'][$field]['reason'] =
                'LLM soft-field review: ' . $reason .
                ' Original rule-based status was ' . strtoupper($originalStatus) . '.';

            $comparison['fields'][$field]['llm_override_applied'] = true;
            $comparison['fields'][$field]['llm_confidence'] = $confidence;
        }

        $comparison['overall_status'] = recalculateOverallStatus($comparison['fields'] ?? []);

        return $comparison;
    }

    function recalculateOverallStatus(array $fields): string
    {
        foreach ($fields as $field) {
            if (($field['status'] ?? null) === 'fail') {
                return 'fail';
            }
        }

        foreach ($fields as $field) {
            if (($field['status'] ?? null) === 'review') {
                return 'review';
            }
        }

        return 'pass';
    }

$sampleMap = [
    'bombay_sapphire.jpg' => __DIR__ . '/../samples/bombay_sapphire.jpg',
    'hawks_shadow.jpg' => __DIR__ . '/../samples/hawks_shadow.jpg',
    'stormchaser.jpg' => __DIR__ . '/../samples/stormchaser.jpg',
    'brand-label-new1.jpg' => __DIR__ . '/../samples/brand-label-new1.jpg',
    'brand-label-new2.jpg' => __DIR__ . '/../samples/brand-label-new2.jpg',
    'honey_huckleberry_pie.png' => __DIR__ . '/../samples/honey_huckleberry_pie.png',
    'tropical_chimp.jpg' => __DIR__ . '/../samples/tropical_chimp.jpg',
    'malt_and_hop_india_pale_ale.png' => __DIR__ . '/../samples/malt_and_hop_india_pale_ale.png',
];

$sampleLabel = $_POST['sample_label'] ?? '';

if ($sampleLabel !== '') {
    /*
     * Demo sample path.
     * Only allow known sample filenames from the whitelist above.
     */
    if (empty($sampleMap[$sampleLabel]) || !is_file($sampleMap[$sampleLabel])) {
        die("Invalid sample label selected.");
    }

    $targetPath = $sampleMap[$sampleLabel];
    $fileName = $sampleLabel;
} else {
    /*
     * Normal upload path.
     */
    if (
        !isset($_FILES['label']) ||
        !isset($_FILES['label']['tmp_name']) ||
        $_FILES['label']['error'] !== UPLOAD_ERR_OK
    ) {
        die("No file uploaded");
    }

    $uploadDir = __DIR__ . "/uploads/";

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $tmpName = $_FILES['label']['tmp_name'];
    $fileName = basename($_FILES['label']['name']);

    $targetPath = $uploadDir . $fileName;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        die("Upload failed");
    }
}

/*
 Run Tesseract OCR
*/
$cmd = "tesseract " . escapeshellarg($targetPath) . " stdout 2>&1";
$output = shell_exec($cmd);

if ($output === null) {
    $output = '';
}

require_once __DIR__ . "/../src/LabelParser.php";
require_once __DIR__ . "/../src/ApplicationComparator.php";

$expected = [
    'brand' => $_POST['expected_brand'] ?? null,
    'class_type' => $_POST['expected_class_type'] ?? null,
    'abv' => $_POST['expected_abv'] ?? null,
    'net_contents' => $_POST['expected_net_contents'] ?? null,
    'producer' => $_POST['expected_producer'] ?? null,
    'country' => $_POST['expected_country'] ?? null,
];

$showDebug = !empty($_POST['show_debug']);

if (empty(trim((string)($expected['class_type'] ?? '')))) {
    die("Class / Type is required.");
}

$parser = new LabelParser();
$parsed = $parser->parse($output, $expected);

$comparator = new ApplicationComparator();
$comparison = $comparator->compare($expected, $parsed);


$llmRequested = !empty($_POST['use_llm']);
$llmRecommendedByRules = shouldUseLlm($parsed, $comparison);
$llmExecuted = false;
$llmResult = null;

$llmDuration = null;
$llmStartedAt = microtime(true);
if ($llmRequested && $llmRecommendedByRules) {
    $adjudicator = LlmAdjudicatorFactory::make();

    $llmResult = $adjudicator->adjudicate(
        buildCompactLlmInput($expected, $parsed, $comparison)
    );

    $llmExecuted = true;
}
$llmDuration = microtime(true) - $llmStartedAt;
if ($llmExecuted && is_array($llmResult)) {
    $comparison = applyLlmSoftFieldOverrides($comparison, $llmResult);
}

$totalDuration = microtime(true) - $processStartedAt;
?>
<!DOCTYPE html>
<html>
<head>
    <title>OCR Result</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1100px;
            margin: 30px auto;
            line-height: 1.4;
            color: #222;
            background: #fafafa;
        }

        h1, h2, h3 {
            margin-bottom: 10px;
        }

        .card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 22px;
        }

        .status {
            padding: 12px 14px;
            border-radius: 5px;
            margin-bottom: 12px;
            font-weight: bold;
        }

        .pass {
            background: #e8f5e9;
            color: #1b5e20;
            border: 1px solid #a5d6a7;
        }

        .review {
            background: #fff8e1;
            color: #7a5200;
            border: 1px solid #ffe082;
        }

        .fail {
            background: #fdecea;
            color: #8a1c1c;
            border: 1px solid #f5b5ae;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            margin-top: 12px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            vertical-align: top;
            text-align: left;
        }

        th {
            background: #f3f3f3;
        }

        tr.pass td {
            background: #e8f5e9;
        }

        tr.review td {
            background: #fff8e1;
        }

        tr.fail td {
            background: #fdecea;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge.pass {
            background: #c8e6c9;
            color: #1b5e20;
            border: 1px solid #81c784;
        }

        .badge.review {
            background: #ffecb3;
            color: #7a5200;
            border: 1px solid #ffd54f;
        }

        .badge.fail {
            background: #ffcdd2;
            color: #8a1c1c;
            border: 1px solid #ef9a9a;
        }

        pre {
            white-space: pre-wrap;
            background: #f5f5f5;
            border: 1px solid #ddd;
            padding: 14px;
            border-radius: 5px;
            overflow-x: auto;
        }

        .meta {
            color: #555;
            font-size: 0.95em;
        }

        .debug-section {
            margin-top: 26px;
        }
    </style>
</head>
<body>

<h1>Alcohol Label Verification Result</h1>
<?php if ($showDebug): ?>
    <p><strong>Total processing duration:</strong> <?php echo number_format($totalDuration, 2); ?> seconds</p>
<?php endif; ?>
<div class="status <?php echo htmlspecialchars($comparison['overall_status']); ?>">
    Overall Rule-Based Status:
    <?php echo strtoupper(htmlspecialchars($comparison['overall_status'])); ?>
</div>

<?php
$llmFinal = $llmResult['final_recommendation'] ?? null;
$llmProvider = $llmResult['provider'] ?? null;
$llmEnabled = $llmResult['enabled'] ?? null;
?>

<?php if ($showDebug): ?>
    <div class="debug-section">
        <div class="status <?php echo $llmRequested ? ($llmRecommendedByRules ? 'review' : 'pass') : 'review'; ?>">
            LLM Adjudication:
            <?php if (!$llmRequested): ?>
                Disabled for this run.
            <?php elseif (!$llmRecommendedByRules): ?>
                Enabled, but not needed based on rule-based checks.
            <?php else: ?>
                Enabled and recommended for ambiguous / low-confidence review.
            <?php endif; ?>
        </div>

        <?php if ($llmExecuted): ?>
            <div class="status <?php echo statusClass($llmFinal ?? 'review'); ?>">
                LLM Adjudication Result:
                <?php echo strtoupper(htmlspecialchars((string)($llmFinal ?? 'not returned'))); ?>
                <?php if ($llmProvider): ?>
                    via <?php echo htmlspecialchars((string)$llmProvider); ?>
                <?php endif; ?>
            </div>

            <?php if ($llmDuration !== null && $showDebug): ?>
                <p><strong>LLM duration:</strong> <?php echo number_format($llmDuration, 2); ?> seconds</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<h2>Field Comparison</h2>

<table>
    <thead>
        <tr>
            <th>Field</th>
            <th>Status</th>
            <th>Expected</th>
            <th>Found</th>
            <th>Reason</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($comparison['fields'] as $fieldName => $field): ?>
            <?php
            $fieldStatus = $field['status'] ?? 'review';
            $fieldStatusClass = statusClass($fieldStatus);
            ?>
            <tr class="<?php echo $fieldStatusClass; ?>">
                <td><?php echo htmlspecialchars($fieldName); ?></td>
                <td>
                    <span class="badge <?php echo $fieldStatusClass; ?>">
                        <?php echo strtoupper(htmlspecialchars((string)$fieldStatus)); ?>
                    </span>
                </td>
                <td><?php echo htmlspecialchars((string)($field['expected'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars((string)($field['found'] ?? '')); ?></td>
                <td>
                    <?php echo htmlspecialchars((string)($field['reason'] ?? '')); ?>

                    <?php if (!empty($field['llm_override_applied'])): ?>
                        <br>
                        <small>
                            LLM-assisted soft-field review applied
                            <?php if (isset($field['llm_confidence'])): ?>
                                at <?php echo htmlspecialchars((string)$field['llm_confidence']); ?>% confidence.
                            <?php else: ?>
                                .
                            <?php endif; ?>
                        </small>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if ($llmExecuted): ?>
    <h3>LLM Soft Field Review</h3>

        <table>
            <thead>
                <tr>
                    <th>Field</th>
                    <th>Override</th>
                    <th>LLM Status</th>
                    <th>Confidence</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $brandLlmStatus = $llmResult['brand_status'] ?? 'review';
                $brandLlmClass = statusClass($brandLlmStatus);

                $producerLlmStatus = $llmResult['producer_status'] ?? 'review';
                $producerLlmClass = statusClass($producerLlmStatus);
                ?>

                <tr class="<?php echo $brandLlmClass; ?>">
                    <td>Brand</td>
                    <td><?php echo !empty($llmResult['brand_override']) ? 'Yes' : 'No'; ?></td>
                    <td>
                        <span class="badge <?php echo $brandLlmClass; ?>">
                            <?php echo strtoupper(htmlspecialchars((string)$brandLlmStatus)); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars((string)($llmResult['brand_confidence'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars((string)($llmResult['brand_reason'] ?? '')); ?></td>
                </tr>

                <tr class="<?php echo $producerLlmClass; ?>">
                    <td>Producer</td>
                    <td><?php echo !empty($llmResult['producer_override']) ? 'Yes' : 'No'; ?></td>
                    <td>
                        <span class="badge <?php echo $producerLlmClass; ?>">
                            <?php echo strtoupper(htmlspecialchars((string)$producerLlmStatus)); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars((string)($llmResult['producer_confidence'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars((string)($llmResult['producer_reason'] ?? '')); ?></td>
                </tr>
            </tbody>
        </table>
<?php endif; ?>

<?php if ($showDebug && $llmExecuted): ?>
    <h2>LLM Debug</h2>

    <?php if ($llmResult === null): ?>
        <pre>LLM was recommended, but no LLM result was returned.</pre>
    <?php else: ?>

        <table>
            <tbody>
                <tr>
                    <th>Enabled</th>
                    <td><?php echo htmlspecialchars(var_export($llmResult['enabled'] ?? null, true)); ?></td>
                </tr>
                <tr>
                    <th>Provider</th>
                    <td><?php echo htmlspecialchars((string)($llmResult['provider'] ?? 'unknown')); ?></td>
                </tr>
                <tr>
                    <th>Model</th>
                    <td><?php echo htmlspecialchars((string)($llmResult['model_id'] ?? $llmResult['model'] ?? '')); ?></td>
                </tr>
                <tr>
                    <th>Final Recommendation</th>
                    <td>
                        <strong>
                            <?php echo strtoupper(htmlspecialchars((string)($llmResult['final_recommendation'] ?? 'not returned'))); ?>
                        </strong>
                    </td>
                </tr>
                <tr>
                    <th>Human Review Required</th>
                    <td><?php echo htmlspecialchars(var_export($llmResult['human_review_required'] ?? null, true)); ?></td>
                </tr>
            </tbody>
        </table>

        <?php if (!empty($llmResult['review_notes'])): ?>
            <h3>LLM Review Notes</h3>
            <ul>
                <?php foreach ($llmResult['review_notes'] as $note): ?>
                    <li><?php echo htmlspecialchars((string)$note); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <h3>LLM Raw Result</h3>
        <pre><?php echo htmlspecialchars(print_r($llmResult, true)); ?></pre>

    <?php endif; ?>
<?php endif; ?>

<?php if ($showDebug): ?>
    <h2>Application Data</h2>
    <pre><?php print_r($expected); ?></pre>

    <h2>Parsed Result</h2>
    <pre><?php print_r($parsed); ?></pre>

    <h2>Comparison Result</h2>
    <pre><?php print_r($comparison); ?></pre>

    <h2>LLM Raw Result</h2>
    <pre><?php print_r($llmResult); ?></pre>

    <h2>Raw OCR Output</h2>
    <pre><?php echo htmlspecialchars($output); ?></pre>
<?php endif; ?>
<br>
<a href="index.php">← Back</a>

</body>
</html>