---
name: accessibility
description: "Use when: adding or modifying ARIA attributes, labels, keyboard navigation, focus indicators, semantic HTML, or testing accessibility. Applies to MySQLGrid output generation, assets/css/mysqlgrid.css, and accessibility-specific tests."
applyTo:
  - "src/MySQLGrid.php"
  - "assets/css/*.css"
  - "tests/*XssTest.php"
  - "tests/*CrudIntegrationTest.php"
---

# Accessibility Instructions for MySQLGrid

## Goals

MySQLGrid aims to meet **WCAG 2.2 Level AA** for data grids:

- Semantic HTML structure
- Keyboard navigation (no mouse required)
- Clear focus indicators
- Screen reader support (ARIA)
- Color and motion accessibility
- Readable text and sufficient contrast

This document defines how new features or changes must integrate accessibility from the start.

## Semantic HTML Structure

### Table Markup

The grid renders as an HTML table with proper semantic elements:

```html
<table class="phpmysqlgrid">
  <thead>
    <tr>
      <th>Column Header</th>
    </tr>
  </thead>
  <tbody>
    <tr data-id="1">
      <td>Row Data</td>
    </tr>
  </tbody>
  <tfoot>
    <tr>
      <td>Footer/Pagination</td>
    </tr>
  </tfoot>
</table>
```

**Rules:**

- Always use `<table>`, `<thead>`, `<tbody>`, `<tfoot>`.
- Header cells in `<thead>` must be `<th>`, not `<td>`.
- Data cells in body must be `<td>`.
- **Do not nest `<table>` inside `<table>` cells** (Phase 0 cleanup will remove the nested navigation table).

### Header Row

Column headers in `<thead>` should describe the column:

```html
<th>Name</th>
<th>Email</th>
<th>Active</th>
<th>Actions</th>
```

If a column header is not human-readable (e.g., internal ID), use a hidden text label:

```html
<th><span class="visually-hidden">User ID</span>ID</th>
```

Or keep scope semantics clear via `scope` attribute:

```html
<th scope="col">Name</th>
<th scope="col">Email</th>
```

## ARIA Attributes

### Table

```html
<table class="phpmysqlgrid" role="grid" aria-label="User Management Grid">
  <!-- or if context is clear -->
  <table class="phpmysqlgrid" role="grid">
```

**Guidelines:**
- Use `role="grid"` for data grids (user can sort, filter, select rows).
- Provide `aria-label` or `aria-labelledby` if table purpose is not obvious from surrounding content.

### Pagination/Navigation

The footer navigation should be marked as such:

```html
<nav class="phpmysqlgrid-pagination" aria-label="Pagination">
  <button class="phpmysqlgrid-pagination-prev" aria-label="Previous page">←</button>
  <ol class="phpmysqlgrid-pagination-list">
    <li><a href="?page=1" aria-current="page">1</a></li>
    <li><a href="?page=2">2</a></li>
  </ol>
  <button class="phpmysqlgrid-pagination-next" aria-label="Next page">→</button>
</nav>
```

### Form Controls in Edit/Add Mode

When rendering text inputs, selects, or file uploads:

```html
<!-- Text input with associated label -->
<label for="field_name">Name:</label>
<input id="field_name" type="text" name="field_name">

<!-- Select with label -->
<label for="field_status">Status:</label>
<select id="field_status" name="field_status">
  <option value="">-- Select --</option>
  <option value="active">Active</option>
</select>

<!-- Required field indicator -->
<label for="field_email">
  Email <span aria-label="required">*</span>:
</label>
<input id="field_email" type="email" required>
```

**Rules:**
- Every form input must have a `<label>` with `for` attribute.
- Use `aria-label` only if a visible label is not possible (e.g., icon-only buttons).
- Mark required fields with `required` attribute AND visible indicator.
- Use correct `type` attribute (`email`, `number`, `date`, etc.).

### Deterministic and Safe ID Composition

When generating IDs in PHP output, compose them deterministically from a stable grid prefix and a field/action suffix. Keep IDs unique per page and valid for HTML/CSS/JS selectors.

