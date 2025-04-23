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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carte du chômage en France</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header>
        <h1>Taux de chômage en France par département et région</h1>
        <div class="controls">
            <form method="get" action="">
                <label for="trimestre">Trimestre :</label>
                <select name="trimestre" id="trimestre" onchange="this.form.submit()">
                    <option value="1" <?php echo $selectedTrimestre == 1 ? 'selected' : ''; ?>>Trimestre 1</option>
                    <option value="2" <?php echo $selectedTrimestre == 2 ? 'selected' : ''; ?>>Trimestre 2</option>
                    <option value="3" <?php echo $selectedTrimestre == 3 ? 'selected' : ''; ?>>Trimestre 3</option>
                </select>
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
            </div>

            <div class="info-panel">
                <h2>Informations détaillées</h2>
                <div id="info-content">
                    <p>Cliquez sur un département pour voir les détails.</p>
                </div>
                <div class="chart-container">
                    <canvas id="evolution-chart"></canvas>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> - Données de chômage en France</p>
    </footer>

    <script>
        // Initialisation de la carte
        const map = L.map('map').setView([46.603354, 1.888334], 6);
        
        // Ajout du fond de carte OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        // Données des départements pour la carte
        const departements = <?php echo json_encode($mapData); ?>;
        const regions = <?php echo json_encode($regionData); ?>;
        const selectedTrimestre = <?php echo $selectedTrimestre; ?>;
        
        // Charger les contours des départements (GeoJSON)
        fetch('data/departements.geojson')
            .then(response => response.json())
            .then(data => {
                L.geoJSON(data, {
                    style: function(feature) {
                        const deptCode = feature.properties.code;
                        const dept = departements[deptCode];
                        let tauxChomage = 0;
                        
                        if (dept) {
                            tauxChomage = dept.taux['t' + selectedTrimestre];
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
                            weight: 1,
                            opacity: 1,
                            color: 'white',
                            fillOpacity: 0.7
                        };
                    },
                    onEachFeature: function(feature, layer) {
                        const deptCode = feature.properties.code;
                        const dept = departements[deptCode];
                        
                        if (dept) {
                            layer.bindTooltip(`${dept.nom} (${deptCode})<br>Taux: ${dept.taux['t' + selectedTrimestre]}%`);
                            
                            layer.on('click', function() {
                                displayDepartementInfo(deptCode);
                            });
                        }
                    }
                }).addTo(map);
            });
            
        // Fonction pour afficher les informations d'un département
        function displayDepartementInfo(code) {
            const dept = departements[code];
            if (!dept) return;
            
            const infoContent = document.getElementById('info-content');
            infoContent.innerHTML = `
                <h3>${dept.nom} (${code})</h3>
                <table>
                    <tr><th>Trimestre</th><th>Taux de chômage</th></tr>
                    <tr><td>Trimestre 1</td><td>${dept.taux.t1}%</td></tr>
                    <tr><td>Trimestre 2</td><td>${dept.taux.t2}%</td></tr>
                    <tr><td>Trimestre 3</td><td>${dept.taux.t3}%</td></tr>
                </table>
            `;
            
            // Mise à jour du graphique
            updateChart(dept);
        }
        
        // Fonction pour mettre à jour le graphique
        let evolutionChart = null;
        
        function updateChart(dept) {
            const ctx = document.getElementById('evolution-chart').getContext('2d');
            
            if (evolutionChart) {
                evolutionChart.destroy();
            }
            
            evolutionChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Trimestre 1', 'Trimestre 2', 'Trimestre 3'],
                    datasets: [{
                        label: `Évolution du taux de chômage - ${dept.nom}`,
                        data: [dept.taux.t1, dept.taux.t2, dept.taux.t3],
                        borderColor: '#3498db',
                        tension: 0.1,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            title: {
                                display: true,
                                text: 'Taux de chômage (%)'
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
