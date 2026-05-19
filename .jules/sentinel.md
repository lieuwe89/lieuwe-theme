## 2026-05-16 - [Security Hardening] Prevented User Enumeration and Disabled XML-RPC
**Vulnerability:** Unauthenticated users could enumerate registered users via the REST API (`/wp/v2/users`) and query string (`/?author=N`). XML-RPC was also enabled, opening the site to brute-force and DDoS attacks.
**Learning:** Classic WordPress themes leave several default endpoints and query patterns open that facilitate reconnaissance.
**Prevention:** Explicitly hooked into `rest_endpoints`, `template_redirect`, and `xmlrpc_enabled` in `functions.php` to restrict these pathways. Ensure future theme developments disable these by default.

## 2026-05-18 - [Security Hardening] Prevented Information Leakage via WP Version and Login Errors
**Vulnerability:** The WordPress version was exposed via the `<meta name="generator">` tag in the `<head>` and RSS feeds, aiding attackers in finding version-specific vulnerabilities. Furthermore, default login errors exposed whether a username existed, aiding in user enumeration.
**Learning:** WordPress is chatty by default, exposing version metadata and detailed login error states which can be used for reconnaissance and user enumeration.
**Prevention:** Used `remove_action` to strip `wp_generator` from `wp_head`, filtered `the_generator` to return an empty string, and filtered `login_errors` to return a generic "Incorrect username or password" message.
