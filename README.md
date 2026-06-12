LDMS — Local Delivery Management System

A role-based delivery management backend built with Laravel 12, exposing a RESTful API secured by token authentication. LDMS coordinates a local delivery operation between three types of users — sellers, who create delivery orders; drivers, who fulfill them; and an administrator, who oversees the entire workflow.


Overview

LDMS digitizes and centralizes the delivery process, ensuring every order is tracked accurately from creation to completion. The backend serves structured JSON over a REST API, while a separate HTML/CSS/JavaScript frontend consumes those endpoints — keeping the presentation layer cleanly decoupled from the application logic.

Tech Stack

LayerTechnologyFrameworkLaravel 12LanguagePHP 8.2+DatabaseMySQLAuthenticationLaravel Sanctum (token-based)FrontendHTML, CSS, JavaScriptArchitectureRESTful API (JSON)

Architecture

The system follows the Model–View–Controller (MVC) pattern. Each request flows through a route, is delegated to the appropriate controller, which interacts with a model to perform database operations before returning a JSON response.

The data layer comprises four principal entities:


User — the base account holding credentials and role.
Seller — linked one-to-one with a user; owns orders.
Driver — linked one-to-one with a user; fulfills orders.
Order — belongs to one seller and may be assigned to one driver.


Authentication & Roles

A unified authentication system assigns every user one of three roles: admin, seller, or driver. A custom RoleMiddleware validates the user's role on every protected request and verifies the account is active, preventing deactivated or unauthorized users from accessing restricted functionality.

RoleCapabilitiesSellerCreate, view, and modify their own ordersDriverView available deliveries, accept assignments, update delivery statusAdminManage users, assign drivers, monitor all orders, generate reports

Order Lifecycle

The core of the system is a validated finite state machine governing how an order moves through its stages:

pending → assigned → picked_up → on_the_way → delivered
            ↓           ↓            ↓
          failed      failed       failed

Permissible transitions are explicitly defined in the Order model. An order may only advance through valid transitions — for example, it cannot jump directly from assigned to delivered. This protects the data from entering inconsistent states and mirrors the real-world delivery process.

Additional Design Considerations


Auto-generated order numbers — each order receives a unique identifier on creation (e.g. ORD-A1B2C3-20260612).
Soft deletes — deleted orders are hidden from active queries but retained for history and auditing.
Lifecycle timestamps — assigned_at, picked_up_at, and delivered_at are recorded to support reporting and analysis.


API Endpoints

Public

MethodEndpointDescriptionPOST/api/loginAuthenticate and receive a tokenPOST/api/registerRegister a new account

Authenticated (auth:sanctum)

MethodEndpointDescriptionPOST/api/logoutRevoke the current token

Admin (role:admin)

MethodEndpointDescriptionGET/api/admin/dashboardDashboard summaryGET/api/admin/usersList usersPOST/api/admin/usersCreate a userPOST/api/admin/users/{user}/toggleActivate / deactivate a userDELETE/api/admin/users/{user}Delete a userGET/api/admin/ordersList all ordersPOST/api/admin/orders/{order}/assignAssign a driverPOST/api/admin/orders/{order}/statusUpdate order statusGET/api/admin/reportsGenerate reports

Seller (role:seller)

MethodEndpointDescriptionGET/api/seller/dashboardSeller dashboardGET/api/seller/ordersList own ordersPOST/api/seller/ordersCreate an orderPUT/api/seller/orders/{order}Update an order

Driver (role:driver)

MethodEndpointDescriptionGET/api/driver/dashboardDriver dashboardGET/api/driver/availableList available deliveriesPOST/api/driver/deliveries/{order}/takeAccept a deliveryGET/api/driver/deliveriesActive deliveriesGET/api/driver/deliveries/historyDelivery historyPOST/api/driver/deliveries/{order}/statusUpdate delivery status

Getting Started

bash# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Configure your database in .env, then migrate
php artisan migrate

# Build frontend assets
npm run build

# Run the development server
php artisan serve

Development Process

The system was built collaboratively using Git and GitHub with a feature-branch workflow — each member developed their component on a separate branch and integrated it through reviewed pull requests. Development progressed in logical layers: the database schema and models first, then authentication and role middleware, then the role-specific controllers, and finally frontend integration.

