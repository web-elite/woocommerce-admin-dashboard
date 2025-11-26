<?php
// Hide header and footer and disable frontend styles/scripts
add_action('wp_enqueue_scripts', function () {
    // Disable all frontend styles and scripts
    global $wp_styles, $wp_scripts;

    // Remove all enqueued styles except our dashboard ones
    foreach ($wp_styles->queue as $handle) {
        if (!in_array($handle, ['inter-font', 'dashboard-style'])) {
            wp_dequeue_style($handle);
            wp_deregister_style($handle);
        }
    }

    // Remove all enqueued scripts except our dashboard ones
    foreach ($wp_scripts->queue as $handle) {
        if (!in_array($handle, ['jquery', 'jalali-datepicker', 'jquery-core', 'jquery-migrate'])) {
            wp_dequeue_script($handle);
            wp_deregister_script($handle);
        }
    }

    // Specifically disable WooCommerce scripts that might cause conflicts
    wp_dequeue_script('woocommerce');
    wp_dequeue_script('wc-cart-fragments');
    wp_dequeue_script('wc-checkout');
    wp_dequeue_script('wc-add-to-cart');
    wp_dequeue_script('wc-single-product');
    wp_dequeue_script('wc-cart');
    wp_dequeue_script('wc-order-attribution');
    wp_dequeue_script('wc-blocks-checkout');
    wp_dequeue_script('wc-blocks-registry');
    wp_dequeue_script('wc-price-format');
    wp_dequeue_script('wc-address-i18n');

    // Dequeue WooCommerce styles
    wp_dequeue_style('woocommerce-general');
    wp_dequeue_style('woocommerce-layout');
    wp_dequeue_style('woocommerce-smallscreen');
    wp_dequeue_style('woocommerce_frontend_styles');
    wp_dequeue_style('woocommerce_fancybox_styles');
    wp_dequeue_style('woocommerce_chosen_styles');
    wp_dequeue_style('woocommerce_prettyPhoto_css');

    // Also deregister them to be safe
    wp_deregister_script('woocommerce');
    wp_deregister_script('wc-cart-fragments');
    wp_deregister_script('wc-checkout');
    wp_deregister_script('wc-add-to-cart');
    wp_deregister_script('wc-single-product');
    wp_deregister_script('wc-cart');
    wp_deregister_script('wc-order-attribution');
    wp_deregister_script('wc-blocks-checkout');
    wp_deregister_script('wc-blocks-registry');
    wp_deregister_script('wc-price-format');
    wp_deregister_script('wc-address-i18n');

    wp_deregister_style('woocommerce-general');
    wp_deregister_style('woocommerce-layout');
    wp_deregister_style('woocommerce-smallscreen');
    wp_deregister_style('woocommerce_frontend_styles');
    wp_deregister_style('woocommerce_fancybox_styles');
    wp_deregister_style('woocommerce_chosen_styles');
    wp_deregister_style('woocommerce_prettyPhoto_css');
}, 999);

// Disable admin bar
add_filter('show_admin_bar', '__return_false');

