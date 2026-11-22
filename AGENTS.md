# CampusBite: guide for coding agents

CampusBite is a campus food ordering site for Ashesi University and Berekuso kitchens, built for an e-commerce course. The team is translating a Shopify theme it designed into this plain PHP project, one sprint at a time. This file records how the code is organised, what is finished and what comes next. Update the progress section when a sprint lands.

## How to work here

- **Build in sprints.** The sprints follow the course labs in the `ecomlab` project (Login and Register, then Brand and Categories, then Product Management, and so on). Finish one sprint, test it, then stop so a person can review it.
- **Never commit or push unless asked.** The owner commits and pushes each sprint separately, never several at once.
- **Translate only the custom theme files.** The theme lives outside this repo, in a local copy at `~/Downloads/shopify-CampusBite`. Only the `cb-*` and `campusbite*` files there are CampusBite's own; the rest is the stock Shopify Horizon theme and should be ignored.
- **No gradients.** The palette is Jet Black `#0F0F0F`, CampusBite Red `#E3001B` (hover `#C10017`, soft tint `#FDE7E9`) and white. The owner switched from orange to red in Sprint 11. The CSS variables are still called `--ember`, `--ember-d` and `--ember-soft`.
- **Don't build a page-content editor** unless someone asks for one.
- **The super admin doesn't edit food.** Kitchens own their meals (add, edit, sold out, delete) in the kitchen portal. Admins see each kitchen's page and menu read-only. After adding a kitchen, the admin can only change its commission and Active or Paused status (`update_brand_action.php`, `ProductController::checkBrandTerms()`); the kitchen keeps its location and phone current from Kitchen info. When the admin pauses a kitchen, `brands.admin_paused` is set and the kitchen can't reopen itself from Kitchen info until the admin sets it Active; a kitchen can still pause and reopen itself otherwise. Each kitchen adds its own payout account (Kitchen info); the admin only sees whether it is set up. Admins also run money, kitchen staff logins (the Users tab) and support. **The super admin doesn't manage customers or orders** (Sprint 21): there is no admin customer list, customer page, suspension or order list. Each kitchen sees its own customers and all its orders in the kitchen portal. The admin can still open a single order page from a support message or the Money page's payment check, and cancel and refund it there.

## Project structure (MVC, same as `ecomlab`)

| Folder | Purpose |
|---|---|
| `core/` | `mailer.php` (email), `paystack.php` (payments, refunds, kitchen subaccounts and splits), `core.php` (session, `is_logged_in`, `is_admin`, `require_login`, `require_admin`, `log_in_customer`), `db_class.php` (PDO `Database` base class), `db_cred.php` (credentials) |
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
- Admin nav (Sprint 21): Dashboard, Kitchens, Money, Users, Support, View shop. The Kitchens tab stays lit on kitchen and category pages.
- Kitchen nav: Dashboard, Orders, Menu, Customers, Messages, Kitchen info.

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
| 12 | Paystack payment, payment result pages and verified-payment check on the Money page | Done, commit `68f4abe` |
| 13 | Admin kitchen page (money, read-only menu, categories, orders, messages, staff); admin no longer edits meals; shorter admin nav | Done, commit `189985f` |
| 14 | Admin oversight fixes, order search and filters, admin pause | Done, commit `3052da1` |
| 15 | Cancelling orders with Paystack refunds (customer cancel, kitchen reject, admin cancel) | Done, commit `db3f7c8` |
| 16 | Kitchen payouts (record payouts, balances, payout history for admin and kitchen) | Done; replaced by split payments in Sprint 22 |
| 17 | Email setup (SMTP or saved copies) and forgot password | Done |
| 18 | Replying to support messages inside the site (customer, kitchen, admin) | Done |
| 19 | Order emails: receipt, kitchen new-order alert, status updates, cancellations | Done |
| 20 | Customer management: account search, customer page, suspend and reinstate | Done, commit `b7b433b`; removed again in Sprint 21 |
| 21 | Users tab (kitchen staff logins) for the admin; Customers page and searchable All orders in the kitchen portal; admin customer pages, suspension and order lists removed; admin dashboard trimmed | Done, commit `d5713c4` |
| 22 | Paystack split payments: each kitchen's payout account is a Paystack subaccount and is paid directly; hand-recorded payouts (Sprint 16) removed | Done |
| 23 | Refunds on split payments taken back from the kitchen's next orders; meal placeholders use the category icon and kitchens are nudged to add photos; live new-order alert in the kitchen portal | Done |
| 24 | More seed meals: 31 new ones (about five per kitchen, each kitchen with its own take on shawarma, pastries, cakes, soft drinks and smoothies), new categories Shawarma & Wraps, Desserts & Cakes and Soft Drinks, "Grills & Shawarma" renamed "Grills & Suya", Unsplash photos for every seed meal | Done |
| 25 | Database table prefixing: added `cb_` prefix to all 14 tables and foreign keys to safely coexist with ecomlab in a shared database on the live server | Done |

