<style>
    .note-builder-page {
        display: grid;
        gap: 8px;
        padding-bottom: 12px;
        min-width: 0;
    }

    .note-builder-page__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        flex-wrap: wrap;
    }

    .note-breadcrumb {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #667085;
        font-size: 11px;
        font-weight: 500;
        flex-wrap: wrap;
    }

    .note-breadcrumb a {
        color: #5b57d6;
        font-weight: 700;
    }

    .note-preview-button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        border: 1px solid #5b57d6;
        border-radius: 12px;
        background: #fff;
        color: #5b57d6;
        font-weight: 700;
        box-shadow: 0 10px 24px rgba(91, 87, 214, 0.08);
        transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
    }

    .note-preview-button:hover {
        transform: translateY(-1px);
        background: rgba(91, 87, 214, 0.04);
        box-shadow: 0 14px 28px rgba(91, 87, 214, 0.12);
    }

    .note-builder-page__hero {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 248px;
        gap: 8px;
        align-items: stretch;
    }

    .note-builder-page__eyebrow {
        margin: 0 0 2px;
        color: #5b57d6;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .note-builder-page__hero h1 {
        margin: 0;
        font-size: clamp(24px, 2.6vw, 34px);
        line-height: 1.02;
        color: #16213f;
        font-weight: 900;
    }

    .note-builder-page__hero p {
        max-width: 720px;
        margin: 4px 0 0;
        color: #667085;
        font-size: 13px;
        line-height: 1.45;
    }

    .note-builder-page__hero-card {
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 4px;
        padding: 14px;
        border: 1px solid rgba(91, 87, 214, 0.14);
        border-radius: 18px;
        background: linear-gradient(145deg, rgba(91, 87, 214, 0.09), rgba(255, 255, 255, 0.95));
        box-shadow: 0 18px 42px rgba(20, 33, 61, 0.05);
    }

    .note-builder-page__hero-card span {
        color: #5b57d6;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .note-builder-page__hero-card strong {
        color: #16213f;
        font-size: 16px;
        font-weight: 800;
    }

    .note-builder-page__hero-card p {
        margin: 0;
        color: #667085;
        font-size: 13px;
        line-height: 1.6;
    }

    .note-stepper {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 6px;
        padding: 0;
    }

    .note-stepper__item {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: center;
        gap: 12px;
        min-width: 0;
        min-height: 44px;
    }

    .note-stepper__item[data-note-step-url] {
        cursor: pointer;
        border-radius: 16px;
        padding: 4px 6px;
        transition: transform 0.18s ease, background 0.18s ease, box-shadow 0.18s ease;
    }

    .note-stepper__item[data-note-step-url]:hover,
    .note-stepper__item[data-note-step-url]:focus-visible {
        background: rgba(91, 87, 214, 0.05);
        transform: translateY(-1px);
        box-shadow: 0 10px 22px rgba(91, 87, 214, 0.08);
        outline: 0;
    }

    .note-stepper__dot {
        display: grid;
        place-items: center;
        width: 34px;
        height: 34px;
        border-radius: 999px;
        border: 1px solid #d9e0f0;
        background: #fff;
        color: #49607f;
        font-size: 14px;
        font-weight: 800;
        flex: 0 0 auto;
        transition: background 0.18s ease, border-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease;
    }

    .note-stepper__label {
        color: #4e5e77;
        font-size: 14px;
        font-weight: 700;
        white-space: nowrap;
    }

    .note-stepper__line {
        display: block;
        height: 1px;
        width: 100%;
        background: linear-gradient(90deg, rgba(215, 222, 238, 0.05), #d7deee 20%, #d7deee 80%, rgba(215, 222, 238, 0.05));
    }

    .note-stepper__item.is-active .note-stepper__dot {
        border-color: transparent;
        background: linear-gradient(135deg, #5b57d6, #6d4fff);
        color: #fff;
        box-shadow: 0 12px 24px rgba(91, 87, 214, 0.28);
    }

    .note-stepper__item.is-active .note-stepper__label {
        color: #16213f;
        font-weight: 800;
    }

    .note-stepper__item.is-completed .note-stepper__dot {
        border-color: transparent;
        background: rgba(22, 163, 74, 0.14);
        color: #15945c;
    }

    .note-stepper__item.is-completed .note-stepper__label {
        color: #16213f;
    }

    @media (max-width: 1280px) {
        .note-builder-page__hero {
            grid-template-columns: minmax(0, 1fr);
        }
    }

    @media (max-width: 991px) {
        .note-stepper {
            display: flex;
            grid-template-columns: none;
            gap: 8px;
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 8px;
            scroll-snap-type: x proximity;
            scrollbar-width: thin;
            scrollbar-color: rgba(91, 87, 214, 0.5) rgba(91, 141, 239, 0.08);
        }

        .note-stepper__item {
            grid-template-columns: auto minmax(0, 1fr);
            flex: 0 0 240px;
            padding: 10px 12px;
            border: 1px solid rgba(215, 222, 238, 0.95);
            background: #fff;
            box-shadow: 0 10px 22px rgba(20, 33, 61, 0.04);
            scroll-snap-align: center;
        }

        .note-stepper__line {
            display: none;
        }

        .note-stepper__item.is-active {
            background: linear-gradient(145deg, rgba(91, 87, 214, 0.1), rgba(255, 255, 255, 0.98));
            border-color: rgba(91, 87, 214, 0.45);
            box-shadow: 0 16px 30px rgba(91, 87, 214, 0.18);
            transform: translateY(-1px) scale(1.02);
        }

        .note-stepper__item.is-active .note-stepper__dot {
            box-shadow: 0 0 0 5px rgba(91, 87, 214, 0.16), 0 14px 26px rgba(91, 87, 214, 0.3);
        }

        .note-stepper__item.is-active .note-stepper__label {
            color: #3f37c9;
            text-shadow: 0 1px 0 rgba(255, 255, 255, 0.8);
        }

        .note-stepper::-webkit-scrollbar {
            height: 8px;
        }

        .note-stepper::-webkit-scrollbar-track {
            background: rgba(91, 141, 239, 0.08);
            border-radius: 999px;
        }

        .note-stepper::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, rgba(91, 87, 214, 0.78), rgba(109, 79, 255, 0.86));
            border-radius: 999px;
        }
    }

    @media (max-width: 767px) {
        .note-builder-page__top {
            align-items: flex-start;
            flex-direction: column;
        }

        .note-builder-page__hero h1 {
            font-size: 26px;
        }

        .note-builder-page__hero p {
            font-size: 12px;
        }

        .note-preview-button {
            width: 100%;
            justify-content: center;
        }
    }
</style>
