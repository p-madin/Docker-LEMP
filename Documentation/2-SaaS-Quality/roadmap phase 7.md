# Phase 7: Page Builder & Block Editor

This document outlines the architecture for the dynamic Page Builder and its client-side Block Editor, enabling the construction of arbitrary CMS page structures.

## 1. Implementation Overview

To allow users to craft rich, custom web pages without writing raw HTML, a vanilla Javascript block editor was developed. It features drag-and-drop mechanics, nested block containers, and in-place content editing.

## 2. Core Enhancements

### Entity Relational Model
The database structure relies on three core tables to define page hierarchies:
- `tblPages`: Stores the top-level route (e.g., `pagSlug = /about-us`), title, and metadata.
- `tblElements`: Stores atomic content blocks (e.g., `eleType = Text`, `eleContent = Hello World`). It includes a self-referential `eleParentFK` column to allow blocks to be nested inside Container blocks (like Flexboxes).
- `brgPageElements`: A bridge table linking `tblElements` to `tblPages`, enforcing the vertical display order via `pelOrder`.

### Client-Side Block Editor
The user interface (`builder.js`) operates entirely in the browser:
1. **Drag & Drop Canvas**: Users drag components (Text, Containers, Buttons) from a sidebar directly into a visual representation of the page.
2. **Contextual Property Panel**: Selecting an element opens a right-side panel where CSS classes and ID attributes can be assigned.
3. **In-Place Editing**: Text blocks support `contenteditable=true`, allowing users to type and format content natively on the canvas.

## 3. Benefits & Outcomes
- **Total Flexibility**: The `eleParentFK` relationship enables infinitely deep layouts (e.g., placing text inside a button, inside a column, inside a row, inside a section).
- **Security**: Inner text edits are explicitly prohibited from rendering raw HTML entities, protecting the CMS from stored XSS vectors.
