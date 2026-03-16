// geoportail_data.js
// Géoportail avec données depuis PostgreSQL + Leaflet

let map;
let communeLayer = null;
let layersControl = {};
let layers = {
    parcelles: null,
    equipements: null,
    routes: null
};

// Couches des communes
let communeLayers = {
    nord: null,
    est: null,
    ouest: null
};
// Configuration de l'API
const API_URL = 'api/';

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    initMap();
    loadAllLayers();
});

// Initialiser la carte Leaflet
function initMap() {
    // Créer la carte centrée sur Thiès
    map = L.map('map').setView([14.7886, -16.9256], 13);
    
    // Fond de carte OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);
    
    // Contrôle d'échelle
    L.control.scale({
        position: 'bottomright',
        imperial: false
    }).addTo(map);
}

// Charger toutes les couches
async function loadAllLayers() {
    showLoadingMessage('Chargement des données...');
    
    try {
        await Promise.all([
            loadParcelles(),
            loadEquipements(),
            loadRoutes()
        ]);
        
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
        
        if (geojson.error) {
            throw new Error(geojson.error);
        }
        
        if (layers.parcelles) {
            map.removeLayer(layers.parcelles);
        }
        
        // Nettoyer les anciens labels
        if (window.parcelLabels) {
            window.parcelLabels.forEach(label => map.removeLayer(label));
        }
        window.parcelLabels = [];
        
        layers.parcelles = L.geoJSON(geojson, {
            style: function(feature) {
                const statut = feature.properties.statut || feature.properties.status;
                return {
                    fillColor: getParcelColor(statut),
                    weight: 2,
                    opacity: 1,
                    color: '#1e40af',
                    fillOpacity: 0.6
                };
            },
            onEachFeature: function(feature, layer) {
    bindParcelPopup(feature, layer);
    
    const props = feature.properties;
    let numero = props['n_parcelle'] || props['nparcelle'] || props.numero || 
                props.numero_parcelle || props.num_parcelle || props.id || 
                props.gid || props.fid || 'N/A';
    
    // ← NOUVEAU : vérifier que la géométrie existe
    if (!feature.geometry || !feature.geometry.coordinates) return;
    
    let center;
    try {
        const geom = feature.geometry;
        
        if (geom.type === 'Polygon') {
            const coords = geom.coordinates[0];
            if (!coords || coords.length === 0) return; // ← sécurité
            let sumLat = 0, sumLng = 0;
            coords.forEach(coord => { sumLng += coord[0]; sumLat += coord[1]; });
            center = L.latLng(sumLat / coords.length, sumLng / coords.length);
            
        } else if (geom.type === 'MultiPolygon') {
            const coords = geom.coordinates[0][0];
            if (!coords || coords.length === 0) return; // ← sécurité
            let sumLat = 0, sumLng = 0;
            coords.forEach(coord => { sumLng += coord[0]; sumLat += coord[1]; });
            center = L.latLng(sumLat / coords.length, sumLng / coords.length);
            
        } else {
            return; // ← on supprime le getBounds() qui plante
        }
        
    } catch(e) {
        console.warn('Centroïde impossible pour feature:', props.id || numero, e);
        return;
    }
    
    if (!center) return; // ← sécurité finale
    
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
        
        console.log(`${geojson.features.length} parcelles chargées`);
        console.log(`${window.parcelLabels.length} labels créés`);
        
        // Gérer l'affichage selon le zoom
        updateLabelsVisibility();
        
        // Écouter les changements de zoom
        map.on('zoomend', updateLabelsVisibility);
        
        if (geojson.features.length > 0) {
            map.fitBounds(layers.parcelles.getBounds());
        }
        
    } catch (error) {
        console.error('Erreur parcelles:', error);
        throw error;
    }
}

// Fonction pour afficher/masquer les labels selon le zoom
function updateLabelsVisibility() {
    const currentZoom = map.getZoom();
    const minZoomForLabels = 18; // Afficher les numéros à partir du zoom 16
    
    if (!window.parcelLabels) return;
    
    window.parcelLabels.forEach(label => {
        if (currentZoom >= minZoomForLabels) {
            // Afficher le label
            if (!map.hasLayer(label)) {
                map.addLayer(label);
            }
        } else {
            // Masquer le label
            if (map.hasLayer(label)) {
                map.removeLayer(label);
            }
        }
    });
    
    console.log(`Zoom: ${currentZoom} - Labels ${currentZoom >= minZoomForLabels ? 'visibles' : 'masqués'}`);
}

// Fonction optionnelle pour changer le niveau de zoom minimum
function setLabelMinZoom(zoomLevel) {
    window.labelMinZoom = zoomLevel;
    updateLabelsVisibility();
    console.log(`Niveau de zoom minimum défini à: ${zoomLevel}`);
}

// Charger les équipements
async function loadEquipements() {
    try {
        const response = await fetch(API_URL + 'equipements.php');
        const geojson = await response.json();
        
        if (geojson.error) {
            throw new Error(geojson.error);
        }
        
        if (layers.equipements) {
            map.removeLayer(layers.equipements);
        }
        
        layers.equipements = L.geoJSON(geojson, {
            pointToLayer: function(feature, latlng) {
                return createEquipementMarker(feature, latlng);
            },
            onEachFeature: function(feature, layer) {
                bindEquipementPopup(feature, layer);
            }
        }).addTo(map);
        
        console.log(`${geojson.features.length} équipements chargés`);
        //renderEquipementsList(geojson.features);
        
    } catch (error) {
        console.error('Erreur équipements:', error);
    }
}

// Charger les routes
async function loadRoutes() {
    try {
        const response = await fetch(API_URL + 'routes.php');
        const geojson = await response.json();
        
        if (geojson.error) {
            throw new Error(geojson.error);
        }
        
        if (layers.routes) {
            map.removeLayer(layers.routes);
        }
        
        layers.routes = L.geoJSON(geojson, {
            style: function(feature) {
                return getRouteStyle(feature);
            },
            onEachFeature: function(feature, layer) {
                bindRoutePopup(feature, layer);
            }
        }).addTo(map);
        
        console.log(`${geojson.features.length} routes chargées`);
        
    } catch (error) {
        console.error('Erreur routes:', error);
    }
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
// Couleur des parcelles selon statut
function getParcelColor(statut) {
    if (!statut) return '#9ca3af';
    
    const statusLower = statut.toLowerCase();
    if (statusLower.includes('libre') || statusLower.includes('vacant')) {
        return '#22c55e';
    } else if (statusLower.includes('occupé') || statusLower.includes('occupied')) {
        return '#3b82f6';
    }
    return '#9ca3af';
}

// Popup pour les parcelles
function bindParcelPopup(feature, layer) {
    const props = feature.properties;
    
    // Construire le popup avec les propriétés disponibles
    let html = '<div style="min-width: 250px;">';
    html += '<h4 style="margin: 0 0 10px 0; color: #1e40af; border-bottom: 2px solid #3b82f6; padding-bottom: 5px;">Parcelle</h4>';
    html += '<table style="width: 100%; font-size: 13px;">';
    
    // Afficher toutes les propriétés de manière dynamique
    for (let key in props) {
        if (props.hasOwnProperty(key) && key !== 'id' && key !== 'gid') {
            const label = key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' ');
            html += `<tr><td style="padding: 3px 5px 3px 0;"><strong>${label}:</strong></td><td style="padding: 3px 0;">${props[key] || 'N/A'}</td></tr>`;
        }
    }
    
    html += '</table>';
    html += '</div>';
    
    layer.bindPopup(html);
    
    // Événements de survol
    layer.on('mouseover', function() {
        layer.setStyle({ weight: 3, fillOpacity: 0.8 });
    });
    
    layer.on('mouseout', function() {
        layer.setStyle({ weight: 2, fillOpacity: 0.6 });
    });
    
    // Clic pour afficher dans le panneau latéral
    layer.on('click', function() {
        displayParcelInfo(props);
    });
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
            html += `<tr><td style="padding: 3px 5px 3px 0;"><strong>${label}:</strong></td><td style="padding: 3px 0;">${props[key] || 'N/A'}</td></tr>`;
        }
    }

    html += '</table></div>';

    layer.bindPopup(html);

    // Clic pour afficher dans le panneau latéral
    layer.on('click', function() {
        displayEquipementInfo(props);
    });
}


// Style pour les routes
function getRouteStyle(feature) {
    const props = feature.properties;
    const type = (props.type || props.categorie || '').toLowerCase();
    
    // Couleurs selon le type de route
    let color = '#6b7280'; // Gris par défaut
    let weight = 3;
    
    if (type.includes('nationale') || type.includes('national')) {
        color = '#dc2626'; // Rouge
        weight = 5;
    } else if (type.includes('régionale') || type.includes('regional')) {
        color = '#f59e0b'; // Orange
        weight = 4;
    } else if (type.includes('départementale') || type.includes('departemental')) {
        color = '#eab308'; // Jaune
        weight = 3;
    } else if (type.includes('communale') || type.includes('municipal')) {
        color = '#22c55e'; // Vert
        weight = 2;
    } else if (type.includes('piste') || type.includes('chemin')) {
        color = '#8b5cf6'; // Violet
        weight = 2;
    }
    
    return {
        color: color,
        weight: weight,
        opacity: 0.8,
        lineCap: 'round',
        lineJoin: 'round'
    };
}
// Popup pour les routes
function bindRoutePopup(feature, layer) {
    const props = feature.properties;
    
    let html = '<div style="min-width: 200px;">';
    html += `<h4 style="margin: 0 0 10px 0; color: #6b7280;">Route</h4>`;
    html += '<table style="width: 100%; font-size: 13px;">';
    
    for (let key in props) {
        if (props.hasOwnProperty(key) && key !== 'id' && key !== 'gid') {
            const label = key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' ');
            html += `<tr><td style="padding: 3px 5px 3px 0;"><strong>${label}:</strong></td><td style="padding: 3px 0;">${props[key] || 'N/A'}</td></tr>`;
        }
    }
    
    html += '</table></div>';
    
    layer.bindPopup(html);
    
    // Effet au survol
    //layer.on('mouseover', function() {
        //layer.setStyle({
            //weight: layer.options.weight + 2,
            //opacity: 1
        //});
    //});
    
    //layer.on('mouseout', function() {
        //layer.setStyle({
            //weight: layer.options.weight,
            //opacity: 0.8
        //});
    //});
}



