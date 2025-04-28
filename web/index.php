<?php
// Configuration
$api_url = "http://localhost:8000";

// Fonction pour appeler l'API
function callAPI($endpoint)
{
    global $api_url;
    $url = $api_url . $endpoint;

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);

    if ($response === false) {
        return ["error" => curl_error($curl)];
    }

    curl_close($curl);
    return json_decode($response, true);
}

// Récupérer les données des départements
$departements = callAPI("/departements");
$regions = callAPI("/regions");

// Préparer les données pour les départements sur la carte
$mapData = [];
if (!isset($departements["error"])) {
    foreach ($departements as $dept) {
        $mapData[$dept["code_departement"]] = [
            "nom" => $dept["nom_departement"],
            "taux" => [
                "t1" => $dept["trimestre_1"],
                "t2" => $dept["trimestre_2"],
                "t3" => $dept["trimestre_3"]
            ]
        ];
    }
}

// Préparer les données des régions
$regionData = [];
if (!isset($regions["error"])) {
    foreach ($regions as $region) {
        $regionData[$region["code_region"]] = [
            "nom" => $region["nom_region"],
            "taux" => [
                "t1" => $region["trimestre_1"],
                "t2" => $region["trimestre_2"],
                "t3" => $region["trimestre_3"]
            ]
        ];
    }
}

// Sélectionner le trimestre à afficher (par défaut le 3e)
$selectedTrimestre = isset($_GET['trimestre']) ? $_GET['trimestre'] : 3;

// Déterminer le mode d'affichage (département ou région)
$viewMode = isset($_GET['view']) ? $_GET['view'] : 'departements';

// Mode sombre ou clair (par défaut clair)
$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'true';

// Titre dynamique selon le mode d'affichage
$pageTitle = $viewMode === 'regions' ? 'Taux de chômage par région' : 'Taux de chômage par département';

// Charger la date de diffusion depuis le fichier
$dateFilePath = __DIR__ . '/data/last_publication_date.txt';
$lastPublicationDate = 'Non disponible';
if (file_exists($dateFilePath)) {
    $lastPublicationDate = trim(file_get_contents($dateFilePath));
}

// Charger l'heure du dernier check depuis le fichier
$lastCheckFilePath = __DIR__ . '/data/last_check_time.txt';
$lastCheckTime = 'Non disponible';
$timeRemaining = 600; // 10 minutes en secondes
if (file_exists($lastCheckFilePath)) {
    $lastCheckTime = trim(file_get_contents($lastCheckFilePath));
    $lastCheckTimestamp = strtotime($lastCheckTime);
    $timeElapsed = time() - $lastCheckTimestamp;
    $timeRemaining = max(600 - $timeElapsed, 0); // Calculer le temps restant
}
?>

<!DOCTYPE html>
<html lang="fr" class="<?php echo $darkMode ? 'dark-mode' : ''; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carte du chômage en France - <?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/share.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</head>

