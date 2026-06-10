import json

with open('phpstan-report.json', 'r') as f:
    data = json.load(f)

count = 0
out = ["# Análisis de Errores Críticos (Top 100)\n\n"]
out.append("A continuación se presenta una recopilación de 100 errores tipográficos, de lógica y llamadas a métodos que podrían causar un fallo crítico (basado en análisis estático).\n\n")

for file_path, file_data in data['files'].items():
    if count >= 100:
        break
    short_file = file_path.split('app/')[-1]
    
    for issue in file_data['messages']:
        if count >= 100:
            break
        count += 1
        msg = issue['message']
        line = issue.get('line', 'N/A')
        out.append(f"### {count}. `app/{short_file}` (Línea {line})\n> **{msg}**\n")

with open('/home/vircom/.gemini/antigravity/brain/403b037a-c289-4870-93a3-c4760d7b3929/analisis_errores.md', 'w') as f:
    f.writelines(out)
