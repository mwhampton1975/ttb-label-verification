<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

move_uploaded_file($tmpName, $targetPath);

/*
 Run Tesseract OCR
*/
$cmd = "tesseract " . escapeshellarg($targetPath) . " stdout 2>&1";

$output = shell_exec($cmd);

require_once __DIR__ . "/../src/LabelParser.php";

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

?>

<!DOCTYPE html>
<html>
<head>
    <title>OCR Result</title>
</head>
<body>

<h2>OCR Output</h2>

<pre>
Application Data:
<?php print_r($expected); ?>

Parsed Result:
<?php print_r($parsed); ?>
</pre>
<br><br>
<pre>
<?php echo $output; ?>
</pre>

<br>
<a href="index.php">← Back</a>

</body>
</html>