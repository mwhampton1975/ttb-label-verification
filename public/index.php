<?php
?>
<!DOCTYPE html>
<html>
<head>
    <title>TTB Label OCR Prototype</title>
</head>
<body>

<h2>Upload Label Image</h2>

<form id="labelForm" action="process.php" method="post" enctype="multipart/form-data">
    <h1>Alcohol Label Verification</h1>

    <fieldset>
        <legend>Label Image</legend>
        <label>
            Upload label image
            <input type="file" name="label" accept="image/*,.pdf" required>
        </label>
    </fieldset>

    <fieldset>
        <legend>Application Data</legend>

        <label>
            Brand Name
            <input type="text" name="expected_brand" placeholder="OLD TOM DISTILLERY" required>
        </label>

        <label>
            Class / Type
            <input
                type="text"
                id="expected_class_type"
                name="expected_class_type"
                placeholder="Kentucky Straight Bourbon Whiskey"
                required
            >
        </label>
        <p id="classTypeError" style="display:none; color:#b71c1c; font-size:0.9em;">
            Class / Type is required.
        </p>

        <label>
            Alcohol Content
            <input type="text" name="expected_abv" placeholder="45%" required>
        </label>

        <label>
            Net Contents
            <input type="text" name="expected_net_contents" placeholder="750 mL" required>
        </label>

        <label>
            Bottler / Producer Name and Address
            <textarea name="expected_producer" placeholder="Bottled by Old Tom Distillery, Louisville, KY"></textarea>
        </label>

        <label>
            Country of Origin (Import Only)
            <input type="text" name="expected_country" placeholder="">
        </label>
    </fieldset>

    <fieldset>
        <legend>AI Adjudication</legend>

        <label>
            <input type="checkbox" name="use_llm" value="1">
            Use LLM adjudication for ambiguous / low-confidence cases
        </label>

        <p style="font-size: 0.9em; color: #555;">
            Leave unchecked while debugging to skip Bedrock.
        </p>
    </fieldset>

    <button type="submit">Verify Label</button>
</form>

<script>
document.getElementById('labelForm').addEventListener('submit', function (event) {
    const classTypeInput = document.getElementById('expected_class_type');
    const classTypeError = document.getElementById('classTypeError');

    if (!classTypeInput.value.trim()) {
        event.preventDefault();
        classTypeError.style.display = 'block';
        classTypeInput.focus();
        return;
    }

    classTypeError.style.display = 'none';
});
</script>

</body>
</html>