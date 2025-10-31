<?php
/**
 * Church Dashboard Shortcode
 * 
 * Displays attendance reports for a specific church with filtering and sorting
 * 
 * @package ChurchAttendanceReports
 * @since 1.1.5
 */

// Enqueue dashboard CSS when shortcode is used
function car_enqueue_dashboard_css() {
    // Check if we're on a page that might have the shortcode
    if (is_page() || is_singular()) {
        wp_enqueue_style(
            'car-church-dashboard', 
            plugins_url('../assets/css/church-dashboard.css', __FILE__),
            array(), 
            '1.1.0'
        );
    }
}
add_action('wp_enqueue_scripts', 'car_enqueue_dashboard_css');

// [church_dashboard_reports] shortcode
add_shortcode('church_dashboard_reports', 'car_church_dashboard_shortcode');

function car_church_dashboard_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Please log in to view the dashboard.</p>';
    }

    $user_id = get_current_user_id();
    $user_church_id = get_user_meta($user_id, 'assigned_church', true);
    $user = wp_get_current_user();
    $can_edit = in_array('church_admin', $user->roles);

    if (!$user_church_id) {
        return '<p>Error: No church assigned to your account.</p>';
    }

    // Get the church name
    $church_term = get_term($user_church_id, 'church');
    $church_name = $church_term && !is_wp_error($church_term) ? $church_term->name : 'Unknown Church';

    ob_start();
    
    ?>
    <div class="church-dashboard">
        <h2>Church Dashboard</h2>
        <p style="margin: 0 0 1.5em; font-size: 1.1em;"><strong>Church:</strong> <?php echo esc_html($church_name); ?></p>
        <div style="margin-bottom: 1em;">
            <label>Start Date: <input type="date" id="dashboard-filter-start" /></label>
            <label>End Date: <input type="date" id="dashboard-filter-end" /></label>
            <button id="dashboard-filter-apply">Apply</button>
            <button id="dashboard-export-csv">Export CSV</button>
        </div>
        <div class="dashboard-table-wrapper">
            <table id="dashboard-reports-table" class="widefat">
            <thead>
                <tr>
                    <th data-sort="id">ID</th>
                    <th data-sort="attendance_date">Date</th>
                    <th data-sort="in_person">In-Person</th>
                    <th data-sort="online">Online</th>
                    <th data-sort="discipleship">Discipleship</th>
                    <th data-sort="acl">ACL</th>
                    <th data-sort="total">Total</th>
                    <th data-sort="submitted_by">Submitted By</th>
                    <th data-sort="submitted_at">Submitted At</th>
                    <th>Edited</th>
                </tr>
            </thead>
            <tbody id="dashboard-reports-body"></tbody>
        </table>
        </div>
        <div class="pagination-controls" style="margin-top:1em; display: flex; justify-content: space-between; align-items: center;">
            <div>
                Rows per page:
                <select id="dashboard-rows-per-page">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div>
                <button id="dashboard-prev-page">Prev</button>
                <span id="dashboard-page-info"></span>
                <button id="dashboard-next-page">Next</button>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const userCanEdit = <?php echo $can_edit ? 'true' : 'false'; ?>;
            const assignedChurchId = <?php echo intval($user_church_id); ?>;
            const currentUser = '<?php echo esc_js($user->user_login); ?>';

            let reportsData = [];
            let filteredReports = [];
            let currentPage = 1;
            let rowsPerPage = 10;
            let sortField = 'attendance_date';
            let sortDirection = 'desc';

            async function fetchReports() {
                const response = await fetch('<?php echo admin_url('admin-ajax.php?action=car_fetch_reports_json'); ?>');
                try {
                    const data = await response.json();
                    reportsData = Array.isArray(data) ? data.filter(r =>
                        r.church_id == assignedChurchId &&
                        r.attendance_date &&
                        r.attendance_date !== '1970-01-01'
                    ) : [];
                    applyFilters();
                } catch (e) {
                    console.error("Failed to parse reports JSON", e);
                }
            }

            function applyFilters() {
                const start = document.getElementById('dashboard-filter-start').valueAsDate;
                const end = document.getElementById('dashboard-filter-end').valueAsDate || new Date();

                filteredReports = reportsData.filter(r => {
                    const reportDate = new Date(r.attendance_date);
                    return (!start || reportDate >= start) && (!end || reportDate <= end);
                });

                sortReports();
            }

            function sortReports() {
                filteredReports.sort((a, b) => {
                    let valA = a[sortField];
                    let valB = b[sortField];
                    if (sortField.includes('date')) {
                        valA = new Date(valA);
                        valB = new Date(valB);
                    }
                    if (valA < valB) return sortDirection === 'asc' ? -1 : 1;
                    if (valA > valB) return sortDirection === 'asc' ? 1 : -1;
                    return 0;
                });
                renderTable();
            }

            function renderTable() {
                const tbody = document.getElementById('dashboard-reports-body');
                tbody.innerHTML = '';
                const start = (currentPage - 1) * rowsPerPage;
                const end = start + rowsPerPage;
                const pageReports = filteredReports.slice(start, end);

                pageReports.forEach(report => {
                    const row = document.createElement('tr');
                    const isEdited = report.versions && report.versions.length > 1;
                    const submittedAtStr = report.submitted_at ? new Date(report.submitted_at).toLocaleString() : '';
                    const dateStr = new Date(report.attendance_date).toLocaleDateString();

                    // Format version history tooltip with error handling
                    let versionHistoryHtml = '';
                    if (isEdited && report.versions && report.versions.length > 0) {
                        versionHistoryHtml = report.versions.map(v => {
                            const timestamp = v.timestamp || v.date; // Handle different timestamp field names
                            let dateStr = 'Unknown date';
                            
                            if (timestamp) {
                                try {
                                    const date = new Date(timestamp);
                                    // Check if date is valid
                                    if (!isNaN(date.getTime())) {
                                        dateStr = date.toLocaleString();
                                    }
                                } catch (e) {
                                    dateStr = 'Invalid date';
                                }
                            }
                            
                            return `${v.user || 'Unknown'} on ${dateStr}`;
                        }).join('<br>');
                    }

                    // Always show as read-only initially
                    row.innerHTML = `
                        <td><strong>${report.id}</strong></td>
                        <td>${dateStr}</td>
                        <td class="editable-cell" data-id="${report.id}" data-field="in_person" data-value="${report.in_person}">${report.in_person}</td>
                        <td class="editable-cell" data-id="${report.id}" data-field="online" data-value="${report.online}">${report.online}</td>
                        <td class="editable-cell" data-id="${report.id}" data-field="discipleship" data-value="${report.discipleship}">${report.discipleship}</td>
                        <td class="editable-cell" data-id="${report.id}" data-field="acl" data-value="${report.acl}">${report.acl}</td>
                        <td>${report.total}</td>
                        <td>${report.submitted_by || ''}</td>
                        <td>${submittedAtStr}</td>
                        <td>${isEdited ? `<span class="tooltip-icon">✏️<span class="tooltip-content">${versionHistoryHtml}</span></span>` : ''}</td>
                    `;
                    
                    row.dataset.reportId = report.id;
                    
                    // Add click handler for editable rows
                    if (userCanEdit) {
                        row.style.cursor = 'pointer';
                        row.addEventListener('click', (e) => {
                            // Don't trigger if clicking on an input that's already there
                            if (e.target.tagName === 'INPUT') return;
                            
                            makeRowEditable(row);
                        });
                    }
                    
                    tbody.appendChild(row);
                });

                document.getElementById('dashboard-page-info').textContent =
                    `Page ${currentPage} of ${Math.ceil(filteredReports.length / rowsPerPage)}`;
            }
            
            function makeRowEditable(row) {
                // If already editing, don't do anything
                if (row.classList.contains('editing')) return;
                
                // Remove editing class from all other rows and convert back to read-only
                document.querySelectorAll('tr.editing').forEach(r => {
                    if (r !== row) makeRowReadOnly(r);
                });
                
                row.classList.add('editing');
                const editableCells = row.querySelectorAll('.editable-cell');
                
                editableCells.forEach(cell => {
                    const value = cell.dataset.value;
                    const field = cell.dataset.field;
                    const id = cell.dataset.id;
                    
                    cell.innerHTML = `<input type="number" data-id="${id}" data-field="${field}" value="${value}" class="inline-edit">`;
                    
                    // Focus the first input
                    const input = cell.querySelector('input');
                    if (editableCells[0] === cell) {
                        input.focus();
                        input.select();
                    }
                });
                
                // Add action buttons at the end of the row
                const lastCell = row.querySelector('td:last-child');
                const originalContent = lastCell.innerHTML;
                lastCell.innerHTML = `
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                        ${originalContent}
                        <div class="edit-actions">
                            <button class="save-btn">Save</button>
                            <button class="cancel-btn">Cancel</button>
                        </div>
                    </div>
                `;
                
                // Save button handler
                lastCell.querySelector('.save-btn').addEventListener('click', (e) => {
                    e.stopPropagation();
                    saveRowEdits(row);
                });
                
                // Cancel button handler
                lastCell.querySelector('.cancel-btn').addEventListener('click', (e) => {
                    e.stopPropagation();
                    makeRowReadOnly(row);
                });
            }
            
            function makeRowReadOnly(row) {
                row.classList.remove('editing');
                const editableCells = row.querySelectorAll('.editable-cell');
                
                editableCells.forEach(cell => {
                    const input = cell.querySelector('input');
                    if (input) {
                        // Restore original value (not the edited value unless saved)
                        cell.innerHTML = cell.dataset.value;
                    }
                });
                
                // Remove action buttons
                const lastCell = row.querySelector('td:last-child');
                const actions = lastCell.querySelector('.edit-actions');
                if (actions) {
                    actions.remove();
                }
            }
            
            async function saveRowEdits(row) {
                const inputs = row.querySelectorAll('input.inline-edit');
                const updates = {};
                let reportId = null;
                
                inputs.forEach(input => {
                    reportId = input.dataset.id;
                    const field = input.dataset.field;
                    updates[field] = parseInt(input.value) || 0;
                });
                
                if (!reportId) return;
                
                try {
                    const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: new URLSearchParams({
                            action: 'car_update_attendance',
                            post_id: reportId,
                            ...updates,
                            username: currentUser
                        })
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        // Update the data and re-render
                        await fetchReports();
                        
                        // Show success indicator briefly
                        row.style.backgroundColor = '#d4edda';
                        setTimeout(() => {
                            row.style.backgroundColor = '';
                        }, 1000);
                    } else {
                        alert('Failed to save changes: ' + (result.data || 'Unknown error'));
                        makeRowReadOnly(row);
                    }
                } catch (error) {
                    console.error('Save error:', error);
                    alert('Failed to save changes. Please try again.');
                    makeRowReadOnly(row);
                }
            }

            document.getElementById('dashboard-filter-apply').addEventListener('click', applyFilters);
            document.getElementById('dashboard-prev-page').addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    renderTable();
                }
            });
            document.getElementById('dashboard-next-page').addEventListener('click', () => {
                if (currentPage < Math.ceil(filteredReports.length / rowsPerPage)) {
                    currentPage++;
                    renderTable();
                }
            });
            document.getElementById('dashboard-rows-per-page').addEventListener('change', e => {
                rowsPerPage = parseInt(e.target.value);
                currentPage = 1;
                renderTable();
            });

            document.querySelectorAll('#dashboard-reports-table th[data-sort]').forEach(th => {
                th.style.cursor = 'pointer';
                th.addEventListener('click', () => {
                    const field = th.getAttribute('data-sort');
                    if (sortField === field) {
                        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        sortField = field;
                        sortDirection = 'asc';
                    }
                    sortReports();
                });
            });

            const today = new Date().toISOString().split('T')[0];
            document.getElementById('dashboard-filter-end').value = today;
            fetchReports();

            // Check if we just submitted a report - if so, show a message and refresh
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('report_status') === 'success') {
                // Show success message
                const successMsg = document.createElement('div');
                successMsg.className = 'notice notice-success';
                successMsg.style.cssText = 'padding: 12px; margin: 20px 0; background: #d4edda; border-left: 4px solid #28a745; color: #155724;';
                successMsg.textContent = 'Attendance report submitted successfully.';
                document.querySelector('.church-dashboard').insertBefore(successMsg, document.querySelector('.church-dashboard').firstChild);
                
                // Clean URL without reload
                window.history.replaceState({}, document.title, window.location.pathname);
                
                // Fetch reports again to show the new one
                setTimeout(() => {
                    fetchReports();
                }, 500);
            } else if (urlParams.get('report_status') === 'updated') {
                // Show updated message
                const updateMsg = document.createElement('div');
                updateMsg.className = 'notice notice-success';
                updateMsg.style.cssText = 'padding: 12px; margin: 20px 0; background: #d4edda; border-left: 4px solid #28a745; color: #155724;';
                updateMsg.textContent = 'Attendance report updated successfully.';
                document.querySelector('.church-dashboard').insertBefore(updateMsg, document.querySelector('.church-dashboard').firstChild);
                
                // Clean URL without reload
                window.history.replaceState({}, document.title, window.location.pathname);
                
                // Fetch reports again to show the updated one
                setTimeout(() => {
                    fetchReports();
                }, 500);
            }
        });
    </script>
    <?php
    return ob_get_clean();
}
?>