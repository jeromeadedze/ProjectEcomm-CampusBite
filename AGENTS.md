# CampusBite: guide for coding agents

CampusBite is a campus food ordering site for Ashesi University and Berekuso kitchens, built for an e-commerce course. The team is translating a Shopify theme it designed into this plain PHP project, one sprint at a time. This file records how the code is organised, what is finished and what comes next. Update the progress section when a sprint lands.

## How to work here

- **Build in sprints.** The sprints follow the course labs in the `ecomlab` project (Login and Register, then Brand and Categories, then Product Management, and so on). Finish one sprint, test it, then stop so a person can review it.
- **Never commit or push unless asked.** The owner commits and pushes each sprint separately, never several at once.
- **Translate only the custom theme files.** The theme lives outside this repo, in a local copy at `~/Downloads/shopify-CampusBite`. Only the `cb-*` and `campusbite*` files there are CampusBite's own; the rest is the stock Shopify Horizon theme and should be ignored.
- **No gradients.** The palette is Jet Black `#0F0F0F`, CampusBite Red `#E3001B` (hover `#C10017`, soft tint `#FDE7E9`) and white. The owner switched from orange to red in Sprint 11. The CSS variables are still called `--ember`, `--ember-d` and `--ember-soft`.
- **Don't build a page-content editor** unless someone asks for one.
- **The super admin doesn't edit food.** Kitchens own their meals (add, edit, sold out, delete) in the kitchen portal. Admins see each kitchen's page and menu read-only. After adding a kitchen, the admin can only change its commission and Active or Paused status (`update_brand_action.php`, `ProductController::checkBrandTerms()`); the kitchen keeps its location and phone current from Kitchen info. When the admin pauses a kitchen, `brands.admin_paused` is set and the kitchen can't reopen itself from Kitchen info until the admin sets it Active; a kitchen can still pause and reopen itself otherwise. Admins also run money, orders, customers and support.

## Project structure (MVC, same as `ecomlab`)

| Folder | Purpose |
|---|---|
| `core/` | `core.php` (session, `is_logged_in`, `is_admin`, `require_login`, `require_admin`, `log_in_customer`), `db_class.php` (PDO `Database` base class), `db_cred.php` (credentials) |
| `classes/` | Models extending `Database`: `CustomerClass`, `ProductClass` (kitchens, categories and meals), `CartClass`, `OrderClass` |
| `controllers/` | Wrap the models and hold form checks, such as `ProductController::checkBrandForm()` and `CustomerController::HOSTELS` |
| `actions/` | Form handlers. They check input, set `$_SESSION['success']` or `$_SESSION['error']`, then redirect |
| `views/` | Pages. Admin pages are in `views/admin/`, shared parts in `views/layout/` |
| `css/` | `campusbite.css` (theme layout), `style.css` (older global styles), `auth.css` (forms and messages), `admin.css` (admin tabs, cards and tables) |
| `js/` | `main.js` (messages, delete confirmations), `validate.js` (sign-up checks) |
| `database/campusbite.sql` | Schema and seed data |

Names in the code follow the course labs: **brands are kitchens** and **products are meals**.

### Layout

Every page includes `views/layout/header.php` and `views/layout/footer.php`. The look follows a "Foodly"-style reference the owner chose (Sprint 11): on wide screens the app sits in a rounded light-grey window on a black page.

- `nav.php` at the top: logo, pill links (shopper, admin or kitchen set, chosen by role), search, red Cart pill and avatar. On phones (under 860px) the shopper links become a floating tab bar at the bottom; admin and kitchen links scroll sideways under the bar.
- The page itself in `<main class="cb-main">`, a rounded panel. The header prints the page title from the `$pageTitles` map; a page that draws its own big heading sets `$hidePageTitle = true` (the product page does).
- `panel.php` on the right, once the cart has something: "My order", dashed meal cards with round − / + buttons (they post to `update_qty_action.php`, which now returns to the page they came from), delivery spot, totals and a red "Confirm order" button.
- Styling: meal and kitchen cards have dashed outlines, meal photos are round "plates", add buttons are black circles, buttons and chips are pills. The product page has a round photo, a large title, and a black "price | Add to order" pill whose price follows the quantity (`main.js`).
- `flash.php` shows one-time success and error messages as toasts.
- Admin nav (Sprint 13): Dashboard, Orders, Kitchens, Money, Customers, Support, View shop. The Kitchens tab stays lit on kitchen and category pages.

### Rules for pages and actions

