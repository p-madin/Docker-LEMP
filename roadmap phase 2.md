# Phase 2: User Input Validation (xmlForm Extensions)

## Goal
Extend the `xmlForm` class to support robust server-side validation and graceful degradation to HTML5 client-side constraints.

## Requirements
- **Server-Side Engine**: A `Validator` class that processes rules like `required`, `min:X`, `max:X`, `numeric`, `email`, and `match:field`.
- **UI Integration**: `xmlForm` should capture these rules during `addRow()` and automatically:
    - Display error messages next to fields on failure.
    - Map rules to HTML5 attributes (`required`, `minlength`, `maxlength`, `type="number"`) for client-side feedback.
- **Form Isolation**: Validation errors should be scoped to the specific form being submitted (e.g., Login errors should not appear on the Registration form).
- **Data Retention**: Preserving user-entered data (except passwords) after validation failure to avoid re-entry.

## Components
- [Validator.php](file:///c:/Workspace/Docker%20Workspace/Docker-LEMP/app/Class%20files/Security/Validator.php)
- [xmlForm.php](file:///c:/Workspace/Docker%20Workspace/Docker-LEMP/app/Class%20files/xmlForm.php)
- [ValidationTest.php](file:///c:/Workspace/Docker%20Workspace/Docker-LEMP/app/Test/Test%20Suite/ValidationTest.php)

## Status: COMPLETE
Phase 2 and its refinements (Phase 2.5, 2.6, 2.7) have been implemented and verified in the Docker environment.

## Implementation Details
The validation system is built on a decoupled architecture:
1.  **Metadata Definition**: Rules are defined during form construction in the UI layer (`xmlForm::addRow`).
2.  **Engine Processing**: The `Validator` class parses these rules and executes specific validation logic (`required`, `numeric`, `match`, etc.).
3.  **Flash Cache**: Validation errors and user input are stored in the session but "flashed" (retrieved once and then immediately destroyed) to prevent stale state.
4.  **Graceful Degradation**: Server-side rules are automatically mapped to standard HTML5 attributes for instant browser-level feedback.

### Future Projects & Enhancements
- **Advanced Client-Side Validation**: Implement non-standard HTML5 mechanisms (e.g., custom JavaScript for `password == confirm_password` matching, `match:field`) to provide real-time UI cues before submission.
- **Dynamic Form Population**: Develop a `fetch()` based system to retrieve form definitions as XML or JSON from the server and render the DOM dynamically using `document.createElement()`.
- **Database-Driven Form Schemas**: Leverage the database to store common form definitions as JSON arrays. 
    - **Example (RegistrationForm)**:
      ```json
      {"RegistrationForm":[
        {"username", "Username:", "text", null, "required|min:3"},
        {"password", "Password:", "password", null, "required|min:6"},
        {"confirm_password", "Confirm Password:", "password", null, "match:password"},
        {"name", "Name:", "text", null, "required"},
        {"age", "Age:", "number", null, "required|numeric"},
        {"city", "City:", "text"},
        {"email", "Email:", "email", null, "required|email"}
      ]}
      ```
- **Asynchronous Submissions**: Implement a headless form submission flow using `fetch()` that handles JSON responses and dynamically updates validation messages without a page reload.
