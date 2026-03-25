// geoportail_data.js
// Géoportail avec données depuis PostgreSQL + Leaflet

let map;
let communeLayer = null;
let layers = {
    parcelles: null,
    equipements: null,
    autres_existants: null,
    occupation_2026: null,
    empietement_de_la_rue:null,
    modifier: null,
    a_modifier: null
};

// Couches des communes
let communeLayers = {
    ouest: null
};
// Configuration de l'API
const API_URL = 'api/';

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    initMap();
    loadAllLayers();
});

function toggleMenu(){
    document.getElementById("navMenu").classList.toggle("active");
}
// Initialiser la carte Leaflet
function initMap() {
    map = L.map('map').setView([14.7886, -16.9256], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    L.control.scale({
        position: 'bottomright',
        imperial: false
    }).addTo(map);
}

// Charger toutes les couches
async function loadAllLayers() {
    showLoadingMessage('Chargement des données...');
    try {
        await loadParcelles();
        await loadEquipements();
        updateStatistics();
        hideLoadingMessage();
    } catch (error) {
        console.error('Erreur lors du chargement:', error);
        showErrorMessage('Erreur lors du chargement des données');
    }
}

// Charger les parcelles
async function loadParcelles() {
    try {
        const response = await fetch(API_URL + 'parcelles.php');
        const geojson = await response.json();

        if (geojson.error) throw new Error(geojson.error);

        if (layers.parcelles) map.removeLayer(layers.parcelles);

        // Nettoyer les anciens labels
        if (window.parcelLabels) {
            window.parcelLabels.forEach(label => map.removeLayer(label));
        }
        window.parcelLabels = [];

        layers.parcelles = L.geoJSON(geojson, {
            style: function(feature) {
                const statut = feature.properties.statut || feature.properties.status;

                const color = getParcelColor(statut);

                return {
                    fillColor: color,
                    color: color,   // contour même couleur
                    weight: 1.5,
                    opacity: 1,
                    fillOpacity: 0.7
                };
            },
            onEachFeature: function(feature, layer) {
                bindParcelPopup(feature, layer);

                const props = feature.properties;
                let numero = props['n_parcelle'] || props['nparcelle'] || props.numero ||
                    props.numero_parcelle || props.num_parcelle || props.id ||
                    props.gid || props.fid || 'N/A';

                if (!feature.geometry || !feature.geometry.coordinates) return;

                let center;
                try {
                    const geom = feature.geometry;

                    if (geom.type === 'Polygon') {
                        const coords = geom.coordinates[0];
                        if (!coords || coords.length === 0) return;
                        let sumLat = 0, sumLng = 0;
                        coords.forEach(coord => { sumLng += coord[0]; sumLat += coord[1]; });
                        center = L.latLng(sumLat / coords.length, sumLng / coords.length);

                    } else if (geom.type === 'MultiPolygon') {
                        const coords = geom.coordinates[0][0];
                        if (!coords || coords.length === 0) return;
                        let sumLat = 0, sumLng = 0;
                        coords.forEach(coord => { sumLng += coord[0]; sumLat += coord[1]; });
                        center = L.latLng(sumLat / coords.length, sumLng / coords.length);

                    } else {
                        return;
                    }
                } catch(e) {
                    console.warn('Centroïde impossible pour feature:', props.id || numero, e);
                    return;
                }

                if (!center) return;

                const labelMarker = L.marker(center, {
                    icon: L.divIcon({
                        className: 'parcel-number-marker',
                        html: `<div class="parcel-number-label">${numero}</div>`,
                        iconSize: [40, 20],
                        iconAnchor: [20, 10]
                    }),
                    interactive: false,
                    zIndexOffset: 1000
                });

                labelMarker._parcelLayer = layer;
                window.parcelLabels.push(labelMarker);
            }
        }).addTo(map);
        updateStatistics();

        console.log(`${geojson.features.length} parcelles chargées`);

        updateLabelsVisibility();
        map.on('zoomend', updateLabelsVisibility);

        if (geojson.features.length > 0) {
            map.fitBounds(layers.parcelles.getBounds());
        }

    } catch (error) {
        console.error('Erreur parcelles:', error);
        throw error;
    }
}

// Afficher/masquer les labels selon le zoom
function updateLabelsVisibility() {
    const currentZoom = map.getZoom();
    const minZoomForLabels = 18;

    if (!window.parcelLabels) return;

    window.parcelLabels.forEach(label => {
        if (currentZoom >= minZoomForLabels) {
            if (!map.hasLayer(label)) map.addLayer(label);
        } else {
            if (map.hasLayer(label)) map.removeLayer(label);
        }
    });
}

// Couleur des parcelles selon statut
function getParcelColor(statut) {

    if (!statut) return '#ef4444'; // rouge si null

    const s = statut.toLowerCase().trim();

    if (s === 'non affecte' || s === 'non affecté') {
        return '#ef4444'; // rouge
    }

    if (s === 'affecte' || s === 'affecté') {
        return '#22c55e'; // vert
    }

    return '#9ca3af'; // gris si autre valeur
}

// Popup parcelle
function bindParcelPopup(feature, layer) {
    const props = feature.properties;

    let html = '<div style="min-width: 250px;">';
    html += '<h4 style="margin: 0 0 10px 0; color: #1a7a3c; border-bottom: 2px solid #1a7a3c; padding-bottom: 5px;">Parcelle</h4>';
    html += '<table style="width: 100%; font-size: 13px;">';

    for (let key in props) {
        if (props.hasOwnProperty(key) && key !== 'id' && key !== 'gid' && key !== 'geom') {
            const label = key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' ');
            html += `<tr><td style="padding: 3px 5px 3px 0;"><strong>${label}:</strong></td><td style="padding: 3px 0;">${props[key] || 'N/A'}</td></tr>`;
        }
    }

    html += '</table>';
    html += `
        <button
            onclick="displayParcelInfo(${JSON.stringify(props).replace(/"/g, '&quot;')})"
            style="width:100%; margin-top:10px; padding:8px; background:#1a7a3c; color:white; border:none; border-radius:4px; font-weight:bold; cursor:pointer;">
            ✏️ Modifier
        </button>
    `;
    html += '</div>';

    layer.bindPopup(html);

    layer.on('mouseover', function() { layer.setStyle({ weight: 3, fillOpacity: 0.8 }); });
    layer.on('mouseout',  function() { layer.setStyle({ weight: 2, fillOpacity: 0.6 }); });
    layer.on('click',     function() { displayParcelInfo(props); });
}