- **Set the root path first.** A page in `views/admin/` must start with `define('ROOT_DIR', '../..');`. A page in `views/` relies on the header's default of `..`, and `index.php` defines `.`. Without the right value, stylesheet links and redirects break.
- **Check permissions before the header.** Call `require_admin()` or `require_login()` after requiring `core/core.php` and before including `header.php`. Otherwise the header shows and clears flash messages that belong to the next page.
- **Make every write a POST.** Deletes use a POST form with `data-confirm="..."`, and `main.js` asks before submitting. Actions that change data reject GET requests.
- **Validate on the server.** Keep the rules in the controller, and send what was typed back through the session (for example `$_SESSION['brand_form']`) so the form refills after an error.
- **Escape all output** with `htmlspecialchars`, and use PDO prepared statements for all SQL.

## Progress

| # | Sprint | Status |
|---|---|---|
| 0 | Shared layout: sidebar, top bar, order panel, theme CSS | Done, commit `99bf6e0` |
| 1 | Login and Register | Done, commit `99bf6e0` |
| 2 | Brand (Kitchens) and Categories admin | Done, commit `16ba405` |
| 3 | Product Management (admin menu items) | Done, commit `962f9c2` |
| 4 | Home page and kitchen and category listings | Done, commit `a9d2721` |
| 5 | Product page and search | Done, commit `bcf3c87` |
| 6 | Cart (Bills) | Done, commit `bcf3c87` |
| 7 | Checkout and Order History | Done, commit `bcf3c87` |
| 8 | Favorites, Settings and Messages | Done, commit `bcf3c87` |
| 9 | Kitchen portal | Done, commit `a0794d0` |
| 10 | Kitchens deliver (courier role dropped) and admin Money report | Done, commit `9406bac` |
| 11 | UI revamp (top pill nav, dashed cards, round plates, phone tab bar), checkout/orders without Bootstrap, and kitchen direct messaging | Done, commit `dce5c8b` |
| 12 | Paystack payment, payment result pages and verified-payment check on the Money page | Done |
| 13 | Admin kitchen page (money, read-only menu, categories, orders, messages, staff); admin no longer edits meals; shorter admin nav | Done |

### What's done

**Shared layout.** Three-column theme layout on every page, with kitchens and the cart coming from the database. Pages not yet translated still use Bootstrap 5, which stays loaded until all of them are converted.

**Login and Register.** Restyled forms. Server and browser checks cover name, email, password (8+ characters with an uppercase letter, a lowercase letter, a number and a symbol) and phone. Duplicate emails, including different capitalisation, are rejected. New accounts are signed in straight away, and the session ID is renewed at login. `?redirect=checkout` returns the user to checkout, and any other redirect value is ignored. Drop points are set at checkout or updated in Settings.

**Kitchens and Categories.** Admins add kitchens (name, zone, location, phone, commission) and later change only commission and status; a kitchen with no meals can be deleted. Categories can be added, edited and deleted at `views/admin/category.php`, which has no nav link since Sprint 13. Kitchens have a zone, a location, a whole-number commission percentage and an Active or Paused status. A paused kitchen is hidden from the sidebar, meal lists and search. Duplicate names are refused. A kitchen or category that still has meals can't be deleted, because the database's `ON DELETE CASCADE` would remove its whole menu. Category icons come from `ProductController::CATEGORY_ICONS`.

**Product Management.** Kitchens add, edit and delete their own meals on `views/kitchen/kitchen_menu.php` (`kitchen_save_meal_action.php`, `kitchen_toggle_meal_action.php`, `delete_product_action.php`; each checks the meal belongs to the signed-in kitchen). The admin meal page from Sprint 3 was removed in Sprint 13. `ProductController::checkProductForm()` checks title (unique within its kitchen), price (above 0, up to GH₵ 10,000), an existing kitchen and category, prep time (1 to 240 minutes), description and keyword lengths and the image. Images must be JPG, PNG, WebP or GIF (checked by file contents, not name) and up to 2 MB, saved under a random name in `images/products/`; replacing or deleting a meal removes the old file. Availability is Available or Sold out (`is_available`); sold-out meals and meals of paused kitchens are hidden from shoppers. A meal that appears in past orders can't be deleted (the `orderdetails` foreign key has no cascade), so set it to Sold out instead. A kitchen only sees Delete on meals nobody has ordered.

