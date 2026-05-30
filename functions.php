<?php
/**
 * ombidya functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package ombidya
 */

if ( ! defined( '_S_VERSION' ) ) {
	// Replace the version number of the theme on each release.
	define( '_S_VERSION', '1.0.0' );
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function ombidya_setup() {
	/*
		* Make theme available for translation.
		* Translations can be filed in the /languages/ directory.
		* If you're building a theme based on ombidya, use a find and replace
		* to change 'ombidya' to the name of your theme in all the template files.
		*/
	load_theme_textdomain( 'ombidya', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/*
		* Let WordPress manage the document title.
		* By adding theme support, we declare that this theme does not use a
		* hard-coded <title> tag in the document head, and expect WordPress to
		* provide it for us.
		*/
	add_theme_support( 'title-tag' );

	/*
		* Enable support for Post Thumbnails on posts and pages.
		*
		* @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		*/
	add_theme_support( 'post-thumbnails' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus(
		array(
			'menu-1' => esc_html__( 'Primary', 'ombidya' ),
		)
	);

	/*
		* Switch default core markup for search form, comment form, and comments
		* to output valid HTML5.
		*/
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	// Set up the WordPress core custom background feature.
	add_theme_support(
		'custom-background',
		apply_filters(
			'ombidya_custom_background_args',
			array(
				'default-color' => 'ffffff',
				'default-image' => '',
			)
		)
	);

	// Add theme support for selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	/**
	 * Add support for core custom logo.
	 *
	 * @link https://codex.wordpress.org/Theme_Logo
	 */
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
}
add_action( 'after_setup_theme', 'ombidya_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function ombidya_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'ombidya_content_width', 640 );
}
add_action( 'after_setup_theme', 'ombidya_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function ombidya_widgets_init() {
	register_sidebar(
		array(
			'name'          => esc_html__( 'Sidebar', 'ombidya' ),
			'id'            => 'sidebar-1',
			'description'   => esc_html__( 'Add widgets here.', 'ombidya' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'ombidya_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function ombidya_scripts() {
	wp_enqueue_style( 'ombidya-style', get_stylesheet_uri(), array(), _S_VERSION );
	wp_style_add_data( 'ombidya-style', 'rtl', 'replace' );

	wp_enqueue_script( 'ombidya-navigation', get_template_directory_uri() . '/js/navigation.js', array(), _S_VERSION, true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'ombidya_scripts' );

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}
<?php

/**
 * App Path Router
 * Serves static files from /dist for SPA-style routing,
 * falling back to WordPress for unmatched paths.
 */

// ─── Constants ───────────────────────────────────────────────────────────────

define('APP_DIST_DIR', get_template_directory() . '/dist');
define('APP_DIST_URI', get_template_directory_uri() . '/dist/');

// Paths that should always resolve to root (dist/index.html)
define('APP_ROOT_ALIASES', ['', '__root__', 'index', 'index.php', 'index.html']);


// ─── 1. Rewrite Rules ────────────────────────────────────────────────────────

add_action('init', function () {
    // Catch root "/"
    add_rewrite_rule('^$', 'index.php?app_path=__root__', 'bottom');

    // Catch all sub-paths
    add_rewrite_rule('^(.+?)/?$', 'index.php?app_path=$matches[1]', 'bottom');
});


// ─── 2. Whitelist Query Var ──────────────────────────────────────────────────

add_filter('query_vars', function ($vars) {
    $vars[] = 'app_path';
    return $vars;
});


// ─── 3. Route Handler ────────────────────────────────────────────────────────

add_action('template_redirect', function () {
    $real_dist = realpath(APP_DIST_DIR);

    // Bail early if dist/ doesn't exist
    if (!$real_dist) {
        return;
    }

    $path = get_query_var('app_path', null);

    // Not our request — let WP handle it
    if ($path === null) {
        return;
    }

    // Normalize root aliases → empty string
    $path = in_array($path, APP_ROOT_ALIASES, true) ? '' : $path;

    // Sanitize: strip traversal attempts and backslashes
    $clean = ltrim($path, '/');
    $clean = str_replace(['..', '\\'], '', $clean);

    // Build candidate file list
    $candidates = $clean === ''
        ? [APP_DIST_DIR . '/index.html']
        : [
            APP_DIST_DIR . '/' . $clean . '/index.html',
            APP_DIST_DIR . '/' . $clean . '.html',
        ];

    foreach ($candidates as $file) {
        $real_file = realpath($file);

        // Security: ensure resolved path is inside dist/
        if (!$real_file || !str_starts_with($real_file, $real_dist . DIRECTORY_SEPARATOR)) {
            continue;
        }

        serve_dist_file($real_file, $real_dist);
        exit;
    }

    // No dist file matched — fall through to WordPress
});


// ─── Helpers ─────────────────────────────────────────────────────────────────

/**
 * Read and serve an HTML file from dist/, injecting a <base> tag.
 */
function serve_dist_file(string $real_file, string $real_dist): void {
    $html = file_get_contents($real_file);

    if ($html === false) {
        status_header(500);
        return;
    }

    // Inject <base> tag so relative assets resolve correctly
    $html = inject_base_tag($html, $real_file, $real_dist);

    status_header(200);
    header('Content-Type: text/html; charset=utf-8');
    header('X-Robots-Tag: noindex', true); // optional: prevent indexing of SPA routes
    echo $html;
}

/**
 * Inject <base href> pointing to the file's directory within dist/.
 * e.g. dist/about/index.html → base href = /dist/about/
 */
function inject_base_tag(string $html, string $real_file, string $real_dist): string {
    // Already has a <base> tag — don't double-inject
    if (stripos($html, '<base') !== false) {
        return $html;
    }

    // Compute relative path from dist/ to the file's directory
    $rel_dir = ltrim(str_replace($real_dist, '', dirname($real_file)), DIRECTORY_SEPARATOR);
    $base_url = APP_DIST_URI . ($rel_dir ? trailingslashit($rel_dir) : '');

    return str_replace(
        '<head>',
        '<head>' . PHP_EOL . '  <base href="' . esc_url($base_url) . '">',
        $html
    );
}