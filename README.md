# Laravel Product Import – Technical Test

## Overview

This project implements a product crawler and asynchronous import system using Laravel.

Technologies used:

* Laravel
* MySQL
* Filament Admin Panel
* Laravel Queues
* Livewire / AlpineJS
* TailwindCSS
* HTTP Client for data scraping

The application fetches product data from an external source and imports them asynchronously into the database.

---

# Features

### 1. Product Crawler

A crawler fetches product data from an external API:

https://fakestoreapi.com/products

Extracted data:

* title
* price
* image_url
* sku

The crawler sends the data to a Laravel Job for asynchronous processing.

---

# Backend Architecture

### Models

Product
Image

Relationship:

Product → hasMany → Image

---

### Queue System

Product imports are processed asynchronously using Laravel Queues.

Job used:

App\Jobs\ImportProductsJob

This allows scalable product imports without blocking the request.

---

# Filament Admin Panel

Admin panel available at:

/admin

Features:

* view products
* edit products
* delete products
* preview product images

---

# Installation

Clone the repository:

```
git clone https://github.com/yourusername/laravel-product-import.git
```

Enter the project:

```
cd laravel-product-import
```

Install dependencies:

```
composer install
```

Create environment file:

```
cp .env.example .env
```

Generate application key:

```
php artisan key:generate
```

Configure database inside `.env`.

Run migrations:

```
php artisan migrate
```

Create admin user (example):

```
php artisan tinker
```

```
\App\Models\User::create([
'name' => 'Admin',
'email' => 'admin@test.com',
'password' => bcrypt('password')
]);
```

Start queue worker:

```
php artisan queue:work
```

Import products:

```
php artisan import:products
```

Run development server:

```
php artisan serve
```

Admin panel:

```
http://127.0.0.1:8000/admin
```

---

# Project Structure

Important files:

* `app/Models/Product.php`
* `app/Models/Image.php`
* `app/Jobs/ImportProductsJob.php`
* `app/Console/Commands/ImportProducts.php`
* `app/Filament/Resources/ProductResource.php`

---

# Notes

The crawler source required by the assignment returned HTML instead of JSON.
For testing purposes, a compatible public API was used to simulate the product dataset.

---

# Author

Technical test developed using Laravel ecosystem tools.