**Home and listings.** `index.php` loads `views/home.php`: greeting, banner, kitchens, categories (only those with orderable meals), six popular meals and the signed-in customer's last three orders. `views/menu.php` is the listing page: no parameters shows all kitchens and meals, `?brand=ID` a kitchen page, `?cat=ID` a category, and the two combine, with category chips and 24 meals per page (`?page=`). Only available meals in active kitchens are listed, and a paused or unknown kitchen redirects to the full menu with a message. Meal and kitchen cards are `views/layout/dish_card.php` and `kitchen_card.php`. The sidebar kitchen links now go to `menu.php?brand=ID`, and the sidebar has a Food Order link. Kitchen cards show the fee from `ProductController::DELIVERY_FEES` (GH₵ 4 Zone A, GH₵ 7 Zone B).

**Product page and search.** `views/single_product.php` translated from `cb-product.liquid` with prep time, delivery fee, special instructions note, and related dishes. `views/search_results.php` translated from `cb-search.liquid` using `dish_card.php`. Deprecated `product_card.php` deleted.

**Cart (Bills).** `views/cart.php` translated from `cb-cart.liquid`, synchronized with `panel.php`. Quantity adjustments, empty cart states, and item removal.

**Checkout and Order History.** `views/checkout.php`: drop point (a list of popular spots fills the text field), instructions, a Paystack payment step, and the order summary beside it. The phone number is shown read-only with a link to Settings, because checkout doesn't save it. `views/my_orders.php` shows each order as a dashed card. `views/order_tracking.php` has a five-step tracker (`Placed`, `Preparing`, `Ready`, `In Transit`, `Delivered`; vertical on phones), the timeline, delivery details and what was ordered; admins and the order's kitchen also get the next-step button. Only `views/pass.php` still uses Bootstrap markup, and the Pass is switched off. Admins see every order on `views/admin/orders.php`, view-only: search by invoice number or customer name, email or phone (`?q=`), filter by kitchen (`?brand=`), order dates (`?from=&to=`) and status (`?status=`, chips with counts), 25 per page (`?page=`), via `OrderController::searchOrdersForAdmin()`. The dashboard's order boxes link to the matching status. Only the order's own kitchen can change an order's status (`update_order_status_action.php` requires a kitchen account).

**Kitchens deliver their own orders.** The courier role (3) was dropped. In the kitchen portal (`views/kitchen/kitchen_orders.php`) each ticket has one button for its next stage (`OrderController::NEXT_STEP`): Start preparing, Mark ready, Out for delivery, Mark delivered. Kitchens keep the delivery fee.

**Money report.** `views/admin/money.php` (admin only) takes a date range (`?from=&to=`, Y-m-d, default this month) and shows, per kitchen: delivered orders, meal sales, CampusBite's commission, delivery fees and what the kitchen is owed (sales − commission + delivery fees), plus orders still in progress, which aren't owed yet. `?export=csv` downloads the same table. Each order stores the kitchen's commission rate when it's placed (`orders.commission_rate`), so changing a rate doesn't change past payouts. A payment check lists orders with no payment, more than one payment, or a payment that doesn't match the order total. The check also shows how much Paystack confirmed and flags payments it didn't (orders from before Sprint 12 were simulated).

**Admin kitchen page (Sprint 13).** `views/admin/kitchen.php?id=` opens from the Kitchens list, the admin dashboard and the admin preview of a meal. It is view-only: the kitchen's details, its money for a date range (This month, Last 7 days, All time; the same figures as its row on the Money page, from `OrderController::getMoneyReport()`), its categories with meal counts, its menu (view-only), recent orders, best sellers, support messages and staff logins.

**Paystack payment (Sprint 12).** `actions/checkout_process_action.php` checks the bill, then calls Paystack's `/transaction/initialize` (amount in pesewas, GHS, a `CB-` + 16 hex reference, and the cart snapshot in `metadata`) and sends the shopper to Paystack. Paystack returns them to `actions/paystack_callback_action.php`, which calls `/transaction/verify` and only creates the order when the payment succeeded, belongs to the signed-in customer, is in GHS and matches the snapshot total exactly. Order, payment (verified amount, channel such as "Paystack - Mobile Money", `verified_at`) and emptying the bill happen in one database transaction. `payment.transaction_reference` is unique, so reloading the callback can't create a second order. Cancelled, declined, pending or mismatched payments go to `views/payment_failed.php` and leave the bill as it was; success goes to `views/payment_success.php`. If the session ran out while on Paystack, the reference is kept and checked straight after sign-in. Helpers are in `core/paystack.php`. Keys go in `core/paystack_config.php` (gitignored; copy `core/paystack_config.example.php` and use test keys). Paystack rejects reserved email domains such as `.test`, so test customers need an address like `name@example.com`.