// Afficher les infos de parcelle dans le panneau
function displayParcelInfo(props) {
    const infoDiv = document.getElementById('parcelInfo');
    
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

// Afficher la liste des équipements
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


// Toggle des couches
function toggleLayer(layerName) {
    const layer = layers[layerName];
    
    if (!layer) {
        console.warn(`Couche ${layerName} non chargée`);
        return;
    }
    
    if (map.hasLayer(layer)) {
        map.removeLayer(layer);
    } else {
        map.addLayer(layer);
    }
    
    // Mettre à jour le style du bouton
    const buttons = document.querySelectorAll('.filter-btn');
    buttons.forEach(btn => {
        if (btn.textContent.toLowerCase().includes(layerName)) {
            btn.classList.toggle('active');
        }
    });
}

// Recherche
function searchMap() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    
    if (!searchTerm) {
        alert('Veuillez entrer un terme de recherche');
        return;
    }
    
    // Rechercher dans toutes les couches
    let found = false;
    
    for (let layerName in layers) {
        if (layers[layerName]) {
            layers[layerName].eachLayer(function(layer) {
                const props = layer.feature.properties;
                
                // Rechercher dans toutes les propriétés
                for (let key in props) {
                    if (props[key] && props[key].toString().toLowerCase().includes(searchTerm)) {
                        // Zoomer sur l'élément trouvé
                        if (layer.getBounds) {
                            map.fitBounds(layer.getBounds());
                        } else if (layer.getLatLng) {
                            map.setView(layer.getLatLng(), 17);
                        }
                        
                        // Ouvrir le popup
                        layer.openPopup();
                        found = true;
                        return false; // Sortir de la boucle
                    }
                }
            });
            
            if (found) break;
        }
    }
    
    if (!found) {
        alert(`Aucun résultat trouvé pour: ${searchTerm}`);
    }
}

