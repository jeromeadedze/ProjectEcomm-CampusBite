# CampusBite - Ashesi University Food Ordering & Delivery Platform

CampusBite is a campus food ordering and delivery web application built for Ashesi University and surrounding Berekuso food vendors.

## System Architecture (3-Tier MVC Pattern)
- **Presentation Layer (Views)**: PHP templates, modern responsive UI, Bootstrap 5 + custom CSS, AJAX/JS.
- **Business Logic Layer (Controllers & Classes)**:
  - `controllers/`: Handles user interactions, calls models, returns processed data.
  - `classes/`: Object-Oriented models extending PDO Database connection (`CustomerClass`, `VendorClass`, `ProductClass`, `CartClass`, `OrderClass`).
  - `actions/`: Form action handlers and API endpoints for cart, checkout, auth, and CRUD operations.
- **Data Access Layer**: MySQL relational database (`database/campusbite.sql`) accessed securely via PDO prepared statements (`core/db_class.php`).

## Delivery Zones
- **Zone A (GH₵ 4.00)**: On-Campus (Hallmark, Akonnor, Munchees, Hostels: Walter Sisulu, Wangari Maathai, FabLab, Library, Faculty).
- **Zone B (GH₵ 7.00)**: Berekuso Corridor (Peckish, Eat Healthy, Hi5, Off-campus Hostels: Charlotte Court, Masere, Dufie).

## Setup Instructions
1. Import `database/campusbite.sql` into MySQL/phpMyAdmin.
2. Configure credentials in `core/db_cred.php`.
3. Launch local PHP server:
   ```bash
   php -S localhost:8000
   ```
4. Access the web app at `http://localhost:8000`.
