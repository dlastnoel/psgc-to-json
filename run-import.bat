@echo off
set DB_CONNECTION=mysql
php -d memory_limit=1G artisan psgc:sync --path="storage/app/psgc/PSGC-4Q-2025-Publication-Datafile.xlsx" --force
