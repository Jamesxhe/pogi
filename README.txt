PUPick AI - PHP/MariaDB lost-and-found app
==========================================

This is a local learning project using PHP, PDO, and MariaDB/MySQL. Its item
matching is rule-based text similarity, not a trained AI model. The PUP-inspired
colors and imagery do not indicate an official PUP website or endorsement.

Requirements
------------
- XAMPP with Apache and MySQL (MariaDB)
- PHP with PDO MySQL and mbstring enabled (both are included in standard XAMPP)

Install on XAMPP
----------------
1. Put this project folder at C:\xampp\htdocs\pupick.
2. Open the XAMPP Control Panel and start Apache and MySQL.
3. Open http://localhost/phpmyadmin/.
4. Select Import, choose database.sql from this folder, then click Import.
   The script creates the pup_pick database and all required tables, keys, and
   relationships. It is safe to import again for a fresh setup; it does not
   erase or migrate existing tables.
5. Set a non-empty password for your MariaDB database account. Prefer creating
   a dedicated account for pup_pick instead of using the MariaDB root account.
   In phpMyAdmin's SQL tab, you can create one with:

   CREATE USER 'pupick_app'@'localhost' IDENTIFIED BY 'REPLACE_WITH_YOUR_OWN_PASSWORD';
   GRANT ALL PRIVILEGES ON pup_pick.* TO 'pupick_app'@'localhost';
   FLUSH PRIVILEGES;

   Replace the example password with one you choose.
6. In PowerShell, from this project folder, run:

   Copy-Item config.local.php.example config.local.php

7. Edit config.local.php and set the username to pupick_app and the password
   to the same password you chose above. Do not share or commit this file.
8. Open http://localhost/pupick/.
9. Create an account, then sign in from the login page.

Sign in
-------
At account creation, choose Student or Administrator. Student accounts require
a student number. Selecting Administrator creates an administrator account
immediately and redirects to the login page with Administrator selected. The
login selection must match the role stored in the database.

Security warning: administrator registration is open and requires no approval
or invite code. Anyone who can access the registration page can create an
administrator account with full access. Use only on a trusted local demo; do
not expose this setup publicly.

Load sample data
----------------
In phpMyAdmin, select pup_pick, open Import, choose sample_data.sql, and click
Import. This adds 60 clearly labeled sample rows: 6 demo users, 20 lost reports,
20 found reports, 6 claims, 4 possible matches, and 4 notifications. The seed
file can be imported more than once without duplicating its sample rows.

Demo accounts use the password PupickDemo123! and emails demo01@example.test
through demo06@example.test. They are for local testing only; do not reuse these
credentials on a public server.

Database connection
-------------------
The app reads its database settings from config.local.php, which is excluded
from direct web access by .htaccess. Copy config.local.php.example to
config.local.php, then set:
- Host: localhost
- Database: pup_pick
- Username: your MariaDB user (the example uses pupick_app)
- Password: a non-empty password you set in MariaDB

The application deliberately refuses a blank or example password. Keep
config.local.php private and never paste your database password into chat or
commit it to source control. If you already use another MariaDB username, set
that account and its non-empty password in config.local.php.

Create an administrator
-----------------------
Choose Administrator on the Create Account form. The account receives admin
access immediately and can sign in from the Login page. Only do this on a
trusted local installation because this registration option is not restricted.

Main pages
----------
index.php          Redirects to login or the signed-in home page
register.php       Student or administrator registration
login.php          Sign in
dashboard.php      Student overview
report_lost.php    Submit a lost-item report
report_found.php   Submit a found-item report
items.php          Browse and search community reports
claim.php          Submit a claim for a found item
ai_matches.php     Review possible lost/found pairs
my_reports.php     View own reports and claims
notifications.php  View account notifications
admin.php          Administrator overview and claim review
admin_items.php    Administrator item management
admin_export.php   Download admin report as XLSX, CSV, or PDF
chatbot.php        AI and offline FAQ assistant for students and admins
database.sql       Create the database and required tables
upgrade_claim_photos.sql  Add private claim-photo storage for existing databases

