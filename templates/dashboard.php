<?php
// Hide header and footer and disable frontend styles/scripts
add_action('wp_enqueue_scripts', function() {
    // Disable all frontend styles and scripts
    global $wp_styles, $wp_scripts;

    // Remove all enqueued styles except our dashboard ones
    foreach ($wp_styles->queue as $handle) {
        if (!in_array($handle, ['inter-font', 'dashboard-css'])) {
            wp_dequeue_style($handle);
            wp_deregister_style($handle);
        }
    }

    // Remove all enqueued scripts except our dashboard ones
    foreach ($wp_scripts->queue as $handle) {
        if (!in_array($handle, ['jquery', 'jquery-core', 'jquery-migrate'])) {
            wp_dequeue_script($handle);
            wp_deregister_script($handle);
        }
    }
}, 999);

// Disable admin bar
add_filter('show_admin_bar', '__return_false');

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
    <title>بروزرسانی محصولات</title>

    <!-- PicoCSS -->
    <link rel="stylesheet" href="https://unpkg.com/@picocss/pico@latest/css/pico.min.css">

    <!-- Vazir Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazir:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Custom Dashboard Styles -->
    <style>
        :root {
            --pico-font-family: 'Vazir', sans-serif;
            --pico-primary: #667eea;
            --pico-secondary: #764ba2;
        }

        body {
            font-family: 'Vazir', sans-serif;
            direction: rtl;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .dashboard-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--pico-muted-border-color);
        }

        .tab-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            background: var(--pico-background-color);
            color: var(--pico-color);
            cursor: pointer;
            border-radius: var(--pico-border-radius) var(--pico-border-radius) 0 0;
            transition: all 0.2s ease;
        }

        .tab-btn.active {
            background: var(--pico-primary-background);
            color: var(--pico-primary-inverse);
            border-bottom: 2px solid var(--pico-primary);
        }

        .tab-btn:hover {
            background: var(--pico-hover-background-color);
        }

        .tab-content {
            display: none;
            padding: 2rem 0;
        }

        .tab-content.active {
            display: block;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }

        .stat-card {
            text-align: center;
            padding: 2rem;
            border-radius: var(--pico-border-radius);
            background: var(--pico-card-background-color);
            border: var(--pico-border-width) solid var(--pico-card-border-color);
        }

        .stat-card h2 {
            margin-bottom: 1rem;
            color: var(--pico-muted-color);
            font-size: 1rem;
        }

        .stat-card span {
            font-size: 2rem;
            font-weight: bold;
            color: var(--pico-color);
        }

        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .chart-wrapper {
            padding: 1.5rem;
            border-radius: var(--pico-border-radius);
            background: var(--pico-card-background-color);
            border: var(--pico-border-width) solid var(--pico-card-border-color);
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th,
        .orders-table td {
            padding: 0.75rem;
            text-align: right;
            border-bottom: 1px solid var(--pico-table-border-color);
        }

        .orders-table th {
            background: var(--pico-table-row-stripe-background);
            font-weight: 600;
        }

        .status-processing { color: #059669; }
        .status-completed { color: #dc2626; }
        .status-on-hold { color: #d97706; }
        .status-cancelled { color: #7f1d1d; }

        .print-column {
            white-space: nowrap;
            text-align: center;
        }

        .print-btn {
            display: inline-block;
            margin: 0 2px;
            padding: 4px 8px;
            color: white;
            border-radius: 3px;
            font-size: 11px;
            text-decoration: none;
            transition: opacity 0.2s ease;
        }

        .thermal-print { background: #ff64b1; }
        .label-print { background: #52cbbf; }
        .invoice-print { background: #98b4c7; }

        .print-btn:hover {
            opacity: 0.8;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: var(--pico-border-radius);
            width: 90%;
            max-width: 700px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .close {
            float: left;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: var(--pico-muted-color);
        }

        .close:hover {
            color: var(--pico-color);
        }

        .order-details-grid {
            display: grid;
            gap: 1.5rem;
        }

        .order-info-section,
        .customer-info-section,
        .items-section,
        .payment-section,
        .notes-section,
        .order-notes-section {
            padding: 1.5rem;
            border-radius: var(--pico-border-radius);
            background: var(--pico-background-color);
            border: var(--pico-border-width) solid var(--pico-muted-border-color);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .order-items {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .order-item {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1rem;
            padding: 1rem;
            background: var(--pico-hover-background-color);
            border-radius: var(--pico-border-radius);
        }

        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .page-btn {
            padding: 0.5rem 1rem;
            border: var(--pico-border-width) solid var(--pico-muted-border-color);
            background: var(--pico-background-color);
            color: var(--pico-color);
            cursor: pointer;
            border-radius: var(--pico-border-radius);
            transition: all 0.2s ease;
        }

        .page-btn:hover {
            background: var(--pico-hover-background-color);
        }

        .page-btn.active {
            background: var(--pico-primary-background);
            color: var(--pico-primary-inverse);
            border-color: var(--pico-primary);
        }
    </style>

    <script src="https://unpkg.com/lightweight-charts@4.1.1/dist/lightweight-charts.standalone.production.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
    </style>

<body>
    <main class="container dashboard-container">
        <h1>🎯 داشبورد ادمین سفارشی</h1>
        <p class="welcome-text">خوش آمدید، <?php echo wp_get_current_user()->display_name; ?>! 👋</p>

        <nav class="dashboard-tabs">
            <button class="tab-btn active" data-tab="import">📥 درون‌ریزی محصولات</button>
            <button class="tab-btn" data-tab="orders">📊 گزارش سفارشات</button>
            <button class="tab-btn" data-tab="manage">⚙️ مدیریت سفارشات</button>
            <button class="tab-btn" data-tab="export">📤 خروجی اکسل</button>
        </nav>

        <!-- تب درون‌ریزی محصولات -->
        <section id="import-tab" class="tab-content active">
            <article class="import-instructions">
                <h3>📥 راهنمای درون‌ریزی محصولات</h3>
                <div class="grid instructions-content">
                    <div class="instruction-step">
                        <h4>📋 فرمت فایل اکسل:</h4>
                        <ul>
                            <li><strong>نام محصول:</strong> نام محصول (الزامی)</li>
                            <li><strong>قیمت:</strong> قیمت اصلی به تومان (الزامی، فقط عدد)</li>
                            <li><strong>درصد تخفیف:</strong> درصد تخفیف برای قیمت فروش ویژه (اختیاری، 0-99)</li>
                            <li><strong>موجودی انبار:</strong> تعداد موجودی (اختیاری، فقط عدد)</li>
                        </ul>
                    </div>
                    <div class="instruction-step">
                        <h4>⚠️ نکات مهم:</h4>
                        <ul>
                            <li>اگر محصول با نام مشابه وجود داشته باشد، بروزرسانی می‌شود</li>
                            <li>اگر محصول وجود نداشته باشد، محصول جدید ایجاد می‌شود</li>
                            <li>قیمت فروش ویژه بر اساس درصد تخفیف محاسبه می‌شود</li>
                            <li>برای حذف تخفیف، ستون درصد تخفیف را خالی بگذارید</li>
                        </ul>
                    </div>
                </div>
                <button id="download-sample-btn" class="sample-btn secondary">📄 دانلود فایل نمونه</button>
            </article>

            <form id="upload-form" enctype="multipart/form-data">
                <label for="excel-file">📊 فایل اکسل را آپلود کنید:</label>
                <input type="file" id="excel-file" name="excel_file" accept=".xlsx,.xls" required>
                <button type="submit" class="primary">🚀 آپلود و بروزرسانی محصولات</button>
            </form>
            <div id="result"></div>
        </section>

        <!-- تب گزارش سفارشات -->
        <section id="orders-tab" class="tab-content">
            <article class="orders-stats">
                <h3>📈 آمار سفارشات</h3>

                <!-- فیلترهای زمانی -->
                <div class="time-filters grid">
                    <div class="filter-group">
                        <label for="stats-period">دوره زمانی:</label>
                        <select id="stats-period">
                            <option value="today">امروز</option>
                            <option value="yesterday">دیروز</option>
                            <option value="7">7 روز اخیر</option>
                            <option value="30" selected>یک ماه اخیر</option>
                            <option value="90">سه ماه اخیر</option>
                            <option value="365">یک سال اخیر</option>
                            <option value="custom">سفارشی</option>
                        </select>
                    </div>
                    <button id="apply-filters-btn" class="refresh-btn secondary">🔄 اعمال فیلتر</button>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <h2>کل سفارشات</h2>
                        <span id="total-orders">-</span>
                    </div>
                    <div class="stat-card">
                        <h2>سفارشات تکمیل شده</h2>
                        <span id="completed-orders">-</span>
                    </div>
                    <div class="stat-card">
                        <h2>مجموع فروش</h2>
                        <span id="total-revenue">-</span>
                    </div>
                    <div class="stat-card">
                        <h2>متوسط سفارش</h2>
                        <span id="avg-order">-</span>
                    </div>
                </div>
            </article>

            <div class="charts-container">
                <div class="chart-wrapper">
                    <h4>نمودار فروش ماهانه</h4>
                    <div id="monthly-sales-chart" style="width: 100%; height: 400px;"></div>
                </div>
                <div class="chart-wrapper">
                    <h4>وضعیت سفارشات</h4>
                    <canvas id="order-status-chart"></canvas>
                </div>
            </div>
        </section>

        <!-- تب مدیریت سفارشات -->
        <section id="manage-tab" class="tab-content">
            <article class="order-management">
                <h3>⚙️ مدیریت سفارشات</h3>

                <!-- فیلترها -->
                <div class="filters-section grid">
                    <div class="filter-group">
                        <label for="manage-status-filter">وضعیت سفارش:</label>
                        <select id="manage-status-filter">
                            <option value="all">همه وضعیت‌ها</option>
                            <option value="processing,pending" selected>در حال انجام و در حال بررسی</option>
                            <option value="processing">در حال انجام</option>
                            <option value="pending">در حال بررسی</option>
                            <option value="completed">تکمیل شده</option>
                            <option value="cancelled">لغو شده</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="manage-sort">مرتب‌سازی:</label>
                        <select id="manage-sort">
                            <option value="date_desc">جدیدترین</option>
                            <option value="date_asc">قدیمی‌ترین</option>
                            <option value="total_desc">بالاترین مبلغ</option>
                            <option value="total_asc">کمترین مبلغ</option>
                        </select>
                    </div>
                    <button id="refresh-orders-btn" class="refresh-btn secondary">🔄 بروزرسانی</button>
                </div>

                <!-- جدول مدیریت سفارشات -->
                <div id="manage-orders-table"></div>

                <!-- مدال جزئیات سفارش -->
                <div id="order-details-modal" class="modal">
                    <div class="modal-content">
                        <button class="close" aria-label="Close">&times;</button>
                        <div id="order-details-content"></div>
                    </div>
                </div>
            </article>
        </section>

        <!-- تب خروجی اکسل -->
        <section id="export-tab" class="tab-content">
            <h3>📤 خروجی اکسل سفارشات</h3>
            <form id="export-form">
                <div class="export-options grid">
                    <div class="option-group">
                        <label for="export-period">بازه زمانی:</label>
                        <select id="export-period">
                            <option value="7">7 روز اخیر</option>
                            <option value="30" selected>یک ماه اخیر</option>
                            <option value="90">سه ماه اخیر</option>
                            <option value="365">یک سال اخیر</option>
                            <option value="custom">سفارشی</option>
                        </select>
                    </div>

                    <div class="option-group">
                        <label for="export-status">وضعیت سفارش:</label>
                        <select id="export-status">
                            <option value="all">همه وضعیت‌ها</option>
                            <option value="completed">تکمیل شده</option>
                            <option value="processing">در حال پردازش</option>
                            <option value="pending">در انتظار پرداخت</option>
                            <option value="cancelled">لغو شده</option>
                        </select>
                    </div>
                </div>

                <button type="submit" id="export-btn" class="primary">📊 تولید فایل اکسل</button>
            </form>

            <div id="export-result"></div>
        </section>
    </main>

    <!-- Dashboard JavaScript -->
    <script src="<?php echo plugin_dir_url(__FILE__) . '../assets/js/dashboard.js'; ?>"></script>

    <script>
        // Dashboard specific JavaScript
        jQuery(document).ready(function($) {
            // بازه زمانی سفارشی
            $('#export-period').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('.custom-date').show();
                } else {
                    $('.custom-date').hide();
                }
            });

            // بازه زمانی سفارشی برای آمار
            $('#stats-period').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('.custom-date-range').show();
                } else {
                    $('.custom-date-range').hide();
                }
            });
        });
    </script>
</body>
</html>