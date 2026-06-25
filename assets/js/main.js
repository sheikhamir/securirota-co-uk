// Main JavaScript functionality for Rota Management System
console.log('Main.js loaded - Enhanced header version 3.0');

// Main initialization - moved to bottom to prevent duplication

// Modal functionality
function initializeModals() {
    // Initialize all modals on the page
    document.querySelectorAll('.modal').forEach(function (modal) {
        // Handle close button clicks
        const closeBtn = modal.querySelector('.close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                modal.style.display = 'none';
                // Add smooth fade out animation
                modal.style.opacity = '0';
                setTimeout(() => {
                    modal.style.display = 'none';
                    modal.style.opacity = '1';
                }, 200);
            });
        }

        // Handle clicks on the modal backdrop only (not on modal dialog or its contents)
        modal.addEventListener('click', function (event) {
            // Get the modal dialog element
            const modalDialog = modal.querySelector('.modal-dialog');

            // Only close if clicking outside the modal dialog
            if (modalDialog && !modalDialog.contains(event.target)) {
                modal.style.opacity = '0';
                setTimeout(() => {
                    modal.style.display = 'none';
                    modal.style.opacity = '1';
                }, 200);
            }
        });
    });
}

// Show modal with content
function showModal(title, content, size = 'default', modalId = 'genericModal') {
    const modal = document.getElementById(modalId);

    if (!modal) {
        console.error('Modal not found:', modalId);
        return;
    }

    if (title && content) {
        const modalTitle = modal.querySelector('#modalTitle') || modal.querySelector('h3');
        const modalBody = modal.querySelector('#modalBody') || modal.querySelector('.modal-body');

        if (modalTitle) modalTitle.textContent = title;
        if (modalBody) modalBody.innerHTML = content;
    }

    // Handle modal size
    const modalDialog = modal.querySelector('.modal-dialog');
    if (modalDialog) {
        // Remove existing size classes
        modalDialog.classList.remove('extra-large', 'large', 'small');

        // Add size class if not default
        if (size !== 'default') {
            modalDialog.classList.add(size);
        }
    }

    // Show modal with smooth animation
    modal.style.display = 'block';
    modal.style.opacity = '0';
    requestAnimationFrame(() => {
        modal.style.opacity = '1';
    });
}

// Close modal
function closeModal(modalId = 'genericModal') {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.style.display = 'none';
            modal.style.opacity = '1';
        }, 200);
    }
}

// Calendar/Rota functionality
function initializeCalendar() {
    // Add click handlers for shift blocks
    document.querySelectorAll('.shift-block').forEach(function (shift) {
        shift.addEventListener('click', function () {
            const shiftId = this.dataset.shiftId;
            editShift(shiftId);
        });
    });

    // Add click handlers for empty calendar cells
    document.querySelectorAll('.calendar-cell').forEach(function (cell) {
        if (!cell.classList.contains('header') && cell.dataset.date) {
            cell.addEventListener('dblclick', function () {
                const date = this.dataset.date;
                const officerId = this.dataset.officerId;
                createShift(date, officerId);
            });
        }
    });
}

// Form validation and submission
function initializeForms() {
    // Add form validation
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });

    // File upload handling
    document.querySelectorAll('input[type="file"]').forEach(function (input) {
        input.addEventListener('change', function () {
            validateFile(this);
        });
    });
}

// Data table initialization
function initializeDataTables() {
    // DataTables are now initialized per-page with specific configurations
    // This function is kept for backward compatibility
    console.log('DataTables initialized per page');
}

