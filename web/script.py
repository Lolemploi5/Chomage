import requests
from bs4 import BeautifulSoup
from datetime import datetime
import os
import time
import threading

def fetch_last_modified_date(url):
    try:
        # Envoyer une requête HEAD pour récupérer les en-têtes HTTP
        response = requests.head(url)
        response.raise_for_status()

        # Extraire la date de dernière modification
        last_modified = response.headers.get("Last-Modified")
        if last_modified:
            # Convertir la date en format lisible
            parsed_date = datetime.strptime(last_modified, "%a, %d %b %Y %H:%M:%S %Z")
            formatted_date = parsed_date.strftime("%d %B %Y à %H:%M")
            return formatted_date
        else:
            return None
    except requests.RequestException as e:
        print(f"Erreur lors de la récupération des en-têtes : {e}")
        return None

def fetch_publication_date(url):
    try:
        # Envoyer une requête GET pour récupérer le contenu de la page
        response = requests.get(url)
        response.raise_for_status()

        # Parser le contenu HTML
        soup = BeautifulSoup(response.text, 'html.parser')

        # Rechercher la date de diffusion dans la div correspondante
        date_div = soup.find('div', class_='date-diffusion hidden-impression-information-rapide')
        if date_div:
            raw_date = date_div.text.strip()
            # Nettoyer la chaîne pour extraire uniquement la date
            raw_date = raw_date.split('Paru le')[-1].strip()
            # Convertir la date en format lisible
            parsed_date = datetime.strptime(raw_date, "%d/%m/%Y")
            formatted_date = parsed_date.strftime("%d %B %Y")
            return formatted_date
        else:
            return None
    except requests.RequestException as e:
        print(f"Erreur lors de la récupération de la page : {e}")
        return None
    except ValueError as e:
        print(f"Erreur lors de l'analyse de la date : {e}")
        return None

def display_top_bar(message):
    bar = "=" * (len(message) + 4)
    print(bar)
    print(f"= {message} =")
    print(bar)

def download_new_file(url, output_path):
    try:
        response = requests.get(url, stream=True)
        response.raise_for_status()

        with open(output_path, 'wb') as file:
            for chunk in response.iter_content(chunk_size=8192):
                file.write(chunk)

        print(f"Fichier téléchargé avec succès : {output_path}")
    except requests.RequestException as e:
        print(f"Erreur lors du téléchargement du fichier : {e}")

def update_last_check_time():
    last_check_file = "data/last_check_time.txt"
    with open(last_check_file, 'w') as file:
        file.write(datetime.now().strftime("%Y-%m-%d %H:%M:%S"))

def periodic_check():
    while True:
        print("Vérification en cours...")
        main_check()
        update_last_check_time()  # Mettre à jour l'heure du dernier check
        print("Prochaine vérification dans 10 minutes.")
        time.sleep(600)  # Attendre 10 minutes

def main_check():
    url = "https://www.insee.fr/fr/statistiques/2012804#tableau-TCRD_025_tab1_departements"
    xls_url = "https://www.insee.fr/fr/statistiques/fichier/2012804/sl_etc_2024T4.xls"
    output_path = "data/sl_etc_2024T4.xls"

    publication_date = fetch_publication_date(url)

    # Vérifier si la date de diffusion a changé
    date_file_path = "data/last_publication_date.txt"
    last_date = None

    if os.path.exists(date_file_path):
        with open(date_file_path, 'r') as date_file:
            last_date = date_file.read().strip()

    if publication_date and publication_date != last_date:
        print("La date de diffusion a changé. Téléchargement du nouveau fichier...")
        download_new_file(xls_url, output_path)

        # Mettre à jour la date de diffusion enregistrée
        with open(date_file_path, 'w') as date_file:
            date_file.write(publication_date)
    else:
        print("Aucun changement dans la date de diffusion.")

if __name__ == "__main__":
    # Lancer le check périodique dans un thread séparé
    thread = threading.Thread(target=periodic_check, daemon=True)
    thread.start()

    # Garder le script actif
    while True:
        time.sleep(1)