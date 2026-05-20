## 2026-05-16 - [Security Hardening] Prevented User Enumeration and Disabled XML-RPC
**Vulnerability:** Unauthenticated users could enumerate registered users via the REST API (`/wp/v2/users`) and query string (`/?author=N`). XML-RPC was also enabled, opening the site to brute-force and DDoS attacks.
**Learning:** Classic WordPress themes leave several default endpoints and query patterns open that facilitate reconnaissance.
**Prevention:** Explicitly hooked into `rest_endpoints`, `template_redirect`, and `xmlrpc_enabled` in `functions.php` to restrict these pathways. Ensure future theme developments disable these by default.

## 2026-05-18 - [Security Hardening] Prevented Full Path Disclosure and WordPress Version Leakage
**Vulnerability:** Core PHP theme files (`functions.php`, `inc/customizer.php`) could be accessed directly, potentially causing Full Path Disclosure (FPD) if PHP errors were displayed. Additionally, the WordPress version was exposed via the `wp_generator` meta tag and RSS feeds, which aids attackers in fingerprinting known vulnerabilities.
**Learning:** Classic WordPress themes don't have an inherent direct access protection or version hiding mechanism by default; it must be implemented manually in `functions.php`.
**Prevention:** Added `if ( ! defined( 'ABSPATH' ) ) { exit; }` to all entry point PHP files. Hooked into `wp_head` (via `remove_action`) and `the_generator` (via `add_filter`) to strip WordPress version information site-wide.

## 2026-05-19 - [Security Hardening] Fixed User Enumeration Bypass and Status Code Leakage
**Vulnerability:** The previous user enumeration protection in `functions.php` only checked for the `$_REQUEST['author']` parameter and returned a 403 Forbidden. This allowed attackers to bypass the check using permalinks (e.g., `/author/username/`). Furthermore, by returning a 403, it leaked the existence of users (existing users returned 403, while non-existing users returned 404), which is a form of status code leakage.
**Learning:** Security checks that rely on specific query parameters can easily be bypassed by alternative routing mechanisms (like WP permalinks). Also, responding with a distinct error code (403 vs 404) defeats the purpose of preventing enumeration.
**Prevention:** Use higher-level WordPress functions like `is_author()` to catch all author-related requests regardless of the URL format. Always respond uniformly (e.g., redirecting to the homepage) so attackers cannot distinguish between existing and non-existing resources.
