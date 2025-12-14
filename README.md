# WP Gravatar Upload Helper

This repository contains the WordPress plugin that allows visitors to supply their own avatar image (or gravatar) when leaving a comment, without needing a site account.

## Current status
The latest code changes live on the local `work` branch. There is no GitHub remote configured yet, so GitHub will not show these commits until you push them.

## Push the code to GitHub
1. Create an empty GitHub repository (or open an existing one) in your account.
2. Add it as the remote:
   ```bash
   git remote add origin https://github.com/<your-account>/<repo>.git
   ```
3. Push the local branch:
   ```bash
   git push -u origin work
   ```
4. Open a pull request on GitHub if you want these changes merged into the default branch.

## Installing the plugin locally
1. Copy the plugin files into your WordPress `wp-content/plugins` directory.
2. Activate the plugin from **Plugins → Installed Plugins**.
3. Visit the comment form to test avatar uploads.

## Testing
You can validate the PHP file locally before deploying:
```bash
php -l local-comment-avatars.php
```
