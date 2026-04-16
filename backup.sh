#!/bin/bash

# Carpeta donde se guardarán los backups
BACKUP_DIR="/home/gabriel/proyecto-tfg/backups"

# Crear carpeta si no existe
mkdir -p "$BACKUP_DIR"

# Nombre del archivo con fecha
FILE_NAME="backup_$(date +%Y-%m-%d_%H-%M-%S).sql"

echo "📦 Creando backup de la base de datos..."

# Ejecutar pg_dump dentro del contenedor postgres_db
docker exec postgres_db pg_dump -U admin -d midb > "$BACKUP_DIR/$FILE_NAME"

echo "✅ Backup completado: $BACKUP_DIR/$FILE_NAME"
