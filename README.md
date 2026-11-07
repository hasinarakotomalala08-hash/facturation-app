# Factura

Application Laravel de gestion de factures inspirée du document PDF fourni : aperçu A4, informations prestataire/client, lignes de prestation, total en Ariary et export PDF.

## Démarrage

Prérequis : PHP 8.3 ou plus avec l'extension PDO SQLite, Composer.

```powershell
composer install
php artisan migrate
php artisan serve
```

Ouvrez `http://127.0.0.1:8000`, créez votre espace puis ajoutez une facture. Les factures sont enregistrées dans la base SQLite locale (`database/database.sqlite`) et restent propres à chaque compte.

## Fonctions

- Création, modification, consultation, recherche et suppression de factures.
- Plusieurs lignes de prestation avec quantités et montants en Ariary.
- Export d'une facture en PDF A4.
- Inscription, connexion et isolation des factures par compte.

## Vérification

```powershell
php artisan test
```
