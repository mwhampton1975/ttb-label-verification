# Alcohol Label Verification Prototype

This prototype demonstrates a workflow for comparing alcohol beverage label content against submitted application data.

The app uses local OCR, deterministic parsing rules, field-level comparison logic, and optional LLM-assisted review to determine whether a label appears to support the values entered by the user.

This is a proof of concept. It is not a complete legal compliance engine and does not make final regulatory determinations.

## Setup

### Requirements

* PHP
* Apache
* Tesseract OCR
* Composer
* AWS credentials, only if using optional Bedrock adjudication

### Install dependencies

```bash
composer install
```

### Create upload directory

```bash
mkdir -p public/uploads
chown admin:www-data public/uploads
chmod 775 public/uploads
```

### Run

Serve the `public/` directory through Apache or a local PHP server.

Example local command:

```bash
php -S localhost:8000 -t public
```

Then open:

```text
http://localhost:8000
```

## How to Use

1. Upload a label image or select a demo sample.
2. Enter the expected application data.
3. Click **Verify Label**.
4. Review the field-level results.
5. Optional: enable debug output to inspect OCR text, parser output, comparison results, and LLM response data.

## Approach

The prototype uses a two-step verification model.

### 1. LabelParser

`LabelParser` converts OCR text into structured label evidence.

It checks for:

* Brand name
* Class/type designation
* Alcohol content
* Net contents
* Producer, bottler, or responsible party information
* Country of origin
* Government warning statement

### 2. ApplicationComparator

`ApplicationComparator` compares the parsed label evidence against the submitted application data.

Each field receives one of three statuses:

* `pass`: label evidence supports the application value
* `review`: evidence is missing, ambiguous, low-confidence, or outside the prototype ruleset
* `fail`: required evidence is missing or clearly conflicts with the application value

## Tools Used

* PHP for the prototype application
* Tesseract OCR for local text extraction
* Deterministic parser and comparator classes for rule-based review
* AWS Bedrock for optional LLM adjudication
* Apache / AWS Lightsail for prototype deployment

## Optional LLM Review

LLM adjudication is optional and disabled by default.

The deterministic OCR and rules workflow is the primary review path. The LLM is only used to assist with selected ambiguous soft-field cases, such as brand or producer text affected by OCR noise.

The LLM does not override hard regulatory fields such as:

* Class/type
* ABV
* Net contents
* Government warning
* Country of origin

## Assumptions

* Application data is treated as the expected source of truth.
* OCR text is treated as label evidence.
* The app verifies whether the label supports the submitted application data.
* The app does not validate whether the submitted application data itself is complete or legally sufficient.
* Local OCR is used for speed and reduced external dependency.
* Human review is still required for ambiguous, unsupported, or legally complex cases.

## Limitations

This prototype does not provide full TTB or CFR compliance validation.

It does not fully validate:

* Complete class/type standards
* Formula requirements
* Ingredient or flavoring rules
* Grape-source or appellation rules
* Import compliance requirements
* Permit data
* COLA data
* Label placement, formatting, type size, or contrast requirements

OCR may also fail on labels with:

* Low contrast
* Small text
* Decorative fonts
* Curved surfaces
* Glare or shadows
* White text on dark backgrounds
* Complex multi-panel layouts

Unsupported or ambiguous results are intentionally routed to `review`.

## Sample Data

Demo samples are included so reviewers can quickly test known scenarios without preparing their own images or application data.

Users may also upload their own label image and enter custom application values manually.

## Future Improvements

Potential production enhancements include:

* Full validated class/type ruleset
* Structured class/type lookup instead of free-text entry
* Separate producer, bottler, and importer fields
* Batch upload support
* Audit logging
* Reviewer comments and overrides
* Authentication and role-based access
* Stronger file upload security
* Integration with authoritative application or permit data sources