Workflow notes
--------------
Lost and found reports use one item category as the item label; users can choose
a suggested category or type a new one. Reports can include a JPG, PNG, or WEBP
photo up to 5 MB. Search covers category, color, description, and location.
Users cannot submit a claim for their own found report.

Claimants can attach an optional supporting photo to a claim. Claim evidence is
stored outside the public web directory and can only be viewed by the claimant,
the finder, and administrators. Apply upgrade_claim_photos.sql once to an
existing database before using claim-photo uploads.

Admins can edit report details and statuses in Manage Items. Pending claims
appear first in the admin dashboard: review the claimant's message, approve to
reserve the found item (and linked lost report) or reject if details do not
match. Mark an approved claim returned only after the handoff is complete.

Admins can download an Excel workbook (.xlsx), CSV, or PDF operational report
from the dashboard. The Excel workbook includes separate Summary, Item
Reports, Claims, and Matches sheets with frozen headers, filters, and readable
column widths. The PDF includes a summary page and paginated record tables.
Exports contain operational records, including user-submitted claim messages,
but never account passwords or password hashes. Treat downloaded files as
confidential and store or share them accordingly.

The help chatbot uses OpenRouter AI (or OpenAI) for general questions when an API key
is configured and the service is reachable. For PUPick questions it can explain
site features, but it cannot access reports or private records. Otherwise it
answers with the built-in offline FAQ in
English, Filipino/Taglish, Spanish, French, German, Portuguese, Indonesian,
Chinese, and Japanese.

To enable OpenRouter AI:
1. Get an API key from https://openrouter.ai/keys (starts with sk-or-v1-...).
2. Open config.local.php and set:
   'openrouter_api_key' => 'YOUR_OPENROUTER_API_KEY',
   'openrouter_model' => 'meta-llama/llama-3.3-70b-instruct:free', // or google/gemini-2.0-flash-001, deepseek/deepseek-chat, openai/gpt-4o-mini
3. Alternatively, you can use OpenAI by setting `openai_api_key` and `openai_model`.
PHP cURL must be enabled. The key stays secure on the server; do not put it in JavaScript.

When online replies are enabled, the current question and up to four recent
chat exchanges are sent to the AI service for context; they are not saved in the app's
database. The assistant cannot access private account or report records, so it
will not confirm whether an item was reported or check a claim's status. Item
lookup and common questions still offer direct links to relevant pages. If the
AI request fails, the chatbot uses its local FAQ reply.
The chatbot also recognizes common item-lookup, no-results, photo, privacy,
and report-edit questions. Item lookup opens the report browser with keywords
inferred from the question; it does not claim that a report exists until the
user checks the results.
The chatbot also offers a guided search that asks whether the user wants lost,
found, or either kind of report, then opens the report browser with the chosen
type and entered keywords.
Users can optionally compare a selected photo in their browser with photos from
up to the 50 newest lost and 50 newest found reports that include photos. This
visual-similarity check is approximate, is not object recognition, and may not
match the same item photographed from another angle. The selected photo is not
uploaded or saved; reports without photos cannot be visually checked.
Chat history stays in the current page and can be cleared with the Clear chat
button. When the AI is enabled, recent messages are sent to OpenAI to generate
the next reply. Questions in other languages may need to be rephrased in a
supported language.

Display theme
-------------
Use the Light mode / Dark mode button at the bottom-right of any page. Your
choice is saved in this browser and follows you between login, student, and
administrator pages.

Security and deployment
-----------------------
This project is intended for local learning and is not production-ready. Before
public deployment, add CSRF protection, rate limits, email verification, secure
image-upload handling, and a formal ownership-verification process. Use a
dedicated least-privilege database account instead of MariaDB root. Never ask
users to share account passwords to verify item ownership.
