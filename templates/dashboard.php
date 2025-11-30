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
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5">
    <path d="M6.53792 2.32172C6.69664 1.89276 7.30336 1.89276 7.46208 2.32172L8.1735 4.2443C8.27331 4.51403 8.48597 4.72669 8.7557 4.8265L10.6783 5.53792C11.1072 5.69664 11.1072 6.30336 10.6783 6.46208L8.7557 7.1735C8.48597 7.27331 8.27331 7.48597 8.1735 7.7557L7.46208 9.67828C7.30336 10.1072 6.69665 10.1072 6.53792 9.67828L5.8265 7.7557C5.72669 7.48597 5.51403 7.27331 5.2443 7.1735L3.32172 6.46208C2.89276 6.30336 2.89276 5.69665 3.32172 5.53792L5.2443 4.8265C5.51403 4.72669 5.72669 4.51403 5.8265 4.2443L6.53792 2.32172Z" />
    <path d="M14.4039 9.64136L15.8869 11.1244M6 22H7.49759C8.70997 22 9.31617 22 9.86124 21.7742C10.4063 21.5484 10.835 21.1198 11.6923 20.2625L19.8417 12.1131C20.3808 11.574 20.6503 11.3045 20.7944 11.0137C21.0685 10.4605 21.0685 9.81094 20.7944 9.25772C20.6503 8.96695 20.3808 8.69741 19.8417 8.15832C19.3026 7.61924 19.0331 7.3497 18.7423 7.20561C18.1891 6.93146 17.5395 6.93146 16.9863 7.20561C16.6955 7.3497 16.426 7.61924 15.8869 8.15832L7.73749 16.3077C6.8802 17.165 6.45156 17.5937 6.22578 18.1388C6 18.6838 6 19.29 6 20.5024V22Z" />
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
                <a href="#" class="gap-2 nav-item flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors" data-page="dashboard">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 11.9896V14.5C3 17.7998 3 19.4497 4.02513 20.4749C5.05025 21.5 6.70017 21.5 10 21.5H14C17.2998 21.5 18.9497 21.5 19.9749 20.4749C21 19.4497 21 17.7998 21 14.5V11.9896C21 10.3083 21 9.46773 20.6441 8.74005C20.2882 8.01237 19.6247 7.49628 18.2976 6.46411L16.2976 4.90855C14.2331 3.30285 13.2009 2.5 12 2.5C10.7991 2.5 9.76689 3.30285 7.70242 4.90855L5.70241 6.46411C4.37533 7.49628 3.71179 8.01237 3.3559 8.74005C3 9.46773 3 10.3083 3 11.9896Z" />
                        <path d="M15.0002 17C14.2007 17.6224 13.1504 18 12.0002 18C10.8499 18 9.79971 17.6224 9.00018 17" />
                    </svg>
                    داشبورد
                </a>

                <a href="#" class="gap-2 nav-item flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors" data-page="orders">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M13 3.5H11C7.70017 3.5 6.05025 3.5 5.02513 4.52513C4 5.55025 4 7.20017 4 10.5V15C4 18.2998 4 19.9497 5.02513 20.9749C6.05025 22 7.70017 22 11 22H14L20 16V10.5C20 7.20017 20 5.55025 18.9749 4.52513C17.9497 3.5 16.2998 3.5 13 3.5Z" />
                        <path d="M8 14H11.5M8 10H16" />
                        <path d="M20 16C17.1716 16 15.7574 16 14.8787 16.8787C14 17.7574 14 19.1716 14 22" />
                        <path d="M16.5 2V5M7.5 2V5M12 2V5" />
                    </svg>
                    مدیریت سفارشات
                </a>

                <a href="#" class="gap-2 nav-item flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors" data-page="analytics">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="square" stroke-linejoin="round">
                        <path d="M3.5 12.5V19.5C3.5 19.9659 3.5 20.1989 3.57612 20.3827C3.67761 20.6277 3.87229 20.8224 4.11732 20.9239C4.30109 21 4.53406 21 5 21C5.46594 21 5.69891 21 5.88268 20.9239C6.12771 20.8224 6.32239 20.6277 6.42388 20.3827C6.5 20.1989 6.5 19.9659 6.5 19.5V12.5C6.5 12.0341 6.5 11.8011 6.42388 11.6173C6.32239 11.3723 6.12771 11.1776 5.88268 11.0761C5.69891 11 5.46594 11 5 11C4.53406 11 4.30109 11 4.11732 11.0761C3.87229 11.1776 3.67761 11.3723 3.57612 11.6173C3.5 11.8011 3.5 12.0341 3.5 12.5Z" />
                        <path d="M10.5 14.5V19.4995C10.5 19.9654 10.5 20.1984 10.5761 20.3822C10.6776 20.6272 10.8723 20.8219 11.1173 20.9234C11.3011 20.9995 11.5341 20.9995 12 20.9995C12.4659 20.9995 12.6989 20.9995 12.8827 20.9234C13.1277 20.8219 13.3224 20.6272 13.4239 20.3822C13.5 20.1984 13.5 19.9654 13.5 19.4995V14.5C13.5 14.0341 13.5 13.8011 13.4239 13.6173C13.3224 13.3723 13.1277 13.1776 12.8827 13.0761C12.6989 13 12.4659 13 12 13C11.5341 13 11.3011 13 11.1173 13.0761C10.8723 13.1776 10.6776 13.3723 10.5761 13.6173C10.5 13.8011 10.5 14.0341 10.5 14.5Z" />
                        <path d="M17.5 10.5V19.5C17.5 19.9659 17.5 20.1989 17.5761 20.3827C17.6776 20.6277 17.8723 20.8224 18.1173 20.9239C18.3011 21 18.5341 21 19 21C19.4659 21 19.6989 21 19.8827 20.9239C20.1277 20.8224 20.3224 20.6277 20.4239 20.3827C20.5 20.1989 20.5 19.9659 20.5 19.5V10.5C20.5 10.0341 20.5 9.80109 20.4239 9.61732C20.3224 9.37229 20.1277 9.17761 19.8827 9.07612C19.6989 9 19.4659 9 19 9C18.5341 9 18.3011 9 18.1173 9.07612C17.8723 9.17761 17.6776 9.37229 17.5761 9.61732C17.5 9.80109 17.5 10.0341 17.5 10.5Z" />
                        <path d="M6.5 6.5C6.5 7.32843 5.82843 8 5 8C4.17157 8 3.5 7.32843 3.5 6.5C3.5 5.67157 4.17157 5 5 5C5.82843 5 6.5 5.67157 6.5 6.5Z" />
                        <path d="M20.5 4.5C20.5 5.32843 19.8284 6 19 6C18.1716 6 17.5 5.32843 17.5 4.5C17.5 3.67157 18.1716 3 19 3C19.8284 3 20.5 3.67157 20.5 4.5Z" />
                        <path d="M13.5 8.5C13.5 9.32843 12.8284 10 12 10C11.1716 10 10.5 9.32843 10.5 8.5C10.5 7.67157 11.1716 7 12 7C12.8284 7 13.5 7.67157 13.5 8.5Z" />
                        <path d="M6.44336 6.91199L10.558 8.08762M13.3033 7.75547L17.6981 5.24414" />
                    </svg>
                    تحلیل و گزارشات
                </a>

                <a href="#" class="gap-2 nav-item flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors" data-page="products">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22C11.1818 22 10.4002 21.6698 8.83693 21.0095C4.94564 19.3657 3 18.5438 3 17.1613C3 16.7742 3 10.0645 3 7M12 22C12.8182 22 13.5998 21.6698 15.1631 21.0095C19.0544 19.3657 21 18.5438 21 17.1613V7M12 22L12 11.3548" />
                        <path d="M8.32592 9.69138L5.40472 8.27785C3.80157 7.5021 3 7.11423 3 6.5C3 5.88577 3.80157 5.4979 5.40472 4.72215L8.32592 3.30862C10.1288 2.43621 11.0303 2 12 2C12.9697 2 13.8712 2.4362 15.6741 3.30862L18.5953 4.72215C20.1984 5.4979 21 5.88577 21 6.5C21 7.11423 20.1984 7.5021 18.5953 8.27785L15.6741 9.69138C13.8712 10.5638 12.9697 11 12 11C11.0303 11 10.1288 10.5638 8.32592 9.69138Z" />
                        <path d="M6 12L8 13" />
                        <path d="M17 4L7 9" />
                    </svg>
                    مدیریت محصولات
                </a>

                <a href="#" class="gap-2 nav-item flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors" data-page="import-products">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8 7C8 7 10.1958 4.28386 11.4044 3.23889C11.5987 3.0709 11.8169 2.99152 12.0337 3.00072C12.2282 3.00897 12.4215 3.08844 12.5958 3.23912C13.8041 4.28428 16 7 16 7M12.0337 4L12.0337 15" />
                        <path d="M8 11C6.59987 11 5.8998 11 5.36502 11.2725C4.89462 11.5122 4.51217 11.8946 4.27248 12.365C4 12.8998 4 13.5999 4 15V16C4 18.357 4 19.5355 4.73223 20.2678C5.46447 21 6.64298 21 9 21H15C17.357 21 18.5355 21 19.2678 20.2678C20 19.5355 20 18.357 20 16V15C20 13.5999 20 12.8998 19.7275 12.365C19.4878 11.8946 19.1054 11.5122 18.635 11.2725C18.1002 11 17.4001 11 16 11" />
                    </svg>
                    درون‌ریزی و برون‌بری
                </a>

                <a href="#" class="gap-2 nav-item flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors" data-page="customers">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M13 11C13 8.79086 11.2091 7 9 7C6.79086 7 5 8.79086 5 11C5 13.2091 6.79086 15 9 15C11.2091 15 13 13.2091 13 11Z" />
                        <path d="M11.0386 7.55773C11.0131 7.37547 11 7.18927 11 7C11 4.79086 12.7909 3 15 3C17.2091 3 19 4.79086 19 7C19 9.20914 17.2091 11 15 11C14.2554 11 13.5584 10.7966 12.9614 10.4423" />
                        <path d="M15 21C15 17.6863 12.3137 15 9 15C5.68629 15 3 17.6863 3 21" />
                        <path d="M21 17C21 13.6863 18.3137 11 15 11" />
                    </svg>
                    مشتریان
                </a>
            </nav>

            <!-- User Profile Section -->
            <div class="p-4 border-t border-gray-200">
                <div class="flex items-center gap-2">
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
                    <div class="flex items-center gap-2">
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
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">خوش آمدید، <?php echo wp_get_current_user()->display_name; ?>! <svg class="rounded-full shadow bg-white p-1 inline-block w-10 h-10 text-slate-600 rotate-y-full" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14.1245 5.74923C14.3983 4.99948 15.2302 4.6129 15.9825 4.88579C16.7348 5.15868 17.1227 5.98769 16.8489 6.73744L16.1878 8.5475M14.1245 5.74923L14.7855 3.93917C15.0594 3.18942 14.6715 2.3604 13.9192 2.08752C13.1668 1.81463 12.335 2.20121 12.0612 2.95096L11.5656 4.30857M14.1245 5.74923L12.3066 10.7269M11.5656 4.30857C11.839 3.55897 11.4511 2.73032 10.699 2.4575C9.94664 2.18461 9.11479 2.57119 8.84097 3.32094L6.04389 10.9791L5.1097 8.97429C4.69981 8.09467 3.61484 7.7678 2.78416 8.27368C2.14856 8.66075 1.85475 9.42786 2.06986 10.1386L3.81898 15.4859C4.15364 16.509 4.04527 17.8595 3.67597 18.8707M11.5656 4.30857L9.91291 8.83372M12.3032 22L12.6881 20.946C12.8639 20.4648 13.2266 20.0763 13.677 19.8297C14.1978 19.5445 14.8694 19.1322 15.2097 18.7412C15.7963 18.0673 16.1555 17.0838 16.8739 15.1169L18.9122 9.53572C19.186 8.78596 18.7981 7.95695 18.0458 7.68406C17.2935 7.41118 16.4616 7.79775 16.1878 8.5475M14.7004 12.6201L16.1878 8.5475" />
                                <path d="M20.8307 13C21.377 14.6354 20.5574 16.4263 19 17" />
                            </svg></h2>
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
                                <button id="apply-filters-btn" class="gap-2 w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20.4879 15C19.2524 18.4956 15.9187 21 12 21C7.02943 21 3 16.9706 3 12C3 7.02943 7.02943 3 12 3C15.7292 3 18.9286 5.26806 20.2941 8.5" />
                                        <path d="M15 9H18C19.4142 9 20.1213 9 20.5607 8.56066C21 8.12132 21 7.41421 21 6V3" />
                                    </svg> اعمال فیلتر
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                            <div class="flex items-center gap-2">
                                <div class="p-3 bg-blue-100 rounded-lg">
                                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12.5 22H10C6.70017 22 5.05025 22 4.02513 20.9749C3 19.9497 3 18.2998 3 15V11C3 9.11438 3 8.17157 3.58579 7.58579C4.17157 7 5.11438 7 7 7H15C16.8856 7 17.8284 7 18.4142 7.58579C19 8.17157 19 9.11438 19 11V13" />
                                        <path d="M15 9.5C15 5.63401 13.2091 2 11 2C8.79086 2 7 5.63401 7 9.5" />
                                        <path d="M17.5 22C17.5 22 14 19.8824 14 17.8333C14 16.8208 14.7368 16 15.75 16C16.275 16 16.8 16.1765 17.5 16.8824C18.2 16.1765 18.725 16 19.25 16C20.2632 16 21 16.8208 21 17.8333C21 19.8824 17.5 22 17.5 22Z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">کل سفارشات</p>
                                    <p class="text-2xl font-bold text-gray-900" id="total-orders">-</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                            <div class="flex items-center gap-2">
                                <div class="p-3 bg-green-100 rounded-lg">
                                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M10.5 20.25C10.5 20.6642 10.1642 21 9.75 21C9.33579 21 9 20.6642 9 20.25C9 19.8358 9.33579 19.5 9.75 19.5C10.1642 19.5 10.5 19.8358 10.5 20.25Z" />
                                        <path d="M19 20.25C19 20.6642 18.6642 21 18.25 21C17.8358 21 17.5 20.6642 17.5 20.25C17.5 19.8358 17.8358 19.5 18.25 19.5C18.6642 19.5 19 19.8358 19 20.25Z" />
                                        <path d="M2 3H2.20664C3.53124 3 4.19354 3 4.6255 3.40221C5.05746 3.80441 5.10464 4.46503 5.19902 5.78626L5.45035 9.30496C5.5924 11.2936 5.66342 12.2879 5.96476 13.0961C6.62531 14.8677 8.08229 16.2244 9.89648 16.757C10.7241 17 11.7267 17 13.7317 17C15.8373 17 16.89 17 17.7417 16.7416C19.6593 16.1599 21.1599 14.6593 21.7416 12.7417C22 11.89 22 10.8433 22 8.75M11.5 6H5.5" />
                                        <path d="M15 7C15 7 16 7 17 9C17 9 19.1765 4 22 3" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">سفارشات تکمیل شده</p>
                                    <p class="text-2xl font-bold text-gray-900" id="completed-orders">-</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                            <div class="flex items-center gap-2">
                                <div class="p-3 bg-purple-100 rounded-lg">
                                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14.4998 11C14.4998 12.3807 13.3805 13.5 11.9998 13.5C10.6191 13.5 9.49982 12.3807 9.49982 11C9.49982 9.61929 10.6191 8.5 11.9998 8.5C13.3805 8.5 14.4998 9.61929 14.4998 11Z" />
                                        <path d="M22 13V5.92705C22 5.35889 21.6756 4.84452 21.1329 4.67632C20.1903 4.38421 18.4794 4 16 4C11.4209 4 10.1967 5.67747 3.87798 4.42361C2.92079 4.23366 2 4.94531 2 5.92116V15.9382C2 16.6265 2.47265 17.231 3.1448 17.3792C8.39034 18.536 10.3316 17.7972 13 17.362" />
                                        <path d="M2 8C3.95133 8 5.70483 6.40507 5.92901 4.75417M18.5005 4.5C18.5005 6.53964 20.2655 8.46899 22 8.46899M6.00049 17.4961C6.00049 15.287 4.20963 13.4961 2.00049 13.4961" />
                                        <path d="M19 14V20M16 17H22" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">مجموع فروش</p>
                                    <p class="text-2xl font-bold text-gray-900" id="total-revenue">-</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                            <div class="flex items-center gap-2">
                                <div class="p-3 bg-orange-100 rounded-lg">
                                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M7 18V16M12 18V15M17 18V13M2.5 12C2.5 7.52166 2.5 5.28249 3.89124 3.89124C5.28249 2.5 7.52166 2.5 12 2.5C16.4783 2.5 18.7175 2.5 20.1088 3.89124C21.5 5.28249 21.5 7.52166 21.5 12C21.5 16.4783 21.5 18.7175 20.1088 20.1088C18.7175 21.5 16.4783 21.5 12 21.5C7.52166 21.5 5.28249 21.5 3.89124 20.1088C2.5 18.7175 2.5 16.4783 2.5 12Z" />
                                        <path d="M5.99219 11.4863C8.14729 11.5581 13.0341 11.2328 15.8137 6.82132M13.9923 6.28835L15.8678 5.98649C16.0964 5.95738 16.432 6.13785 16.5145 6.35298L17.0104 7.99142" />
                                    </svg>
                                </div>
                                <div>
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
                                <button id="refresh-orders-btn" class="w-full gap-2 inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20.4879 15C19.2524 18.4956 15.9187 21 12 21C7.02943 21 3 16.9706 3 12C3 7.02943 7.02943 3 12 3C15.7292 3 18.9286 5.26806 20.2941 8.5" />
                                        <path d="M15 9H18C19.4142 9 20.1213 9 20.5607 8.56066C21 8.12132 21 7.41421 21 6V3" />
                                    </svg>بروزرسانی
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
                                <button id="refresh-analytics-btn" class="gap-2 w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20.4879 15C19.2524 18.4956 15.9187 21 12 21C7.02943 21 3 16.9706 3 12C3 7.02943 7.02943 3 12 3C15.7292 3 18.9286 5.26806 20.2941 8.5" />
                                        <path d="M15 9H18C19.4142 9 20.1213 9 20.5607 8.56066C21 8.12132 21 7.41421 21 6V3" />
                                    </svg> بروزرسانی
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
                                    <svg class="w-4 h-4 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M18 20V10" />
                                        <path d="M12 20V4" />
                                        <path d="M6 20V14" />
                                    </svg> گزارش فروش
                                </button>
                                <button id="export-customers-report" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                    <svg class="w-4 h-4 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M15 20C15 17.2386 12.7614 15 10 15C7.23858 15 5 17.2386 5 20" />
                                        <path d="M12.1591 10.8388C12.3203 10.9622 12.5141 11.0435 12.7205 11.0714C13.343 11.1556 13.9231 10.7166 14.0073 10.0941C14.0915 9.47163 13.6525 8.89153 13.03 8.80732C12.8236 8.77941 12.6298 8.80732 12.4686 8.88341" />
                                        <path d="M10 11C7.23858 11 5 8.76142 5 6C5 3.23858 7.23858 1 10 1C12.7614 1 15 3.23858 15 6C15 6.94627 14.7372 7.82258 14.2837 8.56506" />
                                        <path d="M19 20C19 18.1591 17.5076 16.6667 15.6667 16.6667" />
                                        <path d="M16.6667 13.3333C18.5076 13.3333 20 11.8409 20 10C20 8.15905 18.5076 6.66666 16.6667 6.66666" />
                                    </svg> گزارش مشتریان
                                </button>
                                <button id="export-products-report" class="inline-flex items-center justify-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                                    <svg class="w-4 h-4 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 8L12 3L3 8" />
                                        <path d="M21 16L12 21L3 16" />
                                        <path d="M3 8V16" />
                                        <path d="M21 8V16" />
                                        <path d="M12 3V21" />
                                        <path d="M12 8L21 13" />
                                        <path d="M12 8L3 13" />
                                    </svg> گزارش محصولات
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
                        <p class="text-gray-600">لیست و مدیریت موجودی محصولات</p>
                    </div>

                    <!-- Product List Section -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-semibold text-gray-900">لیست محصولات</h3>
                            <button id="refresh-products-btn" class="gap-2 inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20.4879 15C19.2524 18.4956 15.9187 21 12 21C7.02943 21 3 16.9706 3 12C3 7.02943 7.02943 3 12 3C15.7292 3 18.9286 5.26806 20.2941 8.5" />
                                    <path d="M15 9H18C19.4142 9 20.1213 9 20.5607 8.56066C21 8.12132 21 7.41421 21 6V3" />
                                </svg>
                                بروزرسانی
                            </button>
                        </div>

                        <div class="overflow-hidden">
                            <table id="products-table" class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-<?php echo is_rtl() ? 'right' : 'left'; ?> text-xs font-medium text-gray-500 uppercase tracking-wider">شناسه</th>
                                        <th class="px-6 py-3 text-<?php echo is_rtl() ? 'right' : 'left'; ?> text-xs font-medium text-gray-500 uppercase tracking-wider">تصویر</th>
                                        <th class="px-6 py-3 text-<?php echo is_rtl() ? 'right' : 'left'; ?> text-xs font-medium text-gray-500 uppercase tracking-wider">نام محصول</th>
                                        <th class="px-6 py-3 text-<?php echo is_rtl() ? 'right' : 'left'; ?> text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                                        <th class="px-6 py-3 text-<?php echo is_rtl() ? 'right' : 'left'; ?> text-xs font-medium text-gray-500 uppercase tracking-wider">قیمت</th>
                                        <th class="px-6 py-3 text-<?php echo is_rtl() ? 'right' : 'left'; ?> text-xs font-medium text-gray-500 uppercase tracking-wider">موجودی</th>
                                        <th class="px-6 py-3 text-<?php echo is_rtl() ? 'right' : 'left'; ?> text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <!-- Data loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Import/Export Products Page -->
                <div class="page-content hidden" id="import-products-page">
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">درون‌ریزی و برون‌بری محصولات</h2>
                        <p class="text-gray-600">مدیریت گروهی محصولات با فایل اکسل</p>
                    </div>

                    <!-- Tabs -->
                    <div class="flex border-b border-gray-200 mb-6">
                        <button class="ie-tab-btn active px-6 py-3 text-blue-600 border-b-2 border-blue-600 font-medium focus:outline-none" data-tab="import">درون‌ریزی (Import)</button>
                        <button class="ie-tab-btn px-6 py-3 text-gray-500 hover:text-gray-700 font-medium focus:outline-none" data-tab="export">برون‌بری (Export)</button>
                    </div>

                    <!-- Import Section -->
                    <div id="import-tab-content" class="ie-tab-content">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                            <h3 class="text-xl font-semibold text-gray-900 mb-6"><svg class="inline-block w-6 h-6 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 3V15" />
                                    <path d="M16 11L12 15L8 11" />
                                    <path d="M20 16V19C20 20.1046 19.1046 21 18 21H6C4.89543 21 4 20.1046 4 19V16" />
                                </svg> راهنمای درون‌ریزی محصولات</h3>
                            <div class="grid md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <h4 class="font-medium text-gray-900 mb-3"><svg class="inline-block w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M8 5H6C4.89543 5 4 5.89543 4 7V19C4 20.1046 4.89543 21 6 21H18C19.1046 21 20 20.1046 20 19V7C20 5.89543 19.1046 5 18 5H16" />
                                            <path d="M8 5C8 3.89543 8.89543 3 10 3H14C15.1046 3 16 3.89543 16 5V7H8V5Z" />
                                        </svg> فرمت فایل اکسل:</h4>
                                    <ul class="space-y-2 text-sm text-gray-600">
                                        <li><strong>نام محصول:</strong> نام محصول (الزامی)</li>
                                        <li><strong>قیمت:</strong> قیمت اصلی به تومان (الزامی، فقط عدد)</li>
                                        <li><strong>درصد تخفیف:</strong> درصد تخفیف برای قیمت فروش ویژه (اختیاری، 0-99)</li>
                                        <li><strong>موجودی انبار:</strong> تعداد موجودی (اختیاری، فقط عدد)</li>
                                    </ul>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900 mb-3"><svg class="inline-block w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" />
                                            <path d="M12 8V13" />
                                            <path d="M12 16H12.01" />
                                        </svg> نکات مهم:</h4>
                                    <ul class="space-y-2 text-sm text-gray-600">
                                        <li>اگر محصول با نام مشابه وجود داشته باشد، بروزرسانی می‌شود</li>
                                        <li>اگر محصول وجود نداشته باشد، محصول جدید ایجاد می‌شود</li>
                                        <li>قیمت فروش ویژه بر اساس درصد تخفیف محاسبه می‌شود</li>
                                        <li>برای حذف تخفیف، ستون درصد تخفیف را خالی بگذارید</li>
                                    </ul>
                                </div>
                            </div>
                            <button id="download-sample-btn" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                <svg class="w-4 h-4 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M13 2H6C4.89543 2 4 2.89543 4 4V20C4 21.1046 4.89543 22 6 22H18C19.1046 22 20 21.1046 20 20V9L13 2Z" />
                                    <path d="M13 2V9H20" />
                                </svg> دانلود فایل نمونه
                            </button>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <form id="upload-form" enctype="multipart/form-data" class="space-y-4">
                                <div>
                                    <label for="excel-file" class="block text-sm font-medium text-gray-700 mb-2"><svg class="inline-block w-4 h-4 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M13 2H6C4.89543 2 4 2.89543 4 4V20C4 21.1046 4.89543 22 6 22H18C19.1046 22 20 21.1046 20 20V9L13 2Z" />
                                            <path d="M13 2V9H20" />
                                        </svg> فایل اکسل را آپلود کنید:</label>
                                    <input type="file" id="excel-file" name="excel_file" accept=".xlsx,.xls" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                </div>
                                <button type="submit" class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-medium rounded-md hover:bg-green-700 transition-colors">
                                    <svg class="w-4 h-4 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 2C12 2 12 12 12 12C12 12 16.5 14 16.5 14C16.5 14 21 12 21 12C21 12 19 2 12 2Z" />
                                        <path d="M12 2C12 2 12 12 12 12C12 12 7.5 14 7.5 14C7.5 14 3 12 3 12C3 12 5 2 12 2Z" />
                                        <path d="M12 14V22" />
                                        <path d="M7.5 14L9 17" />
                                        <path d="M16.5 14L15 17" />
                                    </svg> آپلود و بروزرسانی محصولات
                                </button>
                            </form>
                            <div id="result" class="mt-4"></div>
                        </div>
                    </div>

                    <!-- Export Section -->
                    <div id="export-tab-content" class="ie-tab-content hidden">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-xl font-semibold text-gray-900 mb-6"><svg class="inline-block w-6 h-6 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 15V3" />
                                    <path d="M16 7L12 3L8 7" />
                                    <path d="M20 16V19C20 20.1046 19.1046 21 18 21H6C4.89543 21 4 20.1046 4 19V16" />
                                </svg> برون‌بری محصولات</h3>

                            <form id="export-products-form" class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <!-- Category Filter -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">دسته‌بندی</label>
                                        <select id="export-category" name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="all">همه دسته‌بندی‌ها</option>
                                            <!-- Categories will be loaded via AJAX -->
                                        </select>
                                    </div>

                                    <!-- Stock Status Filter -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت موجودی</label>
                                        <select id="export-stock-status" name="stock_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="all">همه وضعیت‌ها</option>
                                            <option value="instock">موجود</option>
                                            <option value="outofstock">ناموجود</option>
                                            <option value="onbackorder">پیش‌خرید</option>
                                        </select>
                                    </div>

                                    <!-- Search Filter -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">جستجو (نام یا SKU)</label>
                                        <input type="text" id="export-search" name="search" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="نام محصول یا کد...">
                                    </div>
                                </div>

                                <div class="pt-4 border-t border-gray-200">
                                    <button type="submit" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition-colors">
                                        <svg class="w-4 h-4 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 3V15" />
                                            <path d="M16 11L12 15L8 11" />
                                            <path d="M20 16V19C20 20.1046 19.1046 21 18 21H6C4.89543 21 4 20.1046 4 19V16" />
                                        </svg> دریافت فایل اکسل محصولات
                                    </button>
                                </div>
                            </form>
                            <div id="export-products-result" class="mt-4"></div>
                        </div>
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
                            <div class="flex items-center gap-2">
                                <div class="p-3 bg-blue-100 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" color="#000000" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M15.5 11C15.5 9.067 13.933 7.5 12 7.5C10.067 7.5 8.5 9.067 8.5 11C8.5 12.933 10.067 14.5 12 14.5C13.933 14.5 15.5 12.933 15.5 11Z" />
                                        <path d="M15.4827 11.3499C15.8047 11.4475 16.1462 11.5 16.5 11.5C18.433 11.5 20 9.933 20 8C20 6.067 18.433 4.5 16.5 4.5C14.6851 4.5 13.1928 5.8814 13.0173 7.65013" />
                                        <path d="M10.9827 7.65013C10.8072 5.8814 9.31492 4.5 7.5 4.5C5.567 4.5 4 6.067 4 8C4 9.933 5.567 11.5 7.5 11.5C7.85381 11.5 8.19535 11.4475 8.51727 11.3499" />
                                        <path d="M22 16.5C22 13.7386 19.5376 11.5 16.5 11.5" />
                                        <path d="M17.5 19.5C17.5 16.7386 15.0376 14.5 12 14.5C8.96243 14.5 6.5 16.7386 6.5 19.5" />
                                        <path d="M7.5 11.5C4.46243 11.5 2 13.7386 2 16.5" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">کل مشتریان</p>
                                    <p class="text-2xl font-bold text-gray-900" id="customers-total">-</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <div class="flex items-center gap-2">
                                <div class="p-3 bg-green-100 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" color="#000000" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 8.5C14 5.73858 11.7614 3.5 9 3.5C6.23858 3.5 4 5.73858 4 8.5C4 11.2614 6.23858 13.5 9 13.5C11.7614 13.5 14 11.2614 14 8.5Z" />
                                        <path d="M16 20.5C16 16.634 12.866 13.5 9 13.5C5.13401 13.5 2 16.634 2 20.5" />
                                        <path d="M19 9V15M22 12L16 12" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">مشتریان جدید</p>
                                    <p class="text-2xl font-bold text-gray-900" id="customers-new">-</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <div class="flex items-center gap-2">
                                <div class="p-3 bg-purple-100 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" color="#000000" fill="none" stroke="#141B34" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14.5 7.5C14.5 4.73858 12.2614 2.5 9.5 2.5C6.73858 2.5 4.5 4.73858 4.5 7.5C4.5 10.2614 6.73858 12.5 9.5 12.5C12.2614 12.5 14.5 10.2614 14.5 7.5Z" />
                                        <path d="M2.5 19.5C2.5 15.634 5.63401 12.5 9.5 12.5C10.5736 12.5 11.5907 12.7417 12.5 13.1736" />
                                        <path d="M17.5 21.5C17.5 21.5 21.5 19.6471 21.5 16.6389C21.5 15.4576 20.6579 14.5 19.5 14.5C18.5526 14.5 17.9211 14.9118 17.5 15.7353C17.0789 14.9118 16.4474 14.5 15.5 14.5C14.3421 14.5 13.5 15.4576 13.5 16.6389C13.5 19.6471 17.5 21.5 17.5 21.5Z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">مشتریان وفادار</p>
                                    <p class="text-2xl font-bold text-gray-900" id="customers-loyal">-</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <div class="flex items-center gap-2">
                                <div class="p-3 bg-orange-100 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" color="#000000" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                                        <path d="M21 21H10C6.70017 21 5.05025 21 4.02513 19.9749C3 18.9497 3 17.2998 3 14V3" />
                                        <path d="M6 12H6.00898M8.9982 12H9.00718M11.9964 12H12.0054M14.9946 12H15.0036M17.9928 12H18.0018M20.991 12H21" />
                                        <path d="M6 7C6.67348 5.87847 7.58712 5 8.99282 5C14.9359 5 11.5954 17 17.9819 17C19.3976 17 20.3057 16.1157 21 15" />
                                    </svg>
                                </div>
                                <div>
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
                                <button id="refresh-customers-btn" class="gap-2 w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" color="currentColor" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20.4879 15C19.2524 18.4956 15.9187 21 12 21C7.02943 21 3 16.9706 3 12C3 7.02943 7.02943 3 12 3C15.7292 3 18.9286 5.26806 20.2941 8.5" />
                                        <path d="M15 9H18C19.4142 9 20.1213 9 20.5607 8.56066C21 8.12132 21 7.41421 21 6V3" />
                                    </svg>
                                    بروزرسانی
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

    <!-- Edit Product Modal -->
    <div id="edit-product-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex backdrop-blur-md items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="flex justify-between items-center p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">ویرایش سریع محصول</h3>
                    <button class="close-modal text-2xl text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <form id="edit-product-form" class="p-6 space-y-4">
                    <input type="hidden" id="edit-product-id" name="product_id">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">نام محصول</label>
                        <input type="text" id="edit-product-name" class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-500" disabled>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">قیمت عادی (تومان)</label>
                            <input type="number" id="edit-product-regular-price" name="regular_price" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">قیمت فروش ویژه</label>
                            <input type="number" id="edit-product-sale-price" name="sale_price" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">موجودی انبار</label>
                        <input type="number" id="edit-product-stock" name="stock_quantity" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="pt-4 flex justify-end space-x-3 space-x-reverse">
                        <button type="button" class="close-modal px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">انصراف</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">ذخیره تغییرات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Dashboard JavaScript -->
    <script src="<?php echo plugin_dir_url(__FILE__) . '../assets/js/dashboard.js'; ?>"></script>
    <link rel="stylesheet" href="<?php echo plugin_dir_url(__FILE__) . '../assets/css/dashboard.css'; ?>">
</body>

</html>