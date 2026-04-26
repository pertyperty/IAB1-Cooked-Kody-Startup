const API_BASE = '../api/index.php';

const ROLE_BLUEPRINTS = {
    learner: {
        inherits: 'Base access',
        summary: 'Learners can browse content, study modules, join challenges, manage their account, and use wallet features.',
        grants: ['A01-A10 self-service', 'D03', 'E01-E11', 'F01-F02', 'F05'],
    },
    contributor: {
        inherits: 'Learner',
        summary: 'Contributors add challenge and creator capabilities on top of learner access.',
        grants: ['C01-C04', 'C06-C07', 'D01', 'F03-F04'],
    },
    instructor: {
        inherits: 'Contributor + Learner',
        summary: 'Instructors extend creator access into course and module authoring.',
        grants: ['B01-B09', 'C01-C04', 'D01', 'F03-F04'],
    },
    moderator: {
        inherits: 'Learner',
        summary: 'Moderators review queues, reports, weekly challenges, and user-account governance flows.',
        grants: ['C05', 'D02', 'D04-D06', 'G01', 'G03-G06'],
    },
    administrator: {
        inherits: 'All roles except self-service recovery',
        summary: 'Administrators have the broadest system access and governance authority.',
        grants: ['All SRS use cases except A02'],
    },
};

const state = {
    auth: null,
    modalResolver: null,
};

const PAGE_ACCESS = {
    home: 'learner,contributor,instructor,moderator,administrator',
    profile: 'learner,contributor,instructor,moderator,administrator',
    learn: 'learner,contributor,instructor,moderator,administrator',
    creator: 'contributor,instructor,administrator',
    rewards: 'learner,contributor,instructor,moderator,administrator',
    finance: 'learner,contributor,instructor,moderator,administrator',
    governance: 'moderator,administrator',
};

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (match) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    }[match]));
}

function createApiError(json, fallbackMessage) {
    const error = new Error(json?.message || fallbackMessage || 'Request failed.');
    error.details = json?.data || {};
    return error;
}

function readAuth() {
    try {
        const parsed = JSON.parse(localStorage.getItem('kodyAuth') || '{}');
        if (parsed?.session_token && parsed?.user?.id) {
            return parsed;
        }
    } catch (error) {
        console.error(error);
    }

    return null;
}

function writeResponse(title, payload) {
    const box = document.getElementById('response-box');
    if (!box) {
        return;
    }

    box.textContent = JSON.stringify({ title, payload, at: new Date().toISOString() }, null, 2);
}

function setStatus(type, message) {
    const banner = document.getElementById('status-banner');
    if (!banner) {
        return;
    }

    banner.className = `status-banner ${type}`;
    banner.innerHTML = message;
}

function formatDate(value) {
    if (!value) {
        return 'n/a';
    }

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
        return String(value);
    }

    return parsed.toLocaleString();
}

function formatNumber(value) {
    const num = Number(value || 0);
    return Number.isFinite(num) ? num.toLocaleString() : '0';
}

