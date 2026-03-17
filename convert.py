import re

input_file = "parcelle_old.sql"
output_file = "parcelle_new.sql"

# Lire tout le fichier
with open(input_file, "r", encoding="utf-8") as f:
    content = f.read()

# Regex pour capturer tous les INSERT
pattern = re.compile(r"INSERT INTO public\.parcelle VALUES \((.*?)\);", re.DOTALL)

matches = pattern.findall(content)

rows = []

for match in matches:
    # Séparer les valeurs en respectant les virgules dans les chaînes
    # On suppose que les champs texte sont entre simples quotes
    values = []
    current = ""
    in_string = False

    for char in match:
        if char == "'" and not in_string:
            in_string = True
            current += char
        elif char == "'" and in_string:
            in_string = False
            current += char
        elif char == "," and not in_string:
            values.append(current.strip())
            current = ""
        else:
            current += char
    # Ajouter la dernière valeur
    if current:
        values.append(current.strip())

    # Assurer qu'il y a au moins 11 colonnes
    while len(values) < 11:
        values.append("NULL")

    # Mapper correctement les colonnes selon ta table
    id_0 = values[0].strip()
    geom = values[1].strip().replace("'", "")
    n_parcelle = values[2].strip()
    liste_attributaire = values[3].strip()
    attribution_2026 = values[4].strip()
    prenom_nom = values[5].strip()
    cni = values[6].strip()
    tel = values[7].strip()
    recensement = values[8].strip()
    observation = values[9].strip()
    recommendation = values[10].strip()
    statut = "'non affecte'"  # si tu veux définir un statut par défaut

    # Construire la nouvelle ligne INSERT avec ST_GeomFromWKB
    row = (
        f"({id_0}, ST_GeomFromWKB(decode('{geom}','hex'),4326), "
        f"{n_parcelle}, {liste_attributaire}, {attribution_2026}, {prenom_nom}, "
        f"{cni}, {tel}, {recensement}, {observation}, {recommendation}, {statut})"
    )

    rows.append(row)

# Créer l'INSERT final pour tout le fichier
sql = """INSERT INTO public.parcelle
(id, geom, n_parcelle, liste_attributaire, attribution_2026, prenom_nom, cni, tel, recensement, observation, recommendation, statut)
VALUES
""" + ",\n".join(rows) + ";"

# Écrire dans le fichier de sortie
with open(output_file, "w", encoding="utf-8") as f:
    f.write(sql)

print("✅ Conversion terminée : parcelle_new.sql")