// Prevent WooCommerce from loading on this page - AGGRESSIVE BLOCKING
add_action('init', function () {
    if (get_query_var('admin_dashboard')) {
        // Block WooCommerce at the earliest possible point
        if (!defined('WOOCOMMERCE_NO_SCRIPTS')) {
            define('WOOCOMMERCE_NO_SCRIPTS', true);
        }

        // Disable WooCommerce frontend completely
        add_filter('woocommerce_is_frontend_request', '__return_false');
        add_filter('woocommerce_is_active', '__return_false');

        // Prevent WooCommerce from initializing
        remove_action('init', 'woocommerce_init', 0);
        remove_action('init', 'woocommerce_loaded', 10);

        // Block all WooCommerce script enqueuing
        add_filter('woocommerce_enqueue_scripts', '__return_false');
        add_filter('woocommerce_enqueue_styles', '__return_empty_array');

        // Remove WooCommerce frontend hooks
        if (class_exists('WooCommerce')) {
            remove_action('wp_enqueue_scripts', [WC()->frontend, 'enqueue_scripts'], 999);
            remove_action('wp_enqueue_scripts', [WC()->frontend, 'enqueue_styles'], 999);
        }

        // Block WooCommerce Blocks
        if (class_exists('Automattic\\WooCommerce\\Blocks\\Package')) {
            remove_action('wp_enqueue_scripts', ['Automattic\\WooCommerce\\Blocks\\Package', 'enqueue_scripts']);
        }

        // Additional blocking at wp_loaded
        add_action('wp_loaded', function () {
            // Remove any WooCommerce scripts that might have been added
            global $wp_scripts, $wp_styles;

            if ($wp_scripts) {
                $scripts_to_remove = [];
                foreach ($wp_scripts->registered as $handle => $script) {
                    if (
                        strpos($handle, 'woocommerce') !== false ||
                        strpos($handle, 'wc-') !== false ||
                        strpos($handle, 'wc_') !== false ||
                        (isset($script->src) && strpos($script->src, 'woocommerce') !== false)
                    ) {
                        $scripts_to_remove[] = $handle;
                    }
                }
                foreach ($scripts_to_remove as $handle) {
                    wp_dequeue_script($handle);
                    wp_deregister_script($handle);
                }
            }

            if ($wp_styles) {
                $styles_to_remove = [];
                foreach ($wp_styles->registered as $handle => $style) {
                    if (
                        strpos($handle, 'woocommerce') !== false ||
                        strpos($handle, 'wc-') !== false ||
                        strpos($handle, 'wc_') !== false ||
                        (isset($style->src) && strpos($style->src, 'woocommerce') !== false)
                    ) {
                        $styles_to_remove[] = $handle;
                    }
                }
                foreach ($styles_to_remove as $handle) {
                    wp_dequeue_style($handle);
                    wp_deregister_style($handle);
                }
            }
        }, 1);

        // Block at template_redirect as well
        add_action('template_redirect', function () {
            if (get_query_var('admin_dashboard')) {
                // Final cleanup - remove any remaining WooCommerce scripts
                global $wp_scripts, $wp_styles;

                if ($wp_scripts) {
                    foreach ($wp_scripts->queue as $handle) {
                        if (
                            strpos($handle, 'woocommerce') !== false ||
                            strpos($handle, 'wc-') !== false ||
                            strpos($handle, 'wc_') !== false
                        ) {
                            wp_dequeue_script($handle);
                        }
                    }
                }

                if ($wp_styles) {
                    foreach ($wp_styles->queue as $handle) {
                        if (
                            strpos($handle, 'woocommerce') !== false ||
                            strpos($handle, 'wc-') !== false ||
                            strpos($handle, 'wc_') !== false
                        ) {
                            wp_dequeue_style($handle);
                        }
                    }
                }
            }
        }, 1);
    }
}, 0); // Priority 0 to run as early as possible

// Remove all actions that add content to wp_head and wp_footer
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
remove_action('wp_head', 'feed_links_extra', 3);
remove_action('wp_head', 'feed_links', 2);
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_head', 'wp_print_styles', 8);
remove_action('wp_head', 'wp_print_head_scripts', 9);
remove_action('wp_head', 'wp_enqueue_scripts', 1);
remove_action('wp_footer', 'wp_print_footer_scripts', 20);
remove_action('wp_head', 'wp_site_icon', 99);
remove_action('wp_head', 'wp_custom_css_cb', 101);