// Afficher le formulaire d'édition dans le panneau latéral
function displayParcelInfo(props) {
    const infoDiv = document.getElementById('parcelInfo');
    if (!infoDiv) return;

    const editableFields = ['liste_attributaire', 'adresse', 'attribution_2026', 'prenom_nom', 'n_parcelle', 'cni', 'tel', 'recensement', 'observation', 'recommendation', 'statut'];

    let html = '<div class="edit-form">';
    html += '<form id="editParcelForm" onsubmit="updateParcel(event)">';

    for (let key in props) {
        if (props.hasOwnProperty(key) && key !== 'geom') {
            const label = key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' ');
            const isEditable = editableFields.includes(key.toLowerCase());

            html += `<div class="form-group" style="margin-bottom:12px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px; color:#374151;">${label}:</label>`;

            if (isEditable) {
                html += `<input type="text" name="${key}" value="${props[key] || ''}"
                    style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:4px; font-size:14px;" />`;
            } else {
                html += `<input type="text" value="${props[key] || 'N/A'}" readonly
                    style="width:100%; padding:8px; border:1px solid #e5e7eb; border-radius:4px; background:#f9fafb; font-size:14px; color:#6b7280;" />`;
                if (key.toLowerCase() === 'id' || key.toLowerCase() === 'gid') {
                    html += `<input type="hidden" name="${key}" value="${props[key]}" />`;
                }
            }
            html += '</div>';
        }
    }

    html += `
        <div style="display:flex; gap:10px; margin-top:20px;">
            <button type="submit"
                style="flex:1; padding:10px; background:#1a7a3c; color:white; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">
                💾 Enregistrer
            </button>
            <button type="button" onclick="cancelEdit()"
                style="flex:1; padding:10px; background:#6b7280; color:white; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">
                ❌ Annuler
            </button>
        </div>
    `;

    html += '</form></div>';
    infoDiv.innerHTML = html;
}