// Officer info link functionality
function setupOfficerInfoLink(selectElement, linkContainerId = null) {
    if (!selectElement) return;

    // Create or get the link container
    let linkContainer;
    if (linkContainerId) {
        linkContainer = document.getElementById(linkContainerId);
    } else {
        // Create a new container after the select element
        linkContainer = document.createElement('div');
        linkContainer.id = selectElement.id + '_officer_link_container';
        linkContainer.style.marginTop = '5px';
        selectElement.parentNode.insertBefore(linkContainer, selectElement.nextSibling);
    }

    function updateOfficerLink() {
        const officerId = selectElement.value;

        if (officerId && officerId !== '' && officerId !== 'null') {
            let officerName = selectElement.dataset.officerName || '';
            if (!officerName && selectElement.options) {
                const selectedOption = selectElement.options[selectElement.selectedIndex];
                officerName = selectedOption ? selectedOption.text : 'Officer';
            }
            officerName = officerName || 'Officer';

            // Construct the correct URL for officer detail page
            let officerDetailUrl;
            if (typeof BASE_URL !== 'undefined') {
                // BASE_URL should already point to /
                officerDetailUrl = BASE_URL + 'pages/officer_detail.php?id=' + officerId;
            } else {
                // Fallback: construct URL based on current location
                const currentPath = window.location.pathname;
                if (currentPath.includes('/pages/')) {
                    // We're in a pages subdirectory, go up one level
                    officerDetailUrl = window.location.protocol + '//' + window.location.host +
                        currentPath.substring(0, currentPath.lastIndexOf('/pages/')) +
                        '/pages/officer_detail.php?id=' + officerId;
                } else {
                    // We're in root directory
                    officerDetailUrl = window.location.protocol + '//' + window.location.host +
                        window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/')) +
                        '/pages/officer_detail.php?id=' + officerId;
                }
            }

            linkContainer.innerHTML = `
                <a href="${officerDetailUrl}" 
                   target="_blank" 
                   class="btn btn-sm btn-outline-info" 
                   style="font-size: 0.75em; padding: 2px 8px; margin-top: 3px;">
                    <i class="fas fa-external-link-alt"></i> View ${officerName} Info
                </a>
            `;
        } else {
            linkContainer.innerHTML = '';
        }
    }

    // Set up the change event listener
    selectElement.addEventListener('change', updateOfficerLink);

    // Initialize the link based on current selection
    updateOfficerLink();
}

function getOfficerDisplayName(officer) {
    if (!officer) return '';
    return `${officer.first_name || ''} ${officer.last_name || ''}${officer.staff_id ? ' - ' + officer.staff_id : ''}${officer.phone ? ' - ' + officer.phone : ''}`.trim();
}

