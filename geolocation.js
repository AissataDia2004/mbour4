// geolocation.js
// Position en temps réel sur la carte (comme Google Maps)
// Nécessite : la variable globale `map` (Leaflet) et HTTPS (ou localhost)

(function () {
    let watchId = null;        // id du watchPosition
    let userMarker = null;     // point bleu
    let accuracyCircle = null; // cercle de précision
    let following = false;     // la carte suit-elle l'utilisateur ?
    let firstFix = true;
    let lastLatLng = null;
    let btnEl = null;

    // ---------- Styles ----------
    const css = document.createElement('style');
    css.textContent = `
        .locate-btn {
            width: 34px; height: 34px;
            background: white; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            border: none; padding: 0;
        }
        .locate-btn svg { stroke: #5a7a62; transition: stroke .2s; }
        .locate-btn.tracking svg { stroke: #1a73e8; }
        .locate-btn.following svg { stroke: #1a73e8; fill: #1a73e8; fill-opacity: .25; }
        .locate-btn.loading svg { animation: locSpin 1s linear infinite; }
        @keyframes locSpin { to { transform: rotate(360deg); } }

        .user-dot-wrap { position: relative; width: 22px; height: 22px; }
        .user-dot {
            position: absolute; inset: 3px;
            background: #1a73e8; border: 3px solid white;
            border-radius: 50%; box-shadow: 0 1px 6px rgba(0,0,0,.45);
        }
        .user-dot-pulse {
            position: absolute; inset: 0; border-radius: 50%;
            background: rgba(26,115,232,.35);
            animation: userPulse 2s ease-out infinite;
        }
        @keyframes userPulse {
            0%   { transform: scale(.6); opacity: .9; }
            100% { transform: scale(2.4); opacity: 0; }
        }
        .locate-toast {
            position: absolute; top: 12px; left: 50%; transform: translateX(-50%);
            z-index: 1100; background: #1a2e1e; color: white;
            padding: 8px 14px; border-radius: 8px; font-size: .8rem;
            font-family: 'Outfit', sans-serif; box-shadow: 0 4px 14px rgba(0,0,0,.3);
            max-width: 90%; text-align: center;
        }
    `;
    document.head.appendChild(css);

    // ---------- Message temporaire ----------
    function toast(msg) {
        const host = document.querySelector('.map-wrapper') || document.body;
        const t = document.createElement('div');
        t.className = 'locate-toast';
        t.textContent = msg;
        host.appendChild(t);
        setTimeout(() => t.remove(), 4500);
    }

    // ---------- Icône du point bleu ----------
    const userIcon = L.divIcon({
        className: '',
        html: '<div class="user-dot-wrap"><div class="user-dot-pulse"></div><div class="user-dot"></div></div>',
        iconSize: [22, 22],
        iconAnchor: [11, 11]
    });

    // ---------- Mise à jour de l'état du bouton ----------
    function setState(state) {
        if (!btnEl) return;
        btnEl.classList.remove('tracking', 'following', 'loading');
        if (state) btnEl.classList.add(state);
        btnEl.title = state === 'following' ? 'Arrêter le suivi de ma position'
                    : state === 'tracking'  ? 'Recentrer sur ma position'
                    : 'Afficher ma position';
    }

    // ---------- Réception d'une position ----------
    function onPosition(pos) {
        const latlng = L.latLng(pos.coords.latitude, pos.coords.longitude);
        const accuracy = pos.coords.accuracy; // en mètres
        lastLatLng = latlng;

        if (!userMarker) {
            userMarker = L.marker(latlng, { icon: userIcon, zIndexOffset: 1000, interactive: true })
                .addTo(map)
                .bindPopup('Vous êtes ici');
            accuracyCircle = L.circle(latlng, {
                radius: accuracy, color: '#1a73e8', weight: 1,
                fillColor: '#1a73e8', fillOpacity: 0.12, interactive: false
            }).addTo(map);
        } else {
            userMarker.setLatLng(latlng);
            accuracyCircle.setLatLng(latlng).setRadius(accuracy);
        }

        if (firstFix) {
            firstFix = false;
            map.flyTo(latlng, Math.max(map.getZoom(), 17), { duration: 1 });
            following = true;
            setState('following');
        } else if (following) {
            map.panTo(latlng, { animate: true });
        }
    }

    function onError(err) {
        const messages = {
            1: 'Accès à la position refusé. Autorisez la localisation dans votre navigateur.',
            2: 'Position indisponible. Vérifiez votre GPS ou votre connexion.',
            3: 'Délai dépassé pour obtenir la position.'
        };
        toast(messages[err.code] || 'Erreur de géolocalisation.');
        if (err.code === 1) stopTracking();
        else if (firstFix) setState(null);
    }

    // ---------- Démarrer / arrêter ----------
    function startTracking() {
        if (!('geolocation' in navigator)) {
            toast("La géolocalisation n'est pas supportée par ce navigateur.");
            return;
        }
        if (!window.isSecureContext) {
            toast('La localisation nécessite une connexion sécurisée (HTTPS).');
            return;
        }
        firstFix = true;
        following = false;
        setState('loading');
        watchId = navigator.geolocation.watchPosition(onPosition, onError, {
            enableHighAccuracy: true,
            maximumAge: 2000,
            timeout: 20000
        });
    }

    function stopTracking() {
        if (watchId !== null) navigator.geolocation.clearWatch(watchId);
        watchId = null;
        following = false;
        firstFix = true;
        lastLatLng = null;
        if (userMarker) { map.removeLayer(userMarker); userMarker = null; }
        if (accuracyCircle) { map.removeLayer(accuracyCircle); accuracyCircle = null; }
        setState(null);
    }

    // ---------- Clic sur le bouton ----------
    function onButtonClick() {
        if (watchId === null) {
            startTracking();                 // 1er clic : active
        } else if (!following && lastLatLng) {
            following = true;                // carte déplacée à la main : recentre
            map.flyTo(lastLatLng, Math.max(map.getZoom(), 17));
            setState('following');
        } else {
            stopTracking();                  // en suivi : désactive
        }
    }

    // ---------- Contrôle Leaflet (sous les boutons +/-) ----------
    const LocateControl = L.Control.extend({
        options: { position: 'topleft' },
        onAdd: function () {
            const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
            btnEl = L.DomUtil.create('button', 'locate-btn', container);
            btnEl.type = 'button';
            btnEl.title = 'Afficher ma position';
            btnEl.innerHTML =
                '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke-width="2" ' +
                'stroke-linecap="round" stroke-linejoin="round">' +
                '<circle cx="12" cy="12" r="3"/><circle cx="12" cy="12" r="8"/>' +
                '<line x1="12" y1="1" x2="12" y2="4"/><line x1="12" y1="20" x2="12" y2="23"/>' +
                '<line x1="1" y1="12" x2="4" y2="12"/><line x1="20" y1="12" x2="23" y2="12"/></svg>';
            L.DomEvent.disableClickPropagation(container);
            L.DomEvent.on(btnEl, 'click', onButtonClick);
            return container;
        }
    });

    // ---------- Initialisation (appelée depuis initMap) ----------
    window.initLocation = function () {
        new LocateControl().addTo(map);

        // Si l'utilisateur déplace la carte, on arrête de la recentrer
        map.on('dragstart', function () {
            if (following) {
                following = false;
                setState('tracking');
            }
        });
    };
})();
