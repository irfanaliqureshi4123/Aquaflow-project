# Aquaflow - E-Commerce Platform

A comprehensive e-commerce platform built with PHP for selling jewelry and accessories. The system includes an admin dashboard, customer portal, order management, payment processing, and membership features.

## Features

### Customer Features
- User registration and email verification
- Product browsing and search
- Shopping cart and wishlist management
- Membership/subscription plans
- Order placement and tracking
- Multiple payment methods (Card, COD)
- Order history and invoices
- Product reviews and ratings
- Email notifications

### Admin Features
- Dashboard with analytics and metrics
- Product management
- Order management and fulfillment
- User/customer management
- Payment processing and history
- Membership management
- Staff management
- Delivery and logistics tracking
- System logs and audit reports
- Email template management
- Payment and wallet functionality
- Backup and database management

### Technical Features
- Email verification system
- OTP verification
- CSRF protection
- Session management
- Database migrations
- Backup functionality
- Invoice generation
- Stripe payment integration
- Database logging

## Project Structure

```
├── admin/                  # Admin panel and management features
├── customer/              # Customer-facing features
├── manager/               # Manager portal
├── staff/                 # Staff management
├── assets/                # CSS, JavaScript, images
├── email_templates/       # Email templates
├── includes/              # Shared PHP includes and utilities
├── backend/               # Backend API and utility functions
├── database/              # Database schema and migrations
├── vendor/                # Composer packages
└── storage/               # File uploads and storage
```

## Installation

### Prerequisites
- PHP 8.2+
- Apache/Nginx web server
- MariaDB 10.4+
- Composer

### Setup Steps

1. Clone the repository
```bash
git clone https://github.com/irfanaliqureshi4123/Aquaflow-project.git
cd aquaWater
```

2. Install dependencies
```bash
composer install
npm install
```

3. Configure environment
```bash
cp .env.example .env
# Edit .env with your database and mail credentials
```

4. Set up database
```bash
php run_migration.php
```

5. Build assets
```bash
npm run build
```

6. Start development server
```bash
php -S localhost:8000
```

## Database

The application uses MariaDB with the following main tables:

- **users** - Customer and admin accounts
- **products** - Product catalog
- **orders** - Customer orders
- **order_items** - Individual items in orders
- **shopping_cart** - Customer shopping carts
- **wishlist** - Saved products
- **product_reviews** - Customer reviews and ratings
- **payments** - Payment records
- **memberships** - Subscription plans
- **contact_messages** - Contact form submissions

### Database Migration

Run migrations to set up the database:
```bash
php migration.php
```

## Configuration

Key configuration files:
- `.env` - Environment variables (database, mail, payment API keys)
- `config/` - Application configuration
- `database/` - Database schema and initialization

## Payment Integration

The system integrates with Stripe for card payments. Configure your Stripe API keys in `.env`:
```
STRIPE_PUBLIC_KEY=your_public_key
STRIPE_SECRET_KEY=your_secret_key
```

## Email Configuration

Configure email settings in `.env`:
```
MAIL_HOST=smtp.host.com
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_password
MAIL_FROM=noreply@example.com
```

## User Roles

- **Admin** - Full system access, management features
- **User** - Customer account with shopping privileges

## Security

- Password hashing with bcrypt
- CSRF token protection
- Session-based authentication
- Email verification for accounts
- OTP verification for sensitive operations

## API Endpoints

### Authentication
- `POST /login.php` - User login
- `POST /register.php` - User registration
- `POST /logout.php` - User logout

### Products
- `GET /products.php` - List products
- `POST /products.php` - Product details (admin)

### Orders
- `POST /customer/membership_checkout.php` - Create order
- `GET /customer/order_history.php` - Order history

### Payments
- `POST /admin/payment_test_utility.php` - Process payment

## Development

### File Naming Conventions
- Controllers: `action_subject.php` (e.g., `add_product.php`, `edit_user.php`)
- Templates: descriptive names (e.g., `email_invoice.html`)
- Utilities: `_utility.php` (e.g., `payment_test_utility.php`)

### Database Queries
- Use prepared statements for all queries
- Follow existing query patterns in the codebase

## Support

For issues and feature requests, please create an issue on GitHub.

## License

This project is private and proprietary.

## Authors

Development Team - Aquaflow Project

## Notes

- This database dump contains sample/test data for reference
- All sensitive information has been removed from the public repository
- Configure your own environment variables before deployment
- Update API keys and credentials in production environment