// Mettre à jour les statistiques
function updateStatistics() {
    let totalParcelles = 0;
    let totalEquipements = 0;
    let totalRoutes = 0;
    
    if (layers.routes) {
        layers.routes.eachLayer(() => totalRoutes++);
    }
    
    if (layers.parcelles) {
        layers.parcelles.eachLayer(() => totalParcelles++);
    }
    
    if (layers.equipements) {
        layers.equipements.eachLayer(() => totalEquipements++);
    }
    
    document.getElementById('totalParcels').textContent = totalParcelles;
    document.getElementById('totalEquipements').textContent = totalEquipements;
    document.getElementById('totalRoutes').textContent = totalRoutes;
    
}

// Messages de chargement
function showLoadingMessage(message) {
    const statusDiv = document.getElementById('loadingStatus') || createStatusDiv();
    statusDiv.textContent = message;
    statusDiv.style.display = 'block';
}

function hideLoadingMessage() {
    const statusDiv = document.getElementById('loadingStatus');
    if (statusDiv) {
        statusDiv.style.display = 'none';
    }
}

function showErrorMessage(message) {
    alert(message);
}

function createStatusDiv() {
    const div = document.createElement('div');
    div.id = 'loadingStatus';
    div.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #2563eb; color: white; padding: 15px 20px; border-radius: 8px; z-index: 10000; box-shadow: 0 4px 6px rgba(0,0,0,0.2);';
    document.body.appendChild(div);
    return div;
}


