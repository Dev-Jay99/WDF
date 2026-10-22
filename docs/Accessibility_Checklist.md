# Accessibility Checklist & WCAG 2.1 Compliance Report

**Project:** StudentHub Portal  
**Subject:** Web Development Frameworks (ITUE203) - Practical 2  
**Target Standard:** WCAG 2.1 Level AA Compliance  

---

## 1. Accessibility Verification Checklist

| Category | Guideline / Checkpoint | Implementation in StudentHub | Status |
| :--- | :--- | :--- | :---: |
| **Landmarks** | Semantic HTML5 structure | `<header>`, `<nav>`, `<main id="main-content">`, `<section>`, `<article>`, `<aside>`, `<footer>` used on every page. | Verified |
| **Navigation** | Skip to main content | Interactive `.skip-link` positioned off-screen and visible upon `Tab` keypress, linking to `#main-content`. | Verified |
| **Navigation** | Breadcrumbs | `<nav aria-label="breadcrumb">` present on all subpages with `<ol>` and `aria-current="page"`. | Verified |
| **Forms** | Explicit form labels | Every `<input>`, `<select>`, and `<textarea>` has a dedicated `<label for="...">`. | Verified |
| **Forms** | Error announcement | Error messages use `<small class="error-message">` placed directly adjacent to inputs with `aria-invalid` attributes. | Verified |
| **Images** | Text alternatives | All images include descriptive `alt="..."` attributes or SVG icons include `aria-label`. | Verified |
| **Color & Contrast**| Minimum 4.5:1 ratio | Text and background combinations tested for high legibility in both Light and Dark themes. | Verified |
| **Keyboard Access** | Focus outlines & Tab navigation | All interactive controls (buttons, links, toggles, accordion headers) are focusable with visible focus indicators. | Verified |
| **Modals** | Keyboard trapping & Dismissal | Modals can be dismissed using the `Escape` key or explicit close buttons with `aria-label="Close modal"`. | Verified |
| **Interactivity** | Screen reader states | Accordion headers utilize `aria-expanded="true/false"`; hamburger menu utilizes `aria-expanded`. | Verified |

---

## 2. Tested Screen Reader & Keyboard Workflows

1. **Tab Navigation:**
   - On page load, pressing `Tab` immediately focuses the "Skip to main content" link.
   - Pressing `Enter` bypasses the repetitive header navigation and places focus into the main page content.
2. **Accordion FAQ:**
   - Navigating via `Tab` focuses each question header button.
   - Pressing `Enter` or `Space` expands the content and updates `aria-expanded` dynamically.
3. **Modal Dialogs:**
   - When a modal opens, focus moves to the dialog card.
   - Pressing `Escape` closes the modal and returns focus to the trigger button.
4. **Theme Switcher:**
   - Has clear `aria-label="Toggle light/dark theme"`, operable by keyboard `Enter` or `Space`.
