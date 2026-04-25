# QR Menu - Restaurant Management System

A production-level Laravel 11 QR Menu and Restaurant Management System with customer ordering and admin panel for kitchen operations.

## Features

✨ **Customer Features**
- QR Code menu with table number tracking
- Browse items by categories
- Product photos (optional)
- Shopping cart system
- Order placement with notes
- "Call Waiter" functionality
- Order confirmation screen

🔐 **Admin Features**
- Category CRUD with sorting
- Product CRUD with photo upload (with Product Code - MSSQL ID display)
- Kitchen & Bar display screens (live orders with real-time updates via SSE)
- **Symphony POS Kitchen Screen** (`/kitchen-pos`): live read-only board powered by the MSSQL KDS query (CheckNumber grouped, kitchen messages support, audio alert, complete/undo with 24h history)
- Table number shown on kitchen order cards
- Order status management (new → preparing → ready → completed)
- Undo ready functionality with configurable time window
- Waiter call management with admin notifications (call button is also available below the mobile cart)
- MSSQL Symphony database product sync (bulk update & preview)
  - Tabbed MSSQL Settings: separate connection + custom SQL for **Products (Symphony)** and **KDS (Kitchen)**
  - **Query Preview** button runs the custom SELECT/WITH and shows the first 100 rows in a modal
  - ProductCode (mssql_id) used as a stable key — re-sync updates existing products and inserts new ones (no deletes)
  - Automatic deduplication for multi-price-level rows (RVC > Property > Enterprise)
- Customizable kitchen & bar screen titles
- Bar completed orders display limit configuration
- Screen auto-clear at scheduled time each day
- QR code generation & bulk table labeling (A4 print template, archive, ZIP download)

