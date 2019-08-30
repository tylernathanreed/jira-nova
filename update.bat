@echo off

REM ===============================
REM == Entering Maintenance Mode ==
REM ===============================

echo "[1/7] Entering maintenance mode..."
php artisan down --quiet

REM ================================
REM == Pre-Update Artisan Cleanup ==
REM ================================

echo "[2/7] Performing pre-update artisan cleanup..."

echo "[2/7] -> Clearing compiled packages and services..."
call php artisan clear-compiled --quiet

echo "[2/7] -> Clearing cached configuration..."
call php artisan config:clear --quiet

echo "[2/7] -> Clearing cached routes..."
call php artisan route:clear --quiet

echo "[2/7] -> Clearing compiled views..."
call php artisan view:clear --quiet

REM ================
REM == Git Update ==
REM ================

echo "[3/7] Updating source files..."

echo "[3/7] -> Clearing local changes..."
git reset --hard --quiet

echo "[3/7] -> Pulling changes from remote repository..."
git pull --quiet


REM ======================
REM == Composer Install ==
REM ======================

echo "[4/7] Updating dependencies..."

echo "[4/7] -> Installing composer packages..."
call composer install --quiet

REM ======================
REM == Database Updates ==
REM ======================

echo "[5/7] Updating database..."

echo "[5/7] -> Running migrations..."
php artisan migrate --force --quiet

echo "[5/7] -> Running seeders..."
php artisan db:seed --force --quiet

REM ===================
REM == Optimizations ==
REM ===================

echo "[6/7] Optimizing application..."

echo "[6/7] -> Caching configuration files..."
php artisan config:cache --quiet

REM =============================
REM == Leaving mantenance mode ==
REM =============================

echo "[7/7] Leaving maintenance mode..."
php artisan up --quiet

REM ==========
REM == Done ==
REM ==========

echo Done

pause