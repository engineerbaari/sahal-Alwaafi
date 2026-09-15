# CargoSom Cargo Management System

A responsive PHP 8 + Bootstrap 5 + MySQL cargo agency platform with Admin, Staff, Customer, and Accountant access.

## Setup

1. Create the database by importing the single file `database/cargosom.sql` in MySQL or phpMyAdmin. Existing installations created with an earlier release should first run `database/migrate_to_payments.sql` to preserve their payment history.
2. Copy `config.example.php` to `config.php` and set the database credentials and base URL.
3. Run `php -S localhost:8000` from this directory.
4. Open `http://localhost:8000`.

All demo users use password `password`: `admin@cargosom.test`, `staff@cargosom.test`, `accountant@cargosom.test`, and `customer@cargosom.test`.

## Included

- Role-based authentication and dashboards
- AWB shipment registration with date, mark, pieces, KG, rate, sender, receiver, airline, and flight AWB
- Database-calculated totals (`KG × RATE`) and MGQ balances (`TOTAL − PAID DXB`)
- Partial DXB/MGQ payment tracking with automatic paid/partial/unpaid status
- Search and filters by AWB, sender, flight, date, airline, and payment state
- Daily/monthly, outstanding-balance, and airline shipment reports
- CSV export and printable PDF reports
- In-app notification records for cargo and invoice events

For production, use HTTPS, change demo credentials, configure a real mail/SMS provider, set secure session cookie options, and schedule database backups.
