# Laravel + Livewire Application - Codebase Flow & Architecture

## Why Duplicate Messages Appeared (FIXED)

**Problem:** The success message ("Registration successful!" / "Login successful! Welcome back!") was showing **twice** on the dashboard.

**Root Cause:** The same `session('message')` was being rendered in **two places**:
1. **`layouts/app.blade.php`** – The main layout displays flash messages in the container (line ~264)
2. **`livewire/user/product-grid.blade.php`** – The ProductGrid component also had its own message block

Since the dashboard uses the layout AND embeds the ProductGrid, both rendered the message → **duplicate**.
 
**Fix:** Removed the message block from ProductGrid (and Cart, Favourites) because the layout already shows it globally. Now the message appears only once.

---

## Application Structure Overview

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           REQUEST LIFECYCLE                                  │
├─────────────────────────────────────────────────────────────────────────────┤
│  1. User visits URL (e.g. /dashboard)                                        │
│  2. Route matches in routes/web.php                                          │
│  3. Middleware runs (auth, admin, etc.)                                      │
│  4. Controller/Closure returns a View                                         │
│  5. View extends Layout → Layout yields content → View renders               │
│  6. Livewire components render (if any)                                      │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Routes (`routes/web.php`)

### Route Groups & Middleware

| Middleware | Purpose |
|------------|---------|
| `guest` | Only unauthenticated users (redirects logged-in users away) |
| `auth` | Only authenticated users (redirects guests to login) |
| `admin` | Only users with `is_admin = true` |

### Route Map

```
/ (root)
├── GET /                    → welcome.blade.php (homepage)
│
├── GUEST ROUTES (middleware: guest)
│   ├── GET /register        → auth/register.blade.php
│   └── GET /login           → auth/login.blade.php
│
└── AUTH ROUTES (middleware: auth)  
    ├── GET /dashboard       → dashboard.blade.php (main user page)
    ├── GET /cart            → cart.blade.php
    ├── GET /favourites      → favourites.blade.php
    │
    └── ADMIN ROUTES (middleware: auth + admin)
        ├── GET /admin/products           → admin/products/index.blade.php
        ├── GET /admin/products/create    → admin/products/create.blade.php
        └── GET /admin/products/{id}/edit → admin/products/edit.blade.php
```

---

## Main Pages & Their Purpose

### 1. **Welcome Page** (`/`)
- **File:** `resources/views/welcome.blade.php`
- **Layout:** None (standalone)
- **Purpose:** Landing page for guests. Shows Laravel branding + Livewire counter demo.
- **Access:** Public (no auth required)

---

### 2. **Register Page** (`/register`)
- **File:** `resources/views/auth/register.blade.php`
- **Layout:** `layouts/auth.blade.php`
- **Livewire:** `@livewire('auth.register')`
- **Purpose:** New user registration form (name, email, password).
- **Flow:** Submit → `Register::register()` → creates User, logs in → redirect to `/dashboard`
- **Access:** Guests only

---

### 3. **Login Page** (`/login`)
- **File:** `resources/views/auth/login.blade.php`
- **Layout:** `layouts/auth.blade.php`
- **Livewire:** `@livewire('auth.login')`
- **Purpose:** User login (email, password, remember me).
- **Flow:** Submit → `Login::login()` → Auth::attempt() → redirect to `/dashboard`
- **Access:** Guests only

---

### 4. **Dashboard** (`/dashboard`) – **MAIN USER PAGE**
- **File:** `resources/views/dashboard.blade.php`
- **Layout:** `layouts/app.blade.php`
- **Livewire:** `@livewire('user.product-grid')`
- **Purpose:** Main page after login. Shows product cards with Add to Cart & Favourite.
- **Access:** Authenticated users only
- **Difference from others:** This is the primary landing page for logged-in users. All products are displayed here in card view.

---

### 5. **Cart Page** (`/cart`)
- **File:** `resources/views/cart.blade.php`
- **Layout:** `layouts/app.blade.php`
- **Livewire:** `@livewire('user.cart')`
- **Purpose:** View cart items, update quantity, remove items.
- **Access:** Authenticated users only