async function apiCall(module, action, payload = {}, method = 'POST') {
    const isGet = method.toUpperCase() === 'GET';
    const response = await fetch(`${API_BASE}?module=${encodeURIComponent(module)}&action=${encodeURIComponent(action)}`, {
        method,
        headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${state.auth.session_token}`,
        },
        body: isGet ? null : JSON.stringify(payload || {}),
    });

    const json = await response.json();
    if (!response.ok || !json.success) {
        throw createApiError(json, 'Workspace request failed.');
    }

    return json;
}

function roleAllowed(allowedCsv) {
    if (!allowedCsv) {
        return true;
    }

    return allowedCsv.split(',').map((item) => item.trim()).includes(state.auth.user.role);
}

function applyRoleVisibility() {
    document.querySelectorAll('[data-roles]').forEach((node) => {
        node.style.display = roleAllowed(node.getAttribute('data-roles')) ? '' : 'none';
    });
}

function renderTable(targetId, rows, emptyMessage = 'No records found.') {
    const target = document.getElementById(targetId);
    if (!target) {
        return;
    }

    if (!rows || rows.length === 0) {
        target.innerHTML = `<div class="empty-state">${escapeHtml(emptyMessage)}</div>`;
        return;
    }

    const headers = Object.keys(rows[0]);
    const headerHtml = headers.map((header) => `<th>${escapeHtml(header)}</th>`).join('');
    const bodyHtml = rows.map((row) => `<tr>${headers.map((header) => `<td>${escapeHtml(row[header])}</td>`).join('')}</tr>`).join('');
    target.innerHTML = `<table><thead><tr>${headerHtml}</tr></thead><tbody>${bodyHtml}</tbody></table>`;
}

function renderNotifications(rows) {
    const target = document.getElementById('notification-list');
    if (!target) {
        return;
    }

    if (!rows || rows.length === 0) {
        target.innerHTML = '<div class="empty-state">No notifications yet.</div>';
        return;
    }

    target.innerHTML = rows.slice(0, 8).map((row) => `
        <article class="list-card">
            <strong>${escapeHtml(row.subject)}</strong>
            <p>${escapeHtml(row.body)}</p>
            <span>${escapeHtml(row.delivery_status)} | ${escapeHtml(formatDate(row.created_at))}</span>
        </article>
    `).join('');
}

function renderFaq(rows) {
    const target = document.getElementById('faq-list');
    if (!target) {
        return;
    }

    if (!rows || rows.length === 0) {
        target.innerHTML = '<div class="empty-state">No FAQ entries available.</div>';
        return;
    }

    target.innerHTML = rows.map((row) => `
        <article class="faq-item">
            <strong>${escapeHtml(row.question)}</strong>
            <p>${escapeHtml(row.answer)}</p>
        </article>
    `).join('');
}

function renderProfile(user) {
    const currentUserBox = document.getElementById('current-user-box');
    if (currentUserBox) {
        currentUserBox.innerHTML = `
            <div class="identity-main">
                <strong>${escapeHtml(user.full_name)}</strong>
                <span>${escapeHtml(user.email)}</span>
            </div>
            <div class="pill-row">
                <span class="pill">${escapeHtml(user.role)}</span>
                <span class="pill">${escapeHtml(user.status)}</span>
                <span class="pill">${user.email_verified ? 'verified' : 'unverified'}</span>
            </div>
        `;
    }

    const profileSummary = document.getElementById('profile-summary');
    if (profileSummary) {
        profileSummary.innerHTML = `
            <dl class="info-list">
                <div><dt>Role</dt><dd>${escapeHtml(user.role)}</dd></div>
                <div><dt>Status</dt><dd>${escapeHtml(user.status)}</dd></div>
                <div><dt>KodeBits</dt><dd>${escapeHtml(formatNumber(user.kodebits_balance))}</dd></div>
                <div><dt>XP</dt><dd>${escapeHtml(formatNumber(user.xp_points))}</dd></div>
                <div><dt>Failed Logins</dt><dd>${escapeHtml(formatNumber(user.failed_login_count))}</dd></div>
                <div><dt>Lockout Until</dt><dd>${escapeHtml(user.lockout_until || 'Not locked')}</dd></div>
                <div><dt>Created</dt><dd>${escapeHtml(formatDate(user.created_at))}</dd></div>
            </dl>
        `;
    }
}

function renderRolePermissions(role) {
    const target = document.getElementById('role-permissions');
    if (!target) {
        return;
    }

    const blueprint = ROLE_BLUEPRINTS[role] || ROLE_BLUEPRINTS.learner;
    target.innerHTML = `
        <p><strong>Inherits:</strong> ${escapeHtml(blueprint.inherits)}</p>
        <p>${escapeHtml(blueprint.summary)}</p>
        <div class="pill-row">
            ${blueprint.grants.map((grant) => `<span class="pill">${escapeHtml(grant)}</span>`).join('')}
        </div>
    `;
}

function renderMetricCards(summary, user) {
    const target = document.getElementById('overview-metrics');
    if (!target) {
        return;
    }

    const metrics = [
        { label: 'My XP', value: formatNumber(user.xp_points) },
        { label: 'My KodeBits', value: formatNumber(user.kodebits_balance) },
        { label: 'My Enrollments', value: formatNumber(summary.my_enrollments) },
        { label: 'My Submissions', value: formatNumber(summary.my_submissions) },
        { label: 'Courses', value: formatNumber(summary.courses) },
        { label: 'Modules', value: formatNumber(summary.modules) },
        { label: 'Challenges', value: formatNumber(summary.challenges) },
        { label: 'Pending Requests', value: formatNumber(summary.pending_requests) },
    ];

    if (roleAllowed('moderator,administrator')) {
        metrics.push({ label: 'Open Reports', value: formatNumber(summary.open_reports) });
        metrics.push({ label: 'Pending Credentials', value: formatNumber(summary.pending_credentials) });
    }

    target.innerHTML = metrics.map((metric) => `
        <article class="metric-card">
            <span>${escapeHtml(metric.label)}</span>
            <strong>${escapeHtml(metric.value)}</strong>
        </article>
    `).join('');
}

function renderModuleDetail(title, body, meta) {
    const target = document.getElementById('module-reader');
    if (!target) {
        return;
    }

    target.innerHTML = `
        <article class="detail-card">
            <h3>${escapeHtml(title)}</h3>
            <p>${escapeHtml(body || 'No content is available in this module record yet.')}</p>
            <div class="pill-row">
                ${meta.map((item) => `<span class="pill">${escapeHtml(item)}</span>`).join('')}
            </div>
        </article>
    `;
}

function renderCourseModules(rows, courseId = null) {
    const target = document.getElementById('course-modules-table');
    if (!target) {
        return;
    }

    if (!rows || rows.length === 0) {
        target.innerHTML = '<div class="empty-state">Enroll in a course, then load its module list here.</div>';
        return;
    }

    target.innerHTML = rows.map((row) => `
        <article class="catalog-card inline-card">
            <div>
                <h3>${escapeHtml(row.title)}</h3>
                <p>${escapeHtml(row.body_content || 'Module content will appear in the reader when opened.')}</p>
            </div>
            <div class="pill-row">
                <span class="pill">Sequence ${escapeHtml(row.sequence_no)}</span>
                <span class="pill">${escapeHtml(row.difficulty_level || 'Module')}</span>
                <span class="pill">${escapeHtml(row.status)}</span>
            </div>
            <div class="inline-actions">
                <button type="button" data-quick="access-course-module" data-course-id="${escapeHtml(courseId || '')}" data-module-id="${escapeHtml(row.id)}">Open Module</button>
                <button type="button" class="secondary" data-quick="react" data-content-type="module" data-content-id="${escapeHtml(row.id)}">Like</button>
            </div>
        </article>
    `).join('');
}

function renderPackageShowcase(rows) {
    const target = document.getElementById('package-showcase');
    if (!target) {
        return;
    }

    if (!rows || rows.length === 0) {
        target.innerHTML = '<div class="empty-state">No top-up packages are available right now.</div>';
        return;
    }

    target.innerHTML = rows.map((row) => `
        <article class="catalog-card package-card">
            <div>
                <h3>${escapeHtml(row.package_name)}</h3>
                <p>${escapeHtml(formatNumber(row.kodebits_amount))} KodeBits for PHP ${escapeHtml(Number(row.php_amount).toFixed(2))}</p>
            </div>
            <div class="pill-row">
                <span class="pill">${escapeHtml(formatNumber(row.kodebits_amount))} KB</span>
                <span class="pill">PHP ${escapeHtml(Number(row.php_amount).toFixed(2))}</span>
            </div>
            <div class="inline-actions">
                <button type="button" data-quick="purchase-package" data-package-id="${escapeHtml(row.id)}">Top Up Now</button>
            </div>
        </article>
    `).join('');
}

function renderCourseCatalog(rows) {
    const target = document.getElementById('course-catalog');
    if (!target) {
        return;
    }

    if (!rows || rows.length === 0) {
        target.innerHTML = '<div class="empty-state">No courses are currently published.</div>';
        return;
    }

    target.innerHTML = rows.map((row) => `
        <article class="catalog-card">
            <div>
                <h3>${escapeHtml(row.title)}</h3>
                <p>${escapeHtml(row.description || 'No description provided.')}</p>
            </div>
            <div class="pill-row">
                <span class="pill">${escapeHtml(row.course_type)}</span>
                <span class="pill">${escapeHtml(row.status)}</span>
                <span class="pill">${escapeHtml(formatNumber(row.kodebits_cost))} KB</span>
            </div>
            <div class="inline-actions">
                <button type="button" data-quick="enroll-course" data-course-id="${escapeHtml(row.id)}">Enroll</button>
                <button type="button" class="secondary" data-quick="view-course-modules" data-course-id="${escapeHtml(row.id)}">View Modules</button>
                <button type="button" class="secondary" data-quick="react" data-content-type="course" data-content-id="${escapeHtml(row.id)}">Like</button>
            </div>
        </article>
    `).join('');
}

function renderStandaloneModules(rows) {
    const target = document.getElementById('module-catalog');
    if (!target) {
        return;
    }

    if (!rows || rows.length === 0) {
        target.innerHTML = '<div class="empty-state">No standalone modules are available.</div>';
        return;
    }

    target.innerHTML = rows.map((row) => `
        <article class="catalog-card">
            <div>
                <h3>${escapeHtml(row.title)}</h3>
                <p>${escapeHtml(row.body_content || 'No preview content available.')}</p>
            </div>
            <div class="pill-row">
                <span class="pill">${escapeHtml(row.difficulty_level)}</span>
                <span class="pill">${escapeHtml(formatNumber(row.kodebits_cost))} KB</span>
            </div>
            <div class="inline-actions">
                <button type="button" data-quick="access-standalone-module" data-module-id="${escapeHtml(row.id)}">Access Module</button>
                <button type="button" class="secondary" data-quick="react" data-content-type="module" data-content-id="${escapeHtml(row.id)}">Like</button>
            </div>
        </article>
    `).join('');
}

function renderChallengeCatalog(rows) {
    const target = document.getElementById('challenge-catalog');
    if (!target) {
        return;
    }

    if (!rows || rows.length === 0) {
        target.innerHTML = '<div class="empty-state">No published challenges are available.</div>';
        return;
    }

    target.innerHTML = rows.map((row) => `
        <article class="catalog-card">
            <div>
                <h3>${escapeHtml(row.title)}</h3>
                <p>Languages: ${escapeHtml(row.language_scope)}</p>
            </div>
            <div class="pill-row">
                <span class="pill">${escapeHtml(row.difficulty_level)}</span>
                <span class="pill">${escapeHtml(row.status)}</span>
                <span class="pill">${escapeHtml(formatNumber(row.kodebits_cost))} KB</span>
            </div>
            <div class="inline-actions">
                <button type="button" data-quick="prefill-challenge" data-challenge-id="${escapeHtml(row.id)}">Participate</button>
                <button type="button" class="secondary" data-quick="react" data-content-type="challenge" data-content-id="${escapeHtml(row.id)}">Like</button>
            </div>
        </article>
    `).join('');
}

function normalizePayload(form) {
    const payload = {};
    new FormData(form).forEach((value, key) => {
        if (value === '') {
            return;
        }

        const maybeNumber = Number(value);
        payload[key] = Number.isNaN(maybeNumber) || String(maybeNumber) !== String(value).trim() ? value : maybeNumber;
    });
    return payload;
}

function prefillField(selector, value) {
    const field = document.querySelector(selector);
    if (field) {
        field.value = value;
    }
}

function openConfirmModal(message) {
    const modal = document.getElementById('confirm-modal');
    const messageNode = document.getElementById('confirm-message');
    if (!modal || !messageNode) {
        return Promise.resolve(window.confirm(message));
    }

    messageNode.textContent = message;
    modal.classList.remove('hidden');
    return new Promise((resolve) => {
        state.modalResolver = resolve;
    });
}

function closeConfirmModal(accepted) {
    const modal = document.getElementById('confirm-modal');
    if (modal) {
        modal.classList.add('hidden');
    }

    if (state.modalResolver) {
        state.modalResolver(Boolean(accepted));
        state.modalResolver = null;
    }
}

async function runAction(label, callback) {
    try {
        const result = await callback();
        writeResponse(label, result);
        setStatus('success', `${escapeHtml(label)} completed successfully.`);
        return result;
    } catch (error) {
        writeResponse(`${label} (error)`, { error: error.message, details: error.details || {} });
        setStatus('error', escapeHtml(error.message));

        if (error.message.toLowerCase().includes('session')) {
            localStorage.removeItem('kodyAuth');
            window.location.href = 'login.php';
        }

        return null;
    }
}

async function loadOverview() {
    const [summaryRes, learningRes, notificationsRes, meRes] = await Promise.all([
        apiCall('dashboard', 'summary'),
        apiCall('interaction', 'my_learning'),
        apiCall('finance', 'notifications'),
        apiCall('auth', 'me'),
    ]);

    state.auth.user = meRes.data.user;
    localStorage.setItem('kodyAuth', JSON.stringify(state.auth));

    renderProfile(meRes.data.user);
    renderRolePermissions(meRes.data.user.role);
    renderMetricCards(summaryRes.data.summary, meRes.data.user);
    renderTable('learning-table', learningRes.data.rows, 'You do not have any enrollments yet.');
    renderNotifications(notificationsRes.data.rows);
}

async function loadAccountCenter() {
    const [requestsRes, credentialsRes] = await Promise.all([
        apiCall('auth', 'my_requests'),
        apiCall('auth', 'my_credentials'),
    ]);

    renderTable('request-table', requestsRes.data.rows, 'No contributor or instructor requests yet.');
    renderTable('credential-table', credentialsRes.data.rows, 'No instructor credentials submitted yet.');
}

async function loadLearningHub() {
    const [coursesRes, modulesRes, challengesRes, leaderboardRes, feedbackRes, faqRes] = await Promise.all([
        apiCall('interaction', 'browse_courses'),
        apiCall('interaction', 'standalone_modules'),
        apiCall('interaction', 'browse_challenges'),
        apiCall('interaction', 'view_leaderboard'),
        apiCall('challenge', 'list_feedback'),
        apiCall('interaction', 'faq_list'),
    ]);

    renderCourseCatalog(coursesRes.data.rows);
    renderStandaloneModules(modulesRes.data.rows);
    renderChallengeCatalog(challengesRes.data.rows);
    renderTable('leaderboard-table', leaderboardRes.data.rows, 'No leaderboard data available.');
    renderCourseModules([], null);
    renderTable('feedback-table', feedbackRes.data.rows, 'No submissions yet.');
    renderFaq(faqRes.data.rows);
}

async function loadCreatorWorkspace() {
    if (!roleAllowed('contributor,instructor,administrator')) {
        return;
    }

    const [coursesRes, modulesRes, challengesRes] = await Promise.all([
        apiCall('content', 'list_courses'),
        apiCall('content', 'list_modules'),
        apiCall('challenge', 'list'),
    ]);

    renderTable('content-courses-table', coursesRes.data.rows, 'No courses available.');
    renderTable('content-modules-table', modulesRes.data.rows, 'No modules available.');
    renderTable('creator-challenges-table', challengesRes.data.rows, 'No challenges available.');
}

async function loadGamification() {
    const [presetsRes, leaderboardRes] = await Promise.all([
        apiCall('gamification', 'list_presets'),
        apiCall('gamification', 'leaderboard'),
    ]);

    renderTable('preset-table', presetsRes.data.rows, 'No presets available.');
    renderTable('gamification-leaderboard-table', leaderboardRes.data.rows, 'No ranking data available.');
}

async function loadFinance() {
    const packagePromise = apiCall('finance', 'packages');
    const earningsPromise = roleAllowed('contributor,instructor,administrator')
        ? apiCall('finance', 'earnings')
        : Promise.resolve({ data: { rows: [] } });

    const [packagesRes, earningsRes] = await Promise.all([packagePromise, earningsPromise]);
    renderPackageShowcase(packagesRes.data.rows);
    renderTable('finance-packages-table', packagesRes.data.rows, 'No packages available.');
    renderTable('earnings-table', earningsRes.data.rows, 'No creator earnings available for this role.');
}

async function loadGovernance() {
    if (!roleAllowed('moderator,administrator')) {
        return;
    }

    const requests = [
        apiCall('admin', 'list_users'),
        apiCall('admin', 'contributor_requests'),
        apiCall('admin', 'list_credentials'),
        apiCall('admin', 'list_reports'),
    ];

    if (roleAllowed('administrator')) {
        requests.push(apiCall('admin', 'system_reports'));
    } else {
        requests.push(Promise.resolve({ data: { report: {} } }));
    }

    const [usersRes, requestsRes, credentialsRes, reportsRes, systemRes] = await Promise.all(requests);
    renderTable('users-table', usersRes.data.rows, 'No user accounts found.');
    renderTable('requests-review-table', requestsRes.data.rows, 'No contributor requests found.');
    renderTable('credentials-review-table', credentialsRes.data.rows, 'No instructor credentials found.');
    renderTable('reports-table', reportsRes.data.rows, 'No content reports found.');

    const systemRows = Object.entries(systemRes.data.report || {}).map(([metric, value]) => ({ metric, value }));
    renderTable('system-reports-table', systemRows, 'System reports are only available to administrators.');
}

async function refreshCurrentPage() {
    const page = document.body.getAttribute('data-page');
    setStatus('info', 'Refreshing page data...');

    try {
        await loadOverview();

        if (page === 'profile') {
            await loadAccountCenter();
        }

        if (page === 'learn') {
            await loadLearningHub();
        }

        if (page === 'creator') {
            await loadCreatorWorkspace();
        }

        if (page === 'rewards') {
            await loadGamification();
        }

        if (page === 'finance') {
            await loadFinance();
        }

        if (page === 'governance') {
            await loadGovernance();
        }

        setStatus('success', 'Page data is up to date.');
    } catch (error) {
        writeResponse('page.refresh (error)', { error: error.message, details: error.details || {} });
        setStatus('error', escapeHtml(error.message));
    }
}

async function handleQuickAction(button) {
    const action = button.getAttribute('data-quick');

    if (action === 'enroll-course') {
        const courseId = Number(button.getAttribute('data-course-id'));
        prefillField('#enroll-form input[name="course_id"]', courseId);
        const result = await runAction('interaction.enroll_course', () => apiCall('interaction', 'enroll_course', { course_id: courseId }));
        if (result) {
            await refreshCurrentPage();
        }
        return;
    }

    if (action === 'view-course-modules') {
        const courseId = Number(button.getAttribute('data-course-id'));
        prefillField('#course-access-form input[name="course_id"]', courseId);
        const result = await runAction('interaction.course_modules', () => apiCall('interaction', 'course_modules', { course_id: courseId }));
        if (result?.data?.rows?.length) {
            renderCourseModules(result.data.rows, courseId);
        }
        return;
    }

    if (action === 'access-course-module') {
        const courseId = Number(button.getAttribute('data-course-id'));
        const moduleId = Number(button.getAttribute('data-module-id'));
        prefillField('#course-access-form input[name="course_id"]', courseId);
        prefillField('#course-access-form input[name="module_id"]', moduleId);
        const result = await runAction('interaction.access_course_module', () => apiCall('interaction', 'access_course_module', {
            course_id: courseId,
            module_id: moduleId,
        }));
        if (result?.data?.row) {
            const row = result.data.row;
            renderModuleDetail(row.title, row.body_content, ['Course module', `Sequence ${row.sequence_no}`, row.status]);
        }
        return;
    }

    if (action === 'access-standalone-module') {
        const moduleId = Number(button.getAttribute('data-module-id'));
        prefillField('#standalone-access-form input[name="module_id"]', moduleId);
        const result = await runAction('interaction.access_standalone_module', () => apiCall('interaction', 'access_standalone_module', { module_id: moduleId }));
        if (result?.data?.row) {
            const row = result.data.row;
            renderModuleDetail(row.title, row.body_content, [row.module_type, row.status, `${row.kodebits_cost} KB`]);
            await refreshCurrentPage();
        }
        return;
    }

    if (action === 'prefill-challenge') {
        const challengeId = Number(button.getAttribute('data-challenge-id'));
        prefillField('#participate-form input[name="challenge_id"]', challengeId);
        const form = document.getElementById('participate-form');
        if (form) {
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        setStatus('info', `Challenge ${escapeHtml(challengeId)} is ready in the participation form.`);
        return;
    }

    if (action === 'react') {
        const contentType = button.getAttribute('data-content-type');
        const contentId = Number(button.getAttribute('data-content-id'));
        const result = await runAction('interaction.react', () => apiCall('interaction', 'react', {
            content_type: contentType,
            content_id: contentId,
            reaction_value: 'like',
        }));
        if (result) {
            setStatus('success', `Reaction saved for ${escapeHtml(contentType)} ${escapeHtml(contentId)}.`);
        }
        return;
    }

    if (action === 'purchase-package') {
        const packageId = Number(button.getAttribute('data-package-id'));
        prefillField('form[data-module="finance"][data-action="purchase"] input[name="package_id"]', packageId);
        const result = await runAction('finance.purchase', () => apiCall('finance', 'purchase', { package_id: packageId }));
        if (result) {
            await refreshCurrentPage();
        }
    }
}

function setupButtons() {
    const refreshButton = document.getElementById('btn-refresh');
    if (refreshButton) {
        refreshButton.addEventListener('click', async () => {
            await refreshCurrentPage();
        });
    }

    const meButton = document.getElementById('btn-me');
    if (meButton) {
        meButton.addEventListener('click', async () => {
            const result = await runAction('auth.me', () => apiCall('auth', 'me'));
            if (result?.data?.user) {
                renderProfile(result.data.user);
            }
        });
    }

    const logoutButton = document.getElementById('btn-logout');
    if (logoutButton) {
        logoutButton.addEventListener('click', async () => {
            const result = await runAction('auth.logout', () => apiCall('auth', 'logout'));
            if (!result) {
                return;
            }

            localStorage.removeItem('kodyAuth');
            window.location.href = 'login.php';
        });
    }

    document.querySelectorAll('.load-button').forEach((button) => {
        button.addEventListener('click', async () => {
            await refreshCurrentPage();
        });
    });

    document.body.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-quick]');
        if (!button) {
            return;
        }

        await handleQuickAction(button);
    });
}

function setupForms() {
    document.querySelectorAll('.api-form').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const message = form.getAttribute('data-critical-msg');
            if (message) {
                const confirmed = await openConfirmModal(message);
                if (!confirmed) {
                    return;
                }
            }

            const module = form.getAttribute('data-module');
            const action = form.getAttribute('data-action');
            const payload = normalizePayload(form);

            const result = await runAction(`${module}.${action}`, () => apiCall(module, action, payload));
            if (!result) {
                return;
            }

            if (module === 'auth' && action === 'edit_account' && result?.data?.verification_token) {
                setStatus('info', `Email change submitted. Verify the new address with token ${escapeHtml(result.data.verification_token)} before continuing.`);
                return;
            }

            if (module === 'auth' && (action === 'archive_account' || action === 'delete_account')) {
                localStorage.removeItem('kodyAuth');
                window.location.href = 'login.php';
                return;
            }

            if (module === 'interaction' && action === 'access_course_module' && result?.data?.row) {
                const row = result.data.row;
                renderModuleDetail(row.title, row.body_content, ['Course module', `Sequence ${row.sequence_no}`, row.status]);
            }

            if (module === 'interaction' && action === 'view_feedback' && result?.data?.row) {
                renderTable('feedback-table', [result.data.row], 'Feedback unavailable.');
            }

            if ((module === 'challenge' || module === 'interaction') && (action === 'submit' || action === 'participate_challenge') && result?.data?.submission_id) {
                prefillField('#feedback-form input[name="submission_id"]', result.data.submission_id);
            }

            await refreshCurrentPage();
        });
    });
}

function setupModal() {
    const cancel = document.getElementById('confirm-cancel');
    const accept = document.getElementById('confirm-accept');
    const modal = document.getElementById('confirm-modal');

    if (!cancel || !accept || !modal) {
        return;
    }

    cancel.addEventListener('click', () => closeConfirmModal(false));
    accept.addEventListener('click', () => closeConfirmModal(true));
    modal.addEventListener('click', (event) => {
        if (event.target.id === 'confirm-modal') {
            closeConfirmModal(false);
        }
    });
}

async function bootstrap() {
    state.auth = readAuth();
    if (!state.auth) {
        window.location.href = 'login.php';
        return;
    }

    setupModal();
    setupButtons();
    setupForms();
    applyRoleVisibility();

    const me = await runAction('auth.me bootstrap', () => apiCall('auth', 'me'));
    if (!me?.data?.user) {
        localStorage.removeItem('kodyAuth');
        window.location.href = 'login.php';
        return;
    }

    state.auth.user = me.data.user;
    localStorage.setItem('kodyAuth', JSON.stringify(state.auth));

    const page = document.body.getAttribute('data-page');
    const allowedForPage = PAGE_ACCESS[page];
    if (allowedForPage && !roleAllowed(allowedForPage)) {
        window.location.href = 'home.php';
        return;
    }

    renderProfile(me.data.user);
    renderRolePermissions(me.data.user.role);
    applyRoleVisibility();

    await refreshCurrentPage();
}

bootstrap();
