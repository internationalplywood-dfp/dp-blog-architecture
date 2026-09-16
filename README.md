# Discount Plywood — Blog Architecture

Permanent fix for the `/blog/` hub on discountplywood.com.

| File | Role |
|---|---|
| `dp-guides-engine.php` | MU-plugin. Drop in `wp-content/mu-plugins/`. Auto-applies the branded guide template to any Post in the **Plywood Guides** category, and renders the hub grid via `[dp_guides_grid]` from both guide Posts and the legacy child Pages of hub page 145. |
| `fix145_dynamic_hub.py` | One-time conversion of hub page 145 from hardcoded cards to the shortcode. Dry-run by default; backs up original `post_content` before writing. |

## Why

The hub held 24 hardcoded links and queried nothing, so new articles could never appear
without a manual page edit. The branded chrome lived inside each page's content, so
generated Posts inherited none of it. Both are now template-level concerns.

## Deploy

```bash
curl -fsSL -o /var/www/staging/wp-content/mu-plugins/dp-guides-engine.php \
  https://raw.githubusercontent.com/internationalplywood-dfp/dp-blog-architecture/main/dp-guides-engine.php
php -l /var/www/staging/wp-content/mu-plugins/dp-guides-engine.php
```

Database credentials are read automatically from the environment's `wp-config.php`.
Nothing needs to be typed and no credentials are stored in this repository.

## Rollback

Delete the MU-plugin file — it holds no state and writes nothing to the database.
Restore page 145 `post_content` from the backup the script writes to `/root/dp-backups/`.
