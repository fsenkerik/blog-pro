# Railway deployment

## 1. Add MySQL

In the Railway project canvas, click **New** / **+ Create** and add a **MySQL** database service.

After it starts, Railway provides database variables such as:

- `MYSQLHOST`
- `MYSQLPORT`
- `MYSQLUSER`
- `MYSQLPASSWORD`
- `MYSQLDATABASE`
- `MYSQL_URL`

## 2. Add variables to the app service

Open the `blog-pro` service, then **Variables**.

Set:

```env
APP_ENV=production
BASE_URL=https://your-app.up.railway.app/
```

Then add database variable references from the MySQL service. The app supports either Railway's native names:

```env
MYSQLHOST=${{MySQL.MYSQLHOST}}
MYSQLPORT=${{MySQL.MYSQLPORT}}
MYSQLUSER=${{MySQL.MYSQLUSER}}
MYSQLPASSWORD=${{MySQL.MYSQLPASSWORD}}
MYSQLDATABASE=${{MySQL.MYSQLDATABASE}}
```

Or the app aliases:

```env
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_USER=${{MySQL.MYSQLUSER}}
DB_PASS=${{MySQL.MYSQLPASSWORD}}
DB_NAME=${{MySQL.MYSQLDATABASE}}
```

Use the exact service name Railway shows in the project canvas if it is not `MySQL`.

## 3. Redeploy

Redeploy `blog-pro`. On the first successful database connection, `init-db.sh` imports `database.sql` if the database has no tables.

The health check is:

```text
/health.php
```

## 4. First login

Open:

```text
https://your-app.up.railway.app/admin/login.php
```

Default users from `database.sql`:

- `admin1` / `heslo123`
- `editor1` / `heslo456`

Change the default passwords after the first login.
