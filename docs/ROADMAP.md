# CampusBite roadmap: outstanding work

Written on Nov 9, from the code on `main` (`bcf3c87`). Add a name in the **Owner** column when you take a task. Each task goes on the branch named in its section; see [CONTRIBUTING.md](../CONTRIBUTING.md) for how to work with branches.

## What already works

Customers can sign up, sign in, browse kitchens and categories, search, open a meal, add it to the bill and check out. They can follow an order through its five stages, save favourites, change their settings and message support. Admins manage kitchens, categories, meals, orders, couriers, customer roles and support messages. CSRF tokens protect every form.

## `feature/db`: database and diagrams

| # | Task | Done when | Size | Owner |
|---|---|---|---|---|
| DB1 | **ERD.** Draw the entity-relationship diagram from `database/campusbite.sql`. | Every table, key and relationship is drawn, and the diagram is saved in `docs/`. | M | |
| DB2 | **UML diagrams.** Draw a class diagram of `classes/` and `controllers/`, plus a use-case diagram for customer, admin and courier. | Both are saved in `docs/` and match the code. | M | |
| DB3 | **Seed images.** None of the 13 images the seed data refers to are in `images/products/`, so seeded meals and kitchens show broken pictures. | Every seeded meal and kitchen shows a photo after a fresh import. | S | |
| DB4 | **Settings template.** `core/db_cred.php` is no longer in git, so a new teammate can't set up the project. | `core/db_cred.example.php` holds placeholder values and the README says to copy it. | S | |
| DB5 | **Fresh import check.** Confirm `campusbite.sql` alone builds the full current schema, so new setups don't need the migration file. | A fresh import on an empty database runs every page without errors. | S | |

## `feature/backend`: features and logic

| # | Task | Done when | Size | Owner |
|---|---|---|---|---|
| BE1 | **Real Paystack payment.** Checkout currently records a made-up transaction reference, so no money is taken. The ecomlab project has a working Paystack flow to reuse. | Checkout opens Paystack in test mode, the order is created only after Paystack confirms the payment, and failed or cancelled payments leave the bill unchanged. Keys stay out of git. | L | |
| BE2 | **Courier page.** Admins can assign couriers to orders, but couriers (role 3) have no page of their own. | A courier signs in, sees their assigned deliveries and can mark them In Transit or Delivered. | M | |
| BE3 | **Kitchen merchant role.** Role 4 exists in the database, but nothing uses it. | The team decides whether to build a kitchen view of incoming orders or drop the role. If it's built, a merchant sees and updates their own kitchen's orders. | M | |
| BE4 | **CampusBite Pass.** It's turned off with `PASS_ENABLED = false` in `core/core.php`. | The team decides whether to finish it (paid through Paystack, giving free Zone A delivery) or remove its pages. | M | |
| BE5 | **Delivery fee rule.** The site charges one fee per order (GH₵ 5 Zone A, GH₵ 10 Zone B), while the theme charged per kitchen. | The team picks one rule, and the cart, checkout, order panel and README all agree. | S | |
| BE6 | **Admin overview data.** The theme's admin page showed counts: active and paused kitchens, meals on sale and sold-out meals. Order counts would be a useful addition. | A controller method returns those counts for FE4. | S | |

## `feature/frontend`: pages and polish

| # | Task | Done when | Size | Owner |
|---|---|---|---|---|
| FE1 | **Remove Bootstrap.** Every page still loads Bootstrap from `views/layout/header.php`, and some older markup still relies on it. | Each page uses the theme classes only, and the Bootstrap CSS and JavaScript links are removed without breaking any page. Bootstrap Icons can stay. | L | |
| FE2 | **Phone layout pass.** | Every page works at 375px wide without sideways scrolling, and buttons are easy to tap. | M | |
| FE3 | **Accessibility pass.** | Every form field has a label, everything works with the keyboard alone, and text contrast passes WCAG AA. | M | |
| FE4 | **Admin overview page.** Uses BE6's data. | Admins land on an overview with the counts and quick links to each admin page. | S | |
| FE5 | **Payment pages.** Pairs with BE1. | Payment success and failure pages match the theme and tell the customer what to do next. | S | |

## `feature/integration`: testing, release and documents

| # | Task | Done when | Size | Owner |
|---|---|---|---|---|
| IN1 | **End-to-end testing** on a fresh database, using the checklist below. | Every item passes, and bugs found are fixed or listed in GitHub Issues. | M | |
| IN2 | **Deploy to the school server.** | The live URL works with its own database and settings, and it goes in the progress submission. | M | |
| IN3 | **Update the README.** It still describes Bootstrap and old setup steps. | It covers setup with the settings template, migrations, delivery fees, team members and the live URL. | S | |
| IN4 | **Update `AGENTS.md`.** Several of its open items are fixed, and its theme table still says favourites, settings and contact aren't built. It's also gitignored, so teammates don't have it. | It matches the code, and the team decides whether to share it. | S | |
| IN5 | **Progress submissions.** | Each class's CS 442 form lists the commits or PRs for that session and each person's contribution. | S | |

**Sizes:** S takes about an afternoon, M a couple of days, L most of a week.

## Suggested way to split the work

- **One person on `feature/db`.** Do DB1–DB5 first, because others need the seed images and the settings template.
- **Backend: start BE1 (Paystack) right away.** It's the largest task and the one most likely to be graded. BE2 can run alongside it.
- **Frontend: start FE2 and FE3.** They don't depend on anything else. FE4 and FE5 wait for their backend partners.
- **The integration owner (often the team lead) also does IN1–IN5,** and moves work from `feature/integration` to `dev` to `main`.
- **Decide BE3, BE4 and BE5 together in class.** They're decisions first and code second.

## Test checklist (for IN1)

Run this on a fresh database with seed data, at desktop width and at phone width.

- [ ] Register a new account. Weak passwords, duplicate emails and an invalid phone number are rejected with clear messages.
- [ ] Sign in, sign out, and sign in from the checkout prompt; you should return to checkout.
- [ ] Browse all kitchens, a single kitchen, a category, and search for a meal and for a kitchen.
- [ ] Add meals from two kitchens, change quantities, add a note, remove an item and clear the bill.
- [ ] Check out and pay with a Paystack test card (once BE1 is done). Cancel a payment and confirm the bill is unchanged.
- [ ] Admin: add, edit, pause and delete a kitchen; add, edit and delete a category; add, edit, mark sold out and delete a meal with an image.
- [ ] Admin: move an order through all five statuses and assign a courier; the customer's tracking page updates.
- [ ] Courier: see the assigned order and mark it delivered (once BE2 is done).
- [ ] A paused kitchen and a sold-out meal can't be opened or ordered by a customer.
- [ ] Favourites, settings (profile and password), and a support message that an admin can see and mark as handled.
- [ ] A customer can't open any admin page, and an admin can't order.
