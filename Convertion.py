import os
import pandas as pd

def convert_csv_to_json(csv_folder='data/csv', json_folder='data/json'):
    """
    Convertit tous les fichiers CSV du dossier csv_folder en JSON et les stocke dans json_folder.
    """
    os.makedirs(json_folder, exist_ok=True)
    
    for file in os.listdir(csv_folder):
        if file.endswith('.csv'):
            csv_path = os.path.join(csv_folder, file)
            json_path = os.path.join(json_folder, file.replace('.csv', '.json'))
            
            try:
                df = pd.read_csv(csv_path, sep=';', encoding='utf-8', low_memory=False)
                
                df.to_json(json_path, orient='records', force_ascii=False, indent=4)
                print(f'✅ Conversion réussie : {file} → {json_path}')
            
            except Exception as e:
                print(f'❌ Erreur lors de la conversion de {file} : {e}')

if __name__ == "__main__":
    convert_csv_to_json()
