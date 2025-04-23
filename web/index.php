<?php
// Configuration
$api_url = "http://localhost:8000";

// Fonction pour appeler l'API
function callAPI($endpoint) {
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

// Préparer les données pour la carte
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
        $regionData[$region["code_departement"]] = [
            "nom" => $region["nom_departement"],
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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carte du chômage en France</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --light-bg: #f8f9fa;
            --dark-bg: #343a40;
            --text-color: #333;
            --light-text: #f8f9fa;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--light-bg);
        }
        
        header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--light-text);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            position: relative;
        }
        
        header h1 {
            margin-bottom: 1rem;
            font-weight: 500;
            font-size: 1.8rem;
            text-align: center;
        }
        
        .controls {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }
        
        .control-group {
            display: flex;
            align-items: center;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
        }
        
        select, button {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: var(--border-radius);
            background-color: white;
            color: var(--secondary-color);
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.9rem;
        }
        
        select:hover, button:hover {
            background-color: var(--light-bg);
        }
        
        label {
            margin-right: 0.5rem;
            font-weight: 500;
        }
        
        main {
            padding: 2rem;
        }
        
        .container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        @media (min-width: 992px) {
            .container {
                grid-template-columns: 3fr 2fr;
            }
        }
        
        .map-container {
            position: relative;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            background-color: white;
            height: 70vh;
        }
        
        #map {
            height: 100%;
            z-index: 1;
        }
        
        .legend {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background-color: rgba(255, 255, 255, 0.9);
            padding: 1rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            z-index: 2;
            max-width: 200px;
        }
        
        .legend h3 {
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            text-align: center;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.3rem;
            font-size: 0.8rem;
        }
        
        .color-box {
            width: 16px;
            height: 16px;
            margin-right: 8px;
            border-radius: 3px;
        }
        
        .info-panel {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            height: 70vh;
            overflow: hidden;
        }
        
        .info-panel h2 {
            margin-bottom: 1rem;
            color: var(--secondary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
        }
        
        #info-content {
            margin-bottom: 1.5rem;
            overflow-y: auto;
            flex: 0 0 auto;
        }
        
        #info-content h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        #info-content p {
            color: var(--text-color);
            margin-bottom: 1rem;
        }
        
        #info-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        
        #info-content th, #info-content td {
            border: 1px solid #ddd;
            padding: 0.5rem;
            text-align: left;
        }
        
        #info-content th {
            background-color: var(--light-bg);
        }
        
        .chart-container {
            flex: 1;
            position: relative;
            min-height: 250px;
            margin-top: 1rem;
        }
        
        .no-data {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            color: #999;
            font-style: italic;
        }
        
        footer {
            background-color: var(--dark-bg);
            color: var(--light-text);
            text-align: center;
            padding: 1rem;
            margin-top: 2rem;
        }
        
        .view-toggle {
            display: flex;
            background-color: var(--light-bg);
            border-radius: var(--border-radius);
            overflow: hidden;
            margin-left: 1rem;
        }
        
        .view-toggle button {
            padding: 0.5rem 1rem;
            border: none;
            background-color: transparent;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .view-toggle button.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .loading-indicator {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(255, 255, 255, 0.8);
            padding: 1rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            display: none;
            z-index: 1000;
        }
        
        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-left-color: var(--primary-color);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .data-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }
        
        .data-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .stat {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-weight: 500;
        }
        
        .stat-value {
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .trend {
            font-size: 0.8rem;
            display: flex;
            align-items: center;
        }
        
        .trend.up {
            color: #e74c3c;
        }
        
        .trend.down {
            color: #2ecc71;
        }
        
        .trend.stable {
            color: #f39c12;
        }
        
        .trend i {
            margin-right: 0.3rem;
        }
        
        .tooltip {
            position: absolute;
            background-color: white;
            border-radius: var(--border-radius);
            padding: 0.5rem;
            box-shadow: var(--box-shadow);
            font-size: 0.9rem;
            z-index: 1000;
            max-width: 250px;
            opacity: 0;
            transition: opacity 0.3s;
        }
    </style>
</head>
<body>
    <header>
        <h1>Taux de chômage en France</h1>
        <div class="controls">
            <form method="get" action="" id="control-form">
                <div class="control-group">
                    <label for="trimestre"><i class="fas fa-calendar-alt"></i> Trimestre :</label>
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
            </form>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="map-container">
                <div id="map"></div>
                <div class="legend">
                    <h3>Légende (Taux de chômage)</h3>
                    <div class="legend-item"><span class="color-box" style="background-color: #a1d99b;"></span> < 6%</div>
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
                });
            });
        });
        
        // Initialiser la carte
        function initMap() {
            map = L.map('map', {
                zoomControl: false,
                attributionControl: false
            }).setView([46.603354, 1.888334], 6);
            
            // Ajouter les contrôles de zoom dans une meilleure position
            L.control.zoom({
                position: 'bottomright'
            }).addTo(map);
            
            // Ajouter le fond de carte avec un style plus moderne
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>, &copy; <a href="https://carto.com/attributions">CARTO</a>',
                subdomains: 'abcd',
                maxZoom: 19
            }).addTo(map);
            
            // Charger les données géographiques
            loadGeoJSON();
        }
        
        // Charger les données GeoJSON selon le mode de vue
        function loadGeoJSON() {
            // Afficher l'indicateur de chargement
            document.querySelector('.loading-indicator').style.display = 'block';
            
            // Supprimer la couche existante si présente
            if (geoJsonLayer) {
                map.removeLayer(geoJsonLayer);
            }
            
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
                    console.error("Erreur lors du chargement des données GeoJSON:", error);
                    document.querySelector('.loading-indicator').style.display = 'none';
                });
        }
        
        // Créer la couche GeoJSON avec les styles et interactions
        function createGeoJsonLayer(data) {
            geoJsonLayer = L.geoJSON(data, {
                style: function(feature) {
                    const code = feature.properties.code;
                    const dataSource = viewMode === 'regions' ? regionData : mapData;
                    const area = dataSource[code];
                    let tauxChomage = 0;
                    
                    if (area) {
                        tauxChomage = area.taux['t' + selectedTrimestre];
                    }
                    
                    // Déterminer la couleur en fonction du taux de chômage
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
                    const code = feature.properties.code;
                    const dataSource = viewMode === 'regions' ? regionData : mapData;
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
                                maxZoom: 8,
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
                    }
                }
            }).addTo(map);
        }
        
        // Afficher les informations détaillées d'une zone
        function displayAreaInfo(code) {
            const dataSource = viewMode === 'regions' ? regionData : mapData;
            const area = dataSource[code];
            
            if (!area) return;
            
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
            
            // Créer un contenu plus riche avec des cartes de données
            infoContent.innerHTML = `
                <h3>${area.nom} (${code})</h3>
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
            `;
            
            // Mise à jour du graphique
            updateChart(area);
        }
        
        // Fonction pour mettre à jour le graphique
        function updateChart(area) {
            const ctx = document.getElementById('evolution-chart').getContext('2d');
            
            if (evolutionChart) {
                evolutionChart.destroy();
            }
            
            evolutionChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Trimestre 1', 'Trimestre 2', 'Trimestre 3'],
                    datasets: [{
                        label: `Évolution du taux de chômage`,
                        data: [area.taux.t1, area.taux.t2, area.taux.t3],
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        borderWidth: 3,
                        tension: 0.2,
                        fill: true,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#3498db',
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
                            text: `${area.nom} - Évolution du chômage`,
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
    </script>
</body>
</html>
