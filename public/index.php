<?php
?>
<!DOCTYPE html>
<html>
<head>
    <title>TTB Label OCR Prototype</title>
</head>
<body>

<h2>Upload Label Image</h2>

<form action="process.php" method="POST" enctype="multipart/form-data">
    <input type="file" name="label" accept="image/*" required />
    <br><br>
    <button type="submit">Run OCR</button>
</form>

</body>
</html>