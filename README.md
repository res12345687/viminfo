# viminfo.in

Content for viminfo.in, a digital transformation consultancy in Delhi NCR.

## Layout

| Path | What it is |
|---|---|
| `content/manifest.json` | List of pages and blog posts to publish (slug, title, file, excerpt, category, date) |
| `content/pages/` | Page content in WordPress block format (`about.html`, `blog.html`) |
| `content/posts/` | Blog posts in WordPress block format |
| `deploy/publish.php` | Creates or updates every item in the manifest, matched by slug |

## Adding a blog post

1. Add an HTML file to `content/posts/` (WordPress block markup).
2. Add an entry for it under `posts` in `content/manifest.json`.
3. Push to `main`. The post goes live automatically.

## Auto-deploy

Every push to `main` runs `.github/workflows/deploy.yml`, which uploads `content/` to the server over SSH and runs `deploy/publish.php` against the WordPress install.

Repository secrets (Settings → Secrets and variables → Actions):

| Secret | Value |
|---|---|
| `SSH_HOST` | server IP |
| `SSH_USER` | SSH username |
| `SSH_PASSWORD` | SSH password |
| `SSH_PORT` | optional, defaults to 22 |
| `WP_PATH` | optional, WordPress folder (auto-detected if omitted) |
