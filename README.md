# CampusBite

CampusBite is a food ordering and delivery site for Ashesi University. Students order from on-campus kitchens and kitchens in Berekuso. Each kitchen runs its own orders, menu and deliveries. Paystack takes the payment and pays each kitchen its share. CampusBite keeps a commission.

It was built for an e-commerce course. The look comes from a Shopify theme the team designed, rebuilt here in plain PHP and MySQL, one sprint at a time.

## Contents

- [What it does](#what-it-does)
- [How money works](#how-money-works)
- [Demo accounts](#demo-accounts)
- [Setting up](#setting-up)
- [Pages](#pages)
- [Project structure](#project-structure)
- [Security](#security)
- [Known limits and open questions](#known-limits-and-open-questions)

## What it does

CampusBite has three kinds of account. A new sign-up is always a customer.

| Role | `user_role` | What they do |
|---|---|---|
| Customer | 2 | Browse kitchens and meals, search, order, pay with Paystack, track orders, cancel before cooking starts, save favourites, message a kitchen or support |
| Kitchen staff | 4 | Run one kitchen (linked by `brand_id`): take and deliver orders, keep the menu, see their customers, answer messages, set where Paystack pays them |
| Super admin | 1 | Add kitchens, set each kitchen's commission, pause a kitchen, manage kitchen staff logins, watch the money, answer support |

### Customers

- **Browse and search.** The home page shows kitchens, categories, popular meals and your last orders. You can filter the menu by kitchen and category, and search meals and kitchens. Sold-out meals and paused kitchens are hidden.
- **Bill (cart).** One order comes from one kitchen. Change quantities on the Bills page or in the side panel. The delivery fee follows the kitchen's zone:
  - Zone A, on campus: GH₵ 5.00
  - Zone B, Berekuso: GH₵ 10.00
- **Checkout.** Choose a drop point and add delivery notes, then pay on Paystack's page (Mobile Money or card). The order is only created after Paystack confirms the payment. A cancelled or failed payment leaves the bill as it was.
- **Track the order.** Each order moves through five steps: Placed → Preparing → Ready → In Transit → Delivered. Emails go out for the receipt and each status change.
- **Cancel.** You can cancel while an order is still Placed. Paystack refunds the full amount.
- **Account.** Favourites, settings (profile, phone, drop point, password), forgot password by email, and Help & Support messages with replies.

### Kitchen staff (kitchen portal)

- **Dashboard.** Today's sales, the last 7 days and all time, CampusBite's commission, the order queue, best sellers, and how the kitchen gets paid (including any refunds still to be taken back).
- **New-order alert.** While any kitchen page is open, it checks for new orders every 20 seconds. A new order puts a count on the Orders link and in the tab title, plays a short chime (once someone has clicked or typed on the page, as browsers require) and shows a message. The Orders page reloads itself unless someone is filling in a form.
- **Orders.** *In progress* shows tickets with one button for the next step: Start preparing, Mark ready, Out for delivery, Mark delivered. You can reject an order (with a refund) while it's Placed or Preparing. *All orders* can be searched by invoice or customer, and filtered by date and status.
- **Menu.** Add, edit, mark sold out and delete meals, with photos. Meals without a photo are marked "No photo", and the page says how many are missing one. A meal that has been ordered can't be deleted, only marked sold out.
- **Customers.** Everyone who has ordered from the kitchen, with order counts, spend, usual drop point, and Call and WhatsApp buttons.
- **Messages.** Customer questions sent to the kitchen, with replies.
- **Kitchen info.** Location, phone, open or paused, and the **payout account**: a Mobile Money number or bank account that Paystack pays the kitchen's share into. A kitchen without a payout account can't take orders.

### Super admin

The super admin looks after kitchens and money. Kitchens own their food, customers and orders.

- **Dashboard.** Sales and CampusBite's earnings for today, the last 7 days and all time. Open support messages. Every kitchen's status, logins, menu size and 7-day sales. Kitchens that can't take payments yet are flagged.
- **Kitchens.** Add a kitchen, change its commission, set it Active or Paused, delete an empty one, and open a read-only kitchen page (money, menu, categories, messages, staff, payout account). A kitchen paused by the admin can't reopen itself.
- **Money.** For any date range: sales, commission, delivery fees and each kitchen's share, plus a CSV download. Also each kitchen's payout account, and a payment check that flags missing, duplicate, unconfirmed or mismatched payments and failed refunds.
- **Users.** Kitchen staff logins grouped by kitchen. Add a login, or remove someone's kitchen access.
- **Support.** Every support message, with replies, and mark resolved or reopen. The admin can also open a single order from a message and cancel it with a refund.

## How money works

1. Before taking orders, a kitchen adds a payout account under **Kitchen info**. CampusBite creates a Paystack *subaccount* for it.
2. At checkout, the payment is split:
   - **CampusBite keeps** its commission on the meals, for example 8% of the meal total.
   - **The kitchen gets** the meals minus the commission, plus the delivery fee, because kitchens deliver their own orders.
   - **Paystack's fee** comes out of CampusBite's share. If a kitchen's commission is 0%, the kitchen pays it.
3. Paystack pays the kitchen's share straight to its account on Paystack's settlement schedule. CampusBite never holds the kitchen's money, so there are no manual payouts.
4. Each order stores the commission rate it was paid at, and each payment stores both shares. Changing a kitchen's commission later doesn't change past orders.
5. Cancelled orders are refunded in full through Paystack. They don't count in sales, commission or best sellers.
6. Paystack takes refunds from CampusBite's balance, even for split payments. So the kitchen's share of a refunded order is **taken back from its next orders**: the next payments send CampusBite a larger charge, up to that order's whole kitchen share, until the amount is repaid. Each payment stores what it took back (`payment.refund_recovery`). Kitchens see what's still to take back on their dashboard and Kitchen info; the admin sees it on the Money page and the kitchen's page.

## Seed menu

A fresh import has six kitchens and 39 meals across nine categories: Rice & Jollof Bowls, Local Staples, Grills & Suya, Healthy Bowls & Salads, Pastries & Quick Bites, Drinks & Fresh Smoothies, Shawarma & Wraps, Desserts & Cakes and Soft Drinks. Each kitchen has its own take on the common items (Hallmark's classic chicken shawarma, Akonnor's spicy beef shawarma with shito, Munchees' mixed platter, Peckish's shawarma fries, Eat Healthy's wholewheat wrap, Hi5's lamb platter), and every meal has a photo in `images/products/` (free Unsplash photos, credited in `images/products/CREDITS.md`). An existing database gets the extra meals from `database/migrations/2026-11-26_more_meals.sql`.

## Demo accounts

| Role | Email | Password | Notes |
|---|---|---|---|
| Super admin | `admin@campusbite.com` | `Admin123!` | Opens the admin dashboard |
| Kitchen staff | `kitchen@campusbite.com` | `Kitchen123!` | Linked to Munchees Grills (`brand_id` 3) |
| Customer | Sign up on the site | Your choice | Every new account is a customer |

`database/campusbite.sql` creates both staff accounts, so they work straight after a fresh import. They are for local testing only: **change their passwords or delete them on any real server.** An existing database won't get them from a migration. To add them there, sign up on the site and then:

```sql
UPDATE cb_customer SET user_role = 1 WHERE customer_email = 'admin@campusbite.com';
```

For a kitchen login, use the admin's **Users** page instead.

The demo kitchen also needs a payout account before customers can pay it. Sign in as the kitchen, open **Kitchen info**, and add one. With Paystack test keys, MTN Mobile Money `0551234987` works.

**Paystack test payments.** Use Paystack's test cards or test Mobile Money on the payment page. Paystack refuses reserved email domains such as `.test`, so test customers need an address like `name@example.com`.

## Setting up

### Requirements

- PHP 8.1 or newer, with the `pdo_mysql`, `curl`, `openssl` and `mbstring` extensions
- MySQL or MariaDB (XAMPP works)
- A Paystack account (test keys are enough) to take payments
- Optional: an SMTP account (for example Gmail with an app password) to send real emails

### 1. Get the code

```bash
git clone <repository-url>
```

```bash
cd CampusBite
```

### 2. Create the database

Create an empty database, then import the schema and seed data:

```bash
mysql -u root -p -e "CREATE DATABASE campusbite CHARACTER SET utf8mb4"
```

```bash
mysql -u root -p campusbite < database/campusbite.sql
```

> **Warning:** `campusbite.sql` starts with `DROP TABLE` statements. Never import it into a database that holds real data. To upgrade an existing database, run the files in `database/migrations/` once each, oldest first (by the date in the name). A fresh import already includes all of them.

### 3. Database settings

`core/db_cred.php` is not in git. Create it yourself:

```php
<?php
define("DB_HOST", "127.0.0.1");
define("DB_USER", "root");
define("DB_PASS", "");
define("DB_NAME", "campusbite");
```

### 4. Paystack keys

```bash
cp core/paystack_config.example.php core/paystack_config.php
```

Paste your **test** secret and public keys into `core/paystack_config.php`. You'll find them in the Paystack dashboard under Settings › API Keys & Webhooks. Without keys, the site works but checkout shows that payments aren't set up.

### 5. Email (optional)

```bash
cp core/mail_config.example.php core/mail_config.php
```

- `MAIL_MODE` `log`, the default, also used when there's no config file: nothing is sent. Each email is saved as an HTML file in `storage/mail/`. On localhost, the forgot-password page shows the reset link on screen.
- `MAIL_MODE` `smtp`: emails go through the SMTP server in the file. Set `APP_URL` to the site's address. For Gmail use `smtp.gmail.com`, port `587`, `tls` and an app password.

### 6. Run it

```bash
php -S localhost:8000
```

Open <http://localhost:8000> and sign in with one of the [demo accounts](#demo-accounts).

The files `core/db_cred.php`, `core/paystack_config.php`, `core/mail_config.php` and `storage/` are git-ignored. **Never commit them, or any password or API key.**

## Pages

### Shopper pages (`views/`)

| Page | File | What it shows |
|---|---|---|
| Home | `index.php` → `views/home.php` | Greeting, banner, kitchens, categories, popular meals, your recent orders |
| Food Order | `views/menu.php` | All meals; `?brand=` for one kitchen, `?cat=` for a category, 24 per page |
| Meal | `views/single_product.php` | Photo, price, prep time, delivery fee, notes for the kitchen, related meals |
| Search | `views/search_results.php` | Meals and kitchens matching the search |
| Bills | `views/cart.php` | The bill, with quantities, totals and a link to checkout |
| Checkout | `views/checkout.php` | Drop point, delivery instructions, order summary, Pay with Paystack |
| Payment result | `views/payment_success.php`, `views/payment_failed.php` | What happened after Paystack |
| Order History | `views/my_orders.php` | Your orders as cards |
| Order Tracking | `views/order_tracking.php` | Five-step tracker, timeline, refund status, Call and WhatsApp the kitchen, cancel |
| Favourites | `views/favorites.php` | Meals you've saved |
| Settings | `views/settings.php` | Profile, phone, drop point, password, sign out |
| Help & Support | `views/contact.php` | Message a kitchen or CampusBite; replies |
| Sign In / Sign Up | `views/login.php`, `views/register.php` | Accounts |
| Forgot / reset password | `views/forgot_password.php`, `views/reset_password.php` | Email link valid for 60 minutes, single use |
| CampusBite Pass | `views/pass.php` | Turned off (`PASS_ENABLED = false` in `core/core.php`) |

### Kitchen portal (`views/kitchen/`, kitchen staff only)

| Nav | File | What it shows |
|---|---|---|
| Dashboard | `kitchen_dashboard.php` | Sales, commission, order queue, best sellers, how you get paid |
| Orders | `kitchen_orders.php` | In progress tickets; `?view=all` for the searchable list of all orders |
| Menu | `kitchen_menu.php` | Add, edit, sold out and delete meals |
| Customers | `kitchen_customers.php` | Customers of this kitchen; `?id=` for one customer |
| Messages | `kitchen_messages.php` | Customer messages and replies |
| Kitchen info | `kitchen_info.php` | Location, phone, open or paused, payout account |

### Admin (`views/admin/`, super admin only)

| Nav | File | What it shows |
|---|---|---|
| Dashboard | `admin_dashboard.php` | Platform sales and earnings, open messages, every kitchen at a glance |
| Kitchens | `brand.php` | Kitchen list, add a kitchen, commission and status (`?edit_id=`) |
| | `kitchen.php?id=` | One kitchen, read-only: money, payout account, categories, menu, messages, staff |
| | `category.php` | Add, edit and delete meal categories (no nav link) |
| Money | `money.php` | Money report by date range, CSV (`?export=csv`), payout accounts, payment check |
| Users | `users.php` | Kitchen staff logins: add or remove access |
| Support | `messages.php` | All support messages and replies |

## Project structure

The code follows the same MVC layout as the course labs. In the code, **brands are kitchens** and **products are meals**.

| Folder | What's in it |
|---|---|
| `core/` | Session and role checks (`core.php`), PDO base class (`db_class.php`), Paystack helpers (`paystack.php`), email (`mailer.php`), config files |
| `classes/` | Models that run the SQL: `CustomerClass`, `ProductClass` (kitchens, categories, meals), `CartClass`, `OrderClass` |
| `controllers/` | Form rules and logic around the models, plus `OrderEmails` and `SupportController` |
| `actions/` | Form handlers. They check input, save, set a success or error message, then redirect |
| `views/` | Pages. Shared parts (header, nav, order panel, cards, toasts) are in `views/layout/` |
| `css/` | `campusbite.css` (theme), `admin.css` (tables and forms), `auth.css`, `style.css` |
| `js/` | `main.js` (toasts, confirmations, price updates, table scroll hints), `validate.js` (sign-up checks) |
| `database/` | `campusbite.sql` (schema and seed) and `migrations/` |
| `images/products/` | Meal photos: the seed meals' Unsplash photos (credits in `CREDITS.md` there) and kitchens' uploads |
| `docs/` | Roadmap |

More detail for contributors:

- [`AGENTS.md`](AGENTS.md): how each feature works, sprint history, rules for pages and actions
- [`CONTRIBUTING.md`](CONTRIBUTING.md): branches and pull requests
- [`docs/ROADMAP.md`](docs/ROADMAP.md): outstanding work

## Security

- **Forms:** every form carries a CSRF token. Anything that changes data accepts POST only. Delete and remove buttons ask for confirmation.
- **Database:** all SQL uses PDO prepared statements, and all output is escaped with `htmlspecialchars`.
- **Passwords:** hashed. Sign-up requires 8+ characters with an uppercase letter, a lowercase letter, a number and a symbol. The session ID is renewed at sign-in.
- **Password resets:** each link is stored only as a SHA-256 hash, works once, and expires after 60 minutes. Requests are rate-limited, and the reply never reveals whether an email has an account.
- **Payments:**
  - Each payment is checked with Paystack's verify API before an order is created.
  - The amount must match the cart snapshot exactly.
  - A payment reference can only ever create one order.
- **Uploads:** meal photos are checked by their contents, not their file name. Only JPG, PNG, WebP or GIF up to 2 MB are accepted, and each is saved under a random name.
- **Access:** every page checks the user's role before showing anything. Kitchen staff only see their own kitchen's orders, customers and meals.

## Known limits and open questions

- **Refunds on split payments.** Paystack's support articles say refunds come from the main account's balance. CampusBite takes the kitchen's share back from the kitchen's next orders. A kitchen that stops taking orders keeps what it owes until it is settled outside the site.
- **Older orders.** Orders paid before split payments (Sprint 22) were collected by CampusBite. Any balance owed to a kitchen from them has to be settled outside the site.
- **No Paystack webhook yet.** A refund's status updates when someone opens the order page.
- **Emails are sent while the page waits.** A slow mail server makes buttons slower, but never undoes a change.
- **Delivery fee.** It is one fee per order, by the kitchen's zone. The original theme charged per kitchen.
- **Bootstrap.** Bootstrap is still loaded for a few older pages.
