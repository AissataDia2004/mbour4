<?php
// login.php
session_start();
require_once 'config/users_config.php';

$error = '';

// Déjà connecté → rediriger vers le géoportail
if (isset($_SESSION['user']) && isset($_SESSION['expire']) && time() < $_SESSION['expire']) {
    header('Location: index.php');
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (isset(USERS[$username]) && password_verify($password, USERS[$username]['password'])) {
        $_SESSION['user']    = $username;
        $_SESSION['role']    = USERS[$username]['role'];
        $_SESSION['nom']     = USERS[$username]['nom'];
        $_SESSION['icon']    = USERS[$username]['icon'];
        $_SESSION['expire']  = time() + SESSION_DURATION;

        header('Location: index.php');
        exit;
    } else {
        $error = 'Identifiant ou mot de passe incorrect.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Géoportail — République du Sénégal</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --vert:     #1a7a3c;
            --vert-dk:  #0f5a2a;
            --jaune:    #F5C518;
            --rouge:    #C8102E;
            --or:       #D4AF37;
            --dark:     #0c1a0f;
            --surface:  #132016;
            --card:     rgba(255,255,255,0.04);
            --border:   rgba(245,197,24,0.2);
            --text:     #f0f7f2;
            --muted:    #89b898;
            --error:    #e05252;
        }

        body {
            min-height: 100vh;
            background-color: var(--dark);
            background-image:
                radial-gradient(ellipse 90% 70% at 50% -5%, rgba(26,122,60,0.5) 0%, transparent 65%),
                radial-gradient(ellipse 40% 40% at 95% 80%, rgba(200,16,46,0.08) 0%, transparent 60%),
                url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%231a7a3c' fill-opacity='0.04'%3E%3Cpath d='M50 50c0-5.523 4.477-10 10-10s10 4.477 10 10-4.477 10-10 10c0 5.523-4.477 10-10 10s-10-4.477-10-10 4.477-10 10-10zM10 10c0-5.523 4.477-10 10-10s10 4.477 10 10-4.477 10-10 10c0 5.523-4.477 10-10 10S0 25.523 0 20s4.477-10 10-10z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Outfit', sans-serif;
            padding: 2rem 1rem;
        }

        .wrapper {
            width: 100%;
            max-width: 900px;
            animation: fadeUp 0.7s cubic-bezier(.22,1,.36,1) both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(32px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ===== HEADER OFFICIEL ===== */
        .official-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .flag-bar {
            display: flex;
            height: 6px;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 1.8rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.4);
        }
        .flag-bar .f1 { flex: 1; background: var(--vert); }
        .flag-bar .f2 { flex: 1; background: #FDEF42; }
        .flag-bar .f3 { flex: 1; background: var(--rouge); }

        .republic-badge {
            display: inline-flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .emblem-wrap {
            position: relative;
            width: 72px;
            height: 48px;
        }

        .emblem-ring {
            width: 72px; height: 48px;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0,0,0,0.4);
        }

        .emblem-inner { display: none; }

        .republic-text {
            text-align: left;
        }

        .republic-text .line1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 0.75rem;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--muted);
            display: block;
        }

        .republic-text .line2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.9rem;
            font-weight: 700;
            color: var(--text);
            line-height: 1.1;
            display: block;
        }

        .republic-text .line3 {
            font-size: 0.72rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--jaune);
            display: block;
            margin-top: 2px;
        }

        .device-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .device-line {
            flex: 1;
            max-width: 120px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--or), transparent);
        }

        .device-text {
            font-family: 'Cormorant Garamond', serif;
            font-size: 0.85rem;
            letter-spacing: 0.35em;
            text-transform: uppercase;
            color: var(--or);
        }

        .subtitle-block {
            font-size: 0.8rem;
            color: var(--muted);
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        /* ===== LAYOUT PRINCIPAL ===== */
        .main-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 32px 80px rgba(0,0,0,0.6), 0 0 0 1px rgba(212,175,55,0.1);
        }

        /* ===== PANNEAU GAUCHE (INFO) ===== */
        .left-panel {
            background: linear-gradient(160deg, var(--vert-dk) 0%, var(--dark) 100%);
            padding: 3rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .left-panel::before {
            content: '';
            position: absolute;
            top: -80px; right: -80px;
            width: 250px; height: 250px;
            border-radius: 50%;
            border: 40px solid rgba(245,197,24,0.06);
            pointer-events: none;
        }

        .left-panel::after {
            content: '';
            position: absolute;
            bottom: -60px; left: -60px;
            width: 200px; height: 200px;
            border-radius: 50%;
            border: 30px solid rgba(26,122,60,0.15);
            pointer-events: none;
        }

        .portal-title {
            position: relative;
            z-index: 1;
        }

        .portal-label {
            display: inline-block;
            font-size: 0.68rem;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--jaune);
            background: rgba(245,197,24,0.1);
            border: 1px solid rgba(245,197,24,0.25);
            border-radius: 20px;
            padding: 0.3rem 0.8rem;
            margin-bottom: 1.2rem;
        }

        .portal-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.8rem;
            font-weight: 700;
            color: white;
            line-height: 1.05;
            margin-bottom: 0.8rem;
        }

        .portal-name span {
            color: var(--jaune);
        }

        .portal-desc {
            font-size: 0.88rem;
            color: var(--muted);
            line-height: 1.7;
        }

        .services-list {
            position: relative;
            z-index: 1;
            margin-top: 2rem;
        }

        .services-list h4 {
            font-size: 0.7rem;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.35);
            margin-bottom: 1rem;
        }

        .service-chip {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            margin-bottom: 0.6rem;
            transition: all 0.3s;
        }

        .service-chip:hover {
            background: rgba(26,122,60,0.2);
            border-color: rgba(26,122,60,0.4);
            transform: translateX(4px);
        }

        .chip-icon {
            width: 36px; height: 36px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
        }
        .chip-icon.green { background: rgba(26,122,60,0.3); }
        .chip-icon.gold  { background: rgba(212,175,55,0.2); }
        .chip-icon.red   { background: rgba(200,16,46,0.2); }

        .chip-text strong {
            display: block;
            font-size: 0.85rem;
            color: var(--text);
            font-weight: 500;
        }
        .chip-text span {
            font-size: 0.72rem;
            color: var(--muted);
        }

        .left-footer {
            position: relative;
            z-index: 1;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.06);
        }

        .left-footer p {
            font-size: 0.72rem;
            color: rgba(255,255,255,0.2);
            letter-spacing: 0.08em;
        }

        /* ===== PANNEAU DROIT (LOGIN) ===== */
        .right-panel {
            padding: 3rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-heading {
            margin-bottom: 2rem;
        }

        .login-heading h2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2rem;
            color: var(--text);
            font-weight: 600;
            margin-bottom: 0.3rem;
        }

        .login-heading p {
            font-size: 0.85rem;
            color: var(--muted);
        }

        .error-msg {
            background: rgba(224,82,82,0.1);
            border: 1px solid rgba(224,82,82,0.3);
            color: #f08080;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
            display: flex; align-items: center; gap: 0.5rem;
        }

        .field {
            margin-bottom: 1.25rem;
        }

        .field label {
            display: block;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 0.6rem;
        }

        .field-wrap {
            position: relative;
        }

        .field-wrap .icon {
            position: absolute;
            left: 1rem; top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            opacity: 0.5;
        }

        .field input {
            width: 100%;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 0.85rem 1rem 0.85rem 2.8rem;
            color: var(--text);
            font-family: 'Outfit', sans-serif;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.25s;
        }

        .field input:focus {
            border-color: var(--vert);
            background: rgba(26,122,60,0.06);
            box-shadow: 0 0 0 3px rgba(26,122,60,0.12);
        }

        .field input::placeholder { color: rgba(255,255,255,0.18); }

        .btn-login {
            width: 100%;
            padding: 0.95rem;
            background: linear-gradient(135deg, var(--vert) 0%, #22a050 100%);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            cursor: pointer;
            margin-top: 0.75rem;
            transition: all 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 0.5rem;
            box-shadow: 0 8px 24px rgba(26,122,60,0.35);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(26,122,60,0.45);
        }

        .btn-login:active { transform: translateY(0); }

        .divider {
            display: flex; align-items: center; gap: 1rem;
            margin: 1.75rem 0 1.25rem;
        }
        .divider-line { flex: 1; height: 1px; background: rgba(255,255,255,0.08); }
        .divider span { font-size: 0.72rem; color: rgba(255,255,255,0.2); letter-spacing: 0.12em; text-transform: uppercase; }

        .info-box {
            background: rgba(245,197,24,0.06);
            border: 1px solid rgba(245,197,24,0.2);
            border-radius: 10px;
            padding: 1rem 1.2rem;
        }

        .info-box p {
            font-size: 0.78rem;
            color: rgba(245,197,24,0.7);
            line-height: 1.6;
        }

        .info-box strong {
            color: var(--jaune);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 680px) {
            .main-layout { grid-template-columns: 1fr; }
            .left-panel { padding: 2rem 1.5rem; }
            .right-panel { padding: 2rem 1.5rem; }
            .portal-name { font-size: 2rem; }
        }
    </style>
</head>
<body>

<div class="wrapper">

    <!-- En-tête officielle -->
    <div class="official-header">
        <div class="flag-bar">
            <div class="f1"></div>
            <div class="f2"></div>
            <div class="f3"></div>
        </div>

        <div class="republic-badge">
            <div class="emblem-wrap">
                <div class="emblem-ring">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 600" width="72" height="48">
                        <rect width="300" height="600" fill="#00853F"/>
                        <rect x="300" width="300" height="600" fill="#FDEF42"/>
                        <rect x="600" width="300" height="600" fill="#E31B23"/>
                        <polygon points="450,210 462,247 501,247 470,270 481,307 450,285 419,307 430,270 399,247 438,247" fill="#00853F"/>
                    </svg>
                </div>
            </div>
            <div class="republic-text">
                <span class="line1">République du</span>
                <span class="line2">Sénégal</span>
                <span class="line3">Un Peuple · Un But · Une Foi</span>
            </div>
        </div>

        <div class="device-row">
            <div class="device-line"></div>
            <div class="device-text">Système d'information géographique</div>
            <div class="device-line"></div>
        </div>
        <p class="subtitle-block">Ministère de l'Urbanisme, du Logement et de l'Hygiène Publique</p>
    </div>

    <!-- Card principale -->
    <div class="main-layout">

        <!-- Panneau gauche -->
        <div class="left-panel">
            <div class="portal-title">
                <div class="portal-label">✦ Géoportail Officiel</div>
                <div class="portal-name">Commune<br>de <span>Thiès Ouest</span></div>
                <p class="portal-desc">Plateforme centralisée de gestion foncière et d'aménagement du territoire de la région de Thiès.</p>
            </div>

            <div class="services-list">
                <h4>Services habilités</h4>
                <div class="service-chip">
                    <div class="chip-icon green"></div>
                    <div class="chip-text">
                        <strong>Urbanisme</strong>
                        <span>Permis & Plans d'aménagement</span>
                    </div>
                </div>
                <div class="service-chip">
                    <div class="chip-icon gold"></div>
                    <div class="chip-text">
                        <strong>Domaine</strong>
                        <span>Gestion du patrimoine foncier</span>
                    </div>
                </div>
                <div class="service-chip">
                    <div class="chip-icon red"></div>
                    <div class="chip-text">
                        <strong>Cadastre</strong>
                        <span>Identification & enregistrement</span>
                    </div>
                </div>
            </div>

            <div class="left-footer">
                <p>🔒 Système sécurisé — Usage interne exclusivement</p>
                <p style="margin-top:0.3rem;">© 2026 Direction de l'Urbanisme de Thiès</p>
            </div>
        </div>

        <!-- Panneau droit (formulaire) -->
        <div class="right-panel">
            <div class="login-heading">
                <h2>Connexion</h2>
                <p>Accédez à votre espace sécurisé</p>
            </div>

            <!-- Erreur PHP: <?php if ($error): ?> -->
            <!-- <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div> -->
            <!-- <?php endif; ?> -->

            <form method="POST" autocomplete="off">
                <div class="field">
                    <label>Identifiant</label>
                    <div class="field-wrap">
                        <span class="icon">👤</span>
                        <input type="text" name="username"
                               placeholder="urbanisme / domaine / cadastre"
                               autofocus required>
                    </div>
                </div>

                <div class="field">
                    <label>Mot de passe</label>
                    <div class="field-wrap">
                        <span class="icon">🔑</span>
                        <input type="password" name="password"
                               placeholder="••••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    Se connecter →
                </button>
            </form>

            <div class="divider">
                <div class="divider-line"></div>
                <span>Information</span>
                <div class="divider-line"></div>
            </div>

            <div class="info-box">
                <p>Accès réservé aux agents habilités de la <strong>Direction de l'Urbanisme, du Domaine et du Cadastre</strong> de Thiès. Pour tout problème de connexion, contactez l'administrateur système.</p>
            </div>
        </div>

    </div>

</div>
</body>
</html>