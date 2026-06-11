Assumptions

1. Prototype focuses on automating comparison of label content against application data.

2. Full CFR compliance validation was considered out of scope for a time-constrained proof of concept.

3. OCR extraction is performed locally using Tesseract to satisfy stakeholder concerns regarding latency, network restrictions, and deployment flexibility.

4. Batch processing supports optional CSV metadata to simulate importer submissions containing both application data and associated label images.

Application is deployed via GitHub and pulled into Lightsail. Apache serves the public/ directory via a symbolic link for simplicity and maintainability in a prototype environment.

Prototype includes a representative subset of TTB
classification rules.

The classification engine was intentionally designed
as a data-driven rule system that can be expanded to
cover the complete TTB classification catalog.