// Remove emoji scripts and styles
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
remove_action('wp_head', 'rest_output_link_wp_head', 10);
remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
remove_action('wp_head', 'wp_oembed_add_host_js');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo get_bloginfo('name'); ?> - داشبورد مدیریت</title>
    <link rel="icon" type="image/x-icon" href="<?= get_site_icon_url() ?>">

    <!-- IMMEDIATE WooCommerce Blocking - Before ANY other scripts -->
    <script>
        // Execute immediately to block WooCommerce before it loads
        (function() {
            'use strict';

            console.log('Dashboard: IMMEDIATE WooCommerce blocking initialized');

            // Block custom element definition IMMEDIATELY
            var originalDefine = customElements.define;
            customElements.define = function(name, constructor, options) {
                if (name === 'wc-order-attribution-inputs' || name.startsWith('wc-')) {
                    console.warn('Dashboard: IMMEDIATELY BLOCKING WooCommerce element:', name);
                    return false;
                }
                try {
                    return originalDefine.call(customElements, name, constructor, options);
                } catch (e) {
                    console.error('Dashboard: Custom element define error:', name, e);
                    return false;
                }
            };

            // Block script loading by URL
            var blockedUrls = ['woocommerce', 'wc-', 'd5ea49f26d0f1f6ee5c27113aae0c56d'];

            // Override script creation
            var originalCreateElement = document.createElement;
            document.createElement = function(tagName) {
                var element = originalCreateElement.call(document, tagName);
                if (tagName === 'script') {
                    element.setAttribute = function(name, value) {
                        if (name === 'src' && blockedUrls.some(url => value.includes(url))) {
                            console.log('Dashboard: BLOCKING script:', value);
                            return;
                        }
                        return HTMLElement.prototype.setAttribute.call(this, name, value);
                    };
                }
                return element;
            };

            // Block appendChild for scripts
            var originalAppendChild = Node.prototype.appendChild;
            Node.prototype.appendChild = function(child) {
                if (child.tagName === 'SCRIPT') {
                    var src = child.src || child.getAttribute('src');
                    if (src && blockedUrls.some(url => src.includes(url))) {
                        console.log('Dashboard: BLOCKING appended script:', src);
                        return child;
                    }
                }
                return originalAppendChild.call(this, child);
            };

        })();
    </script>

    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'vazirmatn': ['Vazirmatn', 'sans-serif']
                    }
                }
            }
        }
    </script>

    <!-- Vazirmatn Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <script src="https://unpkg.com/lightweight-charts@4.1.1/dist/lightweight-charts.standalone.production.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.tailwindcss.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.tailwindcss.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

    <!-- Jalali Datepicker -->
    <link rel="stylesheet" href="https://unpkg.com/@majidh1/jalalidatepicker/dist/jalalidatepicker.min.css">
    <script type="text/javascript" src="https://unpkg.com/@majidh1/jalalidatepicker/dist/jalalidatepicker.min.js"></script>

    <!-- Custom Dashboard Variables -->
    <script>
        var custom_dashboard = {
            ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('process_excel_upload'); ?>'
        };

        // Prevent custom element conflicts - Enhanced version
        (function() {
            // List of known WooCommerce custom elements that might conflict
            var wcElements = [
                'wc-order-attribution-inputs',
                'wc-order-attribution-input',
                'wc-blocks-checkout',
                'wc-checkout',
                'wc-cart',
                'wc-product-add-to-cart',
                'wc-price-format'
            ];

            // Store original define method
            var originalDefine = customElements.define;

            // Override customElements.define to prevent conflicts
            customElements.define = function(name, constructor, options) {
                if (customElements.get(name)) {
                    console.warn('Preventing redefinition of custom element:', name);
                    return;
                }

                // Check if this is a WooCommerce element we want to block
                if (wcElements.includes(name)) {
                    console.log('Blocking WooCommerce custom element:', name);
                    return;
                }

                return originalDefine.call(customElements, name, constructor, options);
            };

            // Also override get to handle already defined elements
            var originalGet = customElements.get;
            customElements.get = function(name) {
                try {
                    return originalGet.call(customElements, name);
                } catch (e) {
                    console.warn('Custom element get error for:', name, e);
                    return undefined;
                }
            };

            // Clean up any existing problematic elements
            wcElements.forEach(function(elementName) {
                try {
                    if (customElements.get(elementName)) {
                        console.log('Found existing custom element:', elementName);
                    }
                } catch (e) {
                    console.warn('Error checking custom element:', elementName, e);
                }
            });
        })();

        // Additional WooCommerce script blocking
        (function() {
            // Block WooCommerce AJAX and other scripts
            var blockedScripts = [
                'woocommerce',
                'wc-cart-fragments',
                'wc-checkout',
                'wc-add-to-cart',
                'wc-single-product',
                'wc-cart',
                'wc-order-attribution'
            ];

            // Override script loading
            var originalCreateElement = document.createElement;
            document.createElement = function(tagName) {
                var element = originalCreateElement.call(document, tagName);
                if (tagName.toLowerCase() === 'script') {
                    var originalSetAttribute = element.setAttribute;
                    element.setAttribute = function(name, value) {
                        if (name === 'src' && blockedScripts.some(function(script) {
                                return value.includes(script);
                            })) {
                            console.log('Blocking WooCommerce script:', value);
                            return;
                        }
                        return originalSetAttribute.call(this, name, value);
                    };
                }
                return element;
            };
        })();
    </script>
</head>