---

### 6. **Favourites Page** (`/favourites`)
- **File:** `resources/views/favourites.blade.php`
- **Layout:** `layouts/app.blade.php`
- **Livewire:** `@livewire('user.favourites')`
- **Purpose:** View favourited products, add to cart, remove from favourites.
- **Access:** Authenticated users only

---

### 7. **Admin Product List** (`/admin/products`)
- **File:** `resources/views/admin/products/index.blade.php`
- **Layout:** `layouts/admin.blade.php`
- **Livewire:** `@livewire('admin.product-list')`
- **Purpose:** Admin manages products (list, add, edit, delete).
- **Access:** Admin users only

---

### 8. **Admin Add Product** (`/admin/products/create`)
- **File:** `resources/views/admin/products/create.blade.php`
- **Layout:** `layouts/admin.blade.php`
- **Livewire:** `@livewire('admin.product-create')`
- **Purpose:** Add new product with image upload.
- **Access:** Admin users only

---

### 9. **Admin Edit Product** (`/admin/products/{id}/edit`)
- **File:** `resources/views/admin/products/edit.blade.php`
- **Layout:** `layouts/admin.blade.php`
- **Livewire:** `@livewire('admin.product-edit', ['productId' => $productId])`
- **Purpose:** Edit existing product.
- **Access:** Admin users only

---

## Layouts – How They Work

### `layouts/auth.blade.php`
- Used by: Register, Login
- Contains: Auth form styling, `@yield('content')`, Livewire scripts
- No navbar (minimal auth-focused design)

### `layouts/app.blade.php`
- Used by: Dashboard, Cart, Favourites
- Contains: Navbar (Products, Cart, Favourites, Admin link, User info, Logout), **global flash message**, `@yield('content')`
- This is why the message was duplicated when child views also showed it

### `layouts/admin.blade.php`
- Used by: Admin product pages
- Contains: Admin navbar (Products, Dashboard, Logout), `@yield('content')`
- Dark theme for admin area

---

## Page Flow Diagram

```
                    ┌─────────────┐
                    │   Visitor   │
                    └──────┬──────┘
                           │
              ┌────────────┴────────────┐
              │                         │
        Not Logged In             Logged In
              │                         │
              ▼                         ▼
     ┌────────────────┐         ┌────────────────┐
     │ / (Welcome)     │         │ /dashboard     │
     │ /register      │         │ (MAIN PAGE)    │
     │ /login         │         │ Product Grid   │
     └────────────────┘         └───────┬────────┘
                                        │
                    ┌───────────────────┼───────────────────┐
                    │                   │                   │
                    ▼                   ▼                   ▼
             ┌────────────┐      ┌────────────┐      ┌────────────┐
             │ /cart     │      │ /favourites│      │ /admin/*   │
             │ Cart items│      │ Favourites │      │ (if admin) │
             └────────────┘      └────────────┘      └────────────┘
```

---

## Key Differences Between Pages

| Page        | Layout   | Main Content                    | Who Sees It   |
|-------------|----------|----------------------------------|---------------|
| Welcome     | None     | Laravel + Counter                | Everyone      |
| Register    | auth     | Registration form               | Guests        |
| Login       | auth     | Login form                      | Guests        |
| Dashboard   | app      | Product grid (cards)            | All users     |
| Cart        | app      | Cart items list                 | All users     |
| Favourites  | app      | Favourite products              | All users     |
| Admin/*     | admin    | Product CRUD                     | Admins only   |

---

## Flash Message Flow

1. **Set:** `session()->flash('message', 'Your message')` (e.g. in Register, Login, Cart)
2. **Display:** Only in `layouts/app.blade.php` (and `layouts/auth.blade.php` for auth pages)
3. **Consumed:** Flash data is cleared after the next request
4. **Fix applied:** Removed duplicate display from Livewire components that sit inside the app layout
