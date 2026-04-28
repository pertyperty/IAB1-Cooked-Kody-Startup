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
    learningSearchTimer: null,
    learning: {
        search: '',
        courses: [],
        modules: [],
    },
    faq: {
        rows: [],
        search: '',
    },
    challenges: {
        rows: [],
        selectedId: null,
        attemptState: null,
    },
};

const PAGE_ACCESS = {
    home: 'learner,contributor,instructor,moderator,administrator',
    profile: 'learner,contributor,instructor,moderator,administrator',
    learn: 'learner,contributor,instructor,moderator,administrator',
    challenges: 'learner,contributor,instructor,moderator,administrator',
    'challenge-play': 'learner,contributor,instructor,moderator,administrator',
    leaderboard: 'learner,contributor,instructor,moderator,administrator',
    faq: 'learner,contributor,instructor,moderator,administrator',
    module: 'learner,contributor,instructor,moderator,administrator',
    creator: 'contributor,instructor,administrator',
    'creator-courses': 'instructor,moderator,administrator',
    'creator-modules': 'instructor,moderator,administrator',
    'creator-challenges': 'contributor,instructor,administrator',
    rewards: 'learner,contributor,instructor,moderator,administrator',
    topup: 'learner,contributor,instructor,moderator,administrator',
    finance: 'learner,contributor,instructor,moderator,administrator',
    governance: 'moderator,administrator',
};

const ROLE_RANK = {
    learner: 1,
    contributor: 2,
    instructor: 3,
    moderator: 4,
    administrator: 5,
};

