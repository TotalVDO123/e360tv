<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/config.php';

function is_authorized(string $password): bool
{
    if ($password === '') {
        return true;
    }

    return isset($_SESSION['e360tv_checklist_authorized'])
        && $_SESSION['e360tv_checklist_authorized'] === true;
}

$loginError = '';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['checklist_password'])
    && $CHECKLIST_PASSWORD !== ''
) {
    $submitted = (string)$_POST['checklist_password'];

    if (hash_equals($CHECKLIST_PASSWORD, $submitted)) {
        session_regenerate_id(true);
        $_SESSION['e360tv_checklist_authorized'] = true;
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }

    $loginError = 'Incorrect password.';
}

if (isset($_GET['logout'])) {
    unset($_SESSION['e360tv_checklist_authorized']);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

if (!is_authorized($CHECKLIST_PASSWORD)):
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>e360TV Checklist Login</title>
<style>
body{margin:0;background:#eef2f6;color:#152331;font:15px Arial,Helvetica,sans-serif}
.box{max-width:420px;margin:12vh auto;background:#fff;border:1px solid #d4dde6;border-radius:12px;padding:25px}
h1{margin-top:0;color:#17324d}input,button{width:100%;padding:11px;margin-top:10px;border:1px solid #b9c7d4;border-radius:7px}
button{background:#17324d;color:#fff;font-weight:700;cursor:pointer}.error{color:#a61b1b;font-weight:700}
</style>
</head>
<body>
<div class="box">
<h1>e360TV Launch Checklist</h1>
<p>Enter the team password.</p>
<?php if ($loginError !== ''): ?><p class="error"><?= htmlspecialchars($loginError) ?></p><?php endif; ?>
<form method="post">
<input type="password" name="checklist_password" autocomplete="current-password" required autofocus>
<button type="submit">Open Checklist</button>
</form>
</div>
</body>
</html>
<?php
exit;
endif;

if (!isset($_SESSION['e360tv_checklist_csrf'])) {
    $_SESSION['e360tv_checklist_csrf'] = bin2hex(random_bytes(32));
}

$csrf = (string)$_SESSION['e360tv_checklist_csrf'];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>e360TV Dev Launch To-Do Checklist</title>
<style>
:root{--bg:#eef2f6;--card:#fff;--ink:#152331;--muted:#617181;--navy:#17324d;--red:#a61b1b;--green:#287443;--line:#d4dde6}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font:14px/1.45 Arial,Helvetica,sans-serif}
header{background:var(--navy);color:#fff;padding:20px 24px;position:sticky;top:0;z-index:5;box-shadow:0 2px 8px #0003}
h1{margin:0 0 5px;font-size:25px}header p{margin:0;color:#dce7f2}.topline{display:flex;justify-content:space-between;gap:12px;align-items:end}
.logout{color:#fff}.wrap{max-width:1500px;margin:20px auto;padding:0 18px 60px}
.summary{display:grid;grid-template-columns:repeat(5,minmax(140px,1fr));gap:12px;margin-bottom:14px}
.kpi{background:#fff;border:1px solid var(--line);border-radius:10px;padding:13px}.kpi b{display:block;font-size:23px;margin-top:3px}
.toolbar{background:#fff;border:1px solid var(--line);border-radius:10px;padding:12px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:14px}
select,input[type=search],input[type=text],button{border:1px solid #b9c7d4;border-radius:7px;padding:9px 10px;background:#fff;color:var(--ink)}
button{cursor:pointer;font-weight:700}button.primary{background:var(--navy);color:#fff;border-color:var(--navy)}
.progress{height:12px;background:#dfe7ee;border-radius:999px;overflow:hidden;min-width:180px;flex:1}.progress i{display:block;height:100%;background:var(--green);width:0}
.phase{margin:18px 0 10px;background:var(--navy);color:#fff;border-radius:8px;padding:10px 13px;font-size:17px}
.task{background:var(--card);border:1px solid var(--line);border-left:6px solid #8899a9;border-radius:9px;padding:13px;margin:8px 0;display:grid;grid-template-columns:95px 110px minmax(280px,1.2fr) minmax(320px,1.4fr) 220px;gap:10px;align-items:start}
.task.blocker{border-left-color:var(--red)}.task.high{border-left-color:#d77b00}.task.medium{border-left-color:#c8a400}.task.done{opacity:.62;background:#edf7ef}
.id{font-weight:700}.badge{display:inline-block;padding:3px 7px;border-radius:999px;font-size:11px;font-weight:700}
.badge.blocker{background:#f8d4d4;color:#820d0d}.badge.high{background:#fee5c5;color:#8a4b00}.badge.medium{background:#fff2b8;color:#705c00}
.required{font-size:11px;font-weight:700;color:#8a1a1a}.task-title{font-weight:700;font-size:15px;margin-bottom:5px}.small,.owner,.updated{font-size:12px;color:var(--muted)}
.accept{font-size:13px}.accept b{display:block;margin-bottom:3px}.status{width:100%}.notes{width:100%;min-height:70px;border:1px solid #b9c7d4;border-radius:7px;padding:7px;resize:vertical}
a{color:#0c5d9f}.launch-note{background:#fff6d8;border:1px solid #e3c66b;border-radius:10px;padding:13px;margin-bottom:14px}
.sync{font-weight:700}.sync.ok{color:#d9f5df}.sync.bad{color:#ffd2d2}
@media(max-width:1050px){.summary{grid-template-columns:repeat(2,1fr)}.task{grid-template-columns:90px 1fr}.task>div:nth-child(n+3){grid-column:2/-1}.topline{display:block}}
@media print{header{position:static}.toolbar{display:none}.wrap{max-width:none;margin:0}.task{break-inside:avoid}body{background:#fff}.logout{display:none}}
</style>
</head>
<body>
<header>
<div class="topline">
<div><h1>e360TV Dev-Site Launch To-Do Checklist</h1><p>Shared server version. Everyone opening this page sees the same saved status and notes.</p></div>
<div><span id="sync" class="sync">Loading shared state…</span><?php if ($CHECKLIST_PASSWORD !== ''): ?> · <a class="logout" href="?logout=1">Log out</a><?php endif; ?></div>
</div>
</header>
<div class="wrap">
<div class="launch-note"><b>Minimum launch approach:</b> fix the legal, account, payment, playback, security and Live TV blockers. Hide unfinished public features rather than launching broken destinations.</div>
<div class="summary">
<div class="kpi">Total tasks<b id="total">0</b></div>
<div class="kpi">Required before launch<b id="required">0</b></div>
<div class="kpi">Completed<b id="done">0</b></div>
<div class="kpi">Open blockers<b id="blockers">0</b></div>
<div class="kpi">Launch status<b id="launchStatus">NOT READY</b></div>
</div>
<div class="toolbar">
<input id="person" type="text" maxlength="80" placeholder="Your name">
<input id="search" type="search" placeholder="Search tasks">
<select id="priority"><option value="">All priorities</option><option>BLOCKER</option><option>HIGH</option><option>MEDIUM</option></select>
<select id="requiredFilter"><option value="">All tasks</option><option value="YES">Required before launch</option><option value="NO">Post-launch / optional</option></select>
<select id="statusFilter"><option value="">All statuses</option><option>Not Started</option><option>In Progress</option><option>Blocked</option><option>Ready for Retest</option><option>Done</option><option>Not Applicable</option></select>
<button class="primary" id="refreshBtn">Refresh Shared State</button>
<button onclick="window.print()">Print / Save PDF</button>
<button id="resetBtn">Reset All</button>
<div class="progress"><i id="bar"></i></div>
</div>
<div id="tasks"></div>
</div>
<script>
const DATA = [{"id": "SEC-01", "phase": "1. Security & Environment", "priority": "BLOCKER", "required": "YES", "area": "Security", "task": "Confirm the dev production candidate is built from a known-clean repository/package.", "acceptance": "Security owner documents the source commit/build package and confirms no untrusted old-site files were reused.", "action": "VERIFY", "owner": "Security / Hosting", "status": "Not Started", "url": "https://dev.e360tv.com/"}, {"id": "SEC-02", "phase": "1. Security & Environment", "priority": "BLOCKER", "required": "YES", "area": "Security", "task": "Confirm the dev site does not share the old site's database, storage, server account, cron jobs, deployment keys, or secrets.", "acceptance": "Written infrastructure map shows full isolation or documented clean migration of each shared component.", "action": "VERIFY", "owner": "Security / Hosting", "status": "Not Started", "url": ""}, {"id": "SEC-03", "phase": "1. Security & Environment", "priority": "BLOCKER", "required": "YES", "area": "Security", "task": "Run malware, web-shell, modified-file, dependency, and server-level scans on the final candidate.", "acceptance": "Scans are clean; any findings are removed and the candidate is rebuilt/retested.", "action": "FIX", "owner": "Security / Hosting", "status": "Not Started", "url": ""}, {"id": "SEC-04", "phase": "1. Security & Environment", "priority": "BLOCKER", "required": "YES", "area": "Security", "task": "Search the dev codebase, routes, database, storage, logs, and web-server configuration for indexed spam paths and Japanese shopping text.", "acceptance": "No malicious route/content remains; source of historical spam URLs is identified.", "action": "FIX", "owner": "Security / Backend", "status": "Not Started", "url": "https://dev.e360tv.com/items/Z83077349/"}, {"id": "SEC-05", "phase": "1. Security & Environment", "priority": "BLOCKER", "required": "YES", "area": "Security", "task": "Rotate every credential used by the dev/production candidate.", "acceptance": "Registrar, DNS/CDN, hosting, SSH/SFTP, database, Laravel/app, mail, payments, Bunny/CDN, OAuth, API and admin credentials are changed and recorded securely.", "action": "FIX", "owner": "Security / Hosting", "status": "Not Started", "url": ""}, {"id": "SEC-06", "phase": "1. Security & Environment", "priority": "HIGH", "required": "YES", "area": "Security", "task": "Review all admin accounts, roles, API users, service accounts, and SSH keys.", "acceptance": "Unknown/test accounts and keys are removed; least privilege and MFA are enabled where supported.", "action": "VERIFY", "owner": "Security / Backend", "status": "Not Started", "url": ""}, {"id": "SEC-07", "phase": "1. Security & Environment", "priority": "HIGH", "required": "YES", "area": "Security", "task": "Enable WAF rules, rate limits, bot protection, login protection, error logging, uptime checks, and security alerts.", "acceptance": "Controls are active and a test alert is received by a named owner.", "action": "FIX", "owner": "Security / Hosting", "status": "Not Started", "url": ""}, {"id": "SEC-08", "phase": "1. Security & Environment", "priority": "HIGH", "required": "YES", "area": "Operations", "task": "Create a fresh production backup and complete a restore test outside the live environment.", "acceptance": "Database, uploads/configuration, and deployment package restore successfully; restore steps are documented.", "action": "TEST", "owner": "Hosting / Backend", "status": "Not Started", "url": ""}, {"id": "LEG-01", "phase": "2. Legal & Public Trust", "priority": "BLOCKER", "required": "YES", "area": "Legal", "task": "Publish approved Terms & Conditions at the exact route used by registration.", "acceptance": "/terms-conditions returns 200, contains approved text, and works on desktop/mobile.", "action": "FIX", "owner": "Legal / Backend", "status": "Not Started", "url": "https://dev.e360tv.com/terms-conditions"}, {"id": "LEG-02", "phase": "2. Legal & Public Trust", "priority": "BLOCKER", "required": "YES", "area": "Legal", "task": "Link Terms & Conditions consistently from registration, footer, checkout, subscriptions, and PPV purchase screens.", "acceptance": "Every public legal link opens the approved production page in the intended manner.", "action": "FIX", "owner": "Frontend / Legal", "status": "Not Started", "url": "https://dev.e360tv.com/register"}, {"id": "LEG-03", "phase": "2. Legal & Public Trust", "priority": "HIGH", "required": "YES", "area": "Legal", "task": "Rewrite the malformed registration consent sentence.", "acceptance": "Consent is grammatically correct, specific, and records the accepted policy/version/date.", "action": "FIX", "owner": "Content / Legal", "status": "Not Started", "url": "https://dev.e360tv.com/register"}, {"id": "LEG-04", "phase": "2. Legal & Public Trust", "priority": "HIGH", "required": "YES", "area": "Privacy", "task": "Review the Privacy Policy against actual site, payment, analytics, email, CDN, and account behavior.", "acceptance": "Policy accurately names data categories, purposes, processors, retention, rights, contact, cookies, and security practices.", "action": "VERIFY", "owner": "Legal / Privacy", "status": "Not Started", "url": "https://dev.e360tv.com/privacy-policy"}, {"id": "LEG-05", "phase": "2. Legal & Public Trust", "priority": "HIGH", "required": "YES", "area": "Privacy", "task": "Reconcile website and app-store privacy/data declarations.", "acceptance": "Website, Google Play, and Apple declarations match actual behavior or clearly explain platform differences.", "action": "FIX", "owner": "Legal / Mobile / Security", "status": "Not Started", "url": ""}, {"id": "LEG-06", "phase": "2. Legal & Public Trust", "priority": "MEDIUM", "required": "NO", "area": "Legal", "task": "Confirm the public company/developer identity used by the website and both app stores.", "acceptance": "Approved legal and brand names are consistent or intentionally explained.", "action": "VERIFY", "owner": "Legal / Mobile", "status": "Not Started", "url": ""}, {"id": "NAV-01", "phase": "3. Navigation & Public Routes", "priority": "BLOCKER", "required": "YES", "area": "Navigation", "task": "Fix the bottom Search item, which currently points to /movie and returns 404.", "acceptance": "Search opens a working search experience, or the Search item is removed from every public template before launch.", "action": "FIX OR HIDE", "owner": "Frontend / Backend", "status": "Not Started", "url": "https://dev.e360tv.com/movie"}, {"id": "NAV-02", "phase": "3. Navigation & Public Routes", "priority": "BLOCKER", "required": "YES", "area": "Navigation", "task": "Fix the bottom Profile item, which currently uses the Live TV target.", "acceptance": "Logged-in users reach their profile; guests are sent to login with a safe return URL, or Profile is hidden for guests.", "action": "FIX OR HIDE", "owner": "Frontend / Backend", "status": "Not Started", "url": "https://dev.e360tv.com/live_list"}, {"id": "NAV-03", "phase": "3. Navigation & Public Routes", "priority": "BLOCKER", "required": "YES", "area": "Catalog", "task": "Resolve the empty Movies page.", "acceptance": "Populate it with valid movies and pagination, or remove every Movies link until ready.", "action": "FIX OR HIDE", "owner": "Backend / Content", "status": "Not Started", "url": "https://dev.e360tv.com/movies"}, {"id": "NAV-04", "phase": "3. Navigation & Public Routes", "priority": "BLOCKER", "required": "YES", "area": "Catalog", "task": "Choose one canonical TV Shows route.", "acceptance": "Make the working network directory canonical; redirect /tv-shows to it, populate /tv-shows, or hide the duplicate route.", "action": "REDIRECT OR HIDE", "owner": "Backend / Product", "status": "Not Started", "url": "https://dev.e360tv.com/tv-shows"}, {"id": "NAV-05", "phase": "3. Navigation & Public Routes", "priority": "BLOCKER", "required": "YES", "area": "Catalog", "task": "Resolve the empty Videos page.", "acceptance": "Populate it with valid items or remove all Videos links until ready.", "action": "FIX OR HIDE", "owner": "Backend / Content", "status": "Not Started", "url": "https://dev.e360tv.com/videos"}, {"id": "NAV-06", "phase": "3. Navigation & Public Routes", "priority": "HIGH", "required": "YES", "area": "Navigation", "task": "Rename “Tv Shows Networs Wise” to “TV Shows by Network.”", "acceptance": "Correct wording appears in every desktop/mobile/menu template.", "action": "FIX", "owner": "Frontend / Content", "status": "Not Started", "url": "https://dev.e360tv.com/tvshow"}, {"id": "NAV-07", "phase": "3. Navigation & Public Routes", "priority": "HIGH", "required": "YES", "area": "Navigation", "task": "Remove test360 from navigation and production-facing content.", "acceptance": "No test360 menu item, page card, API response, sitemap entry, or internal link remains.", "action": "REMOVE", "owner": "Content / Backend", "status": "Not Started", "url": ""}, {"id": "NAV-08", "phase": "3. Navigation & Public Routes", "priority": "HIGH", "required": "YES", "area": "Navigation", "task": "Review every network menu destination and its content count.", "acceptance": "Each published network shows valid content; empty networks are hidden until populated.", "action": "FIX OR HIDE", "owner": "Content / Backend", "status": "Not Started", "url": ""}, {"id": "NAV-09", "phase": "3. Navigation & Public Routes", "priority": "HIGH", "required": "YES", "area": "Navigation", "task": "Review every film-genre destination and its content count.", "acceptance": "Each published genre shows valid content; empty genres are hidden until populated.", "action": "FIX OR HIDE", "owner": "Content / Backend", "status": "Not Started", "url": ""}, {"id": "NAV-10", "phase": "3. Navigation & Public Routes", "priority": "HIGH", "required": "YES", "area": "Navigation", "task": "Resolve the empty Personalities View All page.", "acceptance": "Populate the page or remove the View All link while retaining valid homepage personalities.", "action": "FIX OR HIDE", "owner": "Content / Backend", "status": "Not Started", "url": "https://dev.e360tv.com/castcrew-list/actor/all"}, {"id": "NAV-11", "phase": "3. Navigation & Public Routes", "priority": "HIGH", "required": "YES", "area": "Navigation", "task": "Resolve the empty Coming Soon page.", "acceptance": "Populate it with titled cards and valid release information, or remove it from public navigation.", "action": "FIX OR HIDE", "owner": "Content / Backend", "status": "Not Started", "url": "https://dev.e360tv.com/comingsoon"}, {"id": "NAV-12", "phase": "3. Navigation & Public Routes", "priority": "MEDIUM", "required": "YES", "area": "Navigation", "task": "Remove duplicate top-level links for Film Festivals Online and E360films.", "acceptance": "Each destination appears once in the clearest navigation location.", "action": "FIX", "owner": "Frontend / Product", "status": "Not Started", "url": ""}, {"id": "NAV-13", "phase": "3. Navigation & Public Routes", "priority": "HIGH", "required": "YES", "area": "Navigation", "task": "Test every header, mega-menu, mobile-menu, footer, and bottom-navigation link.", "acceptance": "No published link returns 404/500, redirects incorrectly, or opens an empty shell without an approved empty state.", "action": "TEST", "owner": "QA / Frontend", "status": "Not Started", "url": ""}, {"id": "NAV-14", "phase": "3. Navigation & Public Routes", "priority": "HIGH", "required": "YES", "area": "Homepage", "task": "Test every homepage card, button, carousel item, View All link, logo, and image.", "acceptance": "All visible homepage elements route to the correct content type and valid detail page.", "action": "TEST", "owner": "QA / Content", "status": "Not Started", "url": "https://dev.e360tv.com/"}, {"id": "NAV-15", "phase": "3. Navigation & Public Routes", "priority": "MEDIUM", "required": "YES", "area": "Errors", "task": "Create helpful branded 404 and 500 error pages.", "acceptance": "Unknown URLs show a useful page with navigation; errors do not expose stack traces or server details.", "action": "FIX", "owner": "Frontend / Backend", "status": "Not Started", "url": ""}, {"id": "NAV-16", "phase": "3. Navigation & Public Routes", "priority": "MEDIUM", "required": "YES", "area": "Localization", "task": "Make the language selector and translated navigation consistent, or remove unsupported languages.", "acceptance": "Only supported languages are offered; every published template uses complete translations.", "action": "FIX OR HIDE", "owner": "Frontend / Localization", "status": "Not Started", "url": ""}, {"id": "CNT-01", "phase": "4. Content, Templates & Data", "priority": "HIGH", "required": "YES", "area": "Routing", "task": "Fix The Cure: Final Redemption detail URL that redirects to the homepage.", "acceptance": "The footer/recommendation link reaches the correct valid detail page and returns 200.", "action": "FIX", "owner": "Backend / Content", "status": "Not Started", "url": "https://dev.e360tv.com/tvshow-details/the-cure%3A-final-redemption"}, {"id": "CNT-02", "phase": "4. Content, Templates & Data", "priority": "HIGH", "required": "YES", "area": "Localization", "task": "Replace visible raw translation keys.", "acceptance": "No frontend.* key is visible; missing translations use an approved fallback.", "action": "FIX", "owner": "Frontend / Backend", "status": "Not Started", "url": "https://dev.e360tv.com/episode-details/think-engage-thrive-e19-entrepreneur-scott-ketchum-co-founder-sfoglini-pasta"}, {"id": "CNT-03", "phase": "4. Content, Templates & Data", "priority": "MEDIUM", "required": "YES", "area": "Template", "task": "Remove the “Episode Name” placeholder from movie pages.", "acceptance": "Movie pages use correct movie labels and never show episode-only placeholders.", "action": "FIX", "owner": "Frontend", "status": "Not Started", "url": "https://dev.e360tv.com/movie-details/the-new-empire"}, {"id": "CNT-04", "phase": "4. Content, Templates & Data", "priority": "HIGH", "required": "YES", "area": "Contact", "task": "Replace the inconsistent +154784784545 number with the approved contact number.", "acceptance": "One configuration source controls the number and every template displays the approved value.", "action": "FIX", "owner": "Backend / Content", "status": "Not Started", "url": ""}, {"id": "CNT-05", "phase": "4. Content, Templates & Data", "priority": "HIGH", "required": "YES", "area": "Content", "task": "Rewrite and fact-check the FAQ.", "acceptance": "No raw entities, research notes, incomplete pricing, unsupported claims, or formatting artifacts remain.", "action": "FIX", "owner": "Content / Legal", "status": "Not Started", "url": "https://dev.e360tv.com/faq"}, {"id": "CNT-06", "phase": "4. Content, Templates & Data", "priority": "MEDIUM", "required": "YES", "area": "Content", "task": "Review About Us copy and external join/contact actions.", "acceptance": "Copy is final and every external action goes to an approved owned destination.", "action": "VERIFY", "owner": "Content / Marketing", "status": "Not Started", "url": "https://dev.e360tv.com/about-us"}, {"id": "CNT-07", "phase": "4. Content, Templates & Data", "priority": "HIGH", "required": "YES", "area": "Data", "task": "Reconcile record counts by movie, show, season, episode, network, live channel, personality, PPV item, user, subscription, and transaction.", "acceptance": "Counts are documented and unexplained differences are resolved before final sync.", "action": "VERIFY", "owner": "Data / Backend", "status": "Not Started", "url": ""}, {"id": "CNT-08", "phase": "4. Content, Templates & Data", "priority": "HIGH", "required": "YES", "area": "Data", "task": "Sample at least 25 records per major content type.", "acceptance": "Titles, slugs, descriptions, images, video IDs, durations, categories, status, prices and access rules match the source of truth.", "action": "TEST", "owner": "QA / Content", "status": "Not Started", "url": ""}, {"id": "CNT-09", "phase": "4. Content, Templates & Data", "priority": "HIGH", "required": "YES", "area": "Data", "task": "Remove duplicate, orphaned, test, unpublished, and incorrectly typed records from public results.", "acceptance": "Public lists contain only approved records; orphaned seasons/episodes and invalid relations are resolved.", "action": "FIX", "owner": "Data / Content", "status": "Not Started", "url": ""}, {"id": "CNT-10", "phase": "4. Content, Templates & Data", "priority": "MEDIUM", "required": "YES", "area": "Data", "task": "Test titles and slugs containing colons, apostrophes, Unicode, spaces, encoded characters, and mixed case.", "acceptance": "All representative special-character URLs resolve correctly without redirecting home or producing 404.", "action": "TEST", "owner": "Backend / QA", "status": "Not Started", "url": ""}, {"id": "CNT-11", "phase": "4. Content, Templates & Data", "priority": "HIGH", "required": "YES", "area": "Media", "task": "Verify images and video references do not depend on the compromised old origin.", "acceptance": "All production media use approved CDN/storage URLs and load over HTTPS.", "action": "VERIFY", "owner": "Backend / Content", "status": "Not Started", "url": ""}, {"id": "CNT-12", "phase": "4. Content, Templates & Data", "priority": "HIGH", "required": "YES", "area": "Migration", "task": "Freeze content changes for final sync or document a delta-migration process.", "acceptance": "Final migration cannot silently lose changes made during the cutover window.", "action": "VERIFY", "owner": "Product / Data", "status": "Not Started", "url": ""}, {"id": "LIV-01", "phase": "5. Live TV", "priority": "HIGH", "required": "YES", "area": "Live TV", "task": "Ensure every Live TV card visibly shows the channel/program name, logo, status, and accessible label.", "acceptance": "Names are visible and screen-reader/alt labels identify each card correctly.", "action": "FIX", "owner": "Frontend / Accessibility", "status": "Not Started", "url": "https://dev.e360tv.com/live_list"}, {"id": "LIV-02", "phase": "5. Live TV", "priority": "BLOCKER", "required": "YES", "area": "Live TV", "task": "Remove duplicated/default Upcoming schedule entries dated 01 Dec 2026.", "acceptance": "Schedule source/query returns unique real entries; no fallback date is shown as real programming.", "action": "FIX", "owner": "Backend / Data", "status": "Not Started", "url": "https://dev.e360tv.com/livetv-details/bathrobe-moments-with-dr-loren-michaels-harris"}, {"id": "LIV-03", "phase": "5. Live TV", "priority": "HIGH", "required": "YES", "area": "Live TV", "task": "Confirm schedule timezone and date formatting.", "acceptance": "Displayed times match the approved timezone strategy and are understandable to users.", "action": "VERIFY", "owner": "Product / Backend", "status": "Not Started", "url": ""}, {"id": "LIV-04", "phase": "5. Live TV", "priority": "HIGH", "required": "YES", "area": "Live TV", "task": "Verify LIVE, Upcoming, and Offline state rules.", "acceptance": "State changes occur correctly from source data and do not leave stale cache values.", "action": "TEST", "owner": "Backend / QA", "status": "Not Started", "url": ""}, {"id": "LIV-05", "phase": "5. Live TV", "priority": "BLOCKER", "required": "YES", "area": "Playback", "task": "Test at least one live channel end to end on desktop and mobile.", "acceptance": "Stream starts, recovers from transient failure, shows a useful offline state, and does not expose unprotected source URLs.", "action": "TEST", "owner": "QA / Backend", "status": "Not Started", "url": ""}, {"id": "LIV-06", "phase": "5. Live TV", "priority": "HIGH", "required": "YES", "area": "Live TV", "task": "Test representative channels in every published Live TV category.", "acceptance": "Each category has valid cards, details, schedules, and playable/intentional offline states.", "action": "TEST", "owner": "QA / Content", "status": "Not Started", "url": ""}, {"id": "AUT-01", "phase": "6. Accounts & Forms", "priority": "HIGH", "required": "YES", "area": "Forms", "task": "Stop required-field and success/error messages from appearing before user interaction.", "acceptance": "Messages render only in the correct state and are announced accessibly.", "action": "FIX", "owner": "Frontend / QA", "status": "Not Started", "url": "https://dev.e360tv.com/login"}, {"id": "AUT-02", "phase": "6. Accounts & Forms", "priority": "BLOCKER", "required": "YES", "area": "Accounts", "task": "Test an existing migrated free user login.", "acceptance": "Existing credentials work and profile/history/access data are preserved.", "action": "TEST", "owner": "QA / Backend", "status": "Not Started", "url": ""}, {"id": "AUT-03", "phase": "6. Accounts & Forms", "priority": "BLOCKER", "required": "YES", "area": "Accounts", "task": "Test an existing migrated paid user login.", "acceptance": "Subscription status and paid entitlements are correct after login.", "action": "TEST", "owner": "QA / Backend", "status": "Not Started", "url": ""}, {"id": "AUT-04", "phase": "6. Accounts & Forms", "priority": "HIGH", "required": "YES", "area": "Security", "task": "Test invalid password, unknown email, lockout/rate limiting, and account-enumeration behavior.", "acceptance": "Errors do not reveal whether an account exists; abuse controls work without blocking normal users.", "action": "TEST", "owner": "Security / QA", "status": "Not Started", "url": ""}, {"id": "AUT-05", "phase": "6. Accounts & Forms", "priority": "BLOCKER", "required": "YES", "area": "Registration", "task": "Complete a new registration from form submission through account creation.", "acceptance": "Validation, consent logging, duplicate-email handling, and final login all pass.", "action": "TEST", "owner": "QA / Backend", "status": "Not Started", "url": "https://dev.e360tv.com/register"}, {"id": "AUT-06", "phase": "6. Accounts & Forms", "priority": "BLOCKER", "required": "YES", "area": "Email", "task": "Test registration/verification email delivery and links.", "acceptance": "Email arrives with correct branding/domain; link is secure, single-purpose, and reaches the correct production URL.", "action": "TEST", "owner": "QA / Backend", "status": "Not Started", "url": ""}, {"id": "AUT-07", "phase": "6. Accounts & Forms", "priority": "BLOCKER", "required": "YES", "area": "Password Reset", "task": "Correct “We will sent” and all reset copy/state handling.", "acceptance": "Copy is correct; success and error states never appear simultaneously.", "action": "FIX", "owner": "Content / Frontend", "status": "Not Started", "url": "https://dev.e360tv.com/forget-password"}, {"id": "AUT-08", "phase": "6. Accounts & Forms", "priority": "BLOCKER", "required": "YES", "area": "Password Reset", "task": "Test password-reset email, token, expiry, single use, and post-reset login.", "acceptance": "Reset succeeds once, expires correctly, cannot be replayed, and does not expose account existence.", "action": "TEST", "owner": "QA / Backend", "status": "Not Started", "url": ""}, {"id": "AUT-09", "phase": "6. Accounts & Forms", "priority": "HIGH", "required": "YES", "area": "Sessions", "task": "Test Remember Me, session expiry, logout, CSRF protection, and return URLs.", "acceptance": "Sessions behave consistently and logout invalidates access as intended.", "action": "TEST", "owner": "Security / QA", "status": "Not Started", "url": ""}, {"id": "AUT-10", "phase": "6. Accounts & Forms", "priority": "HIGH", "required": "YES", "area": "Profile", "task": "Verify profile, subscription, purchase/rental history, and account settings.", "acceptance": "Correct user-specific information is shown and other users' data cannot be accessed.", "action": "TEST", "owner": "QA / Backend", "status": "Not Started", "url": ""}, {"id": "PAY-01", "phase": "7. Payments & Entitlements", "priority": "BLOCKER", "required": "YES", "area": "Subscriptions", "task": "Complete a sandbox subscription purchase.", "acceptance": "Checkout succeeds, correct plan/price/currency is shown, and the user receives entitlement.", "action": "TEST", "owner": "QA / Backend", "status": "Not Started", "url": ""}, {"id": "PAY-02", "phase": "7. Payments & Entitlements", "priority": "BLOCKER", "required": "YES", "area": "Payments", "task": "Verify successful payment webhooks and duplicate-event handling.", "acceptance": "Webhook is authenticated, processed once, logged, and updates account access correctly.", "action": "TEST", "owner": "Backend / QA", "status": "Not Started", "url": ""}, {"id": "PAY-03", "phase": "7. Payments & Entitlements", "priority": "HIGH", "required": "YES", "area": "Payments", "task": "Test failed, abandoned, delayed, and retried payments.", "acceptance": "No access is granted incorrectly; user messaging and recovery paths are clear.", "action": "TEST", "owner": "QA / Backend", "status": "Not Started", "url": ""}, {"id": "PAY-04", "phase": "7. Payments & Entitlements", "priority": "HIGH", "required": "YES", "area": "Subscriptions", "task": "Test customer portal, cancellation, renewal, expiration, and grace-period rules.", "acceptance": "Account access follows the approved business rules through every state.", "action": "TEST", "owner": "QA / Product", "status": "Not Started", "url": ""}, {"id": "PAY-05", "phase": "7. Payments & Entitlements", "priority": "BLOCKER", "required": "YES", "area": "PPV", "task": "Complete a sandbox PPV/rental purchase.", "acceptance": "Correct title, price and rental terms appear; purchase grants only the intended entitlement.", "action": "TEST", "owner": "QA / Backend", "status": "Not Started", "url": ""}, {"id": "PAY-06", "phase": "7. Payments & Entitlements", "priority": "BLOCKER", "required": "YES", "area": "PPV", "task": "Test rental start, duration, repeat viewing, expiration, refund, and reversal.", "acceptance": "Rental access follows the approved time window and is removed after expiry/refund.", "action": "TEST", "owner": "QA / Backend", "status": "Not Started", "url": ""}, {"id": "PAY-07", "phase": "7. Payments & Entitlements", "priority": "BLOCKER", "required": "YES", "area": "Access Control", "task": "Attempt to bypass paid access using direct player/CDN/API URLs and a second account.", "acceptance": "Protected content cannot be retrieved without a valid entitlement; signed URLs expire appropriately.", "action": "TEST", "owner": "Security / Backend", "status": "Not Started", "url": ""}, {"id": "PAY-08", "phase": "7. Payments & Entitlements", "priority": "HIGH", "required": "YES", "area": "Email", "task": "Verify purchase, renewal, cancellation, failure, refund, and PPV receipt emails.", "acceptance": "Emails contain correct amounts, titles, support details and production links.", "action": "TEST", "owner": "QA / Backend", "status": "Not Started", "url": ""}, {"id": "PLY-01", "phase": "8. Video Playback", "priority": "BLOCKER", "required": "YES", "area": "Playback", "task": "Test representative free, subscription, PPV, episode, movie, and related-content playback.", "acceptance": "Every required content type starts and respects its access rules.", "action": "TEST", "owner": "QA", "status": "Not Started", "url": ""}, {"id": "PLY-02", "phase": "8. Video Playback", "priority": "HIGH", "required": "YES", "area": "Playback", "task": "Test play, pause, seek, full screen, volume, resume position, captions, and end-of-video behavior.", "acceptance": "Controls work on supported browsers/devices with no blocking console/player errors.", "action": "TEST", "owner": "QA", "status": "Not Started", "url": ""}, {"id": "PLY-03", "phase": "8. Video Playback", "priority": "HIGH", "required": "YES", "area": "Playback", "task": "Test unavailable, removed, expired-token, network-loss, and CDN error states.", "acceptance": "Users receive useful errors and the player does not loop, hang, or expose sensitive URLs.", "action": "TEST", "owner": "QA / Backend", "status": "Not Started", "url": ""}, {"id": "PLY-04", "phase": "8. Video Playback", "priority": "HIGH", "required": "YES", "area": "Compatibility", "task": "Test Chrome, Edge, Firefox, Safari, Android and iOS representative devices.", "acceptance": "Core login, browsing and playback pass on the supported matrix.", "action": "TEST", "owner": "QA", "status": "Not Started", "url": ""}, {"id": "APP-01", "phase": "9. Mobile Apps", "priority": "HIGH", "required": "YES", "area": "Mobile", "task": "Confirm iOS and Android apps point to the new production API/auth/media endpoints.", "acceptance": "No app request depends on the old compromised origin after cutover.", "action": "VERIFY", "owner": "Mobile / Backend", "status": "Not Started", "url": ""}, {"id": "APP-02", "phase": "9. Mobile Apps", "priority": "HIGH", "required": "YES", "area": "Mobile", "task": "Smoke-test app login, registration/reset, browse, details, playback, Live TV, purchases/restore and deep links.", "acceptance": "Both apps pass or app links are temporarily removed from the website with an approved notice.", "action": "TEST OR HIDE", "owner": "Mobile / QA", "status": "Not Started", "url": ""}, {"id": "APP-03", "phase": "9. Mobile Apps", "priority": "MEDIUM", "required": "NO", "area": "Mobile", "task": "Confirm store provider names, privacy labels, screenshots, descriptions, support links and version notes.", "acceptance": "Listings accurately represent the product and current legal owner.", "action": "VERIFY", "owner": "Mobile / Legal", "status": "Not Started", "url": ""}, {"id": "SEO-01", "phase": "10. SEO, URLs & Indexing", "priority": "HIGH", "required": "YES", "area": "SEO", "task": "Build an old-to-new URL map using old sitemaps, analytics, Search Console, database exports, backlinks and known internal links.", "acceptance": "Every valuable old URL has a specific new destination or an intentional retirement decision.", "action": "FIX", "owner": "SEO / Backend", "status": "Not Started", "url": ""}, {"id": "SEO-02", "phase": "10. SEO, URLs & Indexing", "priority": "HIGH", "required": "YES", "area": "SEO", "task": "Implement permanent 301 redirects to the closest equivalent new pages.", "acceptance": "No mass redirect to the homepage; redirects are one hop and preserve useful content intent.", "action": "FIX", "owner": "Backend / SEO", "status": "Not Started", "url": ""}, {"id": "SEO-03", "phase": "10. SEO, URLs & Indexing", "priority": "HIGH", "required": "YES", "area": "SEO", "task": "Return 410 Gone for known malicious spam URLs where practical.", "acceptance": "Known spam URLs do not serve content or redirect to the homepage.", "action": "FIX", "owner": "Backend / SEO", "status": "Not Started", "url": ""}, {"id": "SEO-04", "phase": "10. SEO, URLs & Indexing", "priority": "HIGH", "required": "YES", "area": "SEO", "task": "Verify canonical tags, titles, meta descriptions, Open Graph data, structured data, and index/noindex rules.", "acceptance": "Each public page has one correct canonical and staging/dev-only pages are not indexed.", "action": "VERIFY", "owner": "SEO / Frontend", "status": "Not Started", "url": ""}, {"id": "SEO-05", "phase": "10. SEO, URLs & Indexing", "priority": "HIGH", "required": "YES", "area": "SEO", "task": "Generate a clean XML sitemap containing only valid production URLs.", "acceptance": "Sitemap returns 200, contains canonical working URLs, and excludes spam, tests, empty pages and private routes.", "action": "FIX", "owner": "Backend / SEO", "status": "Not Started", "url": ""}, {"id": "SEO-06", "phase": "10. SEO, URLs & Indexing", "priority": "HIGH", "required": "YES", "area": "SEO", "task": "Review Google Search Console and Bing Webmaster Tools for security issues, manual actions, indexed spam, crawl errors and sitemap status.", "acceptance": "Issues are documented, removal requests are submitted where appropriate, and ownership is confirmed.", "action": "VERIFY", "owner": "SEO / Security", "status": "Not Started", "url": ""}, {"id": "SEO-07", "phase": "10. SEO, URLs & Indexing", "priority": "HIGH", "required": "YES", "area": "SEO", "task": "Test redirects for mixed case, trailing slashes, encoded characters, old Live_list casing, query strings and app/deep links.", "acceptance": "No loops, chains, unexpected homepage redirects or broken high-value URLs remain.", "action": "TEST", "owner": "QA / Backend", "status": "Not Started", "url": ""}, {"id": "SEO-08", "phase": "10. SEO, URLs & Indexing", "priority": "MEDIUM", "required": "NO", "area": "SEO", "task": "Create a two-week post-launch index and branded-search monitoring routine.", "acceptance": "Named owner checks spam decline, indexed counts, canonicals, 404s and security notices daily.", "action": "VERIFY", "owner": "SEO", "status": "Not Started", "url": ""}, {"id": "QA-01", "phase": "11. Cross-Site QA", "priority": "BLOCKER", "required": "YES", "area": "QA", "task": "Run an automated and manual broken-link crawl of the final production candidate.", "acceptance": "No public 404/500, broken image, incorrect redirect, empty published destination, or mixed-content request remains.", "action": "TEST", "owner": "QA", "status": "Not Started", "url": ""}, {"id": "QA-02", "phase": "11. Cross-Site QA", "priority": "HIGH", "required": "YES", "area": "Responsive", "task": "Test homepage, menus, forms, lists, details, player, Live TV, checkout and legal pages at common desktop/tablet/mobile widths.", "acceptance": "No clipped controls, horizontal overflow, inaccessible menu, hidden CTA, or unusable form remains.", "action": "TEST", "owner": "QA / Frontend", "status": "Not Started", "url": ""}, {"id": "QA-03", "phase": "11. Cross-Site QA", "priority": "HIGH", "required": "YES", "area": "Accessibility", "task": "Test keyboard navigation, focus order, visible focus, labels, alt text, contrast, headings and form errors.", "acceptance": "Core flows are usable by keyboard and assistive technology; critical accessibility defects are fixed.", "action": "TEST", "owner": "QA / Frontend", "status": "Not Started", "url": ""}, {"id": "QA-04", "phase": "11. Cross-Site QA", "priority": "HIGH", "required": "YES", "area": "Frontend", "task": "Review browser console and network errors on every core template.", "acceptance": "No blocking JavaScript error, failed API call, CORS issue, insecure request or repeated 4xx/5xx remains.", "action": "TEST", "owner": "Frontend / QA", "status": "Not Started", "url": ""}, {"id": "QA-05", "phase": "11. Cross-Site QA", "priority": "HIGH", "required": "YES", "area": "Performance", "task": "Measure homepage, list, detail, player and login pages on mobile and desktop.", "acceptance": "Images, scripts and API calls are optimized enough for acceptable real-world use; no severe layout shift or stalled content.", "action": "TEST", "owner": "Frontend / Hosting", "status": "Not Started", "url": ""}, {"id": "QA-06", "phase": "11. Cross-Site QA", "priority": "MEDIUM", "required": "YES", "area": "UX", "task": "Add clear loading, empty, no-results, offline and error states.", "acceptance": "Users never see a blank page shell without explanation or a recovery action.", "action": "FIX", "owner": "Frontend", "status": "Not Started", "url": ""}, {"id": "QA-07", "phase": "11. Cross-Site QA", "priority": "HIGH", "required": "YES", "area": "SSL", "task": "Verify SSL, redirects to HTTPS, secure cookies, HSTS decision, and absence of mixed content.", "acceptance": "All public traffic is HTTPS and sensitive cookies use appropriate security attributes.", "action": "VERIFY", "owner": "Security / Hosting", "status": "Not Started", "url": ""}, {"id": "OPS-01", "phase": "12. Cutover & Operations", "priority": "BLOCKER", "required": "YES", "area": "Configuration", "task": "Verify final production domain values in application URLs, cookies, email links, OAuth callbacks, payment webhooks, CDN rules, CORS and mobile API configuration.", "acceptance": "No generated link or integration points to dev or the compromised old origin after cutover.", "action": "VERIFY", "owner": "Backend / Hosting", "status": "Not Started", "url": ""}, {"id": "OPS-02", "phase": "12. Cutover & Operations", "priority": "HIGH", "required": "YES", "area": "Operations", "task": "Verify queues, scheduled jobs, email workers, cache invalidation and Live TV schedule updates.", "acceptance": "Jobs run on schedule, failures alert an owner, and no duplicate jobs process payments or schedules.", "action": "TEST", "owner": "Backend / Hosting", "status": "Not Started", "url": ""}, {"id": "OPS-03", "phase": "12. Cutover & Operations", "priority": "HIGH", "required": "YES", "area": "Monitoring", "task": "Enable application error tracking, server monitoring, payment webhook alerts, player/CDN alerts and 4xx/5xx dashboards.", "acceptance": "A named owner receives and can act on a test alert from each critical system.", "action": "FIX", "owner": "Hosting / Backend", "status": "Not Started", "url": ""}, {"id": "OPS-04", "phase": "12. Cutover & Operations", "priority": "BLOCKER", "required": "YES", "area": "Rollback", "task": "Document and test a rollback plan that does not restore the compromised old application.", "acceptance": "DNS/origin rollback target is clean, backups are usable, and decision authority is named.", "action": "TEST", "owner": "Hosting / Product", "status": "Not Started", "url": ""}, {"id": "OPS-05", "phase": "12. Cutover & Operations", "priority": "HIGH", "required": "YES", "area": "Launch", "task": "Prepare a cutover runbook with freeze, final sync, DNS/CDN change, smoke tests, monitoring, rollback triggers and responsible people.", "acceptance": "Runbook is approved and can be followed step by step without relying on memory.", "action": "VERIFY", "owner": "Product / Hosting", "status": "Not Started", "url": ""}, {"id": "OPS-06", "phase": "12. Cutover & Operations", "priority": "BLOCKER", "required": "YES", "area": "Smoke Test", "task": "Immediately after cutover test homepage, menus, login, registration, reset, profile, search or hidden state, free video, subscription, PPV and Live TV.", "acceptance": "Every launch-critical smoke test passes on the production domain.", "action": "TEST", "owner": "QA / Product", "status": "Not Started", "url": ""}, {"id": "OPS-07", "phase": "12. Cutover & Operations", "priority": "HIGH", "required": "YES", "area": "Analytics", "task": "Verify analytics, consent choices, video events, purchase conversions and error tracking on the production domain.", "acceptance": "Events arrive once with correct page/domain/content identifiers and respect consent settings.", "action": "TEST", "owner": "Marketing / QA", "status": "Not Started", "url": ""}, {"id": "OPS-08", "phase": "12. Cutover & Operations", "priority": "MEDIUM", "required": "NO", "area": "Post-launch", "task": "Run smoke tests at launch, 1, 4, 12, 24, 48 and 72 hours.", "acceptance": "Results and incidents are recorded; critical failures trigger the documented rollback/escalation process.", "action": "TEST", "owner": "QA / Operations", "status": "Not Started", "url": ""}, {"id": "OPS-09", "phase": "12. Cutover & Operations", "priority": "MEDIUM", "required": "NO", "area": "Support", "task": "Prepare a support path for login, password, payment and playback incidents.", "acceptance": "Support contact, escalation owner, diagnostic questions and refund/access procedures are documented.", "action": "VERIFY", "owner": "Support / Product", "status": "Not Started", "url": ""}];
const CSRF = <?php echo json_encode($csrf, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
let sharedState = {};
let saveTimers = {};
const personInput = document.getElementById('person');
personInput.value = localStorage.getItem('e360tv-checklist-name') || '';
personInput.addEventListener('input', () => localStorage.setItem('e360tv-checklist-name', personInput.value));

function esc(s){return String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]))}
function statusFor(t){return sharedState[t.id]?.status || t.status}
function doneFor(t){return statusFor(t)==='Done'}
function setSync(message, ok=true){const el=document.getElementById('sync');el.textContent=message;el.className='sync '+(ok?'ok':'bad')}

async function api(payload=null){
  const options = payload ? {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({...payload, csrf:CSRF})
  } : {method:'GET'};
  const response = await fetch('api.php', options);
  const data = await response.json().catch(()=>({ok:false,error:'Invalid server response.'}));
  if(!response.ok || !data.ok) throw new Error(data.error || 'Request failed.');
  return data;
}

async function loadState(showMessage=true){
  try{
    if(showMessage) setSync('Refreshing shared state…');
    const result = await api();
    sharedState = result.state || {};
    render();
    setSync('Shared state current');
  }catch(error){
    setSync(error.message, false);
  }
}

async function saveTask(t, status, notes){
  try{
    setSync('Saving…');
    const result = await api({
      action:'save_task',
      task_id:t.id,
      status,
      notes,
      updated_by:personInput.value.trim() || 'Anonymous'
    });
    sharedState[t.id] = result.task;
    updateSummary();
    updateTaskMeta(t.id);
    setSync('Saved for everyone');
  }catch(error){
    setSync(error.message, false);
    alert('Could not save this task: '+error.message);
  }
}

function queueNotesSave(t, status, notes){
  clearTimeout(saveTimers[t.id]);
  saveTimers[t.id] = setTimeout(()=>saveTask(t, status, notes), 700);
}

function updateTaskMeta(taskId){
  const node = document.querySelector(`[data-task-id="${taskId}"] .updated`);
  const item = sharedState[taskId];
  if(!node || !item) return;
  node.textContent = item.updated_at ? `Last updated by ${item.updated_by || 'Anonymous'} at ${new Date(item.updated_at).toLocaleString()}` : '';
}

function render(){
  const q=document.getElementById('search').value.toLowerCase();
  const p=document.getElementById('priority').value;
  const r=document.getElementById('requiredFilter').value;
  const sf=document.getElementById('statusFilter').value;
  const root=document.getElementById('tasks');
  root.innerHTML='';
  let phase='';
  DATA.forEach(t=>{
    const st=statusFor(t);
    const text=(t.id+' '+t.phase+' '+t.area+' '+t.task+' '+t.acceptance+' '+t.owner).toLowerCase();
    if((q&&!text.includes(q))||(p&&t.priority!==p)||(r&&t.required!==r)||(sf&&st!==sf)) return;
    if(t.phase!==phase){
      phase=t.phase;
      const h=document.createElement('div');
      h.className='phase';
      h.textContent=phase;
      root.appendChild(h);
    }
    const d=document.createElement('div');
    d.dataset.taskId=t.id;
    d.className='task '+t.priority.toLowerCase()+(doneFor(t)?' done':'');
    const saved=sharedState[t.id]||{};
    const url=t.url?`<div class="small"><a href="${esc(t.url)}" target="_blank">Open reference</a></div>`:'';
    const updated=saved.updated_at ? `Last updated by ${esc(saved.updated_by||'Anonymous')} at ${esc(new Date(saved.updated_at).toLocaleString())}` : '';
    d.innerHTML=`
      <div><div class="id">${esc(t.id)}</div><span class="badge ${t.priority.toLowerCase()}">${esc(t.priority)}</span><div class="required">${t.required==='YES'?'REQUIRED':''}</div></div>
      <div><b>${esc(t.action)}</b><div class="owner">${esc(t.area)}</div>${url}</div>
      <div><div class="task-title">${esc(t.task)}</div><div class="owner">Owner: ${esc(t.owner)}</div></div>
      <div class="accept"><b>Done when:</b>${esc(t.acceptance)}</div>
      <div>
        <select class="status">${['Not Started','In Progress','Blocked','Ready for Retest','Done','Not Applicable'].map(x=>`<option ${x===st?'selected':''}>${x}</option>`).join('')}</select>
        <textarea class="notes" placeholder="Shared notes / evidence">${esc(saved.notes||'')}</textarea>
        <div class="updated">${updated}</div>
      </div>`;
    const statusEl=d.querySelector('.status');
    const notesEl=d.querySelector('.notes');
    statusEl.onchange=()=>saveTask(t,statusEl.value,notesEl.value);
    notesEl.oninput=()=>queueNotesSave(t,statusEl.value,notesEl.value);
    root.appendChild(d);
  });
  updateSummary();
}

function updateSummary(){
  const total=DATA.length;
  const req=DATA.filter(t=>t.required==='YES').length;
  const done=DATA.filter(doneFor).length;
  const blockers=DATA.filter(t=>t.priority==='BLOCKER'&&t.required==='YES'&&!doneFor(t)&&statusFor(t)!=='Not Applicable').length;
  const reqOpen=DATA.filter(t=>t.required==='YES'&&!doneFor(t)&&statusFor(t)!=='Not Applicable').length;
  document.getElementById('total').textContent=total;
  document.getElementById('required').textContent=req;
  document.getElementById('done').textContent=done;
  document.getElementById('blockers').textContent=blockers;
  document.getElementById('launchStatus').textContent=reqOpen===0?'READY FOR SIGN-OFF':'NOT READY';
  document.getElementById('bar').style.width=(total?done/total*100:0)+'%';
}

document.getElementById('refreshBtn').onclick=()=>loadState(true);
document.getElementById('resetBtn').onclick=async()=>{
  const confirmation=prompt('Type RESET to clear the shared checklist. A backup will be created first.');
  if(confirmation!=='RESET') return;
  try{
    setSync('Resetting…');
    await api({action:'reset_all',confirm:'RESET'});
    sharedState={};
    render();
    setSync('Shared checklist reset');
  }catch(error){
    setSync(error.message,false);
    alert(error.message);
  }
};

['search','priority','requiredFilter','statusFilter'].forEach(id=>{
  document.getElementById(id).addEventListener('input',render);
});

loadState(true);
setInterval(()=>{
  if(!document.querySelector('textarea:focus') && !document.querySelector('select:focus')){
    loadState(false);
  }
},30000);
</script>
</body>
</html>
