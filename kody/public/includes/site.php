<?php
declare(strict_types=1);

function renderHead(string $title, string $bodyClass, array $extraAttributes = []): void
{
    $attrs = '';
    foreach ($extraAttributes as $key => $value) {
        $attrs .= ' ' . htmlspecialchars((string) $key, ENT_QUOTES) . '="' . htmlspecialchars((string) $value, ENT_QUOTES) . '"';
    }

    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($title, ENT_QUOTES) . '</title>';
    echo '<link rel="stylesheet" href="assets/css/styles.css">';
    echo '</head>';
    echo '<body class="' . htmlspecialchars($bodyClass, ENT_QUOTES) . '"' . $attrs . '>';
    echo '<div class="background-orb orb-a"></div>';
    echo '<div class="background-orb orb-b"></div>';
}

function renderFooter(array $scripts = []): void
{
    foreach ($scripts as $script) {
        echo '<script src="' . htmlspecialchars($script, ENT_QUOTES) . '"></script>';
    }

    echo '</body>';
    echo '</html>';
}

function renderPublicNav(string $active): void
{
    $items = [
        'index.php' => ['id' => 'home', 'label' => 'Home'],
        'login.php' => ['id' => 'login', 'label' => 'Login'],
        'register.php' => ['id' => 'register', 'label' => 'Register'],
        'verify.php' => ['id' => 'verify', 'label' => 'Verify Email'],
        'recover.php' => ['id' => 'recover', 'label' => 'Recover Account'],
    ];

    echo '<div class="shell topbar">';
    echo '<a class="brandmark" href="index.php">Kody</a>';
    echo '<nav class="site-nav" aria-label="Public navigation">';

    foreach ($items as $href => $item) {
        $class = $item['id'] === $active ? 'nav-link active' : 'nav-link';
        echo '<a class="' . $class . '" href="' . htmlspecialchars($href, ENT_QUOTES) . '">' . htmlspecialchars($item['label'], ENT_QUOTES) . '</a>';
    }

    echo '</nav>';
    echo '</div>';
}

function renderWorkspaceNav(string $active, string $title, string $subtitle): void
{
    $items = [
        'home.php' => ['id' => 'home', 'label' => 'Homepage', 'roles' => 'learner,contributor,instructor,moderator,administrator'],
        'learn.php' => ['id' => 'learn', 'label' => 'Learning', 'roles' => 'learner,contributor,instructor,moderator,administrator'],
        'profile.php' => ['id' => 'profile', 'label' => 'Profile', 'roles' => 'learner,contributor,instructor,moderator,administrator'],
        'creator.php' => ['id' => 'creator', 'label' => 'Creator', 'roles' => 'contributor,instructor,administrator'],
        'rewards.php' => ['id' => 'rewards', 'label' => 'Rewards', 'roles' => 'learner,contributor,instructor,moderator,administrator'],
        'finance.php' => ['id' => 'finance', 'label' => 'Finance', 'roles' => 'learner,contributor,instructor,moderator,administrator'],
        'governance.php' => ['id' => 'governance', 'label' => 'Governance', 'roles' => 'moderator,administrator'],
    ];

    echo '<div class="shell topbar workspace-topbar">';
    echo '<a class="brandmark" href="home.php">Kody</a>';
    echo '<nav class="site-nav" aria-label="Workspace navigation">';

    foreach ($items as $href => $item) {
        $class = $item['id'] === $active ? 'nav-link active' : 'nav-link';
        echo '<a class="' . $class . '" href="' . htmlspecialchars($href, ENT_QUOTES) . '" data-roles="' . htmlspecialchars($item['roles'], ENT_QUOTES) . '">' . htmlspecialchars($item['label'], ENT_QUOTES) . '</a>';
    }

    echo '</nav>';
    echo '<div class="topbar-right">';
    echo '<div class="wallet-chip" id="nav-wallet-chip" aria-live="polite">';
    echo '<span class="wallet-label">Wallet</span>';
    echo '<strong id="nav-wallet-balance">0 KB</strong>';
    echo '</div>';
    echo '<div class="inline-actions compact-actions">';
    echo '<button type="button" class="secondary" id="btn-refresh">Refresh</button>';
    echo '<button type="button" class="secondary" id="btn-me">My Account</button>';
    echo '<button type="button" class="danger" id="btn-logout">Logout</button>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    echo '<header class="shell workspace-hero compact-hero">';
    echo '<div>';
    echo '<p class="eyebrow">Kody Learning Platform</p>';
    echo '<h1>' . htmlspecialchars($title, ENT_QUOTES) . '</h1>';
    echo '<p class="hero-copy">' . htmlspecialchars($subtitle, ENT_QUOTES) . '</p>';
    echo '</div>';
    echo '<div class="identity-card" id="current-user-box">Checking active session...</div>';
    echo '</header>';
}

function renderWorkspaceIntro(string $eyebrow, string $heading, string $copy): void
{
    echo '<section class="workspace-section shell">';
    echo '<div class="surface-header">';
    echo '<div>';
    echo '<p class="eyebrow">' . htmlspecialchars($eyebrow, ENT_QUOTES) . '</p>';
    echo '<h2>' . htmlspecialchars($heading, ENT_QUOTES) . '</h2>';
    echo '</div>';
    echo '<p class="section-copy">' . htmlspecialchars($copy, ENT_QUOTES) . '</p>';
    echo '</div>';
    echo '<div id="status-banner" class="status-banner info">Loading page data...</div>';
    echo '</section>';
}

function renderWorkspaceFooter(): void
{
    echo '<section class="shell response-console developer-console">';
    echo '<h2>For Testing: Live API Response</h2>';
    echo '<pre id="response-box">Waiting for workspace actions...</pre>';
    echo '</section>';

    echo '<div class="modal-backdrop hidden" id="confirm-modal">';
    echo '<div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="confirm-title">';
    echo '<p class="eyebrow">Critical Action</p>';
    echo '<h3 id="confirm-title">Confirm action</h3>';
    echo '<p id="confirm-message">Are you sure you want to continue?</p>';
    echo '<div class="inline-actions">';
    echo '<button type="button" class="secondary" id="confirm-cancel">Cancel</button>';
    echo '<button type="button" class="danger" id="confirm-accept">Continue</button>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}