// Annuler l'édition
function cancelEdit() {
    const infoDiv = document.getElementById('parcelInfo');
    if (infoDiv) {
        infoDiv.innerHTML = '<p style="color:#6b7280; text-align:center; padding:20px;">Sélectionnez une parcelle pour voir ses informations</p>';
    }
}

// Enregistrer les modifications
async function updateParcel(event) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    const data = {};
    formData.forEach((value, key) => { data[key] = value; });

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '⏳ Enregistrement...';
    submitBtn.disabled = true;

    try {
        const response = await fetch(API_URL + 'update_parcelle.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            alert('✅ Parcelle mise à jour avec succès !');
            await loadParcelles();
            cancelEdit();
        } else {
            throw new Error(result.error || 'Erreur lors de la mise à jour');
        }

    } catch (error) {
        console.error('Erreur:', error);
        alert('❌ Erreur : ' + error.message);
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

// Toggle de la couche parcelles
function toggleLayer(layerName) {

    const layer = layers[layerName];
    if (!layer) return;

    if (map.hasLayer(layer)) {
        map.removeLayer(layer);
    } else {
        map.addLayer(layer);
    }

    // changer la couleur du bouton
    const btn = document.getElementById("btn-" + layerName);
    if (btn) {
        btn.classList.toggle("active");
    }
}

// Charger les équipements
async function loadEquipements() {
    try {
        const response = await fetch(API_URL + 'equipements.php');
        const geojson = await response.json();
        
        if (geojson.error) throw new Error(geojson.error);
        
        if (layers.equipements) map.removeLayer(layers.equipements);
        
        layers.equipements = L.geoJSON(geojson, {
            pointToLayer: function(feature, latlng) {
                return createEquipementMarker(feature, latlng);
            },
            onEachFeature: function(feature, layer) {
                bindEquipementPopup(feature, layer);
            }
        }).addTo(map);
        
        console.log(`${geojson.features.length} équipements chargés`);
        updateStatistics(); // ← AJOUTER

    } catch (error) {
        console.error('Erreur équipements:', error);
    }
}


// Popup pour les équipements
function bindEquipementPopup(feature, layer) {
    const props = feature.properties;

    let html = '<div style="min-width: 200px;">';
    html += `<h4 style="margin: 0 0 10px 0; color: #1e40af;">${props.nom || props.name || 'Équipement'}</h4>`;
    html += '<table style="width: 100%; font-size: 13px;">';

    for (let key in props) {
        if (props.hasOwnProperty(key) && key !== 'id' && key !== 'gid' && key !== 'nom' && key !== 'name') {
            const label = key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' ');
            html += `<tr>
                        <td style="padding: 3px 5px 3px 0;"><strong>${label}:</strong></td>
                        <td style="padding: 3px 0;">${props[key] || 'N/A'}</td>
                     </tr>`;
        }
    }

    html += '</table></div>';
    layer.bindPopup(html);

    // Clic pour afficher dans le panneau latéral
    layer.on('click', function() {
        displayEquipementInfo(props);
    });
}

// Afficher les infos d'un équipement dans le panneau latéral
function displayEquipementInfo(props) {
    const infoDiv = document.getElementById('equipementInfo');

    let html = '';
    for (let key in props) {
        if (props.hasOwnProperty(key) && key !== 'id' && key !== 'gid') {
            const label = key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' ');
            html += `
                <div class="parcel-detail-item">
                    <span class="parcel-detail-label">${label}</span>
                    <div class="parcel-detail-value">${props[key] || 'N/A'}</div>
                </div>
            `;
        }
    }

    infoDiv.innerHTML = html;
}


