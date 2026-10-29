/* =========================================
   PHARMACY MANAGEMENT SYSTEM - UI HELPERS
   ========================================= */

/* -----------------------------------------
   1. TOAST NOTIFICATIONS
   ----------------------------------------- */

function createToastContainer() {
    let container = document.querySelector(".toast-container");

    if (!container) {
        container = document.createElement("div");
        container.className = "toast-container";
        container.setAttribute("aria-live", "polite");
        container.setAttribute("aria-atomic", "true");
        document.body.appendChild(container);
    }

    return container;
}

function showToast(message, type = "success") {
    const container = createToastContainer();

    const toast = document.createElement("div");
    toast.className = `toast ${type}`;
    toast.setAttribute("role", "status");
    toast.textContent = message;

    container.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 4000);
}

/* -----------------------------------------
   2. BUTTON LOADING STATE
   ----------------------------------------- */

function setLoading(button, isLoading, loadingText = "Please wait...") {
    if (!button) return;

    if (isLoading) {
        button.dataset.originalText = button.textContent;
        button.textContent = loadingText;
        button.disabled = true;
    } else {
        button.textContent = button.dataset.originalText || "Submit";
        button.disabled = false;
    }
}

/* -----------------------------------------
   3. ACTIVE NAVIGATION LINK
   ----------------------------------------- */

function highlightActiveNav() {
    const currentPage = window.location.pathname.split("/").pop();
    const links = document.querySelectorAll(".nav-links a");

    links.forEach(link => {
        const linkPage = link.getAttribute("href")?.split("/").pop();

        if (linkPage === currentPage) {
            link.classList.add("active");
        }
    });
}

/* -----------------------------------------
   4. REQUIRED FIELD VALIDATION
   ----------------------------------------- */

function validateRequiredFields(form) {
    const requiredFields = form.querySelectorAll("[required]");
    let isValid = true;

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = "var(--danger)";
            isValid = false;
        } else {
            field.style.borderColor = "var(--border)";
        }
    });

    return isValid;
}

/* -----------------------------------------
   5. CONFIRM DIALOG
   ----------------------------------------- */

function confirmAction(message) {
    return confirm(message);
}

/* -----------------------------------------
   6. RUN WHEN PAGE LOADS
   ----------------------------------------- */

document.addEventListener("DOMContentLoaded", () => {
    highlightActiveNav();

    const forms = document.querySelectorAll("form");

    forms.forEach(form => {
        form.addEventListener("submit", event => {
            if (!validateRequiredFields(form)) {
                event.preventDefault();
                showToast("Please complete all required fields.", "error");
            }
        });
    });
});