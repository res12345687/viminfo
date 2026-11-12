# viminfo.in

Content for viminfo.in, a digital transformation consultancy in Delhi NCR.

## About page

`about/about-page.html` contains the About page in WordPress block format.

## Auto-deploy

Every push to `main` runs `.github/workflows/deploy.yml`. It connects to the server over SSH and creates or updates the WordPress page at `/about` using `deploy/update-about.php`.

The workflow needs these repository secrets (Settings → Secrets and variables → Actions):

| Secret | Value |
|---|---|
| `SSH_HOST` | server IP |
| `SSH_USER` | SSH username |
| `SSH_PASSWORD` | SSH password |
| `SSH_PORT` | optional, defaults to 22 |
| `WP_PATH` | optional, WordPress folder (auto-detected if omitted) |

## Manual publish

To publish it by hand, open **Pages → About** in WordPress, switch the editor to **Code editor** (⋮ menu → Code editor), paste the file's contents and click **Update**.
