# Données géographiques

## Départements français

Pour faire fonctionner la carte des départements, vous devez télécharger le fichier GeoJSON des départements français et le placer dans ce répertoire sous le nom `departements.geojson`.

Vous pouvez télécharger ce fichier à partir de:
- https://github.com/gregoiredavid/france-geojson/blob/master/departements.geojson

Ou utilisez directement cette commande pour télécharger le fichier:

```bash
curl -o departements.geojson https://raw.githubusercontent.com/gregoiredavid/france-geojson/master/departements.geojson
```

## Régions françaises

Pour faire fonctionner la carte des régions, vous devez télécharger le fichier GeoJSON des régions françaises et le placer dans ce répertoire sous le nom `regions.geojson`.

Vous pouvez télécharger ce fichier à partir de:
- https://github.com/gregoiredavid/france-geojson/blob/master/regions.geojson

Ou utilisez directement cette commande pour télécharger le fichier:

```bash
curl -o regions.geojson https://raw.githubusercontent.com/gregoiredavid/france-geojson/master/regions.geojson
```

## Téléchargement de tous les fichiers nécessaires

Pour télécharger tous les fichiers GeoJSON nécessaires en une seule fois, utilisez le script suivant:

```bash
# Se placer dans le répertoire /data
cd /Applications/XAMPP/xamppfiles/htdocs/web/data

# Télécharger le fichier des départements
curl -o departements.geojson https://raw.githubusercontent.com/gregoiredavid/france-geojson/master/departements.geojson

# Télécharger le fichier des régions
curl -o regions.geojson https://raw.githubusercontent.com/gregoiredavid/france-geojson/master/regions.geojson

# Vérifier que les fichiers ont bien été téléchargés
ls -la *.geojson
```

Note: Ces fichiers contiennent les contours géographiques des 13 régions et 101 départements français métropolitains et d'outre-mer, dans leur organisation administrative actuelle.
