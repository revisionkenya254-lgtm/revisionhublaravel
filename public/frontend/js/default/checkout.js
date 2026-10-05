"use strict";
$(document).ready(function () {
    const gatewayTabs = $(".gateway-tab-btn");
    const gatewayPanes = $(".gateway-pane");
    const summaryGatewayName = $("#summaryGatewayName");
    const summaryGatewayTotal = $("#summaryGatewayTotal");
    const checkoutCard = $("#checkoutGatewayCard");

    function setAccent(accent) {
        const color = accent || "#5b67f1";
        checkoutCard.css("--gateway-accent", color);
        summaryGatewayTotal.css("color", color);
    }

    function applyGatewaySelection(button) {
        if (!button.length) {
            return;
        }

        gatewayTabs.removeClass("is-active").attr("aria-selected", "false");
        button.addClass("is-active").attr("aria-selected", "true");

        const target = button.data("target");
        const name = button.data("name") || "";
        const total = button.data("total") || "";
        const currency = button.data("currency") || "";
        const accent = button.data("accent") || "#5b67f1";

        gatewayPanes.removeClass("is-active");
        $(target).addClass("is-active");

        summaryGatewayName.text(name);
        summaryGatewayTotal.text(total ? `${total} ${currency}` : "");
        setAccent(accent);
        $("#show_currency_notifications .alert-warning").addClass("d-none").html("");
    }

    if (gatewayTabs.length) {
        const activeTab = gatewayTabs.filter(".is-active").first();
        applyGatewaySelection(activeTab.length ? activeTab : gatewayTabs.first());
    }

    $(document).on("click", ".gateway-tab-btn", function () {
        applyGatewaySelection($(this));
    });

    $(document).on("click", ".gateway-place-order-btn", function (e) {
        e.preventDefault();

        const button = $(this);
        const method = button.data("method");
        const activePane = button.closest(".gateway-pane");
        const supported = activePane.data("supported") === 1 || activePane.data("supported") === "1";

        if (!method) {
            toastr.warning("Please select a payment method.");
            return;
        }

        if (!supported) {
            toastr.warning("The selected payment method does not support this currency.");
            return;
        }

        let msisdn = "";
        if (method === "mpesa_stk_push") {
            const mpesaPhoneField = activePane.find("#checkout_msisdn");
            msisdn = (mpesaPhoneField.val() || "").trim();

            if (!msisdn) {
                toastr.warning("Please enter your M-Pesa phone number.");
                mpesaPhoneField.trigger("focus");
                return;
            }
        }

        const originalButtonHtml = button.html();

        $.ajax({
            url: `${base_url}/place-order/${method}`,
            type: "POST",
            dataType: "json",
            data: {
                _token: $('meta[name="csrf-token"]').attr("content"),
            },
            beforeSend: function () {
                $("#show_currency_notifications .alert-warning").addClass("d-none").html("");
                $(".preloader-two").removeClass("d-none");
                button.prop("disabled", true).html("Processing...");
            },
            success: function (response) {
                if (response.success) {
                    if (method === "mpesa_stk_push") {
                        const query = msisdn
                            ? `&msisdn=${encodeURIComponent(msisdn)}&autostk=1`
                            : "";
                        window.location.href = `${base_url}/payment?invoice_id=${response.invoice_id}${query}`;
                        return;
                    }

                    window.location.href = `${base_url}/payment?invoice_id=${response.invoice_id}`;
                    return;
                }

                if (response.supportCurrency) {
                    $("#show_currency_notifications .alert-warning")
                        .html(response.supportCurrency)
                        .removeClass("d-none");
                }

                toastr.warning(response.messege || basic_error_message);
                $(".preloader-two").addClass("d-none");
                button.prop("disabled", false).html(originalButtonHtml);
            },
            error: function (error) {
                const errorMessage = error.responseJSON?.message || basic_error_message;
                toastr.error(errorMessage);
                $(".preloader-two").addClass("d-none");
                button.prop("disabled", false).html(originalButtonHtml);
            },
        });
    });
});
