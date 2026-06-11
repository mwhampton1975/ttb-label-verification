<?php
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

if (!isset($_FILES['label'])) {
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

$parser = new LabelParser();
$parsed = $parser->parse($output, $expected);

$comparator = new ApplicationComparator();
$comparison = $comparator->compare($expected, $parsed);

$llmRequested = !empty($_POST['use_llm']);
$llmRecommendedByRules = shouldUseLlm($parsed, $comparison);
$llmExecuted = false;
$llmResult = null;

if ($llmRequested && $llmRecommendedByRules) {
    $adjudicator = LlmAdjudicatorFactory::make();

    $llmResult = $adjudicator->adjudicate([
        'application_data' => $expected,
        'ocr_text' => $output,
        'parser_result' => $parsed,
        'comparison_result' => $comparison,
        'rules_summary' => [
            'brand' => 'Brand should match application data after reasonable normalization.',
            'class_type' => 'Class/type should match or be equivalent to the application designation.',
            'abv' => 'Compare numeric ABV value only.',
            'net_contents' => 'Compare normalized volume.',
            'government_warning' => 'Exact required warning text is required for pass. Partial warning evidence means review.',
            'do_not_invent' => 'Do not infer missing text unless supported by OCR evidence.'
        ]
    ]);

    $llmExecuted = true;
}


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
        }

        pre {
            background: #f5f5f5;
            padding: 15px;
            overflow-x: auto;
            border: 1px solid #ddd;
        }

        .status {
            padding: 10px 14px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: bold;
        }

        .pass {
            background: #e8f5e9;
            border: 1px solid #a5d6a7;
            color: #1b5e20;
        }

        .review {
            background: #fff8e1;
            border: 1px solid #ffe082;
            color: #7a5200;
        }

        .fail {
            background: #ffebee;
            border: 1px solid #ef9a9a;
            color: #b71c1c;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        th, td {
            text-align: left;
            vertical-align: top;
            border: 1px solid #ddd;
            padding: 8px;
        }

        th {
            background: #f0f0f0;
        }
    </style>
</head>
<body>

<h1>Alcohol Label Verification Result</h1>

<div class="status <?php echo htmlspecialchars($comparison['overall_status']); ?>">
    Overall Rule-Based Status:
    <?php echo strtoupper(htmlspecialchars($comparison['overall_status'])); ?>
</div>

<?php
$llmFinal = $llmResult['final_recommendation'] ?? null;
$llmProvider = $llmResult['provider'] ?? null;
$llmEnabled = $llmResult['enabled'] ?? null;
?>

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
            <tr>
                <td><?php echo htmlspecialchars($fieldName); ?></td>
                <td>
                    <strong><?php echo strtoupper(htmlspecialchars($field['status'] ?? 'review')); ?></strong>
                </td>
                <td><?php echo htmlspecialchars((string)($field['expected'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars((string)($field['found'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars((string)($field['reason'] ?? '')); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if ($llmExecuted): ?>
    <h2>LLM Adjudication</h2>

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
                    <td><strong><?php echo strtoupper(htmlspecialchars((string)($llmResult['final_recommendation'] ?? 'not returned'))); ?></strong></td>
                </tr>
                <tr>
                    <th>Confidence</th>
                    <td><?php echo htmlspecialchars((string)($llmResult['confidence'] ?? '')); ?></td>
                </tr>
                <tr>
                    <th>Human Review Required</th>
                    <td><?php echo htmlspecialchars(var_export($llmResult['human_review_required'] ?? null, true)); ?></td>
                </tr>
                <tr>
                    <th>Summary</th>
                    <td><?php echo htmlspecialchars((string)($llmResult['summary'] ?? '')); ?></td>
                </tr>
            </tbody>
        </table>

        <?php if (!empty($llmResult['field_results']) && is_array($llmResult['field_results'])): ?>
            <h3>LLM Field Results</h3>

            <table>
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Status</th>
                        <th>Reason</th>
                        <th>Evidence</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($llmResult['field_results'] as $fieldName => $field): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string)$fieldName); ?></td>
                            <td><strong><?php echo strtoupper(htmlspecialchars((string)($field['status'] ?? 'review'))); ?></strong></td>
                            <td><?php echo htmlspecialchars((string)($field['reason'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars((string)($field['evidence'] ?? '')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (!empty($llmResult['review_notes'])): ?>
            <h3>LLM Review Notes</h3>
            <ul>
                <?php foreach ($llmResult['review_notes'] as $note): ?>
                    <li><?php echo htmlspecialchars((string)$note); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

    <?php endif; ?>
<?php endif; ?>

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

<br>
<a href="index.php">← Back</a>

</body>
</html>