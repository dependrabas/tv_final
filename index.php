<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
$data = fetch_board_data();
$settings = $data['settings'];
$welcomeEnabled = in_array(strtolower(trim((string) ($settings['welcome_enabled'] ?? '1'))), ['1', 'true', 'yes', 'on'], true);
$welcomeKicker = $settings['welcome_kicker'] ?? 'Kuzuzangpo la';
$welcomeTitle = $settings['welcome_title'] ?? 'Welcome to Trongsa College';
$welcomeMessage = $settings['welcome_message'] ?? 'A place where Bhutanese heritage, curiosity, and purposeful learning come together.';
$bootstrap = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$boardCssVersion = (string) (filemtime(__DIR__ . '/assets/css/board.css') ?: 1);
$boardJsVersion = (string) (filemtime(__DIR__ . '/assets/js/board.js') ?: 1);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="<?= e($settings['primary_colour'] ?? '#123d2c') ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <title><?= e($settings['short_name'] ?? 'TCHS') ?> Digital Information Board</title>
    <link rel="stylesheet" href="<?= e(base_url('assets/css/board.css?v=' . $boardCssVersion)) ?>">
    <style>:root{--primary:<?= e($settings['primary_colour'] ?? '#123d2c') ?>;--accent:<?= e($settings['accent_colour'] ?? '#d29d2b') ?>}</style>