function initOfficerAjaxPicker(config) {
    const hiddenInput = document.getElementById(config.hiddenInputId);
    const searchInput = document.getElementById(config.searchInputId);
    const results = document.getElementById(config.resultsId);

    if (!hiddenInput || !searchInput || !results || searchInput.disabled) return;

    let searchTimer = null;
    let requestToken = 0;

    const hideResults = () => {
        results.style.display = 'none';
        results.innerHTML = '';
    };

    const clearSelection = () => {
        hiddenInput.value = '';
        hiddenInput.dataset.officerName = '';
        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const selectOfficer = (officer) => {
        hiddenInput.value = officer.id;
        hiddenInput.dataset.officerName = getOfficerDisplayName(officer);
        searchInput.value = hiddenInput.dataset.officerName;
        hideResults();
        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const highlight = (text, query) => {
        const safeText = escapeHtml(text || '');
        if (!query || query.length < 2) return safeText;
        const escapedQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        return safeText.replace(new RegExp(`(${escapedQuery})`, 'gi'), '<strong>$1</strong>');
    };

    const renderResults = (officers, query) => {
        if (!officers.length) {
            results.innerHTML = '<div class="officer-search-empty">No officers found</div>';
            results.style.display = 'block';
            return;
        }

        results.innerHTML = officers.map((officer, index) => `
            <div class="search-result-item" data-index="${index}" role="button" tabindex="-1">
                <div>${highlight(getOfficerDisplayName(officer), query)}</div>
                <div>${officer.staff_id ? 'Staff ID: ' + highlight(officer.staff_id, query) : 'Officer'}</div>
            </div>
        `).join('');

        results.querySelectorAll('.search-result-item').forEach((item) => {
            item.addEventListener('mousedown', (event) => {
                event.preventDefault();
                selectOfficer(officers[parseInt(item.dataset.index, 10)]);
            });
        });

        results.style.display = 'block';
    };

    const searchOfficers = (query) => {
        const token = ++requestToken;
        const baseUrl = typeof BASE_URL !== 'undefined' ? BASE_URL : '../';
        results.innerHTML = '<div class="officer-search-loading"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';
        results.style.display = 'block';

        fetch(baseUrl + 'api/search_officers.php?q=' + encodeURIComponent(query) + '&limit=20')
            .then(response => response.json())
            .then(data => {
                if (token !== requestToken) return;
                if (data.success) {
                    renderResults(data.officers || [], query);
                } else {
                    results.innerHTML = '<div class="officer-search-error">Error loading officers</div>';
                }
            })
            .catch(error => {
                console.error('Officer search error:', error);
                if (token === requestToken) {
                    results.innerHTML = '<div class="officer-search-error">Error loading officers</div>';
                }
            });
    };

    searchInput.addEventListener('input', () => {
        const query = searchInput.value.trim();
        clearTimeout(searchTimer);

        if (!query) {
            clearSelection();
            hideResults();
            return;
        }

        if (query !== hiddenInput.dataset.officerName) {
            clearSelection();
        }

        searchTimer = setTimeout(() => {
            if (query.length >= 2) {
                searchOfficers(query);
            } else {
                results.innerHTML = '<div class="officer-search-empty">Type at least 2 characters</div>';
                results.style.display = 'block';
            }
        }, 250);
    });

    searchInput.addEventListener('blur', () => {
        setTimeout(() => {
            if (!hiddenInput.value && searchInput.value.trim() !== '') {
                searchInput.value = '';
            }
            hideResults();
        }, 150);
    });

    searchInput.addEventListener('keydown', (event) => {
        const items = results.querySelectorAll('.search-result-item');
        let selectedIndex = Array.from(items).findIndex(item => item.classList.contains('selected'));

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            selectedIndex = selectedIndex < items.length - 1 ? selectedIndex + 1 : 0;
            items.forEach(item => item.classList.remove('selected'));
            if (items[selectedIndex]) items[selectedIndex].classList.add('selected');
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            selectedIndex = selectedIndex > 0 ? selectedIndex - 1 : items.length - 1;
            items.forEach(item => item.classList.remove('selected'));
            if (items[selectedIndex]) items[selectedIndex].classList.add('selected');
        } else if (event.key === 'Enter' && selectedIndex >= 0 && items[selectedIndex]) {
            event.preventDefault();
            items[selectedIndex].dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
        } else if (event.key === 'Escape') {
            hideResults();
            searchInput.blur();
        }
    });

    setupOfficerInfoLink(hiddenInput, config.linkContainerId);

    if (typeof config.onChange === 'function') {
        hiddenInput.addEventListener('change', () => config.onChange(hiddenInput));
        config.onChange(hiddenInput);
    }
}

// Shift management functions
function escapeHtml(value) {
    if (value === null || value === undefined) {
        return '';
    }

    const div = document.createElement('div');
    div.textContent = String(value);
    return div.innerHTML;
}

function getAppBaseUrl() {
    if (typeof BASE_URL !== 'undefined') {
        return BASE_URL;
    }

    return '/rota/';
}

function getStoredUploadUrl(path) {
    if (!path) {
        return '';
    }

    if (/^https?:\/\//i.test(path)) {
        return path;
    }

    return getAppBaseUrl() + String(path).replace(/^\/+/, '');
}

function formatShiftTimestamp(value) {
    if (!value) {
        return 'Not recorded';
    }

    const date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) {
        return escapeHtml(value);
    }

    return date.toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function renderAttendancePhotoCard(label, imagePath, timestamp) {
    const imageUrl = getStoredUploadUrl(imagePath);
    const safeLabel = escapeHtml(label);

    if (!imageUrl) {
        return `
            <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 0.75rem; background: #f9fafb;">
                <div style="font-weight: 600; color: #374151; margin-bottom: 0.35rem;">${safeLabel}</div>
                <div style="color: #6b7280; font-size: 0.875rem;">No photo recorded</div>
                <div style="color: #6b7280; font-size: 0.8125rem; margin-top: 0.25rem;">${formatShiftTimestamp(timestamp)}</div>
            </div>
        `;
    }

    return `
        <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 0.75rem; background: #fff;">
            <div style="font-weight: 600; color: #374151; margin-bottom: 0.5rem;">${safeLabel}</div>
            <a href="${escapeHtml(imageUrl)}" target="_blank" rel="noopener" style="display: block;">
                <img src="${escapeHtml(imageUrl)}" alt="${safeLabel}" style="width: 100%; max-height: 220px; object-fit: cover; border-radius: 6px; border: 1px solid #e5e7eb;">
            </a>
            <div style="color: #6b7280; font-size: 0.8125rem; margin-top: 0.5rem;">${formatShiftTimestamp(timestamp)}</div>
            <a href="${escapeHtml(imageUrl)}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary" style="margin-top: 0.5rem;">
                <i class="fas fa-external-link-alt"></i> Open full size
            </a>
        </div>
    `;
}

function getShiftLateReason(shift) {
    const notes = shift.notes || '';
    const match = notes.match(/Late check-in reason:\s*([^\r\n]+)/i);
    return match ? match[1].trim() : '';
}

function showShiftLateReason(reason) {
    alert(reason || 'No late check-in reason recorded.');
}

function isShiftActive(shift) {
    return shift.status === 'in_progress' || Boolean(shift.checkin_timestamp && !shift.checkout_timestamp);
}

function renderAttendanceVerification(shift) {
    if (!shift.checkin_image && !shift.checkout_image && !shift.checkin_timestamp && !shift.checkout_timestamp) {
        return '';
    }

    const lateReason = getShiftLateReason(shift);

    return `
        <div class="form-group" style="margin-top: 1rem;">
            <label>Attendance Verification:</label>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem;">
                ${renderAttendancePhotoCard('Check-in Photo', shift.checkin_image, shift.checkin_timestamp)}
                ${renderAttendancePhotoCard('Check-out Photo', shift.checkout_image, shift.checkout_timestamp)}
            </div>
            ${lateReason ? `
                <button type="button" class="btn btn-sm btn-outline-warning" style="margin-top: 0.75rem;" onclick='showShiftLateReason(${JSON.stringify(lateReason)})'>
                    <i class="fas fa-exclamation-triangle"></i> View late check-in reason
                </button>
            ` : ''}
        </div>
    `;
}

function editShift(shiftId) {
    fetch(`/api/get_shift.php?id=${shiftId}`)
        .then(response => {
            console.log('API Response status:', response.status);
            console.log('API Response headers:', response.headers);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text(); // Get as text first to see what we're getting
        })
        .then(text => {
            console.log('API Response text:', text);
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    const shift = data.shift;
                    const activeShift = isShiftActive(shift);
                    const activeFieldState = activeShift ? 'disabled' : '';
                    const activeReadonlyState = activeShift ? 'readonly' : '';
                    const activeNotice = activeShift ? `
                        <div style="padding: 0.75rem; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; color: #9a3412; margin-bottom: 1rem;">
                            <i class="fas fa-lock"></i>
                            This shift is active. Only the end time can be changed.
                        </div>
                    ` : '';
                    const hiddenActiveFields = activeShift ? `
                        <input type="hidden" name="officer_id" value="${shift.officer_id || ''}">
                        <input type="hidden" name="role_id" value="${shift.role_id || ''}">
                        <input type="hidden" name="role" value="${escapeHtml(shift.role || '')}">
                        <input type="hidden" name="status" value="${shift.status || ''}">
                        <input type="hidden" name="custom_officer_rate" value="${shift.custom_officer_rate || ''}">
                    ` : '';
                    const deleteButton = activeShift ? '' : `<button type="button" class="btn btn-danger" onclick="deleteShift(${shiftId})">Delete Shift</button>`;

                    // Load roles before showing the form
                    fetch('/api/get_roles.php')
                        .then(response => response.json())
                        .then(rolesData => {
                            let roleOptions = '';
                            if (rolesData.success) {
                                roleOptions = rolesData.roles.map(role => 
                                    `<option value="${role.id}" ${role.id == shift.role_id ? 'selected' : ''}>${role.name}</option>`
                                ).join('');
                            } else {
                                // Fallback roles if API fails
                                roleOptions = `
                                    <option value="1" ${shift.role_id == 1 ? 'selected' : ''}>Security Officer</option>
                                    <option value="2" ${shift.role_id == 2 ? 'selected' : ''}>Controller</option>
                                    <option value="3" ${shift.role_id == 3 ? 'selected' : ''}>Manager</option>
                                    <option value="4" ${shift.role_id == 4 ? 'selected' : ''}>Supervisor</option>
                                `;
                            }

                            const content = `
                            <form id="editShiftForm" onsubmit="updateShift(event, ${shiftId})">
                                <input type="hidden" name="site_id" value="${shift.site_id}">
                                <input type="hidden" name="shift_date" value="${shift.shift_date}">
                                <input type="hidden" name="original_status" value="${shift.status}">
                                ${hiddenActiveFields}
                                ${activeNotice}
                                <div class="form-group">
                                    <label>Officer:</label>
                                    <div class="officer-search-wrap">
                                        <input type="hidden"
                                               name="officer_id"
                                               id="edit_officer_select"
                                               value="${escapeHtml(shift.officer_id || '')}"
                                               data-officer-name="${escapeHtml(shift.officer_display_name || shift.officer_name || '')}">
                                        <input type="text"
                                               id="edit_officer_select_search"
                                               class="form-control"
                                               value="${escapeHtml(shift.officer_id ? (shift.officer_display_name || shift.officer_name || '') : '')}"
                                               placeholder="Unallocated - search to assign officer"
                                               autocomplete="off"
                                               ${activeFieldState}>
                                        <div id="edit_officer_select_results" class="officer-search-results"></div>
                                    </div>
                                    <div id="edit_officer_link_container"></div>
                                </div>
                                <div class="form-group" style="display: none;" id="customRateGroupMainEdit">
                                    <label for="custom_officer_rate_main_edit">
                                        Custom Officer Rate (£/hour):
                                        <span style="font-size: 0.8em; color: #6b7280; font-weight: normal;">(Optional - overrides officer's default rate)</span>
                                    </label>
                                    <input type="number" 
                                           name="custom_officer_rate" 
                                           id="custom_officer_rate_main_edit"
                                           class="form-control" 
                                           step="0.01" 
                                           min="0" 
                                           value="${shift.custom_officer_rate || ''}"
                                           ${activeReadonlyState}
                                           placeholder="Leave blank to use officer's default rate">
                                    <small class="form-text text-muted">
                                        Only set this if you want to pay a different rate for this specific shift
                                    </small>
                                </div>
                                <div class="form-group">
                                    <label>Start Time:</label>
                                    <input type="time" name="start_time" class="form-control" value="${shift.start_time}" required ${activeReadonlyState}>
                                </div>
                                <div class="form-group">
                                    <label>End Time:</label>
                                    <input type="time" name="end_time" class="form-control" value="${shift.end_time}" required>
                                </div>
                                <div class="form-group">
                                    <label>Role:</label>
                                    <select name="role_id" class="form-control" required ${activeFieldState}>
                                        ${roleOptions}
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Status:</label>
                                    <select name="status" class="form-control" required ${activeFieldState}>
                                        <option value="unallocated" ${shift.status == 'unallocated' ? 'selected' : ''}>Unallocated</option>
                                        <option value="allocated" ${shift.status == 'allocated' ? 'selected' : ''}>Allocated</option>
                                        <option value="confirmed" ${shift.status == 'confirmed' ? 'selected' : ''}>Confirmed</option>
                                        <option value="in_progress" ${shift.status == 'in_progress' ? 'selected' : ''}>In Progress</option>
                                        <option value="declined" ${shift.status == 'declined' ? 'selected' : ''}>Declined</option>
                                        <option value="completed" ${shift.status == 'completed' ? 'selected' : ''}>Completed</option>
                                    </select>
                                </div>
                                ${renderAttendanceVerification(shift)}
                                <div class="form-group">
                                    <label>Notes:</label>
                                    <textarea name="notes" class="form-control" rows="3" ${activeReadonlyState}>${shift.notes || ''}</textarea>
                                </div>
                                <div class="d-flex gap-10">
                                    <button type="submit" class="btn btn-primary">Update Shift</button>
                                    ${deleteButton}
                                </div>
                            </form>
                            `;
                            showModal('Edit Shift', content);

                            // Setup officer info link after modal is shown
                            setTimeout(() => {
                                const officerSelect = document.getElementById('edit_officer_select');
                                if (officerSelect) {
                                    initOfficerAjaxPicker({
                                        hiddenInputId: 'edit_officer_select',
                                        searchInputId: 'edit_officer_select_search',
                                        resultsId: 'edit_officer_select_results',
                                        linkContainerId: 'edit_officer_link_container',
                                        onChange: toggleCustomRateFieldMainEdit
                                    });
                                    
                                    // Show custom rate field if officer is selected or custom rate exists
                                    const customRateInput = document.getElementById('custom_officer_rate_main_edit');
                                    const hasCustomRate = customRateInput && customRateInput.value && customRateInput.value.trim() !== '';
                                    const hasOfficer = officerSelect.value && officerSelect.value !== '';
                                    
                                    if (hasOfficer || hasCustomRate) {
                                        const customRateGroup = document.getElementById('customRateGroupMainEdit');
                                        if (customRateGroup) {
                                            customRateGroup.style.display = 'block';
                                        }
                                    }
                                }
                            }, 100);
                        })
                        .catch(roleError => {
                            console.error('Error loading roles:', roleError);
                            alert('Error loading roles. Please try again.');
                        });

                } else {
                    alert('Error: ' + data.message);
                }
            } catch (parseError) {
                console.error('JSON Parse Error:', parseError);
                console.error('Raw response:', text);
                alert('Error: Received invalid response from server');
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            alert('Error loading shift data: ' + error.message);
        });
}

