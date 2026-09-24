# PickRide — ride-hailing website (PHP/MySQL)

A tuk/car/van booking website inspired by PickMe's ride-hailing flow, with a
rider-facing site and an admin panel. Built with plain PHP + PDO + MySQL, no
framework required.

## Features ok abd

**Rider site**
- Landing page with vehicle pricing
- Sign up / log in
- Book a ride: pickup + destination, distance, vehicle picker with a live
  fare preview
- Ride history with a 4-step status tracker (requested → confirmed → on the
  way → completed)

**Admin panel** (`/admin`)
- Dashboard with ride/driver/revenue stats
- Bookings: assign an available driver, update ride status
- Drivers: add / edit / delete, set availability, and set each driver's
  dashboard login (username + password)
- Riders: read-only list with ride counts
- Vehicle types: manage base fare and per-km rate, activate/deactivate
- Admin accounts: add/remove other admins, export the admin list as CSV
- Settings: change your own admin email and password
- Login is now fully wired up — only signed-in admins can reach any
  `/admin` page

**Driver dashboard** (`/driver`)
- Drivers log in with the username/password an admin sets for them
- Toggle their own availability (available/offline)
- See rides assigned to them, mark a confirmed ride "on the way", then
  "completed"
- Rides history

## Setup

1. **Create the database.** Import the schema (creates the `pickride`
   database and seeds vehicle types + a default admin account):
   ```
   mysql -u root -p < database.sql
   ```
   Already have a `pickride` database from an earlier version of this
   project? Don't re-run `database.sql` (it would try to recreate
   tables that already exist) — instead just add the two new driver
   login columns:
   ```
   mysql -u root -p pickride < migration_driver_login.sql
   ```

2. **Set your DB credentials** in `config/db.php` (`DB_HOST`, `DB_NAME`,
   `DB_USER`, `DB_PASS`).

3. **Serve the folder** with PHP's built-in server for local testing:
   ```
   php -S localhost:8000
   ```
   Or point an Apache/Nginx vhost's document root at this folder. Requires
   PHP 8+ with the `pdo_mysql` extension.

4. **Log in as admin** at `/admin/login.php`:
   - Email: `admin@pickride.lk`
   - Password: `admin123`

   Change this password straight away from **Settings** inside the
   admin panel (or add a new admin from **Admin accounts** and delete
   this seeded one) before using the site for anything real.

5. **Add drivers with login details.** In **Drivers**, add/edit a
   driver and set a dashboard username + password for them — that's
   what they use to log in at `/driver/login.php`. The password is
   shown once right after you save; write it down or export the
   driver list (CSV) before you navigate away.

## Notes

- Distance is entered manually on the booking form (no mapping API is
  wired in) — fare = base fare + rate/km × distance, calculated
  server-side.
- All queries use prepared statements; passwords are hashed with
  `password_hash()`.
- This is an original build inspired by the ride-hailing category — it
  doesn't reuse PickMe's code, design assets, or branding.
