# LDMS — Local Delivery Management System

A role-based delivery management backend built with **Laravel 12**, exposing a **RESTful API** secured by token authentication. LDMS coordinates a local delivery operation between three types of users — **sellers**, who create delivery orders; **drivers**, who fulfill them; and an **administrator**, who oversees the entire workflow.

---

## Overview

LDMS digitizes and centralizes the delivery process, ensuring every order is tracked accurately from creation to completion. The backend serves structured JSON over a REST API, while a separate HTML/CSS/JavaScript frontend consumes those endpoints — keeping the presentation layer cleanly decoupled from the application logic.

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12 |
| Language | PHP 8.2+ |
| Database | MySQL |
| Authentication | Laravel Sanctum (token-based) |
| Frontend | HTML, CSS, JavaScript |
| Architecture | RESTful API (JSON) |

## Architecture

The system follows the **Model–View–Controller (MVC)** pattern. Each request flows through a route, is delegated to the appropriate controller, which interacts with a model to perform database operations before returning a JSON response.

The data layer comprises four principal entities:

- **User** — the base account holding credentials and role.
- **Seller** — linked one-to-one with a user; owns orders.
- **Driver** — linked one-to-one with a user; fulfills orders.
- **Order** — belongs to one seller and may be assigned to one driver.

## Authentication & Roles

A unified authentication system assigns every user one of three roles: `admin`, `seller`, or `driver`. A custom **`RoleMiddleware`** validates the user's role on every protected request and verifies the account is active, preventing deactivated or unauthorized users from accessing restricted functionality.

| Role | Capabilities |
|---|---|
| **Seller** | Create, view, and modify their own orders |
| **Driver** | View available deliveries, accept assignments, update delivery status |
| **Admin** | Manage users, assign drivers, monitor all orders, generate reports |

## Order Lifecycle

The core of the system is a validated **finite state machine** governing how an order moves through its stages:

```
pending → assigned → picked_up → on_the_way → delivered
            ↓           ↓            ↓
          failed      failed       failed
```

Permissible transitions are explicitly defined in the `Order` model. An order may only advance through valid transitions — for example, it cannot jump directly from `assigned` to `delivered`. This protects the data from entering inconsistent states and mirrors the real-world delivery process.

## Additional Design Considerations

- **Auto-generated order numbers** — each order receives a unique identifier on creation (e.g. `ORD-A1B2C3-20260612`).
- **Soft deletes** — deleted orders are hidden from active queries but retained for history and auditing.
- **Lifecycle timestamps** — `assigned_at`, `picked_up_at`, and `delivered_at` are recorded to support reporting and analysis.

## API Endpoints

### Public
| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/login` | Authenticate and receive a token |
| `POST` | `/api/register` | Register a new account |

### Authenticated (`auth:sanctum`)
| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/logout` | Revoke the current token |

### Admin (`role:admin`)
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/admin/dashboard` | Dashboard summary |
| `GET` | `/api/admin/users` | List users |
| `POST` | `/api/admin/users` | Create a user |
| `POST` | `/api/admin/users/{user}/toggle` | Activate / deactivate a user |
| `DELETE` | `/api/admin/users/{user}` | Delete a user |
| `GET` | `/api/admin/orders` | List all orders |
| `POST` | `/api/admin/orders/{order}/assign` | Assign a driver |
| `POST` | `/api/admin/orders/{order}/status` | Update order status |
| `GET` | `/api/admin/reports` | Generate reports |

### Seller (`role:seller`)
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/seller/dashboard` | Seller dashboard |
| `GET` | `/api/seller/orders` | List own orders |
| `POST` | `/api/seller/orders` | Create an order |
| `PUT` | `/api/seller/orders/{order}` | Update an order |

### Driver (`role:driver`)
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/driver/dashboard` | Driver dashboard |
| `GET` | `/api/driver/available` | List available deliveries |
| `POST` | `/api/driver/deliveries/{order}/take` | Accept a delivery |
| `GET` | `/api/driver/deliveries` | Active deliveries |
| `GET` | `/api/driver/deliveries/history` | Delivery history |
| `POST` | `/api/driver/deliveries/{order}/status` | Update delivery status |

## Getting Started

```bash
# Install PHP dependencies
composer install

# Environment setup
cp .env.example .env
php artisan key:generate

# Configure your database in .env, then migrate
php artisan migrate

# Run the development server
php artisan serve
```

The frontend in `public/` is plain HTML/CSS/JavaScript and loads its
dependencies (Bootstrap, fonts, icons) from CDNs, so no Node/Vite build
step is required to run the application.

## Development Process

The system was built collaboratively using **Git and GitHub** with a **feature-branch workflow** — each member developed their component on a separate branch and integrated it through reviewed pull requests. Development progressed in logical layers: the database schema and models first, then authentication and role middleware, then the role-specific controllers, and finally frontend integration.