</head>
<body>
<div class="board-shell">
    <header class="board-header">
        <div class="brand-lockup">
            <div class="logo-wrap"><img id="college-logo" src="<?= e(public_path($settings['logo_path'] ?? null, 'assets/images/college-logo.png')) ?>" alt="Trongsa College logo"></div>
            <div>
                <div class="eyebrow" id="university-name"><?= e($settings['university_name'] ?? 'Royal University of Bhutan') ?></div>
                <h1 id="college-name"><?= e($settings['college_name'] ?? 'Trongsa College of Heritage and Contemporary Studies') ?></h1>
                <p id="footer-text"><?= e($settings['footer_text'] ?? 'Knowledge • Culture • Service') ?></p>
            </div>
        </div>
        <div class="clock-panel" aria-label="Current date and time">
            <div id="live-time">00:00:00</div>
            <div id="live-date">Loading date…</div>
        </div>
    </header>

    <div class="heritage-band" aria-hidden="true"></div>

    <main class="board-stage">
        <div class="views-viewport">
            <section class="board-view welcome-greeting-view<?= $welcomeEnabled ? ' active' : '' ?>" data-view="Welcome" aria-hidden="<?= $welcomeEnabled ? 'false' : 'true' ?>">
                <article class="welcome-greeting-card heritage-panel">
                    <div class="welcome-pattern" aria-hidden="true"></div>
                    <div class="welcome-corner welcome-corner-top" aria-hidden="true"><span></span></div>
                    <div class="welcome-corner welcome-corner-bottom" aria-hidden="true"><span></span></div>
                    <div class="welcome-greeting-inner">
                        <div class="welcome-seal-wrap">
                            <div class="welcome-seal">
                                <span class="welcome-seal-ray welcome-seal-ray-one" aria-hidden="true"></span>
                                <span class="welcome-seal-ray welcome-seal-ray-two" aria-hidden="true"></span>
                                <img id="welcome-logo" src="<?= e(public_path($settings['logo_path'] ?? null, 'assets/images/college-logo.png')) ?>" alt="Trongsa College logo">
                            </div>
                        </div>
                        <div class="welcome-copy-block">
                            <div class="welcome-overline"><span id="welcome-kicker"><?= e($welcomeKicker) ?></span><i aria-hidden="true"></i></div>
                            <h2 id="welcome-title"><?= e($welcomeTitle) ?></h2>
                            <p class="welcome-message" id="welcome-message"><?= e($welcomeMessage) ?></p>
                            <div class="welcome-signature">
                                <span class="welcome-signature-line" aria-hidden="true"></span>
                                <span id="welcome-footer-text"><?= e($settings['footer_text'] ?? 'Knowledge • Culture • Service') ?></span>
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <section class="board-view<?= $welcomeEnabled ? '' : ' active' ?>" data-view="Highlights" aria-hidden="<?= $welcomeEnabled ? 'true' : 'false' ?>">
                <article class="highlight-hero heritage-panel">
                    <div id="media-stage" class="media-stage"></div>
                    <div class="media-overlay">
                        <span class="content-tag">Campus highlights</span>
                        <h2 id="media-title">Welcome to Trongsa College</h2>
                        <p id="media-caption">Learning rooted in Bhutanese heritage, ready for a changing world.</p>
                        <div id="media-dots" class="media-dots"></div>
                    </div>
                </article>
                <aside class="welcome-panel heritage-panel">
                    <div class="ornament-mark" aria-hidden="true"><span></span></div>
                    <span class="section-kicker">Welcome to Trongsa</span>
                    <h2>Knowledge grounded in culture.</h2>
                    <p id="welcome-vision"><?= e($settings['vision'] ?? '') ?></p>
                    <div id="highlight-notice" class="highlight-notice"></div>
                </aside>
            </section>

            <section class="board-view" data-view="Announcements" aria-hidden="true">
                <header class="view-heading centered-view-heading">
                    <div><span class="section-kicker">Important information</span><h2>College announcements</h2></div>
                    <div class="heading-emblem announcement-heading-emblem" aria-hidden="true"><span></span></div>
                </header>
                <div id="announcement-list" class="announcement-grid"></div>
            </section>

            <section class="board-view" data-view="Latest news" aria-hidden="true">
                <header class="view-heading centered-view-heading">
                    <div><span class="section-kicker">Stories from our community</span><h2>Latest college news</h2></div>
                </header>
                <div id="news-stage" class="news-grid"></div>
            </section>

            <section class="board-view" data-view="College profile" aria-hidden="true">
                <div class="profile-layout single-panel">
                    <article class="profile-statistics heritage-panel">
                        <header class="view-heading compact stats-heading"><h2>At a glance </h2></header>
                        <div id="stats-grid" class="stats-grid"></div>
                    </article>
                </div>
            </section>

            <section class="board-view" data-view="Academic programmes" aria-hidden="true">
                <div class="programmes-layout">
                    <article class="profile-programmes heritage-panel">
                        <header class="view-heading compact programmes-heading"><div><span class="section-kicker">Study with us</span><h2>Academic programmes</h2></div></header>
                        <div id="programme-list" class="programme-list"></div>
                    </article>
                </div>
            </section>

            <section class="board-view" data-view="Vision" aria-hidden="true">
                <div class="community-layout single-panel">
                    <article class="purpose-panel heritage-panel">
                        <div id="vision-block" class="purpose-block vision-feature"><div><h2>Vision</h2><p id="vision-text"><?= e($settings['vision'] ?? '') ?></p></div></div>
                    </article>
                </div>
            </section>

            <section class="board-view" data-view="Staff on leave" aria-hidden="true">
                <div class="community-layout single-panel">
                    <article class="leave-panel heritage-panel">
                        <header class="view-heading compact centered-view-heading"><div><span class="section-kicker">Today on campus</span><h2>Staff on leave</h2></div></header>
                        <div id="leave-list" class="leave-list"></div>
                    </article>
                </div>
            </section>
        </div>
    </main>

    <footer class="ticker-bar">
        <div class="ticker-label"><span aria-hidden="true">◆</span><span>NOTICE</span></div>
        <div class="ticker-window"><div id="ticker-track" class="ticker-track"></div></div>
        <div class="board-status"><span id="sync-indicator"></span> Updated live</div>
    </footer>
</div>

<div id="tv-help" class="tv-help" role="status" aria-live="polite" hidden></div>
<div id="rotation-state" class="rotation-state" role="status" aria-live="polite" hidden>Rotation paused · Press OK to resume</div>

<script>
window.BOARD_DATA = <?= $bootstrap ?>;
window.BOARD_BASE = <?= json_encode(rtrim(base_url(), '/')) ?>;
</script>
<script src="<?= e(base_url('assets/js/board.js?v=' . $boardJsVersion)) ?>"></script>
</body>
</html>
