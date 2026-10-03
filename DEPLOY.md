# Deploying to Hostinger

This guide assumes Hostinger **Premium or Business web hosting** (shared, hPanel). The app also runs unchanged on a Hostinger VPS.

## One-time setup in hPanel

1. **PHP version**: Advanced → PHP Configuration → choose **PHP 8.3**. Make sure the `pdo_mysql`, `mbstring`, `gd`, `exif`, `fileinfo` and `zip` extensions are enabled. Under PHP options, set `upload_max_filesize` and `post_max_size` to at least **16M** so handwriting photos can be uploaded.
2. **Database**: Databases → MySQL Databases → create a database and user. Note the database name, user and password (they look like `u123456789_neat`).
3. **Subdomain**: Domains → Subdomains → create e.g. `app.yourdomain.com`. Hostinger creates a folder for it such as `~/domains/yourdomain.com/public_html/app`.
4. **SSH**: Advanced → SSH Access → enable it and note the host, port (usually `65002`) and username.
5. **SSL**: Security → SSL → make sure the subdomain has a certificate (free).

## First deploy (over SSH)

```bash
ssh -p 65002 u123456789@your-server-ip

# Code lives outside the web root so .env and the source are never public.
cd ~
git clone https://github.com/ashokmsc4/neat-handwriting.git
cd neat-handwriting
composer install --no-dev --optimize-autoloader

cp .env.example .env
nano .env
```

Set at least these in `.env`:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u123456789_neat
DB_USERNAME=u123456789_neat
DB_PASSWORD=your-db-password

OWNER_NAME="Your wife's name"
OWNER_EMAIL=her@email.com
OWNER_PASSWORD=a-strong-password
```

Then:

```bash
php artisan key:generate
php artisan migrate --force --seed
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### Point the subdomain at `public/`

The web root must be Laravel's `public` folder. Replace the subdomain's folder with a symlink:

```bash
rm -rf ~/domains/yourdomain.com/public_html/app
ln -s ~/neat-handwriting/public ~/domains/yourdomain.com/public_html/app
```

(Use the exact folder hPanel created for your subdomain.) Open `https://app.yourdomain.com`; you should see the sign-in page. For a private repository, add an SSH deploy key from the server to GitHub first (Settings → Deploy keys), and clone with the `git@github.com:` URL.

### Scheduler (cron)

hPanel → Advanced → Cron Jobs → add, every minute:

```
cd ~/neat-handwriting && php artisan schedule:run >> /dev/null 2>&1
```

This creates each month's fee invoices automatically (checked every morning at 6:00). If the cron isn't set up yet, use the **Bill <month>** button on the Fees page instead.

## Updating after a change

```bash
cd ~/neat-handwriting
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Built CSS/JS (`public/build`) comes from the repository, so there is nothing to build on the server.

## Installing on phone and iPad

- **iPhone/iPad (Safari)**: open the site → Share → *Add to Home Screen*.
- **Android (Chrome)**: open the site → menu → *Install app*.

## Backups

Hostinger Business takes daily backups. Before go-live we'll also add a nightly database export sent off the server.
