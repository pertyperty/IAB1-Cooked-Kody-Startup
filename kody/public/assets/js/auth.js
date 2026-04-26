const API_BASE = '../api/index.php';
const MAILBOX_KEY = 'kodyMailbox';
const SEED_KEY = 'kodySeedLogin';

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

function setStatus(type, message) {
    const status = document.getElementById('auth-status');
    if (!status) {
        return;
    }

    status.className = `status-banner ${type}`;
    status.innerHTML = message;
}

function authLog(title, payload) {
    const box = document.getElementById('auth-response');
    if (!box) {
        return;
    }

    box.textContent = JSON.stringify({ title, payload, at: new Date().toISOString() }, null, 2);
}

function readMailbox() {
    try {
        return JSON.parse(sessionStorage.getItem(MAILBOX_KEY) || '{}');
    } catch (error) {
        console.error(error);
        return {};
    }
}

function writeMailbox(update) {
    const current = readMailbox();
    sessionStorage.setItem(MAILBOX_KEY, JSON.stringify({ ...current, ...update }));
}

function renderMailbox() {
    const board = document.getElementById('token-board');
    if (!board) {
        return;
    }

    const mailbox = readMailbox();
    const page = document.body.getAttribute('data-page');

    if (page === 'register' || page === 'verify') {
        if (mailbox.verification_token) {
            board.innerHTML = `
                <div class="token-card">
                    <strong>Verification token</strong>
                    <code>${escapeHtml(mailbox.verification_token)}</code>
                    <p>Use this token on the verify page to activate the account.</p>
                </div>
            `;
            return;
        }
    }

    if (page === 'recover') {
        if (mailbox.recovery_token) {
            board.innerHTML = `
                <div class="token-card">
                    <strong>Recovery token</strong>
                    <code>${escapeHtml(mailbox.recovery_token)}</code>
                    <p>Use this token in the reset form on this page.</p>
                </div>
            `;
            return;
        }
    }

    board.textContent = 'Waiting for the next generated token...';
}

function formatDuration(seconds) {
    const total = Number(seconds || 0);
    if (total <= 0) {
        return 'less than a minute';
    }

    const hours = Math.floor(total / 3600);
    const minutes = Math.ceil((total % 3600) / 60);
    if (hours > 0) {
        return `${hours} hour${hours === 1 ? '' : 's'}${minutes > 0 ? ` ${minutes} minute${minutes === 1 ? '' : 's'}` : ''}`;
    }

    return `${minutes} minute${minutes === 1 ? '' : 's'}`;
}

function getFormData(form) {
    const payload = {};
    new FormData(form).forEach((value, key) => {
        payload[key] = value;
    });
    return payload;
}

function toggleInstructorFields() {
    const roleSelect = document.getElementById('register-role-select');
    const container = document.getElementById('instructor-fields');
    if (!roleSelect || !container) {
        return;
    }

    const isInstructor = roleSelect.value === 'instructor';
    container.classList.toggle('hidden-block', !isInstructor);

    container.querySelectorAll('input, textarea').forEach((field) => {
        field.required = isInstructor;
    });
}

