# WaveC - Weighted Average Cost Project API

A comprehensive RESTful API built with Laravel 12 for managing product inventory and transactions with weighted average cost (WAC) calculations, real-time inventory tracking, and complete audit trails.

## 🚀 Features

- **User Authentication** - JWT-based authentication using Laravel Sanctum
- **Product Management** - Listing for products for inventory processing
- **Transaction Management** - Purchase and sale transactions with automatic WAC calculations
- **Inventory Control** - Quantity tracking with negative inventory prevention
- **Audit Trail** - Complete transaction history with before/after states
- **Data Validation** - Comprehensive request validation with custom business rules
- **Data Precision** - 4 decimal points for wac and cost field. 8 decimal points for quantity (to handle cryptocurrency if required)
- **API Resources** - Structured JSON responses with formatted data

## 📋 Requirements

- PHP 8.2 or above
- Composer
- MySQL/PostgreSQL/SQLite database

## 🛠️ Installation

### 1. Clone the Repository

```bash
git clone https://github.com/cztang/wavec.git
cd wavec
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

#publich Laravel Sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### 3. Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Configure Database

Edit the `.env` file with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wavec
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 5. Run Migrations and Seeders

```bash
# Run migrations
php artisan migrate

# Seed the database with sample data
php artisan db:seed --class=ProductSeeder
```


### 6. Start the Server

```bash
# Start Laravel development server
php artisan serve

# The API will be available at http://localhost:8000

# If you are using Laravel Herd, it will be available at http://wavec.test
```

## 📖 API Documentation

### Base URL
```
http://localhost:8000/api

OR

http://wavec.test/api
```

### Authentication

#### Register User
```http
POST /api/register
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password",
    "password_confirmation": "password"
}
```

#### Login User
```http
POST /api/login
Content-Type: application/json

{
    "email": "john@example.com",
    "password": "password"
}
```

#### Get User Profile
```http
GET /api/profile
Authorization: Bearer {token}
```

#### Logout User
```http
POST /api/logout
Authorization: Bearer {token}
```

### Products

#### Get All Products
```http
GET /api/products
Authorization: Bearer {token}
```

### Product Transactions

#### Get Product Transactions
```http
GET /api/products/{id}/transactions
Authorization: Bearer {token}

Query Parameters:
- per_page: Number of records per page (default: 15)
- transaction_type: Filter by type (1=purchase, 2=sale)
```

#### Create Transaction
```http
POST /api/products/{id}/transactions/create
Authorization: Bearer {token}
Content-Type: application/json

{
    "transaction_type": 1,
    "quantity": 100,
    "cost_per_unit": 10.50,
    "transaction_date": "2025-10-30"
}
```

**Transaction Types:**
- `1` = Purchase (cost_per_unit required)
- `2` = Sale (cost_per_unit not required. Value passed in to the API will be ignored and not saved)

#### Update Transaction
```http
POST /api/products/{id}/transactions/{transactionId}
Authorization: Bearer {token}
Content-Type: application/json

