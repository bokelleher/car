<?php
/**
 * Church Dashboard Shortcode
 *
 * Displays attendance reports for a specific church with filtering and sorting
 * and provides CSV export functionality. A warning banner is shown if the
 * logged‑in church admin or reporter has not submitted a report in the past
 * 30 days. This file also adds an "Updated At" column to the dashboard.
 *
 * @package ChurchAttendanceReports
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

    $user_id        = get_current_user_id();
    $user_church_id = get_user_meta($user_id, 'assigned_church', true);
    $user           = wp_get_current_user();
    $can_edit       = in_array('church_admin', $user->roles);

    if (!$user_church_id) {
        return '<p>Error: No church assigned to your account.</p>';
    }

    // Get the church name
    $church_term = get_term($user_church_id, 'church');
    $church_name = $church_term && !is_wp_error($church_term) ? $church_term->name : 'Unknown Church';

    // Determine if the user should see the overdue banner. Only church_admin
    // and church_reporter roles (and not site administrators) will see this
    // message. A report is considered overdue if there is no report or if
    // the latest attendance_date is older than 30 days.
    $show_warning = false;
    if (in_array('church_admin', (array) $user->roles) || in_array('church_reporter', (array) $user->roles)) {
        $args = array(
            'post_type'      => 'attendance_report',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'orderby'        => 'meta_value',
            'meta_key'       => 'attendance_date',
            'order'          => 'DESC',
            'meta_query'     => array(
                array(
                    'key'   => 'church',
                    'value' => $user_church_id,
                ),
            ),
        );
        $latest_query = new WP_Query($args);
        $last_date    = null;
        if ($latest_query->have_posts()) {
            $post_id   = $latest_query->posts[0]->ID;
            $last_date = get_post_meta($post_id, 'attendance_date', true);
        }
        wp_reset_postdata();
        $threshold_ts = strtotime('-30 days');
        if (!$last_date) {
            $show_warning = true;
        } else {
            $last_ts = strtotime($last_date);
            if ($last_ts === false || $last_ts < $threshold_ts) {
                $show_warning = true;
            }
        }
    }

    ob_start();
    ?>
    <div class="church-dashboard">
        <h2><?php esc_html_e('Church Dashboard', 'church-attendance-reports'); ?></h2>
        <p style="margin: 0 0 1.5em; font-size: 1.1em;"><strong><?php esc_html_e('Church:', 'church-attendance-reports'); ?></strong> <?php echo esc_html($church_name); ?></p>
        <?php if ($show_warning) : ?>
            <div class="car-warning-banner" style="background:#fff3cd;border-left:4px solid #ffeeba;padding:12px;margin-bottom:16px;">
                <strong><?php esc_html_e('Warning:', 'church-attendance-reports'); ?></strong> <?php esc_html_e('No attendance report has been submitted in the last 30 days. Please submit a report soon.', 'church-attendance-reports'); ?>
            </div>
        <?php endif; ?>
        <div style="margin-bottom: 1em;">
            <label><?php esc_html_e('Start Date:', 'church-attendance-reports'); ?> <input type="date" id="dashboard-filter-start" /></label>
            <label><?php esc_html_e('End Date:', 'church-attendance-reports'); ?> <input type="date" id="dashboard-filter-end" /></label>
            <button id="dashboard-filter-apply"><?php esc_html_e('Apply', 'church-attendance-reports'); ?></button>
            <button id="dashboard-export-csv"><?php esc_html_e('Export CSV', 'church-attendance-reports'); ?></button>
        </div>
        <div class="dashboard-table-wrapper">
            <table id="dashboard-reports-table" class="widefat">
                <thead>
                    <tr>
                        <th data-sort="id"><?php esc_html_e('ID', 'church-attendance-reports'); ?></th>
                        <th data-sort="attendance_date"><?php esc_html_e('Date', 'church-attendance-reports'); ?></th>
                        <th data-sort="in_person"><?php esc_html_e('In-Person', 'church-attendance-reports'); ?></th>
                        <th data-sort="online"><?php esc_html_e('Online', 'church-attendance-reports'); ?></th>
                        <th data-sort="discipleship"><?php esc_html_e('Discipleship', 'church-attendance-reports'); ?></th>
                        <th data-sort="acl"><?php esc_html_e('ACL', 'church-attendance-reports'); ?></th>
                        <th data-sort="total"><?php esc_html_e('Total', 'church-attendance-reports'); ?></th>
                        <th data-sort="submitted_by"><?php esc_html_e('Submitted By', 'church-attendance-reports'); ?></th>
                        <th data-sort="submitted_at"><?php esc_html_e('Submitted At', 'church-attendance-reports'); ?></th>
                        <th data-sort="updated_at"><?php esc_html_e('Updated At', 'church-attendance-reports'); ?></th>
                        <th><?php esc_html_e('Edited', 'church-attendance-reports'); ?></th>
                    </tr>
                </thead>
                <tbody id="dashboard-reports-body"></tbody>
            </table>
        </div>
        <div class="pagination-controls" style="margin-top:1em; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <?php esc_html_e('Rows per page:', 'church-attendance-reports'); ?>
                <select id="dashboard-rows-per-page">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div>
                <button id="dashboard-prev-page"><?php esc_html_e('Prev', 'church-attendance-reports'); ?></button>
                <span id="dashboard-page-info"></span>
                <button id="dashboard-next-page"><?php esc_html_e('Next', 'church-attendance-reports'); ?></button>
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

            // Helper to determine updated_at string for a report
            function getUpdatedAt(report) {
                if (report.versions && report.versions.length > 1) {
                    const lastVersion = report.versions[report.versions.length - 1];
                    const ts = lastVersion.timestamp || lastVersion.date;
                    if (ts) {
                        const d = new Date(ts);
                        if (!isNaN(d.getTime())) {
                            return d.toLocaleString();
                        }
                    }
                }
                return '';
            }

            async function fetchReports() {
                const response = await fetch('<?php echo admin_url('admin-ajax.php?action=car_fetch_reports_json'); ?>');
                try {
                    const data = await response.json();
                    reportsData = Array.isArray(data) ? data.filter(r =>
                        r.church_id == assignedChurchId &&
                        r.attendance_date &&
                        r.attendance_date !== '1970-01-01'
                    ).map(r => {
                        // Derive updated_at property for sorting
                        if (r.versions && r.versions.length > 1) {
                            const lastVersion = r.versions[r.versions.length - 1];
                            const ts = lastVersion.timestamp || lastVersion.date;
                            r.updated_at = ts || '';
                        } else {
                            r.updated_at = '';
                        }
                        return r;
                    }) : [];
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
                    if (sortField.includes('date') || sortField === 'updated_at' || sortField === 'submitted_at') {
                        valA = valA ? new Date(valA) : new Date(0);
                        valB = valB ? new Date(valB) : new Date(0);
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
                const startIdx = (currentPage - 1) * rowsPerPage;
                const endIdx = startIdx + rowsPerPage;
                const pageReports = filteredReports.slice(startIdx, endIdx);
                pageReports.forEach(report => {
                    const row = document.createElement('tr');
                    const isEdited = report.versions && report.versions.length > 1;
                    const submittedAtStr = report.submitted_at ? new Date(report.submitted_at).toLocaleString() : '';
                    const dateStr = new Date(report.attendance_date).toLocaleDateString();
                    const updatedAtStr = getUpdatedAt(report);

                    // Format version history tooltip with error handling
                    let versionHistoryHtml = '';
                    if (isEdited && report.versions && report.versions.length > 0) {
                        versionHistoryHtml = report.versions.map(v => {
                            const timestamp = v.timestamp || v.date;
                            let dateStr = 'Unknown date';
                            if (timestamp) {
                                try {
                                    const date = new Date(timestamp);
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
                        <td>${updatedAtStr}</td>
                        <td>${isEdited ? `<span class="tooltip-icon">✏️<span class="tooltip-content">${versionHistoryHtml}</span></span>` : ''}</td>
                    `;
                    row.dataset.reportId = report.id;
                    if (userCanEdit) {
                        row.style.cursor = 'pointer';
                        row.addEventListener('click', (e) => {
                            if (e.target.tagName === 'INPUT') return;
                            makeRowEditable(row);
                        });
                    }
                    tbody.appendChild(row);
                });
                document.getElementById('dashboard-page-info').textContent =
                    `<?php esc_html_e('Page', 'church-attendance-reports'); ?> ${currentPage} <?php esc_html_e('of', 'church-attendance-reports'); ?> ${Math.ceil(filteredReports.length / rowsPerPage)}`;
            }

            function makeRowEditable(row) {
                if (row.classList.contains('editing')) return;
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
                    const input = cell.querySelector('input');
                    if (editableCells[0] === cell) {
                        input.focus();
                        input.select();
                    }
                });
                const lastCell = row.querySelector('td:last-child');
                const originalContent = lastCell.innerHTML;
                lastCell.innerHTML = `
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                        ${originalContent}
                        <div class="edit-actions">
                            <button class="save-btn"><?php esc_html_e('Save', 'church-attendance-reports'); ?></button>
                            <button class="cancel-btn"><?php esc_html_e('Cancel', 'church-attendance-reports'); ?></button>
                        </div>
                    </div>
                `;
                lastCell.querySelector('.save-btn').addEventListener('click', (e) => {
                    e.stopPropagation();
                    saveRowEdits(row);
                });
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
                        cell.innerHTML = cell.dataset.value;
                    }
                });
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
                        await fetchReports();
                        row.style.backgroundColor = '#d4edda';
                        setTimeout(() => {
                            row.style.backgroundColor = '';
                        }, 1000);
                    } else {
                        alert('<?php esc_html_e('Failed to save changes:', 'church-attendance-reports'); ?> ' + (result.data || '<?php esc_html_e('Unknown error', 'church-attendance-reports'); ?>'));
                        makeRowReadOnly(row);
                    }
                } catch (error) {
                    console.error('Save error:', error);
                    alert('<?php esc_html_e('Failed to save changes. Please try again.', 'church-attendance-reports'); ?>');
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
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('report_status') === 'success') {
                const successMsg = document.createElement('div');
                successMsg.className = 'notice notice-success';
                successMsg.style.cssText = 'padding: 12px; margin: 20px 0; background: #d4edda; border-left: 4px solid #28a745; color: #155724;';
                successMsg.textContent = '<?php esc_html_e('Attendance report submitted successfully.', 'church-attendance-reports'); ?>';
                document.querySelector('.church-dashboard').insertBefore(successMsg, document.querySelector('.church-dashboard').firstChild);
                window.history.replaceState({}, document.title, window.location.pathname);
                setTimeout(() => { fetchReports(); }, 500);
            } else if (urlParams.get('report_status') === 'updated') {
                const updateMsg = document.createElement('div');
                updateMsg.className = 'notice notice-success';
                updateMsg.style.cssText = 'padding: 12px; margin: 20px 0; background: #d4edda; border-left: 4px solid #28a745; color: #155724;';
                updateMsg.textContent = '<?php esc_html_e('Attendance report updated successfully.', 'church-attendance-reports'); ?>';
                document.querySelector('.church-dashboard').insertBefore(updateMsg, document.querySelector('.church-dashboard').firstChild);
                window.history.replaceState({}, document.title, window.location.pathname);
                setTimeout(() => { fetchReports(); }, 500);
            }
            document.getElementById('dashboard-export-csv').addEventListener('click', () => {
                const headers = ['ID','Date','In-Person','Online','Discipleship','ACL','Total','Submitted By','Submitted At','Updated At'];
                const csvRows = [];
                csvRows.push(headers.join(','));
                filteredReports.forEach(r => {
                    const date = new Date(r.attendance_date).toLocaleDateString();
                    const submittedAt = r.submitted_at ? new Date(r.submitted_at).toLocaleString() : '';
                    const updatedAt = getUpdatedAt(r);
                    const row = [
                        r.id,
                        date,
                        r.in_person,
                        r.online,
                        r.discipleship,
                        r.acl,
                        r.total,
                        r.submitted_by || '',
                        submittedAt,
                        updatedAt
                    ].map(val => `"${String(val).replace(/"/g,'""')}"`).join(',');
                    csvRows.push(row);
                });
                const csvContent = csvRows.join('\n');
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = 'attendance_reports.csv';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        });
    </script>
    <?php
    return ob_get_clean();
}