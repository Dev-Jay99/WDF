/**
 * StudentHub Portal - Reusable Layout Components
 * Practical 3: Advanced Extension - Create reusable layout components using JavaScript libraries such as jQuery.
 */

(function (window, $) {
    'use strict';

    const StudentHubComponents = {
        /**
         * Render a reusable responsive event card component
         * @param {Object} event - Event data object
         * @returns {string} HTML markup string
         */
        renderEventCard: function (event) {
            const badgeClass = event.status === 'open' ? 'badge-success' : 'badge-danger';
            const statusLabel = event.status === 'open' ? 'Registrations Open' : 'Closed';
            const poster = event.poster_image ? `../uploads/posters/${event.poster_image}` : '../images/logo.png';

            return `
                <article class="event-card" data-category="${event.category}" data-status="${event.status}">
                    <div class="event-poster-wrapper">
                        <img src="${poster}" alt="${event.title}" class="event-poster-img" onerror="this.src='../images/logo.png';">
                        <span class="status-badge ${badgeClass}">${statusLabel}</span>
                    </div>
                    <div class="event-card-body">
                        <span class="event-category-tag">${event.category}</span>
                        <h3 class="event-title">${event.title}</h3>
                        <p class="event-description">${event.description || ''}</p>
                        <div class="event-meta">
                            <span>📅 ${event.event_date || 'TBA'}</span>
                            <span>📍 ${event.venue || 'Campus'}</span>
                        </div>
                    </div>
                </article>
            `;
        },

        /**
         * Render a reusable metric KPI stat card
         * @param {string} title - Metric label
         * @param {string|number} value - Metric value
         * @param {string} icon - Emoji/Icon
         * @param {string} subtitle - Subtitle text
         * @returns {string} HTML markup string
         */
        renderStatCard: function (title, value, icon, subtitle) {
            return `
                <div class="stat-card">
                    <div class="stat-icon-wrapper">${icon}</div>
                    <div class="stat-info">
                        <span class="stat-label">${title}</span>
                        <h3 class="stat-value">${value}</h3>
                        ${subtitle ? `<span class="stat-subtitle">${subtitle}</span>` : ''}
                    </div>
                </div>
            `;
        },

        /**
         * Render an accessible breadcrumb navigation component
         * @param {Array<{label: string, url: string|null}>} items - List of crumb objects
         * @returns {string} HTML markup string
         */
        renderBreadcrumbs: function (items) {
            const listItems = items.map((item, index) => {
                const isLast = index === items.length - 1;
                if (isLast || !item.url) {
                    return `<li class="breadcrumb-item active" aria-current="page">${item.label}</li>`;
                }
                return `<li class="breadcrumb-item"><a href="${item.url}">${item.label}</a></li>`;
            }).join('');

            return `
                <nav aria-label="breadcrumb" class="breadcrumb-nav">
                    <ol class="breadcrumb-list">
                        ${listItems}
                    </ol>
                </nav>
            `;
        },

        /**
         * jQuery-powered component loader
         * Allows mounting components to CSS selector targets dynamically
         */
        mount: function (selector, htmlContent) {
            if (window.jQuery && $(selector).length) {
                $(selector).html(htmlContent);
            } else {
                const el = document.querySelector(selector);
                if (el) el.innerHTML = htmlContent;
            }
        }
    };

    // Expose component library globally
    window.StudentHubComponents = StudentHubComponents;

})(window, window.jQuery || null);