// Ajouter cette fonction dans geoportail_data.js

// Fonction pour afficher le formulaire d'édition
function displayParcelInfo(props) {
    const infoDiv = document.getElementById('parcelInfo');
    if (!infoDiv) return;
    
    let html = '<div class="edit-form">';
    html += '<h3 style="margin-bottom: 15px; color: #1e40af;"></h3>';
    html += '<form id="editParcelForm" onsubmit="updateParcel(event)">';
    
    // Champs éditables
    const editableFields = ['titulaire', 'adresse', 'surface', 'montant', 'n_parcelle', 'acheteur', 'adresse_2'];
    
    for (let key in props) {
        if (props.hasOwnProperty(key) && key !== 'geom') {
            const label = key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' ');
            const isEditable = editableFields.includes(key.toLowerCase());
            
            html += `
                <div class="form-group" style="margin-bottom: 12px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px; color: #374151;">
                        ${label}:
                    </label>
            `;
            
            if (isEditable) {
                // Champ éditable
                if (key.toLowerCase() === 'observations') {
                    html += `
                        <textarea 
                            name="${key}" 
                            class="edit-input"
                            style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 14px;"
                            rows="3"
                        >${props[key] || ''}</textarea>
                    `;
                } else {
                    html += `
                        <input 
                            type="text" 
                            name="${key}" 
                            value="${props[key] || ''}"
                            class="edit-input"
                            style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 14px;"
                        />
                    `;
                }
            } else {
                // Champ en lecture seule
                html += `
                    <input 
                        type="text" 
                        value="${props[key] || 'N/A'}"
                        readonly
                        style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 4px; background: #f9fafb; font-size: 14px; color: #6b7280;"
                    />
                `;
                // Stocker l'ID pour la mise à jour
                if (key.toLowerCase() === 'id' || key.toLowerCase() === 'gid') {
                    html += `<input type="hidden" name="${key}" value="${props[key]}" />`;
                }
            }
            
            html += '</div>';
        }
    }
    
    html += `
        <div style="display: flex; gap: 10px; margin-top: 20px;">
            <button 
                type="submit" 
                style="flex: 1; padding: 10px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 14px;"
                onmouseover="this.style.background='#2563eb'"
                onmouseout="this.style.background='#3b82f6'"
            >
                💾 Enregistrer
            </button>
            <button 
                type="button" 
                onclick="cancelEdit()"
                style="flex: 1; padding: 10px; background: #6b7280; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 14px;"
                onmouseover="this.style.background='#4b5563'"
                onmouseout="this.style.background='#6b7280'"
            >
                ❌ Annuler
            </button>
        </div>
    `;
    
    html += '</form></div>';
    infoDiv.innerHTML = html;
}

// Fonction pour annuler l'édition
function cancelEdit() {
    const infoDiv = document.getElementById('parcelInfo');
    if (infoDiv) {
        infoDiv.innerHTML = '<p style="color: #6b7280; text-align: center; padding: 20px;">Sélectionnez une parcelle pour voir ses informations</p>';
    }
}

