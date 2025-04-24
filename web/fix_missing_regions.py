import pymysql
from datetime import datetime

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

def fix_missing_regions():
    """Corrige les données manquantes pour les régions spécifiques."""
    # Définition des données manquantes
    missing_regions = {
        # Code région : [nom, trimestre_1, trimestre_2, trimestre_3]
        "27": ["Bourgogne-Franche-Comté", 6.5, 6.7, 6.6],
        "53": ["Bretagne", 5.9, 6.0, 6.1],
        "84": ["Auvergne-Rhône-Alpes", 6.3, 6.4, 6.5]
    }
    
    connection = get_mysql_connection()
    if not connection:
        return False
    
    try:
        with connection.cursor() as cursor:
            print("\nMise à jour des régions manquantes:")
            
            # Mise à jour des régions manquantes
            for code, data in missing_regions.items():
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
                    data[0],  # nom_departement
                    data[1],  # trimestre_1
                    data[2],  # trimestre_2
                    data[3],  # trimestre_3
                    code      # code_departement
                ))
                
                print(f"  - Région {code} ({data[0]}) mise à jour avec les valeurs: {data[1]}, {data[2]}, {data[3]}")
            
            # Vérification des mises à jour
            cursor.execute("SELECT * FROM regions WHERE code_departement IN ('27', '53', '84')")
            print("\nVérification des régions mises à jour:")
            
            for row in cursor.fetchall():
                print(f"  - {row['code_departement']}: {row['nom_departement']} -> {row['trimestre_1']}, {row['trimestre_2']}, {row['trimestre_3']}")
            
            # Valider les modifications
            connection.commit()
            print("\n✅ Mise à jour des régions manquantes terminée avec succès!")
            
            # Vérifier toutes les régions
            cursor.execute("SELECT * FROM regions ORDER BY code_departement")
            print("\nListe complète des régions après mise à jour:")
            
            for row in cursor.fetchall():
                trimestre_1 = row['trimestre_1'] if row['trimestre_1'] is not None else "NULL"
                trimestre_2 = row['trimestre_2'] if row['trimestre_2'] is not None else "NULL"
                trimestre_3 = row['trimestre_3'] if row['trimestre_3'] is not None else "NULL"
                print(f"  - {row['code_departement']}: {row['nom_departement']} -> {trimestre_1}, {trimestre_2}, {trimestre_3}")
            
            return True
    except Exception as e:
        print(f"\n❌ Erreur lors de la mise à jour des régions manquantes: {e}")
        connection.rollback()
        return False
    finally:
        connection.close()

if __name__ == "__main__":
    print("=== CORRECTION DES DONNÉES MANQUANTES POUR LES RÉGIONS ===")
    print(f"Date/heure d'exécution: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    
    fix_missing_regions()
    
    print("\n=== FIN DU SCRIPT ===")