# Win POS

Win POS is a redesigned version of the existing Factory POS application. It keeps the current PHP/MySQL business workflows and database connection while presenting a simpler, responsive interface.

## Included

- Dashboard
- Customers
- Site surveys
- Invoices and returns
- Orders
- Products and returned inventory
- Payments
- Labour records
- Sales, payment, and customer reports
- Settings, users, and roles

## Not included

- Materials
- Material usage
- Production
- Production, material-stock, and material-cost reports

## Run locally

From this directory:

```sh
php -S localhost:8001
```

Then open <http://localhost:8001>.

The app uses the existing database configuration in `config/config.php`.
