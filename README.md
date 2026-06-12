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

LabelParser:
Extracts fields and detects whether expected fields appear in OCR.

Class/type verification is implemented as a prototype rules lookup, not as a complete legal standards-of-identity engine. The code maps OCR evidence to a limited set of TTB class/type designations and compares those results to the application data using canonical equivalence rules, such as LIQUEUR/CORDIAL, MESCAL/MEZCAL, and WHISKEY/WHISKY.

Because TTB class/type rules are extensive and depend on production method, formula, origin, ABV, ingredients, and other facts not always visible on the label, this prototype intentionally treats uncertain class/type results as Review. A production system would require a validated rule library, test fixtures for each class/type, reviewer-approved edge cases, and audit logging before replacing human review.

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