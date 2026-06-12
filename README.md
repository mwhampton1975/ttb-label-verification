# Alcohol Label Verification Prototype

This prototype demonstrates an automated workflow for comparing alcohol beverage label content against submitted application data.

The system uses local OCR, deterministic parsing rules, field-level comparison logic, and optional LLM-assisted review to identify whether a label appears to support the application values provided by the user.

This is a proof of concept. It is not a complete legal compliance engine and does not make final regulatory determinations.

---

## Purpose

The prototype is designed to answer a focused question:

> Does the uploaded label contain evidence that supports the application data submitted by the user?

The application does not attempt to fully replace human review. Instead, it demonstrates how label review can be partially automated by combining:

* Local OCR extraction
* Structured field parsing
* Deterministic rule-based comparison
* Conservative pass/review/fail routing
* Optional LLM review for selected ambiguous soft-field cases

---

## Prototype Scope

The prototype currently evaluates label evidence for:

* Brand name
* Class/type designation
* Alcohol content
* Net contents
* Producer, bottler, or responsible party information
* Country of origin
* Government warning statement

Application data is submitted through form fields. The label image is processed with OCR, and the resulting text is compared against the submitted application values.

---

## Core Assumptions

This prototype is based on the following assumptions:

* Application data is treated as the expected source of truth.
* OCR provides evidence from the uploaded label.
* The system verifies whether the label supports the submitted application data.
* The system does not determine whether the user submitted complete or legally sufficient application data.
* Full CFR/TTB compliance validation is out of scope for this proof of concept.
* Local OCR is preferred for speed, deployment flexibility, and reduced network dependency.
* Deterministic parsing and comparison rules are the primary review path.
* Optional LLM adjudication is reserved for ambiguous or low-confidence soft-field cases.
* Human review remains necessary for unsupported, ambiguous, or legally complex cases.

---

## Architecture Overview

The prototype is organized around three primary layers:

1. OCR extraction
2. Label parsing
3. Application comparison

Optional LLM adjudication can be added after deterministic comparison when enabled.

---

## Workflow

1. The user uploads a label image and enters application data.
2. Tesseract OCR runs locally against the uploaded file.
3. `LabelParser` converts OCR text into structured label evidence.
4. `ApplicationComparator` compares parsed evidence against expected application values.
5. Each field receives a `pass`, `review`, or `fail` status.
6. An overall recommendation is calculated from the field-level results.
7. If enabled and recommended, the optional LLM layer reviews selected ambiguous soft-field cases.
8. Debug output can be enabled to inspect OCR text, parsed output, comparison results, and LLM responses.

---

## Core Components

### Tesseract OCR

OCR is performed locally using Tesseract.

This keeps the primary review path fast and avoids requiring an external OCR service for each submission.

Tesseract output is treated as imperfect evidence. OCR may miss text, misread characters, merge lines incorrectly, or fail on low-contrast label designs.

---

### LabelParser

`LabelParser` turns OCR output into structured label evidence.

It is responsible for:

* Normalizing OCR text
* Searching for the expected brand
* Verifying class/type evidence against a prototype ruleset
* Extracting ABV
* Extracting net contents
* Checking for the government warning statement
* Verifying country of origin when applicable
* Checking producer/bottler/responsible party evidence
* Assigning parser-level confidence values and flags

The parser answers:

> What evidence did we find on the label?

The parser does not make final field-level application decisions by itself. It prepares structured evidence for comparison.

---

### ApplicationComparator

`ApplicationComparator` compares the structured output from `LabelParser` against the submitted application data.

It is responsible for:

* Comparing expected brand against OCR evidence
* Comparing expected class/type against parser-confirmed class/type evidence
* Comparing expected ABV against parsed ABV
* Comparing expected net contents against parsed net contents
* Comparing expected producer/responsible party text against OCR evidence
* Comparing expected country of origin against OCR evidence
* Producing field-level `pass`, `review`, or `fail` results

The comparator answers:

> Does the label evidence satisfy the submitted application value?

This separation keeps the workflow cleaner:

* `LabelParser` extracts and verifies evidence.
* `ApplicationComparator` evaluates that evidence against the application.

---

## Status Values

Each reviewed field receives one of three statuses.

### Pass

A field passes when the OCR evidence clearly supports the application value.

Examples:

* Expected brand appears in OCR text.
* Expected ABV matches parsed ABV.
* Expected class/type is confirmed through equivalent OCR evidence.
* Required government warning is found exactly.

### Review

A field is routed to review when evidence is partial, ambiguous, low-confidence, unsupported by the prototype ruleset, or not clearly confirmable through OCR.

Examples:

