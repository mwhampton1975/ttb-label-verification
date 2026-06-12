Assumptions

- Prototype focuses on automating comparison of label content against application data.
- Full CFR compliance validation was considered out of scope for a time-constrained proof of concept.
- OCR extraction is performed locally using Tesseract to satisfy stakeholder concerns regarding latency, network restrictions, and deployment flexibility.
- Batch processing supports optional CSV metadata to simulate importer submissions containing both application data and associated label images.
- TTB application approval data is provided as form fields or JSON.
- The prototype does not integrate directly with COLAs Online.
- OCR is performed locally with Tesseract to avoid external network dependency.
- Heuristic parsing is used first for speed.
- LLM review is reserved for ambiguous cases.
- This prototype does not make final legal compliance determinations.

Application is deployed via GitHub and pulled into Lightsail. Apache serves the public/ directory via a symbolic link for simplicity and maintainability in a prototype environment.

Prototype includes a representative subset of TTB
classification rules.

The classification engine was intentionally designed
as a data-driven rule system that can be expanded to
cover the complete TTB classification catalog.

This prototype handles obvious matches with local OCR and deterministic rules.
It only invokes AI when the local result is ambiguous, low-confidence, or mismatched.
The AI does not replace compliance agents. It explains edge cases and routes them to pass, fail, or review.

### Class / Type Verification
Class/type verification is implemented as a prototype rules lookup, not as a complete legal standards-of-identity engine. The code maps OCR evidence to a limited set of TTB class/type designations and compares those results to the application data using canonical equivalence rules, such as LIQUEUR/CORDIAL, MESCAL/MEZCAL, and WHISKEY/WHISKY.

Because TTB class/type rules are extensive and depend on production method, formula, origin, ABV, ingredients, and other facts not always visible on the label, this prototype intentionally treats uncertain class/type results as Review. A production system would require a validated rule library, test fixtures for each class/type, reviewer-approved edge cases, and audit logging before replacing human review.

Only a small prototype subset of TTB class/type rules is implemented. The rules are organized as data so a production version could expand, test, and validate each class/type designation. Because the full standards depend on production method, formula, ingredients, origin, ABV, and other facts not always visible in OCR, unrecognized or ambiguous class/type results are routed to Review.

Class / Type is required as part of the application data. This prototype does not attempt to fully infer a product’s class/type from the label alone. Instead, it treats the application value as the expected designation and checks whether the OCR output contains compatible class/type evidence based on a limited prototype ruleset.

This design choice keeps the core workflow focused on verification rather than full legal classification. TTB class/type rules are complex and may depend on production method, formula, ingredients, origin, ABV, flavoring, and other facts that may not be visible on the label image alone.

A future enhancement would be to replace the free-text Class / Type field with an IntelliSense-style lookup backed by a validated list of confirmed TTB class/type designations. This would reduce typos, normalize equivalent designations such as `LIQUEUR`, `CORDIAL`, and `LIQUEUR/CORDIAL`, and help ensure that application data is entered consistently before OCR verification begins.

If country field is empty:
    Assume domestic / United States.
    But if OCR contains import language, fail because the application country field was left blank.

If country field is populated:
    Search OCR for that country.
    If found, pass.
    If not found, fail or review depending confidence.

## Architecture Overview

This prototype is organized around two main layers:

### 1. LabelParser

`LabelParser` is responsible for turning OCR text into structured label evidence. It extracts or verifies fields such as brand name, class/type, ABV, net contents, producer/bottler information, country of origin, and the government warning statement.

The parser does not attempt to fully replace legal review. Instead, it creates a structured interpretation of the label by applying normalization, OCR-tolerant matching, and a limited set of prototype compliance rules.

For example, the parser can identify whether the expected brand appears in the OCR text, whether the label includes class/type language compatible with the application data, whether ABV and net contents can be found, whether a country of origin appears for an imported product, and whether the required government warning can be confirmed.

### 2. ApplicationComparator

