@echo off
cd /d "c:\inetpub\wwwroot\cdd_app"
php artisan schedule:run >> NUL 2>&1