<body>
    <header>
        <div class="header-container">
            <h1><i class="fas fa-chart-line"></i> Taux de chômage en France</h1>
            <div class="theme-toggle">
                <button id="theme-switch" title="Changer de thème">
                    <i class="fas <?php echo $darkMode ? 'fa-sun' : 'fa-moon'; ?>"></i>
                </button>
            </div>
        </div>

        <div class="topbar">
            <div>
                <p>Date de diffusion : <strong><?php echo $lastPublicationDate; ?></strong></p>
            </div>
            <div>
                <p>Prochain check dans : <span id="timer">...</span></p>
            </div>
        </div>
        

        <div class="controls">
            <form method="get" action="" id="control-form">
                <div class="control-group">
                    <label for="trimestre"><i class="fas fa-calendar-alt"></i> Trimestre :  </label>
                    <select name="trimestre" id="trimestre">
                        <option value="1" <?php echo $selectedTrimestre == 1 ? 'selected' : ''; ?>>Trimestre 1</option>
                        <option value="2" <?php echo $selectedTrimestre == 2 ? 'selected' : ''; ?>>Trimestre 2</option>
                        <option value="3" <?php echo $selectedTrimestre == 3 ? 'selected' : ''; ?>>Trimestre 3</option>
                    </select>
                </div>

                <div class="control-group">
                    <label for="view"><i class="fas fa-layer-group"></i> Affichage :</label>
                    <div class="view-toggle">
                        <button type="button" class="view-btn <?php echo $viewMode == 'departements' ? 'active' : ''; ?>" data-view="departements">Départements</button>
                        <button type="button" class="view-btn <?php echo $viewMode == 'regions' ? 'active' : ''; ?>" data-view="regions">Régions</button>
                    </div>
                    <input type="hidden" name="view" id="view-input" value="<?php echo $viewMode; ?>">
                </div>

                <div class="search-container">
                    <input type="text" id="search-area" placeholder="Rechercher un département ou une région...">
                    <button type="button" id="search-btn"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>

        <!-- Boutons pour switch de page -->
        <div class="page-toggle">
            <button type="button" class="page-view-btn <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" onclick="window.location.href='index.php'">Carte</button>
            <button type="button" class="page-view-btn <?php echo basename($_SERVER['PHP_SELF']) == 'order_liste.php' ? 'active' : ''; ?>" onclick="window.location.href='order_liste.php'">Liste</button>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="map-container">
                <div id="map"></div>
                <div class="legend">
                    <h3>Légende (Taux de chômage)</h3>
                    <div class="legend-item"><span class="color-box" style="background-color: #a1d99b;"></span>
                        < 6%</div>
                            <div class="legend-item"><span class="color-box" style="background-color: #fee08b;"></span> 6% - 8%</div>
                            <div class="legend-item"><span class="color-box" style="background-color: #fc8d59;"></span> 8% - 10%</div>
                            <div class="legend-item"><span class="color-box" style="background-color: #d73027;"></span> > 10%</div>
                    </div>
                    <div class="loading-indicator">
                        <div class="spinner"></div>
                        <div>Chargement des données...</div>
                    </div>
                </div>
                <div class="info-panel">
                    <h2><i class="fas fa-info-circle"></i> Informations détaillées</h2>
                    <div id="info-content">
                        <div class="no-data">
                            <p>Cliquez sur un département pour voir les détails</p>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="evolution-chart"></canvas>
                    </div>
                </div>
            </div>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> - Observatoire du chômage en France | <i class="fas fa-chart-line"></i> Données actualisées</p>
    </footer>

    <script>
        // Variables globales
        const mapData = <?php echo json_encode($mapData); ?>;
        const regionData = <?php echo json_encode($regionData); ?>;
        const selectedTrimestre = <?php echo $selectedTrimestre; ?>;
        let viewMode = '<?php echo $viewMode; ?>';
        let map, geoJsonLayer, selectedArea = null;
        let evolutionChart = null;
        let tooltip = null;
        let searchResults = [];

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            // Créer une infobulle personnalisée
            tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            document.body.appendChild(tooltip);

            // Initialiser la carte
            initMap();

            // Configurer les contrôles
            const trimestreSelect = document.getElementById('trimestre');
            trimestreSelect.addEventListener('change', function() {
                document.getElementById('control-form').submit();
            });

            // Configurer les boutons de vue
            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    viewMode = this.dataset.view;
                    document.getElementById('view-input').value = viewMode;

                    // Mettre à jour la classe active
                    document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');

                    // Recharger la carte
                    loadGeoJSON();

                    // Réinitialiser l'info-panel
                    resetInfoPanel();
                });
            });

            // Configurer le bouton de thème
            const themeBtn = document.getElementById('theme-switch');
            themeBtn.addEventListener('click', toggleTheme);

            // Configurer la recherche
            const searchInput = document.getElementById('search-area');
            const searchBtn = document.getElementById('search-btn');

            searchInput.addEventListener('input', function() {
                performSearch(this.value);
            });

            searchBtn.addEventListener('click', function() {
                performSearch(searchInput.value);
            });

            // Ajouter les boutons d'export dans le panneau d'informations
            const infoPanel = document.querySelector('.info-panel');
            const actionButtons = document.createElement('div');
            actionButtons.className = 'action-buttons';
            actionButtons.innerHTML = `
                <button id="export-csv" title="Exporter en CSV">
                    <i class="fas fa-file-csv"></i> Exporter
                </button>
                <button id="share-data" title="Partager" onclick="captureAndShare()">
                    <i class="fas fa-share-alt"></i> Partager
                </button>
            `;
            infoPanel.appendChild(actionButtons);

            // Configurer les boutons d'action
            document.getElementById('export-csv').addEventListener('click', exportDataAsCSV);
        });

        // Recherche de départements ou régions pour la page index.php
        function performSearch(query) {
            if (!query || query.length < 2) {
                searchResults = [];
                return;
            }

            query = query.toLowerCase();

            // Rechercher dans les données actuelles (départements ou régions)
            const currentData = viewMode === 'regions' ? regionData : mapData;
            searchResults = [];

            for (const code in currentData) {
                const item = currentData[code];
                if (item.nom.toLowerCase().includes(query) || code.toLowerCase().includes(query)) {
                    searchResults.push({
                        code,
                        nom: item.nom
                    });
                }
            }

            console.log("Résultats de recherche:", searchResults);

            if (searchResults.length > 0) {
                // Mettre en évidence le premier résultat sur la carte
                highlightArea(searchResults[0].code);
            }
        }

        // Mettre en évidence une zone sur la carte
        function highlightArea(code) {
            if (!geoJsonLayer) return;

            geoJsonLayer.eachLayer(function(layer) {
                // Obtenir le code de la feature
                const featureCode = viewMode === 'regions' ?
                    layer.feature.properties.code_region || layer.feature.properties.code :
                    layer.feature.properties.code;

                if (featureCode === code) {
                    // Simuler un clic sur cette zone
                    layer.fire('click');

                    // Animation du zoom vers cette zone
                    map.fitBounds(layer.getBounds(), {
                        padding: [50, 50],
                        maxZoom: viewMode === 'regions' ? 6 : 8,
                        animate: true,
                        duration: 1
                    });
                }
            });
        }

        // Bascule entre thème clair et sombre
        function toggleTheme() {
            const html = document.documentElement;
            const isDarkMode = html.classList.toggle('dark-mode');
            const icon = document.querySelector('#theme-switch i');

            // Changer l'icône
            icon.className = isDarkMode ? 'fas fa-sun' : 'fas fa-moon';

            // Enregistrer la préférence dans un cookie
            document.cookie = `darkMode=${isDarkMode}; path=/; max-age=${60 * 60 * 24 * 365}`;

            // Mise à jour du style de carte si nécessaire
            if (map) {
                // Changer le fond de carte pour le thème sombre/clair
                const tileLayer = map.eachLayer(layer => {
                    if (layer instanceof L.TileLayer) {
                        map.removeLayer(layer);
                    }
                });

                const tileStyle = isDarkMode ?
                    'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png' :
                    'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';

                L.tileLayer(tileStyle, {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>, &copy; <a href="https://carto.com/attributions">CARTO</a>',
                    subdomains: 'abcd',
                    maxZoom: 19
                }).addTo(map);

                // Recharger le GeoJSON pour appliquer les nouveaux styles
                if (selectedArea) {
                    // Stocker la zone sélectionnée
                    const tempArea = selectedArea;
                    loadGeoJSON();
                    // Restaurer la sélection après le rechargement
                    setTimeout(() => highlightArea(tempArea), 500);
                } else {
                    loadGeoJSON();
                }
            }

            // Mise à jour du graphique si présent
            if (evolutionChart) {
                updateChartTheme(evolutionChart, isDarkMode);
            }
        }

        // Mettre à jour le thème du graphique en mode dark ou clair
        function updateChartTheme(chart, isDarkMode) {
            if (!chart) return;

            const textColor = isDarkMode ? '#f8f9fa' : '#333';
            const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
            const backgroundColor = isDarkMode ? 'rgba(52, 152, 219, 0.2)' : 'rgba(52, 152, 219, 0.1)';

            // Mettre à jour les couleurs du graphique en mode dark ou clair
            chart.data.datasets.forEach(dataset => {
                if (isDarkMode) {
                    dataset.backgroundColor = viewMode === 'regions' ? 'rgba(142, 68, 173, 0.2)' : backgroundColor;
                    dataset.pointBackgroundColor = '#2c3e50';
                } else {
                    dataset.backgroundColor = viewMode === 'regions' ? 'rgba(142, 68, 173, 0.1)' : 'rgba(52, 152, 219, 0.1)';
                    dataset.pointBackgroundColor = '#fff';
                }
            });

            chart.options.scales.y.grid.color = gridColor;
            chart.options.scales.y.ticks.color = textColor;
            chart.options.scales.x.ticks.color = textColor;
            chart.options.plugins.title.color = textColor;
            chart.options.plugins.tooltip.backgroundColor = isDarkMode ? 'rgba(26, 37, 48, 0.9)' : 'rgba(44, 62, 80, 0.9)';

            chart.update();
        }

        // Fonction export des données en CSV
        function exportDataAsCSV() {
            if (!selectedArea) {
                alert('Veuillez d\'abord sélectionner une zone sur la carte.');
                return;
            }

            const dataSource = viewMode === 'regions' ? regionData : mapData;
            const area = dataSource[selectedArea];

            if (!area) return;

            // Construire les données CSV
            const headers = ['Zone', 'Code', 'Trimestre 1', 'Trimestre 2', 'Trimestre 3'];
            const values = [area.nom, selectedArea, area.taux.t1, area.taux.t2, area.taux.t3];

            let csvContent = headers.join(',') + '\n' + values.join(',');

            // Créer un blob et un lien de téléchargement
            const blob = new Blob([csvContent], {
                type: 'text/csv;charset=utf-8;'
            });
            const link = document.createElement('a');

            // Créer une URL pour le blob et configurer le lien
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `chomage_${viewMode}_${selectedArea}_${new Date().toISOString().slice(0,10)}.csv`);
            link.style.visibility = 'hidden';

            // Ajouter le lien au document et simuler un clic
            document.body.appendChild(link);
            link.click();

            // Nettoyer
            document.body.removeChild(link);
            URL.revokeObjectURL(url);

            // Afficher une confirmation
            const infoContent = document.getElementById('info-content');
            const notification = document.createElement('div');
            notification.className = 'alert';
            notification.innerHTML = `<i class="fas fa-check-circle"></i> Données exportées avec succès.`;
            infoContent.prepend(notification);

            // Supprimer la notification après quelques secondes
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Partager les données
        function shareData() {
            if (!selectedArea) {
                alert('Veuillez d\'abord sélectionner une zone sur la carte.');
                return;
            }

            // Construire l'URL avec les paramètres
            const url = new URL(window.location.href);
            url.searchParams.set('view', viewMode);
            url.searchParams.set('trimestre', selectedTrimestre);
            url.searchParams.set('highlight', selectedArea);

            // Copier l'URL dans le presse-papier
            navigator.clipboard.writeText(url.href)
                .then(() => {
                    // Afficher une confirmation
                    const infoContent = document.getElementById('info-content');
                    const notification = document.createElement('div');
                    notification.className = 'alert';
                    notification.innerHTML = `
                        <i class="fas fa-check-circle"></i> Lien copié dans le presse-papier.<br>
                        <small>URL: ${url.href}</small>
                    `;
                    infoContent.prepend(notification);

                    // Supprimer la notification après quelques secondes
                    setTimeout(() => {
                        notification.remove();
                    }, 3000);
                })
                .catch(err => {
                    console.error('Erreur lors de la copie du lien:', err);
                    alert('Impossible de copier le lien. Veuillez le copier manuellement.');
                });
        }

        // Réinitialiser le panneau d'information
        function resetInfoPanel() {
            const infoContent = document.getElementById('info-content');
            infoContent.innerHTML = `
                <div class="no-data">
                    <p>Cliquez sur ${viewMode === 'regions' ? 'une région' : 'un département'} pour voir les détails</p>
                </div>
            `;

            // Effacer le graphique s'il existe
            if (evolutionChart) {
                evolutionChart.destroy();
                evolutionChart = null;
            }
        }

        // Initialiser la carte
        function initMap() {
            const isDarkMode = document.documentElement.classList.contains('dark-mode');
            const tileStyle = isDarkMode ?
                'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png' :
                'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';

            map = L.map('map', {
                zoomControl: false,
                attributionControl: false
            }).setView([46.603354, 1.888334], 6);

            //  Contrôles de zoom dans une meilleure position
            L.control.zoom({
                position: 'bottomright'
            }).addTo(map);

            // Le fond de carte avec un style plus moderne
            L.tileLayer(tileStyle, {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>, &copy; <a href="https://carto.com/attributions">CARTO</a>',
                subdomains: 'abcd',
                maxZoom: 19
            }).addTo(map);

            // Charger les données géographiques
            loadGeoJSON();

            // Paramètre pour que la carte s'adapte correctement lors du redimensionnement
            window.addEventListener('resize', function() {
                map.invalidateSize();
                adjustMapHeight();
            });

            // Appeler une fois au chargement
            adjustMapHeight();

            // on vérifie si un highlight est demandé dans l'URL
            const urlParams = new URLSearchParams(window.location.search);
            const highlightArea = urlParams.get('highlight');

            if (highlightArea) {
                // Attendre que les données soient chargées
                setTimeout(() => {
                    highlightArea(highlightArea);
                }, 1000);
            }
        }

        // Fonction pour ajuster la hauteur de la carte et du panneau d'informations
        function adjustMapHeight() {
            const infoPanel = document.querySelector('.info-panel');
            const mapContainer = document.querySelector('.map-container');

            if (infoPanel && mapContainer && window.innerWidth >= 992) {
                // Obtenir les dimensions de la fenêtre
                const windowHeight = window.innerHeight;
                // Ajuster la hauteur des deux éléments pour qu'ils soient identiques
                const height = Math.max(500, windowHeight * 0.7); // au moins 500px ou 70% de la hauteur de la fenêtre

                mapContainer.style.height = `${height}px`;
                infoPanel.style.height = `${height}px`;

                // Forcer la mise à jour de la taille de la carte
                if (map) {
                    map.invalidateSize();
                }
            }
        }

        // Charger les données GeoJSON selon le mode de vue
        function loadGeoJSON() {
            // Afficher l'indicateur de chargement
            document.querySelector('.loading-indicator').style.display = 'block';

            // Supprimer la couche existante si présente
            if (geoJsonLayer) {
                map.removeLayer(geoJsonLayer);
            }

            // Réinitialiser la zone sélectionnée
            selectedArea = null;

            // Déterminer le fichier à charger
            const geoJsonFile = viewMode === 'regions' ? 'data/regions.geojson' : 'data/departements.geojson';

            // Charger le fichier GeoJSON
            fetch(geoJsonFile)
                .then(response => response.json())
                .then(data => {
                    createGeoJsonLayer(data);
                    document.querySelector('.loading-indicator').style.display = 'none';
                })
                .catch(error => {
                    console.error(`Erreur lors du chargement des données GeoJSON (${viewMode}):`, error);
                    document.querySelector('.loading-indicator').style.display = 'none';

                    // Afficher un message d'erreur dans la carte
                    const mapContainer = document.querySelector('.map-container');
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'error-message';
                    errorMsg.innerHTML = `
                        <div class="alert">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Impossible de charger les données géographiques. Veuillez vérifier que le fichier 
                            ${geoJsonFile} existe et est accessible.
                        </div>
                    `;
                    mapContainer.appendChild(errorMsg);
                });
        }

        // Créer la couche GeoJSON avec les styles et interactions
        function createGeoJsonLayer(data) {
            geoJsonLayer = L.geoJSON(data, {
                style: function(feature) {
                    // Identifier la clé à utiliser selon le mode d'affichage
                    const code = viewMode === 'regions' ? feature.properties.code_region || feature.properties.code : feature.properties.code;
                    const dataSource = viewMode === 'regions' ? regionData : mapData;

                    // Debug: afficher les informations dans la console
                    console.log(`Style pour ${viewMode} code:`, code);
                    console.log(`Données disponibles:`, dataSource[code]);

                    const area = dataSource[code];
                    let tauxChomage = 0;

                    if (area) {
                        tauxChomage = area.taux['t' + selectedTrimestre];
                    }

                    // Déterminer la couleur en fonction du taux de chômage (code couleur)
                    let fillColor = '#CCCCCC'; // Gris par défaut si pas de données
                    if (tauxChomage) {
                        if (tauxChomage < 6) fillColor = '#a1d99b';
                        else if (tauxChomage < 8) fillColor = '#fee08b';
                        else if (tauxChomage < 10) fillColor = '#fc8d59';
                        else fillColor = '#d73027';
                    }

                    return {
                        fillColor: fillColor,
                        weight: 1.5,
                        opacity: 1,
                        color: 'white',
                        fillOpacity: 0.8,
                        dashArray: selectedArea === code ? '3' : '',
                        className: selectedArea === code ? 'selected-area' : ''
                    };
                },
                onEachFeature: function(feature, layer) {
                    // Utiliser le bon code d'identification selon le mode
                    const code = viewMode === 'regions' ? feature.properties.code_region || feature.properties.code : feature.properties.code;
                    const dataSource = viewMode === 'regions' ? regionData : mapData;

                    console.log(`Interaction pour ${viewMode} code:`, code);

                    const area = dataSource[code];

                    if (area) {
                        // Événement au survol pour afficher l'infobulle
                        layer.on('mouseover', function(e) {
                            const tooltipContent = `
                                <strong>${area.nom} (${code})</strong><br>
                                Chômage T${selectedTrimestre}: ${area.taux['t' + selectedTrimestre]}%
                            `;

                            tooltip.innerHTML = tooltipContent;
                            tooltip.style.opacity = '1';

                            // Positionner l'infobulle près du curseur
                            const pos = e.originalEvent;
                            tooltip.style.left = (pos.pageX + 10) + 'px';
                            tooltip.style.top = (pos.pageY + 10) + 'px';

                            // Animer la zone survolée
                            layer.setStyle({
                                weight: 2.5,
                                fillOpacity: 0.9,
                                dashArray: ''
                            });
                        });

                        // Masquer l'infobulle quand on quitte la zone
                        layer.on('mouseout', function() {
                            tooltip.style.opacity = '0';

                            // Réinitialiser le style si ce n'est pas la zone sélectionnée
                            if (selectedArea !== code) {
                                geoJsonLayer.resetStyle(layer);
                            }
                        });

                        // Clic pour afficher les détails
                        layer.on('click', function() {
                            selectedArea = code;

                            // Mettre à jour les styles pour marquer la sélection
                            geoJsonLayer.eachLayer(function(l) {
                                geoJsonLayer.resetStyle(l);
                            });

                            layer.setStyle({
                                weight: 3,
                                dashArray: '3',
                                fillOpacity: 0.9
                            });

                            // Afficher les informations détaillées
                            displayAreaInfo(code);

                            // Animation pour centrer la carte sur la zone
                            map.fitBounds(layer.getBounds(), {
                                padding: [50, 50],
                                maxZoom: viewMode === 'regions' ? 6 : 8,
                                animate: true,
                                duration: 0.5
                            });
                        });

                        // Gestionnaire pour la position du curseur
                        layer.on('mousemove', function(e) {
                            const pos = e.originalEvent;
                            tooltip.style.left = (pos.pageX + 10) + 'px';
                            tooltip.style.top = (pos.pageY + 10) + 'px';
                        });
                    } else {
                        console.warn(`Aucune donnée trouvée pour ${viewMode === 'regions' ? 'la région' : 'le département'} ${code}`);

                        // Ajouter un événement de clic simple pour montrer qu'il n'y a pas de données
                        layer.on('click', function() {
                            const infoContent = document.getElementById('info-content');
                            infoContent.innerHTML = `
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-circle"></i> 
                                    Aucune donnée disponible pour ${viewMode === 'regions' ? 'la région' : 'le département'} avec le code "${code}".
                                </div>
                            `;
                        });
                    }
                }
            }).addTo(map);

            // Afficher les données disponibles dans la console pour le débogage
            console.log("Mode de vue:", viewMode);
            console.log("Données départements:", mapData);
            console.log("Données régions:", regionData);
        }

        // Afficher les informations détaillées d'une zone
        function displayAreaInfo(code) {
            const dataSource = viewMode === 'regions' ? regionData : mapData;
            const area = dataSource[code];

            if (!area) {
                // Gérer le cas où les données ne sont pas disponibles
                const infoContent = document.getElementById('info-content');
                infoContent.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-circle"></i> 
                        Aucune donnée disponible pour ${viewMode === 'regions' ? 'la région' : 'le département'} ${code}.
                    </div>
                `;
                return;
            }

            const infoContent = document.getElementById('info-content');

            // Calculer la tendance
            const t1 = parseFloat(area.taux.t1);
            const t2 = parseFloat(area.taux.t2);
            const t3 = parseFloat(area.taux.t3);

            let trend1, trend2;
            let trendIcon1, trendIcon2;
            let trendClass1, trendClass2;

            // Tendance entre T1 et T2
            if (t2 < t1) {
                trend1 = ((t1 - t2) / t1 * 100).toFixed(1) + "% de baisse";
                trendIcon1 = '<i class="fas fa-arrow-down"></i>';
                trendClass1 = 'down';
            } else if (t2 > t1) {
                trend1 = ((t2 - t1) / t1 * 100).toFixed(1) + "% d'augmentation";
                trendIcon1 = '<i class="fas fa-arrow-up"></i>';
                trendClass1 = 'up';
            } else {
                trend1 = "Stable";
                trendIcon1 = '<i class="fas fa-equals"></i>';
                trendClass1 = 'stable';
            }

            // Tendance entre T2 et T3
            if (t3 < t2) {
                trend2 = ((t2 - t3) / t2 * 100).toFixed(1) + "% de baisse";
                trendIcon2 = '<i class="fas fa-arrow-down"></i>';
                trendClass2 = 'down';
            } else if (t3 > t2) {
                trend2 = ((t3 - t2) / t2 * 100).toFixed(1) + "% d'augmentation";
                trendIcon2 = '<i class="fas fa-arrow-up"></i>';
                trendClass2 = 'up';
            } else {
                trend2 = "Stable";
                trendIcon2 = '<i class="fas fa-equals"></i>';
                trendClass2 = 'stable';
            }

            // En-tête adaptée au type de zone (région ou département)
            const zoneType = viewMode === 'regions' ? 'Région' : 'Département';

            // Créer un contenu plus riche avec des cartes de données
            infoContent.innerHTML = `
                <h3>${zoneType}: ${area.nom} (${code})</h3>
                <p>Évolution du taux de chômage sur les trois derniers trimestres</p>
                
                <div class="data-card">
                    <div class="stat">
                        <span class="stat-label">Trimestre 1</span>
                        <span class="stat-value">${t1}%</span>
                    </div>
                </div>
                
                <div class="data-card">
                    <div class="stat">
                        <span class="stat-label">Trimestre 2</span>
                        <span class="stat-value">${t2}%</span>
                    </div>
                    <div class="trend ${trendClass1}">
                        ${trendIcon1} ${trend1} par rapport au T1
                    </div>
                </div>
                
                <div class="data-card">
                    <div class="stat">
                        <span class="stat-label">Trimestre 3</span>
                        <span class="stat-value">${t3}%</span>
                    </div>
                    <div class="trend ${trendClass2}">
                        ${trendIcon2} ${trend2} par rapport au T2
                    </div>
                </div>
                
                <div class="info-note" style="margin-top: 15px; font-size: 0.9em; color: #666;">
                    <i class="fas fa-info-circle"></i> 
                    Ces données sont basées sur les statistiques officielles 
                    ${viewMode === 'regions' ? 'régionales' : 'départementales'} du chômage.
                </div>
            `;

            // Mise à jour du graphique
            updateChart(area, zoneType);
        }

        // Fonction pour mettre à jour le graphique
        function updateChart(area, zoneType) {
            const ctx = document.getElementById('evolution-chart').getContext('2d');

            if (evolutionChart) {
                evolutionChart.destroy();
            }

            // Définition de différentes couleurs pour les régions et départements
            const borderColor = viewMode === 'regions' ? '#8e44ad' : '#3498db';
            const backgroundColor = viewMode === 'regions' ? 'rgba(142, 68, 173, 0.1)' : 'rgba(52, 152, 219, 0.1)';

            evolutionChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Trimestre 1', 'Trimestre 2', 'Trimestre 3'],
                    datasets: [{
                        label: `Évolution du taux de chômage`,
                        data: [area.taux.t1, area.taux.t2, area.taux.t3],
                        borderColor: borderColor,
                        backgroundColor: backgroundColor,
                        borderWidth: 3,
                        tension: 0.2,
                        fill: true,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: borderColor,
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animations: {
                        tension: {
                            duration: 1000,
                            easing: 'linear',
                            from: 0.5,
                            to: 0.2,
                            loop: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            title: {
                                display: true,
                                text: 'Taux de chômage (%)',
                                font: {
                                    weight: 'bold'
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: `${zoneType} ${area.nom} - Évolution du chômage`,
                            font: {
                                size: 16
                            },
                            padding: {
                                bottom: 20
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(44, 62, 80, 0.9)',
                            titleFont: {
                                size: 14
                            },
                            bodyFont: {
                                size: 14
                            },
                            padding: 10,
                            cornerRadius: 6,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return `Taux: ${context.raw}%`;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Timer pour le prochain check
        let timer = <?php echo $timeRemaining; ?>;
        const timerElement = document.getElementById('timer');

        function updateTimer() {
            const minutes = Math.floor(timer / 60);
            const seconds = timer % 60;
            timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            if (timer > 0) {
                timer--;
            } else {
                timerElement.textContent = "Vérification en cours...";
            }
        }

        setInterval(updateTimer, 1000);
    </script>
</body>

</html>