<?php

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

?>

<!DOCTYPE html>
<html>
<head>
    <title>OCR Result</title>
</head>
<body>

<h2>OCR Output</h2>

<pre>
<?php echo htmlspecialchars($output); ?>
</pre>

<br>
<a href="index.php">← Back</a>

</body>
</html>