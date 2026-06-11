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

$llmRecommended = shouldUseLlm($parsed, $comparison);
$llmResult = null;

if ($llmRecommended) {
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

<div class="status <?php echo $llmRecommended ? 'review' : 'pass'; ?>">
    LLM Adjudication:
    <?php echo $llmRecommended ? 'Recommended for ambiguous / low-confidence review' : 'Not needed based on rule-based checks'; ?>
</div>

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

<h2>Application Data</h2>
<pre><?php print_r($expected); ?></pre>

<h2>Parsed Result</h2>
<pre><?php print_r($parsed); ?></pre>

<h2>Comparison Result</h2>
<pre><?php print_r($comparison); ?></pre>

<h2>Raw OCR Output</h2>
<pre><?php echo htmlspecialchars($output); ?></pre>

<br>
<a href="index.php">← Back</a>

</body>
</html>