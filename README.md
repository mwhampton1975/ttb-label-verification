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