async function apiCall(module, action, payload) {
    const response = await fetch(`${API_BASE}?module=${encodeURIComponent(module)}&action=${encodeURIComponent(action)}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload || {}),
    });

    const json = await response.json();
    if (!response.ok || !json.success) {
        throw createApiError(json, 'Authentication request failed.');
    }

    return json;
}

async function runAuthAction(label, callback) {
    try {
        const result = await callback();
        authLog(label, result);
        return result;
    } catch (error) {
        authLog(`${label} (error)`, { error: error.message, details: error.details || {} });

        if (error.details?.seconds_remaining) {
            setStatus('error', `${escapeHtml(error.message)} Try again in about <strong>${escapeHtml(formatDuration(error.details.seconds_remaining))}</strong>.`);
        } else {
            setStatus('error', escapeHtml(error.message));
        }

        return null;
    }
}

function goToLoginWithSeed(email, password) {
    sessionStorage.setItem(SEED_KEY, JSON.stringify({ email, password }));
    window.location.href = 'login.php';
}

function applyStoredSeed() {
    const loginForm = document.getElementById('login-form');
    if (!loginForm) {
        return;
    }

    try {
        const seed = JSON.parse(sessionStorage.getItem(SEED_KEY) || '{}');
        if (!seed.email || !seed.password) {
            return;
        }

        loginForm.querySelector('input[name="email"]').value = seed.email;
        loginForm.querySelector('input[name="password"]').value = seed.password;
        setStatus('info', `Seed account loaded for <strong>${escapeHtml(seed.email)}</strong>.`);
        sessionStorage.removeItem(SEED_KEY);
    } catch (error) {
        console.error(error);
    }
}

function setupSeedButtons() {
    document.querySelectorAll('[data-seed-email]').forEach((button) => {
        button.addEventListener('click', () => {
            const email = button.getAttribute('data-seed-email');
            const password = button.getAttribute('data-seed-password');

            if (document.getElementById('login-form')) {
                document.querySelector('#login-form input[name="email"]').value = email;
                document.querySelector('#login-form input[name="password"]').value = password;
                setStatus('info', `Seed account loaded for <strong>${escapeHtml(email)}</strong>.`);
            } else {
                goToLoginWithSeed(email, password);
            }
        });
    });
}

function setupLogin() {
    const form = document.getElementById('login-form');
    if (!form) {
        return;
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const result = await runAuthAction('Login', () => apiCall('auth', 'login', getFormData(form)));
        if (!result?.data?.session_token || !result?.data?.user) {
            return;
        }

        localStorage.setItem('kodyAuth', JSON.stringify({
            session_token: result.data.session_token,
            user: result.data.user,
        }));

        setStatus('success', 'Login successful. Redirecting to homepage...');
        window.location.href = 'home.php';
    });
}

function setupRegister() {
    const form = document.getElementById('register-form');
    if (!form) {
        return;
    }

    const roleSelect = document.getElementById('register-role-select');
    if (roleSelect) {
        roleSelect.addEventListener('change', toggleInstructorFields);
        toggleInstructorFields();
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const payload = getFormData(form);
        const result = await runAuthAction('Register', () => apiCall('auth', 'register', payload));
        if (!result) {
            return;
        }

        writeMailbox({
            verification_token: result?.data?.verification_token || null,
            registered_email: payload.email,
        });
        renderMailbox();
        setStatus('success', 'Registration completed. Redirecting to email verification...');
        window.location.href = 'verify.php';
    });
}

function setupVerify() {
    const form = document.getElementById('verify-form');
    if (!form) {
        return;
    }

    const mailbox = readMailbox();
    const tokenField = form.querySelector('input[name="token"]');
    if (tokenField && mailbox.verification_token && !tokenField.value) {
        tokenField.value = mailbox.verification_token;
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const result = await runAuthAction('Verify Email', () => apiCall('auth', 'verify_email', getFormData(form)));
        if (!result) {
            return;
        }

        writeMailbox({ verification_token: null });
        renderMailbox();
        setStatus('success', 'Email verified. Redirecting to login...');
        window.location.href = 'login.php';
    });
}

function setupRecovery() {
    const recoverForm = document.getElementById('recover-form');
    const resetForm = document.getElementById('reset-form');

    if (recoverForm) {
        recoverForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const result = await runAuthAction('Request Recovery', () => apiCall('auth', 'request_recovery', getFormData(recoverForm)));
            if (!result) {
                return;
            }

            writeMailbox({ recovery_token: result?.data?.recovery_token || null });
            renderMailbox();

            if (result?.data?.recovery_token && resetForm) {
                resetForm.querySelector('input[name="token"]').value = result.data.recovery_token;
            }

            setStatus('success', 'Recovery request processed. Continue with the reset form.');
        });
    }

    if (resetForm) {
        const mailbox = readMailbox();
        const tokenField = resetForm.querySelector('input[name="token"]');
        if (tokenField && mailbox.recovery_token && !tokenField.value) {
            tokenField.value = mailbox.recovery_token;
        }

        resetForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const result = await runAuthAction('Reset Password', () => apiCall('auth', 'reset_recovery', getFormData(resetForm)));
            if (!result) {
                return;
            }

            writeMailbox({ recovery_token: null });
            renderMailbox();
            setStatus('success', 'Password reset complete. Redirecting to login...');
            window.location.href = 'login.php';
        });
    }
}

function redirectIfAuthenticated() {
    const page = document.body.getAttribute('data-page');
    if (!['login', 'register', 'recover', 'verify'].includes(page)) {
        return;
    }

    if (readAuth()) {
        window.location.href = 'home.php';
    }
}

redirectIfAuthenticated();
setupSeedButtons();
applyStoredSeed();
renderMailbox();
setupLogin();
setupRegister();
setupVerify();
setupRecovery();
