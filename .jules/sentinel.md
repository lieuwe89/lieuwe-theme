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

## 2024-05-24 - [Defense in Depth] Adding Permissions-Policy header
**Vulnerability:** Browsers grant powerful web features (geolocation, camera, microphone, usb, payment) by default which can be exploited via XSS or third-party iframe injection if left unrestrained.
**Learning:** Adding a `Permissions-Policy` header acts as a defense-in-depth barrier. It preemptively denies access to features the theme does not require, reducing the attack surface.
**Prevention:** Include modern security headers in the centralised `lieuwe_add_security_headers` function by default to harden against unintended browser feature access.

## 2024-05-24 - [Security Hardening] Prevented User Enumeration via Core Sitemaps
**Vulnerability:** Even when REST API and author query parameters are restricted, WordPress 5.5+ core XML sitemaps feature automatically generates a `/wp-sitemap-users-1.xml` file which lists all users, allowing attackers to easily enumerate valid usernames.
**Learning:** WordPress continually adds new features (like native sitemaps) that may inadvertently expose data previously secured through manual overrides. Security measures must account for these new features that bypass older enumeration protections.
**Prevention:** Filter `wp_sitemaps_add_provider` to return `false` when the provider name is `users` to completely disable the user sitemap generation while leaving post and taxonomy sitemaps intact.
## 2024-05-24 - [CRITICAL] Full Path Disclosure (FPD) vulnerability in template files
**Vulnerability:** Many core WordPress template files (e.g. `header.php`, `footer.php`, `archive.php`, `single.php`, etc.) were missing the `ABSPATH` check at the beginning of the file.
**Learning:** Without the `ABSPATH` check, directly accessing these files could lead to a Full Path Disclosure (FPD) vulnerability, as PHP errors might reveal the absolute path to the file on the server.
**Prevention:** Ensure all PHP entry points and template files include the `if ( ! defined( 'ABSPATH' ) ) { exit; }` check.
## 2026-05-24 - [HIGH] XSS Vulnerability via get_the_date()
**Vulnerability:** The `get_the_date()` function in `index.php` was output directly without escaping, creating a potential Stored XSS vulnerability if an administrator customized the date format with malicious scripts.
**Learning:** Even built-in WordPress functions that seem to output safe data (like dates) can be exploited if they rely on user-configurable settings. All dynamic output must be escaped.
**Prevention:** Always wrap dynamic output, including native functions like `get_the_date()`, with appropriate WordPress escaping functions (e.g., `esc_html()`).

## 2026-05-24 - [HIGH] XSS Vulnerability via Title Functions
**Vulnerability:** Built-in WordPress title functions (`single_cat_title()`, `single_tag_title()`, and `single_post_title()`) were echoing their values by default without escaping, creating a potential Stored XSS vulnerability.
**Learning:** Functions that output data directly can be dangerous if the underlying data can be manipulated. Passing `false` to the second parameter prevents automatic echoing.
**Prevention:** Always pass `false` to prevent direct echoing and wrap the output in `esc_html()` to sanitize the data before display.