// ============================================
// COULEURS PAR CATÉGORIE
// ============================================
const categorieConfig = {
    autres_existants: {
        couleur: '#121312',
        label:   'Autres existants',
        hachure: {
            pattern: 'lines',      // lignes diagonales
            angle:   45,
            spacing: 6,
            weight:  2
        }
    },
    empietement_de_la_rue: {
        couleur: '#f97316',
        label:   'Empiètement de la rue',
        hachure: {
            pattern: 'lines',
            angle:   -45,          // diagonales inverses
            spacing: 6,
            weight:  2
        }
    },
    occupation_2026: {
        couleur: '#8b5cf6',
        label:   'Occupation 2026',
        hachure: {
            pattern: 'cross',      // croisillons
            spacing: 8,
            weight:  1.5
        }
    },
    modifier: {
        couleur: '#6b7280',
        label:   'À modifier',
        hachure: {
            pattern: 'dots',       // pointillés
            spacing: 6,
            weight:  2
        }
    },
    a_modifier: {
        couleur: '#ef4444',
        label:   'A modifier (urgent)',
        hachure: {
            pattern: 'lines',
            angle:   90,           // lignes verticales
            spacing: 5,
            weight:  2
        }
    }
};

// Créer un pattern SVG hachure et retourner l'ID
function createHachurePattern(id, couleur, config) {

    // Supprimer l'ancien pattern si existe
    const old = document.getElementById(id);
    if (old) old.remove();

    // Créer ou récupérer le SVG defs global
    let svgDefs = document.getElementById('leaflet-hachure-defs');
    if (!svgDefs) {
        svgDefs = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svgDefs.id = 'leaflet-hachure-defs';
        svgDefs.setAttribute('style', 'position:absolute;width:0;height:0;overflow:hidden');
        document.body.appendChild(svgDefs);

        const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
        svgDefs.appendChild(defs);
    }

    const defs = svgDefs.querySelector('defs');
    const s    = config.spacing || 8;
    const w    = config.weight  || 1.5;
    const a    = config.angle   || 45;

    const pattern = document.createElementNS('http://www.w3.org/2000/svg', 'pattern');
    pattern.setAttribute('id',            id);
    pattern.setAttribute('patternUnits',  'userSpaceOnUse');
    pattern.setAttribute('width',         s);
    pattern.setAttribute('height',        s);
    pattern.setAttribute('patternTransform', `rotate(${a})`);

    if (config.pattern === 'lines') {
        // Lignes diagonales
        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('x1',           '0');
        line.setAttribute('y1',           '0');
        line.setAttribute('x2',           '0');
        line.setAttribute('y2',           s);
        line.setAttribute('stroke',       couleur);
        line.setAttribute('stroke-width', w);
        pattern.appendChild(line);

    } else if (config.pattern === 'cross') {
        // Croisillons
        const l1 = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        l1.setAttribute('x1', '0'); l1.setAttribute('y1', '0');
        l1.setAttribute('x2', '0'); l1.setAttribute('y2', s);
        l1.setAttribute('stroke', couleur); l1.setAttribute('stroke-width', w);

        const l2 = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        l2.setAttribute('x1', '0'); l2.setAttribute('y1', '0');
        l2.setAttribute('x2', s);   l2.setAttribute('y2', '0');
        l2.setAttribute('stroke', couleur); l2.setAttribute('stroke-width', w);

        pattern.appendChild(l1);
        pattern.appendChild(l2);

    } else if (config.pattern === 'dots') {
        // Points
        const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('cx',   s / 2);
        circle.setAttribute('cy',   s / 2);
        circle.setAttribute('r',    w);
        circle.setAttribute('fill', couleur);
        pattern.appendChild(circle);
    }

    defs.appendChild(pattern);
    return id;
}

