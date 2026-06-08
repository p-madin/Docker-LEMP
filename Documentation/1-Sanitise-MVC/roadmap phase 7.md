# Phase 7: Advanced Interaction (AJAX & Hybrid Validation)

## Status: COMPLETED ✅

## Objective
The goal of Phase 7 is to bridge the gap between our database-defined validation rules (`tcRules`) and the user's browser experience. We will implement a lightweight, native Javascript validation engine that provides real-time feedback while maintaining strict server-side parity via AJAX.

## Key Deliverables

### 1. The Validator Bridge
- **`FormComponent` Serialization**: Update the `FormComponent` to parse `tcRules` from the database and inject them as `data-rules` attributes on all input elements.
- **Rule Metadata**: Ensure the client-side has access to rule parameters (e.g., `min:5`, `max:20`, `match:password`).

### 2. `validate.js` Native Engine
- **No Dependencies**: Implement a native ES6 Javascript validator (no jQuery).
- **Event-Driven**: Validate on `blur` and `input` events for immediate feedback.
- **Core Rules**: Implement browser equivalents for:
    - `required`
    - `min` / `max` (length)
    - `numeric`
    - `email`
    - **`match:fieldName`** (specifically for **Confirm Password** validation).

### 3. Hybrid Validation Strategy
- **Client-Side**: Handles instant structural validation (length, type, match).
- **Server-Side Only**: Handles complex logical checks (e.g., **Unique Username** checking via database lookup).
- **Unified Feedback**: Both client-side errors and server-side errors will be rendered using a consistent DOM-injection pattern into `.validation-error` containers.

### 4. AJAX Hydration (`fetch`)
- **JSON Submission**: Transition forms to use the `fetch()` API for submission.
- **Dynamic Response**: 
    - **On Failure**: Server returns a JSON object containing specific field errors.
    - **On Success**: Server returns a "Success Component" or a redirect instruction.

## Critical Requirements
- **Server-Side Supremacy**: Client-side validation is for UX only. The server must ALWAYS perform the same checks (using our existing `FormValidation` class) before any data persistence.
- **Error Consistency**: Ensure that errors returned via AJAX look identical to the "flash errors" used in standard page reloads.

---

## Metrics
- **Complexity**: High (Parity between PHP and JS logic)
- **Primary Files**: `Static/validate.js`, `FormComponent.php`, `FormValidation.php`
- **Next Milestone**: Successful AJAX-based registration with real-time "Username Taken" feedback.
