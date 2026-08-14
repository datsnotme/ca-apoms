# LAN_DEPLOYMENT.md

How to make the Administrator's PC reachable by other computers on the same office network, so
the Dean, Department Heads, and Faculty can use CA-APOMS from their own machines without installing
anything themselves.

**This is not `DEPLOYMENT.md`.** `DEPLOYMENT.md` covers hosting CA-APOMS on a dedicated server with
a real domain and a public/institutional network path — written for Linux + Apache/Nginx. This
document covers the specific, narrower case this application was actually designed around: the
**Administrator's own Windows PC, running XAMPP, acting as the office's on-premises hub**, with
Dean/Head/Faculty machines reaching it as plain browser clients over the local network only — no
install, no account, no database of their own. See `ASSUMPTIONS.md`'s "Post-Launch: Hybrid
Online/LAN/Offline Sync" section for the full architecture this fits into.

**This is also not sync setup.** Syncing the Admin PC's data to a future cloud instance is a
completely separate concern, configured through the Sync Center (`/sync`) and documented in
`ASSUMPTIONS.md`/`ROLE_PERMISSIONS.md`. Dean/Head/Faculty accessing the app over the LAN never touch
Sync Center, never register a device, and are unaffected by whether sync is configured at all —
they're just using the one application running on the Admin's PC, the same as if they were sitting
at that PC themselves.

## Before you start

- Follow `INSTALLATION.md` first if you haven't already — this document assumes CA-APOMS already
  runs correctly when you visit it from the Admin PC itself.
- **Do not use `php artisan serve` for this.** The same reason `DEPLOYMENT.md` gives (the
  `cli-server` SAPI breaks Phase 8C's `mysqldump`/`mysql` backup shell-outs) applies here — LAN
  deployment means running through XAMPP's real Apache, not the built-in dev server.
- You'll need to be comfortable with your office's router — a DHCP reservation is the cleanest way
  to give the Admin PC a stable address (see Step 1), and that's configured on the router, not the
  PC. If nobody has router admin access, the fallback in Step 1 avoids that requirement.

## Step 1 — Give the Admin PC a stable local address

Dean/Head/Faculty need to type (or bookmark) the same address every time. A normal DHCP-assigned IP
can change after a reboot or a long time offline, silently breaking everyone's bookmark.

**Preferred: DHCP reservation.** In the office router's admin page, find the Admin PC's current IP
and MAC address (`ipconfig /all` on the Admin PC, look for "Physical Address" and "IPv4 Address"
under the active adapter) and add a DHCP reservation binding that MAC to that IP permanently. The
PC keeps using ordinary DHCP — nothing changes on the PC itself — but the router will always hand it
the same address.

**Fallback: static IP on the PC.** If there's no router access, set a static IP directly on the
Admin PC (Windows Settings → Network & Internet → adapter properties → IP assignment → Manual).
Pick an address **outside** the router's DHCP pool range (check the router's DHCP settings for that
range) — reusing an address the router might also hand out to another device causes an intermittent,
hard-to-diagnose IP conflict.

Either way, write the chosen address down — it's what goes in `APP_URL` (Step 4) and what everyone
else types into their browser.

## Step 2 — Serve the app through XAMPP's Apache, not `php artisan serve`

1. Build production frontend assets once (`npm run build`) so the LAN doesn't depend on the Vite
   dev server staying open — see `DEPLOYMENT.md`'s deployment steps for the full
   `composer install --no-dev` / `npm run build` / `migrate --force` / `storage:link` sequence; run
   all of it before continuing here.
2. In `C:\xampp\apache\conf\extra\httpd-vhosts.conf`, add a vhost whose `DocumentRoot` is the
   project's `public/` folder — never the project root, same rule `DEPLOYMENT.md` gives for any web
   server:
   ```apache
   <VirtualHost *:80>
       ServerName ca-apoms.local
       DocumentRoot "C:/Users/<you>/Desktop/CA-APOMS/public"

       <Directory "C:/Users/<you>/Desktop/CA-APOMS/public">
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```
   `AllowOverride All` + `Require all granted` lets the project's existing `public/.htaccess`
   (already ships with the repo) do its `mod_rewrite` job unmodified.
3. Confirm `C:\xampp\apache\conf\httpd.conf` has `LoadModule rewrite_module modules/mod_rewrite.so`
   uncommented (XAMPP ships it enabled by default; only relevant if someone previously disabled it).
4. Confirm Apache is actually listening on all interfaces, not just `127.0.0.1` — XAMPP's default
   `Listen 80` in `httpd.conf` already binds every interface, so this is usually already correct;
   only check it if Step 3's LAN test fails and the Admin PC itself can still reach the app fine.
5. Restart Apache from the XAMPP Control Panel for the vhost to take effect.

## Step 3 — Open the firewall, but only to the LAN

Windows Firewall blocks inbound connections to port 80 by default. Add an inbound rule scoped to
the office's local subnet, not "Any" — the app should be reachable from the LAN, not from
whatever network the PC happens to be on later (a coffee-shop Wi-Fi, a hotel network, etc.):

1. Windows Defender Firewall with Advanced Security → Inbound Rules → New Rule.
2. Rule type: **Port** → TCP → Specific local port `80`.
3. Action: **Allow the connection.**
4. Profile: check only **Private** (uncheck Public and Domain, unless your office network is
   specifically configured as one of those) — this is what actually limits exposure to trusted
   networks.
