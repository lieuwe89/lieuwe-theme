<?php
/**
 * GitHub-driven auto-updater for the lieuwe-theme.
 *
 * Uses the Plugin Update Checker (PUC) library by YahnisElsts to read tags
 * from the public lieuwe89/lieuwe-theme repository. When a tag with a higher
 * semver version than style.css's `Version:` header lands on `main`, WP shows
 * an update notice under Dashboard → Updates and Appearance → Themes.
 *
 * Library: https://github.com/YahnisElsts/plugin-update-checker (v5.6)
 *
 * @package Lieuwe_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$lieuwe_puc_bootstrap = get_template_directory() . '/vendor/plugin-update-checker/plugin-update-checker.php';

if ( ! file_exists( $lieuwe_puc_bootstrap ) ) {
    return;
}

require_once $lieuwe_puc_bootstrap;

if ( ! class_exists( '\\YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory' ) ) {
    return;
}

// Second arg must point at the theme's stylesheet (style.css) so PUC detects
// this as a theme update target rather than a plugin.
$lieuwe_theme_updater = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
    'https://github.com/lieuwe89/lieuwe-theme/',
    get_template_directory() . '/style.css',
    'lieuwe-theme'
);

$lieuwe_theme_updater->setBranch( 'main' );
