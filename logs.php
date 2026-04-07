<?php
require_once 'config/auth.php';
requireLogin();
$user = getCurrentUser();

// Seul l'urbanisme peut accéder aux logs
if ($user['role'] !== 'editeur' || $_SESSION['user'] !== 'urbanisme') {
    header('Location: index.php');
    exit;
}

require_once 'config_supabase.php';

// Récupérer les logs depuis Supabase
$url = SUPABASE_URL . '/rest/v1/logs_activite?order=created_at.desc&limit=200';
$headers = [
    'apikey: '               . SUPABASE_ANON_KEY,
    'Authorization: Bearer ' . SUPABASE_ANON_KEY,
    'Content-Type: application/json'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER,     $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT,        15);
$resp     = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$logs = ($httpCode === 200) ? json_decode($resp, true) : [];

// Statistiques rapides
$stats = ['ajout' => 0, 'modification' => 0, 'suppression' => 0];
foreach ($logs as $log) {
    $action = $log['action'] ?? '';
    if (isset($stats[$action])) $stats[$action]++;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs d'activité — Géoportail de Thiès</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --vert:    #1a7a3c;
            --vert-dk: #0f5a2a;
            --vert-lt: #22a050;
            --jaune:   #F5C518;
            --rouge:   #C8102E;
            --dark:    #0c1a0f;
            --surface: #f7f9f7;
            --white:   #ffffff;
            --text:    #1a2e1e;
            --muted:   #5a7a62;
            --border:  #dde8de;
            --shadow:  rgba(0,0,0,0.08);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--surface);
            color: var(--text);
            min-height: 100vh;
        }

        /* TOPBAR */
        .topbar {
            background: var(--dark);
            color: rgba(255,255,255,0.6);
            font-size: 0.72rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 0.4rem 0;
            border-bottom: 1px solid rgba(245,197,24,0.2);
        }
        .topbar-inner {
            max-width: 1400px; margin: 0 auto; padding: 0 2rem;
            display: flex; justify-content: space-between; align-items: center;
        }

        /* NAVBAR */
        .navbar {
            background: white;
            border-bottom: 3px solid transparent;
            border-image: linear-gradient(90deg, var(--vert) 33%, #FDEF42 33% 66%, var(--rouge) 66%) 1;
            box-shadow: 0 2px 16px var(--shadow);
            position: sticky; top: 0; z-index: 1000;
        }
        .nav-inner {
            max-width: 1400px; margin: 0 auto; padding: 0.6rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
        }
        .nav-brand {
            display: flex; align-items: center; gap: 1rem; text-decoration: none;
        }
        .brand-text .b1 { font-size: 0.6rem; letter-spacing: 0.2em; text-transform: uppercase; color: var(--muted); display: block; }
        .brand-text .b2 { font-family: 'Cormorant Garamond', serif; font-size: 1.3rem; font-weight: 700; color: var(--vert-dk); display: block; line-height: 1.1; }
        .brand-text .b3 { font-size: 0.65rem; color: var(--muted); display: block; }

        .nav-right { display: flex; align-items: center; gap: 1rem; }
        .nav-link {
            text-decoration: none; font-size: 0.85rem; font-weight: 500;
            color: var(--muted); padding: 0.5rem 0.9rem; border-radius: 6px; transition: all 0.2s;
        }
        .nav-link:hover { background: #f0f7f2; color: var(--vert); }
        .nav-link.active { background: #e8f5ec; color: var(--vert-dk); font-weight: 600; }

        .user-chip {
            display: flex; align-items: center; gap: 0.6rem;
            padding: 0.4rem 1rem 0.4rem 0.5rem;
            background: #f0f7f2; border: 1px solid #c8e6d0;
            border-radius: 40px; font-size: 0.82rem;
        }
        .user-chip .name { font-weight: 600; color: var(--vert-dk); }
        .user-chip .role { color: var(--muted); font-size: 0.72rem; }
        .btn-logout {
            background: none; border: 1px solid #fecaca; color: #dc2626;
            font-size: 0.78rem; padding: 0.4rem 0.9rem; border-radius: 6px;
            cursor: pointer; font-family: 'Outfit', sans-serif; transition: all 0.2s;
        }
        .btn-logout:hover { background: #fee2e2; }

        /* PAGE HEADER */
        .page-header {
            background: linear-gradient(135deg, var(--vert-dk) 0%, var(--vert) 60%, #22a050 100%);
            color: white; padding: 2rem 0; position: relative; overflow: hidden;
        }
        .page-header::before {
            content: ''; position: absolute; right: -100px; top: -100px;
            width: 400px; height: 400px; border-radius: 50%;
            border: 60px solid rgba(255,255,255,0.04);
        }
        .header-inner {
            max-width: 1400px; margin: 0 auto; padding: 0 2rem;
            display: flex; align-items: center; justify-content: space-between;
            position: relative; z-index: 1;
        }
        .header-left .eyebrow {
            font-size: 0.7rem; letter-spacing: 0.3em; text-transform: uppercase;
            color: rgba(245,197,24,0.8); margin-bottom: 0.4rem;
        }
        .header-left h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.2rem; font-weight: 700; line-height: 1.1;
        }
        .header-left p { font-size: 0.9rem; color: rgba(255,255,255,0.65); margin-top: 0.4rem; }

        /* STATS CARDS */
        .stats-row {
            display: flex; gap: 1rem;
        }
        .stat-card {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px; padding: 0.9rem 1.4rem;
            text-align: center; backdrop-filter: blur(10px);
            min-width: 110px;
        }
        .stat-card .num {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2rem; font-weight: 700; display: block; line-height: 1;
        }
        .stat-card .lbl { font-size: 0.7rem; color: rgba(255,255,255,0.6); letter-spacing: 0.08em; }
        .num-green  { color: #4ade80; }
        .num-orange { color: #fb923c; }
        .num-red    { color: #f87171; }

        /* CONTENT */
        .content {
            max-width: 1400px; margin: 0 auto; padding: 2rem;
        }

        /* FILTRES */
        .filters-bar {
            background: white; border-radius: 14px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 24px var(--shadow);
            padding: 1rem 1.5rem;
            display: flex; align-items: center; gap: 1rem;
            flex-wrap: wrap; margin-bottom: 1.5rem;
        }
        .filters-bar label { font-size: 0.78rem; font-weight: 600; color: var(--muted); }
        .filters-bar select, .filters-bar input {
            padding: 0.5rem 0.8rem; border: 1px solid var(--border);
            border-radius: 8px; font-family: 'Outfit', sans-serif;
            font-size: 0.85rem; color: var(--text); background: white; outline: none;
        }
        .filters-bar select:focus, .filters-bar input:focus { border-color: var(--vert); }
        .btn-reset {
            padding: 0.5rem 1rem; background: #f3f4f6; border: 1px solid var(--border);
            border-radius: 8px; font-family: 'Outfit', sans-serif;
            font-size: 0.82rem; cursor: pointer; color: var(--muted); transition: all 0.2s;
        }
        .btn-reset:hover { background: #e5e7eb; }
        .btn-export {
            margin-left: auto; padding: 0.5rem 1.2rem;
            background: var(--vert); color: white; border: none;
            border-radius: 8px; font-family: 'Outfit', sans-serif;
            font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
        }
        .btn-export:hover { background: var(--vert-dk); }

        /* TABLE */
        .table-wrap {
            background: white; border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 24px var(--shadow);
            overflow: hidden;
        }
        .table-header {
            padding: 1rem 1.5rem; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .table-header h3 {
            font-size: 0.95rem; font-weight: 600; color: var(--vert-dk);
        }
        .table-header .count {
            font-size: 0.78rem; color: var(--muted);
            background: #f0f7f2; padding: 0.3rem 0.8rem;
            border-radius: 20px; border: 1px solid #c8e6d0;
        }

        table {
            width: 100%; border-collapse: collapse;
        }
        thead th {
            padding: 0.85rem 1rem; text-align: left;
            font-size: 0.72rem; font-weight: 700;
            letter-spacing: 0.12em; text-transform: uppercase;
            color: var(--muted); background: #fafcfa;
            border-bottom: 1px solid var(--border);
        }
        tbody tr {
            border-bottom: 1px solid #f0f4f0;
            transition: background 0.15s;
        }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #f7fdf8; }
        tbody td {
            padding: 0.85rem 1rem; font-size: 0.85rem; vertical-align: middle;
        }

        /* BADGES */
        .badge {
            display: inline-flex; align-items: center; gap: 0.3rem;
            padding: 0.3rem 0.7rem; border-radius: 20px;
            font-size: 0.75rem; font-weight: 600; white-space: nowrap;
        }
        .badge-ajout        { background: #dcfce7; color: #15803d; }
        .badge-modification { background: #fef3c7; color: #b45309; }
        .badge-suppression  { background: #fee2e2; color: #dc2626; }

        .badge-urbanisme  { background: #e8f5ec; color: #0f5a2a; }
        .badge-cadastre   { background: #eff6ff; color: #1d4ed8; }
        .badge-domaine    { background: #fdf4ff; color: #7e22ce; }
        .badge-gouverneur { background: #fff7ed; color: #c2410c; }
        .badge-dgia       { background: #f0fdfa; color: #0f766e; }
        .badge-editeur    { background: #e8f5ec; color: #0f5a2a; }
        .badge-visiteur   { background: #f3f4f6; color: #6b7280; }

        .details-btn {
            padding: 0.3rem 0.7rem; background: #f0f7f2;
            border: 1px solid #c8e6d0; border-radius: 6px;
            font-size: 0.75rem; cursor: pointer; color: var(--vert);
            font-family: 'Outfit', sans-serif; transition: all 0.2s;
        }
        .details-btn:hover { background: #e8f5ec; }

        .empty-state {
            text-align: center; padding: 3rem; color: var(--muted); font-size: 0.9rem;
        }

        /* MODAL DÉTAILS */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.5);
            z-index: 9999; display: none;
            align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal {
            background: white; border-radius: 16px; padding: 2rem;
            max-width: 500px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: fadeUp 0.2s ease;
        }
        @keyframes fadeUp {
            from { opacity:0; transform:translateY(16px); }
            to   { opacity:1; transform:translateY(0); }
        }
        .modal h3 {
            font-size: 1rem; font-weight: 700; color: var(--vert-dk);
            margin-bottom: 1rem; padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border);
        }
        .modal-row {
            display: flex; justify-content: space-between;
            padding: 0.5rem 0; border-bottom: 1px solid #f0f4f0;
            font-size: 0.85rem;
        }
        .modal-row:last-of-type { border-bottom: none; }
        .modal-row .mk { color: var(--muted); font-size: 0.78rem; }
        .modal-row .mv { font-weight: 600; max-width: 60%; text-align: right; word-break: break-word; }
        .modal-close {
            width: 100%; margin-top: 1.25rem; padding: 0.75rem;
            background: var(--vert); color: white; border: none;
            border-radius: 8px; font-family: 'Outfit', sans-serif;
            font-weight: 600; cursor: pointer; transition: background 0.2s;
        }
        .modal-close:hover { background: var(--vert-dk); }

        /* FOOTER */
        .site-footer {
            background: var(--dark); color: rgba(255,255,255,0.4);
            margin-top: 3rem; padding: 1.5rem 0;
            border-top: 3px solid transparent;
            border-image: linear-gradient(90deg, var(--vert) 33%, #FDEF42 33% 66%, var(--rouge) 66%) 1;
        }
        .footer-inner-simple {
            max-width: 1400px; margin: 0 auto; padding: 0 2rem;
            display: flex; justify-content: space-between; align-items: center;
            font-size: 0.75rem;
        }

        /* NO PRINT */
        @media print { .filters-bar, .btn-export, .details-btn, .navbar, .topbar, .site-footer { display: none; } }
    </style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
    <div class="topbar-inner">
        <span>🇸🇳 République du Sénégal — Un Peuple · Un But · Une Foi</span>
        <span>Ministère de l'Urbanisme &nbsp;|&nbsp; Direction de l'Urbanisme de Thiès</span>
    </div>
</div>

<!-- Navbar -->
<nav class="navbar">
    <div class="nav-inner">
        <a href="index.php" class="nav-brand">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 600" width="56" height="38" style="border-radius:4px; box-shadow:0 2px 10px rgba(0,0,0,0.25);">
                <rect width="300" height="600" fill="#00853F"/>
                <rect x="300" width="300" height="600" fill="#FDEF42"/>
                <rect x="600" width="300" height="600" fill="#E31B23"/>
                <polygon points="450,210 462,247 501,247 470,270 481,307 450,285 419,307 430,270 399,247 438,247" fill="#00853F"/>
            </svg>
            <div class="brand-text">
                <span class="b1">République du Sénégal</span>
                <span class="b2">Géoportail de Thiès</span>
                <span class="b3">Direction de l'Urbanisme, du Domaine & du Cadastre</span>
            </div>
        </a>
        <div class="nav-right">
            <a href="index.php"  class="nav-link">← Géoportail</a>
            <a href="logs.php"   class="nav-link active">📋 Logs</a>
            <div class="user-chip">
                <div>
                    <div class="name"><?= htmlspecialchars($user['nom']) ?></div>
                    <div class="role"><?= htmlspecialchars($user['role']) ?></div>
                </div>
            </div>
            <a href="logout.php"><button class="btn-logout">↩ Déconnexion</button></a>
        </div>
    </div>
</nav>

<!-- Page Header -->
<div class="page-header">
    <div class="header-inner">
        <div class="header-left">
            <div class="eyebrow">✦ Traçabilité & Audit</div>
            <h1>Logs d'activité</h1>
            <p>Historique complet des actions effectuées sur le géoportail</p>
        </div>
        <div class="stats-row">
            <div class="stat-card">
                <span class="num num-green"><?= $stats['ajout'] ?></span>
                <span class="lbl">Ajouts</span>
            </div>
            <div class="stat-card">
                <span class="num num-orange"><?= $stats['modification'] ?></span>
                <span class="lbl">Modifications</span>
            </div>
            <div class="stat-card">
                <span class="num num-red"><?= $stats['suppression'] ?></span>
                <span class="lbl">Suppressions</span>
            </div>
            <div class="stat-card">
                <span class="num" style="color:var(--jaune);"><?= count($logs) ?></span>
                <span class="lbl">Total</span>
            </div>
        </div>
    </div>
</div>

<!-- Contenu -->
<div class="content">

    <!-- Filtres -->
    <div class="filters-bar">
        <label>Action</label>
        <select id="filterAction" onchange="filterLogs()">
            <option value="">Toutes</option>
            <option value="ajout">Ajout</option>
            <option value="modification">Modification</option>
            <option value="suppression">Suppression</option>
        </select>

        <label>Utilisateur</label>
        <select id="filterUser" onchange="filterLogs()">
            <option value="">Tous</option>
            <option value="Service Urbanisme">Urbanisme</option>
            <option value="Service Cadastre">Cadastre</option>
            <option value="Service Domaine">Domaine</option>
            <option value="Gouverneur">Gouverneur</option>
            <option value="DGUA">DGUA</option>
        </select>

        <label>Recherche</label>
        <input type="text" id="filterSearch" placeholder="N° parcelle, nom..." oninput="filterLogs()">

        <button class="btn-reset" onclick="resetFilters()">↺ Réinitialiser</button>
        <button class="btn-export" onclick="exportCSV()">⬇ Exporter CSV</button>
    </div>

    <!-- Table -->
    <div class="table-wrap">
        <div class="table-header">
            <h3>📋 Journal des activités</h3>
            <span class="count" id="logCount"><?= count($logs) ?> entrée(s)</span>
        </div>

        <?php if (empty($logs)): ?>
            <div class="empty-state">
                <p>Aucun log enregistré pour le moment.</p>
            </div>
        <?php else: ?>
        <table id="logsTable">
            <thead>
                <tr>
                    <th>Date & Heure</th>
                    <th>Utilisateur</th>
                    <th>Rôle</th>
                    <th>Action</th>
                    <th>Parcelle ID</th>
                    <th>Adresse IP</th>
                    <th>Détails</th>
                </tr>
            </thead>
            <tbody id="logsBody">
                <?php foreach ($logs as $log):
                    $dt      = new DateTime($log['created_at']);
                    $dt->setTimezone(new DateTimeZone('Africa/Dakar'));
                    $dateStr = $dt->format('d/m/Y H:i:s');
                    $action  = $log['action'] ?? '';
                    $details = $log['details'] ?? [];
                    if (is_string($details)) $details = json_decode($details, true) ?? [];
                ?>
                <tr
                    data-action="<?= htmlspecialchars($action) ?>"
                    data-user="<?= htmlspecialchars($log['utilisateur'] ?? '') ?>"
                    data-search="<?= htmlspecialchars(strtolower(json_encode($log))) ?>">
                    <td style="font-size:0.8rem; color:var(--muted);">
                        📅 <?= $dateStr ?>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($log['utilisateur'] ?? 'N/A') ?></strong>
                    </td>
                    <td>
                        <?php
                            $role = $log['role'] ?? '';
                            $roleClass = 'badge-' . strtolower(str_replace(' ', '', $role));
                        ?>
                        <span class="badge <?= $roleClass ?>">
                            <?= htmlspecialchars($role) ?>
                        </span>
                    </td>
                    <td>
                        <?php
                            $badgeClass = match($action) {
                                'ajout'        => 'badge-ajout',
                                'modification' => 'badge-modification',
                                'suppression'  => 'badge-suppression',
                                default        => ''
                            };
                            $icon = match($action) {
                                'ajout'        => '➕',
                                'modification' => '✏️',
                                'suppression'  => '🗑️',
                                default        => '•'
                            };
                        ?>
                        <span class="badge <?= $badgeClass ?>">
                            <?= $icon ?> <?= ucfirst(htmlspecialchars($action)) ?>
                        </span>
                    </td>
                    <td>
                        <strong>#<?= htmlspecialchars((string)($log['enregistrement_id'] ?? 'N/A')) ?></strong>
                    </td>
                    <td style="font-size:0.78rem; color:var(--muted);">
                        <?= htmlspecialchars($log['ip'] ?? 'N/A') ?>
                    </td>
                    <td>
                        <?php if (!empty($details)): ?>
                        <button class="details-btn"
                            onclick='showDetails(<?= json_encode($details, JSON_UNESCAPED_UNICODE) ?>, "<?= htmlspecialchars($log['utilisateur'] ?? '') ?>", "<?= htmlspecialchars($action) ?>", "<?= $dateStr ?>")'>
                            🔍 Voir
                        </button>
                        <?php else: ?>
                        <span style="color:var(--muted); font-size:0.78rem;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal détails -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModal(event)">
    <div class="modal" id="modalBox">
        <h3 id="modalTitle">Détails de l'action</h3>
        <div id="modalContent"></div>
        <button class="modal-close" onclick="closeDetails()">Fermer</button>
    </div>
</div>

<!-- Footer -->
<footer class="site-footer">
    <div class="footer-inner-simple">
        <span>© 2026 Direction de l'Urbanisme de Thiès — Géoportail v2.0</span>
        <span>🔒 Accès restreint — Service Urbanisme uniquement</span>
    </div>
</footer>

<script>
// ===== FILTRES =====
function filterLogs() {
    const action  = document.getElementById('filterAction').value.toLowerCase();
    const user    = document.getElementById('filterUser').value.toLowerCase();
    const search  = document.getElementById('filterSearch').value.toLowerCase();
    const rows    = document.querySelectorAll('#logsBody tr');

    let visible = 0;
    rows.forEach(row => {
        const rowAction = row.dataset.action.toLowerCase();
        const rowUser   = row.dataset.user.toLowerCase();
        const rowSearch = row.dataset.search.toLowerCase();

        const matchAction = !action || rowAction === action;
        const matchUser   = !user   || rowUser.includes(user);
        const matchSearch = !search || rowSearch.includes(search);

        if (matchAction && matchUser && matchSearch) {
            row.style.display = '';
            visible++;
        } else {
            row.style.display = 'none';
        }
    });

    document.getElementById('logCount').textContent = visible + ' entrée(s)';
}

function resetFilters() {
    document.getElementById('filterAction').value = '';
    document.getElementById('filterUser').value   = '';
    document.getElementById('filterSearch').value = '';
    filterLogs();
}

// ===== MODAL DÉTAILS =====
function showDetails(details, utilisateur, action, date) {
    const icons = { ajout: '➕', modification: '✏️', suppression: '🗑️' };
    document.getElementById('modalTitle').textContent =
        (icons[action] || '•') + ' ' + utilisateur + ' — ' + date;

    let html = '';
    if (details && typeof details === 'object') {
        for (const [key, val] of Object.entries(details)) {
            const label = key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' ');
            html += `<div class="modal-row">
                <span class="mk">${label}</span>
                <span class="mv">${val !== null && val !== '' ? val : '<em style="color:#9ca3af;">vide</em>'}</span>
            </div>`;
        }
    }

    document.getElementById('modalContent').innerHTML = html || '<p style="color:var(--muted);">Aucun détail disponible.</p>';
    document.getElementById('modalOverlay').classList.add('open');
}

function closeDetails() {
    document.getElementById('modalOverlay').classList.remove('open');
}

function closeModal(e) {
    if (e.target === document.getElementById('modalOverlay')) closeDetails();
}

// ===== EXPORT CSV =====
function exportCSV() {
    const rows   = document.querySelectorAll('#logsBody tr:not([style*="none"])');
    const lines  = [['Date & Heure', 'Utilisateur', 'Rôle', 'Action', 'Parcelle ID', 'IP']];

    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        lines.push([
            cells[0]?.innerText.replace('📅 ', '').trim() || '',
            cells[1]?.innerText.trim() || '',
            cells[2]?.innerText.trim() || '',
            cells[3]?.innerText.trim() || '',
            cells[4]?.innerText.trim() || '',
            cells[5]?.innerText.trim() || '',
        ]);
    });

    const csv  = lines.map(r => r.map(c => `"${c.replace(/"/g, '""')}"`).join(',')).join('\n');
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = `logs_geoportail_${new Date().toISOString().slice(0,10)}.csv`;
    a.click();
    URL.revokeObjectURL(url);
}
</script>

</body>
</html>