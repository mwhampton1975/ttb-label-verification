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

The LLM is intentionally not the primary decision-maker. The rules engine is primary, and the LLM serves as an escalation path for ambiguous cases.

LabelParser:
Extracts fields and detects whether expected fields appear in OCR.

ApplicationComparator:
Compares expected vs parsed and decides field-level pass/fail/review in a cleaner report.

Exact warning found = pass
warning_partial_found = true but exact not found → review
no warning evidence → fail

Architecture
Phase 1
--------
OCR (Tesseract)

Phase 2
--------
Rule-based extraction
TTB designation engine

Phase 3
--------
Application comparison engine

Phase 4
--------
LLM adjudication only when:
  - confidence < 80
  - classification failed
  - OCR ambiguity detected
  - application mismatch found

Phase 5
--------
Agent review queue