function updateShift(event, shiftId) {
    event.preventDefault();
    const formData = new FormData(event.target);

    // Check if this was originally a declined shift
    const originalStatus = formData.get('original_status');
    if (originalStatus === 'declined' && window.isAdmin) {
        const confirmDeclined = confirm('This is a declined shift. Are you sure you want to change the status of this shift?');
        if (!confirmDeclined) {
            return; // Exit if admin doesn't confirm
        }
    }

    formData.append('id', shiftId);

    fetch('/api/update_shift.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Shift updated successfully');
                location.reload();
            } else {
                alert('Error updating shift: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating shift');
        });
}

function addShift(event) {
    event.preventDefault();
    const formData = new FormData(event.target);

    fetch('/api/create_shift.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Shift created successfully');
                location.reload();
            } else {
                alert('Error creating shift: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error creating shift');
        });
}

function deleteShift(shiftId) {
    // First fetch the shift data to check if it's declined
    fetch(`/api/get_shift.php?id=${shiftId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const shift = data.shift;
                let confirmMessage = 'Are you sure you want to delete this shift?';

                // Special handling for declined shifts
                if (shift.status === 'declined') {
                    if (window.isAdmin) {
                        confirmMessage = 'This is a declined shift. Are you sure you want to change the status of this shift or delete this shift?';
                    } else {
                        alert('This shift has been declined and cannot be deleted.');
                        return; // Exit for non-admin users
                    }
                }

                if (confirm(confirmMessage)) {
                    fetch(`/api/delete_shift.php?id=${shiftId}`, {
                        method: 'DELETE'
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Shift deleted successfully');
                                location.reload();
                            } else {
                                alert('Error deleting shift: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error deleting shift');
                        });
                }
            } else {
                alert('Error loading shift data: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error loading shift:', error);
            alert('Error loading shift data');
        });
}

// Form validation
function validateForm(form) {
    let isValid = true;

    // Clear previous error messages
    form.querySelectorAll('.error-message').forEach(el => el.remove());

    // Check required fields
    form.querySelectorAll('[required]').forEach(function (field) {
        if (!field.value.trim()) {
            showFieldError(field, 'This field is required');
            isValid = false;
        }
    });

    // Validate email fields
    form.querySelectorAll('input[type="email"]').forEach(function (field) {
        if (field.value && !isValidEmail(field.value)) {
            showFieldError(field, 'Please enter a valid email address');
            isValid = false;
        }
    });

    // Validate phone fields
    form.querySelectorAll('input[type="tel"]').forEach(function (field) {
        if (field.value && !isValidPhone(field.value)) {
            showFieldError(field, 'Please enter a valid phone number');
            isValid = false;
        }
    });

    return isValid;
}

function showFieldError(field, message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.style.color = '#dc3545';
    errorDiv.style.fontSize = '0.875rem';
    errorDiv.style.marginTop = '5px';
    errorDiv.textContent = message;

    field.style.borderColor = '#dc3545';
    field.parentNode.appendChild(errorDiv);
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
    return phoneRegex.test(phone);
}

function validateFile(input) {
    const file = input.files[0];
    if (file) {
        // Check file size (5MB limit)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            input.value = '';
            return false;
        }

        // Check file type
        const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if (!allowedTypes.includes(file.type)) {
            alert('Invalid file type. Please upload images, PDF, or Word documents only.');
            input.value = '';
            return false;
        }
    }
    return true;
}

// Table functionality
function sortTable(header) {
    const table = header.closest('table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const columnIndex = Array.from(header.parentNode.children).indexOf(header);
    const isAscending = header.classList.contains('sort-asc');

    // Remove existing sort classes
    table.querySelectorAll('th').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
    });

    // Add new sort class
    header.classList.add(isAscending ? 'sort-desc' : 'sort-asc');

    // Sort rows
    rows.sort((a, b) => {
        const aValue = a.children[columnIndex].textContent.trim();
        const bValue = b.children[columnIndex].textContent.trim();

        // Try to parse as numbers
        const aNum = parseFloat(aValue);
        const bNum = parseFloat(bValue);

        if (!isNaN(aNum) && !isNaN(bNum)) {
            return isAscending ? bNum - aNum : aNum - bNum;
        } else {
            return isAscending ? bValue.localeCompare(aValue) : aValue.localeCompare(bValue);
        }
    });

    // Reorder rows in table
    rows.forEach(row => tbody.appendChild(row));
}

function filterTable(searchInput) {
    const table = searchInput.closest('.table-responsive').querySelector('table');
    const tbody = table.querySelector('tbody');
    const rows = tbody.querySelectorAll('tr');
    const searchTerm = searchInput.value.toLowerCase();

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
}

// Export functions
function exportToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    const wb = XLSX.utils.table_to_book(table);
    XLSX.writeFile(wb, filename + '.xlsx');
}

function exportToPDF(elementId, filename) {
    const element = document.getElementById(elementId);
    html2pdf().from(element).save(filename + '.pdf');
}

// Notification functions
function showNotification(message, type = 'info', duration = 4000) {
    // Remove any existing notifications
    document.querySelectorAll('.notification').forEach(notification => {
        notification.remove();
    });

    const notification = document.createElement('div');
    notification.className = `notification ${type}`;

    // Create notification content with icon
    const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
    };

    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <i class="${icons[type] || icons.info}" style="font-size: 1.25rem;"></i>
            <span style="font-weight: 600;">${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: inherit; margin-left: auto; cursor: pointer; opacity: 0.7; font-size: 1.125rem; padding: 0;" title="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    document.body.appendChild(notification);

    // Trigger animation
    requestAnimationFrame(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    });

    // Auto-remove notification
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }
    }, duration);

    return notification;
}

// Utility functions
function formatCurrency(amount) {
    return '£' + parseFloat(amount).toFixed(2);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB');
}

function formatTime(timeString) {
    return new Date('1970-01-01T' + timeString + 'Z').toLocaleTimeString('en-GB', {
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'UTC'
    });
}

// =============================================================================
// SIDEBAR FUNCTIONALITY
// =============================================================================

function initializeSidebar() {
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');

            // Optional: Add overlay for mobile
            if (window.innerWidth <= 992) {
                if (sidebar.classList.contains('show')) {
                    addSidebarOverlay();
                } else {
                    removeSidebarOverlay();
                }
            }
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function (event) {
            if (window.innerWidth <= 992 &&
                sidebar.classList.contains('show') &&
                !sidebar.contains(event.target) &&
                !sidebarToggle.contains(event.target)) {
                sidebar.classList.remove('show');
                removeSidebarOverlay();
            }
        });

        // Handle window resize
        window.addEventListener('resize', function () {
            if (window.innerWidth > 992) {
                sidebar.classList.remove('show');
                removeSidebarOverlay();
            }
        });
    }
}

function addSidebarOverlay() {
    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1025;
            transition: opacity 0.3s ease;
        `;
        document.body.appendChild(overlay);

        overlay.addEventListener('click', function () {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.classList.remove('show');
                removeSidebarOverlay();
            }
        });
    }
}

