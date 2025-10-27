jQuery(document).ready(function($) {
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
    $('.tab-btn').on('click', function() {
        var tab = $(this).data('tab');

        $('.tab-btn').removeClass('active');
        $('.tab-content').removeClass('active');

        $(this).addClass('active');
        $('#' + tab + '-tab').addClass('active');

        // اگر تب سفارشات فعال شد، داده‌ها را لود کن
        if (tab === 'orders') {
            loadOrdersData();
        }
    });

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

    $('#upload-form').on('submit', function(e) {
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
            success: function(response) {
                removeLoading(submitBtn);
                if (response.success) {
                    $('#result').html('<p style="color: green;">' + response.data + '</p>');
                } else {
                    $('#result').html('<p style="color: red;">' + response.data + '</p>');
                }
            },
            error: function() {
                removeLoading(submitBtn);
                $('#result').html('<p style="color: red;">آپلود ناموفق بود.</p>');
            }
        });
    });

    // دانلود فایل نمونه
    $('#download-sample-btn').on('click', function() {
        var btn = $(this);
        setLoading(btn, 'در حال ایجاد فایل...');

        $.ajax({
            url: custom_dashboard.ajax_url,
            type: 'POST',
            data: {
                action: 'generate_sample_file',
                nonce: custom_dashboard.nonce
            },
            success: function(response) {
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
            error: function() {
                removeLoading(btn);
                alert('خطا در ایجاد فایل نمونه.');
            }
        });
    });

    // فرم خروجی اکسل
    $('#export-form').on('submit', function(e) {
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
            success: function(response) {
                removeLoading(btn);
                if (response.success) {
                    $('#export-result').html('<p style="color: green;">فایل با موفقیت تولید شد. <a href="' + response.data + '" target="_blank">دانلود فایل</a></p>');
                } else {
                    $('#export-result').html('<p style="color: red;">' + response.data + '</p>');
                }
            },
            error: function() {
                removeLoading(btn);
                $('#export-result').html('<p style="color: red;">خطا در تولید فایل.</p>');
            }
        });
    });

    // اعمال فیلترهای آمار
    $('#apply-filters-btn').on('click', function() {
        var btn = $(this);
        setLoading(btn, 'در حال بارگذاری...');
        setSectionLoading($('#orders-tab .orders-stats'));

        loadOrdersData(function() {
            removeLoading(btn);
            removeSectionLoading($('#orders-tab .orders-stats'));
        });
    });

    // کدهای مدیریت سفارشات
    let currentPage = 1;
    let currentStatus = 'processing,pending'; // پیش‌فرض: در حال انجام و در حال بررسی
    let currentSort = 'date_desc';

    // بارگذاری سفارشات مدیریت
    function loadManageOrders(page = 1, callback) {
        setSectionLoading($('#manage-orders-table'));
        $('#manage-orders-table').html('<p>در حال بارگذاری...</p>');

        $.ajax({
            url: custom_dashboard.ajax_url,
            type: 'POST',
            data: {
                action: 'get_manage_orders',
                nonce: custom_dashboard.nonce,
                status: currentStatus,
                sort: currentSort,
                page: page
            },
            success: function(response) {
                removeSectionLoading($('#manage-orders-table'));
                if (response.success) {
                    renderManageOrdersTable(response.data.orders);
                    renderPagination(response.data.pagination);
                } else {
                    $('#manage-orders-table').html('<p style="color: red;">' + response.data + '</p>');
                }
                if (callback) callback();
            },
            error: function() {
                removeSectionLoading($('#manage-orders-table'));
                $('#manage-orders-table').html('<p style="color: red;">خطا در بارگذاری سفارشات.</p>');
                if (callback) callback();
            }
        });
    }

    // رندر جدول مدیریت سفارشات
    function renderManageOrdersTable(orders) {
        if (orders.length === 0) {
            $('#manage-orders-table').html('<p>هیچ سفارشی یافت نشد.</p>');
            return;
        }

        let html = `
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>سفارش</th>
                        <th>مشتری</th>
                        <th>تلفن</th>
                        <th>آدرس</th>
                        <th>یادداشت</th>
                        <th>مجموع</th>
                        <th>وضعیت</th>
                        <th>تاریخ</th>
                        <th>پرینت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>`;

        orders.forEach(function(order) {
            html += `
                <tr>
                    <td>#${order.id}</td>
                    <td>${order.customer}</td>
                    <td>${order.phone}</td>
                    <td>${order.address}</td>
                    <td>${order.notes || '-'}</td>
                    <td>${order.total}</td>
                    <td><span class="status-${order.status}">${order.status_name}</span></td>
                    <td>${order.date}</td>
                    <td class="print-column">
                        <a href="${order.print_links.thermal}" target="_blank" title="پرینت حرارتی">
                            <div class="print-btn thermal-print" style="background: #ff64b1; display: inline-block; margin: 2px; padding: 4px 8px; color: white; border-radius: 3px; font-size: 11px;">
                                <span class="dashicons dashicons-text-page" style="line-height: 20px;"></span>
                            </div>
                        </a>
                        <a href="${order.print_links.label}" target="_blank" title="برچسب">
                            <div class="print-btn label-print" style="background: #52cbbf; display: inline-block; margin: 2px; padding: 4px 8px; color: white; border-radius: 3px; font-size: 11px;">
                                <span class="dashicons dashicons-tag" style="line-height: 20px;"></span>
                            </div>
                        </a>
                        <a href="${order.print_links.invoice}" target="_blank" title="فاکتور">
                            <div class="print-btn invoice-print" style="background: #98b4c7; display: inline-block; margin: 2px; padding: 4px 8px; color: white; border-radius: 3px; font-size: 11px;">
                                <span class="dashicons dashicons-media-spreadsheet" style="line-height: 20px;"></span>
                            </div>
                        </a>
                    </td>
                    <td>
                        <select class="status-select" data-order-id="${order.id}">
                            <option value="processing" ${order.status === 'processing' ? 'selected' : ''}>در حال پردازش</option>
                            <option value="completed" ${order.status === 'completed' ? 'selected' : ''}>تکمیل شده</option>
                            <option value="on-hold" ${order.status === 'on-hold' ? 'selected' : ''}>در انتظار</option>
                            <option value="cancelled" ${order.status === 'cancelled' ? 'selected' : ''}>لغو شده</option>
                        </select>
                        <button class="view-details-btn" data-order-id="${order.id}">جزئیات</button>
                    </td>
                </tr>`;
        });

        html += '</tbody></table>';
        $('#manage-orders-table').html(html);
    }

    // رندر pagination
    function renderPagination(pagination) {
        let html = '<div class="pagination">';
        const totalPages = pagination.total_pages;
        const currentPage = pagination.current_page;

        // اگر کمتر از 8 صفحه داریم، همه را نمایش بده
        if (totalPages <= 8) {
            for (let i = 1; i <= totalPages; i++) {
                html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
            }
        } else {
            // نمایش صفحه اول
            html += `<button class="page-btn ${1 === currentPage ? 'active' : ''}" data-page="1">1</button>`;

            // اگر صفحه فعلی بیشتر از 4 است، ... نمایش بده
            if (currentPage > 4) {
                html += '<span class="pagination-dots">...</span>';
            }

            // نمایش صفحات اطراف صفحه فعلی
            const start = Math.max(2, currentPage - 2);
            const end = Math.min(totalPages - 1, currentPage + 2);

            for (let i = start; i <= end; i++) {
                html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
            }

            // اگر صفحه فعلی کمتر از totalPages-3 است، ... نمایش بده
            if (currentPage < totalPages - 3) {
                html += '<span class="pagination-dots">...</span>';
            }

            // نمایش صفحه آخر
            if (totalPages > 1) {
                html += `<button class="page-btn ${totalPages === currentPage ? 'active' : ''}" data-page="${totalPages}">${totalPages}</button>`;
            }
        }

        html += '</div>';
        $('#manage-orders-table').append(html);
    }

    // تغییر وضعیت سفارش
    $(document).on('change', '.status-select', function() {
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
            success: function(response) {
                selectElement.prop('disabled', false);
                if (response.success) {
                    $(`.status-select[data-order-id="${orderId}"]`).closest('tr').find('.status-' + response.data.new_status).text(response.data.new_status_name);
                    alert(response.data.message);
                } else {
                    alert('خطا: ' + response.data);
                }
            },
            error: function() {
                selectElement.prop('disabled', false);
                alert('خطا در تغییر وضعیت سفارش.');
            }
        });
    });

    // فیلتر وضعیت
    $('#manage-status-filter').on('change', function() {
        currentStatus = $(this).val();
        currentPage = 1;
        loadManageOrders();
    });

    // فیلتر مرتب‌سازی
    $('#manage-sort').on('change', function() {
        currentSort = $(this).val();
        currentPage = 1;
        loadManageOrders();
    });

    // دکمه بروزرسانی
    $('#refresh-orders-btn').on('click', function() {
        var btn = $(this);
        setLoading(btn, 'در حال بروزرسانی...');
        loadManageOrders(currentPage, function() {
            removeLoading(btn);
        });
    });

    // کلیک روی دکمه صفحه
    $(document).on('click', '.page-btn', function() {
        var btn = $(this);
        var page = btn.data('page');
        setLoading(btn, 'در حال بارگذاری...');
        loadManageOrders(page, function() {
            removeLoading(btn);
        });
    });

    // نمایش مدال جزئیات سفارش
    $(document).on('click', '.view-details-btn', function() {
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
            success: function(response) {
                removeLoading(btn);
                if (response.success) {
                    showOrderDetailsModal(response.data);
                } else {
                    alert('خطا: ' + response.data);
                }
            },
            error: function() {
                removeLoading(btn);
                alert('خطا در بارگذاری جزئیات سفارش.');
            }
        });
    });

    // نمایش مدال جزئیات سفارش
    function showOrderDetailsModal(data) {
        const modal = $('#order-details-modal');
        const content = $('#order-details-content');

        let html = `
            <div class="order-details-header">
                <h2>جزئیات سفارش #${data.order_info.id}</h2>
                <div class="order-status status-${data.order_info.status}">
                    ${data.order_info.status_name}
                </div>
            </div>

            <div class="order-details-grid">
                <div class="order-info-section">
                    <h3>اطلاعات سفارش</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>تاریخ ایجاد:</label>
                            <span>${data.order_info.date_created}</span>
                        </div>
                        <div class="info-item">
                            <label>آخرین بروزرسانی:</label>
                            <span>${data.order_info.date_modified || 'ندارد'}</span>
                        </div>
                        <div class="info-item">
                            <label>مجموع:</label>
                            <span class="total-amount">${data.payment_info.total}</span>
                        </div>
                        <div class="info-item">
                            <label>روش پرداخت:</label>
                            <span>${data.payment_info.method}</span>
                        </div>
                    </div>
                </div>

                <div class="customer-info-section">
                    <h3>اطلاعات مشتری</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>نام:</label>
                            <span>${data.customer_info.name}</span>
                        </div>
                        <div class="info-item">
                            <label>ایمیل:</label>
                            <span>${data.customer_info.email || 'ندارد'}</span>
                        </div>
                        <div class="info-item">
                            <label>تلفن:</label>
                            <span>${data.customer_info.phone || 'ندارد'}</span>
                        </div>
                    </div>

                    <div class="address-section">
                        <h4>آدرس صورتحساب</h4>
                        <p>${data.customer_info.billing_address || 'ندارد'}</p>
                    </div>

                    <div class="address-section">
                        <h4>آدرس ارسال</h4>
                        <p>${data.customer_info.shipping_address || 'ندارد'}</p>
                    </div>
                </div>

                <div class="items-section">
                    <h3>محصولات سفارش</h3>
                    <div class="order-items">
                        ${data.items.map(item => `
                            <div class="order-item">
                                <div class="item-info">
                                    <span class="item-name">${item.name}</span>
                                    ${item.sku ? `<span class="item-sku">SKU: ${item.sku}</span>` : ''}
                                </div>
                                <div class="item-details">
                                    <span class="item-quantity">تعداد: ${item.quantity}</span>
                                    <span class="item-price">${item.price}</span>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>

                <div class="payment-section">
                    <h3>جزئیات پرداخت</h3>
                    <div class="payment-details">
                        <div class="payment-row">
                            <span>زیرمجموع:</span>
                            <span>${data.payment_info.subtotal}</span>
                        </div>
                        <div class="payment-row">
                            <span>هزینه ارسال:</span>
                            <span>${data.payment_info.shipping}</span>
                        </div>
                        <div class="payment-row">
                            <span>مالیات:</span>
                            <span>${data.payment_info.tax}</span>
                        </div>
                        <div class="payment-row">
                            <span>تخفیف:</span>
                            <span>${data.payment_info.discount}</span>
                        </div>
                        <div class="payment-row total">
                            <span>مجموع:</span>
                            <span>${data.payment_info.total}</span>
                        </div>
                    </div>
                </div>

                ${data.order_info.customer_note ? `
                <div class="notes-section">
                    <h3>یادداشت مشتری</h3>
                    <p>${data.order_info.customer_note}</p>
                </div>
                ` : ''}

                ${data.notes.length > 0 ? `
                <div class="order-notes-section">
                    <h3>تاریخچه سفارش</h3>
                    <div class="order-notes">
                        ${data.notes.map(note => `
                            <div class="order-note ${note.type}">
                                <div class="note-header">
                                    <span class="note-date">${note.date}</span>
                                    <span class="note-type">${note.type === 'customer' ? 'یادداشت مشتری' : 'یادداشت داخلی'}</span>
                                </div>
                                <div class="note-content">${note.note}</div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                ` : ''}
            </div>
        `;

        content.html(html);
        modal.show();
    }

    // بستن مدال
    $(document).on('click', '.close', function() {
        $(this).closest('.modal').hide();
    });

    // بستن مدال با کلیک خارج از آن
    $(window).on('click', function(event) {
        if ($(event.target).hasClass('modal')) {
            $('.modal').hide();
        }
    });

    // بارگذاری اولیه سفارشات مدیریت وقتی تب فعال می‌شود
    $('.tab-btn[data-tab="manage-tab"]').on('click', function() {
        loadManageOrders();
    });

    // بارگذاری داده‌های آمار
    function loadOrdersData(callback) {
        var period = $('#stats-period').val();
        var startDate = $('#stats-start-date').val();
        var endDate = $('#stats-end-date').val();

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
            success: function(response) {
                if (response.success) {
                    updateStats(response.data.stats);
                    createCharts(response.data.chart_data);
                }
                if (callback) callback();
            },
            error: function() {
                if (callback) callback();
            }
        });
    }

    function updateStats(stats) {
        $('#total-orders').text(stats.total_orders);
        $('#completed-orders').text(stats.completed_orders);
        $('#total-revenue').text(stats.total_revenue + ' تومان');
        $('#avg-order').text(stats.avg_order + ' تومان');
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
                fontFamily: 'Inter, sans-serif'
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
        new Chart(statusCtx, {
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
                                family: 'Inter',
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
                            family: 'Inter',
                            size: 14,
                            weight: '600'
                        },
                        bodyFont: {
                            family: 'Inter',
                            size: 13
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }
});