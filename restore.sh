#!/bin/bash

FILE=$1

if [ -z "$FILE" ]; then
  echo "❌ Debes indicar un archivo SQL"
  exit 1
fi

echo "🔄 Restaurando backup..."

docker exec -i postgres_db psql -U admin -d midb < "$FILE"

echo "✅ Restauración completada"
