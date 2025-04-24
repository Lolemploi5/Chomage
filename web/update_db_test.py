import pandas as pd
import pymysql
import os
from datetime import datetime

# Configuration
DATA_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "data")
EXCEL_PATH = os.path.join(DATA_DIR, "TCRD_025.xlsx")

# Configuration de la base de données
DB_HOST = "localhost"
DB_USER = "root"
DB_PASSWORD = ""
DB_NAME = "chomage_db"

def get_mysql_connection():
    """Établit une connexion à la base de données MySQL."""
    try:
        connection = pymysql.connect(
            host=DB_HOST,
            user=DB_USER,
            password=DB_PASSWORD,
            database=DB_NAME,
            cursorclass=pymysql.cursors.DictCursor
        )
        print("Connexion à la base de données établie avec succès")
        return connection
    except Exception as e:
        print(f"Erreur de connexion à la base de données: {e}")
        return None

def extract_and_update():
    """Extrait les données du fichier TCRD_025.xlsx et les insère directement dans la base de données."""
    print(f"Début de l'extraction à partir de {EXCEL_PATH}")

    # Vérifier si le fichier existe
    if not os.path.exists(EXCEL_PATH):
        print(f"ERREUR: Fichier introuvable: {EXCEL_PATH}")
        return False

    try:
        # 1. DONNÉES DÉPARTEMENTALES
        # Charger avec pandas, en sautant les lignes d'en-tête
        df_dep = pd.read_excel(EXCEL_PATH, sheet_name='DEP', skiprows=6)
        print("\nStructure du DataFrame des départements:")
        print(f"Colonnes: {df_dep.columns.tolist()}")
        print(f"Premières lignes: \n{df_dep.head()}")
        
        # Extraire les données départementales
        departements_data = {}
        
        # Trouver les colonnes importantes
        code_col = df_dep.columns[0]  # Première colonne = code
        nom_col = df_dep.columns[1]   # Deuxième colonne = nom
        
        # Les trimestres sont généralement les 3 colonnes suivantes
        trimestre_1_col = df_dep.columns[2] if len(df_dep.columns) > 2 else None
        trimestre_2_col = df_dep.columns[3] if len(df_dep.columns) > 3 else None
        trimestre_3_col = df_dep.columns[4] if len(df_dep.columns) > 4 else None
        
        print(f"\nColonnes identifiées pour départements:")
        print(f"  - Code: {code_col}")
        print(f"  - Nom: {nom_col}")
        print(f"  - Trimestre 1: {trimestre_1_col}")
        print(f"  - Trimestre 2: {trimestre_2_col}")
        print(f"  - Trimestre 3: {trimestre_3_col}")
        
        # Extraire les données
        print("\nExtractions des données départementales:")
        for idx, row in df_dep.iterrows():
            # Ignorer les lignes non pertinentes
            if pd.isna(row[code_col]) or not str(row[code_col]).strip():
                continue
            
            # Extraire le code et le nom du département
            code = str(row[code_col]).strip()
            if not code.isdigit() and code not in ['2A', '2B', '971', '972', '973', '974', 'F', 'M']:
                continue  # Ignorer les lignes qui ne sont pas des codes de département
                
            nom = str(row[nom_col]).strip() if not pd.isna(row[nom_col]) else "Unknown"
            
            # Extraire les valeurs des trimestres
            tri1 = row[trimestre_1_col] if trimestre_1_col and not pd.isna(row[trimestre_1_col]) else None
            tri2 = row[trimestre_2_col] if trimestre_2_col and not pd.isna(row[trimestre_2_col]) else None
            tri3 = row[trimestre_3_col] if trimestre_3_col and not pd.isna(row[trimestre_3_col]) else None
            
            # Convertir les valeurs en float si nécessaire
            if tri1 is not None:
                try: tri1 = float(str(tri1).replace(',', '.').replace(' ', ''))
                except: tri1 = None
            if tri2 is not None:
                try: tri2 = float(str(tri2).replace(',', '.').replace(' ', ''))
                except: tri2 = None
            if tri3 is not None:
                try: tri3 = float(str(tri3).replace(',', '.').replace(' ', ''))
                except: tri3 = None
            
            departements_data[code] = {
                'nom_departement': nom,
                'trimestre_1': tri1,
                'trimestre_2': tri2,
                'trimestre_3': tri3
            }
            
            print(f"  - {code}: {nom} -> {tri1}, {tri2}, {tri3}")
        
        # 2. DONNÉES RÉGIONALES
        # Même approche pour les régions
        df_reg = pd.read_excel(EXCEL_PATH, sheet_name='REG', skiprows=6)
        print("\nStructure du DataFrame des régions:")
        print(f"Colonnes: {df_reg.columns.tolist()}")
        print(f"Premières lignes: \n{df_reg.head()}")
        
        regions_data = {}
        
        # Trouver les colonnes importantes
        code_col = df_reg.columns[0]  # Première colonne = code
        nom_col = df_reg.columns[1]   # Deuxième colonne = nom
        
        # Les trimestres sont généralement les 3 colonnes suivantes
        trimestre_1_col = df_reg.columns[2] if len(df_reg.columns) > 2 else None
        trimestre_2_col = df_reg.columns[3] if len(df_reg.columns) > 3 else None
        trimestre_3_col = df_reg.columns[4] if len(df_reg.columns) > 4 else None
        
        print(f"\nColonnes identifiées pour régions:")
        print(f"  - Code: {code_col}")
        print(f"  - Nom: {nom_col}")
        print(f"  - Trimestre 1: {trimestre_1_col}")
        print(f"  - Trimestre 2: {trimestre_2_col}")
        print(f"  - Trimestre 3: {trimestre_3_col}")
        
        # Extraire les données
        print("\nExtractions des données régionales:")
        for idx, row in df_reg.iterrows():
            # Ignorer les lignes non pertinentes
            if pd.isna(row[code_col]) or not str(row[code_col]).strip():
                continue
            
            # Extraire le code et le nom de la région
            code = str(row[code_col]).strip()
            nom = str(row[nom_col]).strip() if not pd.isna(row[nom_col]) else "Unknown"
            
            # Extraire les valeurs des trimestres
            tri1 = row[trimestre_1_col] if trimestre_1_col and not pd.isna(row[trimestre_1_col]) else None
            tri2 = row[trimestre_2_col] if trimestre_2_col and not pd.isna(row[trimestre_2_col]) else None
            tri3 = row[trimestre_3_col] if trimestre_3_col and not pd.isna(row[trimestre_3_col]) else None
            
            # Convertir les valeurs en float si nécessaire
            if tri1 is not None:
                try: tri1 = float(str(tri1).replace(',', '.').replace(' ', ''))
                except: tri1 = None
            if tri2 is not None:
                try: tri2 = float(str(tri2).replace(',', '.').replace(' ', ''))
                except: tri2 = None
            if tri3 is not None:
                try: tri3 = float(str(tri3).replace(',', '.').replace(' ', ''))
                except: tri3 = None
            
            regions_data[code] = {
                'nom_departement': nom,
                'trimestre_1': tri1,
                'trimestre_2': tri2,
                'trimestre_3': tri3
            }
            
            print(f"  - {code}: {nom} -> {tri1}, {tri2}, {tri3}")
        
        # 3. MISE À JOUR DE LA BASE DE DONNÉES
        connection = get_mysql_connection()
        if not connection:
            return False
        
        try:
            with connection.cursor() as cursor:
                # Mise à jour des départements
                print("\nMise à jour des données départementales dans la base de données:")
                updated_dept_count = 0
                for code_dept, data in departements_data.items():
                    query = """
                        UPDATE departements
                        SET 
                            nom_departement = %s,
                            trimestre_1 = %s,
                            trimestre_2 = %s,
                            trimestre_3 = %s
                        WHERE code_departement = %s
                    """
                    cursor.execute(query, (
                        data['nom_departement'],
                        data['trimestre_1'],
                        data['trimestre_2'],
                        data['trimestre_3'],
                        code_dept
                    ))
                    updated_dept_count += cursor.rowcount
                
                print(f"  - {updated_dept_count} lignes mises à jour dans la table 'departements'")
                
                # Mise à jour des régions
                print("\nMise à jour des données régionales dans la base de données:")
                updated_reg_count = 0
                for code_region, data in regions_data.items():
                    query = """
                        UPDATE regions
                        SET 
                            nom_departement = %s,
                            trimestre_1 = %s,
                            trimestre_2 = %s,
                            trimestre_3 = %s
                        WHERE code_departement = %s
                    """
                    cursor.execute(query, (
                        data['nom_departement'],
                        data['trimestre_1'],
                        data['trimestre_2'],
                        data['trimestre_3'],
                        code_region
                    ))
                    updated_reg_count += cursor.rowcount
                
                print(f"  - {updated_reg_count} lignes mises à jour dans la table 'regions'")
                
                # Valider les modifications
                connection.commit()
                print("\n✅ Base de données mise à jour avec succès!")
                
                # Vérification finale
                print("\nVérification des données mises à jour:")
                
                # Vérifier quelques données départementales
                cursor.execute("SELECT code_departement, nom_departement, trimestre_1, trimestre_2, trimestre_3 FROM departements LIMIT 5")
                print("Échantillon de données départementales:")
                for row in cursor.fetchall():
                    print(f"  - {row['code_departement']}: {row['nom_departement']} -> {row['trimestre_1']}, {row['trimestre_2']}, {row['trimestre_3']}")
                
                # Vérifier quelques données régionales
                cursor.execute("SELECT code_departement, nom_departement, trimestre_1, trimestre_2, trimestre_3 FROM regions LIMIT 5")
                print("Échantillon de données régionales:")
                for row in cursor.fetchall():
                    print(f"  - {row['code_departement']}: {row['nom_departement']} -> {row['trimestre_1']}, {row['trimestre_2']}, {row['trimestre_3']}")
                
                return True
        except Exception as e:
            print(f"\n❌ Erreur lors de la mise à jour de la base de données: {e}")
            connection.rollback()
            return False
        finally:
            connection.close()
            
    except Exception as e:
        print(f"\n❌ Erreur lors de l'extraction des données: {e}")
        return False

if __name__ == "__main__":
    print("=== DÉBUT DU SCRIPT DE MISE À JOUR DE LA BASE DE DONNÉES ===")
    print(f"Date/heure d'exécution: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print(f"Fichier Excel: {EXCEL_PATH}")
    print(f"Base de données: {DB_NAME} sur {DB_HOST}")
    
    extract_and_update()
    
    print("\n=== FIN DU SCRIPT ===")