🎨 **Design**
- Luxury theme with dark (#1A1A2E) and gold (#D4A574) colors
- Mobile-responsive design using Tailwind CSS
- Fast and smooth user experience

## Tech Stack

- **Backend**: Laravel 11 with PHP 8.2+
- **Database**: MySQL (qr_menu) - primary application database
- **External Database**: MSSQL (Symphony Restaurant) - product sync via PDO
- **Frontend**: Blade Templates, Tailwind CSS, Font Awesome 6.4.0
- **Real-time**: Server-Sent Events (SSE) with fallback polling for kitchen/bar screens
- **Auth**: Laravel Breeze (email/password authentication)
- **QR Codes**: endroid/qr-code library
- **Storage**: Laravel Storage (Local filesystem for product photos)

## System Requirements

- PHP 8.2+
- MySQL 5.7+ (for application data)
- MSSQL Server (optional, for Symphony integration)
- Composer
- Node.js 16+ (for frontend build tools)
- PDO MySQL & PDO MSSQL extensions enabled (if using Symphony sync)

## Installation

### 1. Clone and Setup

```bash
cd rocksqr
composer install
npm install
```

### 2. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` file with your database credentials:
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=qr_menu
DB_USERNAME=root
DB_PASSWORD=your_password
```

**Optional: MSSQL Symphony Integration**
If using the MSSQL product sync or Symphony POS kitchen screen, configure via **Admin → MSSQL Settings** after database setup (no .env variables needed — settings are stored in the database). The Products and KDS tabs are independent and each has its own host/port/db/user/password and SQL query.

### 3. Database Setup

```bash
php artisan migrate
php artisan db:seed
```

### 4. Storage Link

```bash
php artisan storage:link
```

This creates a symlink so uploaded product photos are publicly accessible.

### 5. Build Frontend Assets

```bash
npm run dev     # Development
npm run build   # Production
```

### 6. Create Admin User (Laravel Breeze)

```bash
php artisan breeze:install blade
php artisan migrate
```

Then create admin account through login page.

### 7. Start Development Server

```bash
php artisan serve
```

Access:
- Menu: `http://localhost:8000/menu/1` (replace 1 with table number 1-100)
- Admin: `http://localhost:8000/login`

## Project Structure

```
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── MenuController.php       # Customer ordering
│   │       ├── Admin/
│   │       │   ├── CategoryController.php
│   │       │   ├── ProductController.php
│   │       │   ├── OrderController.php
│   │       │   └── WaiterCallController.php
│   ├── Models/
│   │   ├── Category.php
│   │   ├── Product.php
│   │   ├── Order.php
│   │   └── WaiterCall.php
├── database/
│   ├── migrations/        # Database schema
│   └── seeders/
├── resources/
│   ├── views/
│   │   ├── customer/      # Customer-facing views
│   │   └── admin/         # Admin panel views
│   └── css/app.css
├── routes/
│   └── web.php           # All routes defined here
└── config/
    └── database.php      # Database configuration
```

## Database Schema

### Categories Table
- id, name, slug, description, sort_order, is_active, created_at, updated_at, deleted_at

### Products Table
- id, category_id, name, description, price, photo_path, sort_order, is_available, mssql_id, created_at, updated_at, deleted_at

### Orders Table
- id, table_no, total_price, order_note, status (new/preparing/ready/completed), items_json, completed_at, created_at, updated_at

### Waiter Calls Table
- id, table_no, status (pending/attended), note, attended_at, created_at, updated_at

## Usage Guide

### For Customers

1. Scan QR code or visit `http://localhost/menu/{table_number}`
2. Browse menu items by category
3. Add items to cart
4. Adjust quantities
5. Add special notes (optional)
6. Click "Place Order"
7. View order confirmation
8. Click "Call Waiter" if needed

### For Admins

1. Login at `/admin` (use Breeze auth)
2. **Dashboard**: View system statistics
3. **Categories**: Manage menu categories
4. **Products**: Add/edit/delete products with photos
5. **Orders**: View all orders, change status
6. **Kitchen Screen**: Live view of pending orders at `/admin/orders/kitchen/screen`
7. **Symphony POS Kitchen Screen**: Live MSSQL-driven view at `/kitchen-pos` (cross-linked with the local kitchen screen)
8. **Waiter Calls**: View and mark waiter calls as attended
9. **MSSQL Sync**: Pull products from Symphony via the configured custom query (Admin → MSSQL Settings → Products tab)

## Kitchen Screen

Access at: `http://localhost:8000/admin/orders/kitchen`

- Auto-refreshes every 15 seconds
- Shows NEW orders (red border) and PREPARING orders (yellow border)
- Click status dropdown to update order status
- Color-coded for quick visual identification

## Photo Upload

- Photos stored in `storage/app/public/products/`
- Accessible via `storage/products/{filename}`
- Max file size: 2MB
- Supported formats: JPEG, PNG, JPG, GIF
- Symlink created via `artisan storage:link`

## MSSQL Symphony Integration

All connection details are managed in the database via **Admin → MSSQL Settings** — no `.env` variables required.

### Tabs
- **Products (Symphony)**: connection + custom SELECT used by the product sync. Supports PascalCase aliases `ProductCode`, `ProductName`, `FamilyGroup`, `Price`, `PriceLevel`, `PriceLevelID`. `ProductCode` is stored on `products.mssql_id` and used as the upsert key.
- **KDS (Kitchen)**: independent connection + custom SELECT used by the Symphony POS Kitchen Screen (`/kitchen-pos`). Expected columns include `CheckNumber`, `TableNumber`, `RvcName`, `MajGrp`, etc. Rows with `MajGrp = 99` are treated as kitchen messages; messages without a check number are surfaced as flash banners on top of the screen.

Each tab has its own **Test Connection** and **Preview Query** (first 100 rows, SELECT/WITH only) buttons.

## API Endpoints

### Customer Routes
- `GET /menu/{tableNo}` - View menu
- `POST /order/{tableNo}` - Place order
- `GET /order-success/{order}` - Order confirmation
- `POST /waiter-call/{tableNo}` - Call waiter

### Admin Routes
- `GET /admin` - Dashboard
- `GET/POST /admin/categories` - Category CRUD
- `GET/POST /admin/products` - Product CRUD
- `GET /admin/orders` - All orders
- `GET /admin/orders/kitchen` - Kitchen screen
- `POST /admin/orders/{order}/status` - Update order status
- `GET /admin/waiter-calls` - Waiter calls
- `POST /admin/waiter-calls/{call}/attended` - Mark attended
- `GET /admin/mssql-settings` - MSSQL Symphony / KDS settings (tabs)
- `GET /kitchen-pos` - Symphony POS Kitchen Screen

## Production Checklist

- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Set strong `APP_KEY` (run `php artisan key:generate`)
- [ ] Configure MySQL database with production credentials
- [ ] Run `npm run build` for optimized assets
- [ ] Set proper file permissions: `chmod -R 775 storage bootstrap/cache`
- [ ] Configure MAIL settings for order notifications (optional)
- [ ] Set up proper backups for database and uploads
- [ ] Use HTTPS in production
- [ ] Configure MSSQL Symphony / KDS connections from Admin → MSSQL Settings if using sync or `/kitchen-pos`
- [ ] Run `php artisan config:cache` and `php artisan route:cache`

## Troubleshooting

### "Class 'App\Models\Category' not found"
Run: `composer dump-autoload`

### Photos not uploading
Check storage permissions: `chmod -R 775 storage`
Ensure symlink exists: `php artisan storage:link`

### Database errors
Verify `.env` database credentials
Run migrations: `php artisan migrate:fresh --seed`

### Frontend styles not loading
Run: `npm run dev` or `npm run build`
Clear cache: `php artisan cache:clear`

## Performance Tips

1. **Database**: Add indexes to frequently queried columns
2. **Caching**: Enable Redis for session/cache storage
3. **Images**: Optimize product photos before upload
4. **CDN**: Serve static assets from CDN in production
5. **Kitchen Screen**: Reduce refresh interval if server-sent events are implemented

## Security Notes

- Admin routes are protected by `auth` middleware
- CSRF protection on all POST requests
- SQL injection prevention via Eloquent ORM
- File upload validation on both client and server
- Input sanitization on all user inputs
- Sessions expire after 120 minutes of inactivity

## Support & Maintenance

- Clear Laravel cache: `php artisan cache:clear`
- Clear config cache: `php artisan config:clear`
- View logs: `storage/logs/laravel.log`
- Database reset: `php artisan migrate:fresh --seed`

## License

MIT License - feel free to use for commercial projects

## Author

QR Menu Development Team