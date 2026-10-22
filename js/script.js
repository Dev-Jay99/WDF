// StudentHub Portal - ITUE203 Web Development Frameworks
// Practical 4: JavaScript DOM Manipulation, Event Handling, and UI Interactivity
// Practical 5: Registration Form with Frontend Validation and Error Handling

// ============================================================================
// PRACTICAL 4: DYNAMIC UI COMPONENTS & DOM MANIPULATION
// ============================================================================

// 1. Theme Switcher (Dark Mode / Light Mode)
// This function handles the dark and light theme toggle.
// It checks if the student previously selected a theme stored in localStorage.
// If not found, it checks the student's operating system preference.
function initThemeToggle() {
    const themeToggleBtn = document.querySelector(".theme-toggle-btn");
    const urlParams = new URLSearchParams(window.location.search);
    const urlTheme = urlParams.get("theme");
    const savedTheme = urlTheme || localStorage.getItem("studenthub_theme");
    const systemPrefersDark = window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches;

    // Pick saved theme from localStorage, URL param, or fallback to system preference
    const initialTheme = savedTheme || (systemPrefersDark ? "dark" : "light");
    applyTheme(initialTheme);
    if (urlTheme) {
        localStorage.setItem("studenthub_theme", urlTheme);
    }

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener("click", function () {
            const currentTheme = document.documentElement.getAttribute("data-theme") || "light";
            const newTheme = currentTheme === "dark" ? "light" : "dark";
            
            applyTheme(newTheme);
            // Save theme in localStorage so the choice stays on other pages
            localStorage.setItem("studenthub_theme", newTheme);
        });
    }
}

// Helper function to set the data-theme attribute on both html and body tags
function applyTheme(theme) {
    document.documentElement.setAttribute("data-theme", theme);
    if (document.body) {
        document.body.setAttribute("data-theme", theme);
    }
}

// 2. Mobile Responsive Hamburger Menu
// On small screens, clicking the hamburger icon toggles the navigation links.
// We also create an overlay backdrop and let the user press Escape to close the menu.
function initHamburgerMenu() {
    const hamburgerBtn = document.querySelector(".hamburger-btn");
    const navLinks = document.querySelector(".nav-links");

    if (!hamburgerBtn || !navLinks) return;

    // Create a background backdrop element if it does not already exist
    let navBackdrop = document.querySelector(".nav-backdrop");
    if (!navBackdrop) {
        navBackdrop = document.createElement("div");
        navBackdrop.className = "nav-backdrop";
        document.body.appendChild(navBackdrop);
    }

    // Toggle open or closed state for the mobile menu
    function toggleMenu(forceClose = false) {
        const isOpen = forceClose ? false : !navLinks.classList.contains("mobile-active");
        hamburgerBtn.classList.toggle("is-active", isOpen);
        navLinks.classList.toggle("mobile-active", isOpen);
        navBackdrop.classList.toggle("is-active", isOpen);
        hamburgerBtn.setAttribute("aria-expanded", isOpen ? "true" : "false");
        document.body.classList.toggle("mobile-nav-open", isOpen);
    }

    hamburgerBtn.addEventListener("click", function (e) {
        e.stopPropagation();
        toggleMenu();
    });

    // Clicking on the dim backdrop closes the menu
    navBackdrop.addEventListener("click", function () {
        toggleMenu(true);
    });

    // Automatically close the mobile menu whenever a menu link is clicked
    navLinks.querySelectorAll("a").forEach(link => {
        link.addEventListener("click", () => toggleMenu(true));
    });

    // Close mobile menu if the user presses the Escape key
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && navLinks.classList.contains("mobile-active")) {
            toggleMenu(true);
        }
    });
}

// 3. Dismissible Notification Banner
// This closes the top announcement banner when clicking the close button (X).
// It adds a smooth slide-up animation and remembers that the user closed it in localStorage.
function initNotificationBanner() {
    const banners = document.querySelectorAll(".notification-banner");

    banners.forEach(banner => {
        const bannerId = banner.getAttribute("data-banner-id") || "default_banner";
        const isDismissed = localStorage.getItem("dismissed_banner_" + bannerId);

        // If the student already closed this banner earlier, keep it hidden
        if (isDismissed === "true") {
            banner.style.display = "none";
            return;
        }

        const closeBtn = banner.querySelector(".notification-close");
        if (closeBtn) {
            closeBtn.addEventListener("click", function () {
                banner.classList.add("dismissing");
                setTimeout(() => {
                    banner.style.display = "none";
                    localStorage.setItem("dismissed_banner_" + bannerId, "true");
                }, 300);
            });
        }
    });
}

// 4. Image / Content Slider
// Allows sliding between cards/slides using next/prev buttons and dot indicators.
// It also auto-plays every 5 seconds, and pauses when hovering with the mouse.
function initSlider() {
    const sliderContainers = document.querySelectorAll(".slider-container");

    sliderContainers.forEach(container => {
        const track = container.querySelector(".slider-track");
        const slides = container.querySelectorAll(".slide-item");
        const prevBtn = container.querySelector(".slider-prev");
        const nextBtn = container.querySelector(".slider-next");
        const dotsContainer = container.querySelector(".slider-dots");

        if (!track || slides.length === 0) return;

        let currentIndex = 0;
        const totalSlides = slides.length;
        let slideInterval = null;

        // Dynamically create a dot for each slide
        if (dotsContainer) {
            dotsContainer.innerHTML = "";
            slides.forEach((_, idx) => {
                const dot = document.createElement("button");
                dot.type = "button";
                dot.className = "dot" + (idx === 0 ? " active" : "");
                dot.setAttribute("aria-label", "Go to slide " + (idx + 1));
                dot.addEventListener("click", () => {
                    goToSlide(idx);
                    resetAutoPlay();
                });
                dotsContainer.appendChild(dot);
            });
        }

        // Highlight the dot matching current slide
        function updateDots() {
            if (!dotsContainer) return;
            const dots = dotsContainer.querySelectorAll(".dot");
            dots.forEach((dot, idx) => {
                dot.classList.toggle("active", idx === currentIndex);
            });
        }

        // Slide transition by updating CSS transform translateX
        function goToSlide(index) {
            if (index < 0) {
                currentIndex = totalSlides - 1;
            } else if (index >= totalSlides) {
                currentIndex = 0;
            } else {
                currentIndex = index;
            }
            track.style.transform = "translateX(-" + (currentIndex * 100) + "%)";
            updateDots();
        }

        // Next slide button click
        if (nextBtn) {
            nextBtn.addEventListener("click", () => {
                goToSlide(currentIndex + 1);
                resetAutoPlay();
            });
        }

        // Previous slide button click
        if (prevBtn) {
            prevBtn.addEventListener("click", () => {
                goToSlide(currentIndex - 1);
                resetAutoPlay();
            });
        }

        // Automatically move to the next slide every 5 seconds
        function startAutoPlay() {
            slideInterval = setInterval(() => {
                goToSlide(currentIndex + 1);
            }, 5000);
        }

        function stopAutoPlay() {
            if (slideInterval) clearInterval(slideInterval);
        }

        function resetAutoPlay() {
            stopAutoPlay();
            startAutoPlay();
        }

        // Pause slideshow on mouse hover, resume on mouse leave
        container.addEventListener("mouseenter", stopAutoPlay);
        container.addEventListener("mouseleave", startAutoPlay);

        startAutoPlay();
    });
}

