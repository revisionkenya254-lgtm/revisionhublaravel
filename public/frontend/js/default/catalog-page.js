"use strict";

const catalogPlaceholder = $(".preloader-two");
var catalogFilters = {};
var catalogState = {
    page: 1,
    lastPage: 1,
};

function renderCatalogLoadingMarkup() {
    var cards = "";

    for (var i = 0; i < 5; i++) {
        cards += `
            <div class="catalog-grid-item">
                <div class="catalog-loading-card">
                    <div class="catalog-loading-card__thumb shimmer"></div>
                    <div class="catalog-loading-card__body">
                        <div class="catalog-loading-card__line shimmer"></div>
                        <div class="catalog-loading-card__line catalog-loading-card__line--short shimmer"></div>
                        <div class="catalog-loading-card__line catalog-loading-card__line--tiny shimmer"></div>
                        <div class="catalog-loading-card__actions">
                            <span class="catalog-loading-card__pill shimmer"></span>
                            <span class="catalog-loading-card__pill shimmer"></span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    return cards;
}

function escapeCatalogHtml(value) {
    return String(value ?? "").replace(/[&<>"']/g, function (character) {
        return ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            "\"": "&quot;",
            "'": "&#39;",
        })[character];
    });
}

function renderCatalogBreadcrumbs(breadcrumbs) {
    if (!Array.isArray(breadcrumbs) || breadcrumbs.length === 0) {
        return "";
    }

    var html = "";

    breadcrumbs.forEach(function (crumb, index) {
        var label = escapeCatalogHtml(crumb?.label || "");
        var url = crumb?.url ? String(crumb.url) : "";

        if (index > 0) {
            html += '<span class="catalog-path__crumb"><i class="fas fa-angle-right"></i></span>';
        }

        if (url) {
            html += '<span class="catalog-path__crumb"><a href="' + escapeCatalogHtml(url) + '">' + label + "</a></span>";
        } else {
            html += '<span class="catalog-path__crumb is-current">' + label + "</span>";
        }
    });

    return html;
}

function updateCatalogPageHeader(data) {
    var pageTitle = String(data?.pageTitle || "").trim();
    var focusName = String(data?.focusName || "").trim();
    var breadcrumbs = Array.isArray(data?.catalogBreadcrumbs) ? data.catalogBreadcrumbs : [];

    if (pageTitle) {
        $(".catalog-hero__title").text(pageTitle);
        $(".catalog-focus__copy h2").text(pageTitle);

        var $aiPrompt = $(".catalog-ai-card p");
        if ($aiPrompt.length) {
            $aiPrompt.text("Ask anything about " + pageTitle);
        }
    }

    if (focusName) {
        $(".catalog-focus__copy p").text(focusName);
    }

    if (breadcrumbs.length > 0) {
        $(".catalog-path").html(renderCatalogBreadcrumbs(breadcrumbs));
    }
}

function normalizeCatalogFilters(filters) {
    var normalized = {};
    var keys = ["search", "category", "main_category", "subject", "type", "order"];

    keys.forEach(function (key) {
        var value = filters[key];
        if (value !== undefined && value !== null && value !== "") {
            normalized[key] = value;
        }
    });

    return normalized;
}

function buildCatalogQuery(page, filters) {
    var params = new URLSearchParams();
    params.set("page", page || 1);

    Object.keys(filters || {}).forEach(function (key) {
        var value = filters[key];
        if (value !== undefined && value !== null && value !== "") {
            params.set(key, value);
        }
    });

    return params.toString();
}

function extractCatalogFiltersFromHref(href) {
    if (!href) {
        return {};
    }

    try {
        var parsedUrl = new URL(href, window.location.origin);
        var params = new URLSearchParams(parsedUrl.search);
        var filters = {};

        ["search", "category", "main_category", "subject", "type", "order"].forEach(function (key) {
            if (params.get(key) !== null && params.get(key) !== "") {
                filters[key] = params.get(key);
            }
        });

        if (filters.category) {
            filters.category = filters.category.split(",")[0];
        }

        return filters;
    } catch (error) {
        return {};
    }
}

function syncCatalogSelectionState(filters) {
    var activeType = filters.type || "";

    $(".catalog-type-tab[data-type]").removeClass("is-active");
    $(".catalog-type-tab[data-type=\"" + activeType + "\"]").addClass("is-active");
    if (!activeType) {
        $(".catalog-type-tab[data-type=\"\"]").addClass("is-active");
    }

    $(".catalog-tree__summary-text, .catalog-tree__summary-link, .catalog-tree__item, .catalog-topic-pill").removeClass("is-active");

    if (filters.main_category) {
        $(".catalog-tree__summary-link[data-catalog-main-category=\"" + filters.main_category + "\"]:not([data-catalog-category])").addClass("is-active");

        if (filters.category && filters.subject) {
            $(".catalog-tree__summary-link[data-catalog-main-category=\"" + filters.main_category + "\"][data-catalog-category=\"" + filters.category + "\"]").addClass("is-active");
            $(".catalog-tree__summary-link[data-catalog-main-category=\"" + filters.main_category + "\"][data-catalog-category=\"" + filters.category + "\"][data-catalog-subject=\"" + filters.subject + "\"]").addClass("is-active");
            $(".catalog-tree__item[data-catalog-main-category=\"" + filters.main_category + "\"][data-catalog-category=\"" + filters.category + "\"][data-catalog-subject=\"" + filters.subject + "\"]").addClass("is-active");
            $(".catalog-topic-pill[data-catalog-main-category=\"" + filters.main_category + "\"][data-catalog-category=\"" + filters.category + "\"][data-catalog-subject=\"" + filters.subject + "\"]").addClass("is-active");
        } else if (filters.category) {
            $(".catalog-tree__summary-link[data-catalog-main-category=\"" + filters.main_category + "\"][data-catalog-category=\"" + filters.category + "\"]").addClass("is-active");
            $(".catalog-tree__item[data-catalog-main-category=\"" + filters.main_category + "\"][data-catalog-category=\"" + filters.category + "\"]:not([data-catalog-subject])").addClass("is-active");
            $(".catalog-topic-pill[data-catalog-main-category=\"" + filters.main_category + "\"][data-catalog-category=\"" + filters.category + "\"]:not([data-catalog-subject])").addClass("is-active");
        } else {
            $(".catalog-tree__item[data-catalog-main-category=\"" + filters.main_category + "\"]:not([data-catalog-category])").addClass("is-active");
        }
    }
}

function fetchCatalogData(page = 1, filters = {}, options = {}) {
    var normalizedFilters = normalizeCatalogFilters(filters);
    var appendItems = options.append === true;
    var filterParams = buildCatalogQuery(page, normalizedFilters);

    $.ajax({
        url: base_url + "/fetch-catalog?" + filterParams,
        beforeSend: function () {
            catalogPlaceholder.removeClass("d-none");
            if (!appendItems) {
                $(".catalog-holder").attr("class", "catalog-holder row g-1 courses__grid-wrap row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-3");
                $(".catalog-holder").html(renderCatalogLoadingMarkup());
            }
            scrollToElement(".top-baseline");
        },
        success: function (data) {
            $(".catalog-holder").attr("class", data.holderClass || "catalog-holder row courses__grid-wrap");
            if (appendItems) {
                $(".catalog-holder").append(data.items);
            } else {
                $(".catalog-holder").html(data.items);
            }
            updateCatalogPageHeader(data);
            $(".sub-category-holder").html(data.sidebar_items);
            if (data.toolbar !== undefined) {
                $(".catalog-toolbar-holder").html(data.toolbar);
            }
            $(".catalog-count").text((data.summary && data.summary.total) || data.itemCount || 0);
            if (data.summary) {
                $("[data-catalog-total]").text(data.summary.total || 0);
                Object.keys(data.summary).forEach(function (key) {
                    $("[data-summary-key=\"" + key + "\"] strong").text(data.summary[key] || 0);
                });
            }
            catalogPlaceholder.addClass("d-none");
            catalogState.page = Number(data.currentPage || page);
            catalogState.lastPage = Number(data.lastPage || page);

            if ($(".catalog-load-more").length > 0) {
                $(".pagination-wrap").addClass("d-none");
                if (catalogState.page < catalogState.lastPage) {
                    $(".catalog-load-more").removeClass("d-none");
                } else {
                    $(".catalog-load-more").addClass("d-none");
                }
            } else if ((data.summary && data.summary.total) || data.itemCount != 0) {
                updateCatalogPaginationLinks(page, data.lastPage);
                $(".pagination-wrap").removeClass("d-none");
            } else {
                $(".pagination-wrap").addClass("d-none");
            }

        },
        error: function () {
            catalogPlaceholder.addClass("d-none");
            if (!appendItems) {
                $(".catalog-holder").html(`
                    <div class="w-100">
                        <div class="catalog-empty-card">
                            <div class="catalog-empty-card__icon"><i class="fas fa-cloud-exclamation"></i></div>
                            <h6>${window.__catalogStrings?.loadFailedTitle || "Could not load resources"}</h6>
                            <p>${window.__catalogStrings?.loadFailedMessage || "Please try again in a moment."}</p>
                        </div>
                    </div>
                `);
            }
            toastr.error("Something went wrong! please reload the page");
        },
    });

    catalogFilters = normalizedFilters;
    syncCatalogSelectionState(catalogFilters);

    if (options.updateHistory !== false) {
        var url = filterParams.length > 0 ? window.location.pathname + "?" + filterParams : window.location.pathname;
        history.pushState({ page: page, filters: catalogFilters }, null, url);
    }
}

function updateCatalogPaginationLinks(currentPage, lastPage) {
    currentPage = Number(currentPage);
    lastPage = Number(lastPage);

    var paginationHtml = '<ul class="pagination justify-content-center">';
    var maxPagesToShow = 8;
    var half = Math.floor(maxPagesToShow / 2);
    var startPage = Math.max(currentPage - half, 1);
    var endPage = Math.min(currentPage + half, lastPage);

    if (startPage === 1) {
        endPage = Math.min(maxPagesToShow, lastPage);
    }
    if (endPage === lastPage) {
        startPage = Math.max(lastPage - maxPagesToShow + 1, 1);
    }

    if (currentPage > 1) {
        paginationHtml += `<li class="page-item"><a class="page-link" href="?page=${currentPage - 1}"><i class="fas fa-chevron-left"></i></a></li>`;
    } else {
        paginationHtml += `<li class="page-item disabled"><span class="page-link"><i class="fas fa-chevron-left"></i></span></li>`;
    }

    for (var i = startPage; i <= endPage; i++) {
        paginationHtml += `<li class="page-item ${i === currentPage ? "active" : ""}"><a class="page-link" href="?page=${i}">${i}</a></li>`;
    }

    if (currentPage < lastPage) {
        paginationHtml += `<li class="page-item"><a class="page-link" href="?page=${currentPage + 1}"><i class="fas fa-chevron-right"></i></a></li>`;
    } else {
        paginationHtml += `<li class="page-item disabled"><span class="page-link"><i class="fas fa-chevron-right"></i></span></li>`;
    }

    paginationHtml += "</ul>";
    $(".pagination").html(paginationHtml);
}

$(document).on("click", ".pagination a", function (event) {
    event.preventDefault();
    var page = new URLSearchParams($(this).attr("href").replace("?", "")).get("page") || 1;
    fetchCatalogData(page, catalogFilters);
});

$(document).on("click", ".catalog-load-more__btn", function () {
    if (catalogState.page < catalogState.lastPage) {
        fetchCatalogData(catalogState.page + 1, catalogFilters, { append: true });
    }
});

$(document).on("click", ".catalog-type-tab[data-type]", function () {
    if ($(this).is("a")) {
        return;
    }

    $(".catalog-type-tab").removeClass("is-active");
    $(this).addClass("is-active");
    var nextType = $(this).data("type") || "";
    var nextFilters = $.extend({}, catalogFilters);

    if (nextType) {
        nextFilters.type = nextType;
    } else {
        delete nextFilters.type;
    }

    fetchCatalogData(1, nextFilters);
});

$(document).on("click", ".catalog-tree__item, .catalog-tree__summary-link, .catalog-topic-pill", function (event) {
    var $target = $(this);
    var href = $target.attr("href");
    var nextFilters = extractCatalogFiltersFromHref(href);
    var selectedDepth = Number($target.data("catalog-depth") || 0);

    if (!href || !nextFilters.main_category) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();

    fetchCatalogData(1, nextFilters);

    if (selectedDepth >= 2) {
        setCatalogCurriculumOpen(false);
    }
});

$(".orderby").on("change", function () {
    catalogFilters.order = $(this).val();
    fetchCatalogData(1, catalogFilters);
});

function setCatalogRightbarOpen(isOpen) {
    var $shell = $(".catalog-shell");
    var $drawer = $(".catalog-rightbar--drawer");
    var $toggle = $("[data-rightbar-action=\"toggle\"]");

    if ($shell.length === 0 || $drawer.length === 0) {
        return;
    }

    $shell.toggleClass("catalog-shell--rightbar-open", isOpen);
    $shell.toggleClass("catalog-shell--rightbar-collapsed", !isOpen);
    $drawer.attr("aria-hidden", "false");
    $toggle.attr("aria-expanded", isOpen ? "true" : "false");
    $toggle.each(function () {
        var $button = $(this);
        var label = isOpen ? $button.data("label-close") : $button.data("label-open");
        $button.find("span").text(label);
        $button.attr("aria-label", label);
    });
}

function setCatalogCurriculumOpen(isOpen) {
    var $shell = $(".catalog-shell");
    var $drawer = $(".catalog-shell__curriculum-col");
    var $toggle = $("[data-curriculum-action=\"toggle\"]");
    var $backdrop = $(".catalog-curriculum-backdrop");

    if ($shell.length === 0 || $drawer.length === 0) {
        return;
    }

    $shell.toggleClass("catalog-shell--curriculum-open", isOpen);
    $shell.toggleClass("catalog-shell--curriculum-collapsed", !isOpen);
    $drawer.attr("aria-hidden", isOpen ? "false" : "true");
    $backdrop.attr("aria-hidden", isOpen ? "false" : "true");
    $toggle.attr("aria-expanded", isOpen ? "true" : "false");
    $toggle.each(function () {
        var $button = $(this);
        var label = isOpen ? $button.data("label-close") : $button.data("label-open");
        $button.find("span").text(label);
        $button.attr("aria-label", label);
    });
}

function syncCatalogCurriculumMode() {
    var isMobile = window.matchMedia("(max-width: 991.98px)").matches;

    if (isMobile) {
        setCatalogCurriculumOpen(false);
        return;
    }

    var $shell = $(".catalog-shell");
    $shell.removeClass("catalog-shell--curriculum-open catalog-shell--curriculum-collapsed");
    $(".catalog-shell__curriculum-col").attr("aria-hidden", "false");
    $(".catalog-curriculum-backdrop").attr("aria-hidden", "true");
    $("[data-curriculum-action=\"toggle\"]").attr("aria-expanded", "false");
}

function wireCatalogAccordion() {
    document.querySelectorAll(".catalog-tree__group").forEach(function (group) {
        group.addEventListener("toggle", function () {
            if (!group.open) {
                return;
            }

            var parent = group.parentElement;
            if (!parent) {
                return;
            }

            Array.from(parent.children).forEach(function (sibling) {
                if (sibling !== group && sibling.tagName === "DETAILS") {
                    sibling.removeAttribute("open");
                }
            });
        });
    });
}

$(document).on("click", "[data-rightbar-action]", function (event) {
    event.preventDefault();
    var $shell = $(".catalog-shell");
    var isOpen = $shell.hasClass("catalog-shell--rightbar-open");
    var action = $(this).data("rightbar-action");

    if (action === "close") {
        setCatalogRightbarOpen(false);
        return;
    }

    if (action === "toggle") {
        setCatalogRightbarOpen(!isOpen);
    }
});

$(document).on("click", "[data-curriculum-action]", function (event) {
    event.preventDefault();
    var $shell = $(".catalog-shell");
    var isOpen = $shell.hasClass("catalog-shell--curriculum-open");
    var action = $(this).data("curriculum-action");

    if (action === "close") {
        setCatalogCurriculumOpen(false);
        return;
    }

    if (action === "toggle") {
        setCatalogCurriculumOpen(!isOpen);
    }
});

$(document).on("click", function (event) {
    var $shell = $(".catalog-shell");

    if (!$shell.hasClass("catalog-shell--curriculum-open")) {
        return;
    }

    if ($(event.target).closest(".catalog-shell__curriculum-col, [data-curriculum-action=\"toggle\"], .catalog-curriculum-backdrop").length > 0) {
        return;
    }

    setCatalogCurriculumOpen(false);
});

$(document).on("keydown", function (event) {
    if (event.key === "Escape") {
        setCatalogCurriculumOpen(false);
        setCatalogRightbarOpen(false);
    }
});

$(document).ready(function () {
    var urlParams = new URLSearchParams(window.location.search);
    var page = urlParams.get("page") || 1;

    ["search", "category", "main_category", "subject", "type", "order"].forEach(function (key) {
        if (urlParams.get(key) !== null) {
            catalogFilters[key] = urlParams.get(key);
        }
    });
    if (catalogFilters.category) {
        catalogFilters.category = catalogFilters.category.split(",")[0];
    }

    syncCatalogSelectionState(catalogFilters);
    syncCatalogCurriculumMode();
    var curriculumDrawerQuery = window.matchMedia("(max-width: 991.98px)");
    if (typeof curriculumDrawerQuery.addEventListener === "function") {
        curriculumDrawerQuery.addEventListener("change", syncCatalogCurriculumMode);
    } else if (typeof curriculumDrawerQuery.addListener === "function") {
        curriculumDrawerQuery.addListener(syncCatalogCurriculumMode);
    }
    setCatalogRightbarOpen(false);
    wireCatalogAccordion();
    fetchCatalogData(page, catalogFilters);
});
