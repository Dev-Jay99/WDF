<?php
/**
 * StudentHub Portal - Asynchronous Fetch/AJAX Events Single Page Application
 * 
 * Subject: Web Development Frameworks (ITUE203)
 * Practical 13: PHP JSON API and AJAX/Fetch-Based CRUD without Page Reload
 * - Problem Definition: AJAX/Fetch consumption of PHP JSON API without full page reloads
 * - Intermediate Extension: Live search with debounce
 * - Advanced Extension: Inspect HTTP response status codes (200, 201, 400, 404, 500)
 */

require_once __DIR__ . '/../includes/auth.php';
$isAuth = is_logged_in();
$user   = get_logged_in_user();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Event Management | StudentHub Portal</title>
    <link rel="icon" href="../images/logo.png">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .ajax-container { padding: 32px 0 60px; }
        .toolbar {
            display: flex; flex-wrap: wrap; gap: 14px; justify-content: space-between; align-items: center;
            background: var(--card-bg, #fff); padding: 18px 24px; border-radius: 12px;
            border: 1px solid var(--border-color, #e2e8f0); margin-bottom: 24px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        .search-box-wrapper { display: flex; gap: 10px; align-items: center; flex: 1; max-width: 560px; }
        .search-box-wrapper input {
            width: 100%; padding: 11px 16px; border: 1px solid var(--border-color, #cbd5e1);
            border-radius: 8px; font-size: 0.95rem; background: var(--bg-surface, #fff); color: inherit;
            box-sizing: border-box; transition: border-color 0.2s, box-shadow 0.2s;
        }
        .search-box-wrapper input:focus {
            outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        .category-select {
            padding: 10px 14px; border-radius: 8px; border: 1px solid var(--border-color, #cbd5e1);
            background: var(--bg-surface, #fff); color: inherit; font-size: 0.9rem; cursor: pointer;
        }
        .events-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;
        }
        .event-card {
            background: var(--card-bg, #fff); border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 12px; padding: 22px; display: flex; flex-direction: column;
            transition: all 0.25s ease-in-out; box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        }
        .event-card:hover { transform: translateY(-3px); box-shadow: 0 12px 20px -3px rgba(0,0,0,0.09); }
        .event-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .badge-open { background: #10b981; color: #fff; padding: 3px 10px; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-closed { background: #ef4444; color: #fff; padding: 3px 10px; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .category-tag { background: rgba(37, 99, 235, 0.1); color: #2563eb; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }
        
        /* Floating Toast Notification - Explicit Green for Success */
        .toast-notification {
            position: fixed !important; bottom: 30px !important; right: 30px !important;
            padding: 16px 24px !important; border-radius: 12px !important; z-index: 100000 !important;
            box-shadow: 0 12px 30px rgba(0,0,0,0.3) !important; font-size: 1rem !important; font-weight: 700 !important;
            display: none; align-items: center; gap: 12px; animation: toastSlideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
        }
        .toast-success {
            background-color: #10b981 !important; color: #ffffff !important;
            border: 2px solid #059669 !important; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.5) !important;
        }
        .toast-error {
            background-color: #ef4444 !important; color: #ffffff !important;
            border: 2px solid #dc2626 !important; box-shadow: 0 10px 25px rgba(239, 68, 68, 0.5) !important;
        }
        @keyframes toastSlideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Modal Overlay & Box - Force High Priority to override any style.css defaults */
        #ajaxEventModal {
            position: fixed !important; top: 0 !important; left: 0 !important;
            width: 100vw !important; height: 100vh !important;
            background: rgba(15, 23, 42, 0.72) !important; backdrop-filter: blur(6px) !important;
            -webkit-backdrop-filter: blur(6px) !important; z-index: 99999 !important;
            display: none; align-items: center !important; justify-content: center !important;
            padding: 20px !important; box-sizing: border-box !important;
            opacity: 0; visibility: hidden; transition: opacity 0.25s ease, visibility 0.25s ease !important;
        }
        #ajaxEventModal.active,
        #ajaxEventModal.is-open {
            display: flex !important; opacity: 1 !important; visibility: visible !important;
            pointer-events: auto !important;
        }
        #ajaxEventModal .modal-box {
            background: var(--bg-card, #ffffff) !important; color: var(--text-color, #1e293b) !important;
            padding: 28px !important; border-radius: 16px !important; width: 100% !important; max-width: 520px !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35) !important; border: 1px solid var(--bs-border-color, #e2e8f0) !important;
            position: relative !important; transform: scale(0.95); transition: transform 0.25s ease;
            box-sizing: border-box !important;
        }
        #ajaxEventModal.active .modal-box,
        #ajaxEventModal.is-open .modal-box {
            transform: scale(1) !important;
        }

        /* Form Controls */
        .form-control {
            width: 100%; padding: 10px 14px; border: 1px solid var(--border-color, #cbd5e1);
            border-radius: 8px; font-size: 0.95rem; background: var(--bg-surface, #fff);
            color: var(--text-color, #1e293b); box-sizing: border-box; font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        /* HTTP Status Badge */
        .status-badge-http {
            font-family: monospace; font-size: 0.8rem; font-weight: 700; padding: 6px 12px; border-radius: 6px;
            background: #e2e8f0; color: #334155; border: 1px solid var(--border-color, #cbd5e1);
            transition: all 0.2s ease;
        }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 9px 18px; border-radius: 8px; font-weight: 600; font-size: 0.9rem;
            cursor: pointer; transition: all 0.2s ease; text-decoration: none; border: 1px solid transparent;
        }
        .btn-primary {
            background: #2563eb; color: #ffffff; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }
        .btn-primary:hover {
            background: #1d4ed8; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
        }
        .btn-outline {
            background: transparent; color: var(--text-color, #334155); border-color: var(--border-color, #cbd5e1);
        }
        .btn-outline:hover {
            background: var(--bg-surface-hover, #f1f5f9);
        }
        .btn-danger {
            background: #ef4444; color: #ffffff; border: none;
        }
        .btn-danger:hover {
            background: #dc2626;
        }

        /* Dark mode overrides */
        [data-theme="dark"] #ajaxEventModal .modal-box {
            background: #1e293b !important; color: #f8fafc !important; border-color: #334155 !important;
        }
        [data-theme="dark"] #ajaxEventModal .form-control {
            background: #0f172a !important; color: #f8fafc !important; border-color: #334155 !important;
        }
        [data-theme="dark"] .status-badge-http {
            background: #1e293b; color: #cbd5e1; border-color: #334155;
        }
        [data-theme="dark"] .category-tag {
            background: rgba(59, 130, 246, 0.2); color: #60a5fa;
        }
    </style>
</head>

<body>
    <!-- Skip to main content -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- HEADER / NAVBAR -->
    <header>
        <div class="container">
            <nav>
                <a href="../index.html" class="logo">StudentHub</a>
                <div class="nav-actions">
                    <ul class="nav-links">
                        <li><a href="../index.html">Home</a></li>
                        <li><a href="about.html">About</a></li>
                        <li><a href="events.html">Events</a></li>
                        <li><a href="events_ajax.php" class="active">Live Events API</a></li>
                        <li><a href="faq.html">FAQ</a></li>
                        <li><a href="contact.html">Contact</a></li>
                        <?php if ($isAuth): ?>
                            <li><a href="dashboard.php" class="btn-login">Dashboard</a></li>
                            <li><a href="logout.php" class="btn-register">Logout</a></li>
                        <?php else: ?>
                            <li><a href="login.php" class="btn-login">Login</a></li>
                            <li><a href="register.php" class="btn-register">Register</a></li>
                        <?php endif; ?>
                    </ul>
                    <button type="button" class="theme-toggle-btn" aria-label="Toggle theme">
                        <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"></circle></svg>
                        <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                    </button>
                    <button type="button" class="hamburger-btn" aria-label="Toggle menu"><span></span><span></span><span></span></button>
                </div>
            </nav>
        </div>
    </header>

    <main id="main-content" class="ajax-container">
        <div class="container">
            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                <ol class="breadcrumb-list">
                    <li class="breadcrumb-item"><a href="../index.html">Home</a></li>
                    <li class="breadcrumb-item"><a href="events.html">Events</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Live Event Management</li>
                </ol>
            </nav>

            <div style="margin-bottom: 24px;">
                <h1 style="margin: 0 0 6px 0;">Asynchronous Fetch API &amp; JSON Endpoints</h1>
                <p style="margin: 0; color: var(--text-muted, #64748b);">
                    Manage campus events with live instant updates without page reloading. Features real-time search, instant updates, and centralized API error handling.
                </p>
            </div>

            <!-- Toolbar with Debounced Search and Category Filter -->
            <div class="toolbar">
                <div class="search-box-wrapper">
                    <input type="text" id="ajaxSearchInput" oninput="onSearchInput()" placeholder="🔍 Type to search events in real-time (Debounced 300ms)...">
                    <select id="ajaxCategorySelect" class="category-select" onchange="loadEvents()">
                        <option value="">All Categories</option>
                        <option value="Hackathon">Hackathon</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Technical">Technical</option>
                        <option value="Sports">Sports</option>
                        <option value="Cultural">Cultural</option>
                    </select>
                </div>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <span id="httpStatusIndicator" class="status-badge-http">Ready (HTTP ---)</span>
                    <button type="button" id="openCreateModalBtn" class="btn btn-primary" onclick="openCreateModal()">+ Add Event (AJAX POST)</button>
                </div>
            </div>

            <!-- Loading Spinner -->
            <div id="loadingIndicator" style="display: none; text-align: center; padding: 40px; color: var(--text-muted, #64748b);">
                ⏳ Fetching event data from JSON API...
            </div>

            <!-- Events Grid -->
            <div id="eventsGrid" class="events-grid"></div>
        </div>
    </main>

    <!-- CREATE / EDIT MODAL -->
    <div id="ajaxEventModal" class="modal-overlay">
        <div class="modal-box">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 id="modalTitle" style="margin: 0; font-size: 1.25rem;">Add Campus Event via AJAX</h3>
                <button type="button" onclick="closeModal()" aria-label="Close modal" style="background: none; border: none; font-size: 1.6rem; line-height: 1; cursor: pointer; color: var(--text-muted, #64748b); padding: 0 4px;">&times;</button>
            </div>
            <form id="ajaxEventForm" onsubmit="handleFormSubmit(event)">
                <input type="hidden" id="event_id" value="">

                <div style="margin-bottom: 14px;">
                    <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Event Title *</label>
                    <input type="text" id="event_title" class="form-control" required placeholder="Workshop on Cloud Native AI">
                </div>
                <div style="display: flex; gap: 10px; margin-bottom: 14px;">
                    <div style="flex: 1;">
                        <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Category *</label>
                        <select id="event_category" class="form-control" required>
                            <option value="Technical">Technical</option>
                            <option value="Workshop">Workshop</option>
                            <option value="Hackathon">Hackathon</option>
                            <option value="Sports">Sports</option>
                            <option value="Cultural">Cultural</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Status *</label>
                        <select id="event_status" class="form-control">
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; margin-bottom: 14px;">
                    <div style="flex: 1;">
                        <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Event Date *</label>
                        <input type="date" id="event_date" class="form-control" required>
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Venue *</label>
                        <input type="text" id="event_venue" class="form-control" required placeholder="Seminar Hall 202">
                    </div>
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Description</label>
                    <textarea id="event_description" class="form-control" rows="3" placeholder="Brief event summary..."></textarea>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" id="modalSubmitBtn" class="btn btn-primary">Create Event (201 Created)</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Floating Toast Notification (Prominently Green on Success) -->
    <div id="toast" class="toast-notification"></div>

    <script src="../js/script.js"></script>
    <script>
        const API_URL = '../api/events.php';
        let eventsCache = [];

        // Debounce Utility (Practical 13 Intermediate Extension)
        function debounce(func, wait = 300) {
            let timeout;
            return function (...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        // Display Floating Toast with Green Success styling
        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            if (!toast) return;
            toast.innerHTML = (isError ? '&#x274C;&nbsp;&nbsp;' : '&#x2714;&#xFE0F;&nbsp;&nbsp;') + escapeHtml(message);
            toast.className = 'toast-notification ' + (isError ? 'toast-error' : 'toast-success');
            toast.style.display = 'flex';
            toast.style.opacity = '1';
            toast.style.visibility = 'visible';
            clearTimeout(window._toastTimeout);
            window._toastTimeout = setTimeout(() => {
                toast.style.display = 'none';
                toast.style.opacity = '0';
            }, 6000);
        }

        function updateHttpBadge(code, text) {
            const badge = document.getElementById('httpStatusIndicator');
            if (!badge) return;
            badge.textContent = `HTTP ${code} ${text}`;
            if (code >= 200 && code < 300) {
                badge.style.background = 'rgba(16, 185, 129, 0.15)';
                badge.style.color = '#065f46';
                badge.style.borderColor = '#10b981';
            } else {
                badge.style.background = 'rgba(239, 68, 68, 0.15)';
                badge.style.color = '#991b1b';
                badge.style.borderColor = '#ef4444';
            }
        }

        // Fetch events asynchronously via Fetch API (GET)
        async function loadEvents() {
            const searchInput = document.getElementById('ajaxSearchInput');
            const categorySelect = document.getElementById('ajaxCategorySelect');
            const search = searchInput ? searchInput.value.trim() : '';
            const category = categorySelect ? categorySelect.value : '';
            const loader = document.getElementById('loadingIndicator');
            const grid = document.getElementById('eventsGrid');

            if (loader) loader.style.display = 'block';

            try {
                const url = new URL(API_URL, window.location.href);
                if (search) url.searchParams.set('search', search);
                if (category) url.searchParams.set('category', category);

                const response = await fetch(url);
                updateHttpBadge(response.status, response.statusText);

                const json = await response.json();
                eventsCache = json.data || [];
                renderEvents(eventsCache);
            } catch (err) {
                console.error("Fetch error:", err);
                showToast("Failed to connect to JSON API: " + err.message, true);
                updateHttpBadge(500, 'Error');
            } finally {
                if (loader) loader.style.display = 'none';
            }
        }

        // Render events into DOM without page reload
        function renderEvents(events) {
            const grid = document.getElementById('eventsGrid');
            if (!grid) return;

            if (!events.length) {
                grid.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 48px; background: var(--card-bg, #fff); border: 1px solid var(--border-color, #e2e8f0); border-radius: 12px; color: var(--text-muted, #64748b);">
                        No campus events matched the search query.
                    </div>
                `;
                return;
            }

            grid.innerHTML = events.map(ev => {
                const statusClass = (ev.status && ev.status.toLowerCase() === 'open') ? 'badge-open' : 'badge-closed';
                const statusText = (ev.status && ev.status.toLowerCase() === 'open') ? 'OPEN' : 'CLOSED';
                return `
                <div class="event-card" id="event-card-${ev.id}">
                    <div class="event-card-header">
                        <span class="category-tag">${escapeHtml(ev.category || 'General')}</span>
                        <span class="${statusClass}">${statusText}</span>
                    </div>
                    <h3 style="margin: 0 0 8px 0; font-size: 1.15rem;">${escapeHtml(ev.title)}</h3>
                    <p style="margin: 0 0 14px 0; font-size: 0.88rem; color: var(--text-muted, #64748b); flex: 1; line-height: 1.5;">
                        ${escapeHtml(ev.description || 'No detailed description available.')}
                    </p>
                    <div style="font-size: 0.82rem; color: var(--text-muted, #64748b); margin-bottom: 16px;">
                        <div style="margin-bottom: 4px;">📅 <strong>Date:</strong> ${escapeHtml(ev.event_date || ev.date || 'TBD')}</div>
                        <div>📍 <strong>Venue:</strong> ${escapeHtml(ev.venue || 'Campus')}</div>
                    </div>
                    <div style="display: flex; gap: 8px; justify-content: flex-end; border-top: 1px solid var(--border-color, #e2e8f0); padding-top: 14px;">
                        <button type="button" class="btn btn-outline" style="padding: 6px 14px; font-size: 0.82rem;" onclick="openEditModal(${ev.id})">Edit</button>
                        <button type="button" class="btn btn-danger" style="padding: 6px 14px; font-size: 0.82rem;" onclick="deleteEvent(${ev.id})">Delete</button>
                    </div>
                </div>
            `;
            }).join('');
        }

        // Open modal for Create (POST)
        function openCreateModal() {
            const modal = document.getElementById('ajaxEventModal');
            if (!modal) return;

            document.getElementById('modalTitle').textContent = 'Add Campus Event via AJAX (POST)';
            document.getElementById('modalSubmitBtn').textContent = 'Create Event (201 Created)';
            document.getElementById('event_id').value = '';
            document.getElementById('ajaxEventForm').reset();
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('event_date').value = today;

            modal.classList.add('active');
            modal.classList.add('is-open');
            modal.style.display = 'flex';
            modal.style.opacity = '1';
            modal.style.visibility = 'visible';
            document.body.classList.add('modal-open');
        }

        // Open modal for Edit (PUT)
        function openEditModal(id) {
            const ev = eventsCache.find(e => e.id == id);
            if (!ev) return;

            const modal = document.getElementById('ajaxEventModal');
            if (!modal) return;

            document.getElementById('modalTitle').textContent = `Edit Event #${id} (PUT)`;
            document.getElementById('modalSubmitBtn').textContent = 'Update Event (200 OK)';
            document.getElementById('event_id').value = ev.id;
            document.getElementById('event_title').value = ev.title;
            document.getElementById('event_category').value = ev.category || 'Technical';
            document.getElementById('event_status').value = (ev.status && ev.status.toLowerCase() === 'closed') ? 'closed' : 'open';
            document.getElementById('event_date').value = ev.event_date || ev.date || '';
            document.getElementById('event_venue').value = ev.venue || '';
            document.getElementById('event_description').value = ev.description || '';

            modal.classList.add('active');
            modal.classList.add('is-open');
            modal.style.display = 'flex';
            modal.style.opacity = '1';
            modal.style.visibility = 'visible';
            document.body.classList.add('modal-open');
        }

        function closeModal() {
            const modal = document.getElementById('ajaxEventModal');
            if (!modal) return;
            modal.classList.remove('active');
            modal.classList.remove('is-open');
            modal.style.display = 'none';
            modal.style.opacity = '0';
            modal.style.visibility = 'hidden';
            document.body.classList.remove('modal-open');
        }

        // Handle Add/Edit submission asynchronously without page reload
        async function handleFormSubmit(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('modalSubmitBtn');
            const originalBtnText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';

            const id = document.getElementById('event_id').value;
            const payload = {
                title: document.getElementById('event_title').value.trim(),
                category: document.getElementById('event_category').value,
                status: document.getElementById('event_status').value,
                event_date: document.getElementById('event_date').value,
                venue: document.getElementById('event_venue').value.trim(),
                description: document.getElementById('event_description').value.trim()
            };

            const isEdit = Boolean(id);
            if (isEdit) payload.id = parseInt(id, 10);

            const method = isEdit ? 'PUT' : 'POST';

            try {
                const response = await fetch(API_URL, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                updateHttpBadge(response.status, response.statusText);
                const result = await response.json();

                if (response.ok && result.success) {
                    const successMsg = (!isEdit) 
                        ? 'Event created successfully via API'
                        : (result.message || 'Event updated successfully via API');
                    showToast(successMsg, false);
                    closeModal();
                    await loadEvents(); // Smoothly refresh DOM without page reload
                } else {
                    showToast(result.message || 'Operation failed', true);
                }
            } catch (err) {
                console.error("Save error:", err);
                showToast("Request failed: " + err.message, true);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
            }
        }

        // Asynchronous Delete (DELETE)
        async function deleteEvent(id) {
            if (!confirm(`Are you sure you want to delete event #${id} via AJAX?`)) return;

            try {
                const response = await fetch(API_URL, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });

                updateHttpBadge(response.status, response.statusText);
                const result = await response.json();

                if (response.ok && result.success) {
                    showToast(result.message || 'Event deleted successfully via API', false);
                    const card = document.getElementById(`event-card-${id}`);
                    if (card) {
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.95)';
                        setTimeout(() => loadEvents(), 200);
                    } else {
                        loadEvents();
                    }
                } else {
                    showToast(result.message || 'Failed to delete event', true);
                }
            } catch (err) {
                showToast("Delete request failed: " + err.message, true);
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }

        // Export functions to window object
        const onSearchInput = debounce(loadEvents, 300);
        window.onSearchInput = onSearchInput;
        window.loadEvents = loadEvents;
        window.openCreateModal = openCreateModal;
        window.openEditModal = openEditModal;
        window.closeModal = closeModal;
        window.handleFormSubmit = handleFormSubmit;
        window.deleteEvent = deleteEvent;

        // Attach listeners and start loading
        function initEventsPage() {
            const searchInput = document.getElementById('ajaxSearchInput');
            const categorySelect = document.getElementById('ajaxCategorySelect');
            const addBtn = document.getElementById('openCreateModalBtn');
            const modal = document.getElementById('ajaxEventModal');

            if (searchInput) searchInput.addEventListener('input', onSearchInput);
            if (categorySelect) categorySelect.addEventListener('change', loadEvents);
            if (addBtn) addBtn.addEventListener('click', openCreateModal);

            if (modal) {
                modal.addEventListener('click', function (e) {
                    if (e.target === this) closeModal();
                });
            }

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeModal();
            });

            // Trigger initial fetch
            loadEvents();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initEventsPage);
        } else {
            initEventsPage();
        }
    </script>
</body>
</html>
