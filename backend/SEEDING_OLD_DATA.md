# Seeding Old SQL Data Into The New Backend

This guide explains how to import legacy data from `database/old_databse.sql` into the new Laravel backend database. The import is safe to run multiple times (idempotent) and is guarded by an environment flag so you can opt in when you need it.

What this seeding does
- Stages (creates and fills) the following legacy tables in your app database by extracting them from `database/old_databse.sql`:
  - members
  - loan
  - investment_record_details
  - units
- Seeds required Schemes (Savings types) used to map legacy columns.
- Imports Members into `users`:
  - Maps members.memberno -> users.membership_number
  - Maps members.emailaddress -> users.email (keeps valid unique emails; generates a fallback when missing/invalid/duplicate)
  - Maps members.phoneno -> users.phone (if the phone column exists)
  - Maps members.address -> users.address (new column added via migration and left nullable)
  - Maps members.datejoined -> users.created_at (preserves legacy join date on first insert)
- Creates Branches from legacy `units` and links Users to Branches:
  - Creates a Branch for each legacy units.description
  - Sets users.branch_id based on members.unitid
  - Propagates members.is_default -> users.is_defaulter
- Imports Loans into `qard_hasans`:
  - Maps core fields (principal, months, monthly amount, status) and keeps created_at from legacy release date
  - Status mapping: Approved -> active; Pending -> pending; Completed/Repaid -> completed; Cancelled/Rejected -> cancelled
  - Attaches loan guarantors to each loan by resolving guarantor fields (memberno/id/name) to Users
- Imports every non-zero contribution column from `investment_record_details` into `contributions`:
  - Column-to-scheme mapping: Investment, Savings, Shares, Building, Development, AGM, Loan Repayment, Sav, Welfare, Lateness, Stationery, Loan Form, Others, ID Card, Emergency, Entrance, H Savings, Fine
  - Preserves the original `paymentdate`
  - Uses deterministic references (e.g., `OLDINV-<row>-<COLUMN>`) so reruns donâ€™t duplicate
- Migrates legacy Loan Repayment amounts into the dedicated `qard_hasan_repayments` table and updates each loanâ€™s `paid_amount`:
  - Creates a repayment record per legacy row with reference like `OLDREPAY-<row>` and `status=success`
  - Increments the linked loanâ€™s `paid_amount` and marks it `completed` when fully repaid
  - Matches repayments to the most plausible active/incomplete loan for the member based on payment date
  - If no plausible loan exists in the new DB, it will look up the correct legacy loan (by member and paid date) and auto-import that loan on-the-fly (idempotent) before applying the repayment

Idempotency and safety
- Seeders use update-or-create semantics so you can re-run them without creating duplicates.
- Legacy tables are only staged if they donâ€™t already exist in your database.
- Toggle the import on or off via the `SEED_OLD_DATA` flag.

Prerequisites
- Either Docker + Docker Compose (recommended), or local PHP 8.2+/8.3+, Composer, and MySQL.
- Ensure `backend/database/old_databse.sql` exists (it is already included in this repo).

One-time project setup
1) Copy env file and configure
   - From backend folder, copy `.env.example` to `.env` and adjust if needed.
   - Set the flag to enable legacy import:
     SEED_OLD_DATA=true

2) Install PHP dependencies
   - With Docker Sail: run Composer on host or via Sail
   - Without Docker: run Composer on host

Option A: Using Docker (Laravel Sail)
- Initialize and install dependencies
  - Windows PowerShell (from repo root):
    cd backend
    composer install
- Start containers
  - Windows PowerShell:
    .\sail.ps1 up -d
  - Or cross-platform alternative (if `sail` bash script is available):
    ./sail up -d
- Generate app key (first time only):
  - Windows:
    .\sail.ps1 artisan key:generate
  - Or:
    ./sail artisan key:generate
- Run migrations
  - Windows:
    .\sail.ps1 artisan migrate
  - Or:
    ./sail artisan migrate
- Seed database (includes legacy import because SEED_OLD_DATA=true)
  - Windows:
    .\sail.ps1 artisan db:seed
  - Or:
    ./sail artisan db:seed
- One-shot rebuild (optional):
  - This drops all tables then runs migrations and seeds in one go:
    .\sail.ps1 artisan migrate:fresh --seed

Option B: Running locally without Docker
- From repo root:
  cd backend
  composer install
  php artisan key:generate
- Ensure your `.env` has working DB credentials and `SEED_OLD_DATA=true`.
- Run migrations:
  php artisan migrate
- Seed the database (includes legacy import):
  php artisan db:seed
- Optional clean rebuild:
  php artisan migrate:fresh --seed

Verifying the import
- Users created from legacy members:
  - SELECT COUNT(*) FROM users;
  - Each legacy member with a memberno becomes/updates a user with membership_number.
- Branches from legacy Units and user-branch links:
  - SELECT COUNT(*) FROM branches;  -- should be >= legacy units count
  - SELECT COUNT(*) FROM users WHERE branch_id IS NOT NULL;  -- many users should be linked
- Defaulter flag propagation:
  - SELECT COUNT(*) FROM users WHERE is_defaulter = 1;  -- should match members.is_default=1
- Loans imported:
  - SELECT COUNT(*) FROM qard_hasans;
  - Spot check a few by matching qard_id_string like OLD-000020 -> legacy loan id 20.
  - If none show in the Admin > Loans list, update to the latest code and reseed. The importer now resolves legacy loan.memberno/memberid via the staged members table to the correct users.