* OCR partially reads a producer name or address.
* OCR appears to contain warning language, but the exact government warning cannot be confirmed.
* Class/type evidence is not found, but the issue is not a hard deterministic mismatch.
* OCR quality prevents reliable automated confirmation.

### Fail

A field fails when required evidence is missing or clearly does not match the application value.

Examples:

* Required country of origin is provided in application data but is not found in OCR.
* Government warning is not found at all.
* ABV is expected but a conflicting ABV is found.
* Net contents are expected but a conflicting volume is found.

---

## Class / Type Verification

Class/type verification is implemented as a prototype rules lookup, not as a complete legal standards-of-identity engine.

The current design treats the application class/type value as the expected designation. The parser then checks whether OCR contains compatible evidence based on a limited data-driven ruleset.

For example, the ruleset can normalize equivalent or related values such as:

* `WHISKEY` and `WHISKY`
* `MESCAL` and `MEZCAL`
* `LIQUEUR`, `CORDIAL`, and `LIQUEUR/CORDIAL`
* `IPA` and `INDIA PALE ALE`

The system does not try to fully infer a product’s legal class/type from the label alone. This is intentional.

TTB class/type rules can depend on facts that may not be visible in OCR, including:

* Production method
* Formula
* Ingredients
* Flavoring materials
* Origin
* ABV
* Grape source or varietal requirements
* Appellation or foreign approval rules

Because of that complexity, unsupported or ambiguous class/type results are routed to `review`.

A production version would require:

* A validated class/type rule library
* Complete class/type coverage
* Reviewer-approved edge cases
* Test fixtures for each designation
* Audit logging
* Ongoing compliance review

---

## Class / Type Evidence vs. Canonical Mapping

The system distinguishes between:

* The application value submitted by the user
* The canonical class/type rule that value maps to
* The actual OCR evidence found on the label

For example:

* Application value: `IPA`
* Canonical rule display: `INDIA PALE ALE`
* OCR evidence found: `IPA`, `I.P.A.`, `INDIA PALE ALE`, or nothing

The comparison report should only show OCR-confirmed text as “found.” Canonical rule names are used internally for normalization and debugging, not as a substitute for label evidence.

---

## Brand Verification

Brand verification checks whether the expected brand appears in OCR text.

The parser uses OCR-tolerant matching to handle common issues such as:

* Punctuation differences
* Ampersand vs. “AND”
* Brand names split across multiple OCR lines
* Minor OCR spacing differences

The system is designed to compare the submitted application brand against label evidence, not to invent a brand from the label when an expected brand is provided.

---

## ABV Verification

The parser attempts to extract alcohol content from common label formats, including:

* `40% ALC/VOL`
* `40% ABV`
* `ALC. 13.5% BY VOL.`
* `80 PROOF`

The comparator then compares parsed ABV against the expected application value.

ABV extraction intentionally avoids treating unrelated marketing percentages as alcohol content. For example, a phrase such as “100% natural ingredients” should not be treated as `100% ABV`.

---

## Net Contents Verification

The parser attempts to extract net contents from common formats such as:

* `750 ML`
* `500 ML`
* `1 L`
* `16.9 FL OZ`
* `1 PINT`
* Mixed U.S. customary formats when supported by the comparator logic

The comparator evaluates whether the parsed volume supports the expected application value.

OCR failures can occur when net contents are printed in small type, low contrast, curved bottle surfaces, or white text on dark backgrounds.

---

## Government Warning Verification

The government warning check is deterministic.

The prototype uses three general outcomes:

* Exact warning found: `pass`
* Partial warning evidence found: `review`
* No warning evidence found: `fail`

The warning check allows for some OCR spacing and punctuation variation, but the full required warning language must be confirmed for an exact pass.

Partial fragments such as “Government Warning,” “Surgeon General,” pregnancy language, machinery language, or health problems language can support a `review` result when the full text cannot be confidently confirmed.

---

## Producer / Bottler / Responsible Party Verification

The producer field is treated as application-submitted text that should be supported by OCR evidence.

The system verifies whether the submitted producer, bottler, importer, or responsible party text appears on the label. It does not determine whether the user entered a complete or legally sufficient address.

For example, if the user submits only:

```text
ABC DISTILLERY
```

and OCR contains:

```text
ABC DISTILLERY RALEIGH, NC
```

the system may pass that field because the submitted value appears on the label.

Completeness of the submitted application value is considered an application data quality issue, not an OCR verification issue in this prototype.

---

## Country of Origin Verification

Country of origin is handled conservatively.

If the country field is blank:

* The system assumes domestic / United States unless OCR detects import language.
* If import language is detected and no country was provided, the country field fails.

If the country field is populated:

* The system searches OCR text for that country or recognized aliases.
* If found, the field passes.
* If not found, the field fails or routes to review depending on the available evidence.

Country of origin verification is not a full customs or import compliance review.

---

## Optional LLM Adjudication

The LLM layer is optional and disabled by default.

The deterministic OCR and rules workflow is the primary path. The LLM is used only when enabled and when the rule-based result suggests ambiguity or low confidence.

The LLM does not replace deterministic compliance checks.

It is intended to assist with selected soft-field cases, such as:

* Brand OCR noise
* Producer/responsible party OCR noise
* Human-readable explanation of ambiguous results

Hard regulatory values remain deterministic and conservative.

Examples of hard fields that should not be upgraded by LLM judgment alone:

* Class/type
* ABV
* Net contents
* Government warning
* Country of origin

The LLM may help explain why a field should remain in review, or why a soft-field OCR mismatch appears likely to be a pass.

---

## Debug Mode

The application includes optional debug output.

Debug mode may display:

* Raw OCR text
* Parsed result arrays
* Comparison arrays
* Class/type canonical mapping
* OCR matched text
* LLM raw result
* Processing time
* LLM duration

Debug mode is intended for development and testing only. It should not be part of the normal user-facing review experience.

---

## Known Limitations

This prototype has several important limitations.

### OCR Limitations

OCR may fail or produce unreliable text when labels include:

* White text on dark backgrounds
* Low contrast
* Curved bottle surfaces
* Decorative fonts
* Small type
* Foil, glare, shadows, or reflections
* Vertical or rotated text
* Complex layouts
* Multiple panels in one image

OCR failures may cause fields to route to `review` or `fail` even when the physical label may be compliant.

### Compliance Limitations

The prototype does not provide complete legal compliance validation.

It does not fully validate:

* Standards of identity
* Complete CFR requirements
* Formula requirements
* Ingredient permissibility
* Flavoring rules
* Appellation rules
* Grape-source percentages
* Imported product requirements
* Permit data
* COLA data
* Complete address sufficiency
* Label formatting, placement, contrast, type size, or separation requirements

### Application Data Limitations

The system verifies whether the label supports the application data submitted.

It does not independently determine whether the application data itself is complete, accurate, or legally sufficient.

If a user submits incomplete or incorrect application data, the system may still confirm that the submitted value appears on the label.

### Ruleset Limitations

Only a representative prototype subset of class/type rules is implemented.

The rules are organized as data so that a production version could expand coverage over time. However, the current ruleset should not be treated as complete.

Unsupported, unrecognized, or ambiguous class/type values route to `review`.

---

## Future Enhancements

Potential future improvements include:

* Replacing free-text class/type input with an IntelliSense-style lookup
* Expanding the class/type ruleset
* Adding validated test fixtures for each supported class/type
* Adding reviewer-approved edge cases
* Adding structured import vs. domestic workflows
* Separating producer, bottler, and importer fields
* Adding batch review support
* Adding CSV metadata imports for simulated submission packages
* Adding confidence thresholds by field type
* Improving OCR preprocessing
* Supporting multi-image label uploads
* Adding structured audit logs
* Adding persistent review history
* Adding reviewer comments and override tracking
* Integrating with authoritative application or permit data sources
* Adding role-based access and authentication

---

## Deployment Notes

The prototype is deployed through GitHub and pulled into an AWS Lightsail environment.

Apache serves the `public/` directory. A symbolic link may be used in the prototype environment for simplicity and maintainability.

The upload directory must be writable by the web server.

Example setup:

```bash
mkdir -p public/uploads
chown admin:www-data public/uploads
chmod 775 public/uploads
```

---

## Security Notes

This prototype should not be treated as production-ready without additional security review.

A production deployment should consider:

* File type validation
* File size limits
* Malware scanning
* Upload isolation
* Authentication
* Authorization
* HTTPS-only access
* Secure handling of AWS credentials
* Environment-based configuration
* Logging and audit controls
* Rate limiting
* Error handling that does not expose internal paths or credentials

---

## Summary

This prototype demonstrates a practical approach to alcohol label review automation.

The core idea is simple:

1. OCR extracts text from the uploaded label.
2. `LabelParser` structures the OCR evidence.
3. `ApplicationComparator` compares that evidence against application data.
4. Deterministic rules produce field-level `pass`, `review`, or `fail` results.
5. Optional LLM adjudication can assist with ambiguous soft-field cases.
6. Human review remains necessary for legal, ambiguous, unsupported, or low-confidence scenarios.

The prototype is intentionally conservative. It is designed to demonstrate workflow, architecture, and automation potential, not to replace final regulatory or legal review.