async function loadCategorieLayer(nomTable) {
    try {
        const response = await fetch(API_URL + nomTable + '.php');
        const geojson  = await response.json();

        if (geojson.error) throw new Error(geojson.error);
        if (layers[nomTable]) map.removeLayer(layers[nomTable]);

        const config     = categorieConfig[nomTable];
        const patternId  = 'hachure-' + nomTable;

        // Créer le pattern SVG
        createHachurePattern(patternId, config.couleur, config.hachure);

        layers[nomTable] = L.geoJSON(geojson, {
            style: function() {
                return {
                    // Contour plein
                    color:       config.couleur,
                    weight:      2,
                    opacity:     1,
                    // Remplissage hachure via SVG pattern
                    fillColor:   config.couleur,
                    fillOpacity: 0.15,           // fond très léger
                    // Classe CSS pour appliquer le pattern
                    className:   'hachure-layer-' + nomTable
                };
            },
            onEachFeature: function(feature, layer) {
                // Appliquer le pattern après rendu
                layer.on('add', function() {
                    const el = layer.getElement ? layer.getElement() : null;
                    if (el) {
                        el.style.fill = `url(#${patternId})`;
                    }
                });

                const props = feature.properties;
                let html = `<div style="min-width:220px;">
                    <h4 style="margin:0 0 8px 0; color:${config.couleur};
                        border-bottom:2px solid ${config.couleur}; padding-bottom:4px;">
                        ${config.label}
                    </h4>
                    <table style="width:100%; font-size:13px;">`;

                for (let key in props) {
                    if (key !== 'geom' && key !== 'gid') {
                        html += `<tr>
                            <td style="padding:2px 5px 2px 0;">
                                <strong>${key.charAt(0).toUpperCase()+key.slice(1).replace(/_/g,' ')}:</strong>
                            </td>
                            <td>${props[key] || 'N/A'}</td>
                        </tr>`;
                    }
                }
                html += `</table></div>`;
                layer.bindPopup(html);

                layer.on('mouseover', () => layer.setStyle({ weight: 3, fillOpacity: 0.3 }));
                layer.on('mouseout',  () => layer.setStyle({ weight: 2, fillOpacity: 0.15 }));
            }
        });

        // Appliquer le pattern SVG sur tous les paths après ajout à la carte
        layers[nomTable].on('add', function() {
            setTimeout(() => {
                document.querySelectorAll(`.hachure-layer-${nomTable} path`)
                    .forEach(path => {
                        path.style.fill = `url(#hachure-${nomTable})`;
                    });
            }, 50);
        });

        console.log(`${geojson.features.length} éléments : ${nomTable}`);
        return geojson.features.length;

    } catch (error) {
        console.error(`Erreur ${nomTable}:`, error);
        return 0;
    }
}

async function toggleCategorie(nomTable) {
    // Charger si pas encore fait
    if (!layers[nomTable]) {
        showLoadingMessage(`Chargement ${categorieConfig[nomTable].label}...`);
        await loadCategorieLayer(nomTable);
        hideLoadingMessage();
    }

    // Toggle visibilité
    if (layers[nomTable]) {
        if (map.hasLayer(layers[nomTable])) {
            map.removeLayer(layers[nomTable]);
        } else {
            layers[nomTable].addTo(map);
        }
    }

    // Toggle bouton actif
    const btn = document.getElementById('btn-' + nomTable);
    if (btn) btn.classList.toggle('active');
}
// Recherche
function searchMap() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    if (!searchTerm) { alert('Veuillez entrer un terme de recherche'); return; }

    let found = false;

    if (layers.parcelles) {
        layers.parcelles.eachLayer(function(layer) {
            const props = layer.feature.properties;
            for (let key in props) {
                if (props[key] && props[key].toString().toLowerCase().includes(searchTerm)) {
                    if (layer.getBounds) map.fitBounds(layer.getBounds());
                    layer.openPopup();
                    found = true;
                    return false;
                }
            }
        });
    }

    if (!found) alert(`Aucun résultat trouvé pour : ${searchTerm}`);
}

// Statistiques
let parcelChart = null;

