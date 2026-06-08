# Phase 10: Enhance testing functionality (Source Code Draft)

This document details improvements to the custom XML-based integration test framework, primarily introducing dynamic state extraction and substitution.

## 1. Implementation Overview

To test the full lifecycle of entities within the platform (e.g., creating a form, editing its columns, then deleting it), the testing framework required a method to track dynamically generated primary keys (auto-increment IDs) across test steps, moving away from brittle string-based selectors.

## 2. Core Enhancements

### Suite Variable Extraction
In `xmlDomTest.php`, the test runner was enhanced to recognize `<saveSuiteVariable>` tags within a step's `<expected>` block.
When this tag is present, the runner submits the form via an AJAX request (`X-Requested-With: XMLHttpRequest`). Action Controllers respond with JSON, containing a `dependency` field (the newly generated PK). The runner intercepts this JSON and stores the PK in the `$this->suiteVariables` registry.

```xml
<step>
  <action>FillForm</action>
  <selector>#editFormFormComponent</selector>
  <parameters>...</parameters>
  <submit>true</submit>
  <expected>
    <!-- Instructs the runner to capture the returned 'dependency' PK -->
    <saveSuiteVariable>testFormID</saveSuiteVariable>
  </expected>
</step>
```

### Variable Substitution
Before executing actions like `NavigateTo`, `Click`, or `AssertText`, the runner invokes `$this->substituteVariables()`, seamlessly interpolating stored PKs into URLs, CSS selectors, and assertion strings.

```xml
<step>
  <action>NavigateTo</action>
  <url>/edit_form?id={{testFormID}}</url>
</step>
<step>
  <action>Click</action>
  <selector>#delete-form-{{testFormID}}</selector>
</step>
```

### Data Provider Standardization
To support these tests, all Data Providers (`FormDataProvider.php`, `NavbarDataProvider.php`, etc.) were refactored. The `idSuffixKey` for their interactive buttons (like Edit/Delete) was updated to use the true Primary Key (e.g., `tfPK`) rather than potentially non-unique strings (e.g., `tfName`).

## 3. Benefits & Outcomes
- **Robust Contracts**: `test-suite.xml` definitions are completely agnostic to string collisions.
- **State Independence**: Tests no longer fail due to dirty databases from previous crashes, as they explicitly target the unique ID of the entity they *just* created.
