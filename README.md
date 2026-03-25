# FreelanceBook API

A comprehensive freelancer management system backend built with Laravel 12. This API provides endpoints for managing clients, projects, services, invoices, payments, and recurring billing for freelancers.

## 🚀 Features

- **Freelancer Registration & Authentication** - Secure user registration and login system
- **Client Management** - Add, edit, and track clients with complete billing history
- **Project Management** - Organize multiple projects per client with budget tracking
- **Service Management** - Handle milestones and services (one-time or recurring)
- **Invoice Generation** - Create and manage invoices with PDF export
- **Payment Tracking** - Record payments and maintain running balances
- **Dashboard** - Overview of income, dues, and project status

## 🛠️ Tech Stack

- **Framework:** Laravel 12
- **Database:** MySQL
- **Authentication:** Laravel Sanctum
- **Testing:** Pest PHP
- **API Response:** Custom ApiResponse class for consistent responses

## 📋 Prerequisites

- PHP 8.2+
- Composer
- MySQL 8.0+
- Node.js 18+ (for asset compilation)

## ⚡ Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/brijeshcode/freelancebook-api.git
   cd freelancebook-api
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database configuration**
   Update your `.env` file:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=freelancebook
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   ```

6. **Seed database (optional)**
   ```bash
   php artisan db:seed
   ```

7. **Start development server**
   ```bash
   php artisan serve
   ```

## 🧪 Testing

Run the test suite using Pest:

```bash
# Run all tests
./vendor/bin/pest

# Run specific test file
./vendor/bin/pest tests/Feature/ClientTest.php

# Run with coverage
./vendor/bin/pest --coverage
```

## 📡 API Documentation

### Base URL
```
http://localhost:8000/api
```

### Authentication
All endpoints require authentication using Laravel Sanctum tokens.

```http
Authorization: Bearer {your-token}
```

## 📱 User Flow

1. **Freelancer Registration** - Create account with basic details
2. **Login** - Secure authentication with Sanctum tokens
3. **Add Clients** - Register client information and contact details
4. **Create Projects** - Set up projects under specific clients
5. **Add Services** - Define milestones and services (one-time or recurring)
6. **Generate Invoices** - Create invoices for completed work
7. **Track Payments** - Record payments and maintain balance history

## 🏗️ Project Structure

Standard Laravel 12 structure:

```
app/
├── Http/
│   ├── Controllers/      # API Controllers
│   └── Requests/        # Form Request Classes (ClientStoreRequest.php format)
├── Models/              # Eloquent Models
└── ...

database/
├── migrations/          # Database migrations
└── seeders/            # Database seeders

tests/
├── Feature/            # Feature tests (Pest)
└── Unit/              # Unit tests (Pest)
```

## 🔧 Configuration

### Database Only
Configure database settings in `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=freelancebook
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## 🚀 Deployment

This API is configured for shared hosting deployment via GitHub integration:

1. **Push to main branch** triggers automatic deployment
2. **Vue.js build files** are served from `public/` directory
3. **API routes** are available at `/api/*`
4. **Frontend routes** fallback to Vue.js SPA

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/new-feature`
3. Commit changes: `git commit -am 'Add new feature'`
4. Push to branch: `git push origin feature/new-feature`
5. Submit a pull request

## 📞 Contact

- **LinkedIn:** [Brijesh Kumar Chaturvedi](https://www.linkedin.com/in/brijesh-it/)
- **GitHub Issues:** For bug reports and feature requests

## 🔄 Version History

- **v1.0.0** - Initial release with core functionality

---

**Built with ❤️ for freelancers**