function updateStatistics() {

    let total = 0, affecte = 0, nonAffecte = 0;

    if (layers.parcelles) {
        layers.parcelles.eachLayer(function(layer) {
            total++;
            const statut =
                layer.feature.properties.statut ||
                layer.feature.properties.status ||
                layer.feature.properties.attribution_2026;

            if (!statut) { nonAffecte++; return; }
            const s = statut.toLowerCase().trim();
            if (s === 'affecte' || s === 'affecté' || s === 'oui') {
                affecte++;
            } else {
                nonAffecte++;
            }
        });
    }

    // Compter les équipements
    let totalEquipements = 0;
    if (layers.equipements) {
        layers.equipements.eachLayer(() => totalEquipements++);
    }

    // Mise à jour header badge parcelles
    const totalEl = document.getElementById('totalParcels');
    if (totalEl) totalEl.textContent = total;

    // Mise à jour header badge équipements
    const eqEl = document.getElementById('totalEquipements');
    if (eqEl) eqEl.textContent = totalEquipements;

    // Graphique
    const ctx = document.getElementById('parcelChart');
    if (!ctx) return;

    if (parcelChart) parcelChart.destroy();

    parcelChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Affectées', 'Non affectées', 'Équipements'],
            datasets: [{
                data: [affecte, nonAffecte, totalEquipements],
                backgroundColor: ['#22c55e', '#ef4444', '#3b82f6'],
                borderRadius: 6,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.parsed.y} éléments`
                    }
                }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

// Messages
function showLoadingMessage(message) {
    const statusDiv = document.getElementById('loadingStatus') || createStatusDiv();
    statusDiv.textContent = message;
    statusDiv.style.display = 'block';
}

function hideLoadingMessage() {
    const statusDiv = document.getElementById('loadingStatus');
    if (statusDiv) statusDiv.style.display = 'none';
}

function showErrorMessage(message) { alert(message); }

function createStatusDiv() {
    const div = document.createElement('div');
    div.id = 'loadingStatus';
    div.style.cssText = 'position:fixed; top:20px; right:20px; background: #1a7a3c; color:white; padding:15px 20px; border-radius:8px; z-index:10000; box-shadow:0 4px 6px rgba(0,0,0,0.2);';
    document.body.appendChild(div);
    return div;
}

// NOUVELLE FONCTION : Charger une commune spécifique
async function loadCommune(communeName) {
    try {
        const response = await fetch(API_URL + `thies_${communeName}.php`);
        const geojson = await response.json();
        
        if (geojson.error) {
            throw new Error(geojson.error);
        }
        
        // Créer la couche avec un style distinct
        communeLayers[communeName] = L.geoJSON(geojson, {
            style: function(feature) {
                return {
                    fillColor: getCommuneColor(communeName),
                    weight: 3,
                    opacity: 1,
                    color: '#000000',
                    fillOpacity: 0.3,
                    dashArray: '5, 5'
                };
            },
            onEachFeature: function(feature, layer) {
                const props = feature.properties;
                let popupContent = `<div style="min-width: 200px;">`;
                popupContent += `<h4 style="margin: 0 0 10px 0; color: #1e40af;">Commune de Thiès ${communeName.charAt(0).toUpperCase() + communeName.slice(1)}</h4>`;
                popupContent += `<table style="width: 100%; font-size: 13px;">`;
                
                for (let key in props) {
                    if (props.hasOwnProperty(key) && key !== 'id' && key !== 'gid') {
                        const label = key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' ');
                        popupContent += `<tr><td style="padding: 3px 5px 3px 0;"><strong>${label}:</strong></td><td style="padding: 3px 0;">${props[key] || 'N/A'}</td></tr>`;
                    }
                }
                
                popupContent += `</table></div>`;
                layer.bindPopup(popupContent);
            }
        });
        
        console.log(`Commune ${communeName} chargée`);
        
    } catch (error) {
        console.error(`Erreur chargement commune ${communeName}:`, error);
        showErrorMessage(`Impossible de charger la commune de Thiès ${communeName}`);
    }
}

// NOUVELLE FONCTION : Couleur par commune
function getCommuneColor(communeName) {
    const colors = {
        nord: '#ef4444',      // Rouge
        //est: '#22c55e',       // Vert
        //ouest: '#f59e0b'      // Orange
    };
    return colors[communeName] || '#9ca3af';
}

// NOUVELLE FONCTION : Toggle d'une commune
async function toggleCommune(communeName) {
    const checkbox = document.getElementById(`commune-${communeName}`);
    
    // Si la commune n'a jamais été chargée
    if (!communeLayers[communeName]) {
        if (checkbox.checked) {
            showLoadingMessage(`Chargement de Thiès ${communeName}...`);
            await loadCommune(communeName);
            hideLoadingMessage();
            
            if (communeLayers[communeName]) {
                communeLayers[communeName].addTo(map);
            }
        }
    } else {
        // Toggle la visibilité
        if (checkbox.checked) {
            communeLayers[communeName].addTo(map);
        } else {
            map.removeLayer(communeLayers[communeName]);
        }
    }
}

// ===== BASEMAP SWITCHER =====
const basemapTiles = {
    osm:       'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    satellite: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
    hybrid:    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
    topo:      'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
    dark:      'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
    carto:     'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png'
};

let currentBasemap = 'osm';
let basemapLayer = null;
let overlayLayer  = null;

// Stocker le fond OSM initial créé dans initMap()
// On intercepte après initMap en cherchant la couche tile existante
function initBasemapRef() {
    map.eachLayer(function(layer) {
        if (layer._url && layer._url.includes('openstreetmap')) {
            basemapLayer = layer;
        }
    });
}
// Appeler après loadAllLayers
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(initBasemapRef, 500);
});

function toggleBasemapPanel() {
    const panel = document.getElementById('basemapPanel');
    panel.classList.toggle('open');
    if (panel.classList.contains('open')) {
        setTimeout(() => {
            document.addEventListener('click', function closePanel(e) {
                if (!document.getElementById('basemapControl').contains(e.target)) {
                    panel.classList.remove('open');
                }
                document.removeEventListener('click', closePanel);
            });
        }, 10);
    }
}

function switchBasemap(type) {
    if (!map || currentBasemap === type) {
        document.getElementById('basemapPanel').classList.remove('open');
        return;
    }

    // Supprimer anciens fonds
    if (basemapLayer)  { map.removeLayer(basemapLayer);  basemapLayer = null; }
    if (overlayLayer)  { map.removeLayer(overlayLayer);  overlayLayer = null; }

    // Créer nouveau fond
    basemapLayer = L.tileLayer(basemapTiles[type], {
        maxZoom: type === 'topo' ? 17 : 19,
        subdomains: (type === 'satellite' || type === 'hybrid') ? '' : 'abcd',
        attribution: '© ' + type
    }).addTo(map);
    basemapLayer.bringToBack();

    // Overlay labels pour hybride
    if (type === 'hybrid') {
        overlayLayer = L.tileLayer(
            'https://{s}.basemaps.cartocdn.com/rastertiles/voyager_only_labels/{z}/{x}/{y}{r}.png',
            { maxZoom: 19, subdomains: 'abcd', opacity: 0.9 }
        ).addTo(map);
    }

    // Mettre à jour l'UI
    document.querySelectorAll('.basemap-option').forEach(el => el.classList.remove('active'));
    document.getElementById('opt-' + type).classList.add('active');

    currentBasemap = type;
    document.getElementById('basemapPanel').classList.remove('open');
}

// ===== AJOUT PARCELLE =====
let drawingMode   = false;
let drawnPoints   = [];
let drawMarkers   = [];
let drawPolyline  = null;
let drawPolygon   = null;

async function startDrawParcelle() {

    try {
        const res  = await fetch(API_URL + 'next_id.php');

        // Vérifier si la réponse est bien du JSON
        const contentType = res.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await res.text();
            console.error('Réponse non-JSON reçue:', text);
            alert('Erreur serveur — vérifiez api/next_id.php\n\n' + text.substring(0, 200));
            return;
        }

        const data = await res.json();

        if (data.error) {
            alert('Erreur API : ' + data.error);
            return;
        }

        document.getElementById('newParcelId').value = data.next_id || '—';

    } catch (err) {
        alert('Erreur connexion : ' + err.message);
        return;
    }

    // Suite du code existant...
    document.getElementById('parcelInfo').style.display    = 'none';
    document.getElementById('addParcelForm').style.display = 'block';
    document.getElementById('parcelPanelTitle').textContent = 'Nouvelle Parcelle';
    document.getElementById('drawStatus').style.display    = 'block';

    map.getContainer().style.cursor = 'crosshair';
    drawingMode  = true;
    drawnPoints  = [];
    drawMarkers  = [];
    drawPolyline = null;
    drawPolygon  = null;

    const btn = document.getElementById('btnAddParcelle');
    btn.textContent      = '⬛ Annuler dessin';
    btn.style.background = '#ef4444';
    btn.onclick          = cancelAddParcelle;

    map.on('click',    onMapDrawClick);
    map.on('dblclick', onMapDrawDblClick);
}

function onMapDrawClick(e) {
    if (!drawingMode) return;

    drawnPoints.push([e.latlng.lng, e.latlng.lat]);

    // Marker point
    const m = L.circleMarker(e.latlng, {
        radius: 5, color: '#1a7a3c', fillColor: '#1a7a3c',
        fillOpacity: 1, weight: 2
    }).addTo(map);
    drawMarkers.push(m);

    // Dessiner la ligne en cours
    if (drawPolyline) map.removeLayer(drawPolyline);
    if (drawnPoints.length > 1) {
        const latlngs = drawnPoints.map(p => [p[1], p[0]]);
        drawPolyline = L.polyline(latlngs, {
            color: '#1a7a3c', weight: 2, dashArray: '6 4'
        }).addTo(map);
    }

    // Mise à jour statut
    document.getElementById('drawStatus').innerHTML =
        `✏️ ${drawnPoints.length} point(s) — double-cliquez pour terminer`;
}

function onMapDrawDblClick(e) {
    if (!drawingMode || drawnPoints.length < 3) {
        alert('Tracez au moins 3 points pour former une parcelle.');
        return;
    }

    // Fermer le polygone
    drawnPoints.push(drawnPoints[0]);
    const latlngs = drawnPoints.map(p => [p[1], p[0]]);

    // Afficher le polygone final
    if (drawPolyline) map.removeLayer(drawPolyline);
    drawPolygon = L.polygon(latlngs, {
        color: '#1a7a3c', fillColor: '#22c55e',
        fillOpacity: 0.4, weight: 2
    }).addTo(map);

    // Désactiver le mode dessin (mais garder les données)
    map.off('click',    onMapDrawClick);
    map.off('dblclick', onMapDrawDblClick);
    map.getContainer().style.cursor = '';

    document.getElementById('drawStatus').style.background = '#d1fae5';
    document.getElementById('drawStatus').style.color      = '#065f46';
    document.getElementById('drawStatus').innerHTML =
        `✅ Polygone tracé (${drawnPoints.length - 1} points) — remplissez le formulaire`;
}

async function saveNewParcelle() {

    if (!drawPolygon && drawnPoints.length < 3) {
        alert('Vous devez d\'abord dessiner la parcelle sur la carte.');
        return;
    }

    const n_parcelle = document.getElementById('new_n_parcelle').value.trim();
    if (!n_parcelle) {
        alert('Le numéro de parcelle est obligatoire.');
        document.getElementById('new_n_parcelle').focus();
        return;
    }

    // Construire le GeoJSON de la géométrie
    const geomJson = JSON.stringify({
        type: 'Polygon',
        coordinates: [drawnPoints]
    });

    const payload = {
        n_parcelle:     n_parcelle,
        prenom_nom:     document.getElementById('new_prenom_nom').value,
        adresse:        document.getElementById('new_adresse').value,
        tel:            document.getElementById('new_tel').value,
        cni:            document.getElementById('new_cni').value,
        statut:         document.getElementById('new_statut').value,
        observation:    document.getElementById('new_observation').value,
        geom:           geomJson
    };

    try {
        showLoadingMessage('Enregistrement...');
        const res    = await fetch(API_URL + 'add_parcelle.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload)
        });
        const result = await res.json();
        hideLoadingMessage();

        if (result.success) {
            alert(`✅ ${result.message}`);
            cancelAddParcelle();
            await loadParcelles(); // recharger la carte
            updateStatistics();
        } else {
            alert('❌ Erreur : ' + result.error);
        }
    } catch (err) {
        hideLoadingMessage();
        alert('❌ Erreur réseau : ' + err.message);
    }
}

function cancelAddParcelle() {
    // Nettoyer la carte
    drawingMode = false;
    map.off('click',    onMapDrawClick);
    map.off('dblclick', onMapDrawDblClick);
    map.getContainer().style.cursor = '';

    drawMarkers.forEach(m => map.removeLayer(m));
    drawMarkers = [];
    drawnPoints = [];
    if (drawPolyline) { map.removeLayer(drawPolyline); drawPolyline = null; }
    if (drawPolygon)  { map.removeLayer(drawPolygon);  drawPolygon  = null; }

    // Restaurer l'UI
    document.getElementById('parcelInfo').style.display    = 'block';
    document.getElementById('addParcelForm').style.display = 'none';
    document.getElementById('parcelPanelTitle').textContent = 'Informations Parcelle';
    document.getElementById('drawStatus').style.display    = 'none';

    const btn = document.getElementById('btnAddParcelle');
    btn.textContent      = '＋ Ajouter parcelle';
    btn.style.background = 'var(--vert)';
    btn.onclick          = startDrawParcelle;
}