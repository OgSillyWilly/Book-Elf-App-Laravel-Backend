# Book-Elf 📚

Een moderne boekenkast applicatie gebouwd met Laravel en Flutter.

## Features

- 📖 Boeken beheren met volledige CRUD functionaliteit
- 🔍 Zoeken via Google Books API met automatische data import
- 🖼️ Book covers automatisch ophalen
- 📅 Datums invoeren met kalender (DD-MM-YYYY formaat)
- ✅ Lees status tracking met start- en einddatum
- 🏷️ Categorisatie per type, uitgever, publicatiedatum
- 📍 Locatie tracking (kast, plank, positie)
- 🔎 Zoeken en filteren op titel en lees status
- 📱 Native iOS app (Flutter)
- 🌐 Web interface (Laravel)

## Tech Stack

**Backend:**
- Laravel 11
- MySQL database
- RESTful API

**Frontend:**
- Flutter (iOS)
- Material Design 3
- HTTP package voor API communicatie

## Setup

### Laravel Backend

```bash
# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Start development server
./start-server.sh
# Or manually:
php artisan serve --host=0.0.0.0 --port=8000
```

## API Endpoints

- `GET /api/books` - Lijst van alle boeken
- `POST /api/books` - Nieuw boek aanmaken
- `GET /api/books/{id}` - Enkel boek ophalen
- `PUT /api/books/{id}` - Boek bijwerken
- `DELETE /api/books/{id}` - Boek verwijderen

## Database Schema

### Books Table

- `id` - Primary key
- `title` - Boektitel (verplicht)
- `author` - Auteur (verplicht)
- `isbn` - ISBN nummer
- `type` - Type boek (Roman, Thriller, etc.)
- `publisher` - Uitgever
- `publication_date` - Publicatiedatum
- `cover_url` - URL naar book cover
- `cabinet` - Kast locatie
- `shelf` - Plank nummer (verplicht)
- `position` - Positie op plank (verplicht)
- `is_read` - Gelezen status (boolean)
- `start_date` - Startdatum lezen
- `end_date` - Einddatum lezen

---

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