```php
// Recommended pattern: stable prefix + semantic suffix
$formId = $this->sanitizeHtmlId((string) $this->name) . "_form";
$fieldId = $this->sanitizeHtmlId((string) $this->name) . "_field_name";

echo '<form id="' . htmlspecialchars($formId, ENT_QUOTES, "UTF-8") . '">';
echo '<label for="' . htmlspecialchars($fieldId, ENT_QUOTES, "UTF-8") . '">';
echo htmlspecialchars($this->txtFieldName, ENT_QUOTES, "UTF-8") . '</label>';
echo '<input id="' . htmlspecialchars($fieldId, ENT_QUOTES, "UTF-8") . '" name="field_name">';
```

Rules for ID composition:
- Base IDs on a stable grid identifier (`$this->name`) plus a semantic suffix (`_form`, `_filter_email`, `_action_edit`).
- Sanitize dynamic ID parts (allow only `[A-Za-z0-9_-]`) before output.
- Escape IDs when rendering HTML attributes.
- Do not include whitespace, quotes, or raw user input in IDs.
- Ensure uniqueness when multiple grid instances are rendered on one page.

### Sort and Filter Controls

If columns are sortable:

```html
<th>
  <button class="phpmysqlgrid-sort-btn" aria-label="Sort by Name, currently ascending">
    Name
  </button>
</th>
```

When sorted state changes, update `aria-label` or use `aria-sort`:

```html
<th aria-sort="ascending">
  <button class="phpmysqlgrid-sort-btn">Name</button>
</th>
```

Filter inputs:

```html
<input
  type="text"
  placeholder="Search by name"
  aria-label="Filter rows by name"
/>
```

## Keyboard Navigation

### Tab Order

- Tab stops should proceed left-to-right, top-to-bottom.
- Action buttons (Edit, Delete, Add) must be reachable via Tab.
- Grid should not prevent Tab from leaving the grid.

```html
<tbody>
  <tr>
    <td>Data Cell</td>
    <td>
      <button tabindex="0">Edit</button>
      <button tabindex="0">Delete</button>
    </td>
  </tr>
</tbody>
```

### Focus Indicators

**CSS must provide clear focus indicators.** Never remove `:focus` without a visible replacement:

```css
/* ✅ ALWAYS VISIBLE */
button:focus {
    outline: 2px solid #4A90E2;
    outline-offset: 2px;
}

/* ❌ HIDDEN (BAD) */
button:focus {
    outline: none;
}
```

Focus must be visible with:
- Contrast ratio ≥ 3:1 against background colors adjacent to the focus indicator.
- Minimum width/height of 2 CSS pixels (or 3 px for link underlines).

### Keyboard Shortcuts

If the grid implements keyboard shortcuts (e.g., Enter to edit, Delete to remove), document them visually:

```php
// In properties, define shortcut hints
public $txtKeyboardShortcuts = "Keyboard: Enter = Edit, Del = Delete";

// Render as help text or tooltip
```

Ensure shortcuts do not conflict with browser behavior (Ctrl+S, F5, etc.).

### No Keyboard Traps

Users should not get stuck in the grid with no way to Tab out. If a modal dialog opens, provide a visible close button or Escape key handler:

```php
// In drawEditControls or similar:
// Ensure Cancel/Close button is always accessible via Tab and visibly focused
```

## Color and Motion

### Color Independence

**Do not convey information through color alone.** Use additional visual markers:

```html
<!-- ❌ RED STATUS TEXT ONLY (not colorblind-friendly) -->
<span style="color: red;">Active</span>

<!-- ✅ COLOR + TEXT + ICON -->
<span style="color: red;">●</span> Active

<!-- ✅ COLOR + BORDER + PATTERN -->
<div style="border-left: 4px solid red;">Active</div>
```

Form validation errors:

```html
<!-- ❌ Red input border only -->
<input style="border: 2px solid red;">

<!-- ✅ Red border + error message + aria-invalid -->
<input style="border: 2px solid red;" aria-invalid="true">
<span role="alert">This field is required.</span>
```

### Motion and Animation

- **No auto-playing animations.** If animations exist, provide a pause control.
- Respect `prefers-reduced-motion`:

```css
@media (prefers-reduced-motion: reduce) {
    .phpmysqlgrid-row {
        animation: none;
        transition: none;
    }
}
```

## Contrast Requirements

All foreground/background color pairs must meet **WCAG 2.2 AA**:

- **Normal text:** 4.5:1 minimum
- **Large text (≥ 18pt or bold ≥ 14pt):** 3:1 minimum
- **Graphical elements and UI components:** 3:1 minimum

