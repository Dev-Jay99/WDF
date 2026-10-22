# Practical 6: Fetch API Dynamic Rendering, Search & Filter Verification

**Project:** StudentHub Portal  
**Subject:** Web Development Frameworks (ITUE203) - Practical 6  
**Implementation Files:** `js/script.js`, `pages/events.html`, `pages/faq.html`, `pages/dashboard.html`  
**Data Sources:** `data/events.json`, `data/students.json`, `data/faqs.json`  

---

## 1. Dataset Verification ($\ge$ 15 Records Required)

| JSON Dataset | File Path | Total Records | Sample Record ID | Attributes Covered |
| :--- | :--- | :---: | :---: | :--- |
| **Events** | `data/events.json` | **16** | `1` (Hackout 2026) | `id`, `title`, `category`, `date`, `time`, `venue`, `organizer`, `description`, `registrationDeadline`, `status` |
| **Students** | `data/students.json` | **16** | `23DCE001` (Aarav Patel) | `id`, `name`, `course`, `semester`, `email`, `cgpa`, `attendance`, `city` |
| **FAQs** | `data/faqs.json` | **16** | `1` (Attendance query) | `id`, `category`, `question`, `answer` |

---

## 2. JavaScript Array Operations & Logic Implemented

1. **Fetching & Parsing (`fetchExternalJsonData`)**:
   - Asynchronous `fetch(dataPath)` with HTTP status verification (`response.ok`).
   - Loading indicator displayed in the DOM during the fetch lifecycle.
   - Offline storage: `localStorage.setItem("cached_" + datasetName + "_data", JSON.stringify(data))`.
2. **Filtering & Search (`Array.prototype.filter`)**:
   - Multi-field substring search across title, description, venue, organizer, student name, ID, and city.
   - Exact category filtering based on dynamically generated distinct categories from the dataset using `new Set()`.
3. **Sorting (`Array.prototype.sort`)**:
   - Dates sorted chronologically: `new Date(a.date) - new Date(b.date)`.
   - String titles and names sorted alphabetically: `(a.title).localeCompare(b.title)`.
   - Numeric metrics (CGPA, Attendance) sorted in descending order: `(b.cgpa - a.cgpa)`.
4. **Pagination & Slicing (`Array.prototype.slice`)**:
   - Dynamic page slice calculation: `startIndex = (jsonCurrentPage - 1) * jsonItemsPerPage`.
   - Items-per-page selection: 4, 6, or 8 items per page.
   - Interactive Prev, Next, and numbered page controls.
5. **DOM Card Generation (`Array.prototype.map` & `.join("")`)**:
   - Converts parsed objects into semantic card components with category badges, meta tags, and buttons.

---

## 3. Test Scenarios & Results

| Test Scenario | Test Input | Observed Output | Verification Status |
| :--- | :--- | :--- | :---: |
| **Dataset Switch** | Click "Students" tab | Dataset switches to student directory, category dropdown repopulates with courses, cards display CGPA and attendance. | PASS |
| **Live Keyword Search** | Type "Hackathon" | Only Hackathon events display; status bar updates to "Showing 1 - 2 of 2 records". | PASS |
| **Category Filter** | Select "Workshop" | Only events categorized under "Workshop" are rendered. | PASS |
| **Sorting by CGPA** | Select "Highest CGPA" | Students are arranged from highest CGPA (9.42 - Kavya Desai) down to lowest. | PASS |
| **Pagination Next/Prev** | Click "Next" | Cards for page 2 render smoothly, active page button highlights, status counter updates. | PASS |
| **Reset Action** | Click "Reset" button | Search query, category, and sorting reset to defaults, returning to page 1 with all records. | PASS |
| **Offline Cache** | Simulate offline mode | Data loads seamlessly from `localStorage` cached state without throwing network error. | PASS |
