# TCHS College Digital Information Board

A complete PHP 8 and MySQL information-board system for Trongsa College of Heritage and Contemporary Studies, Royal University of Bhutan. It includes a full-screen public display, secure administration portal, publication workflow, automatic expiry, media uploads, live refresh, statistics, and activity auditing.

## Requirements

- PHP 8.0+ with `pdo_mysql`, `fileinfo`, and sessions enabled
- MySQL 8.0+
- Apache 2.4+ with `mod_rewrite`, or Nginx with PHP-FPM
- A writable `uploads/` directory

## Quick installation

1. Create a dedicated MySQL user (recommended):

   ```sql
   CREATE USER 'tchs_board'@'localhost' IDENTIFIED BY 'use-a-long-random-password';
   GRANT ALL PRIVILEGES ON tchs_info_board.* TO 'tchs_board'@'localhost';
   FLUSH PRIVILEGES;
   ```

2. Import the non-destructive schema and sample data from [database/install.sql](database/install.sql):

   ```bash
   mysql -u root -p < database/install.sql
   ```

   The installer creates missing tables and seeds empty content tables. It does not drop existing tables.

3. Configure database environment variables in your web server or PHP-FPM pool. The supported names are `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS`. Defaults are `127.0.0.1`, `3306`, `tchs_info_board`, `root`, and an empty password for local testing only. Use [.env.example](.env.example) as a reference; the application intentionally does not parse public `.env` files.

4. Allow the web-server user to write uploads:

   ```bash
   chmod -R 775 uploads
   chown -R www-data:www-data uploads
   ```

5. Point the web server document root at this project directory. Open `/` for the public board and `/login.php` for administration.

For a local PHP test server:

```bash
php -S 127.0.0.1:8000
```

Then open `http://127.0.0.1:8000/`.

## Initial administrator

- Username: `admin`
- Email: `admin@tchs.edu.bt`
- Password: `Admin@123`

Change this immediately after the first sign-in: open **Administration → shield icon → Change Password**, enter the temporary password, and choose a unique password of at least 10 characters containing uppercase, lowercase, and a number.

## Public display

- Open the public page with `?tv=1`. TV mode requests full screen automatically and retries on the first remote-control key or click when the browser requires a user action.
- Announcements, news, leave records, notices, slides, programmes, statistics, the college vision, and the configurable welcome greeting refresh automatically.
- Publication and expiry rules are enforced by the server. Expired announcements, leave records, notices, and slides are excluded from the public API.
- Available information views swap automatically every 10 seconds by default. Empty sections are skipped, so the television never stops on a blank announcement, news, profile, community, or welcome screen. Change the timing in **Board Settings**. Media items and the welcome greeting can use their own configured duration.
- The layout is optimised for a 16:9 television and adapts to tablets and phones.

## Samsung 75-inch TV setup

The public board is designed on Samsung's standard 16:9 logical canvases: **1920×1080** on UHD TVs and **1280×720** on Full HD TVs. The physical 75-inch size does not change the web layout; the TV model/year determines its Tizen browser version.

1. Put the TV and board computer/server on the same trusted network. For local testing, listen on the network instead of only `127.0.0.1`:

   ```bash
   php -S 0.0.0.0:8000
   ```

   Open `http://SERVER-LAN-IP:8000/?tv=1` in the Samsung Internet/Browser app and bookmark it. Replace `SERVER-LAN-IP` with the computer's LAN address, such as `192.168.1.25`. The PHP development server is for a trusted test network only; use Apache or Nginx for the permanent installation.

2. Leave browser zoom at **100%**. In the TV picture settings, use **16:9** and enable **Fit to Screen** when the model provides it. Menu names differ by model.

3. TV mode is detected automatically on Samsung/Tizen browsers; `?tv=1` forces it. It adds overscan-safe margins, hides the pointer when idle, and enables remote controls:

   - **Left / Right** — previous or next information view
   - **1–8** — jump directly to a view when that section contains current information
   - **OK / Enter** — pause or resume automatic rotation
   - **Back / Exit** — left to the Samsung browser and not intercepted

   Standard direction and Enter keys work in the TV browser. Samsung's special playback/colour keys are not dependable in ordinary hosted webpages, so the board does not require them.

