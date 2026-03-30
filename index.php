<?php
require_once 'config/auth.php';
requireLogin();
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Géoportail — Direction de l'Urbanisme de Thiès</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --vert:     #1a7a3c;
            --vert-dk:  #0f5a2a;
            --vert-lt:  #22a050;
            --jaune:    #F5C518;
            --or:       #D4AF37;
            --rouge:    #C8102E;
            --dark:     #0c1a0f;
            --surface:  #f7f9f7;
            --white:    #ffffff;
            --text:     #1a2e1e;
            --muted:    #5a7a62;
            --border:   #dde8de;
            --shadow:   rgba(0,0,0,0.08);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--surface);
            color: var(--text);
            min-height: 100vh;
        }

        /* ===========================
           TOPBAR OFFICIELLE
           =========================== */
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
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar a { color: rgba(255,255,255,0.4); text-decoration: none; }
        .topbar a:hover { color: var(--jaune); }

        /* ===========================
        NAVBAR
        =========================== */

        .navbar {
            background: white;
            border-bottom: 3px solid transparent;
            border-image: linear-gradient(90deg, var(--vert) 33%, #FDEF42 33% 66%, var(--rouge) 66%) 1;
            box-shadow: 0 2px 16px var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        /* conteneur navbar */
        .nav-inner{
            display:flex;
            align-items:center;
            justify-content:space-between;
            padding:0.6rem 1.5rem;
        }

        /* ===========================
        LOGO
        =========================== */

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
        }

        .brand-emblem {
            width: 56px;
            height: 38px;
            border-radius: 4px;
            overflow: hidden;
            flex-shrink: 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.25);
            display: flex;
        }

        .brand-text .b1 {
            font-size: 0.6rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--muted);
            display: block;
        }

        .brand-text .b2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--vert-dk);
            display: block;
            line-height: 1.1;
        }

        .brand-text .b3 {
            font-size: 0.65rem;
            color: var(--muted);
            display: block;
        }

        /* ===========================
        MENU
        =========================== */

        .nav-menu{
            display:flex;
            align-items:center;
            gap:2rem;
        }

        .nav-center {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .nav-link {
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--muted);
            padding: 0.5rem 0.9rem;
            border-radius: 6px;
            transition: all 0.2s;
            letter-spacing: 0.02em;
        }

        .nav-link:hover {
            background: #f0f7f2;
            color: var(--vert);
        }

        .nav-link.active {
            background: #e8f5ec;
            color: var(--vert-dk);
            font-weight: 600;
        }

        /* ===========================
        PARTIE DROITE
        =========================== */

        .nav-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-chip {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.4rem 1rem 0.4rem 0.5rem;
            background: #f0f7f2;
            border: 1px solid #c8e6d0;
            border-radius: 40px;
            font-size: 0.82rem;
        }

        .user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--vert), var(--vert-lt));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            color: white;
        }

        .user-chip .name {
            font-weight: 600;
            color: var(--vert-dk);
        }

        .user-chip .role {
            color: var(--muted);
            font-size: 0.72rem;
        }

        .btn-logout {
            background: none;
            border: 1px solid #fecaca;
            color: #dc2626;
            font-size: 0.78rem;
            padding: 0.4rem 0.9rem;
            border-radius: 6px;
            cursor: pointer;
            font-family: 'Outfit', sans-serif;
            transition: all 0.2s;
        }

        .btn-logout:hover {
            background: #fee2e2;
        }

        /* ===========================
        MENU HAMBURGER
        =========================== */

        .menu-toggle{
            display:none;
            font-size:28px;
            cursor:pointer;
        }

        /* ===========================
        RESPONSIVE
        =========================== */

        @media (max-width: 900px){

        .menu-toggle{
            display:block;
        }

        /* menu caché par défaut */
        .nav-menu{
            position:absolute;
            top:70px;
            right:0;
            width:260px;
            background:white;

            flex-direction:column;
            align-items:flex-start;

            padding:20px;

            box-shadow:0 10px 25px rgba(0,0,0,0.15);

            display:none;
        }

        /* menu visible */
        .nav-menu.active{
            display:flex;
        }

        /* liens menu */
        .nav-center{
            flex-direction:column;
            width:100%;
        }

        .nav-center a{
            width:100%;
            padding:10px 0;
        }

        /* zone utilisateur */
        .nav-right{
            flex-direction:column;
            width:100%;
            margin-top:15px;
        }

        }

        /* ===========================
           PAGE HEADER
           =========================== */
        .page-header {
            background: linear-gradient(135deg, var(--vert-dk) 0%, var(--vert) 60%, #22a050 100%);
            color: white;
            padding: 2.5rem 0;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            right: -100px; top: -100px;
            width: 400px; height: 400px;
            border-radius: 50%;
            border: 60px solid rgba(255,255,255,0.04);
        }

        .page-header::after {
            content: '';
            position: absolute;
            left: 30%; bottom: -30px;
            width: 200px; height: 200px;
            border-radius: 50%;
            border: 30px solid rgba(245,197,24,0.08);
        }

        .header-inner {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 1;
        }

        .header-left .eyebrow {
            font-size: 0.7rem;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: rgba(245,197,24,0.8);
            margin-bottom: 0.4rem;
        }

        .header-left h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.4rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .header-left p {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.65);
            margin-top: 0.4rem;
        }

        .header-badge {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            padding: 1rem 1.5rem;
            text-align: center;
            backdrop-filter: blur(10px);
        }

        .header-badge .big-num {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--jaune);
            display: block;
            line-height: 1;
        }

        .header-badge .label {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.6);
            letter-spacing: 0.1em;
        }

        /* ===========================
           GEOPORTAIL SECTION
           =========================== */
        .geo-section {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .geo-grid {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 1.5rem;
        }

        /* MAP AREA */
        .map-area {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 24px var(--shadow);
            overflow: visible;
            border: 1px solid var(--border);
        }

        .map-toolbar {
            padding: 1rem 1.25rem;
            background: white;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .search-field {
            display: flex;
            flex: 1;
            min-width: 200px;
        }

        .search-field input {
            flex: 1;
            padding: 0.6rem 0.9rem;
            border: 1px solid var(--border);
            border-right: none;
            border-radius: 8px 0 0 8px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.88rem;
            outline: none;
            color: var(--text);
        }

        .search-field input:focus { border-color: var(--vert); }

        .search-field button {
            padding: 0.6rem 1rem;
            background: var(--vert);
            color: white;
            border: none;
            border-radius: 0 8px 8px 0;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .filter-tabs {
            display: flex;
            gap: 0.4rem;
        }

        .filter-tab {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: 1px solid var(--border);
            font-size: 0.8rem;
            font-family: 'Outfit', sans-serif;
            cursor: pointer;
            background: white;
            color: var(--muted);
            transition: all 0.2s;
            font-weight: 500;
        }

        .filter-tab.active {
            background: var(--vert);
            color: white;
            border-color: var(--vert);
        }

        .commune-bar {
            padding: 0.75rem 1.25rem;
            border-bottom: 1px solid var(--border);
            background: #fafcfa;
        }

        .commune-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            user-select: none;
        }

        .commune-toggle h4 {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--vert-dk);
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .commune-toggle .arrow {
            font-size: 0.75rem;
            color: var(--muted);
            transition: transform 0.3s;
        }

        .commune-toggle .arrow.open { transform: rotate(180deg); }

        .commune-options {
            display: none;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .commune-options.open { display: flex; }

        .commune-check {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            color: var(--muted);
            cursor: pointer;
        }

        .commune-check input { accent-color: var(--vert); }

        #map {
            width: 100%;
            height: 560px;
        }

        /* SIDEBAR */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .panel {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 24px var(--shadow);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .panel-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .panel-header .icon {
            width: 32px; height: 32px;
            border-radius: 8px;
            background: #e8f5ec;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem;
        }

        .panel-header h3 {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--vert-dk);
        }

        .panel-body {
            padding: 1.25rem;
        }

        .no-selection {
            text-align: center;
            color: var(--muted);
            font-size: 0.85rem;
            padding: 1.5rem 0;
        }

        .parcel-attr {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 0.6rem 0;
            border-bottom: 1px solid #f0f4f0;
            font-size: 0.85rem;
        }

        .parcel-attr:last-child { border-bottom: none; }
        .parcel-attr .key { color: var(--muted); font-size: 0.78rem; }
        .parcel-attr .val { font-weight: 600; color: var(--text); text-align: right; max-width: 60%; }

        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 0.7rem 0;
            border-bottom: 1px solid #f0f4f0;
            font-size: 0.85rem;
        }

        .stat-row:last-child { border-bottom: none; }
        .stat-row .sk { color: var(--muted); }
        .stat-row .sv { font-weight: 700; color: var(--vert); }


        /* ===== BASEMAP SWITCHER ===== */
        .basemap-control {
            position: absolute;
            bottom: 24px;
            top: 10px;
            right: 16px;
            z-index: 1000;
            font-family: 'Outfit', sans-serif;
        }
        .basemap-btn {
            width: 48px; height: 48px;
            border-radius: 10px;
            border: 2px solid white;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(0,0,0,0.35);
            overflow: hidden;
            background: white;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex; align-items: center; justify-content: center;
        }
        .basemap-btn:hover { transform: scale(1.07); box-shadow: 0 6px 20px rgba(0,0,0,0.4); }
        .basemap-panel {
            position: absolute;
            top: 58px;       
            right: 0;       
            left: auto;   
            background: white;
            border-radius: 14px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.22);
            padding: 12px;
            display: none;
            flex-direction: column;
            gap: 8px;
            min-width: 200px;
            border: 1px solid #e5e7eb;
            animation: slideUpPanel 0.2s ease;
        }
        @keyframes slideUpPanel {
            from { opacity:0; transform:translateY(8px); }
            to   { opacity:1; transform:translateY(0); }
        }
        .basemap-panel.open { display: flex; }
        .basemap-panel-title {
            font-size: 0.68rem; font-weight: 700;
            letter-spacing: 0.15em; text-transform: uppercase;
            color: #9ca3af; padding: 0 4px 4px;
            border-bottom: 1px solid #f3f4f6; margin-bottom: 2px;
        }
        .basemap-option {
            display: flex; align-items: center; gap: 10px;
            padding: 7px 8px; border-radius: 8px;
            cursor: pointer; transition: background 0.15s;
            border: 2px solid transparent;
        }
        .basemap-option:hover { background: #f0f7f2; }
        .basemap-option.active { background: #e8f5ec; border-color: #1a7a3c; }
        .basemap-thumb {
            width: 44px; height: 36px;
            border-radius: 6px; overflow: hidden;
            flex-shrink: 0; border: 1px solid #e5e7eb;
        }
        .basemap-thumb-color {
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
        }
        .basemap-info strong { display:block; font-size:0.82rem; font-weight:600; color:#1a2e1e; }
        .basemap-info span   { font-size:0.7rem; color:#9ca3af; }
        .basemap-check { margin-left:auto; color:#1a7a3c; font-size:1rem; opacity:0; }
        .basemap-option.active .basemap-check { opacity:1; }

        /* ===========================
           FOOTER
           =========================== */
        .site-footer {
            background: var(--dark);
            color: rgba(255,255,255,0.75);
            margin-top: 4rem;
            position: relative;
            overflow: hidden;
        }

        .footer-flag-bar {
            height: 5px;
            background: linear-gradient(90deg, var(--vert) 33%, #FDEF42 33% 66%, var(--rouge) 66%);
        }

        .footer-top {
            background: linear-gradient(180deg, rgba(26,122,60,0.15) 0%, transparent 100%);
            padding: 3.5rem 0 2.5rem;
        }

        .footer-inner {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .footer-republic {
            display: flex;
            align-items: center;
            gap: 1.2rem;
            margin-bottom: 2.5rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .footer-emblem {
            width: 72px; height: 48px;
            border-radius: 6px;
            overflow: hidden;
            flex-shrink: 0;
            box-shadow: 0 2px 12px rgba(0,0,0,0.4);
        }

        .footer-emblem-inner { display: none; }

        .footer-republic-text .r1 {
            font-size: 0.65rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.4);
            display: block;
        }

        .footer-republic-text .r2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.6rem;
            font-weight: 700;
            color: white;
            line-height: 1.1;
            display: block;
        }

        .footer-republic-text .r3 {
            font-size: 0.7rem;
            color: var(--or);
            letter-spacing: 0.15em;
            display: block;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.5fr;
            gap: 3rem;
        }

        .footer-col h4 {
            font-size: 0.75rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--jaune);
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .footer-col p {
            font-size: 0.85rem;
            line-height: 1.7;
            color: rgba(255,255,255,0.5);
        }

        .service-badge {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.6rem 0.75rem;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 8px;
            margin-bottom: 0.5rem;
            font-size: 0.82rem;
            color: rgba(255,255,255,0.6);
        }

        .service-badge .dot {
            width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
        }
        .dot-green { background: var(--vert-lt); }
        .dot-gold  { background: var(--or); }
        .dot-red   { background: var(--rouge); }

        .footer-link-list {
            list-style: none;
        }

        .footer-link-list li {
            padding: 0.35rem 0;
        }

        .footer-link-list a {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            transition: color 0.2s;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .footer-link-list a:hover { color: white; }
        .footer-link-list a::before { content: '›'; color: var(--vert-lt); font-size: 1rem; }

        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 0.8rem;
            font-size: 0.82rem;
            color: rgba(255,255,255,0.55);
        }

        .contact-item .ci-icon {
            width: 28px; height: 28px;
            border-radius: 6px;
            background: rgba(26,122,60,0.25);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        #footerMap {
            width: 100%;
            height: 140px;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 1rem;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.07);
            padding: 1.25rem 0;
        }

        .footer-bottom-inner {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .footer-bottom p {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.25);
        }

        .footer-bottom-links {
            display: flex;
            gap: 1.5rem;
        }

        .footer-bottom-links a {
            font-size: 0.72rem;
            color: rgba(255,255,255,0.25);
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-bottom-links a:hover { color: rgba(255,255,255,0.6); }

        /* Responsive */
        @media (max-width: 1024px) {
            .geo-grid { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 640px) {
            .header-left h1 { font-size: 1.6rem; }
            .header-badge { display: none; }
            .footer-grid { grid-template-columns: 1fr; }
            .footer-bottom-links { display: none; }
        }
    </style>
</head>
<body>

<!-- Topbar officielle -->
<div class="topbar">
    <div class="topbar-inner">
        <span>🇸🇳 République du Sénégal — Un Peuple · Un But · Une Foi</span>
        <span>Ministère de l'Urbanisme &nbsp;|&nbsp; <a href="#">Direction de l'Urbanisme de Thiès</a></span>
    </div>
</div>

<!-- Navbar -->
<nav class="navbar">
    <div class="nav-inner">
        <a href="#" class="nav-brand">
            <div class="brand-emblem">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 600" width="56" height="38">
                    <rect width="300" height="600" fill="#00853F"/>
                    <rect x="300" width="300" height="600" fill="#FDEF42"/>
                    <rect x="600" width="300" height="600" fill="#E31B23"/>
                    <polygon points="450,210 462,247 501,247 470,270 481,307 450,285 419,307 430,270 399,247 438,247" fill="#00853F"/>
                </svg>
            </div>
            <div class="brand-text">
                <span class="b1">République du Sénégal</span>
                <span class="b2">Géoportail de Thiès</span>
                <span class="b3">Direction de l'Urbanisme, du Domaine & du Cadastre</span>
            </div>
        </a>

        <!-- bouton hamburger -->
        <div class="menu-toggle" onclick="toggleMenu()">
            ☰
        </div>

        <!-- menu qui va se cacher -->
        <div class="nav-menu" id="navMenu">

        <div class="nav-center">
            <a href="#" class="nav-link">Accueil</a>
            <a href="#" class="nav-link active">Géoportail</a>
            <a href="#" class="nav-link">Urbanisme</a>
            <a href="#" class="nav-link">Domaine</a>
            <a href="#" class="nav-link">Cadastre</a>
            <a href="#" class="nav-link">Contact</a>
        </div>

        <div class="nav-right">
            <div class="user-chip">
                <div>
                    <div class="name">Administrateur</div>
                    <div class="role">Urbanisme</div>
                </div>
            </div>
            <a href="logout.php"><button class="btn-logout">↩ Déconnexion</button></a>
        </div>
        </div>
    </div>
</nav>

<!-- Page Header -->
<div class="page-header">
    <div class="header-inner">
        <div class="header-left">
            <div class="eyebrow">✦ Système d'Information Géographique</div>
            <h1>Géoportail de Thiès</h1>
            <p>Cartographie interactive du territoire — Commune de Thiès Ouest</p>
        </div>
        <div style="display:flex; gap:1rem;">
            <div class="header-badge">
                <span class="big-num" id="totalParcels">—</span>
                <span class="label">Parcelles enregistrées</span>
            </div>
            <div class="header-badge">
                <span class="big-num" id="totalEquipements">—</span>
                <span class="label">Équipements recensés</span>
            </div>
        </div>
    </div>
</div>

<!-- Géoportail -->
<div class="geo-section">
    <div class="geo-grid">

        <!-- Carte -->
        <div class="map-area">
            <!-- Toolbar -->
            <div class="map-toolbar">
                <div class="search-field">
                    <input type="text" id="searchInput" placeholder="Rechercher une parcelle, un numéro...">
                    <button onclick="searchMap()">🔍</button>
                </div>
                <!-- Bouton ajouter parcelle -->
                <button id="btnAddParcelle" onclick="startDrawParcelle()" style="
                    padding: 0.5rem 1rem;
                    background: var(--vert);
                    color: white;
                    border: none;
                    border-radius: 8px;
                    font-family: 'Outfit', sans-serif;
                    font-size: 0.82rem;
                    font-weight: 600;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    gap: 0.4rem;
                    transition: all 0.2s;
                    white-space: nowrap;
                ">
                    ＋ Ajouter parcelle
                </button>
                <div class="filter-tabs">
                    <button id="btn-parcelles" class="filter-tab active" onclick="toggleLayer('parcelles')">
                Parcelles
                </button>
                <button id="btn-equipements" class="filter-tab active" onclick="toggleLayer('equipements')">
                Équipements
                </button>
                    <button class="filter-tab" id="btn-autres_existants"
            onclick="toggleCategorie('autres_existants')"
            style="border-color:#22c55e; color:#22c55e;">
        ● Autres existants
    </button>
    <button class="filter-tab" id="btn-empietement_de_la_rue"
            onclick="toggleCategorie('empietement_de_la_rue')"
            style="border-color:#f97316; color:#f97316;">
        ● Empiètement
    </button>
    <button class="filter-tab" id="btn-occupation_2026"
            onclick="toggleCategorie('occupation_2026')"
            style="border-color:#8b5cf6; color:#8b5cf6;">
        ● Occupation 2026
    </button>
    <button class="filter-tab" id="btn-modifier"
            onclick="toggleCategorie('modifier')"
            style="border-color:#6b7280; color:#6b7280;">
        ● Modifier
    </button>
    <button class="filter-tab" id="btn-a_modifier"
            onclick="toggleCategorie('a_modifier')"
            style="border-color:#ef4444; color:#ef4444;">
        ● A modifier
    </button>
                </div>
            </div>

            <!-- Commune selector -->
            <div class="commune-bar">
                <div class="commune-toggle" onclick="toggleCommuneMenu()">
                    <h4>📍 Commune</h4>
                    <span class="arrow" id="toggleIcon">▼</span>
                </div>
                <div class="commune-options" id="communeCheckboxes">
                    <label class="commune-check">
                        <input type="checkbox" id="commune-ouest" onchange="toggleCommune('ouest')">
                        Thiès Ouest
                    </label>
                </div>
            </div>

<!-- Map + contrôles superposés -->
            <div style="position:relative; width:100%; height:560px;">

                <!-- Carte Leaflet -->
                <div id="map" style="width:100%; height:100%;"></div>

                <!-- Basemap Switcher — par-dessus la carte -->
                <div class="basemap-control" id="basemapControl" style="position:absolute; top:10px; right:16px; z-index:1000;">
                    <div class="basemap-panel" id="basemapPanel">
                        <div class="basemap-panel-title">🗺️ Fond de carte</div>
                        <div class="basemap-option active" id="opt-osm" onclick="switchBasemap('osm')">
                            <div class="basemap-info"><strong>OpenStreetMap</strong><span>Plan standard</span></div>
                            <span class="basemap-check">✔</span>
                        </div>
                        <div class="basemap-option" id="opt-satellite" onclick="switchBasemap('satellite')">
                            <div class="basemap-info"><strong>Satellite</strong><span>Vue aérienne</span></div>
                            <span class="basemap-check">✔</span>
                        </div>
                        <div class="basemap-option" id="opt-hybrid" onclick="switchBasemap('hybrid')">
                            <div class="basemap-info"><strong>Hybride</strong><span>Satellite + noms</span></div>
                            <span class="basemap-check">✔</span>
                        </div>
                        <div class="basemap-option" id="opt-topo" onclick="switchBasemap('topo')">
                            <div class="basemap-info"><strong>Topographique</strong><span>Relief & contours</span></div>
                            <span class="basemap-check">✔</span>
                        </div>
                        <div class="basemap-option" id="opt-dark" onclick="switchBasemap('dark')">
                            <div class="basemap-info"><strong>Sombre</strong><span>Mode nuit</span></div>
                            <span class="basemap-check">✔</span>
                        </div>
                    </div>
                    <button class="basemap-btn" onclick="toggleBasemapPanel()" title="Changer le fond de carte">
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#1a7a3c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 2 7 12 12 22 7 12 2"/>
                            <polyline points="2 17 12 22 22 17"/>
                            <polyline points="2 12 12 17 22 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Légende — en bas à gauche -->
                <div id="legendeCategories" style="
                    position:absolute; bottom:24px; left:16px;
                    z-index:1000; background:white; border-radius:10px;
                    padding:10px 14px; box-shadow:0 4px 14px rgba(0,0,0,0.2);
                    border:1px solid #e5e7eb; font-family:'Outfit',sans-serif;
                    font-size:0.75rem; min-width:190px;">
                    <div style="font-weight:700; font-size:0.7rem; letter-spacing:0.1em;
                        text-transform:uppercase; color:#9ca3af;
                        margin-bottom:8px; border-bottom:1px solid #f3f4f6; padding-bottom:4px;">
                        Légende
                    </div>
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:5px;">
                        <svg width="16" height="16" style="flex-shrink:0; border:1px solid #121312; border-radius:2px;">
                            <defs><pattern id="leg1" patternUnits="userSpaceOnUse" width="4" height="4" patternTransform="rotate(45)">
                                <line x1="0" y1="0" x2="0" y2="4" stroke="#121312" stroke-width="1.5"/>
                            </pattern></defs>
                            <rect width="16" height="16" fill="url(#leg1)"/>
                        </svg>
                        Autres existants
                    </div>
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:5px;">
                        <svg width="16" height="16" style="flex-shrink:0; border:1px solid #f97316; border-radius:2px;">
                            <defs><pattern id="leg2" patternUnits="userSpaceOnUse" width="4" height="4" patternTransform="rotate(-45)">
                                <line x1="0" y1="0" x2="0" y2="4" stroke="#f97316" stroke-width="1.5"/>
                            </pattern></defs>
                            <rect width="16" height="16" fill="url(#leg2)"/>
                        </svg>
                        Empiètement de la rue
                    </div>
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:5px;">
                        <svg width="16" height="16" style="flex-shrink:0; border:1px solid #8b5cf6; border-radius:2px;">
                            <defs><pattern id="leg3" patternUnits="userSpaceOnUse" width="5" height="5">
                                <line x1="0" y1="0" x2="0" y2="5" stroke="#8b5cf6" stroke-width="1.2"/>
                                <line x1="0" y1="0" x2="5" y2="0" stroke="#8b5cf6" stroke-width="1.2"/>
                            </pattern></defs>
                            <rect width="16" height="16" fill="url(#leg3)"/>
                        </svg>
                        Occupation 2026
                    </div>
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:5px;">
                        <svg width="16" height="16" style="flex-shrink:0; border:1px solid #6b7280; border-radius:2px;">
                            <defs><pattern id="leg4" patternUnits="userSpaceOnUse" width="5" height="5">
                                <circle cx="2.5" cy="2.5" r="1.2" fill="#6b7280"/>
                            </pattern></defs>
                            <rect width="16" height="16" fill="url(#leg4)"/>
                        </svg>
                        À modifier
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <svg width="16" height="16" style="flex-shrink:0; border:1px solid #ef4444; border-radius:2px;">
                            <defs><pattern id="leg5" patternUnits="userSpaceOnUse" width="4" height="4" patternTransform="rotate(90)">
                                <line x1="0" y1="0" x2="0" y2="4" stroke="#ef4444" stroke-width="1.5"/>
                            </pattern></defs>
                            <rect width="16" height="16" fill="url(#leg5)"/>
                        </svg>
                        A modifier (urgent)
                    </div>
                </div>

            </div><!-- fin wrapper carte -->

        </div><!-- fin .map-area -->

        <!-- Sidebar -->
        <div class="sidebar">

            <!-- Info parcelle -->
            <div class="panel">
                <div class="panel-header">
                    <h3 id="parcelPanelTitle">Informations Parcelle</h3>
                </div>
                <div class="panel-body">
                    <div id="parcelInfo">
                        <p class="no-selection">Cliquez sur une parcelle pour afficher les détails</p>
                    </div>
                    <!-- Formulaire ajout — caché par défaut -->
                    <div id="addParcelForm" style="display:none;">

                        <div style="background:#e8f5ec; border:1px solid #c8e6d0; border-radius:8px;
                            padding:10px 12px; margin-bottom:14px; font-size:0.8rem; color:#1a7a3c;">
                            🖱️ <strong>Dessinez</strong> la parcelle sur la carte en cliquant pour placer les points.
                            Double-cliquez pour terminer.
                        </div>

                        <!-- ID auto -->
                        <div style="margin-bottom:10px;">
                            <label style="display:block; font-size:0.75rem; font-weight:600;
                                color:var(--muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.08em;">
                                ID (auto)
                            </label>
                            <input type="text" id="newParcelId" readonly value="—" style="
                                width:100%; padding:7px 10px;
                                border:1px solid var(--border); border-radius:6px;
                                font-size:0.85rem; background:#f9fafb; color:#6b7280;">
                        </div>

                        <div style="margin-bottom:10px;">
                            <label style="display:block; font-size:0.75rem; font-weight:600;
                                color:var(--muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.08em;">
                                N° Parcelle *
                            </label>
                            <input type="text" id="new_n_parcelle" placeholder="ex: P-2026-001" style="
                                width:100%; padding:7px 10px;
                                border:1px solid var(--border); border-radius:6px; font-size:0.85rem;">
                        </div>

                        <div style="margin-bottom:10px;">
                            <label style="display:block; font-size:0.75rem; font-weight:600;
                                color:var(--muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.08em;">
                                Prénom & Nom
                            </label>
                            <input type="text" id="new_prenom_nom" placeholder="Attributaire" style="
                                width:100%; padding:7px 10px;
                                border:1px solid var(--border); border-radius:6px; font-size:0.85rem;">
                        </div>

                        <div style="margin-bottom:10px;">
                            <label style="display:block; font-size:0.75rem; font-weight:600;
                                color:var(--muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.08em;">
                                Adresse
                            </label>
                            <input type="text" id="new_adresse" placeholder="Adresse" style="
                                width:100%; padding:7px 10px;
                                border:1px solid var(--border); border-radius:6px; font-size:0.85rem;">
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:10px;">
                            <div>
                                <label style="display:block; font-size:0.75rem; font-weight:600;
                                    color:var(--muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.08em;">
                                    Téléphone
                                </label>
                                <input type="text" id="new_tel" placeholder="77 000 00 00" style="
                                    width:100%; padding:7px 10px;
                                    border:1px solid var(--border); border-radius:6px; font-size:0.85rem;">
                            </div>
                            <div>
                                <label style="display:block; font-size:0.75rem; font-weight:600;
                                    color:var(--muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.08em;">
                                    CNI
                                </label>
                                <input type="text" id="new_cni" placeholder="N° CNI" style="
                                    width:100%; padding:7px 10px;
                                    border:1px solid var(--border); border-radius:6px; font-size:0.85rem;">
                            </div>
                        </div>

                        <div style="margin-bottom:10px;">
                            <label style="display:block; font-size:0.75rem; font-weight:600;
                                color:var(--muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.08em;">
                                Statut
                            </label>
                            <select id="new_statut" style="
                                width:100%; padding:7px 10px;
                                border:1px solid var(--border); border-radius:6px;
                                font-family:'Outfit',sans-serif; font-size:0.85rem; background:white;">
                                <option value="non affecté">Non affecté</option>
                                <option value="affecté">Affecté</option>
                            </select>
                        </div>

                        <div style="margin-bottom:10px;">
                            <label style="display:block; font-size:0.75rem; font-weight:600;
                                color:var(--muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.08em;">
                                Observation
                            </label>
                            <textarea id="new_observation" rows="2" placeholder="Observation..." style="
                                width:100%; padding:7px 10px;
                                border:1px solid var(--border); border-radius:6px;
                                font-family:'Outfit',sans-serif; font-size:0.85rem; resize:vertical;"></textarea>
                        </div>

                        <!-- Indicateur dessin -->
                        <div id="drawStatus" style="
                            text-align:center; padding:8px;
                            border-radius:6px; font-size:0.8rem;
                            background:#fff3cd; color:#856404;
                            margin-bottom:12px; display:none;">
                            ✏️ En attente du dessin sur la carte...
                        </div>

                        <div style="display:flex; gap:8px;">
                            <button onclick="saveNewParcelle()" style="
                                flex:1; padding:9px;
                                background:var(--vert); color:white;
                                border:none; border-radius:6px;
                                font-family:'Outfit',sans-serif;
                                font-weight:600; font-size:0.85rem; cursor:pointer;">
                                💾 Enregistrer
                            </button>
                            <button onclick="cancelAddParcelle()" style="
                                flex:1; padding:9px;
                                background:#f3f4f6; color:#6b7280;
                                border:1px solid var(--border); border-radius:6px;
                                font-family:'Outfit',sans-serif;
                                font-weight:600; font-size:0.85rem; cursor:pointer;">
                                ✕ Annuler
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info équipement -->
            <div class="panel">
                <div class="panel-header">
                    <h3>Informations Équipement</h3>
                </div>
                <div class="panel-body">
                    <div id="equipementInfo">
                        <p class="no-selection">Cliquez sur un équipement pour afficher les détails</p>
                    </div>
                </div>
            </div>

            <!-- Statistiques -->
            <div class="panel">
                <div class="panel-header">
                    <div class="icon">📊</div>
                    <h3>Statistiques</h3>
                </div>
                <div class="panel-body">
                    <canvas id="parcelChart"></canvas>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ===== FOOTER ===== -->
<footer class="site-footer">
    <div class="footer-flag-bar"></div>

    <div class="footer-top">
        <div class="footer-inner">

            <!-- Identité officielle -->
            <div class="footer-republic">
                <div class="footer-emblem">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 600" width="72" height="48">
                        <rect width="300" height="600" fill="#00853F"/>
                        <rect x="300" width="300" height="600" fill="#FDEF42"/>
                        <rect x="600" width="300" height="600" fill="#E31B23"/>
                        <polygon points="450,210 462,247 501,247 470,270 481,307 450,285 419,307 430,270 399,247 438,247" fill="#00853F"/>
                    </svg>
                </div>
                <div class="footer-republic-text">
                    <span class="r1">République du</span>
                    <span class="r2">Sénégal</span>
                    <span class="r3">Un Peuple · Un But · Une Foi</span>
                </div>
            </div>

            <!-- Grille footer -->
            <div class="footer-grid">

                <!-- Col 1 : Présentation -->
                <div class="footer-col">
                    <h4>🏛️ Direction de l'Urbanisme</h4>
                    <p>Le Géoportail de Thiès est la plateforme officielle de gestion foncière et d'aménagement du territoire de la commune de Thiès Ouest. Outil au service des agents de l'État et des administrés.</p>
                    <div id="footerMap" style="margin-top:1.2rem;"></div>
                </div>

                <!-- Col 2 : Services -->
                <div class="footer-col">
                    <h4>⚙️ Services</h4>
                    <div class="service-badge"><span class="dot dot-green"></span> Urbanisme & Permis</div>
                    <div class="service-badge"><span class="dot dot-gold"></span> Gestion du Domaine</div>
                    <div class="service-badge"><span class="dot dot-red"></span> Cadastre & Parcelles</div>
                    <div class="service-badge"><span class="dot dot-green"></span> Cartographie SIG</div>
                    <div class="service-badge"><span class="dot dot-gold"></span> Plans d'aménagement</div>
                </div>

                <!-- Col 3 : Liens -->
                <div class="footer-col">
                    <h4>🔗 Liens Utiles</h4>
                    <ul class="footer-link-list">
                        <li><a href="#">Mentions légales</a></li>
                        <li><a href="#">Politique de confidentialité</a></li>
                        <li><a href="#">Plan du site</a></li>
                        <li><a href="#">Manuel utilisateur</a></li>
                        <li><a href="#">Demande d'accès</a></li>
                        <li><a href="#">Portail national SIG</a></li>
                    </ul>
                </div>

                <!-- Col 4 : Contact -->
                <div class="footer-col">
                    <h4>📞 Contact</h4>
                    <div class="contact-item">
                        <div class="ci-icon">📍</div>
                        <div>Direction de l'Urbanisme<br>Q3V9+RQH, Thiès, Sénégal</div>
                    </div>
                    <div class="contact-item">
                        <div class="ci-icon">✉️</div>
                        <div><a href="/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="dda8afbfbcb3b4aeb0b89dabb4b1b1b8f0a9b5b4b8aef3aeb3">[email&#160;protected]</a></div>
                    </div>
                    <div class="contact-item">
                        <div class="ci-icon">🕐</div>
                        <div>Lun – Ven : 08h00 – 17h00</div>
                    </div>
                    <div class="contact-item">
                        <div class="ci-icon">🔒</div>
                        <div>Système sécurisé — Usage interne</div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Footer bottom -->
    <div class="footer-bottom">
        <div class="footer-bottom-inner">
            <p>© 2026 Direction de l'Urbanisme de Thiès — Tous droits réservés · Géoportail v2.0</p>
            <div class="footer-bottom-links">
                <a href="#">Mentions légales</a>
                <a href="#">Confidentialité</a>
                <a href="#">Accessibilité</a>
                <a href="#">Support technique</a>
            </div>
        </div>
    </div>
</footer>

<script data-cfasync="false" src="/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script><script>
    // Toggle commune menu
    function toggleCommuneMenu() {
        const opts = document.getElementById('communeCheckboxes');
        const icon = document.getElementById('toggleIcon');
        opts.classList.toggle('open');
        icon.classList.toggle('open');
    }

    // Footer map
    window.addEventListener('load', function() {
        setTimeout(function() {
            if (typeof L === 'undefined') return;
            const el = document.getElementById('footerMap');
            if (!el) return;

            const footerMap = L.map('footerMap', {
                center: [14.79503, -16.9306],
                zoom: 14,
                zoomControl: false,
                scrollWheelZoom: false,
                dragging: false,
                attributionControl: false
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19
            }).addTo(footerMap);

            const icon = L.divIcon({
                className: '',
                html: `<div style="
                    background:#1a7a3c;
                    width:32px;height:32px;
                    border-radius:50% 50% 50% 0;
                    transform:rotate(-45deg);
                    border:3px solid white;
                    box-shadow:0 3px 8px rgba(0,0,0,0.5);
                    display:flex;align-items:center;justify-content:center;">
                    <span style="transform:rotate(45deg);font-size:14px;">🏛️</span>
                </div>`,
                iconSize: [32, 32],
                iconAnchor: [16, 32]
            });

            L.marker([14.79503, -16.9306], { icon })
             .addTo(footerMap)
             .bindPopup('<strong>Direction de l\'Urbanisme</strong><br>Thiès, Sénégal')
             .openPopup();

            setTimeout(() => footerMap.invalidateSize(), 250);
        }, 500);
    });
</script>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="geoportail.js"></script>
</body>
</html>