<body class="bg-gray-50 font-vazirmatn" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
    <style>
        header,
        footer,
        .wd-prefooter,
        div#wpadminbar,
        .admin-bar {
            display: none !important;
        }

        /* Loading states */
        .loading {
            opacity: 0.6;
            pointer-events: none;
            position: relative;
        }

        .loading::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #e5e7eb;
            border-top: 2px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .loading-overlay {
            position: relative;
        }

        .loading-overlay::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(2px);
            z-index: 10;
        }

        .loading-overlay::after {
            content: "در حال بارگذاری...";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(59, 130, 246, 0.9);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            z-index: 11;
        }

        /* Loading row state */
        .loading-row {
            opacity: 0.6;
            pointer-events: none;
            position: relative;
        }

        .loading-overlay-row {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(1px);
            z-index: 5;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .loading-overlay-row::after {
            content: "";
            width: 20px;
            height: 20px;
            border: 2px solid #e5e7eb;
            border-top: 2px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: translate(-50%, -50%) rotate(0deg);
            }

            100% {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        /* RTL Support for DataTables */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
            direction: <?php echo is_rtl() ? 'rtl' : 'ltr'; ?>;
        }

        .dataTables_wrapper .dataTables_filter input {
            margin-<?php echo is_rtl() ? 'right' : 'left'; ?>: 0.5rem;
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
        }

        .dataTables_wrapper .dataTables_length select {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            margin: 0 0.5rem;
        }

        .dataTables_wrapper table.dataTable thead th {
            text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
            border-bottom: 1px solid #e5e7eb;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            color: #374151;
        }

        .dataTables_wrapper table.dataTable tbody td {
            padding: 0.75rem 1.5rem;
            border-bottom: 1px solid #f3f4f6;
            text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.375rem 0.75rem;
            margin: 0 0.125rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            background: white;
            color: #374151;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #f3f4f6;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .dataTables_wrapper .dataTables_processing {
            text-align: center;
            padding: 1rem;
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 0.375rem;
            margin: 1rem 0;
        }

        /* Responsive table adjustments */
        @media (max-width: 768px) {

            .dataTables_wrapper table.dataTable thead th,
            .dataTables_wrapper table.dataTable tbody td {
                padding: 0.5rem;
                font-size: 0.875rem;
            }
        }

        div#orders-table_filter,
        div#orders-table_paginate,
        div#customers-table_filter,
        div#customers-table_paginate {
            display: inline-block !important;
        }

        div#orders-table_length,
        div#orders-table_info,
        div#customers-table_length,
        div#customers-table_info {
            width: 50% !important;
            display: inline-block !important;
            padding: 20px !important;
        }

        table#orders-table td,
        table#customers-table td {
            padding: 15px;
        }

        /* Sidebar transitions */
        .sidebar-transition {
            transition: all 0.3s ease-in-out;
        }

        /* Custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>

    <div class="flex h-screen bg-gray-50">
        <!-- Sidebar -->
        <div class="sidebar-transition <?php echo is_rtl() ? 'order-1' : 'order-2'; ?> w-64 bg-white shadow-lg border-<?php echo is_rtl() ? 'l' : 'r'; ?> border-gray-200 flex flex-col">
            <!-- Logo and Brand -->
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div class="<?php echo is_rtl() ? 'mr' : 'ml'; ?>-3">
                        <h1 class="text-lg font-bold text-gray-900"><?php echo get_bloginfo('name'); ?></h1>
                        <p class="text-xs text-gray-500">داشبورد مدیریت</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-6 space-y-2 custom-scrollbar overflow-y-auto">
                <a href="#" class="nav-item flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors" data-page="dashboard">
                    <svg class="w-5 h-5 <?php echo is_rtl() ? 'ml' : 'mr'; ?>-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                    </svg>
                    داشبورد
                </a>

                <a href="#" class="nav-item flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors" data-page="orders">
                    <svg class="w-5 h-5 <?php echo is_rtl() ? 'ml' : 'mr'; ?>-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    مدیریت سفارشات
                </a>

                <a href="#" class="nav-item flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors" data-page="analytics">
                    <svg class="w-5 h-5 <?php echo is_rtl() ? 'ml' : 'mr'; ?>-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    تحلیل و گزارشات
                </a>

                <a href="#" class="nav-item flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors" data-page="products">
                    <svg class="w-5 h-5 <?php echo is_rtl() ? 'ml' : 'mr'; ?>-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    مدیریت محصولات
                </a>

                <a href="#" class="nav-item flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors" data-page="customers">
                    <svg class="w-5 h-5 <?php echo is_rtl() ? 'ml' : 'mr'; ?>-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                    مشتریان
                </a>

                <a href="#" class="nav-item flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors" data-page="settings">
                    <svg class="w-5 h-5 <?php echo is_rtl() ? 'ml' : 'mr'; ?>-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    تنظیمات
                </a>
            </nav>

            <!-- User Profile Section -->
            <div class="p-4 border-t border-gray-200">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-blue-500 rounded-full flex items-center justify-center">
                        <span class="text-white font-semibold text-sm">
                            <?php echo substr(wp_get_current_user()->display_name, 0, 1); ?>
                        </span>
                    </div>
                    <div class="<?php echo is_rtl() ? 'mr' : 'ml'; ?>-3 flex-1">
                        <p class="text-sm font-medium text-gray-900"><?php echo wp_get_current_user()->display_name; ?></p>
                        <p class="text-xs text-gray-500"><?php echo wp_get_current_user()->user_email; ?></p>
                    </div>
                    <button onclick="logout()" class="p-2 text-gray-400 hover:text-red-500 transition-colors" title="خروج">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="sidebar-transition <?php echo is_rtl() ? 'order-2' : 'order-1'; ?> flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <button class="sidebar-toggle p-2 rounded-lg hover:bg-gray-100 transition-colors <?php echo is_rtl() ? 'ml' : 'mr'; ?>-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                        <h1 class="text-xl font-semibold text-gray-900 page-title">داشبورد مدیریت</h1>
                    </div>

                    <div class="flex items-center space-x-4 space-x-reverse">
                        <div class="text-sm text-gray-500">
                            <?php echo date_i18n('l, j F Y', current_time('timestamp')); ?>
                        </div>
                        <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.868 12.683A17.925 17.925 0 0112 21c7.962 0 12-1.21 12-2.683m-12 2.683a17.925 17.925 0 01-7.132-8.317M12 21c4.411 0 8-4.03 8-9s-3.589-9-8-9-8 4.03-8 9a9.06 9.06 0 001.832 5.683L4 21l4.868-8.317z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-6 custom-scrollbar">
                <!-- Dashboard Page -->
                <div class="page-content" id="dashboard-page">
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">خوش آمدید، <?php echo wp_get_current_user()->display_name; ?>! 👋</h2>
                        <p class="text-gray-600">نمای کلی از وضعیت فروشگاه شما</p>
                    </div>

                    <!-- Configuration Notice -->
                    <?php
                    $allowed_users = get_option('wc_admin_dashboard_allowed_users', array());
                    $current_user_id = get_current_user_id();
                    if (empty($allowed_users) || !in_array($current_user_id, $allowed_users)) {
                        echo '<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="mr-3">
                                    <h3 class="text-sm font-medium text-yellow-800">تنظیمات دسترسی</h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>برای مشاهده داده‌های داشبورد، ابتدا باید کاربران مجاز را در تنظیمات ووکامرس پیکربندی کنید.</p>
                                        <p class="mt-1"><a href="' . admin_url('admin.php?page=wc-settings&tab=wc_admin_dashboard') . '" class="font-medium underline text-yellow-700 hover:text-yellow-600">رفتن به تنظیمات ووکامرس ←</a></p>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    }
                    ?>

                    <!-- Dashboard Filters -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">دوره زمانی</label>
                                <select id="stats-period" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="all" selected>همه زمان‌ها</option>
                                    <option value="today">امروز</option>
                                    <option value="yesterday">دیروز</option>
                                    <option value="7">7 روز اخیر</option>
                                    <option value="30">یک ماه اخیر</option>
                                    <option value="custom">سفارشی</option>
                                </select>
                            </div>
                            <div class="custom-date-range hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">از تاریخ</label>
                                <input type="text" id="stats-start-date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="1403/01/01" data-jdp>
                            </div>
                            <div class="custom-date-range hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">تا تاریخ</label>
                                <input type="text" id="stats-end-date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="1403/12/29" data-jdp>
                            </div>
                            <div class="flex items-end">
                                <button id="apply-filters-btn" class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                    🔄 اعمال فیلتر
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                            <div class="flex items-center">
                                <div class="p-3 bg-blue-100 rounded-lg">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                    </svg>
                                </div>
                                <div class="<?php echo is_rtl() ? 'mr' : 'ml'; ?>-4">
                                    <p class="text-sm font-medium text-gray-600">کل سفارشات</p>
                                    <p class="text-2xl font-bold text-gray-900" id="total-orders">-</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                            <div class="flex items-center">
                                <div class="p-3 bg-green-100 rounded-lg">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="<?php echo is_rtl() ? 'mr' : 'ml'; ?>-4">
                                    <p class="text-sm font-medium text-gray-600">سفارشات تکمیل شده</p>
                                    <p class="text-2xl font-bold text-gray-900" id="completed-orders">-</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                            <div class="flex items-center">
                                <div class="p-3 bg-purple-100 rounded-lg">
                                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                </div>
                                <div class="<?php echo is_rtl() ? 'mr' : 'ml'; ?>-4">
                                    <p class="text-sm font-medium text-gray-600">مجموع فروش</p>
                                    <p class="text-2xl font-bold text-gray-900" id="total-revenue">-</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                            <div class="flex items-center">
                                <div class="p-3 bg-orange-100 rounded-lg">
                                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                    </svg>
                                </div>
                                <div class="<?php echo is_rtl() ? 'mr' : 'ml'; ?>-4">
                                    <p class="text-sm font-medium text-gray-600">متوسط سفارش</p>
                                    <p class="text-2xl font-bold text-gray-900" id="avg-order">-</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="grid lg:grid-cols-2 gap-6">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">نمودار فروش ماهانه</h3>
                            <div id="monthly-sales-chart" class="w-full h-80"></div>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">وضعیت سفارشات</h3>
                            <canvas id="order-status-chart" class="max-w-full h-auto"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Orders Management Page -->
                <div class="page-content hidden" id="orders-page">
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">مدیریت سفارشات</h2>
                        <p class="text-gray-600">مشاهده و مدیریت همه سفارشات فروشگاه</p>
                    </div>

                    <!-- Filters -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت سفارش</label>
                                <select id="manage-status-filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="all">همه وضعیت‌ها</option>
                                    <option value="processing,pending" selected>در حال انجام و در حال بررسی</option>
                                    <option value="processing">در حال انجام</option>
                                    <option value="pending">در حال بررسی</option>
                                    <option value="completed">تکمیل شده</option>
                                    <option value="cancelled">لغو شده</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">مرتب‌سازی</label>
                                <select id="manage-sort" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="date_desc">جدیدترین</option>
                                    <option value="date_asc">قدیمی‌ترین</option>
                                    <option value="total_desc">بالاترین مبلغ</option>
                                    <option value="total_asc">کمترین مبلغ</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">فیلتر تاریخ</label>
                                <select id="manage-date-filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="all">همه تاریخ‌ها</option>
                                    <option value="today">امروز</option>
                                    <option value="yesterday">دیروز</option>
                                    <option value="7">7 روز اخیر</option>
                                    <option value="30">یک ماه اخیر</option>
                                    <option value="custom">تاریخ خاص</option>
                                    <option value="range">بازه زمانی</option>
                                </select>
                            </div>
                            <div class="custom-date-single hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">تاریخ</label>
                                <input type="text" id="manage-single-date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="1403/01/01" data-jdp>
                            </div>
                            <div class="custom-date-range hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">از تاریخ</label>
                                <input type="text" id="manage-start-date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="1403/01/01" data-jdp>
                            </div>
                            <div class="custom-date-range hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">تا تاریخ</label>
                                <input type="text" id="manage-end-date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="1403/12/29" data-jdp>
                            </div>
                            <div class="flex items-end">
                                <button id="refresh-orders-btn" class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                    🔄 بروزرسانی
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Orders Table -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <table id="orders-table" class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-<?php echo is_rtl() ? 'right' : 'left'; ?> text-xs font-medium text-gray-500 uppercase tracking-wider">سفارش</th>
                                    <th class="px-6 py-3 text-<?php echo is_rtl() ? 'right' : 'left'; ?> text-xs font-medium text-gray-500 uppercase tracking-wider">آدرس</th>
                                    <th class="px-6 py-3 text-<?php echo is_rtl() ? 'right' : 'left'; ?> text-xs font-medium text-gray-500 uppercase tracking-wider">یادداشت</th>
                                    <th class="px-6 py-3 text-<?php echo is_rtl() ? 'right' : 'left'; ?> text-xs font-medium text-gray-500 uppercase tracking-wider">مجموع</th>
                                    <th class="px-6 py-3 text-<?php echo is_rtl() ? 'right' : 'left'; ?> text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                                    <th class="px-6 py-3 text-<?php echo is_rtl() ? 'right' : 'left'; ?> text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ</th>
                                    <th class="px-6 py-3 text-<?php echo is_rtl() ? 'right' : 'left'; ?> text-xs font-medium text-gray-500 uppercase tracking-wider">پرینت</th>
                                    <th class="px-6 py-3 text-<?php echo is_rtl() ? 'right' : 'left'; ?> text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <!-- داده‌ها توسط DataTables بارگذاری می‌شوند -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Analytics Page -->
                <div class="page-content hidden" id="analytics-page">
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">تحلیل و گزارشات</h2>
                        <p class="text-gray-600">گزارش‌های جامع از عملکرد فروشگاه</p>
                    </div>

                    <!-- Analytics Filters -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">دوره زمانی</label>
                                <select id="analytics-period" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="7">7 روز اخیر</option>
                                    <option value="30" selected>یک ماه اخیر</option>
                                    <option value="90">سه ماه اخیر</option>
                                    <option value="365">یک سال اخیر</option>
                                    <option value="custom">سفارشی</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">از تاریخ</label>
                                <input type="text" id="analytics-start-date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="1403/01/01">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">تا تاریخ</label>
                                <input type="text" id="analytics-end-date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="1403/12/29">
                            </div>
                            <div class="flex items-end">
                                <button id="refresh-analytics-btn" class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                    🔄 بروزرسانی
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Analytics Content -->
                    <div class="space-y-6">
                        <!-- Export Reports -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">خروجی گزارشات</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <button id="export-sales-report" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                                    📊 گزارش فروش
                                </button>
                                <button id="export-customers-report" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                    👥 گزارش مشتریان
                                </button>
                                <button id="export-products-report" class="inline-flex items-center justify-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                                    📦 گزارش محصولات
                                </button>
                            </div>
                        </div>

                        <!-- Revenue Overview -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">فروش ماهانه</h3>
                                <div id="monthly-revenue-chart" class="w-full h-64"></div>
                            </div>
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">فروش روزانه</h3>
                                <div id="daily-revenue-chart" class="w-full h-64"></div>
                            </div>
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">توزیع فروش</h3>
                                <canvas id="revenue-distribution-chart" class="max-w-full h-auto"></canvas>
                            </div>
                        </div>

                        <!-- Top Products & Categories -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">پرفروش‌ترین محصولات</h3>
                                <div class="space-y-4" id="top-products-list">
                                    <!-- Top products will be loaded here -->
                                </div>
                            </div>
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">آمار مشتریان</h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-blue-600" id="total-customers">-</div>
                                        <div class="text-sm text-gray-600">کل مشتریان</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-green-600" id="new-customers">-</div>
                                        <div class="text-sm text-gray-600">مشتریان جدید</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-purple-600" id="returning-customers">-</div>
                                        <div class="text-sm text-gray-600">مشتریان وفادار</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-orange-600" id="avg-customer-value">-</div>
                                        <div class="text-sm text-gray-600">میانگین ارزش مشتری</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Geographic & Performance Analytics -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">فروش بر اساس استان</h3>
                                <div class="space-y-3" id="province-sales-list">
                                    <!-- Province sales will be loaded here -->
                                </div>
                            </div>
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">عملکرد فروشگاه</h3>
                                <div class="space-y-4">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">نرخ تبدیل</span>
                                        <span class="text-sm font-medium" id="conversion-rate">-</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" id="conversion-rate-bar" style="width: 0%"></div>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">میانگین زمان پردازش</span>
                                        <span class="text-sm font-medium" id="avg-processing-time">-</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">نرخ بازگشت مشتریان</span>
                                        <span class="text-sm font-medium" id="customer-retention">-</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">رضایت مشتریان</span>
                                        <span class="text-sm font-medium" id="customer-satisfaction">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Page -->
                <div class="page-content hidden" id="products-page">
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">مدیریت محصولات</h2>
                        <p class="text-gray-600">درون‌ریزی و بروزرسانی محصولات</p>
                    </div>

                    <!-- Import Section -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                        <h3 class="text-xl font-semibold text-gray-900 mb-6">📥 راهنمای درون‌ریزی محصولات</h3>
                        <div class="grid md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <h4 class="font-medium text-gray-900 mb-3">📋 فرمت فایل اکسل:</h4>
                                <ul class="space-y-2 text-sm text-gray-600">
                                    <li><strong>نام محصول:</strong> نام محصول (الزامی)</li>
                                    <li><strong>قیمت:</strong> قیمت اصلی به تومان (الزامی، فقط عدد)</li>
                                    <li><strong>درصد تخفیف:</strong> درصد تخفیف برای قیمت فروش ویژه (اختیاری، 0-99)</li>
                                    <li><strong>موجودی انبار:</strong> تعداد موجودی (اختیاری، فقط عدد)</li>
                                </ul>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900 mb-3">⚠️ نکات مهم:</h4>
                                <ul class="space-y-2 text-sm text-gray-600">
                                    <li>اگر محصول با نام مشابه وجود داشته باشد، بروزرسانی می‌شود</li>
                                    <li>اگر محصول وجود نداشته باشد، محصول جدید ایجاد می‌شود</li>
                                    <li>قیمت فروش ویژه بر اساس درصد تخفیف محاسبه می‌شود</li>
                                    <li>برای حذف تخفیف، ستون درصد تخفیف را خالی بگذارید</li>
                                </ul>
                            </div>
                        </div>
                        <button id="download-sample-btn" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                            📄 دانلود فایل نمونه
                        </button>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <form id="upload-form" enctype="multipart/form-data" class="space-y-4">
                            <div>
                                <label for="excel-file" class="block text-sm font-medium text-gray-700 mb-2">📊 فایل اکسل را آپلود کنید:</label>
                                <input type="file" id="excel-file" name="excel_file" accept=".xlsx,.xls" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>
                            <button type="submit" class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-medium rounded-md hover:bg-green-700 transition-colors">
                                🚀 آپلود و بروزرسانی محصولات
                            </button>
                        </form>
                        <div id="result" class="mt-4"></div>
                    </div>
                </div>

                <!-- Customers Page -->
                <div class="page-content hidden" id="customers-page">
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">مدیریت مشتریان</h2>
                        <p class="text-gray-600">مشاهده و مدیریت مشتریان فروشگاه</p>
                    </div>

                    <!-- Customer Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <div class="flex items-center">
                                <div class="p-3 bg-blue-100 rounded-lg">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                    </svg>
                                </div>
                                <div class="<?php echo is_rtl() ? 'mr' : 'ml'; ?>-4">
                                    <p class="text-sm font-medium text-gray-600">کل مشتریان</p>
                                    <p class="text-2xl font-bold text-gray-900" id="customers-total">-</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <div class="flex items-center">
                                <div class="p-3 bg-green-100 rounded-lg">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                    </svg>
                                </div>
                                <div class="<?php echo is_rtl() ? 'mr' : 'ml'; ?>-4">
                                    <p class="text-sm font-medium text-gray-600">مشتریان جدید</p>
                                    <p class="text-2xl font-bold text-gray-900" id="customers-new">-</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <div class="flex items-center">
                                <div class="p-3 bg-purple-100 rounded-lg">
                                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                    </svg>
                                </div>
                                <div class="<?php echo is_rtl() ? 'mr' : 'ml'; ?>-4">
                                    <p class="text-sm font-medium text-gray-600">مشتریان وفادار</p>
                                    <p class="text-2xl font-bold text-gray-900" id="customers-loyal">-</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <div class="flex items-center">
                                <div class="p-3 bg-orange-100 rounded-lg">
                                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                </div>
                                <div class="<?php echo is_rtl() ? 'mr' : 'ml'; ?>-4">
                                    <p class="text-sm font-medium text-gray-600">میانگین خرید</p>
                                    <p class="text-2xl font-bold text-gray-900" id="customers-avg-order">-</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Filters -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">جستجو</label>
                                <input type="text" id="customers-search" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="نام، ایمیل، تلفن...">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">مرتب‌سازی</label>
                                <select id="customers-sort" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="name_asc">نام (الف تا ی)</option>
                                    <option value="name_desc">نام (ی تا الف)</option>
                                    <option value="orders_desc">بیشترین سفارش</option>
                                    <option value="orders_asc">کمترین سفارش</option>
                                    <option value="total_desc">بالاترین خرید</option>
                                    <option value="total_asc">کمترین خرید</option>
                                    <option value="date_desc">جدیدترین</option>
                                    <option value="date_asc">قدیمی‌ترین</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">فیلتر تاریخ</label>
                                <select id="customers-date-filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="all">همه تاریخ‌ها</option>
                                    <option value="30">یک ماه اخیر</option>
                                    <option value="90">سه ماه اخیر</option>
                                    <option value="365">یک سال اخیر</option>
                                    <option value="custom">سفارشی</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button id="refresh-customers-btn" class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                    🔄 بروزرسانی
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Customers Table -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <table id="customers-table" class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-<?php echo is_rtl() ? 'right' : 'left'; ?> text-xs font-medium text-gray-500 uppercase tracking-wider">مشتری</th>
                                    <th class="px-6 py-3 text-<?php echo is_rtl() ? 'right' : 'left'; ?> text-xs font-medium text-gray-500 uppercase tracking-wider">اطلاعات تماس</th>
                                    <th class="px-6 py-3 text-<?php echo is_rtl() ? 'right' : 'left'; ?> text-xs font-medium text-gray-500 uppercase tracking-wider">آمار سفارشات</th>
                                    <th class="px-6 py-3 text-<?php echo is_rtl() ? 'right' : 'left'; ?> text-xs font-medium text-gray-500 uppercase tracking-wider">مجموع خرید</th>
                                    <th class="px-6 py-3 text-<?php echo is_rtl() ? 'right' : 'left'; ?> text-xs font-medium text-gray-500 uppercase tracking-wider">آخرین سفارش</th>
                                    <th class="px-6 py-3 text-<?php echo is_rtl() ? 'right' : 'left'; ?> text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <!-- داده‌ها توسط DataTables بارگذاری می‌شوند -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Settings Page -->
                <div class="page-content hidden" id="settings-page">
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">تنظیمات</h2>
                        <p class="text-gray-600">مدیریت تنظیمات داشبورد</p>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <p class="text-gray-600">برای تغییر تنظیمات به <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=wc_admin_dashboard'); ?>" class="text-blue-600 hover:text-blue-800">صفحه تنظیمات ووکامرس</a> بروید.</p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modals -->
    <!-- Order Details Modal -->
    <div id="order-details-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex backdrop-blur-md items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center p-6 border-b border-gray-200">
                    <button class="close text-2xl text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <div id="order-details-content" class="p-6"></div>
            </div>
        </div>
    </div>

    <!-- Customer Details Modal -->
    <div id="customer-details-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex backdrop-blur-md items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">جزئیات مشتری</h3>
                    <button class="close text-2xl text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <div id="customer-details-content" class="p-6">
                    <!-- Customer details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard JavaScript -->
    <script src="<?php echo plugin_dir_url(__FILE__) . '../assets/js/dashboard.js'; ?>"></script>
</body>

</html>