4. For reliable unattended display, prefer wired Ethernet, review the TV's screen-saver/eco/automatic-power-off settings, and arrange a daily browser/TV restart if required by local operations policy.

5. Use TV-safe media:

   - Images: 1920×1080 JPG or PNG, optimised for the web.
   - Video: MP4 containing H.264 video and AAC audio, preferably 1920×1080 at 30 fps. Test every clip on the actual TV before publishing it.
   - A 4K source is normally unnecessary because Samsung TV web apps use a 1920×1080 logical canvas on UHD panels.

The public board has no external font, icon, or stylesheet dependency, so it remains visually complete when the campus server is reachable but the Internet is unavailable. Send the Samsung model code (for example, the code on **Settings → Support → About This TV**) if an exact legacy-Tizen compatibility check is needed.

## Administration workflow

All management pages require a PHP session. Forms use CSRF tokens and prepared PDO statements. Content can be Draft, Published, or Archived. List pages provide view, edit, publish/unpublish, and delete actions. Uploaded filenames are randomised; MIME type and size are checked; executable uploads are blocked by the included Apache rule.

The **Board Settings** page controls college identity, vision, mission, colours, logo, refresh interval, default slide duration, contact information, and motto. The **Welcome Note** page controls the welcome greeting's visibility, text, and duration. The **Activity Log** records sign-ins, sign-outs, content operations, settings changes, statistics snapshots, and password changes.

## Apache virtual host

```apache
<VirtualHost *:80>
    ServerName board.example.edu.bt
    DocumentRoot /var/www/tchs-board

    <Directory /var/www/tchs-board>
        AllowOverride All
        Options -Indexes
        Require all granted
    </Directory>

    SetEnv DB_HOST 127.0.0.1
    SetEnv DB_PORT 3306
    SetEnv DB_NAME tchs_info_board
    SetEnv DB_USER tchs_board
    SetEnv DB_PASS "replace-with-a-strong-password"

    ErrorLog ${APACHE_LOG_DIR}/tchs-board-error.log
    CustomLog ${APACHE_LOG_DIR}/tchs-board-access.log combined
</VirtualHost>
```

Enable the site and reload Apache:

```bash
sudo a2enmod rewrite
sudo a2ensite tchs-board.conf
sudo systemctl reload apache2
```

On production, serve the site over HTTPS and add the database variables through a protected server configuration or secrets manager rather than source control.

## Nginx server block

```nginx
server {
    listen 80;
    server_name board.example.edu.bt;
    root /var/www/tchs-board;
    index index.php;

    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param DB_HOST 127.0.0.1;
        fastcgi_param DB_PORT 3306;
        fastcgi_param DB_NAME tchs_info_board;
        fastcgi_param DB_USER tchs_board;
        fastcgi_param DB_PASS replace-with-a-strong-password;
    }

    location ~* /uploads/.*\.(php|phtml|phar|cgi|pl|py|sh)$ { deny all; }
    location ~ /\. { deny all; }
}
```

Check and reload:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

Update the PHP-FPM socket path to match the installed PHP version. Add TLS with the institution's certificate or Certbot before production use.

## Project map

- `index.php` — public display
- `api/board-data.php` — filtered live JSON feed
- `login.php`, `logout.php` — authentication
- `admin/` — dashboard and all management interfaces
- `includes/` — security, data, and reusable layout helpers
- `config/` — application and PDO configuration
- `assets/` — responsive CSS, JavaScript, and static graphics
- `uploads/` — managed news, staff, slide, and logo files
- `database/install.sql` — complete schema and sample dataset

## Production checklist

- Change the default administrator password.
- Use a least-privilege database user and a long random password.
- Enable HTTPS; session cookies automatically become `Secure` under HTTPS.
- Keep PHP `display_errors=Off` and log errors outside the web root.
- Back up the database and `uploads/` directory regularly.
- Set `upload_max_filesize` and `post_max_size` to at least `20M` if large slide videos are required.
- Review Draft and expired records periodically; keep the activity log according to college policy.
# infotv
# tv_final
