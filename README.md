# Application de visualisation du chômage en France

Cette application permet de visualiser les taux de chômage en France par département et région.

## Structure du projet

- `api/` - Backend FastAPI (Python)
- `web/` - Frontend PHP
- `Chomage_DB.sql` - Script de création de la base de données

## Installation et configuration

### 1. Base de données

Importez le fichier SQL dans votre serveur MySQL:

```bash
mysql -u root -p < Chomage_DB.sql
```

### 2. Backend (API)

Installez les dépendances Python:

```bash
cd api
python -m venv venv
source venv/bin/activate  # Sur Windows: venv\Scripts\activate
pip install -r requirements.txt
```

Configurez les variables d'environnement dans le fichier `.env`:

```
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=votre_mot_de_passe
DB_NAME=chomage_db
```

#### Lancement de l'API FastAPI

Il existe plusieurs façons de lancer l'API:

**Méthode 1: En utilisant uvicorn directement**
```bash
cd api
# Assurez-vous que votre environnement virtuel est activé
uvicorn main:app --reload --host 0.0.0.0 --port 8000
```

**Méthode 2: En exécutant le script Python**
```bash
cd api
# Assurez-vous que votre environnement virtuel est activé
python main.py
```

**Méthode 3: En utilisant python -m uvicorn**
```bash
cd api
# Assurez-vous que votre environnement virtuel est activé
python -m uvicorn main:app --reload --host 0.0.0.0 --port 8000
```

L'API sera disponible à l'adresse: http://localhost:8000

Vous pouvez accéder à la documentation interactive de l'API à l'adresse:
- http://localhost:8000/docs (Swagger UI)
- http://localhost:8000/redoc (ReDoc)

**Résolution des problèmes courants:**

1. Erreur "Command not found: uvicorn" - Vérifiez que le package uvicorn est installé:
   ```bash
   pip install uvicorn
   ```

2. Erreur de connexion à la base de données - Vérifiez vos paramètres dans le fichier .env

3. Problème de CORS - Si vous rencontrez des erreurs CORS lors de l'accès à l'API depuis votre frontend, vérifiez que les origines sont correctement configurées dans le middleware CORS.

### 3. Frontend

#### Option 1 : Utiliser le serveur PHP intégré (pour développement)

PHP dispose d'un serveur web intégré que vous pouvez utiliser pour le développement :

```bash
cd web
php -S localhost:8080
```

Votre application sera accessible à l'adresse : http://localhost:8080

#### Option 2 : Utiliser XAMPP/WAMP/MAMP

1. Installez [XAMPP](https://www.apachefriends.org/), [WAMP](https://www.wampserver.com/) (Windows) ou [MAMP](https://www.mamp.info/) (Mac)
2. Démarrez les services Apache et MySQL
3. Copiez le dossier `web/` dans le répertoire `htdocs` (XAMPP/MAMP) ou `www` (WAMP)
4. Accédez à http://localhost/web/

#### Option 3 : Configurer manuellement un serveur Apache ou Nginx

Pour Apache :
1. Installez Apache et PHP
```bash
# Sur Ubuntu/Debian
sudo apt install apache2 php libapache2-mod-php

# Sur macOS (avec Homebrew)
brew install php httpd
```

2. Configurez un VirtualHost dans Apache :
```
<VirtualHost *:80>
    ServerName chomage.local
    DocumentRoot "/Users/lolemploi/Projects/Chomage/web"
    <Directory "/Users/lolemploi/Projects/Chomage/web">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

3. Ajoutez `chomage.local` à votre fichier hosts
```
127.0.0.1 chomage.local
```

4. Redémarrez Apache
```bash
# Ubuntu/Debian
sudo systemctl restart apache2

# macOS
brew services restart httpd
```

Placez les fichiers du dossier `web/` dans votre serveur PHP (Apache, Nginx, etc.).

Téléchargez le fichier GeoJSON des départements:

```bash
cd web/data
curl -o departements.geojson https://raw.githubusercontent.com/gregoiredavid/france-geojson/master/departements.geojson
```

### 4. Accès à l'application

Ouvrez votre navigateur et accédez à l'URL de votre serveur PHP, par exemple:
- http://localhost/chomage/

## Utilisation

- Sélectionnez le trimestre à afficher dans le menu déroulant
- Cliquez sur un département pour voir les détails et l'évolution du taux de chômage
- La couleur des départements représente le taux de chômage selon la légende

## Technologies utilisées

- Backend: Python avec FastAPI
- Frontend: PHP, HTML, CSS, JavaScript
- Cartographie: Leaflet.js
- Graphiques: Chart.js
- Base de données: MySQL