5. Scope (on the rule's Properties → Scope tab, after creating it): restrict "Remote IP address" to
   the office's LAN subnet (e.g. `192.168.1.0/24`) rather than leaving it open to any address —
   belt-and-suspenders alongside the Private-profile restriction above.

From another PC on the same LAN, browse to `http://<the-address-from-step-1>` and confirm the login
page loads. If it doesn't, check the firewall rule first (the most common cause), then confirm the
Admin PC's antivirus/security suite doesn't have its own separate firewall blocking the port.

## Step 4 — `.env` for LAN access

Most of `DEPLOYMENT.md`'s environment-configuration section still applies (fresh `APP_KEY`,
`APP_DEBUG=false`, a dedicated non-root database user, a real `MAIL_MAILER` if notifications are
in use). Two settings specifically matter for LAN access by IP address rather than a real domain:

```
APP_URL=http://192.168.1.50          # the address from Step 1 — not localhost, not 127.0.0.1
SESSION_DOMAIN=null                  # leave unset — see below
```

Leave `SESSION_DOMAIN` as `null` (the `.env.example` default). Setting it to a specific hostname
(e.g. `localhost`) would make the session cookie only valid for that literal host — every
Dean/Head/Faculty browser hitting the app by its LAN IP would then fail to stay logged in. `null`
tells Laravel to use whatever host the request actually came in on, which is what a multi-client LAN
setup needs.

**HTTPS is optional here, unlike `DEPLOYMENT.md`'s "always HTTPS" rule** — that rule assumes
internet-facing exposure. Traffic that never leaves a physically-controlled office LAN has a
materially different threat model. If the office network includes untrusted devices (guest Wi-Fi on
the same subnet, for instance), treat it like a public deployment instead and follow
`DEPLOYMENT.md`'s HTTPS section, generating a self-signed certificate for the LAN IP/hostname and
installing it as trusted on each client PC (avoids a browser warning on every visit). If the LAN is
genuinely a closed, trusted office network, plain HTTP on port 80 as configured above is a reasonable
simplification — leave `SESSION_SECURE_COOKIE` unset/false in that case, since it requires HTTPS to
work at all.

## Step 5 — Keep the Admin PC available during office hours

The Admin PC is now a shared resource, not just one person's workstation — the app being reachable
depends on the PC being on, awake, and running Apache/MySQL, for as long as anyone might need it.

- **Disable sleep while plugged in**: Windows Settings → System → Power & battery → Screen and
  sleep → set "When plugged in, put my device to sleep" to Never. Leaving the screen lock/timeout
  alone is fine — that doesn't affect network reachability, only sleep does.
- **Auto-start Apache and MySQL**: open the XAMPP Control Panel, and check the "Svc" checkbox next
  to both Apache and MySQL to install them as Windows services that start automatically on boot —
  this means the app comes back up on its own after a Windows update forces a restart, without
  anyone needing to remember to open the XAMPP Control Panel and click Start.
- **Known XAMPP gotcha — MySQL can silently stop**: XAMPP's bundled MySQL/MariaDB has a real,
  observed tendency to drop out from under a long-running session (seen firsthand while developing
  and testing this application) without the XAMPP Control Panel clearly reflecting it — the GUI
  can appear to hang rather than show a clean "stopped" state. If the app starts returning database
  connection errors, don't assume the worst; first try restarting MySQL directly rather than via
  the Control Panel GUI:
  ```
  C:\xampp\mysql_start.bat
  ```
  run hidden/in the background if you don't want a console window staying open. If this becomes a
  recurring problem rather than a rare one, that's a sign to plan a move off XAMPP's bundled MySQL
  onto a real standalone MySQL/MariaDB Windows service — XAMPP is a development convenience bundle,
  not something designed for unattended always-on hosting.

## Step 6 — Point everyone at it

Give Dean/Head/Faculty the address from Step 1 (or a short internal name if you've set one up via
the router or a hosts-file entry, e.g. `http://ca-apoms.local`) to bookmark. From here, `USER_GUIDE.md`
covers everything about actually using the application — logging in, navigating by role, and so on.
Nothing about how they reached the app changes what they see; permissions still work exactly as
`ROLE_PERMISSIONS.md` describes.

## Post-setup verification checklist

- [ ] From a *different* PC on the LAN (not the Admin PC itself), `http://<address>/login` loads.
- [ ] Logging in from that second PC works and lands on the correct role-specific dashboard.
- [ ] The Admin PC's own browser still reaches the app fine at the same address (confirms the vhost
  isn't accidentally `127.0.0.1`-only).
- [ ] Restart the Admin PC and confirm Apache/MySQL come back up on their own (tests Step 5's
  service auto-start) and the app is reachable again without anyone touching the XAMPP Control
  Panel.
- [ ] Put the Admin PC to sleep manually (if you can) and confirm it doesn't — or fix the power
  setting in Step 5 if it did.
- [ ] From a phone or laptop on the office guest Wi-Fi (if one exists and is a separate subnet from
  the main office LAN), confirm the app is **not** reachable — proves the firewall/DHCP scoping
  actually kept this LAN-only rather than accidentally open to every device the router serves.

## Troubleshooting

- **Works on the Admin PC, not from another PC on the LAN**: almost always the Windows Firewall
  rule (Step 3) — either missing, scoped to the wrong profile, or the security suite has its own
  separate firewall. Confirm with `telnet <address> 80` (or `Test-NetConnection <address> -Port 80`
  in PowerShell) from the other PC first, before suspecting the application itself.
- **Login page loads but styles/images are broken**: frontend assets weren't built (`npm run
  build`, Step 2.1), or `APP_URL` (Step 4) doesn't match the address actually being used, which can
  affect how the compiled asset manifest resolves URLs.
- **Logged-in state doesn't persist / immediately bounced back to login**: check `SESSION_DOMAIN`
  (Step 4) hasn't been set to a specific hostname.
- **Database connection errors after the app has been up for a while**: see the MySQL gotcha in
  Step 5 before assuming a configuration problem.
