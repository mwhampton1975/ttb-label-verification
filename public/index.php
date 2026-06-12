<?php
$samples = [
    '12345_imports_rum_liqueur' => [
        'label' => '12345 Imports Rum Liqueur',
        'image' => '12345_rum_liqueur.jpg',
        'brand' => '12345 Imports',
        'class_type' => 'LIQUEUR',
        'abv' => '18',
        'net_contents' => '200 ML',
        'producer' => '12345 Imports Miami, FL',
        'country' => 'Canada',
        'notes' => 'Demonstrates liqueur class/type evidence, imported-product country handling, and Happy Path testing.',
    ],
    'abc_straight_rye_whisky' => [
        'label' => 'ABC Straight Rye Whisky',
        'image' => 'abc_whisky.jpg',
        'brand' => 'ABC Distillery',
        'class_type' => 'WHISKY',
        'abv' => '45',
        'net_contents' => '750 ML',
        'producer' => 'ABC Distillery Frederick, MD',
        'country' => '',
        'notes' => 'Demonstrates whisky class/type evidence, and government warning validation strictness with slight OCR misreading.',
    ],
    'stormchaser' => [
        'label' => 'Stormchaser Chardonnay',
        'image' => 'stormchaser.jpg',
        'brand' => 'LIGHTHOUSE',
        'class_type' => 'CHARDONNAY',
        'abv' => '13.5',
        'net_contents' => '750 ML',
        'producer' => 'LIGHTHOUSE VINTNERS KINGSTON, NY',
        'country' => '',
        'notes' => 'Demonstrates wine varietal class/type verification, ABV parsing, and producer review.',
    ],
    'malt_and_hop_ipa' => [
        'label' => 'Malt & Hop India Pale Ale',
        'image' => 'malt_and_hop_india_pale_ale.png',
        'brand' => 'MALT & HOP BREWERY',
        'class_type' => 'IPA',
        'abv' => '4',
        'net_contents' => '500 ML',
        'producer' => 'MALT & HOP BREWERY HYATTSVILLE, MD',
        'country' => '',
        'notes' => 'Demonstrates malt beverage class/type alias handling, government warning detection, and white lettering OCR limitations.',
    ],
    'honey_huckleberry_pie' => [
        'label' => 'Honey Huckleberry Pie',
        'image' => 'honey_huckleberry_pie.png',
        'brand' => 'MALT & HOP BREWERY',
        'class_type' => 'ALE',
        'abv' => '5',
        'net_contents' => '1 PINT 0.9 FL. OZ.',
        'producer' => 'MALT & HOP BREWERY HYATTSVILLE, MD',
        'country' => '',
        'notes' => 'Demonstrates ALE class/type handling. White lettering OCR limitations.',
    ],
    'tropical_chimp' => [
        'label' => 'Tropical Chimp',
        'image' => 'tropical_chimp.jpg',
        'brand' => 'BROTHER CHIMP BREWING',
        'class_type' => 'IPA',
        'abv' => '6.7',
        'net_contents' => '1 PINT',
        'producer' => 'BROTHER CHIMP BREWING 1059 West Orchard Road North Aurora, IL 60542',
        'country' => '',
        'notes' => 'Demonstrates OCR limitations with curved lettering and obscure fonts',
    ],
    'hawks_shadow' => [
        'label' => 'Hawk’s Shadow',
        'image' => 'hawks_shadow.jpg',
        'brand' => 'Hawk’s Shadow Estate',
        'class_type' => 'Wine',
        'abv' => '13.68',
        'net_contents' => '375',
        'producer' => 'Hawk’s Shadow Estate Dripping Springs, Texas',
        'country' => '',
        'notes' => 'Demonstrates limitations of curved images using actual bottle photos with blured edges.',
    ],
    'bombay_sapphire' => [
        'label' => 'Bombay Sapphire Gin',
        'image' => 'bombay_sapphire.jpg',
        'brand' => 'BOMBAY SAPPHIRE',
        'class_type' => 'GIN',
        'abv' => '40',
        'net_contents' => '700 ML',
        'producer' => 'The Bombay Sapphire Distillery Laverstoke Mill, RG28 7NR, UK',
        'country' => 'England',
        'notes' => 'Demonstrates gin class/type evidence, imported-product country handling, and OCR noise.',
    ],
];
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
        select {
            display: block;
            width: 100%;
            box-sizing: border-box;
            margin-top: 6px;
            padding: 10px;
            border: 1px solid #bbb;
            border-radius: 4px;
            font-size: 15px;
            font-family: Arial, sans-serif;
            background: #fff;
        }

        .sample-notes {
            display: none;
            margin-top: 10px;
            padding: 12px 14px;
            border-radius: 4px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            color: #444;
            font-size: 0.95em;
        }

        .sample-active {
            background: #e8f5e9;
            border: 1px solid #a5d6a7;
            color: #1b5e20;
        }
        .upload-wrapper {
            display: block;
        }

        .sample-preview {
            display: none;
            margin-top: 12px;
            padding: 14px;
            border: 1px solid #a5d6a7;
            border-radius: 5px;
            background: #e8f5e9;
        }

        .sample-preview img {
            display: block;
            max-width: 100%;
            max-height: 420px;
            object-fit: contain;
            margin-top: 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #fff;
        }

        .sample-preview strong {
            color: #1b5e20;
        }

        .sample-preview-actions {
            margin-top: 12px;
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
    <fieldset>
        <legend>Demo Sample</legend>

        <label>
            Load a sample scenario
            <select id="sampleSelector">
                <option value="">Manual upload / custom application data</option>
                <?php foreach ($samples as $sampleId => $sample): ?>
                    <option value="<?php echo htmlspecialchars($sampleId); ?>">
                        <?php echo htmlspecialchars($sample['label']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <input type="hidden" id="sample_label" name="sample_label" value="">

        <p class="hint">
            Select a sample to auto-fill known application data and run the demo without uploading a file.
            You may still upload your own label and enter custom values manually.
        </p>

        <div id="sampleNotes" class="sample-notes"></div>
    </fieldset>
    <p class="required-note">Fields marked by the browser as required must be completed before verification.</p>

    <fieldset>
        <legend>Label Image</legend>

        <div id="uploadWrapper" class="upload-wrapper">
            <label>
                Upload label image
                <input id="labelUpload" type="file" name="label" accept="image/*,.pdf" required>
            </label>

            <p class="hint">
                Use a clear label image when possible. OCR quality directly affects the verification result.
            </p>
        </div>

        <div id="samplePreview" class="sample-preview">
            <strong>Demo sample selected. No file upload is required.</strong>
            <p id="samplePreviewText" class="hint" style="margin-top: 8px; margin-bottom: 0;"></p>
            <img id="samplePreviewImage" src="" alt="Selected demo sample preview">

            <div class="sample-preview-actions">
                <button type="button" id="clearSampleButton">Use manual upload instead</button>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Application Data</legend>

        <label>
            Brand Name
            <input type="text" id="expected_brand" name="expected_brand" placeholder="OLD TOM DISTILLERY" required>
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
            <input type="text" id="expected_abv" name="expected_abv" placeholder="45%" required>
        </label>
        <p class="hint">
            You may enter values like 45 or 45%. The label itself must still show alcohol content clearly.
        </p>

        <label>
            Net Contents
            <input type="text" id="expected_net_contents" name="expected_net_contents" placeholder="750 mL" required>
        </label>

        <label>
            Bottler / Producer Name and Address
            <textarea id="expected_producer" name="expected_producer" placeholder="Bottled by Old Tom Distillery, Louisville, KY"></textarea>
        </label>

        <label>
            Country of Origin (Import Only)
            <input type="text" id="expected_country" name="expected_country" placeholder="">
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
            Bedrock adjudication is optional and may add approximately 4 seconds to processing time.
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
const sampleData = <?php echo json_encode($samples, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?>;

const labelForm = document.getElementById('labelForm');
const sampleSelector = document.getElementById('sampleSelector');
const sampleLabelInput = document.getElementById('sample_label');
const sampleNotes = document.getElementById('sampleNotes');
const labelUpload = document.getElementById('labelUpload');
const uploadWrapper = document.getElementById('uploadWrapper');
const samplePreview = document.getElementById('samplePreview');
const samplePreviewImage = document.getElementById('samplePreviewImage');
const samplePreviewText = document.getElementById('samplePreviewText');
const clearSampleButton = document.getElementById('clearSampleButton');

const fields = {
    brand: document.getElementById('expected_brand'),
    class_type: document.getElementById('expected_class_type'),
    abv: document.getElementById('expected_abv'),
    net_contents: document.getElementById('expected_net_contents'),
    producer: document.getElementById('expected_producer'),
    country: document.getElementById('expected_country')
};

sampleSelector.addEventListener('change', function () {
    const sampleId = sampleSelector.value;

    if (!sampleId || !sampleData[sampleId]) {
        clearSampleSelection();
        return;
    }

    const sample = sampleData[sampleId];

    sampleLabelInput.value = sample.image || '';

    fields.brand.value = sample.brand || '';
    fields.class_type.value = sample.class_type || '';
    fields.abv.value = sample.abv || '';
    fields.net_contents.value = sample.net_contents || '';
    fields.producer.value = sample.producer || '';
    fields.country.value = sample.country || '';

    labelUpload.required = false;
    labelUpload.value = '';

    uploadWrapper.style.display = 'none';

    samplePreview.style.display = 'block';
    samplePreviewImage.src = 'sample-image.php?file=' + encodeURIComponent(sample.image || '');
    samplePreviewText.textContent = sample.notes || 'This demo sample will be used for verification.';

    sampleNotes.style.display = 'block';
    sampleNotes.classList.add('sample-active');
    sampleNotes.textContent = sample.notes || 'Sample loaded.';
});

labelUpload.addEventListener('change', function () {
    if (labelUpload.files.length > 0) {
        sampleSelector.value = '';
        sampleLabelInput.value = '';
        labelUpload.required = true;

        sampleNotes.style.display = 'none';
        sampleNotes.classList.remove('sample-active');
        sampleNotes.textContent = '';
    }
});

function clearSampleSelection() {
    sampleSelector.value = '';
    sampleLabelInput.value = '';

    labelUpload.required = true;

    uploadWrapper.style.display = 'block';

    samplePreview.style.display = 'none';
    samplePreviewImage.src = '';
    samplePreviewText.textContent = '';

    sampleNotes.style.display = 'none';
    sampleNotes.classList.remove('sample-active');
    sampleNotes.textContent = '';
}

clearSampleButton.addEventListener('click', function () {
    clearSampleSelection();
    labelUpload.focus();
});

labelForm.addEventListener('submit', function (event) {
    const classTypeInput = document.getElementById('expected_class_type');
    const classTypeError = document.getElementById('classTypeError');

    if (!classTypeInput.value.trim()) {
        event.preventDefault();
        classTypeError.style.display = 'block';
        classTypeInput.focus();
        return;
    }

    if (!sampleLabelInput.value && !labelUpload.files.length) {
        event.preventDefault();
        alert('Please upload a label image or select a demo sample.');
        labelUpload.focus();
        return;
    }

    classTypeError.style.display = 'none';
});
</script>

</body>
</html>