// 5. Collapsible FAQ Accordion
// Toggles opening and closing FAQ items when clicking questions.
// Calculates scrollHeight dynamically for smooth slide animation.
// If data-single is set to true, opening one question closes any other open questions.
function initFaqAccordion() {
    const accordions = document.querySelectorAll(".faq-accordion");

    accordions.forEach(accordion => {
        const isSingle = accordion.getAttribute("data-single") === "true";
        const items = accordion.querySelectorAll(".faq-item");

        items.forEach(item => {
            const header = item.querySelector(".faq-header");
            const content = item.querySelector(".faq-content");

            if (!header || !content) return;

            header.addEventListener("click", function () {
                const isActive = item.classList.contains("active");

                // If accordion is in single mode, close other open items first
                if (isSingle && !isActive) {
                    items.forEach(otherItem => {
                        if (otherItem !== item && otherItem.classList.contains("active")) {
                            otherItem.classList.remove("active");
                            const otherHeader = otherItem.querySelector(".faq-header");
                            const otherContent = otherItem.querySelector(".faq-content");
                            if (otherHeader) otherHeader.setAttribute("aria-expanded", "false");
                            if (otherContent) otherContent.style.maxHeight = null;
                        }
                    });
                }

                // Toggle the clicked FAQ item
                if (isActive) {
                    item.classList.remove("active");
                    header.setAttribute("aria-expanded", "false");
                    content.style.maxHeight = null;
                } else {
                    item.classList.add("active");
                    header.setAttribute("aria-expanded", "true");
                    content.style.maxHeight = content.scrollHeight + "px";
                }
            });
        });
    });
}

// 6. Modal Popup System
// Opens modals when clicking elements that have [data-modal-target].
// Closes when clicking close buttons, clicking the dark background overlay, or pressing Escape.
// Also loads course syllabus details dynamically for courses on courses.html.
const courseSyllabusData = {
    "ITUE203": {
        title: "ITUE203 - Web Development Frameworks",
        credits: "4 Credits (L-3, P-2)",
        desc: "Covers HTML5, CSS3 Grid/Flexbox, JavaScript ES6+, DOM manipulation, form validation, and PHP/MySQL backend.",
        units: [
            "Unit 1: Introduction to Web Technologies & Client-Server Architecture",
            "Unit 2: Building User Interface with HTML5 & CSS3 (Box Model, Flexbox, Grid)",
            "Unit 3: Interactive UI with JavaScript ES6 (Operators, Functions, Events)",
            "Unit 4: Document Object Model (DOM) Manipulation & Dynamic Rendering",
            "Unit 5: JavaScript Objects, Form Handling & Client Validation",
            "Unit 6: Backend PHP Scripting & MySQL Database Connectivity"
        ]
    },
    "CE202": {
        title: "CE202 - Fundamentals of Computer Networks",
        credits: "4 Credits (L-3, P-2)",
        desc: "Covers the OSI 7-layer architecture, TCP/IP protocol suite, subnetting, routing algorithms, and network security fundamentals.",
        units: [
            "Unit 1: Introduction to Data Communications & Network Architectures",
            "Unit 2: Physical Layer & Transmission Media",
            "Unit 3: Data Link Layer & Medium Access Control (MAC)",
            "Unit 4: Network Layer, IP Addressing (IPv4/IPv6), and Routing Protocols",
            "Unit 5: Transport Layer (TCP, UDP, Congestion Control)",
            "Unit 6: Application Layer Protocols (HTTP, DNS, FTP, SMTP)"
        ]
    },
    "MA203": {
        title: "MA203 - Discrete Mathematics",
        credits: "3 Credits (L-3, P-0)",
        desc: "Mathematical foundations of computer science including sets, relations, propositional logic, graph theory, and combinatorics.",
        units: [
            "Unit 1: Set Theory, Relations, and Functions",
            "Unit 2: Propositional and Predicate Logic & Proof Methods",
            "Unit 3: Combinatorics, Permutations, and Generating Functions",
            "Unit 4: Graph Theory, Euler and Hamiltonian Circuits",
            "Unit 5: Trees, Spanning Trees, and Boolean Algebra"
        ]
    },
    "CE201": {
        title: "CE201 - Data Structures and Algorithms",
        credits: "5 Credits (L-3, P-4)",
        desc: "Comprehensive study of arrays, linked lists, stacks, queues, trees, graphs, sorting, searching, and algorithmic complexity.",
        units: [
            "Unit 1: Introduction to Data Structures & Asymptotic Notation",
            "Unit 2: Linear Data Structures: Arrays, Stacks, and Queues",
            "Unit 3: Singly, Doubly, and Circular Linked Lists",
            "Unit 4: Non-Linear Structures: Binary Trees, BST, AVL Trees, Heaps",
            "Unit 5: Graph Representations, BFS, DFS, Shortest Path Algorithms",
            "Unit 6: Sorting and Searching Techniques with Complexity Analysis"
        ]
    },
    "HS201": {
        title: "HS201 - Professional Communication & Ethics",
        credits: "2 Credits (L-2, P-0)",
        desc: "Enhances technical writing, presentation skills, workplace ethics, group discussion, and interview readiness.",
        units: [
            "Unit 1: Principles of Effective Communication & Workplace Etiquette",
            "Unit 2: Technical Writing: Reports, Emails, and Resumes",
            "Unit 3: Oral Presentations, Public Speaking, and Body Language",
            "Unit 4: Professional Ethics, Integrity, and Intellectual Property"
        ]
    }
};

function initModalPopups() {
    function openModal(modal) {
        if (!modal) return;
        modal.classList.add("is-open");
        modal.setAttribute("aria-hidden", "false");
        document.body.classList.add("modal-open");
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.classList.remove("is-open");
        modal.setAttribute("aria-hidden", "true");
        if (!document.querySelector(".modal-overlay.is-open")) {
            document.body.classList.remove("modal-open");
        }
    }

    // Modal open trigger buttons
    document.querySelectorAll("[data-modal-target]").forEach(trigger => {
        trigger.addEventListener("click", function (e) {
            e.preventDefault();
            const targetSelector = this.getAttribute("data-modal-target");
            const modal = document.querySelector(targetSelector);

            // If syllabus button, fill in course details dynamically
            const courseCode = this.getAttribute("data-course");
            if (courseCode && courseSyllabusData[courseCode] && modal) {
                const data = courseSyllabusData[courseCode];
                const titleEl = modal.querySelector(".modal-course-title");
                const creditsEl = modal.querySelector(".modal-course-credits");
                const descEl = modal.querySelector(".modal-course-desc");
                const unitsEl = modal.querySelector(".modal-course-units");

                if (titleEl) titleEl.textContent = data.title;
                if (creditsEl) creditsEl.textContent = data.credits;
                if (descEl) descEl.textContent = data.desc;
                if (unitsEl) {
                    unitsEl.innerHTML = data.units.map(u => `<li>${u}</li>`).join("");
                }
            }

            openModal(modal);
        });
    });

    // Close buttons inside modal headers and footers
    document.querySelectorAll("[data-modal-close], .modal-close-btn").forEach(btn => {
        btn.addEventListener("click", function () {
            const modal = this.closest(".modal-overlay");
            closeModal(modal);
        });
    });

    // Clicking anywhere outside the modal box closes the modal
    document.querySelectorAll(".modal-overlay").forEach(overlay => {
        overlay.addEventListener("click", function (e) {
            if (e.target === this) {
                closeModal(this);
            }
        });
    });

    // Pressing Escape key closes any active open modal
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            const openModalEl = document.querySelector(".modal-overlay.is-open");
            if (openModalEl) closeModal(openModalEl);
        }
    });
}


