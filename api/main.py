from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
import mysql.connector
from mysql.connector import Error
from pydantic import BaseModel
from typing import List, Optional
import os
from dotenv import load_dotenv

# Charger les variables d'environnement
load_dotenv()

app = FastAPI(title="API Chomage France", description="API pour les données de chômage en France")

# Configuration CORS pour permettre les requêtes du frontend
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Dans un environnement de production, spécifier les origines exactes
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Configuration de la base de données
DB_CONFIG = {
    "host": os.getenv("DB_HOST", "localhost"),
    "user": os.getenv("DB_USER", "root"),
    "password": os.getenv("DB_PASSWORD", ""),
    "database": os.getenv("DB_NAME", "chomage_db")
}

# Modèles de données
class Departement(BaseModel):
    code_departement: str
    nom_departement: Optional[str] = None
    trimestre_1: Optional[float] = None
    trimestre_2: Optional[float] = None
    trimestre_3: Optional[float] = None

class Region(BaseModel):
    code_region: str  # Corrigé : code_region au lieu de code_departement
    nom_region: Optional[str] = None  # Corrigé : nom_region au lieu de nom_departement
    trimestre_1: Optional[float] = None
    trimestre_2: Optional[float] = None
    trimestre_3: Optional[float] = None

# Fonction pour se connecter à la base de données
def get_db_connection():
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        return conn
    except Error as e:
        print(f"Erreur de connexion à la base de données: {e}")
        return None

# Routes API
@app.get("/")
def read_root():
    return {"message": "Bienvenue sur l'API des données de chômage en France"}

@app.get("/departements", response_model=List[Departement])
def get_departements():
    conn = get_db_connection()
    if not conn:
        raise HTTPException(status_code=500, detail="Erreur de connexion à la base de données")
    
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT * FROM departements ORDER BY code_departement")
        departements = cursor.fetchall()
        return departements
    except Error as e:
        raise HTTPException(status_code=500, detail=f"Erreur lors de la récupération des départements: {str(e)}")
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()

@app.get("/departements/{code}", response_model=Departement)
def get_departement(code: str):
    conn = get_db_connection()
    if not conn:
        raise HTTPException(status_code=500, detail="Erreur de connexion à la base de données")
    
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT * FROM departements WHERE code_departement = %s", (code,))
        departement = cursor.fetchone()
        
        if not departement:
            raise HTTPException(status_code=404, detail=f"Département avec le code {code} non trouvé")
        
        return departement
    except Error as e:
        raise HTTPException(status_code=500, detail=f"Erreur lors de la récupération du département: {str(e)}")
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()

@app.get("/regions", response_model=List[Region])
def get_regions():
    conn = get_db_connection()
    if not conn:
        raise HTTPException(status_code=500, detail="Erreur de connexion à la base de données")
    
    try:
        cursor = conn.cursor(dictionary=True)
        # Requête avec alias si nécessaire pour correspondre au modèle de réponse
        cursor.execute("""
            SELECT 
                code_departement as code_region, 
                nom_departement as nom_region, 
                trimestre_1, 
                trimestre_2, 
                trimestre_3 
            FROM regions 
            ORDER BY code_departement
        """)
        regions = cursor.fetchall()
        return regions
    except Error as e:
        raise HTTPException(status_code=500, detail=f"Erreur lors de la récupération des régions: {str(e)}")
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()

@app.get("/regions/{code}", response_model=Region)
def get_region(code: str):
    conn = get_db_connection()
    if not conn:
        raise HTTPException(status_code=500, detail="Erreur de connexion à la base de données")
    
    try:
        cursor = conn.cursor(dictionary=True)
        # Requête avec alias pour correspondre au modèle de réponse
        cursor.execute("""
            SELECT 
                code_departement as code_region, 
                nom_departement as nom_region, 
                trimestre_1, 
                trimestre_2, 
                trimestre_3 
            FROM regions 
            WHERE code_departement = %s
        """, (code,))
        region = cursor.fetchone()
        
        if not region:
            raise HTTPException(status_code=404, detail=f"Région avec le code {code} non trouvée")
        
        return region
    except Error as e:
        raise HTTPException(status_code=500, detail=f"Erreur lors de la récupération de la région: {str(e)}")
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
