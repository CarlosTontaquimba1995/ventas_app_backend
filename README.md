# E-commerce Backend API

<p align="center">
<img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
</p>

<p align="center">
<a href="#"><img src="https://img.shields.io/badge/Laravel-10.x-FF2D20?style=flat&logo=laravel" alt="Laravel Version"></a>
<a href="#"><img src="https://img.shields.io/badge/PHP-8.1-777BB4?style=flat&logo=php" alt="PHP Version"></a>
<a href="#"><img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql" alt="MySQL Version"></a>
<a href="#"><img src="https://img.shields.io/badge/License-MIT-blue.svg" alt="License"></a>
</p>

## 📋 Table of Contents
- [Features](#-features)
- [Requirements](#-requirements)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [API Documentation](#-api-documentation)
- [API Endpoints](#-api-endpoints)
- [Database Schema](#-database-schema)
- [Testing](#-testing)
- [Deployment](#-deployment)
- [Contributing](#-contributing)
- [License](#-license)

## ✨ Features

- **User Authentication**
  - JWT-based authentication
  - Email verification
  - Password reset
  - Profile management

- **Product Management**
  - CRUD operations for products
  - Product categories
  - Product variants and specifications
  - Image gallery

- **Shopping Cart**
  - Persistent cart across sessions
  - Guest checkout support
  - Cart summary and calculations

- **Order Processing**
  - Multi-step checkout
  - Order history
  - Order status tracking
  - Email notifications

- **Promotions & Discounts**
  - Coupon codes
  - Percentage and fixed amount discounts
  - Limited-time offers

## 🚀 Requirements

- PHP 8.1 or higher
- Composer
- MySQL 8.0 or higher
- Node.js & NPM (for frontend assets)
- Redis (for caching and queues)

## 🛠️ Installation

1. **Clone the repository**
   ```bash
   git clone [repository-url] ventas-app-backend
   cd ventas-app-backend
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

4. **Create environment file**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database**
   Update your `.env` file with your database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ventas_app
   DB_USERNAME=root
   DB_PASSWORD=
   ```

6. **Run migrations and seeders**
   ```bash
   php artisan migrate --seed
   ```

7. **Generate JWT secret**
   ```bash
   php artisan jwt:secret
   ```

8. **Start the development server**
   ```bash
   php artisan serve
   ```

## ⚙️ Configuration

### Environment Variables

| Key | Description |
|-----|-------------|
| `APP_ENV` | Application environment (local, production, etc.) |
| `APP_DEBUG` | Enable/disable debug mode |
| `APP_URL` | Application URL |
| `DB_*` | Database connection settings |
| `MAIL_*` | Email configuration |
| `JWT_SECRET` | JWT authentication secret |
| `STRIPE_KEY` | Stripe API key |
| `STRIPE_SECRET` | Stripe API secret |

### Caching

```bash
# Clear application cache
php artisan cache:clear

# Clear route cache
php artisan route:clear

# Clear config cache
php artisan config:clear

# Clear view cache
php artisan view:clear
```

## 📚 API Documentation

## 🔌 API Endpoints

Base URL: `http://your-domain.com/api/v1`

### Authentication

#### Register a new user
```http
POST /auth/register
```
**Request Body**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "yourpassword123",
    "password_confirmation": "yourpassword123"
}
```
**Response (201 Created)**
```json
{
    "message": "User registered successfully. Please check your email to verify your account.",
    "user": {
        "name": "John Doe",
        "email": "john@example.com",
        "updated_at": "2025-09-03T20:00:00.000000Z",
        "created_at": "2025-09-03T20:00:00.000000Z",
        "id": 1
    }
}
```

#### User Login
```http
POST /auth/login  
```
**Request Body**
```json
{
    "email": "john@example.com",
    "password": "yourpassword123"
}
```
**Response (200 OK)**
```json
{
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "bearer",
    "expires_in": 3600,
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "email_verified_at": null,
        "created_at": "2025-09-03T20:00:00.000000Z",
        "updated_at": "2025-09-03T20:00:00.000000Z"
    }
}
```

#### Forgot Password
```http
POST /auth/forgot-password
```
**Request Body**
```json
{
    "email": "john@example.com"
}
```
**Response (200 OK)**
```json
{
    "message": "Password reset link sent to your email"
}
```

#### Reset Password
```http
POST /auth/reset-password
```
**Request Body**
```json
{
    "token": "reset_token_from_email",
    "email": "john@example.com",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```
**Response (200 OK)**
```json
{
    "message": "Password reset successful"
}
```

#### Get Current User (Requires Authentication)
```http
GET /auth/me
```
**Headers**
```
Authorization: Bearer your_access_token
```
**Response (200 OK)**
```json
{
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": "2025-09-03T20:00:00.000000Z",
    "created_at": "2025-09-03T20:00:00.000000Z",
    "updated_at": "2025-09-03T20:00:00.000000Z"
}
```

### Cart

#### Get Cart Items (Customer only)
```http
GET /cart/items
```
**Headers**
```
Authorization: Bearer your_access_token
```
**Response (200 OK)**
```json
{
    "data": [
        {
            "id": 1,
            "product_id": 1,
            "quantity": 2,
            "unit_price": 99.99,
            "total": 199.98,
            "product": {
                "id": 1,
                "name": "Sample Product",
                "price": 99.99,
                "image_url": "https://example.com/images/product.jpg"
            }
        }
    ],
    "summary": {
        "subtotal": 199.98,
        "tax": 23.99,
        "discount": 0,
        "total": 223.97
    }
}
```

#### Add Item to Cart (Customer only)
```http
POST /cart/items
```
**Headers**
```
Authorization: Bearer your_access_token
Content-Type: application/json
```
**Request Body**
```json
{
    "product_id": 1,
    "quantity": 1
}
```
**Response (201 Created)**
```json
{
    "message": "Item added to cart",
    "cart_item": {
        "id": 2,
        "cart_id": 1,
        "product_id": 1,
        "quantity": 1,
        "unit_price": 99.99,
        "total": 99.99,
        "created_at": "2025-09-03T20:00:00.000000Z",
        "updated_at": "2025-09-03T20:00:00.000000Z"
    }
}
```

### Discounts

#### List All Discounts
```http
GET /discounts
```
**Response (200 OK)**
```json
{
    "data": [
        {
            "id": 1,
            "code": "SUMMER25",
            "discount_type": "percentage",
            "discount_value": 25,
            "min_order_amount": 100,
            "max_discount": 500,
            "start_date": "2025-06-01T00:00:00.000000Z",
            "end_date": "2025-08-31T23:59:59.000000Z",
            "is_active": true
        }
    ]
}
```

#### Apply Discount (Requires Authentication)
```http
POST /discounts/apply
```
**Headers**
```
Authorization: Bearer your_access_token
Content-Type: application/json
```
**Request Body**
```json
{
    "code": "SUMMER25"
}
```
**Response (200 OK)**
```json
{
    "message": "Discount applied successfully",
    "discount_amount": 50.00,
    "new_total": 150.00
}
```

#### Remove Discount (Requires Authentication)
```http
DELETE /discounts/remove/{order_id}
```
**Headers**
```
Authorization: Bearer your_access_token
```
**Response (200 OK)**
```json
{
    "message": "Discount removed successfully",
    "original_total": 200.00,
    "new_total": 250.00
}
```

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/v1/auth/register` | Register a new user |
| `POST` | `/api/v1/auth/login` | User login |
| `POST` | `/api/v1/auth/forgot-password` | Request password reset link |
| `POST` | `/api/v1/auth/reset-password` | Reset password with token |
| `GET` | `/api/v1/auth/verify-email/{token}` | Verify email address |
| `POST` | `/api/v1/auth/logout` | Logout user (requires authentication) |
| `POST` | `/api/v1/auth/refresh` | Refresh authentication token |
| `GET` | `/api/v1/auth/me` | Get current user profile (requires authentication) |

### Discounts

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v1/discounts` | List all available discounts |
| `POST` | `/api/v1/discounts/validate` | Validate a discount code |
| `POST` | `/api/v1/discounts/apply` | Apply a discount (requires authentication) |
| `DELETE` | `/api/v1/discounts/remove/{order}` | Remove discount from order (requires authentication) |

### Authentication

#### Register a new user
```http
POST /api/v1/auth/register
```

**Request Body**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "yourpassword",
    "password_confirmation": "yourpassword"
}
```

### Products

#### Get all products
```http
GET /api/v1/products
```

#### Get single product
```http
GET /api/v1/products/{id}
```

### Cart

#### Get cart items
```http
GET /api/v1/cart/items
```

#### Add item to cart
```http
POST /api/v1/cart/items
```

**Request Body**
```json
{
    "product_id": 1,
    "quantity": 2
}
```

## 🗃️ Database Schema

### Users
- id (bigint)
- name (string)
- email (string)
- email_verified_at (timestamp)
- email_verification_token (string)
- password (string)
- phone (string, nullable)
- address (json, nullable)
- last_login_at (timestamp, nullable)
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp, nullable)

### Products
- id (bigint)
- name (string)
- slug (string)
- description (text, nullable)
- price (decimal)
- compare_price (decimal, nullable)
- stock (integer)
- sku (string, nullable, unique)
- barcode (string, nullable, unique)
- is_active (boolean)
- is_featured (boolean)
- has_variants (boolean)
- images (json, nullable)
- specifications (json, nullable)
- category_id (foreign key)
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp, nullable)

### Carts
- id (bigint)
- user_id (foreign key)
- subtotal (decimal)
- tax (decimal)
- discount (decimal)
- total (decimal)
- status (string)
- session_id (string, nullable)
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp, nullable)

### Orders
- id (bigint)
- order_number (string, unique)
- user_id (foreign key)
- cart_id (foreign key, nullable)
- subtotal (decimal)
- tax (decimal)
- discount (decimal)
- shipping_cost (decimal)
- total (decimal)
- status (enum)
- payment_method (string, nullable)
- payment_status (string)
- transaction_id (string, nullable)
- shipping_method (string, nullable)
- tracking_number (string, nullable)
- billing_address (json)
- shipping_address (json)
- notes (text, nullable)
- paid_at (timestamp, nullable)
- created_at (timestamp)
- updated_at (timestamp)

## 🧪 Testing

Run the test suite:

```bash
php artisan test
```

## 🚀 Deployment

### Production Requirements
- PHP 8.1+
- MySQL 8.0+
- Redis
- Composer 2.0+
- Node.js 16+

### Deployment Steps

1. Clone the repository to your server
2. Install dependencies:
   ```bash
   composer install --optimize-autoloader --no-dev
   npm install && npm run build
   ```
3. Set up environment variables
4. Generate application key:
   ```bash
   php artisan key:generate
   ```
5. Run migrations:
   ```bash
   php artisan migrate --force
   ```
6. Optimize the application:
   ```bash
   php artisan optimize
   ```
7. Set up queue workers (Supervisor recommended)

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📧 Contact

For any questions or support, please contact [your-email@example.com](mailto:your-email@example.com)

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