// Fonction pour mettre à jour la parcelle
async function updateParcel(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const data = {};
    
    // Convertir FormData en objet
    formData.forEach((value, key) => {
        data[key] = value;
    });
    
    // Afficher un message de chargement
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '⏳ Enregistrement...';
    submitBtn.disabled = true;
    
    try {
        const response = await fetch(API_URL + 'update_parcelle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Parcelle mise à jour avec succès !');
            
            // Recharger les parcelles pour voir les changements
            await loadParcelles();
            
            // Fermer le formulaire
            cancelEdit();
        } else {
            throw new Error(result.error || 'Erreur lors de la mise à jour');
        }
        
    } catch (error) {
        console.error('Erreur:', error);
        alert('❌ Erreur lors de la mise à jour: ' + error.message);
        
        // Restaurer le bouton
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

// IMPORTANT : Modifier aussi la fonction bindParcelPopup pour ajouter un bouton "Éditer"
function bindParcelPopup(feature, layer) {
    const props = feature.properties;
    
    let html = '<div style="min-width: 250px;">';
    html += '<h4 style="margin: 0 0 10px 0; color: #1e40af; border-bottom: 2px solid #3b82f6; padding-bottom: 5px;">Parcelle</h4>';
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
            style="width: 100%; margin-top: 10px; padding: 8px; background: #3b82f6; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;"
        >
            ✏️ Modifier
        </button>
    `;
    html += '</div>';
    
    layer.bindPopup(html);
    
    layer.on('mouseover', function() {
        layer.setStyle({ weight: 3, fillOpacity: 0.8 });
    });
    
    layer.on('mouseout', function() {
        layer.setStyle({ weight: 2, fillOpacity: 0.6 });
    });
    
    layer.on('click', function() {
        displayParcelInfo(props);
    });
}
// Toggle du menu mobile
function toggleMenu() {
    const navLinks = document.getElementById('navLinks');
    navLinks.classList.toggle('active');
}

// Fermer le menu mobile lors du clic sur un lien
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.nav-links a');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            const navMenu = document.getElementById('navLinks');
            if (navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
            }
        });
    });
    
    // Fermer le menu si on clique en dehors
    document.addEventListener('click', function(event) {
        const navMenu = document.getElementById('navLinks');
        const menuToggle = document.querySelector('.menu-toggle');
        const navbar = document.querySelector('.navbar');
        
        if (navMenu.classList.contains('active') && 
            !navbar.contains(event.target)) {
            navMenu.classList.remove('active');
        }
    });
    
    // Gestion du redimensionnement de la fenêtre
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            const navMenu = document.getElementById('navLinks');
            // Fermer le menu mobile si on passe en mode desktop
            if (window.innerWidth > 768 && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
            }
        }, 250);
    });
    
    // Animation au scroll (optionnel)
    handleScrollAnimations();
});

// Animation des éléments au scroll
function handleScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observer les cartes de services et actualités
    const cards = document.querySelectorAll('.service-card, .news-card');
    cards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
}

// Smooth scroll pour les ancres
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Détection du scroll pour la navbar
let lastScroll = 0;
const navbar = document.querySelector('.navbar');

window.addEventListener('scroll', function() {
    const currentScroll = window.pageYOffset;
    
    // Ajouter une ombre plus prononcée lors du scroll
    if (currentScroll > 50) {
        navbar.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.15)';
    } else {
        navbar.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
    }
    
    lastScroll = currentScroll;
});

// Fonction pour afficher un message de chargement
function showLoading(message = 'Chargement...') {
    const loadingDiv = document.createElement('div');
    loadingDiv.id = 'loading-overlay';
    loadingDiv.innerHTML = `
        <div style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        ">
            <div style="
                background: white;
                padding: 2rem 3rem;
                border-radius: 10px;
                text-align: center;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            ">
                <div style="
                    width: 50px;
                    height: 50px;
                    border: 4px solid #e5e7eb;
                    border-top: 4px solid #3b82f6;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                    margin: 0 auto 1rem;
                "></div>
                <p style="color: #374151; font-weight: 500;">${message}</p>
            </div>
        </div>
    `;
    
    // Ajouter l'animation de rotation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
    
    document.body.appendChild(loadingDiv);
}

// Fonction pour cacher le message de chargement
function hideLoading() {
    const loadingDiv = document.getElementById('loading-overlay');
    if (loadingDiv) {
        loadingDiv.remove();
    }
}

// Validation des formulaires (si présents)
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.style.borderColor = '#ef4444';
            
            // Retirer la bordure rouge après focus
            input.addEventListener('focus', function() {
                this.style.borderColor = '#3b82f6';
            }, { once: true });
        }
    });
    
    if (!isValid) {
        alert('Veuillez remplir tous les champs obligatoires');
    }
    
    return isValid;
}

// Gestion des erreurs d'images
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        img.addEventListener('error', function() {
            this.style.display = 'none';
            console.warn('Image non trouvée:', this.src);
        });
    });
});

// Détection du support des fonctionnalités modernes
function checkBrowserSupport() {
    const features = {
        grid: CSS.supports('display', 'grid'),
        flexbox: CSS.supports('display', 'flex'),
        customProperties: CSS.supports('--custom', 'value')
    };
    
    if (!features.grid || !features.flexbox) {
        console.warn('Navigateur ancien détecté. Certaines fonctionnalités peuvent ne pas fonctionner correctement.');
    }
    
    return features;
}

// Initialisation
checkBrowserSupport();

// Export des fonctions pour utilisation globale
window.toggleMenu = toggleMenu;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.validateForm = validateForm;