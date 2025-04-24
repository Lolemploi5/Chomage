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

// Fonction de normalisation pour accents, tirets, etc...
function normalizeString($string)
{
    $string = mb_strtolower(trim($string), 'UTF-8');
    if (class_exists('Normalizer')) {
        $string = Normalizer::normalize($string, Normalizer::FORM_D);
    }
    $string = preg_replace('/[\p{Mn}]/u', '', $string);
    $string = strtr(
        $string,
        'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖŒÙÚÛÜÝŸàáâãäåæçèéêëìíîïðñòóôõöœùúûüýÿ',
        'AAAAAAAECEEEEIIIIDNOOOOEOEUUUUYYaaaaaaaeceeeeiiiidnooooeeuuuuyy'
    );
    $string = preg_replace('/[^a-z0-9 ]/', '', $string);

    return $string;
}

// Trier les données
function sortData($data, $trimestre, $order = 'asc')
{
    usort($data, function ($a, $b) use ($trimestre, $order) {
        $key = "trimestre_$trimestre";
        if ($order === 'asc') {
            return $a[$key] <=> $b[$key];
        } else {
            return $b[$key] <=> $a[$key];
        }
    });
    return $data;
}

// utlisation de requête AJAX
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Récupération des filtres (GET ou POST selon le contexte)
$selectedTrimestre = $isAjax ? ($_POST['trimestre'] ?? 3) : ($_GET['trimestre'] ?? 3);
$viewMode = $isAjax ? ($_POST['view'] ?? 'departements') : ($_GET['view'] ?? 'departements');
$order = ($isAjax ? ($_POST['order'] ?? 'asc') : ($_GET['order'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
$search = $isAjax ? strtolower(trim($_POST['search'] ?? '')) : strtolower(trim($_GET['search'] ?? ''));

// Récupération des données API
$departements = callAPI("/departements");
$regions = callAPI("/regions");

// Choix des données à afficher
$data = $viewMode === 'regions' ? $regions : $departements;

if (!isset($data["error"])) {
    $data = sortData($data, $selectedTrimestre, $order);

    // Filtrage de recherche
    $normalizedSearch = normalizeString($search);
    if (!empty($normalizedSearch)) {
        $data = array_filter($data, function ($item) use ($normalizedSearch, $viewMode) {
            $name = $viewMode === 'regions' ? $item["nom_region"] : $item["nom_departement"];
            $normalizedName = normalizeString($name);
            return strpos($normalizedName, $normalizedSearch) !== false;
        });
    }
}

// Mode sombre ou clair (par défaut clair)
$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'true';

// Si requête AJAX : renvoyer le JSON et sortir
if ($isAjax) {
    echo json_encode(array_values($data)); // Remet les index numériques
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des taux de chômage</title>
    <link rel="stylesheet" href="css/order_liste.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>

<body>
    <header>
        <div class="header-container">
            <h1><i class="fas fa-chart-line"></i> Liste des taux de chômage</h1>
            <div class="theme-toggle">
                <button id="theme-switch" title="Changer de thème">
                    <i class="fas <?php echo $darkMode ? 'fa-sun' : 'fa-moon'; ?>"></i>
                </button>
            </div>
        </div>
        
        <div class="controls">
            <form method="get" action="" id="filter-form">
                <div class="control-group">
                    <label for="trimestre"><i class="fas fa-calendar-alt"></i> Trimestre :</label>
                    <select name="trimestre" id="trimestre" onchange="document.getElementById('filter-form').submit();">
                        <option value="1" <?php echo $selectedTrimestre == 1 ? 'selected' : ''; ?>>Trimestre 1</option>
                        <option value="2" <?php echo $selectedTrimestre == 2 ? 'selected' : ''; ?>>Trimestre 2</option>
                        <option value="3" <?php echo $selectedTrimestre == 3 ? 'selected' : ''; ?>>Trimestre 3</option>
                    </select>
                </div>

                <div class="control-group">
                    <label for="view"><i class="fas fa-layer-group"></i> Affichage :</label>
                    <select name="view" id="view" onchange="document.getElementById('filter-form').submit();">
                        <option value="departements" <?php echo $viewMode === 'departements' ? 'selected' : ''; ?>>Départements</option>
                        <option value="regions" <?php echo $viewMode === 'regions' ? 'selected' : ''; ?>>Régions</option>
                    </select>
                </div>

                <div class="control-group">
                    <label for="order"><i class="fas fa-sort"></i> Trier par :</label>
                    <select name="order" id="order" onchange="document.getElementById('filter-form').submit();">
                        <option value="asc" <?php echo $order === 'asc' ? 'selected' : ''; ?>>Croissant</option>
                        <option value="desc" <?php echo $order === 'desc' ? 'selected' : ''; ?>>Décroissant</option>
                    </select>
                </div>

                <div class="search-container">
                    <input type="text" name="search" id="search-area" placeholder="Rechercher un département ou une région..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
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
        <table>
            <thead>
                <tr>
                    <th><?php echo $viewMode === 'regions' ? 'Région' : 'Départements'; ?></th>
                    <th>Code</th>
                    <th>Taux de chômage (T<?php echo $selectedTrimestre; ?>)</th>
                </tr>
            </thead>
            <tbody id="results-body">
                <?php if (!isset($data["error"])): ?>
                    <?php foreach ($data as $item): ?>
                        <tr>
                            <td><?php echo $viewMode === 'regions' ? $item["nom_region"] : $item["nom_departement"]; ?></td>
                            <td><?php echo $viewMode === 'regions' ? $item["code_region"] : $item["code_departement"]; ?></td>
                            <td><?php echo $item["trimestre_$selectedTrimestre"]; ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">Erreur : <?php echo $data["error"]; ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</body>
<script>
    // Fonction pour normaliser les chaînes de caractères
    function normalizeJS(str) {
        return str
            .toLowerCase() // Convertir en minuscules
            .normalize("NFD") // Décomposer les caractères accentués
            .replace(/[\u0300-\u036f]/g, "") // Supprimer les diacritiques
            .replace(/[^a-z0-9 ]/g, '') // Supprimer les caractères non alphanumériques
            .trim(); // Supprimer les espaces inutiles
    }

    document.getElementById('search-area').addEventListener('input', function() {
        const trimestre = document.getElementById('trimestre').value;
        const view = document.getElementById('view').value;
        const rawSearch = document.getElementById('search-area').value;
        const search = normalizeJS(rawSearch);


        const xhr = new XMLHttpRequest();
        xhr.open('POST', '', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.onload = function() {
            if (xhr.status === 200) {
                const results = JSON.parse(xhr.responseText);
                const tbody = document.getElementById('results-body');
                tbody.innerHTML = '';

                if (results.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3">Aucun résultat</td></tr>';
                } else {
                    results.forEach(item => {
                        const name = view === 'regions' ? item.nom_region : item.nom_departement;
                        const code = view === 'regions' ? item.code_region : item.code_departement;
                        const taux = item['trimestre_' + trimestre];

                        tbody.innerHTML += `<tr>
                                <td>${name}</td>
                                <td>${code}</td>
                                <td>${taux}%</td>
                            </tr>`;
                    });
                }
            }
        };
        const order = document.getElementById('order').value;
        const params = `trimestre=${trimestre}&view=${view}&order=${order}&search=${encodeURIComponent(search)}`;
        xhr.send(params);
    });
</script>

</html>