**Favorites, Settings, Messages, and Security.** Favorites heart toggle (`views/favorites.php`), customer account settings and password updates (`views/settings.php`), customer contact and admin messages (`views/contact.php`, `views/admin/messages.php`), an accounts list (`views/customers.php`), and sitewide CSRF token protection.

**Kitchen direct support & messaging.** Support messages on `views/contact.php` now route directly to the kitchen handling the order (or a selected kitchen). Kitchen staff have a **Messages** inbox in their portal (`views/kitchen/kitchen_messages.php`) where they can view customer inquiries, see customer contact details, call or WhatsApp the customer directly, and mark messages resolved. Customers also get instant 1-click **Call** and **WhatsApp** contact buttons for their kitchen on `views/order_tracking.php` and `views/contact.php`. Kitchen phone numbers (`brands.brand_phone`) can be updated from both `views/kitchen/kitchen_info.php` and `views/admin/brand.php`.

### Database migrations

Existing databases must run each file in `database/migrations/` once, in date order: `2026-11-08_phase1_phase2.sql`, `2026-11-09_kitchen_portal.sql`, `2026-11-17_kitchen_delivery.sql`, `2026-11-18_kitchen_support.sql` (adds `brands.brand_phone`, routes `support_messages` to `brand_id`), `2026-11-20_paystack.sql` (adds `payment.verified_at` and makes `transaction_reference` unique), then `2026-11-20_admin_pause.sql` (adds `brands.admin_paused`). Fresh databases imported from `database/campusbite.sql` already include all tables.

### Open items & next steps

- **Refunds:** cancelled or rejected orders aren't refunded through Paystack yet (next sprint).
- **Delivery fee policy:** The theme charges per kitchen (GH₵ 4 on campus, GH₵ 7 Berekuso), while `CartController::calculateDeliveryFee()` currently applies one flat fee per order. The team needs to settle on the final fee rule.
- **Order receipts & notifications:** Email receipts (or SMS notifications) for placed orders and status updates.
- **Store credit & coupons:** The original Shopify theme's order panel displayed coupon code input and store credit balance, but the database currently does not model them.

### Theme sections and their PHP pages

| Theme file (`sections/`) | PHP page |
|---|---|
| `campusbite-dashboard.liquid` | `index.php` / `views/home.php` |
| `cb-collection.liquid` | `views/menu.php` (kitchen and category listings) |
| `cb-product.liquid` | `views/single_product.php` |
| `cb-search.liquid` | `views/search_results.php` |
| `cb-cart.liquid` | `views/cart.php` (Bills) |
| `cb-orders.liquid` | `views/my_orders.php` |
| `cb-favorites.liquid` | `views/favorites.php` |
| `cb-settings.liquid` | `views/settings.php` |
| `cb-contact.liquid` | `views/contact.php` |
| `cb-admin.liquid` | admin pages |

Shared theme parts live in `snippets/`, for example `cb-sidebar`, `cb-panel`, `cb-dish-card` and `cb-vendor-card`. The theme's CSS is in `assets/campusbite*.css`.

## Database and testing

- **Never import `database/campusbite.sql` into a database that holds real data.** It starts with `DROP TABLE` statements.
- **This database is CampusBite's alone.** Locally, it uses `ecommerce_2026A_jerome_adedze` on XAMPP MariaDB (port 3306). The `ecomlab` project used to share it but now uses its own `shoppn` database.
- **Test against a throwaway database.** Start a separate MySQL on another port (3307 was used), import the SQL there, and point PHP at it with `-d auto_prepend_file` defining the `DB_*` constants. Check `SELECT @@port` before writing anything.
- **Run the site** with `php -S localhost:8000` from the repo root.
- **Demo accounts:**
  - **Admin:** `admin@campusbite.com` / `Admin123!` (role 1)
  - **Kitchen:** `kitchen@campusbite.com` / `Kitchen123!` (role 4, Munchees Grills `brand_id = 3`)
- **Making an admin:** a new account has `user_role` 2 (customer). Roles: 1 admin, 2 customer, 4 kitchen (linked to a kitchen through `brand_id`). Role 3, courier, was retired. Promote it with `UPDATE customer SET user_role = 1 WHERE customer_email = '...';`, then sign out and back in.

## Known issues

- **Credentials on GitHub:** `core/db_cred.php` was purged from git history using `git filter-repo`, untracked, and added to `.gitignore`. Teammates must create their own `core/db_cred.php` locally. Any passwords previously committed should still be changed on the database server.
- **Seed icon:** the seed SQL now gives "Rice & Jollof Bowls" a valid icon, but databases imported earlier still store `bi-bowl`, which doesn't exist. Change it on the Categories page.