// ============================================================================
// PRACTICAL 5: REGISTRATION FORM VALIDATION & PASSWORD STRENGTH METER
// ============================================================================

// Regular expressions to check input formats as required by the syllabus:
// - Name: only alphabets and spaces (2 to 50 characters)
// - Email: standard university or public email format
// - Phone: 10-digit Indian phone number starting with 6, 7, 8, or 9
// - Password: at least 8 characters, with uppercase, lowercase, digit, and special symbol
const REGEX_PATTERNS = {
    name: /^[a-zA-Z\s]{2,50}$/,
    email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
    phone: /^[6-9]\d{9}$/,
    password: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/
};

// Helper function to show or clear error text and apply green/red border classes
function setFieldStatus(inputElement, errorElement, errorMessage) {
    if (!errorElement) return;

    if (errorMessage) {
        errorElement.textContent = errorMessage;
        if (inputElement) {
            inputElement.classList.add("is-invalid");
            inputElement.classList.remove("is-valid");
            inputElement.setAttribute("aria-invalid", "true");
        }
    } else {
        errorElement.textContent = "";
        if (inputElement) {
            inputElement.classList.remove("is-invalid");
            inputElement.classList.add("is-valid");
            inputElement.removeAttribute("aria-invalid");
        }
    }
}

// Password strength meter calculation
// Calculates points based on length, lowercase, uppercase, number, and special characters
function checkPasswordStrength(password) {
    let score = 0;
    if (!password) return { score: 0, label: "", color: "", width: "0%" };

    if (password.length >= 8) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/\d/.test(password)) score++;
    if (/[@$!%*?&#]/.test(password)) score++;

    // Return strength label, progress bar width, and bar color
    if (score <= 2) {
        return { score: score, label: "Weak", color: "#ef4444", width: "30%" };
    } else if (score === 3 || score === 4) {
        return { score: score, label: "Medium", color: "#f59e0b", width: "70%" };
    } else {
        return { score: score, label: "Strong", color: "#10b981", width: "100%" };
    }
}

// Builds the visual password strength bar under the password input box
function initPasswordStrengthMeter() {
    const passwordInput = document.getElementById("password");
    if (!passwordInput) return;

    // Create the strength bar container if it's not in the HTML yet
    let meterContainer = document.getElementById("passwordStrengthContainer");
    if (!meterContainer) {
        meterContainer = document.createElement("div");
        meterContainer.id = "passwordStrengthContainer";
        meterContainer.style.marginTop = "6px";
        meterContainer.style.display = "none";
        meterContainer.innerHTML = `
            <div style="height: 6px; width: 100%; background-color: #e2e8f0; border-radius: 4px; overflow: hidden;">
                <div id="passwordStrengthBar" style="height: 100%; width: 0%; transition: width 0.3s ease, background-color 0.3s ease;"></div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                <span id="passwordStrengthText" style="font-size: 0.8rem; font-weight: 600;"></span>
                <span style="font-size: 0.75rem; color: var(--bs-secondary, #64748b);">Min 8 chars (A-Z, a-z, 0-9, @$!%*?&#)</span>
            </div>
        `;
        passwordInput.parentNode.insertBefore(meterContainer, passwordInput.nextSibling);
    }

    const strengthBar = document.getElementById("passwordStrengthBar");
    const strengthText = document.getElementById("passwordStrengthText");

    // Update the strength bar dynamically on each keystroke
    passwordInput.addEventListener("input", function () {
        const val = this.value;
        if (!val) {
            meterContainer.style.display = "none";
            return;
        }

        meterContainer.style.display = "block";
        const result = checkPasswordStrength(val);

        if (strengthBar) {
            strengthBar.style.width = result.width;
            strengthBar.style.backgroundColor = result.color;
        }
        if (strengthText) {
            strengthText.textContent = "Strength: " + result.label;
            strengthText.style.color = result.color;
        }
    });
}

// Field-by-field validation functions:

// 1. Check Full Name
function validateFullNameField() {
    const input = document.getElementById("fullName");
    const error = document.getElementById("fullNameError");
    if (!input || !error) return true;

    const val = input.value.trim();
    if (val === "") {
        setFieldStatus(input, error, "Full name is required.");
        return false;
    } else if (!REGEX_PATTERNS.name.test(val)) {
        setFieldStatus(input, error, "Name must contain alphabets and spaces only (2-50 characters).");
        return false;
    } else {
        setFieldStatus(input, error, "");
        return true;
    }
}

// 2. Check Email Address
function validateEmailField() {
    const input = document.getElementById("email");
    const error = document.getElementById("emailError");
    if (!input || !error) return true;

    const val = input.value.trim();
    if (val === "") {
        setFieldStatus(input, error, "Email address is required.");
        return false;
    } else if (!REGEX_PATTERNS.email.test(val)) {
        setFieldStatus(input, error, "Please enter a valid email address (e.g., student@charusat.edu.in).");
        return false;
    } else {
        setFieldStatus(input, error, "");
        return true;
    }
}

// 3. Check Mobile Number
function validatePhoneField() {
    const input = document.getElementById("phone");
    const error = document.getElementById("phoneError");
    if (!input || !error) return true;

    // Remove optional +91 prefix and extra spaces
    const cleaned = input.value.trim().replace(/^(\+91|\+91\s)/, "").replace(/[\s-]/g, "");
    if (cleaned === "") {
        setFieldStatus(input, error, "Mobile number is required.");
        return false;
    } else if (!REGEX_PATTERNS.phone.test(cleaned)) {
        setFieldStatus(input, error, "Enter a valid 10-digit mobile number starting with 6-9.");
        return false;
    } else {
        setFieldStatus(input, error, "");
        return true;
    }
}

// 4. Check Course Selection
function validateCourseField() {
    const input = document.getElementById("course");
    const error = document.getElementById("courseError");
    if (!input || !error) return true;

    if (input.value === "") {
        setFieldStatus(input, error, "Please select your enrolled course/branch.");
        return false;
    } else {
        setFieldStatus(input, error, "");
        return true;
    }
}

// 5. Check Academic Year Selection
function validateYearField() {
    const input = document.getElementById("year");
    const error = document.getElementById("yearError");
    if (!input || !error) return true;

    if (input.value === "") {
        setFieldStatus(input, error, "Please select your academic year.");
        return false;
    } else {
        setFieldStatus(input, error, "");
        return true;
    }
}

// 6. Check Gender Selection
function validateGenderField() {
    const error = document.getElementById("genderError");
    const selectedGender = document.querySelector('input[name="gender"]:checked');
    if (!error) return true;

    if (!selectedGender) {
        error.textContent = "Please select your gender.";
        return false;
    } else {
        error.textContent = "";
        return true;
    }
}

// 7. Check Password
function validatePasswordField() {
    const input = document.getElementById("password");
    const error = document.getElementById("passwordError");
    if (!input || !error) return true;

    const val = input.value;
    if (val === "") {
        setFieldStatus(input, error, "Password is required.");
        return false;
    } else if (!REGEX_PATTERNS.password.test(val)) {
        setFieldStatus(input, error, "Password must be at least 8 characters long and contain uppercase, lowercase, digit, and special symbol (@$!%*?&#).");
        return false;
    } else {
        setFieldStatus(input, error, "");
        return true;
    }
}

// 8. Check Confirm Password
function validateConfirmPasswordField() {
    const passInput = document.getElementById("password");
    const confirmInput = document.getElementById("confirmPassword");
    const error = document.getElementById("confirmPasswordError");
    if (!confirmInput || !error) return true;

    const confirmVal = confirmInput.value;
    const passVal = passInput ? passInput.value : "";

    if (confirmVal === "") {
        setFieldStatus(confirmInput, error, "Please confirm your password.");
        return false;
    } else if (confirmVal !== passVal) {
        setFieldStatus(confirmInput, error, "Passwords do not match.");
        return false;
    } else {
        setFieldStatus(confirmInput, error, "");
        return true;
    }
}

// 9. Check Terms Checkbox
function validateTermsField() {
    const input = document.getElementById("terms");
    const error = document.getElementById("termsError");
    if (!input || !error) return true;

    if (!input.checked) {
        error.textContent = "You must agree to the Terms of Service to proceed.";
        return false;
    } else {
        error.textContent = "";
        return true;
    }
}

// 10. Check Dependent Dropdown Locations (Practical 6 Extension)
function validateLocationFields() {
    const country = document.getElementById("country");
    const state = document.getElementById("state");
    const city = document.getElementById("city");
    const error = document.getElementById("locationError");

    // If location fields don't exist on this form, consider it valid
    if (!country || !state || !city || !error) return true;

    if (!country.value || country.value === "") {
        error.textContent = "Please select your country.";
        country.classList.add("is-invalid");
        return false;
    }
    if (!state.value || state.value === "") {
        error.textContent = "Please select your state/province.";
        state.classList.add("is-invalid");
        return false;
    }
    if (!city.value || city.value === "") {
        error.textContent = "Please select your city.";
        city.classList.add("is-invalid");
        return false;
    }

    error.textContent = "";
    country.classList.remove("is-invalid");
    state.classList.remove("is-invalid");
    city.classList.remove("is-invalid");
    return true;
}

// 11. HTML5 Canvas Custom CAPTCHA (Easy to read, No extra design / noise)
let currentCaptchaCode = "";

function generateCaptcha() {
    const canvas = document.getElementById("captchaCanvas");
    if (!canvas) return;

    canvas.width = 140;
    canvas.height = 40;

    const ctx = canvas.getContext("2d");
    // Simple, crystal-clear characters: exactly 4 characters
    const chars = "23456789ABCDEFGHJKLMNPQRSTUVWXYZ";
    let captchaStr = "";
    for (let i = 0; i < 4; i++) {
        captchaStr += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    currentCaptchaCode = captchaStr.toUpperCase();

    // Clear canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Clean, solid contrasting background
    const isDark = document.documentElement.getAttribute("data-theme") === "dark";
    ctx.fillStyle = isDark ? "#0f172a" : "#f1f5f9";
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Plain, bold, easy-to-read text without any noise lines or distortion dots
    ctx.fillStyle = isDark ? "#38bdf8" : "#1e40af";
    ctx.font = "bold 23px Consolas, Monaco, 'Courier New', monospace";
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";

    // Draw characters evenly spaced and crystal clear
    const spacing = 28;
    const startX = (canvas.width - (3 * spacing)) / 2;
    for (let i = 0; i < currentCaptchaCode.length; i++) {
        ctx.fillText(currentCaptchaCode[i], startX + i * spacing, canvas.height / 2);
    }

    // Clear captcha input box and error message if any
    const captchaInput = document.getElementById("captchaInput");
    if (captchaInput) {
        captchaInput.value = "";
        captchaInput.classList.remove("is-valid", "is-invalid");
    }
    const captchaError = document.getElementById("captchaError");
    if (captchaError) captchaError.textContent = "";
}

function validateCaptchaField() {
    const input = document.getElementById("captchaInput");
    const error = document.getElementById("captchaError");
    if (!input || !error) return true;

    // Fallback: ensure a valid code exists
    if (!currentCaptchaCode) {
        generateCaptcha();
    }

    const val = input.value.trim().toUpperCase();
    if (val === "") {
        setFieldStatus(input, error, "Please enter the 4-character CAPTCHA code.");
        return false;
    } else if (val !== currentCaptchaCode) {
        setFieldStatus(input, error, "CAPTCHA does not match. Please try again.");
        return false;
    } else {
        setFieldStatus(input, error, "");
        return true;
    }
}

function initCanvasCaptcha() {
    const canvas = document.getElementById("captchaCanvas");
    const refreshBtn = document.getElementById("refreshCaptchaBtn");

    if (canvas) {
        generateCaptcha();
    }
    if (refreshBtn) {
        refreshBtn.addEventListener("click", function (e) {
            e.preventDefault();
            generateCaptcha();
            const captchaInput = document.getElementById("captchaInput");
            if (captchaInput) captchaInput.focus();
        });
    }

    const captchaInput = document.getElementById("captchaInput");
    if (captchaInput) {
        captchaInput.setAttribute("maxlength", "4");
        captchaInput.setAttribute("autocomplete", "off");
        captchaInput.style.textTransform = "uppercase";

        captchaInput.addEventListener("input", function () {
            this.value = this.value.toUpperCase();
            if (this.value.trim().length === 4) {
                validateCaptchaField();
            } else if (this.value.trim().length < 4) {
                const captchaError = document.getElementById("captchaError");
                if (captchaError) captchaError.textContent = "";
                this.classList.remove("is-invalid", "is-valid");
            }
        });

        captchaInput.addEventListener("blur", function () {
            if (this.value.trim().length > 0) {
                validateCaptchaField();
            }
        });
    }
}

// 12. Dependent Dropdowns (Country -> State -> City) - Practical 6 Extension
const DEPENDENT_LOCATION_DATA = {
    "India": {
        "Gujarat": ["Anand", "Changa", "Ahmedabad", "Vadodara", "Surat", "Rajkot", "Gandhinagar", "Bhavnagar"],
        "Maharashtra": ["Mumbai", "Pune", "Nagpur", "Nashik", "Aurangabad"],
        "Rajasthan": ["Jaipur", "Udaipur", "Jodhpur", "Kota"],
        "Karnataka": ["Bengaluru", "Mysuru", "Mangaluru", "Hubballi"]
    },
    "United States": {
        "California": ["San Francisco", "Los Angeles", "San Diego", "San Jose"],
        "New York": ["New York City", "Buffalo", "Rochester", "Albany"],
        "Texas": ["Austin", "Houston", "Dallas", "San Antonio"]
    },
    "United Kingdom": {
        "England": ["London", "Manchester", "Birmingham", "Oxford", "Cambridge"],
        "Scotland": ["Edinburgh", "Glasgow", "Aberdeen"]
    },
    "Canada": {
        "Ontario": ["Toronto", "Ottawa", "Mississauga", "Hamilton"],
        "British Columbia": ["Vancouver", "Victoria", "Kelowna"]
    }
};

function initDependentDropdowns() {
    const countrySelect = document.getElementById("country");
    const stateSelect = document.getElementById("state");
    const citySelect = document.getElementById("city");

    if (!countrySelect || !stateSelect || !citySelect) return;

    // Populate countries
    countrySelect.innerHTML = '<option value="" selected disabled>Select Country</option>';
    Object.keys(DEPENDENT_LOCATION_DATA).forEach(country => {
        const opt = document.createElement("option");
        opt.value = country;
        opt.textContent = country;
        countrySelect.appendChild(opt);
    });

    // When country changes, populate states
    countrySelect.addEventListener("change", function () {
        const selectedCountry = this.value;
        stateSelect.innerHTML = '<option value="" selected disabled>Select State / Province</option>';
        citySelect.innerHTML = '<option value="" selected disabled>Select City</option>';
        stateSelect.disabled = false;
        citySelect.disabled = true;

        if (DEPENDENT_LOCATION_DATA[selectedCountry]) {
            Object.keys(DEPENDENT_LOCATION_DATA[selectedCountry]).forEach(state => {
                const opt = document.createElement("option");
                opt.value = state;
                opt.textContent = state;
                stateSelect.appendChild(opt);
            });
        }
        validateLocationFields();
    });

    // When state changes, populate cities
    stateSelect.addEventListener("change", function () {
        const selectedCountry = countrySelect.value;
        const selectedState = this.value;
        citySelect.innerHTML = '<option value="" selected disabled>Select City</option>';
        citySelect.disabled = false;

        if (DEPENDENT_LOCATION_DATA[selectedCountry] && DEPENDENT_LOCATION_DATA[selectedCountry][selectedState]) {
            DEPENDENT_LOCATION_DATA[selectedCountry][selectedState].forEach(city => {
                const opt = document.createElement("option");
                opt.value = city;
                opt.textContent = city;
                citySelect.appendChild(opt);
            });
        }
        validateLocationFields();
    });

    citySelect.addEventListener("change", validateLocationFields);
}

// Real-time validation listeners:
// Validates fields as the user types (input event) or leaves the field (blur event)
function initRealtimeValidation() {
    const fullName = document.getElementById("fullName");
    if (fullName) {
        fullName.addEventListener("input", validateFullNameField);
        fullName.addEventListener("blur", validateFullNameField);
    }

    const email = document.getElementById("email");
    if (email) {
        email.addEventListener("input", validateEmailField);
        email.addEventListener("blur", validateEmailField);
    }

    const phone = document.getElementById("phone");
    if (phone) {
        phone.addEventListener("input", validatePhoneField);
        phone.addEventListener("blur", validatePhoneField);
    }

    const course = document.getElementById("course");
    if (course) {
        course.addEventListener("change", validateCourseField);
        course.addEventListener("blur", validateCourseField);
    }

    const year = document.getElementById("year");
    if (year) {
        year.addEventListener("change", validateYearField);
        year.addEventListener("blur", validateYearField);
    }

    const genderInputs = document.querySelectorAll('input[name="gender"]');
    genderInputs.forEach(radio => {
        radio.addEventListener("change", validateGenderField);
    });

    const password = document.getElementById("password");
    if (password) {
        password.addEventListener("input", () => {
            validatePasswordField();
            const confirm = document.getElementById("confirmPassword");
            if (confirm && confirm.value) validateConfirmPasswordField();
        });
        password.addEventListener("blur", validatePasswordField);
    }

    const confirmPassword = document.getElementById("confirmPassword");
    if (confirmPassword) {
        confirmPassword.addEventListener("input", validateConfirmPasswordField);
        confirmPassword.addEventListener("blur", validateConfirmPasswordField);
    }

    const terms = document.getElementById("terms");
    if (terms) {
        terms.addEventListener("change", validateTermsField);
    }
}

// Submit handler for the student registration form
function validateRegister() {
    const isNameValid = validateFullNameField();
    const isEmailValid = validateEmailField();
    const isPhoneValid = validatePhoneField();
    const isCourseValid = validateCourseField();
    const isYearValid = validateYearField();
    const isGenderValid = validateGenderField();
    const isPasswordValid = validatePasswordField();
    const isConfirmValid = validateConfirmPasswordField();
    const isTermsValid = validateTermsField();
    const isLocationValid = validateLocationFields();
    const isCaptchaValid = validateCaptchaField();

    const isFormValid = isNameValid && isEmailValid && isPhoneValid &&
        isCourseValid && isYearValid && isGenderValid &&
        isPasswordValid && isConfirmValid && isTermsValid &&
        isLocationValid && isCaptchaValid;

    if (isFormValid) {
        if (window.location.protocol === "file:") {
            alert("Registration Successful! Redirecting to login page...");
            window.location.href = "login.html";
        }
        return true;
    } else {
        // Focus the first invalid field so the student can fix it easily
        const firstInvalid = document.querySelector(".is-invalid, input:invalid");
        if (firstInvalid) firstInvalid.focus();
        return false;
    }
}

// Submit handler for student login form
function validateLogin() {
    const email = document.getElementById("email");
    const password = document.getElementById("password");
    const loginEmailError = document.getElementById("loginEmailError");
    const loginPasswordError = document.getElementById("loginPasswordError");

    let isValid = true;

    if (!email || !REGEX_PATTERNS.email.test(email.value.trim())) {
        if (loginEmailError) loginEmailError.textContent = "Please enter a valid university email address.";
        if (email) email.classList.add("is-invalid");
        isValid = false;
    } else {
        if (loginEmailError) loginEmailError.textContent = "";
        if (email) email.classList.remove("is-invalid");
    }

    if (!password || password.value.trim().length < 3) {
        if (loginPasswordError) loginPasswordError.textContent = "Please enter your password.";
        if (password) password.classList.add("is-invalid");
        isValid = false;
    } else {
        if (loginPasswordError) loginPasswordError.textContent = "";
        if (password) password.classList.remove("is-invalid");
    }

    if (isValid) {
        if (window.location.protocol === "file:") {
            alert("Login Successful! Redirecting to dashboard...");
            if (email && email.value.trim().toLowerCase() === "admin@charusat.edu.in") {
                window.location.href = "admin.html";
            } else {
                window.location.href = "dashboard.html";
            }
        }
        return true;
    }

    return false;
}


// ============================================================================
// PRACTICAL 6: RENDERING EXTERNAL JSON DATA USING FETCH API, SEARCH AND FILTER
// ============================================================================

// Global state variables for Practical 6
// These variables keep track of which dataset is open, the current page, and filter settings.
let currentDataset = "events";      // Can be "events", "students", or "faqs"
let rawJsonData = [];               // Full list fetched from external JSON
let filteredJsonData = [];          // Filtered and sorted list after user search/filters
let jsonCurrentPage = 1;            // Active pagination page
let jsonItemsPerPage = 6;           // Default number of cards shown per page
let jsonSearchQuery = "";           // User's text search term
let jsonSelectedCategory = "All";   // Category filter choice
let jsonSelectedSort = "default";   // Active sorting option

// Helper function to figure out the right relative path to the data folder
// If the current webpage is inside the /pages/ folder, we need to go up one folder with ../data/
function getJsonDataPath(fileName) {
    const isInsidePages = window.location.pathname.includes("/pages/") || window.location.pathname.includes("\\pages\\");
    return (isInsidePages ? "../data/" : "./data/") + fileName + ".json";
}

// Fetches JSON data from the external file using the standard Fetch API
// Shows a loading spinner while fetching, and caches data in localStorage for offline use
async function fetchExternalJsonData(datasetName) {
    const cardsContainer = document.getElementById("jsonCardsContainer");
    const statusBar = document.getElementById("jsonStatusBar");

    // Display loading spinner while data is being fetched
    if (cardsContainer) {
        cardsContainer.innerHTML = `
            <div class="json-loading-state" style="grid-column: 1 / -1;">
                <div class="json-spinner"></div>
                <p>Fetching ${datasetName}.json using JavaScript Fetch API...</p>
            </div>
        `;
    }
    if (statusBar) {
        statusBar.textContent = "Loading data from server...";
    }

    const dataPath = getJsonDataPath(datasetName);

    try {
        const response = await fetch(dataPath);
        if (!response.ok) {
            throw new Error("HTTP error with status: " + response.status);
        }
        const data = await response.json();

        // Advanced Extension: Save latest JSON in localStorage for offline access
        localStorage.setItem("cached_" + datasetName + "_data", JSON.stringify(data));
        return data;
    } catch (error) {
        console.warn("Fetch failed or blocked by browser CORS policy. Checking localStorage cache...", error);

        // Check if we previously cached this dataset in localStorage
        const cached = localStorage.getItem("cached_" + datasetName + "_data");
        if (cached) {
            return JSON.parse(cached);
        }

        // If fetch fails and no cache exists, show a friendly error with a retry button
        if (cardsContainer) {
            cardsContainer.innerHTML = `
                <div class="json-error-state" style="grid-column: 1 / -1;">
                    <p style="color: #ef4444; font-weight: 600; margin-bottom: 8px;">
                        Could not load ${datasetName}.json via Fetch API.
                    </p>
                    <p style="font-size: 0.88rem; margin-bottom: 16px;">
                        Note: If opening HTML files directly from local disk (file:///), browser security blocks fetch requests. Run using a local web server (like Live Server or XAMPP).
                    </p>
                    <button type="button" class="btn btn-outline" id="jsonRetryBtn">Try Again</button>
                </div>
            `;
            const retryBtn = document.getElementById("jsonRetryBtn");
            if (retryBtn) {
                retryBtn.addEventListener("click", () => loadDataset(datasetName));
            }
        }
        return [];
    }
}

// Populates the category dropdown filter based on the distinct categories found in the JSON file
function updateCategoryDropdownOptions() {
    const categorySelect = document.getElementById("jsonCategoryFilter");
    if (!categorySelect) return;

    // Use Set to get unique category values from the JSON records
    const categories = new Set();
    rawJsonData.forEach(item => {
        if (item.category) categories.add(item.category);
        if (item.course) categories.add(item.course);
    });

    // Reset dropdown with default 'All' option
    categorySelect.innerHTML = `<option value="All">All Categories</option>`;
    categories.forEach(cat => {
        const opt = document.createElement("option");
        opt.value = cat;
        opt.textContent = cat;
        categorySelect.appendChild(opt);
    });
    categorySelect.value = jsonSelectedCategory;
}

// Updates the sort dropdown options depending on whether we are viewing events, students, or FAQs
function updateSortDropdownOptions() {
    const sortSelect = document.getElementById("jsonSortSelect");
    if (!sortSelect) return;

    if (currentDataset === "events") {
        sortSelect.innerHTML = `
            <option value="default">Sort by: Default</option>
            <option value="date-asc">Date: Earliest First</option>
            <option value="date-desc">Date: Latest First</option>
            <option value="title-asc">Title: A to Z</option>
            <option value="title-desc">Title: Z to A</option>
        `;
    } else if (currentDataset === "students") {
        sortSelect.innerHTML = `
            <option value="default">Sort by: Default</option>
            <option value="name-asc">Name: A to Z</option>
            <option value="name-desc">Name: Z to A</option>
            <option value="cgpa-desc">Highest CGPA</option>
            <option value="attendance-desc">Highest Attendance</option>
        `;
    } else {
        sortSelect.innerHTML = `
            <option value="default">Sort by: Default</option>
            <option value="title-asc">Question: A to Z</option>
            <option value="title-desc">Question: Z to A</option>
        `;
    }
    sortSelect.value = jsonSelectedSort;
}

// Filters and sorts the array using JavaScript array methods (.filter, .sort, .slice)
function applyFilterAndPagination() {
    const cardsContainer = document.getElementById("jsonCardsContainer");
    const statusBar = document.getElementById("jsonStatusBar");
    const paginationContainer = document.getElementById("jsonPagination");

    if (!cardsContainer) return;

    // Step 1: Filter array by search keyword
    let result = rawJsonData.filter(item => {
        const query = jsonSearchQuery.trim().toLowerCase();
        if (!query) return true;

        if (currentDataset === "events") {
            return (item.title && item.title.toLowerCase().includes(query)) ||
                   (item.description && item.description.toLowerCase().includes(query)) ||
                   (item.venue && item.venue.toLowerCase().includes(query)) ||
                   (item.organizer && item.organizer.toLowerCase().includes(query));
        } else if (currentDataset === "students") {
            return (item.name && item.name.toLowerCase().includes(query)) ||
                   (item.id && item.id.toLowerCase().includes(query)) ||
                   (item.course && item.course.toLowerCase().includes(query)) ||
                   (item.city && item.city.toLowerCase().includes(query));
        } else {
            return (item.question && item.question.toLowerCase().includes(query)) ||
                   (item.answer && item.answer.toLowerCase().includes(query)) ||
                   (item.category && item.category.toLowerCase().includes(query));
        }
    });

    // Step 2: Filter array by selected category
    if (jsonSelectedCategory !== "All") {
        result = result.filter(item => {
            return (item.category && item.category === jsonSelectedCategory) ||
                   (item.course && item.course === jsonSelectedCategory);
        });
    }

    // Step 3: Sort array using Array.prototype.sort
    if (jsonSelectedSort === "date-asc") {
        result.sort((a, b) => new Date(a.date) - new Date(b.date));
    } else if (jsonSelectedSort === "date-desc") {
        result.sort((a, b) => new Date(b.date) - new Date(a.date));
    } else if (jsonSelectedSort === "title-asc" || jsonSelectedSort === "name-asc") {
        result.sort((a, b) => (a.title || a.name || a.question).localeCompare(b.title || b.name || b.question));
    } else if (jsonSelectedSort === "title-desc" || jsonSelectedSort === "name-desc") {
        result.sort((a, b) => (b.title || b.name || b.question).localeCompare(a.title || a.name || a.question));
    } else if (jsonSelectedSort === "cgpa-desc") {
        result.sort((a, b) => (b.cgpa || 0) - (a.cgpa || 0));
    } else if (jsonSelectedSort === "attendance-desc") {
        result.sort((a, b) => (b.attendance || 0) - (a.attendance || 0));
    }

    filteredJsonData = result;
    const totalItems = filteredJsonData.length;
    const totalPages = Math.ceil(totalItems / jsonItemsPerPage) || 1;

    // Keep current page within valid bounds
    if (jsonCurrentPage > totalPages) jsonCurrentPage = 1;
    if (jsonCurrentPage < 1) jsonCurrentPage = 1;

    // Step 4: Pagination slicing using Array.prototype.slice
    const startIndex = (jsonCurrentPage - 1) * jsonItemsPerPage;
    const endIndex = Math.min(startIndex + jsonItemsPerPage, totalItems);
    const pageItems = filteredJsonData.slice(startIndex, endIndex);

    // Update status text showing total results
    if (statusBar) {
        if (totalItems === 0) {
            statusBar.textContent = "No records found matching your criteria.";
        } else {
            statusBar.textContent = `Showing ${startIndex + 1} - ${endIndex} of ${totalItems} records (Page ${jsonCurrentPage} of ${totalPages})`;
        }
    }

    // Step 5: Render items into HTML cards
    if (pageItems.length === 0) {
        cardsContainer.innerHTML = `
            <div class="json-empty-state" style="grid-column: 1 / -1;">
                <h5>No results found</h5>
                <p>Try clearing your search keyword or changing the category filter.</p>
            </div>
        `;
    } else {
        cardsContainer.innerHTML = pageItems.map(item => renderSingleItemCard(item)).join("");
    }

    // Step 6: Render pagination buttons
    renderPaginationButtons(totalPages, paginationContainer);
}

// Renders an individual card based on whether we are displaying an event, student, or FAQ
function renderSingleItemCard(item) {
    if (currentDataset === "events") {
        return `
            <div class="json-card">
                <div>
                    <div class="json-card-top">
                        <span class="json-card-badge">${item.category || "Event"}</span>
                        <span class="json-card-date"> ${item.date}</span>
                    </div>
                    <h4 class="json-card-title">${item.title}</h4>
                    <p class="json-card-desc">${item.description}</p>
                </div>
                <div>
                    <div class="json-card-details">
                        <div><strong>Time:</strong> ${item.time}</div>
                        <div><strong>Venue:</strong> ${item.venue}</div>
                        <div><strong>Organizer:</strong> ${item.organizer}</div>
                    </div>
                    <div class="json-card-footer">
                        <small style="color: var(--bs-primary); font-weight: 600;">Deadline: ${item.registrationDeadline}</small>
                        <span class="badge-tag">${item.status}</span>
                    </div>
                </div>
            </div>
        `;
    } else if (currentDataset === "students") {
        return `
            <div class="json-card">
                <div>
                    <div class="json-card-top">
                        <span class="json-card-badge">${item.id}</span>
                        <span class="json-card-date">Sem ${item.semester}</span>
                    </div>
                    <h4 class="json-card-title">${item.name}</h4>
                    <p class="json-card-desc">${item.course}</p>
                </div>
                <div>
                    <div class="json-card-details">
                        <div><strong>Email:</strong> ${item.email}</div>
                        <div><strong>City:</strong> ${item.city}</div>
                        <div style="display: flex; justify-content: space-between;">
                            <span><strong>CGPA:</strong> ${item.cgpa}</span>
                            <span><strong>Attendance:</strong> ${item.attendance}%</span>
                        </div>
                    </div>
                    <div class="json-card-footer">
                        <small style="color: var(--bs-secondary);">CHARUSAT Student</small>
                        <span class="badge-tag">Verified</span>
                    </div>
                </div>
            </div>
        `;
    } else {
        return `
            <div class="json-card">
                <div>
                    <div class="json-card-top">
                        <span class="json-card-badge">${item.category || "General"}</span>
                        <span class="json-card-date">FAQ #${item.id}</span>
                    </div>
                    <h4 class="json-card-title">${item.question}</h4>
                    <p class="json-card-desc">${item.answer}</p>
                </div>
                <div class="json-card-footer">
                    <small style="color: var(--bs-secondary);">StudentHub Helpdesk</small>
                    <span class="badge-tag">Answered</span>
                </div>
            </div>
        `;
    }
}

// Builds the pagination buttons (Previous, Page numbers, and Next)
function renderPaginationButtons(totalPages, container) {
    if (!container) return;
    if (totalPages <= 1) {
        container.innerHTML = "";
        return;
    }

    let buttonsHtml = "";

    // Previous page button
    buttonsHtml += `
        <button type="button" class="json-page-btn" data-page="${jsonCurrentPage - 1}" ${jsonCurrentPage === 1 ? "disabled" : ""}>
            &larr; Prev
        </button>
    `;

    // Page number buttons
    for (let i = 1; i <= totalPages; i++) {
        buttonsHtml += `
            <button type="button" class="json-page-btn ${i === jsonCurrentPage ? "active" : ""}" data-page="${i}">
                ${i}
            </button>
        `;
    }

    // Next page button
    buttonsHtml += `
        <button type="button" class="json-page-btn" data-page="${jsonCurrentPage + 1}" ${jsonCurrentPage === totalPages ? "disabled" : ""}>
            Next &rarr;
        </button>
    `;

    container.innerHTML = buttonsHtml;

    // Attach click listeners to all pagination buttons
    container.querySelectorAll(".json-page-btn").forEach(btn => {
        btn.addEventListener("click", function () {
            const pageNum = parseInt(this.getAttribute("data-page"));
            if (pageNum && pageNum !== jsonCurrentPage && pageNum >= 1 && pageNum <= totalPages) {
                jsonCurrentPage = pageNum;
                applyFilterAndPagination();
            }
        });
    });
}

// Loads and switches to a specific dataset (events, students, or faqs)
async function loadDataset(datasetName) {
    currentDataset = datasetName;
    jsonCurrentPage = 1;
    jsonSearchQuery = "";
    jsonSelectedCategory = "All";
    jsonSelectedSort = "default";

    // Clear search input box
    const searchInput = document.getElementById("jsonSearchInput");
    if (searchInput) searchInput.value = "";

    // Highlight active dataset tab button
    document.querySelectorAll(".json-tab-btn").forEach(tab => {
        tab.classList.toggle("active", tab.getAttribute("data-dataset") === datasetName);
    });

    // Fetch the JSON records
    rawJsonData = await fetchExternalJsonData(datasetName);

    // Update filter dropdowns and render the cards
    updateCategoryDropdownOptions();
    updateSortDropdownOptions();
    applyFilterAndPagination();
}

// Injects the Practical 6 UI module into the webpage and sets up event listeners
function initJsonDataModule() {
    // Look for dedicated page mount containers or fallback to dashboard mount
    let mountContainer = document.getElementById("eventsMountContainer") || 
                         document.getElementById("faqMountContainer") || 
                         document.getElementById("jsonModuleMount");

    if (!mountContainer) {
        // Check if we are on dashboard page
        const dashboardLayout = document.querySelector(".dashboard-layout");
        if (dashboardLayout && dashboardLayout.parentNode) {
            mountContainer = document.createElement("section");
            mountContainer.id = "jsonModuleMount";
            mountContainer.className = "json-module-section";
            dashboardLayout.parentNode.appendChild(mountContainer);
        } else {
            // Check if there is a main container on dashboard or events pages
            const mainContainer = document.querySelector("main .container");
            if (mainContainer && (window.location.pathname.includes("dashboard") || window.location.pathname.includes("events"))) {
                mountContainer = document.createElement("section");
                mountContainer.id = "jsonModuleMount";
                mountContainer.className = "json-module-section";
                mainContainer.appendChild(mountContainer);
            }
        }
    }

    if (!mountContainer) return;

    // Determine initial dataset
    const isFaqPage = window.location.pathname.includes("faq") || mountContainer.id === "faqMountContainer";
    const initialDataset = isFaqPage ? "faqs" : "events";

    // Build the outer HTML skeleton with search bar, filter dropdowns, and cards grid
    mountContainer.innerHTML = `
        <div class="json-module-header">
            <div>
                <h3>${isFaqPage ? "Frequently Asked Questions Directory" : "Campus Events & Student Directory"}</h3>
                <p>Dynamically fetched from external JSON files using JavaScript Fetch API with live search, filter, sorting, and pagination.</p>
            </div>
            <div class="json-dataset-tabs">
                <button type="button" class="json-tab-btn ${initialDataset === 'events' ? 'active' : ''}" data-dataset="events">Events</button>
                <button type="button" class="json-tab-btn ${initialDataset === 'students' ? 'active' : ''}" data-dataset="students">Students</button>
                <button type="button" class="json-tab-btn ${initialDataset === 'faqs' ? 'active' : ''}" data-dataset="faqs">FAQs</button>
            </div>
        </div>

        <div class="json-controls-bar">
            <input type="text" id="jsonSearchInput" class="form-control" placeholder="Search by title, description, or keyword...">
            <select id="jsonCategoryFilter" class="form-select">
                <option value="All">All Categories</option>
            </select>
            <select id="jsonSortSelect" class="form-select">
                <option value="default">Sort by: Default</option>
            </select>
            <select id="jsonLimitSelect" class="form-select" style="min-width: 110px;">
                <option value="4">4 per page</option>
                <option value="6" selected>6 per page</option>
                <option value="8">8 per page</option>
            </select>
            <button type="button" class="btn btn-outline" id="jsonResetBtn">Reset</button>
        </div>

        <div class="json-status-bar" id="jsonStatusBar">
            Loading data...
        </div>

        <div class="json-cards-grid" id="jsonCardsContainer"></div>

        <div class="json-pagination-wrapper" id="jsonPagination"></div>
    `;

    // Attach event listeners to controls:

    // 1. Live text search
    const searchInput = document.getElementById("jsonSearchInput");
    if (searchInput) {
        searchInput.addEventListener("input", function () {
            jsonSearchQuery = this.value;
            jsonCurrentPage = 1; // Reset to page 1 on new search
            applyFilterAndPagination();
        });
    }

    // 2. Category dropdown filter
    const categorySelect = document.getElementById("jsonCategoryFilter");
    if (categorySelect) {
        categorySelect.addEventListener("change", function () {
            jsonSelectedCategory = this.value;
            jsonCurrentPage = 1;
            applyFilterAndPagination();
        });
    }

    // 3. Sorting dropdown
    const sortSelect = document.getElementById("jsonSortSelect");
    if (sortSelect) {
        sortSelect.addEventListener("change", function () {
            jsonSelectedSort = this.value;
            applyFilterAndPagination();
        });
    }

    // 4. Items per page selection
    const limitSelect = document.getElementById("jsonLimitSelect");
    if (limitSelect) {
        limitSelect.addEventListener("change", function () {
            jsonItemsPerPage = parseInt(this.value) || 6;
            jsonCurrentPage = 1;
            applyFilterAndPagination();
        });
    }

    // 5. Reset button
    const resetBtn = document.getElementById("jsonResetBtn");
    if (resetBtn) {
        resetBtn.addEventListener("click", function () {
            jsonSearchQuery = "";
            jsonSelectedCategory = "All";
            jsonSelectedSort = "default";
            if (searchInput) searchInput.value = "";
            if (categorySelect) categorySelect.value = "All";
            if (sortSelect) sortSelect.value = "default";
            jsonCurrentPage = 1;
            applyFilterAndPagination();
        });
    }

    // 6. Dataset tab switcher buttons
    document.querySelectorAll(".json-tab-btn").forEach(tab => {
        tab.addEventListener("click", function () {
            const dataset = this.getAttribute("data-dataset");
            if (dataset && dataset !== currentDataset) {
                loadDataset(dataset);
            }
        });
    });

    // Start by loading the default dataset
    loadDataset(initialDataset);
}


// ============================================================================
// INITIALIZATION ON DOMContentLoaded
// ============================================================================
// When the browser finishes loading the HTML document, initialize all practical components
document.addEventListener("DOMContentLoaded", function () {
    // Start Practical 4 interactive components
    initThemeToggle();
    initHamburgerMenu();
    initNotificationBanner();
    initSlider();
    initFaqAccordion();
    initModalPopups();

    // Start Practical 5 form validation and password strength
    initPasswordStrengthMeter();
    initCanvasCaptcha();
    initRealtimeValidation();

    // Start Practical 6 extensions and JSON data rendering
    initDependentDropdowns();
    initJsonDataModule();

    // Attach submit listeners to forms if they exist on the current page
    const registerForm = document.getElementById("registerForm");
    if (registerForm) {
        registerForm.addEventListener("submit", function (e) {
            const isValid = validateRegister();
            if (!isValid) {
                e.preventDefault();
            } else if (window.location.protocol === "file:") {
                e.preventDefault();
            }
        });
    }

    const loginForm = document.getElementById("loginForm");
    if (loginForm) {
        loginForm.addEventListener("submit", function (e) {
            const isValid = validateLogin();
            if (!isValid) {
                e.preventDefault();
            } else if (window.location.protocol === "file:") {
                e.preventDefault();
            }
        });
    }

    const feedbackForm = document.getElementById("feedbackForm");
    if (feedbackForm) {
        feedbackForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const category = document.getElementById("feedbackCategory");
            const comments = document.getElementById("feedbackComments");
            const rating = document.querySelector('input[name="rating"]:checked');

            if (!category || !category.value) {
                alert("Please select a feedback category.");
                if (category) category.focus();
                return;
            }
            if (!rating) {
                alert("Please select a star rating.");
                return;
            }
            if (!comments || !comments.value.trim()) {
                alert("Please enter your comments/feedback.");
                if (comments) comments.focus();
                return;
            }

            alert("Thank you for your feedback! Your response has been recorded.");
            feedbackForm.reset();
        });
    }
});

