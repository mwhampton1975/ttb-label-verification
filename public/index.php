<?php
?>
<!DOCTYPE html>
<html>
<head>
    <title>TTB Label OCR Prototype</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 30px auto;
            line-height: 1.4;
            color: #222;
            background: #fafafa;
        }

        .page-header {
            margin-bottom: 24px;
        }

        .page-header h1 {
            margin-bottom: 6px;
        }

        .page-header p {
            margin-top: 0;
            color: #555;
            max-width: 720px;
        }

        form {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 24px;
        }

        fieldset {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 18px;
            margin-bottom: 22px;
            background: #fff;
        }

        legend {
            font-weight: bold;
            padding: 0 8px;
        }

        label {
            display: block;
            margin-bottom: 16px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="file"],
        textarea {
            display: block;
            width: 100%;
            box-sizing: border-box;
            margin-top: 6px;
            padding: 10px;
            border: 1px solid #bbb;
            border-radius: 4px;
            font-size: 15px;
            font-family: Arial, sans-serif;
        }

        textarea {
            min-height: 90px;
            resize: vertical;
        }

        .hint {
            margin-top: -8px;
            margin-bottom: 16px;
            font-size: 0.9em;
            color: #555;
        }

        .notice {
            padding: 12px 14px;
            border-radius: 4px;
            margin-bottom: 18px;
            font-size: 0.95em;
        }

        .notice.info {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            color: #0d47a1;
        }

        .notice.warning {
            background: #fff8e1;
            border: 1px solid #ffe082;
            color: #7a5200;
        }

        .checkbox-label {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .checkbox-label input {
            margin-top: 3px;
        }

        .error {
            display: none;
            color: #b71c1c;
            font-size: 0.9em;
            margin-top: -10px;
            margin-bottom: 16px;
        }

        button {
            background: #1f4e79;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 12px 18px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #173b5c;
        }

        .required-note {
            font-size: 0.9em;
            color: #555;
            margin-top: 0;
        }
    </style>
</head>
<body>

<div class="page-header">
    <h1>Alcohol Label Verification</h1>
    <p>
        Upload a label image and provide the expected application data. The prototype will run OCR,
        parse the label evidence, and compare it against the submitted application fields.
    </p>
</div>

<div class="notice info">
    Core verification uses local OCR and deterministic rules. Optional LLM adjudication is disabled by default.
</div>

<form id="labelForm" action="process.php" method="post" enctype="multipart/form-data">
    <p class="required-note">Fields marked by the browser as required must be completed before verification.</p>

    <fieldset>
        <legend>Label Image</legend>

        <label>
            Upload label image
            <input type="file" name="label" accept="image/*,.pdf" required>
        </label>

        <p class="hint">
            Use a clear label image when possible. OCR quality directly affects the verification result.
        </p>
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
                placeholder="Straight Rye Whisky"
                required
            >
        </label>
        <p id="classTypeError" class="error">
            Class / Type is required.
        </p>
        <p class="hint">
            This prototype verifies the submitted class/type against a limited ruleset. Future versions could use an IntelliSense lookup of confirmed class/type designations.
        </p>

        <label>
            Alcohol Content
            <input type="text" name="expected_abv" placeholder="45%" required>
        </label>
        <p class="hint">
            You may enter values like 45 or 45%. The label itself must still show alcohol content clearly.
        </p>

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
            <input type="text" name="expected_country" placeholder="Canada">
        </label>
        <p class="hint">
            Leave blank for domestic products. If the label appears to be imported, the country of origin should be provided.
        </p>
    </fieldset>

    <fieldset>
        <legend>AI Adjudication</legend>

        <label class="checkbox-label">
            <input type="checkbox" name="use_llm" value="1">
            <span>Use LLM adjudication for ambiguous / low-confidence cases</span>
        </label>

        <div class="notice warning">
            Bedrock adjudication is optional and may add approximately 3 seconds to processing time.
            Leave unchecked for the fastest local rule-based verification.
        </div>
    </fieldset>

    <fieldset>
        <legend>Debug Output</legend>

        <label class="checkbox-label">
            <input type="checkbox" name="show_debug" value="1">
            <span>Show debug output</span>
        </label>

        <p class="hint">
            Shows raw OCR text, parser output, comparison arrays, LLM raw results, and timing details.
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