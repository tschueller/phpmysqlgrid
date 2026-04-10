---
name: accessibility
description: "Use when: adding or modifying ARIA attributes, labels, keyboard navigation, focus indicators, semantic HTML, or testing accessibility. Applies to MySQLGrid output generation, assets/css/mysqlgrid.css, and accessibility-specific tests."
applyTo: "{src/MySQLGrid.php,assets/css/*.css,tests/*XssTest.php,tests/*CrudIntegrationTest.php}"
---

# Accessibility Instructions for MySQLGrid

## Goals

MySQLGrid targets **WCAG 2.2 Level AA**: semantic HTML, keyboard navigation, focus handling, screen reader support, sufficient contrast.

## Semantic HTML

- Table structure: `<table>`, `<thead>`, `<tbody>`, `<tfoot>` with `<th scope="col">` for column headers.
- Do not nest `<table>` inside table cells.

## ARIA

- Add `role="grid"` to the table element; provide `aria-label` if the grid purpose is not clear from surrounding content.
- Sortable columns: set `aria-sort="ascending|descending|none"` on `<th>`.
- Filter inputs: use `aria-label="Filter rows by <column-name>"`.
- Icon-only buttons: use `aria-label` for the action name.

## Form Controls (Add/Edit Mode)

- Every `<input>`, `<select>`, `<textarea>` must have a `<label for="...">` with a matching `id`.
- Required fields: add the `required` attribute and a visible indicator.
- Pre-populate edit fields with current values, always HTML-escaped.
- Never hardcode visible label text — use `txt*` internationalization properties.

## Keyboard Navigation and Focus

- All action buttons (edit, delete, add) must be reachable via Tab in DOM order.
- CSS must always provide `:focus-visible` style; never set `outline: none` without a visible replacement.
- Focus indicator contrast must be ≥ 3:1 against the adjacent background.

## Contrast

All foreground/background pairs must meet WCAG 2.2 AA:
- Normal text: ≥ 4.5:1
- Large text and UI components: ≥ 3:1

Verify with WebAIM Contrast Checker or Browser DevTools before merging color changes.

## Color Independence

Do not convey information by color alone — always pair color with a text label or icon with `aria-label`.

## Automated Tests

Assert `<label for>` / `id` associations and `aria-*` attributes in unit and integration tests:

```php
$this->assertStringContainsString('id="field_name"', $html);
$this->assertStringContainsString('for="field_name"', $html);
```

## Reference

- [WCAG 2.2](https://www.w3.org/WAI/WCAG22/quickref/)
- [WAI-ARIA APG Patterns](https://www.w3.org/WAI/ARIA/apg/patterns/)
- See [styling.instructions.md](./styling.instructions.md) for focus and contrast CSS guidance.
