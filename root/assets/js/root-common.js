/**
 * Root Control Center - Common JavaScript Functions
 * Shared utilities and functions for all root management pages
 */

// Common form submission handler
function submitForm(formId, action) {
    const form = document.getElementById(formId);
    if (form) {
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = action;
        form.appendChild(actionInput);
        form.submit();
    }
}

// Common confirmation dialog
function confirmAction(message, callback) {
    if (confirm(message)) {
        if (typeof callback === 'function') {
            callback();
        }
        return true;
    }
    return false;
}

// Format numbers with commas
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Format file sizes
function formatBytes(bytes, precision = 2) {
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let i = 0;

    while (bytes >= 1024 && i < units.length - 1) {
        bytes /= 1024;
        i++;
    }

    return `${bytes.toFixed(precision)} ${units[i]}`;
}

// Show loading spinner
function showLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = '<div class="spinner-purple mx-auto"></div>';
    }
}

// Hide loading spinner
function hideLoading(elementId, content = '') {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = content;
    }
}

// Show notification toast
function showNotification(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(toast);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 5000);
}

// Copy text to clipboard
function copyToClipboard(text, successMessage = 'Copied to clipboard!') {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showNotification(successMessage, 'success');
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showNotification(successMessage, 'success');
    }
}

// Debounce function for search inputs
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Live search functionality
function initLiveSearch(inputId, tableId, searchColumns = []) {
    const searchInput = document.getElementById(inputId);
    const table = document.getElementById(tableId);

    if (!searchInput || !table) return;

    const debouncedSearch = debounce((query) => {
        const rows = table.querySelectorAll('tbody tr');
        const searchTerm = query.toLowerCase();

        rows.forEach(row => {
            let shouldShow = false;

            if (searchColumns.length === 0) {
                // Search all columns
                const text = row.textContent.toLowerCase();
                shouldShow = text.includes(searchTerm);
            } else {
                // Search specific columns
                searchColumns.forEach(columnIndex => {
                    const cell = row.cells[columnIndex];
                    if (cell && cell.textContent.toLowerCase().includes(searchTerm)) {
                        shouldShow = true;
                    }
                });
            }

            row.style.display = shouldShow ? '' : 'none';
        });
    }, 300);

    searchInput.addEventListener('input', (e) => {
        debouncedSearch(e.target.value);
    });
}

// Table sorting functionality
function initTableSorting(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const headers = table.querySelectorAll('th[data-sortable]');

    headers.forEach((header, index) => {
        header.style.cursor = 'pointer';
        header.innerHTML += ' <i class="fas fa-sort text-muted"></i>';

        header.addEventListener('click', () => {
            sortTable(table, index, header);
        });
    });
}

function sortTable(table, columnIndex, header) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const isAscending = !header.classList.contains('sorted-asc');

    // Remove previous sort indicators
    table.querySelectorAll('th').forEach(th => {
        th.classList.remove('sorted-asc', 'sorted-desc');
        const icon = th.querySelector('i');
        if (icon) {
            icon.className = 'fas fa-sort text-muted';
        }
    });

    rows.sort((a, b) => {
        const aVal = a.cells[columnIndex].textContent.trim();
        const bVal = b.cells[columnIndex].textContent.trim();

        // Try to parse as numbers
        const aNum = parseFloat(aVal.replace(/[^0-9.-]/g, ''));
        const bNum = parseFloat(bVal.replace(/[^0-9.-]/g, ''));

        if (!isNaN(aNum) && !isNaN(bNum)) {
            return isAscending ? aNum - bNum : bNum - aNum;
        }

        // String comparison
        return isAscending ?
            aVal.localeCompare(bVal) :
            bVal.localeCompare(aVal);
    });

    // Update header indicator
    header.classList.add(isAscending ? 'sorted-asc' : 'sorted-desc');
    const icon = header.querySelector('i');
    if (icon) {
        icon.className = `fas fa-sort-${isAscending ? 'up' : 'down'} text-primary`;
    }

    // Reorder rows
    rows.forEach(row => tbody.appendChild(row));
}

// Auto-refresh functionality
function initAutoRefresh(intervalMinutes = 5) {
    if (intervalMinutes > 0) {
        setTimeout(() => {
            window.location.reload();
        }, intervalMinutes * 60 * 1000);
    }
}

// Form validation helpers
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePassword(password, minLength = 8) {
    if (password.length < minLength) {
        return `Password must be at least ${minLength} characters long`;
    }

    if (!/[A-Z]/.test(password)) {
        return 'Password must contain at least one uppercase letter';
    }

    if (!/[a-z]/.test(password)) {
        return 'Password must contain at least one lowercase letter';
    }

    if (!/[0-9]/.test(password)) {
        return 'Password must contain at least one number';
    }

    return null; // Valid
}

function validatePasswordConfirmation(password, confirmPassword) {
    if (password !== confirmPassword) {
        return 'Passwords do not match';
    }
    return null; // Valid
}

// Common modal functions
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        new bootstrap.Modal(modal).show();
    }
}

function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        const bsModal = bootstrap.Modal.getInstance(modal);
        if (bsModal) {
            bsModal.hide();
        }
    }
}

// Status toggle functions
function toggleStatus(itemId, newStatus, endpoint = '') {
    const message = `Change status to ${newStatus}?`;

    confirmAction(message, () => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = endpoint;

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'toggle_status';

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'item_id';
        idInput.value = itemId;

        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'new_status';
        statusInput.value = newStatus;

        form.appendChild(actionInput);
        form.appendChild(idInput);
        form.appendChild(statusInput);

        document.body.appendChild(form);
        form.submit();
    });
}

// Delete functions
function deleteItem(itemId, itemName, endpoint = '') {
    const message = `Are you sure you want to delete '${itemName}'? This action cannot be undone.`;

    confirmAction(message, () => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = endpoint;

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'item_id';
        idInput.value = itemId;

        form.appendChild(actionInput);
        form.appendChild(idInput);

        document.body.appendChild(form);
        form.submit();
    });
}

// Initialize common functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    const popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Add fade-in animation to main content
    const mainContent = document.getElementById('content');
    if (mainContent) {
        mainContent.classList.add('fade-in');
    }

    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) {
                closeBtn.click();
            }
        }, 5000);
    });
});

// Export functions for use in other scripts
window.RootCommon = {
    submitForm,
    confirmAction,
    formatNumber,
    formatBytes,
    showLoading,
    hideLoading,
    showNotification,
    copyToClipboard,
    debounce,
    initLiveSearch,
    initTableSorting,
    initAutoRefresh,
    validateEmail,
    validatePassword,
    validatePasswordConfirmation,
    showModal,
    hideModal,
    toggleStatus,
    deleteItem
};