### What's done

**Shared layout.** Three-column theme layout on every page, with kitchens and the cart coming from the database. Pages not yet translated still use Bootstrap 5, which stays loaded until all of them are converted.

**Login and Register.** Restyled forms. Server and browser checks cover name, email, password (8+ characters with an uppercase letter, a lowercase letter, a number and a symbol) and phone. Duplicate emails, including different capitalisation, are rejected. New accounts are signed in straight away, and the session ID is renewed at login. `?redirect=checkout` returns the user to checkout, and any other redirect value is ignored. Drop points are set at checkout or updated in Settings.

**Kitchens and Categories.** Admins add kitchens (name, zone, location, phone, commission) and later change only commission and status; a kitchen with no meals can be deleted. Categories can be added, edited and deleted at `views/admin/category.php`, which has no nav link since Sprint 13. Kitchens have a zone, a location, a whole-number commission percentage and an Active or Paused status. A paused kitchen is hidden from the sidebar, meal lists and search. Duplicate names are refused. A kitchen or category that still has meals can't be deleted, because the database's `ON DELETE CASCADE` would remove its whole menu. Category icons come from `ProductController::CATEGORY_ICONS`.

**Product Management.** Kitchens add, edit and delete their own meals on `views/kitchen/kitchen_menu.php` (`kitchen_save_meal_action.php`, `kitchen_toggle_meal_action.php`, `delete_product_action.php`; each checks the meal belongs to the signed-in kitchen). The admin meal page from Sprint 3 was removed in Sprint 13. `ProductController::checkProductForm()` checks title (unique within its kitchen), price (above 0, up to GH₵ 10,000), an existing kitchen and category, prep time (1 to 240 minutes), description and keyword lengths and the image. Images must be JPG, PNG, WebP or GIF (checked by file contents, not name) and up to 2 MB, saved under a random name in `images/products/`; replacing or deleting a meal removes the old file. Availability is Available or Sold out (`is_available`); sold-out meals and meals of paused kitchens are hidden from shoppers. A meal that appears in past orders can't be deleted (the `orderdetails` foreign key has no cascade), so set it to Sold out instead. A kitchen only sees Delete on meals nobody has ordered.

**Home and listings.** `index.php` loads `views/home.php`: greeting, banner, kitchens, categories (only those with orderable meals), six popular meals and the signed-in customer's last three orders. `views/menu.php` is the listing page: no parameters shows all kitchens and meals, `?brand=ID` a kitchen page, `?cat=ID` a category, and the two combine, with category chips and 24 meals per page (`?page=`). Only available meals in active kitchens are listed, and a paused or unknown kitchen redirects to the full menu with a message. Meal and kitchen cards are `views/layout/dish_card.php` and `kitchen_card.php`. The sidebar kitchen links now go to `menu.php?brand=ID`, and the sidebar has a Food Order link. Kitchen cards show the fee from `ProductController::DELIVERY_FEES` (GH₵ 4 Zone A, GH₵ 7 Zone B).

**Product page and search.** `views/single_product.php` translated from `cb-product.liquid` with prep time, delivery fee, special instructions note, and related dishes. `views/search_results.php` translated from `cb-search.liquid` using `dish_card.php`. Deprecated `product_card.php` deleted.

**Cart (Bills).** `views/cart.php` translated from `cb-cart.liquid`, synchronized with `panel.php`. Quantity adjustments, empty cart states, and item removal.

