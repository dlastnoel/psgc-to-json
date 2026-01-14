@echo off
REM Set MySQL database connection for Laravel
set DB_CONNECTION=mysql

REM Run Laravel command
php artisan %*

REM Restore original (optional - comment out if you want to keep mysql)
REM set DB_CONNECTION=
