#!/bin/bash

echo "⏳ Attente de la base de données MySQL..."

until php -r "new PDO('mysql:host=db;dbname=meme_generator_app', 'root', 'password');" > /dev/null 2>&1; do
  echo "📡 En attente de connexion à MySQL..."
  sleep 2
done

echo "✅ MySQL prêt, lancement des commandes artisan..."

php artisan config:clear
php artisan cache:clear
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear

exec "$@"