**Checkout and Order History.** `views/checkout.php`: drop point (a list of popular spots fills the text field), instructions, a Paystack payment step, and the order summary beside it. The phone number is shown read-only with a link to Settings, because checkout doesn't save it. `views/my_orders.php` shows each order as a dashed card. `views/order_tracking.php` has a five-step tracker (`Placed`, `Preparing`, `Ready`, `In Transit`, `Delivered`; vertical on phones), the timeline, delivery details and what was ordered; admins and the order's kitchen also get the next-step button. Only `views/pass.php` still uses Bootstrap markup, and the Pass is switched off. Kitchens see every one of their orders under All orders on `views/kitchen/kitchen_orders.php?view=all` (Sprint 21; it was the admin's Orders page before): search by invoice number or customer name, email or phone (`?q=`), order dates (`?from=&to=`) and status (`?status=`, chips with counts), 25 per page (`?page=`), via `OrderController::searchKitchenOrders()`, which always limits the list to the signed-in kitchen. Customer names link to the kitchen's Customers page. Only the order's own kitchen can change an order's status (`update_order_status_action.php` requires a kitchen account).

**Kitchens deliver their own orders.** The courier role (3) was dropped. In the kitchen portal (`views/kitchen/kitchen_orders.php`) each ticket has one button for its next stage (`OrderController::NEXT_STEP`): Start preparing, Mark ready, Out for delivery, Mark delivered. Kitchens keep the delivery fee.

**Money report.** `views/admin/money.php` (admin only) takes a date range (`?from=&to=`, Y-m-d, default this month) and shows, per kitchen: delivered orders, meal sales, CampusBite's commission, delivery fees and the kitchen's share (sales − commission + delivery fees, which Paystack pays the kitchen directly since Sprint 22), plus orders still in progress, which aren't counted yet. `?export=csv` downloads the same table. Each order stores the kitchen's commission rate when it's placed (`orders.commission_rate`), so changing a rate doesn't change past payouts. A payment check lists orders with no payment, more than one payment, or a payment that doesn't match the order total. The check also shows how much Paystack confirmed and flags payments it didn't (orders from before Sprint 12 were simulated).

**Split payments (Sprint 22, replaces Sprint 16's recorded payouts).** CampusBite no longer collects kitchens' money and pays it out by hand. Each kitchen adds a payout account on `views/kitchen/kitchen_info.php#payout` (one list of Mobile Money networks and Ghana banks from Paystack's `/bank` API, `paystack_payout_options()`; account number; name on the account) through `actions/kitchen_save_payout_action.php` → `ProductController::savePayoutAccount()`, which creates or updates the kitchen's Paystack subaccount (`paystack_save_subaccount()`) and stores `brands.payout_*` and `brands.paystack_subaccount`; Paystack fills in the account name it resolves, and an account Paystack refuses leaves the old one in place. A kitchen without a subaccount can't be paid: the bill and checkout show "can't take online payments yet" and `checkout_process_action.php` refuses it. At checkout `paystack_split_for()` works out CampusBite's commission on the meals (sent as a flat `transaction_charge` with the kitchen's `subaccount`) and the kitchen's share (meals − commission + delivery fee); Paystack's fee comes out of CampusBite's share (`bearer` account), or the kitchen's when the commission is 0. The rate and shares travel in the payment metadata; the callback saves the order with that rate and stores `payment.subaccount_code`, `kitchen_share` and `campusbite_share`. The Money page now shows "Kitchen's share" (paid by Paystack) and "How kitchens get paid" (each kitchen's payout account, or "Not set up"); the admin kitchen page and the kitchen dashboard show the payout account or a warning. The `payouts` table, `PayoutClass`, `PayoutController` and `record_payout_action.php` are gone. A fresh seed has no subaccounts, so each kitchen must add its account (Paystack test keys accept test MoMo numbers such as MTN 0551234987) before it can take orders.

**Refund recovery, meal icons and new-order alerts (Sprint 23).** Paystack refunds come out of CampusBite's main balance even for split payments, so the kitchen's share of a refunded split payment is taken back from its next orders. `OrderController::getKitchenRefundBalance()` returns `refunded` (the `kitchen_share` of split payments whose refund isn't `failed` or `not_needed`), `recovered` (the `payment.refund_recovery` of the kitchen's orders that weren't refunded themselves) and `owed`. At checkout `paystack_split_for(..., $refundsOwed)` raises the flat `transaction_charge` by up to the order's whole kitchen share (or, if too much was taken back, lowers it by up to the commission); the amount travels in the metadata and is stored in `payment.refund_recovery`. Kitchens see what's still owed on the dashboard and Kitchen info; the admin on the Money page ("Refunds to take back") and the kitchen page; the kitchen's reject confirmation says its share is taken back. The 39 seed meals (Sprint 24 added 31, across nine categories) have free Unsplash photos in `images/products/` (credits in `images/products/CREDITS.md`). Meals with no photo show their category's icon (`meal_icon()` in `core/core.php`; meal, cart, favourite and order queries select `cat_icon`), and the kitchen menu marks meals with "No photo". In the kitchen portal, the Orders nav link carries a count of Placed orders; `main.js` asks `actions/kitchen_order_poll.php` (GET, kitchen only, JSON `{placed, latest}`) every 20 seconds while the tab is visible, and on a new order updates the count and tab title, plays a Web Audio chime (after the first click or key, as browsers require), announces it in a polite live region and shows a toast; `kitchen_orders.php` (In progress) reloads itself unless a field has focus or a fold is open.

**Email and forgot password (Sprint 17).** `core/mailer.php` sends email: `send_email($to, $name, $subject, $html, $text)` with `email_html()` / `email_text()` for CampusBite's layout (white card, red pill button, no images) and `app_url($path)` for links. Settings go in `core/mail_config.php` (gitignored; copy `core/mail_config.example.php`). `MAIL_MODE` 'smtp' sends through any SMTP server (Gmail with an app password: smtp.gmail.com, 587, 'tls'; 'none' for a local test server) and needs `APP_URL`, so reset links never come from the request's Host header. With no config, or 'log', nothing is sent: each email is saved as an HTML file in `storage/mail/` (gitignored; the mailer writes `storage/.htaccess` so Apache refuses to serve it). A failed send is logged and never stops the page. The SMTP client is built in (STARTTLS or SSL, AUTH LOGIN), so there's no Composer dependency.

Forgot password: "Forgot password?" on the sign-in page opens `views/forgot_password.php` → `actions/forgot_password_action.php`, which always gives the same answer, so it can't reveal which emails have accounts. `CustomerController::requestPasswordReset()` makes a 64-hex token, stores only its SHA-256 in `password_resets`, cancels older links and emails the link (works once, for 60 minutes). Limits: 3 links per account and 10 per IP address per hour; extra requests are quietly ignored. `views/reset_password.php?token=` (sent with `Referrer-Policy: no-referrer`) → `actions/reset_password_action.php` checks the password rules, claims the link with a conditional UPDATE so it works once, saves the password, closes every other link for the account, emails a "your password was changed" notice and signs the browser out. In log mode, when the site is opened from this computer (`mail_shows_links()`), the check-your-email page also shows the link so it can be tested without a mail server.

**Support replies (Sprint 18).** Every support message is a conversation. Replies live in `support_replies` (author, role `customer`/`kitchen`/`admin`, text). `views/layout/support_thread.php` draws the replies and a folded "Reply" form under each message on the customer's Help page (`views/contact.php`), the kitchen's Messages inbox and the admin Support page; all post to `actions/reply_message_action.php`. `SupportController::roleFor($msg)` decides who takes part: the admin in any message, a kitchen in messages sent to it or about its orders, a customer in their own (the status action uses the same check). A customer's reply reopens a resolved message; staff can tick "Mark resolved after sending". When a kitchen or admin replies, the customer gets an email with the reply and a link back. Inboxes sort open messages first, then by latest activity, and show "Needs a reply" / "Waiting for customer" (`SupportController::waitingOn()`); the customer sees "New reply". The admin can join kitchen conversations, which stay the kitchen's to answer.

**Order emails (Sprint 19).** `controllers/OrderEmails.php` sends, after each change is saved: the customer's receipt (meals, delivery, total, payment reference, drop point) and a "New order" alert to each of the kitchen's staff logins when the Paystack callback creates the order; a status email when the kitchen moves it to Preparing, In Transit or Delivered (Ready is skipped); and a cancellation email to the customer with the refund amount, plus a "stop preparing it" email to the kitchen when the customer or admin cancelled. Emails are sent during the request, so a slow SMTP server makes the button slower but never blocks or undoes the change.

**Users and kitchen customers (Sprint 21).** Sprint 20's admin customer pages and suspension were removed: the super admin looks after kitchens and money, not customers or orders. `views/admin/users.php` (nav "Users") lists kitchen staff logins grouped by kitchen, with "No login yet" and an Add login link (`?kitchen=ID#new-login` picks the kitchen in the form) for kitchens without one, and Remove access per login. Creating and removing kitchen logins moved there from the Kitchens page (`create_kitchen_account_action.php`, `remove_kitchen_access_action.php` now return to Users). The kitchen portal has `views/kitchen/kitchen_customers.php` (nav "Customers"): everyone who has ordered from that kitchen, most recent first, with headline numbers (customers, came back for a second order, new in the last 30 days), search by name, email or phone (`?q=`), 25 per page, and per customer their orders with the kitchen (cancelled counted apart), what they've paid it, last order and usual drop point (`OrderController::getKitchenCustomers()`). `?id=` opens one customer, only if they've ordered from that kitchen: contact details with Call and WhatsApp buttons, numbers and their orders from the kitchen. Orders also moved to the kitchens: the admin Orders page (`views/admin/orders.php`), the dashboard's "Orders right now" boxes and Latest orders, and the admin kitchen page's Recent orders were removed; the kitchen's Orders page has In progress (tickets) and All orders (the searchable table; `?view=done` still opens it). The admin dashboard now shows the money boxes, Needs attention (only open support messages) and the Kitchens table. Staff who open a shopper-only page go to their orders (kitchens) or the dashboard (admins), via `staff_orders_path()`. `sync_account_session()` in `core/core.php` still re-reads the account on every request, so a deleted account is signed out and kitchen access changes apply at once.

**Cancelling and refunds (Sprint 15).** Orders can end as `Cancelled` (kept out of `OrderController::STATUSES`, so no status menu can set it). `OrderController::CANCELLABLE_FROM` decides who can cancel up to which stage: the customer while it's Placed (reason optional), the order's kitchen while it's Placed or Preparing (a reason from `KITCHEN_REJECT_REASONS` or its own words), and the admin until it's delivered (reason required). All go through `actions/cancel_order_action.php` and `OrderController::cancelOrder()`: the order is claimed with a conditional UPDATE (so it can't be cancelled twice or moved on meanwhile), then Paystack's `/refund` is called for the full amount paid; if Paystack refuses, the order is put back and nothing changes. Payments from before Paystack were simulated, so those orders are cancelled with a `not_needed` refund. Each cancelled order has one row in `refunds` (amount, Paystack refund id and status) plus `orders.cancelled_at`, `cancelled_by` and `cancel_reason`, and a timeline entry. The order page shows the refund status and asks Paystack for an update at most once a minute while it's pending. Cancelled orders count nowhere in sales, commission, payouts, best sellers or "in progress"; the Money page's payment check shows what was refunded and flags failed or missing refunds. There's no Paystack webhook yet, so refund status only updates when someone opens the order.

**Admin kitchen page (Sprint 13).** `views/admin/kitchen.php?id=` opens from the Kitchens list, the admin dashboard and the admin preview of a meal. It is view-only: the kitchen's details, its money for a date range (This month, Last 7 days, All time; the same figures as its row on the Money page, from `OrderController::getMoneyReport()`), its categories with meal counts, its menu (view-only), recent orders, best sellers, support messages and staff logins.

**Paystack payment (Sprint 12).** `actions/checkout_process_action.php` checks the bill, then calls Paystack's `/transaction/initialize` (amount in pesewas, GHS, a `CB-` + 16 hex reference, and the cart snapshot in `metadata`) and sends the shopper to Paystack. Paystack returns them to `actions/paystack_callback_action.php`, which calls `/transaction/verify` and only creates the order when the payment succeeded, belongs to the signed-in customer, is in GHS and matches the snapshot total exactly. Order, payment (verified amount, channel such as "Paystack - Mobile Money", `verified_at`) and emptying the bill happen in one database transaction. `payment.transaction_reference` is unique, so reloading the callback can't create a second order. Cancelled, declined, pending or mismatched payments go to `views/payment_failed.php` and leave the bill as it was; success goes to `views/payment_success.php`. If the session ran out while on Paystack, the reference is kept and checked straight after sign-in. Helpers are in `core/paystack.php`. Keys go in `core/paystack_config.php` (gitignored; copy `core/paystack_config.example.php` and use test keys). Paystack rejects reserved email domains such as `.test`, so test customers need an address like `name@example.com`.

**Favorites, Settings, Messages, and Security.** Favorites heart toggle (`views/favorites.php`), customer account settings and password updates (`views/settings.php`), customer contact and admin messages (`views/contact.php`, `views/admin/messages.php`), and sitewide CSRF token protection.

**Kitchen direct support & messaging.** Support messages on `views/contact.php` now route directly to the kitchen handling the order (or a selected kitchen). Kitchen staff have a **Messages** inbox in their portal (`views/kitchen/kitchen_messages.php`) where they can view customer inquiries, see customer contact details, call or WhatsApp the customer directly, and mark messages resolved. Customers also get instant 1-click **Call** and **WhatsApp** contact buttons for their kitchen on `views/order_tracking.php` and `views/contact.php`. Kitchen phone numbers (`brands.brand_phone`) can be updated from both `views/kitchen/kitchen_info.php` and `views/admin/brand.php`.

### Database migrations

Existing databases must run each file in `database/migrations/` once, in date order: `2026-11-08_phase1_phase2.sql`, `2026-11-09_kitchen_portal.sql`, `2026-11-17_kitchen_delivery.sql`, `2026-11-18_kitchen_support.sql` (adds `brands.brand_phone`, routes `support_messages` to `brand_id`), `2026-11-20_paystack.sql` (adds `payment.verified_at` and makes `transaction_reference` unique), `2026-11-20_admin_pause.sql` (adds `brands.admin_paused`), `2026-11-21_cancel_refund.sql` (adds the cancel columns on `orders` and the `refunds` table), `2026-11-21_kitchen_payouts.sql` (adds the `payouts` table), `2026-11-22_password_resets.sql` (adds the `password_resets` table), `2026-11-22_support_replies.sql` (adds the `support_replies` table), `2026-11-22_customer_admin.sql` (adds suspension columns and `created_at` to `customer`), `2026-11-23_remove_customer_admin.sql` (drops those four columns again, Sprint 21), `2026-11-24_paystack_split.sql` (adds the payout account columns on `brands` and the split columns on `payment`, drops `payouts`), `2026-11-25_refund_recovery.sql` (adds `payment.refund_recovery`), `2026-11-26_more_meals.sql` (adds the Sprint 24 categories and meals by name; safe to run twice), then `2026-11-22_cb_prefix.sql` (renames all tables to `cb_*` so CampusBite can share a database with other apps like ecomlab). Fresh databases imported from `database/campusbite.sql` already include all tables prefixed with `cb_`, and it can now be re-imported over itself (it turns foreign key checks off while it rebuilds).

### Open items & next steps

- **Refund webhooks:** refund status is refreshed when the order page is opened; a Paystack webhook would keep it current without that (needs a public URL).
- **Refunds owed by a kitchen that stops trading:** recovery only happens through new orders, so a kitchen that leaves with a balance owed has to settle it outside the site.
- **Orders from before split payments:** they were collected by CampusBite; any kitchen balance left from Sprint 16 has to be settled outside the site.
- **Settlement schedule:** Paystack settles subaccounts on the main account's schedule. Kitchens can't see their Paystack settlements in CampusBite yet.
- **Delivery fee policy:** The theme charges per kitchen (GH₵ 4 on campus, GH₵ 7 Berekuso), while `CartController::calculateDeliveryFee()` currently applies one flat fee per order. The team needs to settle on the final fee rule.
- **Email preferences:** customers can't turn off status emails yet, and kitchens aren't emailed when a customer replies to a message.
- **Sending emails in the background:** emails go out while the page waits; a queue would keep buttons fast with a slow mail server.
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
- **Demo accounts** (seeded by `database/campusbite.sql` since Sprint 22, so a fresh import has them):
  - **Admin:** `admin@campusbite.com` / `Admin123!` (role 1)
  - **Kitchen:** `kitchen@campusbite.com` / `Kitchen123!` (role 4, Munchees Grills `brand_id = 3`)
- **Making an admin:** a new account has `user_role` 2 (customer). Roles: 1 admin, 2 customer, 4 kitchen (linked to a kitchen through `brand_id`). Role 3, courier, was retired. Promote it with `UPDATE customer SET user_role = 1 WHERE customer_email = '...';`, then sign out and back in.

## Known issues

- **Credentials on GitHub:** `core/db_cred.php` was purged from git history using `git filter-repo`, untracked, and added to `.gitignore`. Teammates must create their own `core/db_cred.php` locally. Any passwords previously committed should still be changed on the database server.
- **Seed icon:** the seed SQL now gives "Rice & Jollof Bowls" a valid icon, but databases imported earlier still store `bi-bowl`, which doesn't exist. Change it on the Categories page.