- Loan guarantors linked:
  - SELECT COUNT(*) FROM qard_hasan_guarantors;  -- should be > 0 where legacy loan had guarantors
- Contributions imported:
  - SELECT COUNT(*) FROM contributions;
  - Look for references starting with OLDINV- and confirm amounts & dates.

Troubleshooting
- SEED_OLD_DATA has no effect
  - Ensure you edited backend/.env (not the root .env) and that the app is reading it.
  - Clear cached config if you previously cached settings:
    - With Sail: .\sail.ps1 artisan config:clear
    - Locally: php artisan config:clear
- Memory or timeout during seeding of the large SQL file
  - Increase PHP memory_limit and max_execution_time.
  - For MySQL, if you encounter packet size issues, increase max_allowed_packet.
- Seeding seems to hang at InvestmentRecordsFromOldSeeder
  - We optimized this step to batch-upsert contributions and added indexes to speed up idempotent writes.
  - Make sure latest migrations are applied to add the indexes:
    - With Sail: .\sail.ps1 artisan migrate
    - Locally: php artisan migrate
  - If you attempted earlier and aborted mid-way, consider a clean rebuild:
    - .\sail.ps1 artisan migrate:fresh --seed  (Docker)
    - php artisan migrate:fresh --seed          (local)
- Duplicate or unique constraint errors
  - The import uses deterministic references and update-or-create/upsert logic. If you manually changed data, you may need a clean rebuild:
    - migrate:fresh --seed (see commands above)
- Database connection errors
  - Verify DB_* settings match your running MySQL service (Sail uses the values from backend/.env.example by default).
- Error: "Incorrect datetime value: '1970-01-01' for column 'created_at'"
  - Fixed in the importer: legacy DATE-only fields are now normalized to full timestamps (e.g., 1970-01-01 00:00:00).
  - If you hit this previously, update your code and rerun a clean seed:
    - .\sail.ps1 artisan migrate:fresh --seed  (Docker)
    - php artisan migrate:fresh --seed          (local)
- Previously saw "Failed executing old SQL for table <name>: ... doesn't exist"
  - You were likely on an earlier importer that didnâ€™t finalize CREATE TABLE statements correctly. Update to this version and rerun a clean seed:
    - .\sail.ps1 artisan migrate:fresh --seed  (Docker)
    - php artisan migrate:fresh --seed          (local)

Recreate legacy loans only (preserve member investments)
- Use this when you want to rebuild loans imported from the old SQL without touching the very large member investment data in `contributions`.
- What it does:
  - Ensures legacy tables exist (no-op if already staged).
  - Optionally purges previously imported legacy loans with no repayments and zero paid_amount.
  - Re-imports legacy loans and their guarantors.

Commands (Docker Sail):
1) From backend folder, ensure latest migrations are applied:
   .\sail.ps1 artisan migrate
2) Option A â€” Re-import without purging existing legacy loans (safe upsert):
   .\sail.ps1 artisan db:seed --class=Database\\Seeders\\ReimportLegacyLoansSeeder
3) Option B â€” Purge safe-to-delete legacy loans first, then re-import:
   - Temporarily set in backend/.env: REIMPORT_PURGE_OLD_LOANS=true
   - Clear config cache (if any): .\sail.ps1 artisan config:clear
   - Run: .\sail.ps1 artisan db:seed --class=Database\\Seeders\\ReimportLegacyLoansSeeder
   - Optional: unset REIMPORT_PURGE_OLD_LOANS after run.

Commands (Local PHP):
1) php artisan migrate
2) php artisan db:seed --class=Database\\Seeders\\ReimportLegacyLoansSeeder
3) To purge safe legacy loans first, set REIMPORT_PURGE_OLD_LOANS=true in backend/.env, then run the seed.

Notes
- Purge step deletes only loans with qard_id_string like OLD-*, with paid_amount <= 0, and with no repayments; others are left intact.
- Contributions (member investments) are not touched by this seeder.
- Importers are idempotent; repeated runs wonâ€™t duplicate data.

Notes
- Legacy tables (members, loan, investment_record_details) are staged inside the same database and are only used during import.
- The import relies on legacy members.memberno to match users.membership_number. If some rows lack memberno, they are skipped.
- Email addresses from legacy data are validated; invalid or duplicate emails get a generated unique placeholder like `member-<id>-<slug>@old.local`.

Support
If you run into issues that arenâ€™t covered here, please share the relevant error output from the artisan command you ran so we can help diagnose quickly.


## Member Passports Linking

The importer can link legacy member passport photos to Users.

How it works
- The legacy `members.passport` column contains a filename like `IKASSALAAM01060.jpg`.
- Place all legacy passport images inside `backend/public/upload` (exact path), keeping their original filenames.
- During seeding (with `SEED_OLD_DATA=true`), `MemberPassportsFromOldSeeder` will:
  - Scan `public/upload` and build a case-insensitive filename map.
  - For each legacy member that has a `passport` value, find a matching file and set `users.passport_path` to `upload/<filename>` for the user whose `membership_number` matches `members.memberno`.
  - The process is idempotent and safe to re-run.

Notes
- Matching is case-insensitive for filenames.
- If `public/upload` does not exist or a file is missing, the seeder logs a warning and skips that record.
- Ensure the migration adding `users.passport_path` is applied (it is included in this repo).

Verify
- After seeding, run a query (example):
  - SELECT COUNT(*) FROM users WHERE passport_path IS NOT NULL;
- Spot check a few examples by comparing `users.membership_number` to `members.memberno` and confirm that `passport_path` points to a file that exists at `backend/public/` + `passport_path`.
