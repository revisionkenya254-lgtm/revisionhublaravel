"use strict";

const csrf_token = $("meta[name='csrf-token']").attr("content");

/** On Document Load */

$(document).ready(function () {
    function getButtonTextElement(element) {
        if (element.find(".auth-submit-btn__label").first().length) {
            return element.find(".auth-submit-btn__label").first();
        }

        return element.find(".text").first().length ? element.find(".text").first() : element.find("span").first();
    }

    function resetPurchaseButtons() {
        $(".purchase-btn, .buy-now, .add-to-cart").each(function () {
            const element = $(this);

            element.removeClass("is-loading");
            element.prop("disabled", false);
            element.attr("aria-busy", "false");
            resetCartActionButton(element);
        });
    }

    function setLoadingState(element, loading) {
        if (!element || !element.length) {
            return;
        }

        element.toggleClass("is-loading", loading);

        if (element.is("button")) {
            element.prop("disabled", loading);
        }

        element.attr("aria-busy", loading ? "true" : "false");
    }

    function markCartActionAdded(element) {
        element.addClass("is-added-to-cart");
        getButtonTextElement(element).text("Added to Cart");
    }

    function markMatchingCartButtons(element) {
        const itemId = element.data("id");
        const productType = element.data("product-type") || "course";

        $(`.add-to-cart[data-id="${itemId}"]`).filter(function () {
            return ($(this).data("product-type") || "course") === productType;
        }).each(function () {
            markCartActionAdded($(this));
        });
    }

    function resetCartActionButton(element) {
        const defaultText = element.data("default-text") || "Add to Cart";
        getButtonTextElement(element).text(defaultText);
    }

    function redirectToCheckout(url) {
        window.location.href = url || `${base_url}/checkout`;
    }

    function updateCartCount(count) {
        const normalizedCount = Number(count) || 0;
        $(".mini-cart-count, .site-header__count, .revision-header__cart-badge, .cart-count, [data-cart-count]").text(normalizedCount);
        $(document).trigger("cart:updated", [normalizedCount]);
    }

    function pushCartDataLayer(payload) {
        if (!payload || typeof payload !== "object") {
            return;
        }

        try {
            if (!Array.isArray(window.dataLayer)) {
                window.dataLayer = [];
            }

            window.dataLayer.push({
                event: "addToCart",
                cart_details: payload
            });
        } catch (error) {
            // Best-effort tracking only. Checkout flow should never fail because analytics is unavailable.
        }
    }

    function handleCartAction(element, shouldRedirectToCheckout) {
        const defaultText = element.data("default-text") || getButtonTextElement(element).text() || "Add to Cart";
        const loadingText = shouldRedirectToCheckout ? "Redirecting..." : "Adding...";

        element.data("default-text", defaultText);

        $.ajax({
            method: "post",
            url: base_url + "/add-to-cart/" + element.data("id"),
            dataType: "json",
            data: {
                _token: csrf_token,
                product_type: element.data("product-type") || "course",
                buy_now: shouldRedirectToCheckout ? 1 : 0
            },
            beforeSend: function () {
                setLoadingState(element, true);
                getButtonTextElement(element).text(loadingText);
            },
            success: function (data) {
                if (data.status == "success") {
                    toastr.success(data.message);
                    updateCartCount(data.cart_count);
                    markMatchingCartButtons(element);
                    pushCartDataLayer(data.dataLayer);

                    if (shouldRedirectToCheckout) {
                        redirectToCheckout(data.redirect_url);
                        return;
                    }

                    setLoadingState(element, false);
                    resetCartActionButton(element);
                } else {
                    if (shouldRedirectToCheckout && data.message === "Already added to cart") {
                        markMatchingCartButtons(element);
                        redirectToCheckout(data.redirect_url);
                        return;
                    }

                    toastr.error(data.message);
                    setLoadingState(element, false);
                    resetCartActionButton(element);
                }
            },
            error: function (xhr, status, error) {
                toastr.error(basic_error_message);
                setLoadingState(element, false);
                resetCartActionButton(element);
            },

        });
    }

    window.addEventListener("pageshow", function (event) {
        if (event.persisted) {
            resetPurchaseButtons();
        }
    });

    // Add to cart
    $(document).on("click", ".add-to-cart", function (e) {
        e.preventDefault();
        handleCartAction($(this), false);
    });

    // Buy now
    $(document).on("click", ".buy-now", function (e) {
        e.preventDefault();
        handleCartAction($(this), true);
    });

    // apply coupon
    $('.coupon-form').on('submit', function (e) {
        e.preventDefault();

        let formData = $(this).serialize();
        $.ajax({
            method: "POST",
            url: base_url + "/apply-coupon",
            data: formData,
            beforeSend: function () {
                $('.coupon-form button').attr('disabled', true);
                $('.coupon-form button').text("Applying...");
            },
            success: function (data) {
                let html = `
                  <span>${discount}</span>
                    <br>
                  <small>${data.coupon_code} (${data.offer_percentage}%) <a class="ms-2 text-danger" href="/remove-coupon">×</a></small>
                `;
                $('.coupon-discount').html(html);
                $('.discount-amount').text(data.discount_amount);
                $('.amount').text(data.total);
                // reset form
                $('.coupon-form button').attr('disabled', false);
                $('.coupon-form button').text("Apply Coupon");
                $('.coupon-form')[0].reset();
                toastr.success(data.message);
            },
            error: function (xhr, status, error) {
                $('.coupon-form button').attr('disabled', false);
                $('.coupon-form button').text("Apply Coupon");
                if (xhr.responseJSON?.errors) {
                    $.each(xhr.responseJSON.errors, function (key, value) {
                        toastr.error(value);
                    });
                } else if (xhr.responseJSON?.message) {
                    toastr.error(xhr.responseJSON.message);
                }
            }
        })
    });
})