const CREATOR_SUBPAGE_CONFIG = {
    'creator-courses': { idParam: 'course_id' },
    'creator-modules': { idParam: 'module_id' },
    'creator-challenges': { idParam: 'challenge_id' },
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

function formatLikes(value) {
    return `${formatNumber(value)} like${Number(value || 0) === 1 ? '' : 's'}`;
}

function toStatusClass(status) {
    const normalized = String(status || '').toLowerCase();
    if (normalized.includes('approve') || normalized.includes('active') || normalized.includes('published') || normalized.includes('success') || normalized.includes('accept')) {
        return 'status-pill good';
    }

    if (normalized.includes('reject') || normalized.includes('suspend') || normalized.includes('delete') || normalized.includes('fail')) {
        return 'status-pill bad';
    }

    if (normalized.includes('pending') || normalized.includes('review') || normalized.includes('processing') || normalized.includes('draft')) {
        return 'status-pill warn';
    }

    return 'status-pill';
}

function renderStatusPill(status) {
    return `<span class="${toStatusClass(status)}">${escapeHtml(status || 'n/a')}</span>`;
}

function updateNavWallet(user) {
    const walletNode = document.getElementById('nav-wallet-balance');
    if (!walletNode || !user) {
        return;
    }

    walletNode.textContent = `${formatNumber(user.kodebits_balance || 0)} KB`;
}

function redirectToModulePage(courseId, moduleId = null, isStandalone = false) {
    const params = new URLSearchParams();
    if (courseId) {
        params.set('course_id', String(courseId));
    }
    if (moduleId) {
        params.set('module_id', String(moduleId));
    }
    if (isStandalone) {
        params.set('standalone', '1');
    }

    window.location.href = `module.php?${params.toString()}`;
}

async function redirectToFirstCourseModule(courseId) {
    const moduleList = await apiCall('interaction', 'course_modules', { course_id: courseId });
    const first = moduleList?.data?.rows?.[0];

    if (!first?.id) {
        setStatus('info', 'Enrollment completed, but this course has no published/active modules yet.');
        return;
    }

    redirectToModulePage(courseId, Number(first.id));
}

async function loadModulePage() {
    const page = document.body.getAttribute('data-page');
    if (page !== 'module') {
        return;
    }

    const params = new URLSearchParams(window.location.search);
    const courseId = Number(params.get('course_id') || 0);
    const moduleId = Number(params.get('module_id') || 0);
    const standalone = params.get('standalone') === '1';

    const heading = document.getElementById('module-page-heading');
    if (!heading) {
        return;
    }

    if (moduleId > 0 && standalone) {
        const result = await runAction('interaction.access_standalone_module', () => apiCall('interaction', 'access_standalone_module', { module_id: moduleId }));
        if (!result?.data?.row) {
            return;
        }

        const challenges = await apiCall('interaction', 'browse_challenges');
        renderModuleChallengeCatalog(challenges.data.rows || []);

        const row = result.data.row;
        heading.textContent = row.title || 'Standalone Module';
        renderModuleDetail(row.title, row.body_content, [row.module_type, row.status, `${row.kodebits_cost} KB`, formatLikes(row.likes_count || 0)]);
        return;
    }

    if (courseId > 0 && moduleId > 0) {
        const result = await runAction('interaction.access_course_module', () => apiCall('interaction', 'access_course_module', {
            course_id: courseId,
            module_id: moduleId,
        }));
        if (!result?.data?.row) {
            return;
        }

        const challenges = await apiCall('interaction', 'browse_challenges');
        renderModuleChallengeCatalog(challenges.data.rows || []);

        const row = result.data.row;
        heading.textContent = row.title || 'Course Module';
        renderModuleDetail(row.title, row.body_content, ['Course module', `Sequence ${row.sequence_no}`, row.status, formatLikes(row.likes_count || 0)]);
        return;
    }

    if (courseId > 0 && moduleId <= 0) {
        const listResult = await runAction('interaction.course_modules', () => apiCall('interaction', 'course_modules', { course_id: courseId }));
        const first = listResult?.data?.rows?.[0];
        if (!first?.id) {
            setStatus('error', 'No published/active modules found for this course.');
            return;
        }

        redirectToModulePage(courseId, Number(first.id));
        return;
    }

    setStatus('error', 'No module was selected. Return to Learning and choose a module.');
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

    const userRank = ROLE_RANK[state.auth.user.role] || 0;
    const allowedRanks = allowedCsv.split(',').map((item) => ROLE_RANK[item.trim()] || 0).filter((rank) => rank > 0);
    if (allowedRanks.length === 0) {
        return false;
    }

    const minRank = Math.min(...allowedRanks);
    return userRank >= minRank;
}

function applyRoleVisibility() {
    document.querySelectorAll('[data-roles]').forEach((node) => {
        node.style.display = roleAllowed(node.getAttribute('data-roles')) ? '' : 'none';
    });
}

function decodeRowPayload(raw) {
    if (!raw) {
        return null;
    }

    try {
        return JSON.parse(decodeURIComponent(raw));
    } catch (error) {
        console.error(error);
        return null;
    }
}

function encodeRowPayload(row) {
    return encodeURIComponent(JSON.stringify(row || {}));
}

function prefillApiForm(module, action, values) {
    const form = document.querySelector(`form.api-form[data-module="${module}"][data-action="${action}"]`);
    if (!form || !values) {
        return;
    }

    Object.entries(values).forEach(([key, value]) => {
        const field = form.querySelector(`[name="${key}"]`);
        if (!field || value === null || typeof value === 'undefined') {
            return;
        }

        field.value = String(value);
    });

    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function markCreatorSubpageAccess(page, mode) {
    try {
        const payload = { page, mode, at: Date.now() };
        sessionStorage.setItem('kodyCreatorSubpageAccess', JSON.stringify(payload));
    } catch (error) {
        console.error(error);
    }
}

function hasCreatorSubpageAccess(page, mode) {
    try {
        const raw = sessionStorage.getItem('kodyCreatorSubpageAccess');
        if (!raw) {
            return false;
        }

        const parsed = JSON.parse(raw);
        const ageMs = Date.now() - Number(parsed?.at || 0);
        if (ageMs > 10 * 60 * 1000) {
            return false;
        }

        return parsed?.page === page && parsed?.mode === mode;
    } catch (error) {
        console.error(error);
        return false;
    }
}

function enforceCreatorSubpageIntent() {
    const page = document.body.getAttribute('data-page');
    const cfg = CREATOR_SUBPAGE_CONFIG[page];
    if (!cfg) {
        return true;
    }

    const params = new URLSearchParams(window.location.search);
    const editId = Number(params.get(cfg.idParam) || 0);
    const intent = String(params.get('intent') || '').toLowerCase();
    const mode = editId > 0 ? 'edit' : (intent === 'create' ? 'create' : '');

    if (!mode || !hasCreatorSubpageAccess(page, mode)) {
        window.location.href = 'creator.php';
        return false;
    }

    document.querySelectorAll('[data-creator-mode]').forEach((node) => {
        const allowedMode = String(node.getAttribute('data-creator-mode') || 'both');
        node.style.display = (allowedMode === 'both' || allowedMode === mode) ? '' : 'none';
    });

    return true;
}

function getRowQuickActions(targetId, row) {
    if (!row || typeof row !== 'object') {
        return [];
    }

    if (targetId === 'content-courses-table') {
        return [
            { label: 'Edit', quick: 'row-course-edit', style: 'secondary' },
            { label: 'Archive', quick: 'row-course-archive', style: 'danger', confirm: 'Archive this course?' },
            { label: 'Delete', quick: 'row-course-delete', style: 'danger', confirm: 'Delete this course now?' },
        ];
    }

    if (targetId === 'content-modules-table') {
        const actions = [
            { label: 'Edit', quick: 'row-module-edit', style: 'secondary' },
            { label: 'Archive', quick: 'row-module-archive', style: 'danger', confirm: 'Archive this module?' },
            { label: 'Delete', quick: 'row-module-delete', style: 'danger', confirm: 'Delete this module now?' },
        ];

        if (String(row.module_type || '').toLowerCase() === 'standalone') {
            actions.splice(2, 0, { label: 'Assign', quick: 'row-module-assign', style: 'secondary' });
        }

        return actions;
    }

    if (targetId === 'creator-challenges-table') {
        const actions = [
            { label: 'Edit', quick: 'row-challenge-edit', style: 'secondary' },
            { label: 'Archive', quick: 'row-challenge-archive', style: 'danger', confirm: 'Archive this challenge?' },
            { label: 'Delete', quick: 'row-challenge-delete', style: 'danger', confirm: 'Delete this challenge now?' },
            { label: 'Open Coding', quick: 'row-challenge-open-play', style: 'secondary' },
        ];

        if (roleAllowed('administrator')) {
            actions.push({ label: 'Weekly', quick: 'row-challenge-configure-weekly', style: 'secondary' });
        }

        return actions;
    }

    if (targetId === 'challenge-review-table') {
        return [
            { label: 'Approve', quick: 'row-challenge-approve' },
            { label: 'Reject', quick: 'row-challenge-reject', style: 'danger' },
        ];
    }

    if (targetId === 'preset-table') {
        return [
            { label: 'Use for Activity', quick: 'row-preset-use', style: 'secondary' },
        ];
    }

    if (targetId === 'weekly-table') {
        return [
            { label: 'Evaluate', quick: 'row-weekly-evaluate', style: 'secondary' },
            { label: 'Publish', quick: 'row-weekly-publish' },
        ];
    }

    if (targetId === 'gamification-leaderboard-table' && roleAllowed('moderator,administrator')) {
        return [
            { label: 'Grant Reward', quick: 'row-leaderboard-grant', style: 'secondary' },
        ];
    }

    if (targetId === 'earnings-table') {
        return [
            { label: 'Request Payout', quick: 'row-earning-request-payout', style: 'secondary' },
        ];
    }

    if (targetId === 'requests-review-table') {
        return [
            { label: 'Approve', quick: 'row-request-approve' },
            { label: 'Reject', quick: 'row-request-reject', style: 'danger' },
        ];
    }

    if (targetId === 'credentials-review-table') {
        return [
            { label: 'Accept', quick: 'row-credential-accept' },
            { label: 'Reject', quick: 'row-credential-reject', style: 'danger' },
        ];
    }

    if (targetId === 'users-table') {
        const actions = [
            { label: 'Suspend', quick: 'row-user-suspend', style: 'danger' },
            { label: 'Reinstate', quick: 'row-user-reinstate', style: 'secondary' },
        ];

        if (roleAllowed('administrator')) {
            actions.push({ label: 'Fill Update', quick: 'row-user-fill-update', style: 'secondary' });
        }

        return actions;
    }

    if (targetId === 'reports-table') {
        return [
            { label: 'Archive Target', quick: 'row-report-archive', style: 'danger', confirm: 'Archive target content for this report?' },
            { label: 'Reject Report', quick: 'row-report-reject', style: 'secondary' },
        ];
    }

    return [];
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
    const includeActions = rows.some((row) => getRowQuickActions(targetId, row).length > 0);
    const headerHtml = headers.map((header) => `<th>${escapeHtml(header)}</th>`).join('');

    const bodyHtml = rows.map((row) => {
        const encodedRow = encodeURIComponent(JSON.stringify(row));
        const actionButtons = getRowQuickActions(targetId, row).map((item) => {
            const styleClass = item.style || '';
            const confirmAttr = item.confirm ? ` data-confirm="${escapeHtml(item.confirm)}"` : '';
            return `<button type="button" class="row-action-btn ${styleClass}" data-quick="${escapeHtml(item.quick)}" data-row="${escapeHtml(encodedRow)}"${confirmAttr}>${escapeHtml(item.label)}</button>`;
        }).join('');

        const actionCell = includeActions ? `<td><div class="table-action-group">${actionButtons}</div></td>` : '';
        return `<tr>${headers.map((header) => `<td>${escapeHtml(row[header])}</td>`).join('')}${actionCell}</tr>`;
    }).join('');

    const actionHeader = includeActions ? '<th>actions</th>' : '';
    target.innerHTML = `<table><thead><tr>${headerHtml}${actionHeader}</tr></thead><tbody>${bodyHtml}</tbody></table>`;
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

function renderGlobalLeaderboardCards(rows) {
    const target = document.getElementById('leaderboard-cards');
    if (!target) {
        return;
    }

    if (!rows || rows.length === 0) {
        target.innerHTML = '<div class="empty-state">No global leaderboard rows yet.</div>';
        return;
    }

    target.innerHTML = rows.slice(0, 10).map((row, index) => `
        <article class="catalog-card leaderboard-card">
            <h3>#${escapeHtml(index + 1)} ${escapeHtml(row.full_name || 'User')}</h3>
            <div class="pill-row">
                <span class="pill">XP ${escapeHtml(formatNumber(row.xp_points || 0))}</span>
                <span class="pill">KB ${escapeHtml(formatNumber(row.kodebits_balance || 0))}</span>
                <span class="pill">${escapeHtml(row.primary_role || 'role n/a')}</span>
            </div>
        </article>
    `).join('');
}

function renderWeeklyLeaderboardCards(rows) {
    const target = document.getElementById('weekly-leaderboard-cards');
    if (!target) {
        return;
    }

    if (!rows || rows.length === 0) {
        target.innerHTML = '<div class="empty-state">No weekly leaderboard rows yet.</div>';
        return;
    }

    target.innerHTML = rows.slice(0, 16).map((row) => `
        <article class="catalog-card leaderboard-card">
            <h3>${escapeHtml(row.weekly_code || 'Weekly')} • Rank #${escapeHtml(row.rank_position || 'n/a')}</h3>
            <p>${escapeHtml(row.challenge_title || 'Challenge')}</p>
            <div class="pill-row">
                <span class="pill">${escapeHtml(row.full_name || 'User')}</span>
                <span class="pill">Score ${escapeHtml(formatNumber(row.final_score || 0))}</span>
                <span class="pill">Submission #${escapeHtml(row.best_submission_id || 'n/a')}</span>
            </div>
        </article>
    `).join('');
}

function renderCreatorCourseCards(courses, modules, challenges) {
    const target = document.getElementById('creator-course-cards');
    if (!target) {
        return;
    }

    if (!courses || courses.length === 0) {
        target.innerHTML = '<div class="empty-state">No courses created yet.</div>';
        return;
    }

    const moduleCountByCourse = new Map();
    (modules || []).forEach((item) => {
        const key = Number(item.course_id || 0);
        if (key > 0) {
            moduleCountByCourse.set(key, (moduleCountByCourse.get(key) || 0) + 1);
        }
    });

    target.innerHTML = courses.map((row) => `
        <article class="catalog-card cms-card">
            <h3>${escapeHtml(row.title || `Course #${row.id}`)}</h3>
            <p>${escapeHtml(row.description || 'No description provided.')}</p>
            <div class="pill-row">
                ${renderStatusPill(row.status || 'status n/a')}
                <span class="pill">Cost ${escapeHtml(formatNumber(row.kodebits_cost || 0))} KB</span>
                <span class="pill">Modules ${escapeHtml(formatNumber(moduleCountByCourse.get(Number(row.id)) || 0))}</span>
                <span class="pill">Challenges ${escapeHtml(formatNumber((challenges || []).length))}</span>
            </div>
            <div class="inline-actions">
                <button type="button" class="secondary" data-quick="row-course-edit" data-row="${escapeHtml(encodeRowPayload(row))}">Edit</button>
                <button type="button" class="danger" data-quick="row-course-archive" data-row="${escapeHtml(encodeRowPayload(row))}" data-confirm="Archive this course?">Archive</button>
                <button type="button" class="danger" data-quick="row-course-delete" data-row="${escapeHtml(encodeRowPayload(row))}" data-confirm="Delete this course now?">Delete</button>
            </div>
        </article>
    `).join('');
}

function renderCreatorModuleCards(rows) {
    const target = document.getElementById('creator-module-cards');
    if (!target) {
        return;
    }

    if (!rows || rows.length === 0) {
        target.innerHTML = '<div class="empty-state">No modules created yet.</div>';
        return;
    }

    target.innerHTML = rows.map((row) => {
        const showAssign = String(row.module_type || '').toLowerCase() === 'standalone';
        return `
            <article class="catalog-card cms-card">
                <h3>${escapeHtml(row.title || `Module #${row.id}`)}</h3>
                <p>${escapeHtml(row.body_content || 'No module body available.')}</p>
                <div class="pill-row">
                    <span class="pill">${escapeHtml(row.module_type || 'module')}</span>
                    ${renderStatusPill(row.status || 'status n/a')}
                    <span class="pill">Cost ${escapeHtml(formatNumber(row.kodebits_cost || 0))} KB</span>
                    <span class="pill">Likes ${escapeHtml(formatNumber(row.likes_count || 0))}</span>
                </div>
                <div class="inline-actions">
                    <button type="button" class="secondary" data-quick="row-module-edit" data-row="${escapeHtml(encodeRowPayload(row))}">Edit</button>
                    ${showAssign ? `<button type="button" class="secondary" data-quick="row-module-assign" data-row="${escapeHtml(encodeRowPayload(row))}">Assign to Course</button>` : ''}
                    <button type="button" class="danger" data-quick="row-module-archive" data-row="${escapeHtml(encodeRowPayload(row))}" data-confirm="Archive this module?">Archive</button>
                    <button type="button" class="danger" data-quick="row-module-delete" data-row="${escapeHtml(encodeRowPayload(row))}" data-confirm="Delete this module now?">Delete</button>
                </div>
            </article>
        `;
    }).join('');
}

function renderCreatorChallengeCards(rows) {
    const target = document.getElementById('creator-challenge-cards');
    if (!target) {
        return;
    }

    if (!rows || rows.length === 0) {
        target.innerHTML = '<div class="empty-state">No challenges created yet.</div>';
        return;
    }

    target.innerHTML = rows.map((row) => `
        <article class="catalog-card cms-card">
            <h3>${escapeHtml(row.title || `Challenge #${row.id}`)}</h3>
            <p>${escapeHtml(row.prompt_text || 'No challenge prompt available.')}</p>
            <div class="pill-row">
                <span class="pill">${escapeHtml(row.difficulty_level || 'difficulty n/a')}</span>
                ${renderStatusPill(row.status || 'status n/a')}
                <span class="pill">Cost ${escapeHtml(formatNumber(row.kodebits_cost || 0))} KB</span>
                <span class="pill">Likes ${escapeHtml(formatNumber(row.likes_count || 0))}</span>
            </div>
            <div class="inline-actions">
                <button type="button" class="secondary" data-quick="row-challenge-edit" data-row="${escapeHtml(encodeRowPayload(row))}">Edit</button>
                <button type="button" class="secondary" data-quick="row-challenge-open-play" data-row="${escapeHtml(encodeRowPayload(row))}">Open Coding Page</button>
                <button type="button" class="danger" data-quick="row-challenge-archive" data-row="${escapeHtml(encodeRowPayload(row))}" data-confirm="Archive this challenge?">Archive</button>
                <button type="button" class="danger" data-quick="row-challenge-delete" data-row="${escapeHtml(encodeRowPayload(row))}" data-confirm="Delete this challenge now?">Delete</button>
            </div>
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

    updateNavWallet(user);
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

function renderHomeShortcuts() {
    const target = document.getElementById('home-shortcuts');
    if (!target) {
        return;
    }

    const links = [
        { href: 'learn.php', title: 'Learning Hub', copy: 'Browse courses and modules.' },
        { href: 'challenges.php', title: 'Challenge Arena', copy: 'Select a challenge, confirm entry, and submit your code.' },
        { href: 'leaderboard.php', title: 'Leaderboard', copy: 'Track rankings in a dedicated leaderboard page.' },
        { href: 'faq.php', title: 'FAQ and Help', copy: 'Read platform FAQs in the help center page.' },
        { href: 'profile.php', title: 'Profile Settings', copy: 'Manage account updates and role requests.' },
        { href: 'topup.php', title: 'Top Up KodeBits', copy: 'Purchase KodeBits packages and manage wallet usage.' },
        { href: 'finance.php', title: 'Earnings and Payouts', copy: 'Track creator earnings and submit payout requests.' },
        { href: 'rewards.php', title: 'Rewards and Leaderboard', copy: 'Check rankings and gamification flows.' },
    ];

    if (roleAllowed('contributor,instructor,administrator')) {
        links.push({ href: 'creator.php', title: 'Creator Workspace', copy: 'Create and manage courses, modules, and challenges.' });
    }

    if (roleAllowed('moderator,administrator')) {
        links.push({ href: 'governance.php', title: 'Governance Center', copy: 'Review reports, users, and moderation queues.' });
    }

    target.innerHTML = links.map((item) => `
        <a class="home-shortcut-card" href="${escapeHtml(item.href)}">
            <strong>${escapeHtml(item.title)}</strong>
            <p>${escapeHtml(item.copy)}</p>
        </a>
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
                <span class="pill">${escapeHtml(formatLikes(row.likes_count || 0))}</span>
            </div>
            <div class="inline-actions">
                <button type="button" data-quick="access-course-module" data-course-id="${escapeHtml(courseId || '')}" data-module-id="${escapeHtml(row.id)}">Open Module</button>
                <button type="button" class="secondary" data-quick="react" data-content-type="module" data-content-id="${escapeHtml(row.id)}">Like</button>
            </div>
        </article>
    `).join('');
}

function renderLearningSnapshot(rows) {
    const target = document.getElementById('learning-table');
    if (!target) {
        return;
    }

    if (!rows || rows.length === 0) {
        target.innerHTML = '<div class="empty-state">You do not have any enrollments yet.</div>';
        return;
    }

    target.innerHTML = `<div class="learning-snapshot-grid">${rows.map((row) => {
        const openFirstDisabled = !row.first_module_id ? 'disabled' : '';
        return `
            <article class="catalog-card learning-snapshot-card">
                <div>
                    <h3>${escapeHtml(row.course_title)}</h3>
                    <p>${escapeHtml(row.course_description || 'No description provided yet.')}</p>
                </div>
                <div class="pill-row">
                    <span class="pill">${escapeHtml(row.course_type || 'course')}</span>
                    ${renderStatusPill(row.enrollment_status || 'status n/a')}
                    <span class="pill">Progress ${escapeHtml(formatNumber(row.progress_percent || 0))}%</span>
                    <span class="pill">${escapeHtml(formatNumber(row.module_count || 0))} modules</span>
                    <span class="pill">${escapeHtml(formatNumber(row.challenge_submission_count || 0))} challenge submissions</span>
                </div>
                <div class="inline-actions">
                    <button type="button" data-quick="view-course-modules" data-course-id="${escapeHtml(row.course_id)}">View Modules</button>
                    <button type="button" class="secondary" data-quick="access-course-module" data-course-id="${escapeHtml(row.course_id)}" data-module-id="${escapeHtml(row.first_module_id || '')}" ${openFirstDisabled}>Open First Module</button>
                    <button type="button" class="secondary" data-quick="open-first-challenge">Start Challenge</button>
                </div>
            </article>
        `;
    }).join('')}</div>`;
}

function renderContributorRequestCards(rows) {
    const target = document.getElementById('contributor-request-cards');
    if (!target) {
        return;
    }

    if (!rows || rows.length === 0) {
        target.innerHTML = '<div class="empty-state">No contributor requests pending review.</div>';
        return;
    }

    target.innerHTML = rows.map((row) => `
        <article class="catalog-card governance-card">
            <div>
                <h3>${escapeHtml(row.full_name || `User #${row.user_id}`)}</h3>
                <p>${escapeHtml(row.notes || 'No notes provided.')}</p>
            </div>
            <div class="pill-row">
                <span class="pill">Request #${escapeHtml(row.id)}</span>
                <span class="pill">Role: ${escapeHtml(row.requested_role)}</span>
                ${renderStatusPill(row.status)}
                <span class="pill">Created: ${escapeHtml(formatDate(row.created_at))}</span>
            </div>
            <div class="inline-actions">
                <button type="button" data-quick="row-request-approve" data-row="${escapeHtml(encodeRowPayload(row))}">Approve</button>
                <button type="button" class="danger" data-quick="row-request-reject" data-row="${escapeHtml(encodeRowPayload(row))}">Reject</button>
            </div>
        </article>
    `).join('');
}

function renderCredentialReviewCards(rows) {
    const target = document.getElementById('credential-review-cards');
    if (!target) {
        return;
    }

    if (!rows || rows.length === 0) {
        target.innerHTML = '<div class="empty-state">No instructor credentials pending review.</div>';
        return;
    }

    target.innerHTML = rows.map((row) => `
        <article class="catalog-card governance-card">
            <div>
                <h3>${escapeHtml(row.full_name || `User #${row.user_id}`)}</h3>
                <p>${escapeHtml(row.credential_title || 'Credential title unavailable.')}</p>
            </div>
            <div class="pill-row">
                <span class="pill">Credential #${escapeHtml(row.id)}</span>
                ${renderStatusPill(row.verification_status)}
                <span class="pill">Submitted: ${escapeHtml(formatDate(row.created_at))}</span>
            </div>
            <div class="inline-actions">
                <a class="button-link secondary-link" href="${escapeHtml(row.file_url || '#')}" target="_blank" rel="noopener noreferrer">View File</a>
                <button type="button" data-quick="row-credential-accept" data-row="${escapeHtml(encodeRowPayload(row))}">Accept</button>
                <button type="button" class="danger" data-quick="row-credential-reject" data-row="${escapeHtml(encodeRowPayload(row))}">Reject</button>
            </div>
        </article>
    `).join('');
}

function renderChallengeReviewCards(rows) {
    const target = document.getElementById('challenge-review-cards');
    if (!target) {
        return;
    }

    if (!rows || rows.length === 0) {
        target.innerHTML = '<div class="empty-state">No challenges are waiting for review.</div>';
        return;
    }

    target.innerHTML = rows.map((row) => `
        <article class="catalog-card governance-card">
            <div>
                <h3>${escapeHtml(row.title || `Challenge #${row.id}`)}</h3>
                <p>${escapeHtml(row.prompt_text || 'No prompt text available.')}</p>
            </div>
            <div class="pill-row">
                <span class="pill">Challenge #${escapeHtml(row.id)}</span>
                <span class="pill">${escapeHtml(row.difficulty_level || 'difficulty n/a')}</span>
                <span class="pill">${escapeHtml(row.language_scope || 'language n/a')}</span>
                ${renderStatusPill(row.status || 'status n/a')}
                <span class="pill">${escapeHtml(formatLikes(row.likes_count || 0))}</span>
            </div>
            <div class="inline-actions">
                <button type="button" data-quick="row-challenge-approve" data-row="${escapeHtml(encodeRowPayload(row))}">Approve</button>
                <button type="button" class="danger" data-quick="row-challenge-reject" data-row="${escapeHtml(encodeRowPayload(row))}">Reject</button>
            </div>
        </article>
    `).join('');
}

function renderRequestHistoryCards(rows) {
    const target = document.getElementById('request-history-cards');
    if (!target) {
        return;
    }

    if (!rows || rows.length === 0) {
        target.innerHTML = '<div class="empty-state">No request history yet.</div>';
        return;
    }

    target.innerHTML = rows.map((row) => `
        <article class="catalog-card process-card">
            <h3>${escapeHtml(row.requested_role || 'role request')}</h3>
            <p>${escapeHtml(row.notes || 'No request notes provided.')}</p>
            <div class="pill-row">
                <span class="pill">Request #${escapeHtml(row.id)}</span>
                ${renderStatusPill(row.status || 'status n/a')}
                <span class="pill">Created: ${escapeHtml(formatDate(row.created_at))}</span>
                <span class="pill">Reviewed: ${escapeHtml(formatDate(row.reviewed_at))}</span>
            </div>
        </article>
    `).join('');
}

function renderCredentialHistoryCards(rows) {
    const target = document.getElementById('credential-history-cards');
    if (!target) {
        return;
    }

    if (!rows || rows.length === 0) {
        target.innerHTML = '<div class="empty-state">No credential history yet.</div>';
        return;
    }

    target.innerHTML = rows.map((row) => `
        <article class="catalog-card process-card">
            <h3>${escapeHtml(row.credential_title || 'Credential')}</h3>
            <p><a href="${escapeHtml(row.file_url || '#')}" target="_blank" rel="noopener noreferrer">Open submitted file</a></p>
            <div class="pill-row">
                <span class="pill">Credential #${escapeHtml(row.id)}</span>
                ${renderStatusPill(row.verification_status || 'status n/a')}
                <span class="pill">Submitted: ${escapeHtml(formatDate(row.created_at))}</span>
                <span class="pill">Validated: ${escapeHtml(formatDate(row.validated_at))}</span>
            </div>
        </article>
    `).join('');
}

function renderEarningsCards(rows) {
    const target = document.getElementById('earnings-cards');
    if (!target) {
        return;
    }

    if (!rows || rows.length === 0) {
        target.innerHTML = '<div class="empty-state">No earnings records available.</div>';
        return;
    }

    target.innerHTML = rows.map((row) => `
        <article class="catalog-card process-card">
            <h3>${escapeHtml(row.period_label || 'Earnings Period')}</h3>
            <p>Creator share: PHP ${escapeHtml(Number(row.creator_share_php || 0).toFixed(2))}</p>
            <div class="pill-row">
                <span class="pill">Earning #${escapeHtml(row.id)}</span>
                <span class="pill">Gross: PHP ${escapeHtml(Number(row.gross_php || 0).toFixed(2))}</span>
                ${renderStatusPill(row.payout_status || 'status n/a')}
                <span class="pill">Generated: ${escapeHtml(formatDate(row.generated_at))}</span>
            </div>
            <div class="inline-actions">
                <button type="button" data-quick="row-earning-request-payout" data-row="${escapeHtml(encodeRowPayload(row))}">Request Payout</button>
            </div>
        </article>
    `).join('');
}

function renderPayoutRequestCards(rows) {
    const target = document.getElementById('payout-request-cards');
    if (!target) {
        return;
    }

    if (!rows || rows.length === 0) {
        target.innerHTML = '<div class="empty-state">No payout requests yet.</div>';
        return;
    }

    target.innerHTML = rows.map((row) => `
        <article class="catalog-card process-card">
            <h3>Payout Request #${escapeHtml(row.id)}</h3>
            <p>PHP ${escapeHtml(Number(row.request_amount_php || 0).toFixed(2))} via ${escapeHtml(row.payout_channel || 'channel n/a')}</p>
            <div class="pill-row">
                <span class="pill">Earning #${escapeHtml(row.creator_earning_id)}</span>
                ${renderStatusPill(row.payout_status || 'status n/a')}
                <span class="pill">Requested: ${escapeHtml(formatDate(row.requested_at))}</span>
                <span class="pill">Processed: ${escapeHtml(formatDate(row.processed_at))}</span>
            </div>
        </article>
    `).join('');
}

function renderTopupHistoryCards(rows) {
    const target = document.getElementById('topup-history-cards');
    if (!target) {
        return;
    }

    if (!rows || rows.length === 0) {
        target.innerHTML = '<div class="empty-state">No transactions recorded yet.</div>';
        return;
    }

    target.innerHTML = rows.slice(0, 40).map((row) => `
        <article class="catalog-card process-card">
            <h3>${escapeHtml(String(row.transaction_type || 'transaction').toUpperCase())} ${escapeHtml(formatNumber(row.amount_kb || 0))} KB</h3>
            <p>${escapeHtml(row.notes || 'Wallet transaction')}</p>
            <div class="pill-row">
                <span class="pill">Ref: ${escapeHtml(row.reference_type || 'n/a')} / ${escapeHtml(row.reference_id || 'n/a')}</span>
                ${renderStatusPill(row.payment_status || 'recorded')}
                <span class="pill">PHP: ${escapeHtml(Number(row.php_amount || 0).toFixed(2))}</span>
                <span class="pill">At: ${escapeHtml(formatDate(row.created_at))}</span>
            </div>
        </article>
    `).join('');
}

function renderPresetCards(rows) {
    const target = document.getElementById('preset-cards');
    if (!target) {
        return;
    }

    if (!rows || rows.length === 0) {
        target.innerHTML = '<div class="empty-state">No presets available.</div>';
        return;
    }

    target.innerHTML = rows.map((row) => `
        <article class="catalog-card process-card">
            <h3>${escapeHtml(row.preset_name || `Preset #${row.id}`)}</h3>
            <p>Prepare an activity by selecting this preset.</p>
            <div class="pill-row">
                <span class="pill">Preset #${escapeHtml(row.id)}</span>
                ${renderStatusPill(row.status || 'status n/a')}
                <span class="pill">Created: ${escapeHtml(formatDate(row.created_at))}</span>
            </div>
            <div class="inline-actions">
                <button type="button" class="secondary" data-quick="row-preset-use" data-row="${escapeHtml(encodeRowPayload(row))}">Use for Activity</button>
            </div>
        </article>
    `).join('');
}

function renderWeeklyCards(rows) {
    const target = document.getElementById('weekly-cards');
    if (!target) {
        return;
    }

    if (!rows || rows.length === 0) {
        target.innerHTML = '<div class="empty-state">No weekly challenges configured yet.</div>';
        return;
    }

    target.innerHTML = rows.map((row) => `
        <article class="catalog-card process-card">
            <h3>${escapeHtml(row.weekly_code || `Weekly #${row.id}`)}</h3>
            <p>${escapeHtml(row.challenge_title || 'Challenge not found')}</p>
            <div class="pill-row">
                <span class="pill">Weekly #${escapeHtml(row.id)}</span>
                <span class="pill">Challenge #${escapeHtml(row.challenge_id)}</span>
                <span class="pill">Submissions: ${escapeHtml(formatNumber(row.submission_count || 0))}</span>
                <span class="pill">Results: ${escapeHtml(formatNumber(row.result_count || 0))}</span>
                ${renderStatusPill(row.status || 'status n/a')}
            </div>
            <div class="inline-actions">
                <button type="button" class="secondary" data-quick="row-weekly-evaluate" data-row="${escapeHtml(encodeRowPayload(row))}">Evaluate</button>
                <button type="button" data-quick="row-weekly-publish" data-row="${escapeHtml(encodeRowPayload(row))}">Publish</button>
            </div>
        </article>
    `).join('');
}

function renderUserGovernanceCards(rows) {
    const target = document.getElementById('user-governance-cards');
    if (!target) {
        return;
    }

    if (!rows || rows.length === 0) {
        target.innerHTML = '<div class="empty-state">No user accounts found.</div>';
        return;
    }

    target.innerHTML = rows.slice(0, 20).map((row) => `
        <article class="catalog-card governance-card process-card">
            <div>
                <h3>${escapeHtml(row.full_name || `User #${row.id}`)}</h3>
                <p>${escapeHtml(row.email || 'No email available')}</p>
            </div>
            <div class="pill-row">
                <span class="pill">User #${escapeHtml(row.id)}</span>
                <span class="pill">Role: ${escapeHtml(row.primary_role || 'n/a')}</span>
                ${renderStatusPill(row.status || 'status n/a')}
                <span class="pill">Created: ${escapeHtml(formatDate(row.created_at))}</span>
            </div>
            <div class="inline-actions">
                <button type="button" class="danger" data-quick="row-user-suspend" data-row="${escapeHtml(encodeRowPayload(row))}">Suspend</button>
                <button type="button" class="secondary" data-quick="row-user-reinstate" data-row="${escapeHtml(encodeRowPayload(row))}">Reinstate</button>
            </div>
        </article>
    `).join('');
}

function renderReportGovernanceCards(rows) {
    const target = document.getElementById('report-governance-cards');
    if (!target) {
        return;
    }

    if (!rows || rows.length === 0) {
        target.innerHTML = '<div class="empty-state">No content reports found.</div>';
        return;
    }

    target.innerHTML = rows.map((row) => `
        <article class="catalog-card governance-card process-card">
            <div>
                <h3>Report #${escapeHtml(row.id)}</h3>
                <p>${escapeHtml(row.report_reason || 'No report reason provided.')}</p>
            </div>
            <div class="pill-row">
                <span class="pill">Target: ${escapeHtml(row.content_type)} #${escapeHtml(row.content_id)}</span>
                <span class="pill">Reporter: #${escapeHtml(row.reporter_user_id)}</span>
                ${renderStatusPill(row.report_status || 'status n/a')}
                <span class="pill">Filed: ${escapeHtml(formatDate(row.created_at))}</span>
            </div>
            <div class="inline-actions">
                <button type="button" class="danger" data-quick="row-report-archive" data-row="${escapeHtml(encodeRowPayload(row))}">Archive Target</button>
                <button type="button" class="secondary" data-quick="row-report-reject" data-row="${escapeHtml(encodeRowPayload(row))}">Reject Report</button>
            </div>
        </article>
    `).join('');
}

function setupDeveloperConsoles() {
    document.querySelectorAll('.developer-console').forEach((consoleNode) => {
        const heading = consoleNode.querySelector('h2');
        if (!heading) {
            return;
        }

        if (consoleNode.querySelector('.dev-console-toggle')) {
            return;
        }

        const body = document.createElement('div');
        body.className = 'developer-console-body';

        while (heading.nextSibling) {
            body.appendChild(heading.nextSibling);
        }

        consoleNode.appendChild(body);
        consoleNode.classList.add('developer-console-collapsed');

        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'secondary dev-console-toggle';
        toggle.textContent = 'Show Direct API Tools';
        toggle.addEventListener('click', () => {
            const expanded = consoleNode.classList.toggle('developer-console-collapsed');
            toggle.textContent = expanded ? 'Show Direct API Tools' : 'Hide Direct API Tools';
        });

        heading.insertAdjacentElement('afterend', toggle);
    });
}

function matchesLearningSearch(row, fields, query) {
    if (!query) {
        return true;
    }

    return fields.some((key) => String(row?.[key] || '').toLowerCase().includes(query));
}

function applyLearningSearch() {
    const query = String(state.learning.search || '').trim().toLowerCase();
    const filteredCourses = state.learning.courses.filter((row) => matchesLearningSearch(row, ['title', 'description', 'course_type'], query));
    const filteredModules = state.learning.modules.filter((row) => matchesLearningSearch(row, ['title', 'body_content', 'difficulty_level'], query));

    renderCourseCatalog(filteredCourses);
    renderStandaloneModules(filteredModules);
}

function setupLearningSearch() {
    const input = document.getElementById('learning-search');
    const clearButton = document.getElementById('btn-clear-learning-search');
    if (!input || !clearButton) {
        return;
    }

    input.addEventListener('input', () => {
        state.learning.search = String(input.value || '');
        if (state.learningSearchTimer) {
            window.clearTimeout(state.learningSearchTimer);
        }

        state.learningSearchTimer = window.setTimeout(() => {
            applyLearningSearch();
        }, 200);
    });

    clearButton.addEventListener('click', () => {
        state.learning.search = '';
        input.value = '';
        applyLearningSearch();
    });
}

function applyFaqSearch() {
    const query = String(state.faq.search || '').trim().toLowerCase();
    if (!query) {
        renderFaq(state.faq.rows || []);
        return;
    }

    const filtered = (state.faq.rows || []).filter((row) => (
        String(row?.question || '').toLowerCase().includes(query)
        || String(row?.answer || '').toLowerCase().includes(query)
    ));

    renderFaq(filtered);
}

function setupFaqSearch() {
    const input = document.getElementById('faq-search');
    const clearButton = document.getElementById('btn-clear-faq-search');
    if (!input || !clearButton) {
        return;
    }

    input.addEventListener('input', () => {
        state.faq.search = String(input.value || '');
        applyFaqSearch();
    });

    clearButton.addEventListener('click', () => {
        state.faq.search = '';
        input.value = '';
        applyFaqSearch();
    });
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
                <button type="button" data-quick="purchase-package" data-package-id="${escapeHtml(row.id)}">Proceed to Paygate</button>
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
                <span class="pill">${escapeHtml(formatLikes(row.likes_count || 0))}</span>
            </div>
            <div class="inline-actions">
                <button type="button" data-quick="enroll-course" data-course-id="${escapeHtml(row.id)}" data-course-type="${escapeHtml(row.course_type)}" data-course-cost="${escapeHtml(row.kodebits_cost)}" data-course-title="${escapeHtml(row.title)}">Enroll</button>
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
                <span class="pill">${escapeHtml(formatLikes(row.likes_count || 0))}</span>
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
                <span class="pill">${escapeHtml(formatLikes(row.likes_count || 0))}</span>
            </div>
            <div class="inline-actions">
                <button type="button" data-quick="prefill-challenge" data-challenge-id="${escapeHtml(row.id)}">Participate</button>
                <button type="button" class="secondary" data-quick="react" data-content-type="challenge" data-content-id="${escapeHtml(row.id)}">Like</button>
            </div>
        </article>
    `).join('');
}

function renderSelectedChallenge(row) {
    const target = document.getElementById('challenge-selected');
    if (!target) {
        return;
    }

    if (!row) {
        target.innerHTML = 'Select a challenge from the catalog to review details and participate.';
        return;
    }

    target.innerHTML = `
        <article class="detail-card">
            <h3>${escapeHtml(row.title)}</h3>
            <p>${escapeHtml(row.prompt_text || 'No prompt text provided.')}</p>
            <div class="pill-row">
                <span class="pill">${escapeHtml(row.difficulty_level || 'difficulty n/a')}</span>
                <span class="pill">${escapeHtml(row.language_scope || 'language n/a')}</span>
                <span class="pill">Entry Fee: ${escapeHtml(formatNumber(row.kodebits_cost || 0))} KB</span>
                ${renderStatusPill(row.status || 'status n/a')}
            </div>
        </article>
    `;
}

function renderChallengeAttemptState(attemptState) {
    const stateNode = document.getElementById('challenge-attempt-state');
    const hintNode = document.getElementById('challenge-workbench-hint');
    const submitButton = document.getElementById('challenge-submit-btn');
    const evaluateButton = document.getElementById('challenge-evaluate-btn');
    const pendingSubmissionField = document.getElementById('challenge-pending-submission-id');

    if (!stateNode || !hintNode || !submitButton || !evaluateButton || !pendingSubmissionField) {
        return;
    }

    if (!attemptState) {
        stateNode.innerHTML = '<span class="pill">Attempts: 0/3</span><span class="pill">Select a challenge</span>';
        hintNode.textContent = 'Select a challenge to start coding.';
        submitButton.disabled = true;
        evaluateButton.disabled = true;
        pendingSubmissionField.value = '';
        return;
    }

    const attemptsUsed = Number(attemptState.attempts_used || 0);
    const maxAttempts = Number(attemptState.max_attempts || 3);
    const attemptsRemaining = Number(attemptState.attempts_remaining || 0);
    const isEvaluated = Boolean(attemptState.is_evaluated);
    const pendingSubmissionId = attemptState.pending_submission_id ? Number(attemptState.pending_submission_id) : null;

    stateNode.innerHTML = `
        <span class="pill">Attempts: ${escapeHtml(attemptsUsed)} / ${escapeHtml(maxAttempts)}</span>
        <span class="pill">Remaining: ${escapeHtml(attemptsRemaining)}</span>
        ${isEvaluated ? renderStatusPill('Evaluated') : renderStatusPill('Awaiting Evaluation')}
    `;

    pendingSubmissionField.value = pendingSubmissionId ? String(pendingSubmissionId) : '';
    submitButton.disabled = !Boolean(attemptState.can_submit);
    evaluateButton.disabled = !Boolean(attemptState.can_evaluate);

    if (isEvaluated) {
        hintNode.textContent = 'Evaluation completed. This challenge is locked for new submissions.';
        return;
    }

    if (pendingSubmissionId) {
        hintNode.textContent = `Submission #${pendingSubmissionId} is ready. Click Evaluate Submission to see results and feedback.`;
        return;
    }

    if (attemptsRemaining <= 0) {
        hintNode.textContent = String(attemptState.lock_reason || 'Submission limit reached (3 attempts).');
        return;
    }

    hintNode.textContent = `You can submit ${attemptsRemaining} more attempt(s). Attempt #3 will auto-evaluate.`;
}

async function syncChallengeAttemptState(challengeId) {
    const id = Number(challengeId || 0);
    if (id <= 0) {
        state.challenges.attemptState = null;
        renderChallengeAttemptState(null);
        return;
    }

    try {
        const result = await apiCall('challenge', 'attempt_state', { challenge_id: id });
        state.challenges.attemptState = result?.data?.state || null;
        renderChallengeAttemptState(state.challenges.attemptState);
    } catch (error) {
        state.challenges.attemptState = null;
        renderChallengeAttemptState(null);
        setStatus('error', escapeHtml(error.message || 'Unable to load challenge attempt state.'));
    }
}

async function selectChallengeForWorkbench(challengeId, shouldScroll = false) {
    const id = Number(challengeId || 0);
    if (id <= 0) {
        return;
    }

    state.challenges.selectedId = id;
    const selected = findChallengeById(id);
    renderSelectedChallenge(selected);

    const workbenchId = document.getElementById('challenge-workbench-id');
    if (workbenchId) {
        workbenchId.value = String(id);
    }

    await syncChallengeAttemptState(id);

    if (shouldScroll) {
        document.getElementById('challenge-workbench-form')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function setupChallengeWorkbench() {
    const form = document.getElementById('challenge-workbench-form');
    const evaluateButton = document.getElementById('challenge-evaluate-btn');
    const sourceField = document.getElementById('challenge-source');
    if (!form || !evaluateButton || !sourceField) {
        return;
    }

    renderChallengeAttemptState(null);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const challengeId = Number(document.getElementById('challenge-workbench-id')?.value || 0);
        if (challengeId <= 0) {
            setStatus('error', 'Pick a challenge from the catalog first.');
            return;
        }

        const attemptState = state.challenges.attemptState;
        if (attemptState && !attemptState.can_submit) {
            setStatus('error', String(attemptState.lock_reason || 'Submission is locked for this challenge.'));
            return;
        }

        const sourceCode = String(sourceField.value || '').trim();
        if (!sourceCode) {
            setStatus('error', 'Source code is required before submitting.');
            return;
        }

        let challenge = findChallengeById(challengeId);
        if (!challenge) {
            const challengeRes = await apiCall('interaction', 'browse_challenges');
            state.challenges.rows = challengeRes.data.rows || [];
            challenge = findChallengeById(challengeId);
        }

        const fee = Number(challenge?.kodebits_cost || 0);
        const balance = Number(state.auth?.user?.kodebits_balance || 0);
        const challengeTitle = String(challenge?.title || `Challenge #${challengeId}`);

        if (fee > balance) {
            setStatus('error', `Insufficient KodeBits. ${challengeTitle} requires ${formatNumber(fee)} KB but your wallet has ${formatNumber(balance)} KB.`);
            return;
        }

        const confirmed = await openConfirmModal(`Submit solution for ${challengeTitle}? Entry fee: ${formatNumber(fee)} KB (charged once for paid challenges).`);
        if (!confirmed) {
            return;
        }

        const payload = {
            challenge_id: challengeId,
            language_name: String(document.getElementById('challenge-language')?.value || 'Python'),
            source_code: sourceCode,
        };

        const result = await runAction('interaction.participate_challenge', () => apiCall('interaction', 'participate_challenge', payload));
        if (!result) {
            return;
        }

        if (result?.data?.attempt_state) {
            state.challenges.attemptState = result.data.attempt_state;
            renderChallengeAttemptState(state.challenges.attemptState);
        } else {
            await syncChallengeAttemptState(challengeId);
        }

        if (result?.data?.auto_evaluated) {
            setStatus('success', `Attempt #${escapeHtml(result.data.attempt_number || 3)} auto-evaluated. Feedback is now available.`);
        } else {
            setStatus('success', `Submission #${escapeHtml(result.data.submission_id)} stored. Click Evaluate Submission when ready.`);
        }

        await loadChallengesHub();
    });

    evaluateButton.addEventListener('click', async () => {
        const attemptState = state.challenges.attemptState;
        const submissionId = Number(document.getElementById('challenge-pending-submission-id')?.value || 0);
        if (!attemptState || !attemptState.can_evaluate || submissionId <= 0) {
            setStatus('error', 'No pending submission available for evaluation. Submit code first.');
            return;
        }

        const confirmed = await openConfirmModal(`Evaluate submission #${submissionId}? This will lock new submissions for this challenge.`);
        if (!confirmed) {
            return;
        }

        const result = await runAction('challenge.evaluate', () => apiCall('challenge', 'evaluate', { submission_id: submissionId }));
        if (!result) {
            return;
        }

        if (result?.data?.attempt_state) {
            state.challenges.attemptState = result.data.attempt_state;
            renderChallengeAttemptState(state.challenges.attemptState);
        }

        await loadChallengesHub();
    });
}

function findChallengeById(challengeId) {
    if (!challengeId) {
        return null;
    }

    const id = Number(challengeId);
    const fromChallengePage = state.challenges.rows.find((item) => Number(item.id) === id);
    if (fromChallengePage) {
        return fromChallengePage;
    }

    return null;
}

function renderModuleChallengeCatalog(rows) {
    const target = document.getElementById('module-challenge-catalog');
    if (!target) {
        return;
    }

    if (!rows || rows.length === 0) {
        target.innerHTML = '<div class="empty-state">No published challenges are available yet.</div>';
        return;
    }

    target.innerHTML = rows.slice(0, 6).map((row) => `
        <article class="catalog-card">
            <div>
                <h3>${escapeHtml(row.title)}</h3>
                <p>Languages: ${escapeHtml(row.language_scope)}</p>
            </div>
            <div class="pill-row">
                <span class="pill">${escapeHtml(row.difficulty_level)}</span>
                ${renderStatusPill(row.status || 'status n/a')}
                <span class="pill">${escapeHtml(formatLikes(row.likes_count || 0))}</span>
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
    renderHomeShortcuts();
    renderLearningSnapshot(learningRes.data.rows);
    renderNotifications(notificationsRes.data.rows);
}

async function loadAccountCenter() {
    const [requestsRes, credentialsRes] = await Promise.all([
        apiCall('auth', 'my_requests'),
        apiCall('auth', 'my_credentials'),
    ]);

    renderTable('request-table', requestsRes.data.rows, 'No contributor or instructor requests yet.');
    renderTable('credential-table', credentialsRes.data.rows, 'No instructor credentials submitted yet.');
    renderRequestHistoryCards(requestsRes.data.rows || []);
    renderCredentialHistoryCards(credentialsRes.data.rows || []);
}

async function loadLearningHub() {
    const [coursesRes, modulesRes] = await Promise.all([
        apiCall('interaction', 'browse_courses'),
        apiCall('interaction', 'standalone_modules'),
    ]);

    state.learning.courses = coursesRes.data.rows || [];
    state.learning.modules = modulesRes.data.rows || [];
    applyLearningSearch();
    renderCourseModules([], null);
}

async function loadLeaderboardHub() {
    const [leaderboardRes, weeklyRes] = await Promise.all([
        apiCall('interaction', 'view_leaderboard'),
        apiCall('gamification', 'weekly_leaderboard'),
    ]);

    renderTable('leaderboard-table', leaderboardRes.data.rows, 'No leaderboard data available.');
    renderGlobalLeaderboardCards(leaderboardRes.data.rows || []);
    renderTable('weekly-leaderboard-table', weeklyRes.data.rows, 'No weekly leaderboard data available.');
    renderWeeklyLeaderboardCards(weeklyRes.data.rows || []);
}

async function loadFaqHub() {
    const faqRes = await apiCall('interaction', 'faq_list');
    state.faq.rows = faqRes.data.rows || [];
    applyFaqSearch();
}

async function loadChallengesHub() {
    const challengeRes = await apiCall('interaction', 'browse_challenges');

    state.challenges.rows = challengeRes.data.rows || [];
    renderChallengeCatalog(state.challenges.rows);

    const feedbackTarget = document.getElementById('feedback-table');
    if (feedbackTarget) {
        const feedbackRes = await apiCall('challenge', 'list_feedback');
        renderTable('feedback-table', feedbackRes.data.rows, 'No submissions yet.');
    }

    const params = new URLSearchParams(window.location.search);
    const challengeId = Number(params.get('challenge_id') || 0);
    const selected = challengeId > 0 ? findChallengeById(challengeId) : (findChallengeById(state.challenges.selectedId) || state.challenges.rows[0] || null);
    if (selected?.id) {
        await selectChallengeForWorkbench(Number(selected.id));
    } else {
        renderSelectedChallenge(null);
        renderChallengeAttemptState(null);
    }
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

    const isAdmin = roleAllowed('administrator');
    const ownUserId = Number(state.auth?.user?.id || 0);
    const courses = (coursesRes.data.rows || []).filter((row) => isAdmin || Number(row.created_by || 0) === ownUserId);
    const modules = (modulesRes.data.rows || []).filter((row) => isAdmin || Number(row.created_by || 0) === ownUserId);
    const challenges = (challengesRes.data.rows || []).filter((row) => isAdmin || Number(row.created_by || 0) === ownUserId);

    renderTable('content-courses-table', courses, 'No courses available.');
    renderTable('content-modules-table', modules, 'No modules available.');
    renderTable('creator-challenges-table', challenges, 'No challenges available.');
    renderCreatorCourseCards(courses, modules, challenges);
    renderCreatorModuleCards(modules);
    renderCreatorChallengeCards(challenges);

    const params = new URLSearchParams(window.location.search);
    const presetId = Number(params.get('preset_id') || 0);
    if (presetId > 0) {
        document.querySelectorAll('form.api-form[data-module="gamification"][data-action="create_activity"] input[name="preset_id"]').forEach((field) => {
            field.value = String(presetId);
        });
    }

    const page = document.body.getAttribute('data-page');
    const courseId = Number(params.get('course_id') || 0);
    const moduleId = Number(params.get('module_id') || 0);
    const challengeId = Number(params.get('challenge_id') || 0);

    if (page === 'creator-courses' && courseId > 0) {
        const row = courses.find((item) => Number(item.id) === courseId);
        if (row) {
            prefillApiForm('content', 'edit_course', {
                course_id: row.id,
                title: row.title,
                description: row.description,
                status: row.status,
            });
        }
    }

    if (page === 'creator-modules' && moduleId > 0) {
        const row = modules.find((item) => Number(item.id) === moduleId);
        if (row) {
            prefillApiForm('content', 'edit_module', {
                module_id: row.id,
                title: row.title,
                body_content: row.body_content || '',
                module_type: row.module_type || '',
                difficulty_level: row.difficulty_level || '',
                kodebits_cost: row.kodebits_cost,
                status: row.status,
            });
        }
    }

    if (page === 'creator-challenges' && challengeId > 0) {
        const row = challenges.find((item) => Number(item.id) === challengeId);
        if (row) {
            prefillApiForm('challenge', 'edit', {
                challenge_id: row.id,
                title: row.title,
                prompt_text: row.prompt_text,
                difficulty_level: row.difficulty_level,
                language_scope: row.language_scope,
                time_limit_ms: row.time_limit_ms,
                memory_limit_kb: row.memory_limit_kb,
                kodebits_cost: row.kodebits_cost,
                status: row.status,
            });
        }
    }
}

async function loadGamification() {
    const [presetsRes, leaderboardRes, weeklyRes] = await Promise.all([
        apiCall('gamification', 'list_presets'),
        apiCall('gamification', 'leaderboard'),
        apiCall('gamification', 'list_weekly'),
    ]);

    renderTable('preset-table', presetsRes.data.rows, 'No presets available.');
    renderPresetCards(presetsRes.data.rows || []);
    renderTable('gamification-leaderboard-table', leaderboardRes.data.rows, 'No ranking data available.');
    renderTable('weekly-table', weeklyRes.data.rows, 'No weekly challenge queue available.');
    renderWeeklyCards(weeklyRes.data.rows || []);

    const params = new URLSearchParams(window.location.search);
    const challengeId = Number(params.get('challenge_id') || 0);
    if (challengeId > 0) {
        prefillField('form[data-module="gamification"][data-action="create_weekly"] input[name="challenge_id"]', challengeId);
    }
}

async function loadFinance() {
    const earningsPromise = roleAllowed('contributor,instructor,administrator')
        ? apiCall('finance', 'earnings')
        : Promise.resolve({ data: { rows: [] } });
    const payoutPromise = roleAllowed('contributor,instructor,administrator')
        ? apiCall('finance', 'list_payout_requests')
        : Promise.resolve({ data: { rows: [] } });

    const [earningsRes, payoutRes] = await Promise.all([earningsPromise, payoutPromise]);
    renderTable('earnings-table', earningsRes.data.rows, 'No creator earnings available for this role.');
    renderEarningsCards(earningsRes.data.rows || []);
    renderTable('payout-requests-table', payoutRes.data.rows, 'No payout requests available for this role.');
    renderPayoutRequestCards(payoutRes.data.rows || []);
}

async function loadTopup() {
    const [packagesRes, historyRes] = await Promise.all([
        apiCall('finance', 'packages'),
        apiCall('finance', 'transaction_history'),
    ]);

    renderPackageShowcase(packagesRes.data.rows);
    renderTable('finance-packages-table', packagesRes.data.rows, 'No packages available.');
    renderTable('topup-history-table', historyRes.data.rows, 'No transactions yet.');
    renderTopupHistoryCards(historyRes.data.rows || []);
}

async function loadGovernance() {
    if (!roleAllowed('moderator,administrator')) {
        return;
    }

    const requests = [
        apiCall('admin', 'list_users'),
        apiCall('challenge', 'list'),
        apiCall('admin', 'contributor_requests'),
        apiCall('admin', 'list_credentials'),
        apiCall('admin', 'list_reports'),
    ];

    if (roleAllowed('administrator')) {
        requests.push(apiCall('admin', 'system_reports'));
    } else {
        requests.push(Promise.resolve({ data: { report: {} } }));
    }

    const [usersRes, challengeRes, requestsRes, credentialsRes, reportsRes, systemRes] = await Promise.all(requests);
    const challengeQueue = (challengeRes.data.rows || []).filter((row) => {
        const status = String(row.status || '').toLowerCase();
        return !['approved', 'published', 'archived', 'deleted'].includes(status);
    });

    renderTable('users-table', usersRes.data.rows, 'No user accounts found.');
    renderUserGovernanceCards(usersRes.data.rows || []);
    renderTable('challenge-review-table', challengeQueue, 'No challenges are waiting for review.');
    renderChallengeReviewCards(challengeQueue);
    renderTable('requests-review-table', requestsRes.data.rows, 'No contributor requests found.');
    renderTable('credentials-review-table', credentialsRes.data.rows, 'No instructor credentials found.');
    renderContributorRequestCards(requestsRes.data.rows || []);
    renderCredentialReviewCards(credentialsRes.data.rows || []);
    renderTable('reports-table', reportsRes.data.rows, 'No content reports found.');
    renderReportGovernanceCards(reportsRes.data.rows || []);

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

        if (page === 'challenges') {
            await loadChallengesHub();
        }

        if (page === 'challenge-play') {
            await loadChallengesHub();
        }

        if (page === 'leaderboard') {
            await loadLeaderboardHub();
        }

        if (page === 'faq') {
            await loadFaqHub();
        }

        if (page === 'creator') {
            await loadCreatorWorkspace();
        }

        if (page === 'creator-courses' || page === 'creator-modules' || page === 'creator-challenges') {
            await loadCreatorWorkspace();
        }

        if (page === 'rewards') {
            await loadGamification();
        }

        if (page === 'finance') {
            await loadFinance();
        }

        if (page === 'topup') {
            await loadTopup();
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
    const row = decodeRowPayload(button.getAttribute('data-row'));
    const confirmMessage = button.getAttribute('data-confirm');

    if (confirmMessage) {
        const confirmed = await openConfirmModal(confirmMessage);
        if (!confirmed) {
            return;
        }
    }

    if (action === 'enroll-course') {
        const courseId = Number(button.getAttribute('data-course-id'));
        const courseType = String(button.getAttribute('data-course-type') || 'free').toLowerCase();
        const courseCost = Number(button.getAttribute('data-course-cost') || 0);
        const courseTitle = String(button.getAttribute('data-course-title') || 'course');
        const currentBalance = Number(state.auth?.user?.kodebits_balance || 0);

        prefillField('#enroll-form input[name="course_id"]', courseId);

        if (courseType === 'premium' && courseCost > 0) {
            const confirmed = await openConfirmModal(`Premium enrollment: ${courseTitle} costs ${formatNumber(courseCost)} KB. Current wallet: ${formatNumber(currentBalance)} KB. Continue?`);
            if (!confirmed) {
                return;
            }
        }

        const result = await runAction('interaction.enroll_course', () => apiCall('interaction', 'enroll_course', { course_id: courseId }));
        if (result) {
            await redirectToFirstCourseModule(courseId);
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
        redirectToModulePage(courseId, moduleId);
        return;
    }

    if (action === 'access-standalone-module') {
        const moduleId = Number(button.getAttribute('data-module-id'));
        prefillField('#standalone-access-form input[name="module_id"]', moduleId);
        redirectToModulePage(null, moduleId, true);
        return;
    }

    if (action === 'prefill-challenge') {
        const challengeId = Number(button.getAttribute('data-challenge-id'));
        window.location.href = `challenge-play.php?challenge_id=${encodeURIComponent(String(challengeId))}`;
        return;
    }

    if (action === 'jump-to-learning-challenges') {
        window.location.href = 'challenges.php';
        return;
    }

    if (action === 'open-first-challenge') {
        window.location.href = 'challenges.php';
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
        prefillField('#paygate-package-id', packageId);

        const packageCard = button.closest('.package-card');
        const labelField = document.getElementById('paygate-package-label');
        const title = packageCard?.querySelector('h3')?.textContent || `Package #${packageId}`;
        if (labelField) {
            labelField.value = title;
        }

        document.getElementById('paygate-form')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        setStatus('info', `Package ${escapeHtml(packageId)} selected. Complete paygate details to continue.`);
        return;
    }

    if (action === 'open-create-course') {
        markCreatorSubpageAccess('creator-courses', 'create');
        window.location.href = 'creator-courses.php?intent=create';
        return;
    }

    if (action === 'open-create-module') {
        markCreatorSubpageAccess('creator-modules', 'create');
        window.location.href = 'creator-modules.php?intent=create';
        return;
    }

    if (action === 'open-create-challenge') {
        markCreatorSubpageAccess('creator-challenges', 'create');
        window.location.href = 'creator-challenges.php?intent=create';
        return;
    }

    if (action === 'row-course-edit' && row) {
        markCreatorSubpageAccess('creator-courses', 'edit');
        window.location.href = `creator-courses.php?course_id=${encodeURIComponent(String(row.id))}`;
        return;
    }

    if (action === 'row-course-archive' && row) {
        const result = await runAction('content.archive_course', () => apiCall('content', 'archive_course', { course_id: Number(row.id) }));
        if (result) {
            await refreshCurrentPage();
        }
        return;
    }

    if (action === 'row-course-delete' && row) {
        const result = await runAction('content.delete_course', () => apiCall('content', 'delete_course', { course_id: Number(row.id) }));
        if (result) {
            await refreshCurrentPage();
        }
        return;
    }

    if (action === 'row-module-edit' && row) {
        markCreatorSubpageAccess('creator-modules', 'edit');
        window.location.href = `creator-modules.php?module_id=${encodeURIComponent(String(row.id))}`;
        return;
    }

    if (action === 'row-module-assign' && row) {
        markCreatorSubpageAccess('creator-modules', 'edit');
        window.location.href = `creator-modules.php?module_id=${encodeURIComponent(String(row.id))}`;
        return;
    }

    if (action === 'row-module-view' && row) {
        setStatus('info', `Use Edit to open module ${escapeHtml(row.id)} in the implementation page.`);
        return;
    }

    if (action === 'row-module-archive' && row) {
        const result = await runAction('content.archive_module', () => apiCall('content', 'archive_module', { module_id: Number(row.id) }));
        if (result) {
            await refreshCurrentPage();
        }
        return;
    }

    if (action === 'row-module-delete' && row) {
        const result = await runAction('content.delete_module', () => apiCall('content', 'delete_module', { module_id: Number(row.id) }));
        if (result) {
            await refreshCurrentPage();
        }
        return;
    }

    if (action === 'row-challenge-edit' && row) {
        markCreatorSubpageAccess('creator-challenges', 'edit');
        window.location.href = `creator-challenges.php?challenge_id=${encodeURIComponent(String(row.id))}`;
        return;
    }

    if (action === 'row-challenge-view' && row) {
        setStatus('info', `Use Edit to open challenge ${escapeHtml(row.id)} in the implementation page.`);
        return;
    }

    if (action === 'row-challenge-archive' && row) {
        const result = await runAction('challenge.archive', () => apiCall('challenge', 'archive', { challenge_id: Number(row.id) }));
        if (result) {
            await refreshCurrentPage();
        }
        return;
    }

    if (action === 'row-challenge-delete' && row) {
        const result = await runAction('challenge.delete', () => apiCall('challenge', 'delete', { challenge_id: Number(row.id) }));
        if (result) {
            await refreshCurrentPage();
        }
        return;
    }

    if (action === 'row-challenge-submit' && row) {
        window.location.href = `challenge-play.php?challenge_id=${encodeURIComponent(String(row.id))}`;
        return;
    }

    if (action === 'row-challenge-open-play' && row) {
        window.location.href = `challenge-play.php?challenge_id=${encodeURIComponent(String(row.id))}`;
        return;
    }

    if (action === 'row-challenge-configure-weekly' && row) {
        if (document.body.getAttribute('data-page') === 'rewards') {
            prefillField('form[data-module="gamification"][data-action="create_weekly"] input[name="challenge_id"]', Number(row.id));
            setStatus('info', `Challenge ${escapeHtml(row.id)} loaded for weekly configuration.`);
            return;
        }

        window.location.href = `rewards.php?challenge_id=${encodeURIComponent(String(row.id))}`;
        return;
    }

    if (action === 'row-challenge-approve' && row) {
        const result = await runAction('challenge.review approve', () => apiCall('challenge', 'review', {
            challenge_id: Number(row.id),
            review_status: 'Approved',
            review_notes: 'Approved from governance review queue.',
        }));
        if (result) {
            await refreshCurrentPage();
        }
        return;
    }

    if (action === 'row-challenge-reject' && row) {
        const result = await runAction('challenge.review reject', () => apiCall('challenge', 'review', {
            challenge_id: Number(row.id),
            review_status: 'Rejected',
            review_notes: 'Rejected from governance review queue.',
        }));
        if (result) {
            await refreshCurrentPage();
        }
        return;
    }

    if (action === 'row-preset-use' && row) {
        window.location.href = `creator.php?preset_id=${encodeURIComponent(String(row.id))}`;
        return;
    }

    if (action === 'row-weekly-evaluate' && row) {
        const result = await runAction('gamification.evaluate_weekly', () => apiCall('gamification', 'evaluate_weekly', {
            weekly_challenge_id: Number(row.id),
        }));
        if (result) {
            await refreshCurrentPage();
        }
        return;
    }

    if (action === 'row-weekly-publish' && row) {
        const result = await runAction('gamification.publish_weekly', () => apiCall('gamification', 'publish_weekly', {
            weekly_challenge_id: Number(row.id),
        }));
        if (result) {
            await refreshCurrentPage();
        }
        return;
    }

    if (action === 'row-earning-request-payout' && row) {
        prefillApiForm('finance', 'request_payout', {
            creator_earning_id: row.id,
            request_amount_php: Number(row.creator_share_php || 0),
            payout_channel: 'GCash',
        });
        setStatus('info', `Earning ${escapeHtml(row.id)} loaded into payout request form.`);
        return;
    }

    if (action === 'row-leaderboard-grant' && row) {
        prefillApiForm('gamification', 'grant_reward', {
            user_id: row.id,
            xp_awarded: 10,
            kodebits_awarded: 2,
            reference_type: 'leaderboard_manual',
            reference_id: `leaderboard-${row.id}`,
        });
        setStatus('info', `User ${escapeHtml(row.id)} loaded into grant reward form.`);
        return;
    }

    if (action === 'row-request-approve' && row) {
        const result = await runAction('admin.update_request approve', () => apiCall('admin', 'update_request', {
            request_id: Number(row.id),
            status: 'Approved',
        }));
        if (result) {
            await refreshCurrentPage();
        }
        return;
    }

    if (action === 'row-request-reject' && row) {
        const result = await runAction('admin.update_request reject', () => apiCall('admin', 'update_request', {
            request_id: Number(row.id),
            status: 'Rejected',
        }));
        if (result) {
            await refreshCurrentPage();
        }
        return;
    }

    if (action === 'row-credential-accept' && row) {
        const result = await runAction('admin.verify_instructor_credentials accept', () => apiCall('admin', 'verify_instructor_credentials', {
            credential_id: Number(row.id),
            verification_status: 'Accepted',
        }));
        if (result) {
            await refreshCurrentPage();
        }
        return;
    }

    if (action === 'row-credential-reject' && row) {
        const result = await runAction('admin.verify_instructor_credentials reject', () => apiCall('admin', 'verify_instructor_credentials', {
            credential_id: Number(row.id),
            verification_status: 'Rejected',
        }));
        if (result) {
            await refreshCurrentPage();
        }
        return;
    }

    if (action === 'row-user-suspend' && row) {
        const result = await runAction('admin.suspend_user', () => apiCall('admin', 'suspend_user', {
            user_id: Number(row.id),
            notes: 'Suspended from table action.',
        }));
        if (result) {
            await refreshCurrentPage();
        }
        return;
    }

    if (action === 'row-user-reinstate' && row) {
        const result = await runAction('admin.reinstate_user', () => apiCall('admin', 'reinstate_user', {
            user_id: Number(row.id),
        }));
        if (result) {
            await refreshCurrentPage();
        }
        return;
    }

    if (action === 'row-user-fill-update' && row) {
        prefillApiForm('admin', 'update_user_account', {
            user_id: row.id,
            full_name: row.full_name,
            primary_role: row.primary_role,
            status: row.status,
        });
        setStatus('info', `User ${escapeHtml(row.id)} loaded into update form.`);
        return;
    }

    if (action === 'row-report-archive' && row) {
        const result = await runAction('admin.moderate_content archive', () => apiCall('admin', 'moderate_content', {
            report_id: Number(row.id),
            target_type: row.content_type,
            target_id: Number(row.content_id),
            action_type: 'archive',
            notes: 'Archived using row action.',
        }));
        if (result) {
            await refreshCurrentPage();
        }
        return;
    }

    if (action === 'row-report-reject' && row) {
        const result = await runAction('admin.moderate_content reject', () => apiCall('admin', 'moderate_content', {
            report_id: Number(row.id),
            target_type: row.content_type,
            target_id: Number(row.content_id),
            action_type: 'reject',
            notes: 'Rejected using row action.',
        }));
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
                redirectToModulePage(Number(payload.course_id || 0), Number(payload.module_id || 0));
                return;
            }

            if (module === 'interaction' && action === 'access_standalone_module' && result?.data?.row) {
                redirectToModulePage(null, Number(payload.module_id || 0), true);
                return;
            }

            if (module === 'interaction' && action === 'enroll_course') {
                await redirectToFirstCourseModule(Number(payload.course_id || 0));
                return;
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
    setupDeveloperConsoles();
    setupButtons();
    setupLearningSearch();
    setupFaqSearch();
    setupForms();
    setupChallengeWorkbench();
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

    if (!enforceCreatorSubpageIntent()) {
        return;
    }

    renderProfile(me.data.user);
    renderRolePermissions(me.data.user.role);
    applyRoleVisibility();

    await refreshCurrentPage();
    await loadModulePage();
}

bootstrap();
