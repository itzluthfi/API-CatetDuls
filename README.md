# 💰 FinNote API

> Modern Personal Finance Management RESTful API built with Laravel

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](LICENSE)

---

## 🎯 About

**FinNote API** adalah RESTful API untuk manajemen keuangan pribadi. Fitur lengkap untuk tracking income/expense, multiple wallets, categories, dan financial analytics.

**Perfect for:** Mobile Apps • Web Apps • Desktop Apps • Integrations

---

## ✨ Key Features

- 🔐 **Authentication** - Register, Login, Token-based auth (Sanctum)
- 👤 **User Management** - Profile, Photo, Preferences, Statistics
- 📚 **Multi Books** - Kelola multiple buku keuangan
- 💳 **Multi Wallets** - Cash, Bank, E-Wallet dengan real-time balance
- 📂 **Categories** - Income & Expense categories
- 💸 **Transactions** - Full CRUD, Image upload, Advanced filtering
- 📊 **Analytics** - Summary, Group by category/date, Reports

---

## 🚀 Quick Start

### Installation

```bash
# Clone repository
git clone https://github.com/yourusername/finnote-api.git
cd finnote-api

# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database in .env
DB_DATABASE=finnote
DB_USERNAME=root
DB_PASSWORD=

# Install Sanctum
composer require laravel/sanctum

# Run migrations & seeders
php artisan migrate --seed

# Create storage link
php artisan storage:link

# Start server
php artisan serve
```

**Demo Account:**
```
Email: sirL@gamil.com
Password: password
```

---

## 📖 API Endpoints

### Authentication
```
POST   /api/auth/register          # Register user
POST   /api/auth/login             # Login
POST   /api/auth/logout            # Logout
GET    /api/auth/me                # Get current user
POST   /api/auth/change-password   # Change password
```

### User Profile
```
GET    /api/user/profile           # Get profile
PUT    /api/user/profile           # Update profile
POST   /api/user/photo             # Upload photo
GET    /api/user/statistics        # Get statistics
```

### Books
```
GET    /api/books                  # Get all books
POST   /api/books                  # Create book
GET    /api/books/{id}             # Get single book
PUT    /api/books/{id}             # Update book
DELETE /api/books/{id}             # Delete book
```

### Wallets
```
GET    /api/wallets                # Get all wallets
POST   /api/wallets                # Create wallet
GET    /api/wallets/{id}           # Get single wallet
PUT    /api/wallets/{id}           # Update wallet
DELETE /api/wallets/{id}           # Delete wallet
```

### Categories
```
GET    /api/categories             # Get all categories
POST   /api/categories             # Create category
GET    /api/categories/{id}        # Get single category
PUT    /api/categories/{id}        # Update category
DELETE /api/categories/{id}        # Delete category
```

### Transactions
```
GET    /api/transactions           # Get all (paginated, filterable)
POST   /api/transactions           # Create transaction
GET    /api/transactions/{id}      # Get single transaction
PUT    /api/transactions/{id}      # Update transaction
DELETE /api/transactions/{id}      # Delete transaction

# Analytics
GET    /api/transactions/summary        # Income/Expense summary
GET    /api/transactions/by-category    # Group by category
GET    /api/transactions/by-date        # Group by date
POST   /api/transactions/bulk-delete    # Bulk delete
```

---

## 💡 Usage Examples

### Register & Login
```bash
# Register
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'

# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'

# Response will include token
{
  "success": true,
  "data": {
    "token": "1|xxxxxxxxxxxxxx",
    "token_type": "Bearer"
  }
}
```

### Create Transaction
```bash
curl -X POST http://localhost:8000/api/transactions \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "book_id": 1,
    "wallet_id": 1,
    "category_id": 1,
    "type": "PENGELUARAN",
    "amount": 50000,
    "note": "Makan siang"
  }'
```

### Get Summary
```bash
curl -X GET "http://localhost:8000/api/transactions/summary?book_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Response
{
  "success": true,
  "data": {
    "income": 2000000,
    "expense": 25000,
    "balance": 1975000
  }
}
```

---

## 🗄️ Database Schema

```
users
├── id
├── name
├── email
├── password
├── photo_url
└── preferences (JSON)

books
├── id
├── user_id
├── name
├── description
├── icon
├── color
└── is_default

wallets
├── id
├── book_id
├── name
├── type (CASH|BANK|E_WALLET)
├── icon
├── color
├── initial_balance
└── is_default

categories
├── id
├── book_id
├── name
├── type (PEMASUKAN|PENGELUARAN)
├── icon
├── color
└── is_default

transactions
├── id
├── book_id
├── wallet_id
├── category_id
├── type (PEMASUKAN|PENGELUARAN)
├── amount
├── note
├── image_url
└── created_at_ms (timestamp)
```

---

## ⚙️ Configuration

### .env Configuration
```env
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_DATABASE=finnote
DB_USERNAME=root
DB_PASSWORD=

FILESYSTEM_DISK=public

SESSION_DRIVER=cookie
SANCTUM_STATEFUL_DOMAINS=localhost:3000
```

### CORS Setup
File: `config/cors.php`
```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => ['*'],
'supports_credentials' => true,
```

---

## 🧪 Testing

```bash
# Run tests
php artisan test

# Test with Postman
# Import collection from /docs/postman_collection.json
```

---

## 📦 Deployment

### Production Checklist

```bash
# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set production environment
APP_ENV=production
APP_DEBUG=false

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Storage link
php artisan storage:link
```

### Recommended Hosting
- ✅ Laravel Forge
- ✅ DigitalOcean
- ✅ AWS EC2
- ✅ Heroku

---

## 🔒 Security

- ✅ Password hashing (bcrypt)
- ✅ Token authentication (Sanctum)
- ✅ CSRF protection
- ✅ Rate limiting
- ✅ Input validation
- ✅ SQL injection prevention
- ✅ XSS protection

---

## 📝 License

This project is licensed under the MIT License.

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the project
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📧 Contact

- **Email:** luthfishidqi2@gmail.com
- **GitHub:** [@itzluthfi](https://github.com/itzltuhfi)
- **Website:** https://yourwebsite.com

---

## 🙏 Acknowledgments

- Laravel Framework
- Laravel Sanctum
- All contributors

---

<div align="center">

Made with ❤️ by [Your Name]

**Star ⭐ this repository if you find it helpful!**

</div>