function removeSidebarOverlay() {
    const overlay = document.querySelector('.sidebar-overlay');
    if (overlay) {
        overlay.remove();
    }
}

// =============================================================================
// FLASH MESSAGE AUTO-DISMISS
// =============================================================================

function initializeFlashMessages() {
    const alerts = document.querySelectorAll('.alert-dismissible');

    alerts.forEach(function (alert) {
        // Auto-dismiss after 5 seconds
        setTimeout(function () {
            if (alert && alert.parentNode) {
                alert.style.transition = 'opacity 0.3s ease';
                alert.style.opacity = '0';
                setTimeout(function () {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 300);
            }
        }, 5000);
    });
}

// Function to toggle custom rate field visibility for main edit form
function toggleCustomRateFieldMainEdit(selectElement) {
    const customRateGroup = document.getElementById('customRateGroupMainEdit');
    if (customRateGroup) {
        if (selectElement.value && selectElement.value !== '') {
            customRateGroup.style.display = 'block';
        } else {
            customRateGroup.style.display = 'none';
            // Clear the custom rate input when hiding
            const customRateInput = customRateGroup.querySelector('input[name="custom_officer_rate"]');
            if (customRateInput) {
                customRateInput.value = '';
            }
        }
    }
}

// Update the main DOMContentLoaded event listener
document.addEventListener('DOMContentLoaded', function () {
    // Initialize all components
    initializeModals();
    initializeCalendar();
    initializeForms();
    initializeDataTables();
    initializeSidebar();
    initializeFlashMessages();

    // Handle ESC key to close modals
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            const openModal = document.querySelector('.modal[style*="block"]');
            if (openModal) {
                openModal.style.display = 'none';
            }
        }
    });
});