`ApplicationComparator` takes the structured output from `LabelParser` and compares it against the submitted application data.

This separates field extraction from field evaluation. The parser answers, “What evidence did we find on the label?” The comparator answers, “Does that evidence satisfy the application expectation?”

The comparator produces field-level results using `pass`, `review`, or `fail` statuses. These field results are then combined into an overall rule-based recommendation.

## Two-Phase Verification Model

The application uses a two-phase approach to determine whether a label can pass automated review.

### Phase 1: Evidence Extraction

The OCR output is parsed into structured fields:

* Brand name
* Class/type designation
* Alcohol content
* Net contents
* Producer/bottler information
* Country of origin for imports
* Government warning statement

This phase focuses on locating and normalizing information from the label image.

### Phase 2: Application Comparison and Compliance Evaluation

The parsed evidence is compared against the application data submitted through the form. Each field receives a status:

* `pass` when the label evidence clearly supports the application value
* `review` when the evidence is partial, ambiguous, or outside the prototype ruleset
* `fail` when a required field is missing or clearly does not match

This creates two levels of confidence: first, whether the information can be extracted from the image, and second, whether the extracted information satisfies the expected application data and prototype compliance rules.

## Compliance Rule Limitations

The compliance logic in this prototype is intentionally partial. It demonstrates how rules can be represented in code, but it is not a complete or legally validated implementation of TTB class/type standards.

TTB class/type rules can depend on production method, formula, ingredients, flavoring materials, origin, ABV, and other facts that may not be visible on the label image alone. For that reason, this prototype is intentionally conservative. Ambiguous, incomplete, or unsupported results are routed to `review` rather than being automatically approved.

A production version would require a validated rule library, complete class/type coverage, reviewer-approved test cases, audit logging, and ongoing compliance review before it could replace human decision-making.

## Optional LLM Adjudication

The Bedrock LLM layer is optional and disabled unless selected on the form. It does not replace the deterministic rule-based checks. Instead, it can provide an additional explanation for ambiguous or low-confidence cases.

The core workflow remains local OCR, structured parsing, deterministic comparison, and conservative pass/review/fail evaluation.

The deterministic OCR + rules workflow is the primary path and is designed for fast processing. Bedrock adjudication is optional, disabled by default, and only used for ambiguous or low-confidence cases. Because Bedrock adds network and model latency, it is treated as an explanation/review assist layer rather than part of the required fast path.

For wine varietals such as Chardonnay, Muscat, or Riesling, the prototype verifies that the expected varietal designation appears in OCR text, but it does not validate grape-source percentages, appellation requirements, or foreign-country approval rules.

Limitations:
OCR fails to read white text on dark backgrounds


LabelParser:
Extracts fields and detects whether expected fields appear in OCR.

ApplicationComparator:
Compares expected vs parsed and decides field-level pass/fail/review in a cleaner report.

Exact warning found = pass
warning_partial_found = true but exact not found → review
no warning evidence → fail

Architecture
1. User uploads label + enters application data

2. Tesseract OCR runs locally

3. LabelParser extracts:
   - brand candidate
   - expected brand found/not found
   - class/type
   - ABV
   - net contents
   - warning exact/partial/not found
   - flags
   - confidence values

4. ApplicationComparator compares:
   - expected brand vs OCR/parsed brand
   - expected class/type vs parsed designation
   - expected ABV vs parsed ABV
   - expected net contents vs parsed net contents
   - producer/country if available

5. Decision gate:
   - if all deterministic fields pass with high confidence → no AI call
   - if anything fails/reviews/low confidence → call LLM

6. LLM receives structured JSON only:
   - expected application data
   - OCR text
   - parser output
   - comparator output
   - applicable rules summary

7. LLM returns strict JSON:
   - final_recommendation
   - field decisions
   - reasons
   - whether human review is needed

8. Store review item or display result


Permissions:
mkdir -p public/uploads
chown admin:www-data public/uploads
chmod 775 public/uploads