(function ($) {
    $(document).ready(function () {
        // Utility functions for loading states
        function setLoading(button, loadingText = 'در حال بارگذاری...') {
            button.prop('disabled', true).addClass('loading').data('original-text', button.text()).text(loadingText);
        }

        function removeLoading(button) {
            button.prop('disabled', false).removeClass('loading');
            if (button.data('original-text')) {
                button.text(button.data('original-text'));
            }
        }

        function setSectionLoading(section, showOverlay = true) {
            if (showOverlay) {
                section.addClass('loading-overlay');
            }
        }

        function removeSectionLoading(section) {
            section.removeClass('loading-overlay');
        }

        // تب‌ها
        $('.tab-btn').on('click', function () {
            var tab = $(this).data('tab');

            // Remove active classes from all tabs
            $('.tab-btn').removeClass('bg-blue-500 text-white border-b-2 border-blue-500').addClass('bg-white text-gray-700');

            // Hide all tab contents
            $('.tab-content').removeClass('active').addClass('hidden');

            // Add active class to clicked tab
            $(this).removeClass('bg-white text-gray-700').addClass('bg-blue-500 text-white border-b-2 border-blue-500');

            // Show selected tab content
            $('#' + tab + '-tab').removeClass('hidden').addClass('active');

            // اگر تب سفارشات فعال شد، داده‌ها را لود کن
            if (tab === 'orders') {
                loadOrdersData();
            }
        });

        // بازه زمانی سفارشی
        $('#export-period').on('change', function () {
            if ($(this).val() === 'custom') {
                $('.custom-date').show();
            } else {
                $('.custom-date').hide();
            }
        });

        // بازه زمانی سفارشی برای آمار
        $('#stats-period').on('change', function () {
            if ($(this).val() === 'custom') {
                $('.custom-date-range').show();
            } else {
                $('.custom-date-range').hide();
            }
        });

        $('#upload-form').on('submit', function (e) {
            e.preventDefault();

            var formData = new FormData();
            formData.append('excel_file', $('#excel-file')[0].files[0]);
            formData.append('action', 'process_excel_upload');
            formData.append('nonce', custom_dashboard.nonce);

            var submitBtn = $(this).find('button[type="submit"]');
            setLoading(submitBtn, 'در حال آپلود...');
            $('#result').html('<p>در حال آپلود...</p>').show();

            $.ajax({
                url: custom_dashboard.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    removeLoading(submitBtn);
                    if (response.success) {
                        $('#result').html('<p style="color: green;">' + response.data + '</p>');
                    } else {
                        $('#result').html('<p style="color: red;">' + response.data + '</p>');
                    }
                },
                error: function () {
                    removeLoading(submitBtn);
                    $('#result').html('<p style="color: red;">آپلود ناموفق بود.</p>');
                }
            });
        });

        // دانلود فایل نمونه
        $('#download-sample-btn').on('click', function () {
            var btn = $(this);
            setLoading(btn, 'در حال ایجاد فایل...');

            $.ajax({
                url: custom_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'generate_sample_file',
                    nonce: custom_dashboard.nonce
                },
                success: function (response) {
                    removeLoading(btn);
                    if (response.success) {
                        // Create download link and trigger download
                        var link = document.createElement('a');
                        link.href = response.data.file_url;
                        link.download = response.data.filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);

                        alert('فایل نمونه با موفقیت دانلود شد.');
                    } else {
                        alert('خطا در ایجاد فایل نمونه: ' + response.data);
                    }
                },
                error: function () {
                    removeLoading(btn);
                    alert('خطا در ایجاد فایل نمونه.');
                }
            });
        });

        // فرم خروجی اکسل
        $('#export-form').on('submit', function (e) {
            e.preventDefault();

            var btn = $('#export-btn');
            setLoading(btn, 'در حال تولید فایل...');
            $('#export-result').html('<p>در حال تولید فایل...</p>').show();

            var period = $('#export-period').val();
            var startDate = $('#export-start-date').val();
            var endDate = $('#export-end-date').val();
            var status = $('#export-status').val();

            $.ajax({
                url: custom_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'export_orders_excel',
                    nonce: custom_dashboard.nonce,
                    period: period,
                    start_date: startDate,
                    end_date: endDate,
                    status: status
                },
                success: function (response) {
                    removeLoading(btn);
                    if (response.success) {
                        $('#export-result').html('<p style="color: green;">فایل با موفقیت تولید شد. <a href="' + response.data + '" target="_blank">دانلود فایل</a></p>');
                    } else {
                        $('#export-result').html('<p style="color: red;">' + response.data + '</p>');
                    }
                },
                error: function () {
                    removeLoading(btn);
                    $('#export-result').html('<p style="color: red;">خطا در تولید فایل.</p>');
                }
            });
        });

        // اعمال فیلترهای آمار
        $('#apply-filters-btn').on('click', function () {
            var btn = $(this);
            setLoading(btn, 'در حال بارگذاری...');
            setSectionLoading($('#orders-tab .orders-stats'));

            loadOrdersData(function () {
                removeLoading(btn);
                removeSectionLoading($('#orders-tab .orders-stats'));
            });
        });

        // کدهای مدیریت سفارشات
        let ordersTable;
        let currentStatus = 'processing,pending'; // پیش‌فرض: در حال انجام و در حال بررسی
        let currentDateFilter = 'all';
        let currentSingleDate = '';
        let currentStartDate = '';
        let currentEndDate = '';

        // مقداردهی اولیه DataTable
        function initializeOrdersTable() {
            if (typeof $.fn.DataTable !== 'function') {
                console.error('DataTables is not loaded yet');
                return;
            }

            if (ordersTable) {
                ordersTable.destroy();
            }

            ordersTable = $('#orders-table').DataTable({
                serverSide: true,
                ajax: {
                    url: custom_dashboard.ajax_url,
                    type: 'POST',
                    data: function (d) {
                        d.action = 'get_datatable_orders';
                        d.nonce = custom_dashboard.nonce;
                        d.status_filter = currentStatus;
                        d.date_filter = currentDateFilter;
                        d.single_date = currentSingleDate;
                        d.start_date = currentStartDate;
                        d.end_date = currentEndDate;
                    }
                },
                columns: [
                    { data: 0, orderable: true, searchable: true }, // سفارش
                    { data: 1, orderable: false, searchable: true }, // آدرس
                    { data: 2, orderable: false, searchable: true }, // یادداشت
                    { data: 3, orderable: true, searchable: false }, // مجموع
                    { data: 4, orderable: true, searchable: false }, // وضعیت
                    { data: 5, orderable: true, searchable: false }, // تاریخ
                    { data: 6, orderable: false, searchable: false }, // پرینت
                    { data: 7, orderable: false, searchable: false }  // عملیات
                ],
                order: [[5, 'desc']], // مرتب‌سازی پیش‌فرض بر اساس تاریخ نزولی
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/fa.json'
                },
                initComplete: function () {
                    // اضافه کردن کلاس‌های RTL و فارسی
                    $('.dataTables_wrapper').addClass('rtl');
                    $('.dataTables_filter input').addClass('text-right').attr('placeholder', 'جستجو...');
                }
            });
        }

        // بروزرسانی جدول با فیلترهای جدید
        function refreshOrdersTable() {
            if (ordersTable) {
                ordersTable.ajax.reload();
            }
        }        // تغییر وضعیت سفارش
        $(document).on('change', '.status-select', function () {
            const selectElement = $(this);
            const orderId = selectElement.data('order-id');
            const newStatus = selectElement.val();

            // غیرفعال کردن select تا درخواست کامل شود
            selectElement.prop('disabled', true);

            $.ajax({
                url: custom_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'update_order_status',
                    nonce: custom_dashboard.nonce,
                    order_id: orderId,
                    new_status: newStatus
                },
                success: function (response) {
                    selectElement.prop('disabled', false);
                    if (response.success) {
                        $(`.status-select[data-order-id="${orderId}"]`).closest('tr').find('.status-' + response.data.new_status).text(response.data.new_status_name);
                        alert(response.data.message);
                    } else {
                        alert('خطا: ' + response.data);
                    }
                },
                error: function () {
                    selectElement.prop('disabled', false);
                    alert('خطا در تغییر وضعیت سفارش.');
                }
            });
        });

        // فیلتر وضعیت
        $('#manage-status-filter').on('change', function () {
            currentStatus = $(this).val();
            refreshOrdersTable();
        });

        // فیلتر تاریخ
        $('#manage-date-filter').on('change', function () {
            var filterValue = $(this).val();
            $('.custom-date-single, .custom-date-range').addClass('hidden');

            if (filterValue === 'custom') {
                $('.custom-date-single').removeClass('hidden');
            } else if (filterValue === 'range') {
                $('.custom-date-range').removeClass('hidden');
            }

            currentDateFilter = filterValue;
            refreshOrdersTable();
        });
        // دکمه بروزرسانی
        $('#refresh-orders-btn').on('click', function () {
            var btn = $(this);
            setLoading(btn, 'در حال بروزرسانی...');
            refreshOrdersTable();
            setTimeout(function () {
                removeLoading(btn);
            }, 1000);
        });
        // نمایش مدال جزئیات سفارش
        $(document).on('click', '.view-details-btn', function () {
            const orderId = $(this).data('order-id');
            const btn = $(this);
            setLoading(btn, 'بارگذاری...');

            $.ajax({
                url: custom_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_order_details',
                    nonce: custom_dashboard.nonce,
                    order_id: orderId
                },
                success: function (response) {
                    removeLoading(btn);
                    if (response.success) {
                        showOrderDetailsModal(response.data);
                    } else {
                        alert('خطا: ' + response.data);
                    }
                },
                error: function () {
                    removeLoading(btn);
                    alert('خطا در بارگذاری جزئیات سفارش.');
                }
            });
        });

        // نمایش مدال جزئیات سفارش
        function showOrderDetailsModal(data) {
            const modal = $('#order-details-modal');
            const content = $('#order-details-content');
            console.log(data);
            let html = `
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-2">جزئیات سفارش #${data.order_info.id}</h2>
                <div class="inline-flex px-3 py-1 text-sm font-semibold rounded-full ${data.order_info.status === 'processing' ? 'bg-yellow-100 text-yellow-800' : data.order_info.status === 'completed' ? 'bg-green-100 text-green-800' : data.order_info.status === 'on-hold' ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800'}">
                    ${data.order_info.status_name}
                </div>
            </div>

            <div class="grid gap-6">
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">اطلاعات سفارش</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">تاریخ ایجاد:</label>
                            <span class="text-sm text-gray-900">${data.order_info.date_created || 'نامشخص'}</span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">آخرین بروزرسانی:</label>
                            <span class="text-sm text-gray-900">${data.order_info.date_modified || 'ندارد'}</span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">مجموع:</label>
                            <span class="text-lg font-bold text-green-600">${data.payment_info ? data.payment_info.total : 'نامشخص'}</span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">روش پرداخت:</label>
                            <span class="text-sm text-gray-900">${data.payment_info ? data.payment_info.method : 'نامشخص'}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">اطلاعات مشتری</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">نام:</label>
                            <span class="text-sm text-gray-900">${data.customer_info ? data.customer_info.name : 'نامشخص'}</span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">ایمیل:</label>
                            <span class="text-sm text-gray-900">${data.customer_info ? (data.customer_info.email || 'ندارد') : 'نامشخص'}</span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">تلفن:</label>
                            <span class="text-sm text-gray-900">${data.customer_info ? (data.customer_info.phone || 'ندارد') : 'نامشخص'}</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h4 class="text-md font-medium text-gray-900 mb-2">آدرس صورتحساب</h4>
                        <p class="text-sm text-gray-600">${data.customer_info ? (data.customer_info.billing_address || 'ندارد') : 'نامشخص'}</p>
                    </div>

                    <div>
                        <h4 class="text-md font-medium text-gray-900 mb-2">آدرس ارسال</h4>
                        <p class="text-sm text-gray-600">${data.customer_info ? (data.customer_info.shipping_address || 'ندارد') : 'نامشخص'}</p>
                    </div>
                </div>

                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">محصولات سفارش</h3>
                    <div class="space-y-3">
                        ${data.items && data.items.length > 0 ? data.items.map(item => `
                            <div class="bg-white p-4 rounded border border-gray-200">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <span class="font-medium text-gray-900">${item.name || 'نام محصول ندارد'}</span>
                                        ${item.sku ? `<span class="text-sm text-gray-500 mr-2">SKU: ${item.sku}</span>` : ''}
                                    </div>
                                    <div class="text-left">
                                        <span class="text-sm text-gray-600">تعداد: ${item.quantity || '0'}</span>
                                        <span class="text-sm font-medium text-gray-900 mr-4">${item.price || '0'}</span>
                                    </div>
                                </div>
                            </div>
                        `).join('') : '<p class="text-sm text-gray-500">هیچ محصولی یافت نشد</p>'}
                    </div>
                </div>

                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">جزئیات پرداخت</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">زیرمجموع:</span>
                            <span class="text-sm text-gray-900">${data.payment_info ? data.payment_info.subtotal : '0'}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">هزینه ارسال:</span>
                            <span class="text-sm text-gray-900">${data.payment_info ? data.payment_info.shipping : '0'}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">مالیات:</span>
                            <span class="text-sm text-gray-900">${data.payment_info ? data.payment_info.tax : '0'}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">تخفیف:</span>
                            <span class="text-sm text-gray-900">${data.payment_info ? data.payment_info.discount : '0'}</span>
                        </div>
                        <div class="flex justify-between border-t border-gray-300 pt-2">
                            <span class="text-sm font-medium text-gray-900">مجموع:</span>
                            <span class="text-lg font-bold text-green-600">${data.payment_info ? data.payment_info.total : '0'}</span>
                        </div>
                    </div>
                </div>

                ${data.order_info && data.order_info.customer_note ? `
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">یادداشت مشتری</h3>
                    <p class="text-sm text-gray-600">${data.order_info.customer_note}</p>
                </div>
                ` : ''}

                ${data.notes && data.notes.length > 0 ? `
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">تاریخچه سفارش</h3>
                    <div class="space-y-3">
                        ${data.notes.map(note => `
                            <div class="bg-white p-4 rounded border border-gray-200">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="text-sm font-medium text-gray-900">${note.date || 'بدون تاریخ'}</span>
                                    <span class="text-xs px-2 py-1 rounded-full ${note.type === 'customer' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'}">${note.type === 'customer' ? 'یادداشت مشتری' : 'یادداشت داخلی'}</span>
                                </div>
                                <div class="text-sm text-gray-600">${note.note || 'بدون یادداشت'}</div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                ` : ''}
            </div>
            `;

            content.html(html);
            modal.removeClass('hidden');
        }

        // بستن مدال
        $(document).on('click', '.close', function () {
            $('#order-details-modal').addClass('hidden');
        });

        // بستن مدال با کلیک خارج از آن
        $(window).on('click', function (event) {
            if ($(event.target).is('#order-details-modal')) {
                $('#order-details-modal').addClass('hidden');
            }
        });

        // مقداردهی اولیه DataTable وقتی تب فعال می‌شود
        $('.tab-btn[data-tab="manage"]').on('click', function () {
            if (!ordersTable) {
                if (typeof $.fn.DataTable === 'function') {
                    initializeOrdersTable();
                }
            }
        });

        // تابع نمایش جزئیات سفارش (برای onclick در HTML)
        window.showOrderDetails = function (orderId) {
            const btn = $(`.view-details-btn[data-order-id="${orderId}"]`);
            setLoading(btn, 'بارگذاری...');

            $.ajax({
                url: custom_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_order_details',
                    nonce: custom_dashboard.nonce,
                    order_id: orderId
                },
                success: function (response) {
                    removeLoading(btn);
                    if (response.success) {
                        showOrderDetailsModal(response.data);
                    } else {
                        alert('خطا: ' + response.data);
                    }
                },
                error: function () {
                    removeLoading(btn);
                    alert('خطا در بارگذاری جزئیات سفارش.');
                }
            });
        };

        // بارگذاری داده‌های آمار
        function loadOrdersData(callback) {
            var period = $('#stats-period').val();
            var startDate = $('#stats-start-date').val();
            var endDate = $('#stats-end-date').val();

            console.log('Loading orders data with period:', period);

            // Show loading state
            $('#total-orders, #completed-orders, #total-revenue, #avg-order').text('در حال بارگذاری...');

            $.ajax({
                url: custom_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_orders_stats',
                    nonce: custom_dashboard.nonce,
                    period: period,
                    start_date: startDate,
                    end_date: endDate
                },
                success: function (response) {
                    console.log('AJAX response:', response);
                    if (response.success) {
                        updateStats(response.data.stats);
                        createCharts(response.data.chart_data);

                        // Show message if no data
                        if (response.data.message) {
                            // You can show this message in a notification or alert
                            console.log('Dashboard message:', response.data.message);
                        }
                    } else {
                        console.error('AJAX error:', response.data);
                        // Show error message to user
                        $('#total-orders, #completed-orders, #total-revenue, #avg-order').text('خطا در بارگذاری');
                        alert('خطا در بارگذاری داده‌ها: ' + response.data);
                    }
                    if (callback) callback();
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', status, error);
                    $('#total-orders, #completed-orders, #total-revenue, #avg-order').text('خطا در اتصال');
                    alert('خطا در اتصال به سرور. لطفا دوباره تلاش کنید.');
                    if (callback) callback();
                }
            });
        }

        function createCharts(chartData) {
            // نمودار فروش ماهانه با TradingView
            const chartElement = document.getElementById('monthly-sales-chart');

            // پاک کردن محتوای قبلی
            chartElement.innerHTML = '';

            const chart = LightweightCharts.createChart(chartElement, {
                layout: {
                    background: { color: 'rgba(255, 255, 255, 0.95)' },
                    textColor: '#2d3748',
                    fontSize: 12,
                    fontFamily: 'Vazirmatn, sans-serif'
                },
                grid: {
                    vertLines: { color: 'rgba(0, 0, 0, 0.05)' },
                    horzLines: { color: 'rgba(0, 0, 0, 0.05)' }
                },
                crosshair: {
                    mode: LightweightCharts.CrosshairMode.Normal
                },
                rightPriceScale: {
                    borderColor: 'rgba(102, 126, 234, 0.3)',
                    textColor: '#4a5568'
                },
                timeScale: {
                    borderColor: 'rgba(102, 126, 234, 0.3)',
                    timeVisible: true,
                    secondsVisible: false
                },
                width: chartElement.clientWidth,
                height: 400
            });

            // تبدیل داده‌های ماهانه به فرمت TradingView
            const areaSeries = chart.addAreaSeries({
                topColor: 'rgba(102, 126, 234, 0.56)',
                bottomColor: 'rgba(102, 126, 234, 0.04)',
                lineColor: '#667eea',
                lineWidth: 3,
                crosshairMarkerVisible: true,
                crosshairMarkerRadius: 6,
                priceFormat: {
                    type: 'price',
                    precision: 0,
                    minMove: 1
                }
            });

            // تبدیل داده‌ها به فرمت مناسب
            let tradingViewData = [];
            if (chartData.monthly.labels && chartData.monthly.labels.length > 0) {
                tradingViewData = chartData.monthly.labels.map((label, index) => ({
                    time: new Date(label + '-01').getTime() / 1000, // تبدیل به timestamp
                    value: parseFloat(chartData.monthly.data[index]) || 0
                }));
            }

            areaSeries.setData(tradingViewData);

            // تنظیم محدوده زمانی اگر داده وجود داشته باشد
            if (tradingViewData.length > 0) {
                chart.timeScale().fitContent();
            }

            // responsive کردن نمودار
            const resizeObserver = new ResizeObserver(entries => {
                for (let entry of entries) {
                    const { width, height } = entry.contentRect;
                    chart.applyOptions({ width, height: 400 });
                }
            });
            resizeObserver.observe(chartElement);

            // نمودار وضعیت سفارشات با Chart.js
            const statusCtx = document.getElementById('order-status-chart').getContext('2d');

            // Destroy existing chart if it exists
            if (window.orderStatusChart) {
                window.orderStatusChart.destroy();
            }

            window.orderStatusChart = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: chartData.status.labels,
                    datasets: [{
                        data: chartData.status.data,
                        backgroundColor: [
                            '#48bb78',
                            '#ed8936',
                            '#f56565',
                            '#a0aec0'
                        ],
                        borderColor: [
                            '#38a169',
                            '#dd6b20',
                            '#e53e3e',
                            '#718096'
                        ],
                        borderWidth: 2,
                        hoverBorderWidth: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1.5,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: {
                                    family: 'Vazirmatn',
                                    size: 14,
                                    weight: '500'
                                },
                                color: '#2d3748',
                                padding: 20
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(45, 55, 72, 0.9)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            cornerRadius: 8,
                            titleFont: {
                                family: 'Vazirmatn',
                                size: 14,
                                weight: '600'
                            },
                            bodyFont: {
                                family: 'Vazirmatn',
                                size: 13
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
        }
        // Analytics functionality
        let analyticsCharts = {};

        // Load analytics data
        function loadAnalyticsData() {
            const period = $('#analytics-period').val();
            const startDate = $('#analytics-start-date').val();
            const endDate = $('#analytics-end-date').val();

            setSectionLoading($('#analytics-page'), true);

            $.ajax({
                url: custom_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_analytics_data',
                    nonce: custom_dashboard.nonce,
                    period: period,
                    start_date: startDate,
                    end_date: endDate
                },
                success: function (response) {
                    removeSectionLoading($('#analytics-page'));
                    if (response.success) {
                        updateAnalyticsUI(response.data);
                    } else {
                        console.error('Analytics error:', response.data);
                    }
                },
                error: function (xhr, status, error) {
                    removeSectionLoading($('#analytics-page'));
                    console.error('Analytics AJAX error:', status, error);
                }
            });
        }

        // Update analytics UI
        function updateAnalyticsUI(data) {
            // Update stats
            $('#customers-total').text(data.customer_stats.total || 0);
            $('#customers-new').text(data.customer_stats.new || 0);
            $('#customers-loyal').text(data.customer_stats.loyal || 0);
            $('#customers-avg-order').text((data.customer_stats.avg_order || 0) + ' تومان');

            // Update top products
            updateTopProducts(data.top_products);

            // Update province sales
            updateProvinceSales(data.province_sales);

            // Update performance metrics
            updatePerformanceMetrics(data.performance);

            // Create charts
            createAnalyticsCharts(data.charts);
        }

        // Update top products list
        function updateTopProducts(products) {
            const container = $('#top-products-list');
            container.empty();

            if (products && products.length > 0) {
                products.forEach((product, index) => {
                    const item = `
                        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-sm font-medium text-blue-600 ml-3">
                                    ${index + 1}
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">${product.name}</div>
                                    <div class="text-sm text-gray-500">${product.sales} فروش</div>
                                </div>
                            </div>
                            <div class="text-left">
                                <div class="font-medium text-gray-900">${product.revenue} تومان</div>
                            </div>
                        </div>
                    `;
                    container.append(item);
                });
            } else {
                container.html('<p class="text-gray-500 text-center py-4">داده‌ای یافت نشد</p>');
            }
        }

        // Update province sales
        function updateProvinceSales(provinces) {
            const container = $('#province-sales-list');
            container.empty();

            if (provinces && provinces.length > 0) {
                provinces.forEach((province, index) => {
                    const item = `
                        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                            <div class="flex items-center">
                                <span class="font-medium text-gray-900">${province.name}</span>
                            </div>
                            <div class="text-left">
                                <div class="font-medium text-gray-900">${province.sales} سفارش</div>
                                <div class="text-sm text-gray-500">${province.revenue} تومان</div>
                            </div>
                        </div>
                    `;
                    container.append(item);
                });
            } else {
                container.html('<p class="text-gray-500 text-center py-4">داده‌ای یافت نشد</p>');
            }
        }

        // Update performance metrics
        function updatePerformanceMetrics(performance) {
            $('#conversion-rate').text((performance.conversion_rate || 0) + '%');
            $('#conversion-rate-bar').css('width', (performance.conversion_rate || 0) + '%');
            $('#avg-processing-time').text((performance.avg_processing_time || 0) + ' روز');
            $('#customer-retention').text((performance.customer_retention || 0) + '%');
            $('#customer-satisfaction').text((performance.customer_satisfaction || 0) + '%');
        }

        // Create analytics charts
        function createAnalyticsCharts(chartData) {
            // Monthly revenue chart
            createMonthlyRevenueChart(chartData.monthly);

            // Daily revenue chart
            createDailyRevenueChart(chartData.daily);

            // Revenue distribution chart
            createRevenueDistributionChart(chartData.distribution);
        }

        // Monthly revenue chart
        function createMonthlyRevenueChart(data) {
            const chartElement = document.getElementById('monthly-revenue-chart');
            if (!chartElement) return;

            // Clear existing chart
            if (analyticsCharts.monthly) {
                analyticsCharts.monthly.dispose();
            }

            analyticsCharts.monthly = LightweightCharts.createChart(chartElement, {
                layout: {
                    background: { color: 'rgba(255, 255, 255, 0.95)' },
                    textColor: '#2d3748',
                    fontSize: 12,
                    fontFamily: 'Vazirmatn, sans-serif'
                },
                grid: {
                    vertLines: { color: 'rgba(0, 0, 0, 0.05)' },
                    horzLines: { color: 'rgba(0, 0, 0, 0.05)' }
                },
                width: chartElement.clientWidth,
                height: 250
            });

            const areaSeries = analyticsCharts.monthly.addAreaSeries({
                topColor: 'rgba(102, 126, 234, 0.56)',
                bottomColor: 'rgba(102, 126, 234, 0.04)',
                lineColor: '#667eea',
                lineWidth: 2
            });

            if (data && data.labels && data.data) {
                const chartData = data.labels.map((label, index) => ({
                    time: new Date(label + '-01').getTime() / 1000,
                    value: parseFloat(data.data[index]) || 0
                }));
                areaSeries.setData(chartData);
                analyticsCharts.monthly.timeScale().fitContent();
            }
        }

        // Daily revenue chart
        function createDailyRevenueChart(data) {
            const chartElement = document.getElementById('daily-revenue-chart');
            if (!chartElement) return;

            if (analyticsCharts.daily) {
                analyticsCharts.daily.dispose();
            }

            analyticsCharts.daily = LightweightCharts.createChart(chartElement, {
                layout: {
                    background: { color: 'rgba(255, 255, 255, 0.95)' },
                    textColor: '#2d3748',
                    fontSize: 12,
                    fontFamily: 'Vazirmatn, sans-serif'
                },
                grid: {
                    vertLines: { color: 'rgba(0, 0, 0, 0.05)' },
                    horzLines: { color: 'rgba(0, 0, 0, 0.05)' }
                },
                width: chartElement.clientWidth,
                height: 250
            });

            const lineSeries = analyticsCharts.daily.addLineSeries({
                color: '#48bb78',
                lineWidth: 2
            });

            if (data && data.labels && data.data) {
                const chartData = data.labels.map((label, index) => ({
                    time: new Date(label).getTime() / 1000,
                    value: parseFloat(data.data[index]) || 0
                }));
                lineSeries.setData(chartData);
                analyticsCharts.daily.timeScale().fitContent();
            }
        }

        // Revenue distribution chart
        function createRevenueDistributionChart(data) {
            const ctx = document.getElementById('revenue-distribution-chart');
            if (!ctx) return;

            if (analyticsCharts.distribution) {
                analyticsCharts.distribution.destroy();
            }

            analyticsCharts.distribution = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: data ? data.labels : [],
                    datasets: [{
                        data: data ? data.data : [],
                        backgroundColor: ['#667eea', '#48bb78', '#ed8936', '#f56565', '#a0aec0'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: {
                                    family: 'Vazirmatn',
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });
        }

        // Analytics event handlers
        $('#refresh-analytics-btn').on('click', function () {
            const btn = $(this);
            setLoading(btn, 'در حال بروزرسانی...');
            loadAnalyticsData();
            setTimeout(() => removeLoading(btn), 1000);
        });

        $('#analytics-period').on('change', function () {
            if ($(this).val() === 'custom') {
                $('#analytics-start-date, #analytics-end-date').prop('disabled', false);
            } else {
                $('#analytics-start-date, #analytics-end-date').prop('disabled', true);
                loadAnalyticsData();
            }
        });

        // Export handlers
        $('#export-sales-report').on('click', function () {
            exportReport('sales');
        });

        $('#export-customers-report').on('click', function () {
            exportReport('customers');
        });

        $('#export-products-report').on('click', function () {
            exportReport('products');
        });

        function exportReport(type) {
            const btn = $(`#export-${type}-report`);
            setLoading(btn, 'در حال تولید...');

            const period = $('#analytics-period').val();
            const startDate = $('#analytics-start-date').val();
            const endDate = $('#analytics-end-date').val();

            $.ajax({
                url: custom_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: `export_${type}_report`,
                    nonce: custom_dashboard.nonce,
                    period: period,
                    start_date: startDate,
                    end_date: endDate
                },
                success: function (response) {
                    removeLoading(btn);
                    if (response.success) {
                        // Create download link
                        const link = document.createElement('a');
                        link.href = response.data.file_url;
                        link.download = response.data.filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        alert('خطا: ' + response.data);
                    }
                },
                error: function () {
                    removeLoading(btn);
                    alert('خطا در تولید گزارش');
                }
            });
        }

        function updateStats(stats) {
            $('#total-orders').text(stats.total_orders);
            $('#completed-orders').text(stats.completed_orders);
            $('#total-revenue').text(stats.total_revenue + ' تومان');
            $('#avg-order').text(stats.avg_order + ' تومان');
        }

        // Customers functionality
        let customersTable;

        // Initialize customers DataTable
        function initializeCustomersTable() {
            if (typeof $.fn.DataTable !== 'function') {
                console.error('DataTables is not loaded yet');
                return;
            }

            if (customersTable) {
                customersTable.destroy();
            }

            customersTable = $('#customers-table').DataTable({
                serverSide: true,
                ajax: {
                    url: custom_dashboard.ajax_url,
                    type: 'POST',
                    data: function (d) {
                        d.action = 'get_datatable_customers';
                        d.nonce = custom_dashboard.nonce;
                        d.search = $('#customers-search').val();
                        d.sort = $('#customers-sort').val();
                        d.date_filter = $('#customers-date-filter').val();
                    }
                },
                columns: [
                    { data: 0, orderable: true, searchable: true }, // مشتری
                    { data: 1, orderable: false, searchable: true }, // اطلاعات تماس
                    { data: 2, orderable: true, searchable: false }, // آمار سفارشات
                    { data: 3, orderable: true, searchable: false }, // مجموع خرید
                    { data: 4, orderable: true, searchable: false }, // آخرین سفارش
                    { data: 5, orderable: false, searchable: false }  // عملیات
                ],
                order: [[0, 'asc']],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/fa.json'
                },
                initComplete: function () {
                    $('.dataTables_wrapper').addClass('rtl');
                    $('.dataTables_filter input').addClass('text-right').attr('placeholder', 'جستجو...');
                }
            });
        }

        // Load customers stats
        function loadCustomersStats() {
            $.ajax({
                url: custom_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_customers_stats',
                    nonce: custom_dashboard.nonce
                },
                success: function (response) {
                    if (response.success) {
                        $('#customers-total').text(response.data.total);
                        $('#customers-new').text(response.data.new);
                        $('#customers-loyal').text(response.data.loyal);
                        $('#customers-avg-order').text(response.data.avg_order + ' تومان');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Customers stats error:', status, error);
                }
            });
        }

        // Refresh customers table
        function refreshCustomersTable() {
            if (customersTable) {
                customersTable.ajax.reload();
            }
            loadCustomersStats();
        }

        // Show customer details modal
        function showCustomerDetailsModal(customerId) {
            const modal = $('#customer-details-modal');
            const content = $('#customer-details-content');

            setSectionLoading(modal, true);

            $.ajax({
                url: custom_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_customer_details',
                    nonce: custom_dashboard.nonce,
                    customer_id: customerId
                },
                success: function (response) {
                    removeSectionLoading(modal);
                    if (response.success) {
                        const data = response.data;
                        let html = `
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div class="bg-gray-50 p-6 rounded-lg">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">اطلاعات مشتری</h3>
                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">نام:</label>
                                            <span class="text-sm text-gray-900">${data.name || 'نامشخص'}</span>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">ایمیل:</label>
                                            <span class="text-sm text-gray-900">${data.email || 'ندارد'}</span>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">تلفن:</label>
                                            <span class="text-sm text-gray-900">${data.phone || 'ندارد'}</span>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">تاریخ عضویت:</label>
                                            <span class="text-sm text-gray-900">${data.registered_date || 'نامشخص'}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gray-50 p-6 rounded-lg">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">آمار خرید</h3>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="text-center">
                                            <div class="text-2xl font-bold text-blue-600">${data.total_orders || 0}</div>
                                            <div class="text-sm text-gray-600">کل سفارشات</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="text-2xl font-bold text-green-600">${data.total_spent || 0} تومان</div>
                                            <div class="text-sm text-gray-600">مجموع خرید</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="text-2xl font-bold text-purple-600">${data.avg_order_value || 0} تومان</div>
                                            <div class="text-sm text-gray-600">میانگین سفارش</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="text-2xl font-bold text-orange-600">${data.last_order_date || 'ندارد'}</div>
                                            <div class="text-sm text-gray-600">آخرین سفارش</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gray-50 p-6 rounded-lg lg:col-span-2">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">آخرین سفارشات</h3>
                                    <div class="space-y-3">
                                        ${data.recent_orders && data.recent_orders.length > 0 ?
                                data.recent_orders.map(order => `
                                                <div class="bg-white p-4 rounded border border-gray-200">
                                                    <div class="flex justify-between items-center">
                                                        <div>
                                                            <span class="font-medium text-gray-900">سفارش #${order.id}</span>
                                                            <span class="text-sm text-gray-500 mr-4">${order.date}</span>
                                                        </div>
                                                        <div class="text-left">
                                                            <span class="text-sm font-medium text-gray-900">${order.total} تومان</span>
                                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ml-2 ${order.status === 'completed' ? 'bg-green-100 text-green-800' :
                                        order.status === 'processing' ? 'bg-yellow-100 text-yellow-800' :
                                            'bg-gray-100 text-gray-800'
                                    }">${order.status_name}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            `).join('') :
                                '<p class="text-sm text-gray-500 text-center py-4">هیچ سفارشی یافت نشد</p>'
                            }
                                    </div>
                                </div>
                            </div>
                        `;
                        content.html(html);
                        modal.removeClass('hidden');
                    } else {
                        alert('خطا: ' + response.data);
                    }
                },
                error: function () {
                    removeSectionLoading(modal);
                    alert('خطا در بارگذاری جزئیات مشتری.');
                }
            });
        }

        // بستن مدال جزئیات مشتری
        $(document).on('click', '#customer-details-modal .close', function () {
            $('#customer-details-modal').addClass('hidden');
        });

        $(window).on('click', function (event) {
            if ($(event.target).is('#customer-details-modal')) {
                $('#customer-details-modal').addClass('hidden');
            }
        });

        // Customers page event handlers
        $('#refresh-customers-btn').on('click', function () {
            const btn = $(this);
            setLoading(btn, 'در حال بروزرسانی...');
            refreshCustomersTable();
            setTimeout(() => removeLoading(btn), 1000);
        });

        $('#customers-search').on('keyup', function () {
            if (customersTable) {
                customersTable.search($(this).val()).draw();
            }
        });

        $('#customers-sort').on('change', function () {
            refreshCustomersTable();
        });

        $('#customers-date-filter').on('change', function () {
            refreshCustomersTable();
        });

        // Customer details modal handlers
        $(document).on('click', '.view-customer-details-btn', function () {
            const customerId = $(this).data('customer-id');
            showCustomerDetailsModal(customerId);
        });

        // Initialize analytics when analytics page is shown
        $('.nav-item[data-page="analytics"]').on('click', function () {
            setTimeout(function () {
                if (!analyticsCharts.monthly) {
                    loadAnalyticsData();
                }
            }, 100);
        });

        // Initialize customers when customers page is shown
        $('.nav-item[data-page="customers"]').on('click', function () {
            setTimeout(function () {
                if (!customersTable) {
                    initializeCustomersTable();
                    loadCustomersStats();
                }
            }, 100);
        });

        // اگر تب manage فعال است، DataTable را مقداردهی اولیه کن
        if ($('.tab-btn[data-tab="manage"]').hasClass('active')) {
            if (typeof $.fn.DataTable === 'function') {
                initializeOrdersTable();
            }
        }

        // بارگذاری آمار سفارشات در هنگام بارگذاری صفحه
        loadOrdersData();

        jalaliDatepicker.startWatch();
    });

})(jQuery);
