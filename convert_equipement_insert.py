import re

input_file = "equipement_old.sql"
output_file = "equipement_new.sql"

with open(input_file, "r", encoding="utf-8") as f:
    lines = f.readlines()

rows = []

pattern = re.compile(
    r"INSERT INTO public\.equipement VALUES \((.*)\);"
)

for line in lines:
    m = pattern.search(line)
    if not m:
        continue

    values = m.group(1).split(",")

    # sécuriser le nombre de colonnes
    while len(values) < 10:
        values.append("NULL")

    id_0 = values[0].strip()
    geom = values[1].strip().replace("'", "")
    id = values[2].strip()
    Nom= values[3].strip()
    

    row = (
        f"({id_0}, ST_GeomFromWKB(decode('{geom}','hex'),4326), "
        f"{Nom}, {id})"
    )

    rows.append(row)

sql = """INSERT INTO public.parcelles
(id_0, geom, Nom, id)
VALUES
""" + ",\n".join(rows) + ";"

with open(output_file, "w", encoding="utf-8") as f:
    f.write(sql)

print("✅ Conversion terminée : equipement_new.sql")