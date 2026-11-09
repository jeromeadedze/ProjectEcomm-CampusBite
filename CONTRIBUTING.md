# Working on CampusBite with branches

Code moves through five levels of branches. Nobody pushes straight to `main` or `dev`; every change reaches them through a pull request on GitHub.

```
feature/db ────────┐
feature/frontend ──┼──► feature/integration ──► dev ──► main
feature/backend ───┘
```

| Branch | What it's for | Who merges into it |
|---|---|---|
| `feature/db` | Schema, migrations, seed data, ERD and UML diagrams | Database task owners, through a PR |
| `feature/frontend` | Pages, CSS, JavaScript, layout and accessibility | Frontend task owners, through a PR |
| `feature/backend` | Actions, controllers, classes, payments and roles | Backend task owners, through a PR |
| `feature/integration` | Where the three feature branches come together and the whole site is tested | The integration owner, after each feature PR is reviewed |
| `dev` | Tested, working code that the team agrees is ready | The integration owner, once integration passes the test checklist |
| `main` | What gets submitted and deployed | The team lead, at each milestone or submission |

## One-time setup (team lead only)

Do this once, from an up-to-date `main`:

```bash
git switch main
git pull
git switch -c dev
git push -u origin dev
git switch -c feature/integration
git push -u origin feature/integration
git switch dev
for b in feature/db feature/frontend feature/backend; do git switch -c "$b" dev && git push -u origin "$b"; done
```

Then on GitHub, open **Settings → Branches → Add branch protection rule** and add one rule for `main` and another for `dev`. In each, turn on **Require a pull request before merging** with **1 approval**. This stops anyone pushing to those branches directly.

## Before you start (everyone)

`main`'s history was rewritten on Nov 9. If you cloned or pulled before then, reset your copy first. Save any unpushed work on a branch beforehand, because this discards local changes:

```bash
git fetch origin
git switch main
git reset --hard origin/main
```

You also need your own `core/db_cred.php`. It's ignored by git, so it never gets committed. Ask the team lead for the format.

## Day-to-day workflow

1. **Switch to your lane's branch and pull the latest work.** Use `feature/frontend`, `feature/backend` or `feature/db`, whichever your task is in:

   ```bash
   git switch feature/frontend
   git pull
   ```

2. **If two people share a lane, each works on a short task branch** and merges it back into the lane through a PR. Name it after the task, for example:

   ```bash
   git switch -c frontend-phone-layout
   ```

3. **Commit small, clear changes** that each do one thing:

   ```bash
   git add views/menu.php css/campusbite.css
   git commit -m "fix: stop menu cards overflowing on phones"
   ```

   Stage the files you changed by name. Don't use `git add .`, which can pick up `core/db_cred.php` backups, uploaded images and other files that shouldn't be committed.

4. **Keep your branch up to date with `dev`** at least once a day, and always before opening a PR:

   ```bash
   git fetch origin
   git merge origin/dev
   ```

   If git reports a conflict, open each listed file, keep the right lines, remove the `<<<<<<<`, `=======` and `>>>>>>>` markers, then run `git add <file>` and `git commit`. Ask the person who wrote the other side if you're unsure.

5. **Push and open a pull request:**

   ```bash
   git push -u origin HEAD
   ```

   On GitHub, open a pull request into the right target:
   - a task branch goes into its lane, for example `frontend-phone-layout` into `feature/frontend`
   - a lane goes into `feature/integration`

   In the description, say what changed, how you tested it, and whether teammates need to run a database migration. Ask one teammate to review it.

## Moving work up to `main`

1. **Into `feature/integration`:** the integration owner merges the reviewed feature PRs, imports the schema into a fresh database and runs the test checklist in [docs/ROADMAP.md](docs/ROADMAP.md).
2. **Into `dev`:** when everything passes, they open a PR from `feature/integration` into `dev`.
3. **Into `main`:** before a submission or deployment, the team lead opens a PR from `dev` into `main` and merges it.
4. **After each merge into `main`,** bring every branch back in line so nothing drifts:

   ```bash
   for b in dev feature/integration feature/db feature/frontend feature/backend; do
     git switch "$b" && git pull && git merge origin/main && git push
   done
   ```

## Rules

- Never push to `main` or `dev` directly, and never force-push a shared branch.
- Never commit `core/db_cred.php` or any password, API key or Paystack secret key.
- A database change needs a new file in `database/migrations/` named with the date, plus the same change in `database/campusbite.sql`.
- Never import `database/campusbite.sql` into a database that holds data you need, because it drops every table first.
- Pull before you start work and push before you stop, so the class progress submission can link to real commits.
