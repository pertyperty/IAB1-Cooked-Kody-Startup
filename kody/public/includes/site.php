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
        'challenges.php' => ['id' => 'challenges', 'label' => 'Challenges', 'roles' => 'learner,contributor,instructor,moderator,administrator'],
        'leaderboard.php' => ['id' => 'leaderboard', 'label' => 'Leaderboard', 'roles' => 'learner,contributor,instructor,moderator,administrator'],
        'faq.php' => ['id' => 'faq', 'label' => 'FAQ', 'roles' => 'learner,contributor,instructor,moderator,administrator'],
        'profile.php' => ['id' => 'profile', 'label' => 'Profile', 'roles' => 'learner,contributor,instructor,moderator,administrator'],
        'creator.php' => ['id' => 'creator', 'label' => 'Creator', 'roles' => 'contributor,instructor,administrator'],
        'rewards.php' => ['id' => 'rewards', 'label' => 'Rewards', 'roles' => 'learner,contributor,instructor,moderator,administrator'],
        'topup.php' => ['id' => 'topup', 'label' => 'Top Up', 'roles' => 'learner,contributor,instructor,moderator,administrator'],
        'finance.php' => ['id' => 'finance', 'label' => 'Earnings', 'roles' => 'learner,contributor,instructor,moderator,administrator'],
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

function renderInterfaceRail(string $eyebrow, string $heading, string $copy, array $items): void
{
    echo '<section class="shell interface-rail">';
    echo '<div class="surface interface-rail-surface">';
    echo '<div class="surface-header">';
    echo '<div>';
    echo '<p class="eyebrow">' . htmlspecialchars($eyebrow, ENT_QUOTES) . '</p>';
    echo '<h2>' . htmlspecialchars($heading, ENT_QUOTES) . '</h2>';
    echo '</div>';
    echo '<p class="section-copy">' . htmlspecialchars($copy, ENT_QUOTES) . '</p>';
    echo '</div>';
    echo '<div class="rail-grid">';

    foreach ($items as $item) {
        $isActive = !empty($item['active']);
        $className = $isActive ? 'rail-card active' : 'rail-card';

        echo '<a class="' . $className . '" href="' . htmlspecialchars((string) $item['href'], ENT_QUOTES) . '">';
        echo '<span class="rail-kicker">' . htmlspecialchars((string) $item['kicker'], ENT_QUOTES) . '</span>';
        echo '<strong>' . htmlspecialchars((string) $item['title'], ENT_QUOTES) . '</strong>';
        echo '<p>' . htmlspecialchars((string) $item['copy'], ENT_QUOTES) . '</p>';
        echo '</a>';
    }

    echo '</div>';
    echo '</div>';
    echo '</section>';
}

function renderAuthFlowRail(string $active): void
{
    renderInterfaceRail(
        'Separated Auth Flow',
        'Public Entry Interfaces',
        'Each authentication task lives in its own page so the public flow stays predictable and easier to test.',
        [
            ['href' => 'index.php', 'kicker' => 'Gateway', 'title' => 'Start Here', 'copy' => 'Choose the correct authentication path or jump into the workspace.', 'active' => $active === 'index'],
            ['href' => 'login.php', 'kicker' => 'A01', 'title' => 'Login', 'copy' => 'Returning users authenticate here with seed-account shortcuts available.', 'active' => $active === 'login'],
            ['href' => 'register.php', 'kicker' => 'A03', 'title' => 'Register', 'copy' => 'New users create learner or instructor-track accounts here.', 'active' => $active === 'register'],
            ['href' => 'verify.php', 'kicker' => 'A04', 'title' => 'Verify', 'copy' => 'Email verification is isolated into its own activation screen.', 'active' => $active === 'verify'],
            ['href' => 'recover.php', 'kicker' => 'A02', 'title' => 'Recover', 'copy' => 'Password recovery and reset stay together in a dedicated support page.', 'active' => $active === 'recover'],
        ]
    );
}

function renderLearningRail(string $active): void
{
    renderInterfaceRail(
        'Learning Interfaces',
        'Study, Challenge, and Reference Pages',
        'Content discovery, challenge work, rankings, and help are split into separate pages instead of one overloaded learning screen.',
        [
            ['href' => 'learn.php', 'kicker' => 'E02-E06', 'title' => 'Learning Hub', 'copy' => 'Browse courses and modules, then launch focused module reading.', 'active' => $active === 'learn'],
            ['href' => 'module.php', 'kicker' => 'Reader', 'title' => 'Module View', 'copy' => 'Read the selected module in a distraction-light page.', 'active' => $active === 'module'],
            ['href' => 'challenges.php', 'kicker' => 'E07-E08', 'title' => 'Challenge Arena', 'copy' => 'Challenge participation and execution feedback live here.', 'active' => $active === 'challenges'],
            ['href' => 'leaderboard.php', 'kicker' => 'D03', 'title' => 'Leaderboard', 'copy' => 'Ranking visibility is separated from active study and challenge work.', 'active' => $active === 'leaderboard'],
            ['href' => 'faq.php', 'kicker' => 'E11', 'title' => 'Help Center', 'copy' => 'FAQ and support guidance sit in their own reference page.', 'active' => $active === 'faq'],
        ]
    );
}

function renderWalletRail(string $active): void
{
    renderInterfaceRail(
        'Wallet Interfaces',
        'Spend and Earn Separately',
        'Token purchase and creator earnings have distinct pages so transaction intent stays clear.',
        [
            ['href' => 'topup.php', 'kicker' => 'F01-F02', 'title' => 'Top Up Center', 'copy' => 'Browse packages, purchase KodeBits, and review wallet history.', 'active' => $active === 'topup'],
            ['href' => 'finance.php', 'kicker' => 'F03-F04', 'title' => 'Earnings Center', 'copy' => 'Review creator earnings and request payouts in a separate interface.', 'active' => $active === 'finance'],
        ]
    );
}

function renderWorkspaceAreasRail(string $active): void
{
    renderInterfaceRail(
        'Workspace Areas',
        'Operational Areas',
        'The logged-in product is split by job-to-be-done so learners, creators, and moderators do not share one crowded page.',
        [
            ['href' => 'home.php', 'kicker' => 'E01', 'title' => 'Homepage', 'copy' => 'See your current state, metrics, and cross-product shortcuts.', 'active' => $active === 'home'],
            ['href' => 'profile.php', 'kicker' => 'A05-A10', 'title' => 'Account Center', 'copy' => 'Manage profile, requests, credentials, and sensitive account actions.', 'active' => $active === 'profile'],
            ['href' => 'creator.php', 'kicker' => 'B + C', 'title' => 'Creator Workspace', 'copy' => 'Author courses, modules, and challenges in a dedicated operational page.', 'active' => $active === 'creator'],
            ['href' => 'rewards.php', 'kicker' => 'D', 'title' => 'Rewards Center', 'copy' => 'Handle presets, gamified activity setup, and weekly challenge flows.', 'active' => $active === 'rewards'],
            ['href' => 'governance.php', 'kicker' => 'G', 'title' => 'Governance Center', 'copy' => 'Moderation, review queues, and admin lifecycle tools stay together.', 'active' => $active === 'governance'],
        ]
    );
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
