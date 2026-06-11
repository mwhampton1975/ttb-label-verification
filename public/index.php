<?php
?>
<!DOCTYPE html>
<html>
<head>
    <title>TTB Label OCR Prototype</title>
</head>
<body>

<h2>Upload Label Image</h2>

<form action="process.php" method="post" enctype="multipart/form-data">
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
            <input type="text" name="expected_class_type" placeholder="Kentucky Straight Bourbon Whiskey" required>
        </label>

        <label>
            Alcohol Content
            <input type="text" name="expected_abv" placeholder="45% Alc./Vol. (90 Proof)" required>
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
            Country of Origin
            <input type="text" name="expected_country" placeholder="United States">
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

</body>
</html>