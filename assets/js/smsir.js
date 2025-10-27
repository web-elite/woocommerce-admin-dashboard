jQuery(document).ready(function($) {
    // Login form handler
    $('#smsir-login-form').on('submit', function(e) {
        e.preventDefault();

        var formData = {
            action: 'smsir_login',
            nonce: smsir_ajax.nonce,
            username: $('#smsir-username').val(),
            password: $('#smsir-password').val()
        };

        $('#smsir-login-message').html('در حال ورود...');

        $.post(smsir_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                $('#smsir-login-message').html('<span style="color: green;">' + response.data + '</span>');
                location.reload();
            } else {
                $('#smsir-login-message').html('<span style="color: red;">' + response.data + '</span>');
            }
        });
    });

    // Modal functionality
    var modal = $('#smsir-modal');
    var modalBtn = $('#smsir-login-modal-btn');
    var closeBtn = $('.smsir-close');

    modalBtn.on('click', function() {
        modal.show();
    });

    closeBtn.on('click', function() {
        modal.hide();
    });

    $(window).on('click', function(event) {
        if (event.target == modal[0]) {
            modal.hide();
        }
    });

    // Tab switching
    $('.smsir-tab-btn').on('click', function() {
        var tab = $(this).data('tab');

        $('.smsir-tab-btn').removeClass('active');
        $('.smsir-tab-content').removeClass('active');

        $(this).addClass('active');
        $('#smsir-' + tab + '-tab').addClass('active');
    });

    // Modal login form
    $('#smsir-modal-login-form').on('submit', function(e) {
        e.preventDefault();

        var formData = {
            action: 'smsir_login',
            nonce: smsir_ajax.nonce,
            username: $('#smsir-modal-username').val(),
            password: $('#smsir-modal-password').val()
        };

        $('#smsir-modal-message').html('<p style="color: blue;">در حال ورود...</p>');

        $.post(smsir_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                $('#smsir-modal-message').html('<p style="color: green;">' + response.data + '</p>');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                $('#smsir-modal-message').html('<p style="color: red;">' + response.data + '</p>');
            }
        });
    });

    // Modal register form
    $('#smsir-modal-register-form').on('submit', function(e) {
        e.preventDefault();

        var formData = {
            action: 'smsir_register',
            nonce: smsir_ajax.nonce,
            username: $('#smsir-modal-reg-username').val(),
            email: $('#smsir-modal-reg-email').val(),
            password: $('#smsir-modal-reg-password').val()
        };

        $('#smsir-modal-message').html('<p style="color: blue;">در حال ثبت نام...</p>');

        $.post(smsir_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                $('#smsir-modal-message').html('<p style="color: green;">' + response.data + '</p>');
                // Switch to login tab after successful registration
                $('.smsir-tab-btn[data-tab="login"]').click();
            } else {
                $('#smsir-modal-message').html('<p style="color: red;">' + response.data + '</p>');
            }
        });
    });

    // Newsletter form
    $('#smsir-newsletter-form').on('submit', function(e) {
        e.preventDefault();

        var formData = {
            action: 'smsir_newsletter',
            nonce: smsir_ajax.nonce,
            email: $('#smsir-newsletter-email').val()
        };

        $('#smsir-newsletter-message').html('در حال عضویت...');

        $.post(smsir_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                $('#smsir-newsletter-message').html('<span style="color: green;">' + response.data + '</span>');
                $('#smsir-newsletter-form')[0].reset();
            } else {
                $('#smsir-newsletter-message').html('<span style="color: red;">' + response.data + '</span>');
            }
        });
    });
});