<style>
    body.instructor-dashboard-page .note-chapter-row,
    body.instructor-dashboard-page .note-file-row,
    body.instructor-dashboard-page .note-toggle-row,
    body.instructor-dashboard-page .note-visibility-option,
    body.instructor-dashboard-page .note-review-file,
    body.instructor-dashboard-page .note-review-settings > div {
        border: 1px solid #e1e6f1;
        border-radius: 14px;
        background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
        box-shadow: 0 10px 22px rgba(20, 33, 61, 0.04);
    }

    body.instructor-dashboard-page .note-chapter-row,
    body.instructor-dashboard-page .note-file-row,
    body.instructor-dashboard-page .note-review-file {
        padding: 12px 14px;
        min-height: 62px;
    }

    body.instructor-dashboard-page .note-toggle-row,
    body.instructor-dashboard-page .note-visibility-option,
    body.instructor-dashboard-page .note-review-settings > div {
        padding: 14px 16px;
    }

    body.instructor-dashboard-page .note-chapter-row__handle,
    body.instructor-dashboard-page .note-file-row__icon,
    body.instructor-dashboard-page .note-review-file__icon,
    body.instructor-dashboard-page .note-visibility-option__dot {
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        border-radius: 12px;
    }

    body.instructor-dashboard-page .note-chapter-row__handle,
    body.instructor-dashboard-page .note-file-row__icon,
    body.instructor-dashboard-page .note-review-file__icon {
        width: 40px;
        height: 40px;
    }

    body.instructor-dashboard-page .note-visibility-option__dot {
        width: 22px;
        height: 22px;
        border: 2px solid #cfd7e8;
        background: #fff;
    }

    body.instructor-dashboard-page .note-file-row__actions button,
    body.instructor-dashboard-page .note-chapter-row__actions button {
        display: grid;
        place-items: center;
        width: 34px;
        height: 34px;
        border: 0;
        border-radius: 10px;
        background: rgba(91, 87, 214, 0.06);
        color: #52607b;
        transition: background 0.18s ease, color 0.18s ease, transform 0.18s ease;
    }

    body.instructor-dashboard-page .note-file-row__actions button:hover,
    body.instructor-dashboard-page .note-chapter-row__actions button:hover {
        background: rgba(91, 87, 214, 0.12);
        color: #5b57d6;
        transform: translateY(-1px);
    }

    body.instructor-dashboard-page .note-switch {
        position: relative;
        width: 52px;
        height: 28px;
        flex: 0 0 auto;
    }

    body.instructor-dashboard-page .note-switch input {
        position: absolute;
        opacity: 0;
    }

    body.instructor-dashboard-page .note-switch span {
        position: absolute;
        inset: 0;
        border-radius: 999px;
        background: #d9e1ef;
        transition: background-color 0.2s ease;
        box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.1);
    }

    body.instructor-dashboard-page .note-switch span::after {
        content: '';
        position: absolute;
        top: 4px;
        left: 4px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #fff;
        box-shadow: 0 6px 14px rgba(15, 23, 42, 0.15);
        transition: transform 0.2s ease;
    }

    body.instructor-dashboard-page .note-switch input:checked + span {
        background: linear-gradient(135deg, #5b57d6, #6d4fff);
    }

    body.instructor-dashboard-page .note-switch input:checked + span::after {
        transform: translateX(24px);
    }

    body.instructor-dashboard-page .note-visibility-option {
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
        transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
    }

    body.instructor-dashboard-page .note-visibility-option:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 26px rgba(20, 33, 61, 0.06);
    }

    body.instructor-dashboard-page .note-visibility-option input {
        position: absolute;
        opacity: 0;
    }

    body.instructor-dashboard-page .note-visibility-option strong {
        display: block;
        color: #16213f;
        font-size: 14px;
        font-weight: 800;
    }

    body.instructor-dashboard-page .note-visibility-option small {
        display: block;
        margin-top: 4px;
        color: #6a7287;
        font-size: 12px;
        line-height: 1.45;
    }

    body.instructor-dashboard-page .note-visibility-option input:checked + .note-visibility-option__dot {
        border-color: #5b57d6;
        background: rgba(91, 87, 214, 0.1);
    }

    body.instructor-dashboard-page .note-visibility-option input:checked + .note-visibility-option__dot::after {
        content: '';
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #5b57d6;
    }

    body.instructor-dashboard-page .note-review-file__icon,
    body.instructor-dashboard-page .note-review-settings > div dt {
        color: #5b57d6;
    }

    body.instructor-dashboard-page .note-file-empty,
    body.instructor-dashboard-page .note-review-file-empty {
        display: grid;
        place-items: center;
        gap: 8px;
        padding: 28px;
        border: 1px dashed rgba(91, 87, 214, 0.3);
        border-radius: 16px;
        background: rgba(91, 87, 214, 0.03);
        text-align: center;
        color: #667085;
    }

    body.instructor-dashboard-page .note-file-empty i,
    body.instructor-dashboard-page .note-review-file-empty i {
        color: #5b57d6;
        font-size: 28px;
    }

    body.instructor-dashboard-page .note-file-empty strong,
    body.instructor-dashboard-page .note-review-file-empty strong {
        color: #16213f;
        font-size: 15px;
        font-weight: 800;
    }

    body.instructor-dashboard-page .note-file-empty p,
    body.instructor-dashboard-page .note-review-file-empty p {
        margin: 0;
        font-size: 13px;
        line-height: 1.6;
    }

    body.instructor-dashboard-page .note-review-file {
        display: grid;
        grid-template-columns: 40px minmax(0, 1fr) auto;
        gap: 14px;
        align-items: center;
    }

    body.instructor-dashboard-page .note-review-file__name {
        color: #16213f;
        font-size: 14px;
        font-weight: 800;
    }

    body.instructor-dashboard-page .note-review-file__size {
        color: #667085;
        font-size: 13px;
    }

    body.instructor-dashboard-page .note-review-settings > div {
        display: grid;
        grid-template-columns: minmax(120px, 1fr) minmax(0, 1.6fr);
        gap: 12px;
        align-items: start;
        font-size: 13px;
    }

    @media (max-width: 1200px) {
        body.instructor-dashboard-page .note-chapter-row {
            grid-template-columns: 26px 58px minmax(0, 1fr);
            align-items: start;
        }

        body.instructor-dashboard-page .note-chapter-row__pages {
            text-align: left;
        }

        body.instructor-dashboard-page .note-chapter-row__actions {
            grid-column: 1 / -1;
            justify-content: flex-start;
            margin-top: 6px;
        }
    }

    @media (max-width: 992px) {
        body.instructor-dashboard-page .note-toggle-row,
        body.instructor-dashboard-page .note-review-settings > div,
        body.instructor-dashboard-page .note-review-file {
            grid-template-columns: 1fr;
        }
    }
</style>