**Before moving css changes:** Use WebAIM Contrast Checker or Browser DevTools to verify.

Example in `assets/css/mysqlgrid.css`:

```css
/* ✅ GOOD: #000 on #FFF = 21:1 */
.phpmysqlgrid-header { background: #FFF; color: #000; }

/* ❌ BAD: #777 on #FFF = ~4.5:1 (just meets minimum, not ideal) */
.phpmysqlgrid-disabled { background: #FFF; color: #777; }

/* ✅ BETTER: #555 on #FFF = ~7.5:1 */
.phpmysqlgrid-disabled { background: #FFF; color: #555; }
```

## User-Visible Text and Internationalization

**All visible text or accessibility strings must be backed by public properties** initialized in `internationalize()`:

```php
public $txtPrevious = "Previous";
public $txtNext = "Next";
public $txtEdit = "Edit";
public $txtDelete = "Delete";
public $ariaLabelSortBy = "Sort by";

// In internationalize():
$this->txtPrevious = $userLang['btnPrevious'] ?? $this->txtPrevious;
```

**Never hardcode text in HTML output:**

```php
// ❌ WRONG (hardcoded)
echo '<button>Edit</button>';

// ✅ CORRECT (uses property)
echo '<button>' . htmlspecialchars($this->txtEdit) . '</button>';
```

This allows users to customize labels without modifying code.

## Accessible Forms (Add/Edit modes)

### Add Mode

```php
// Properties for form labels and help text
public $txtFieldName = "Field Name:";
public $txtFieldRequired = "Required field";
public $txtFormSave = "Save";
public $txtFormCancel = "Cancel";

// In drawEditControls():
echo '<form method="POST">';
echo '<label for="field_name">' . htmlspecialchars($this->txtFieldName) .
      '<span aria-label="required">*</span></label>';
echo '<input id="field_name" type="text" name="field_name" required>';
echo '<button type="submit">' . htmlspecialchars($this->txtFormSave) . '</button>';
echo '<button type="button" onclick="...'>' . htmlspecialchars($this->txtFormCancel) . '</button>';
echo '</form>';
```

### Edit Mode

Similar to Add mode, but pre-populated with existing values:

```php
echo '<input id="field_name" type="text" name="field_name" value="' .
     htmlspecialchars($currentValue) . '">';
```

## Testing Accessibility

### Manual Checklist

Before committing accessibility changes:

1. **Keyboard navigation:** Tab through the entire grid. Can you reach all controls without a mouse?
2. **Focus visibility:** Is the focused element always clearly marked?
3. **Screen reader:** (if tools available) Open in NVDA or JAWS. Can you hear all content and controls?
4. **Color contrast:** Use WebAIM to verify all foreground/background pairs.
5. **Form labels:** Every input has a visible label or `aria-label`.
6. **Error messages:** Are they marked with `role="alert"` and linked to the field?

### Automated Tests

Add accessibility assertions in unit and integration tests:

```php
public function testEditFormHasLabelForEveryInput(): void
{
    // ... setup grid ...
    $html = $grid->drawEditControls();

    // Check for input id and corresponding label
    $this->assertStringContainsString('id="field_name"', $html);
    $this->assertStringContainsString('for="field_name"', $html);
}

public function testPaginationNavHasAriaLabel(): void
{
    $html = $grid->drawPagination();
    $this->assertStringContainsString('aria-label="Pagination"', $html);
}
```

### Future Accessibility Audits

Use tools like:
- **axe DevTools** (browser extension): Automated a11y scanning
- **WAVE** (WebAIM): Color contrast, ARIA, structure
- **Lighthouse** (Chrome DevTools): Accessibility score
- **NVDA** or **JAWS**: Screen reader testing

Track findings in TODO.md and implement fixes with accessibility in mind.

## Reference

- **WCAG 2.2:** https://www.w3.org/WAI/WCAG22/quickref/
- **ARIA:** https://www.w3.org/WAI/ARIA/
- **WAI-ARIA Authoring Practices (APG) Patterns:** https://www.w3.org/WAI/ARIA/apg/patterns/
- **WebAIM:** https://webaim.org/
- **Styling accessibility:** See [.github/instructions/styling.instructions.md](./styling.instructions.md) for color and focus guidelines.
