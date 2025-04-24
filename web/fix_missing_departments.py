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

def fix_missing_departments():
    """Corrige les données manquantes pour les départements spécifiques."""
    # Définition des données manquantes
    missing_departments = {
        # Code département : [nom, trimestre_1, trimestre_2, trimestre_3]
        "01": ["Ain", 5.5, 5.6, 5.7],
        "02": ["Aisne", 10.2, 10.6, 10.8],
        "03": ["Allier", 7.9, 8.1, 8.0]
    }
    
    connection = get_mysql_connection()
    if not connection:
        return False
    
    try:
        with connection.cursor() as cursor:
            print("\nMise à jour des départements manquants:")
            
            # Mise à jour des départements manquants
            for code, data in missing_departments.items():
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
                    data[0],  # nom_departement
                    data[1],  # trimestre_1
                    data[2],  # trimestre_2
                    data[3],  # trimestre_3
                    code      # code_departement
                ))
                
                print(f"  - Département {code} ({data[0]}) mis à jour avec les valeurs: {data[1]}, {data[2]}, {data[3]}")
            
            # Vérification des mises à jour
            cursor.execute("SELECT * FROM departements WHERE code_departement IN ('01', '02', '03')")
            print("\nVérification des départements mis à jour:")
            
            for row in cursor.fetchall():
                print(f"  - {row['code_departement']}: {row['nom_departement']} -> {row['trimestre_1']}, {row['trimestre_2']}, {row['trimestre_3']}")
            
            # Valider les modifications
            connection.commit()
            print("\n✅ Mise à jour des départements manquants terminée avec succès!")
            
            # Vérifier s'il reste des valeurs NULL dans la table départements
            cursor.execute("SELECT code_departement, nom_departement, trimestre_1, trimestre_2, trimestre_3 FROM departements WHERE trimestre_1 IS NULL OR trimestre_2 IS NULL OR trimestre_3 IS NULL")
            null_values = cursor.fetchall()
            
            if null_values:
                print("\n⚠️ Il reste des départements avec des valeurs NULL:")
                for row in null_values:
                    trimestre_1 = row['trimestre_1'] if row['trimestre_1'] is not None else "NULL"
                    trimestre_2 = row['trimestre_2'] if row['trimestre_2'] is not None else "NULL"
                    trimestre_3 = row['trimestre_3'] if row['trimestre_3'] is not None else "NULL"
                    print(f"  - {row['code_departement']}: {row['nom_departement']} -> {trimestre_1}, {trimestre_2}, {trimestre_3}")
            else:
                print("\n✅ Tous les départements ont maintenant des valeurs pour les trimestres!")
            
            return True
    except Exception as e:
        print(f"\n❌ Erreur lors de la mise à jour des départements manquants: {e}")
        connection.rollback()
        return False
    finally:
        connection.close()

if __name__ == "__main__":
    print("=== CORRECTION DES DONNÉES MANQUANTES POUR LES DÉPARTEMENTS ===")
    print(f"Date/heure d'exécution: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    
    fix_missing_departments()
    
    print("\n=== FIN DU SCRIPT ===")