{
    "quantity": 75,
    "cost_per_unit": 12.00,
}
```

**Note:** 
- Transaction type and transaction date cannot be changed via update. If you need to change the date, please delete the record and create a new transaction
- If the transaction type for the existing transaction ID is 2 (sale), cost_per_unit will not required, value passed in to the API will be ignored.

#### Delete Transaction
```http
DELETE /api/products/{id}/transactions/{transactionId}
Authorization: Bearer {token}
```

## 🏗️ Database Schema

### Products Table
- `id` - Primary key
- `name` - Product name
- `sku` - Stock keeping unit
- `current_wac` - Current weighted average cost
- `current_quantity` - Current inventory quantity
- `total_cost` - Total inventory cost
- `timestamps`

### Product Transactions Table
- `id` - Primary key
- `product_id` - Foreign key to products
- `product_name` - Product name at transaction time
- `product_sku` - Product SKU at transaction time
- `transaction_type` - 1=Purchase, 2=Sale
- `transaction_date` - Date of transaction
- `quantity` - Transaction quantity (positive for purchase, negative for sale)
- `unit_cost` - Cost per unit
- `total_cost` - Total transaction cost
- `wac_before` - WAC before transaction
- `wac_after` - WAC after transaction
- `quantity_before` - Quantity before transaction
- `quantity_after` - Quantity after transaction
- `timestamps`

## 🎯 Business Rules

### Transaction Rules
1. **First Transaction**: Must always be a purchase. If you add a backdated transaction, it will check if it is earlier than the first transaction. If yes, it can only be purchase.
2. **Sale Validation**: Cannot sell more than available inventory. If you add a backdated transaction for sale, it will check if the quantity at that point of time is enough for sale or not. If not, it will be blocked.
3. **Date Constraints**: Transactions cannot be more than 30 days earlier than the latest transaction. Usually after end of month account closing, it must not be changed anymore. Stricter way should be we shouldn't allow edit or delete for transaction from last month. However, in real world, it seems like this is not always the case.
4. **WAC Calculation**: Automatically calculated for purchases, and preserved for sales. Random date transaction will recalculate the wac for all subsequent transactions
5. **Negative Inventory**: System prevents negative inventory situations. Updating or deleting existing transactions will be blocked if it will cause negative inventory in any of the subsequent transactions
6. **Audit Trail**: Historical product data captured at transaction date

### WAC (Weighted Average Cost) Calculation

**For Purchases:**
```
New WAC = (Current Total Cost + Purchase Cost) / New Total Quantity
```

**For Sales:**
```
WAC remains unchanged (uses current WAC for cost calculation)
```


### Features that can make the system better
1.  **User tracking**: As of now, system doesnt track who is the one who add, update or delete a transaction. Once system sign in, they will be updating a transaction and the transaction is viewable by anyone who register via the system. If this is a project for my friend running e-commerce business, I will setup a simple hosting for him, create a simple frontend, and hide the register account API.
2.  **Product binding to user**: If we tie the product to user, we can make this to be a SAAS project. Each user who register, will have their own product. We also will need to create API to allow product creation.
3.  **Product binding to company**: If we create company module, link the product to company instead of user, and link the user to company, we can allow multiple user to update same product for their company.
4.  **Role & permissions for user**: If we allow company user to create role and permission, to limit their other users activity, such as only Super Admin can do update and delete, general admin can only create new transaction and create new product, this will be an even better SAAS project.
5.  **Selling price record**: If we record the selling price too, not just how many quantity is sold, we can generate a good report to calculate how much profit they earn on each transaction.


## 🔒 Validation Rules

### Create Transaction
- `transaction_type`: Required, integer, must be 1 or 2
- `quantity`: Required, numeric, greater than 0
- `cost_per_unit`: Required for purchases, not required for sales, numeric, greater than 0
- `transaction_date`: Required, valid date

### Update Transaction
- `quantity`: Required, numeric, greater than 0
- `cost_per_unit`: Required for purchase transactions, not required for sales, numeric, greater than 0

## 📊 Response Format

### Success Response
```json
{
    "message": "Operation completed successfully",
    "data": {
        // Resource data
    }
}
```

### Error Response
```json
{
    "message": "Error description",
    "error": "Detailed error message"
}
```

### Transaction Resource Format
```json
{
    "id": 1,
    "product_id": 1,
    "product_name": "Sample Product",
    "product_sku": "SKU001",
    "transaction_type": 1,
    "transaction_type_label": "purchase",
    "transaction_date": "2025-10-30",
    "transaction_date_formatted": "Oct 30, 2025",
    "quantity": "100.00",
    "quantity_formatted": "100.00",
    "unit_cost": "10.5000",
    "unit_cost_formatted": "$10.50",
    "total_cost": "1050.0000",
    "total_cost_formatted": "$1,050.00",
    "created_at": "2025-10-30T10:00:00.000000Z",
    "updated_at": "2025-10-30T10:00:00.000000Z"
}
```

## 🧪 Development Commands

```bash
# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

## 🛡️ Security Features

- **Authentication**: Laravel Sanctum for API token authentication
- **Authorization**: Middleware protection for all authenticated routes
- **Validation**: Comprehensive input validation and sanitization
- **Database Transactions**: ACID compliance for data integrity
- **Error Handling**: Graceful error responses without exposing system details

## 📝 Notes

- All monetary values are stored as decimals with appropriate precision
- Inventory calculations use database transactions to ensure consistency
- Historical data is preserved for audit purposes
- System prevents data inconsistencies through validation and constraints

## 🤖 AI Assists

- Product Seeding
- Validation Request but plenty of changes is done afterwards
- Standardising API return pagination format
- Resources for data return in API

---

Built using Laravel 12


