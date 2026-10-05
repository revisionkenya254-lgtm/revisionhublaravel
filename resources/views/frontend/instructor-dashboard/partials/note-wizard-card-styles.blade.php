<style>
    body.instructor-dashboard-page .note-card,
    body.instructor-dashboard-page .note-content-card,
    body.instructor-dashboard-page .note-attachments-card,
    body.instructor-dashboard-page .note-settings-card,
    body.instructor-dashboard-page .note-review-card {
        border: 1px solid rgba(216, 222, 234, 0.95);
        border-radius: 20px;
        background: #fff;
        box-shadow: 0 18px 48px rgba(20, 33, 61, 0.05);
    }

    body.instructor-dashboard-page .note-card--main,
    body.instructor-dashboard-page .note-content-card,
    body.instructor-dashboard-page .note-attachments-card,
    body.instructor-dashboard-page .note-settings-card,
    body.instructor-dashboard-page .note-review-card {
        padding: 22px;
    }

    body.instructor-dashboard-page .note-card__head,
    body.instructor-dashboard-page .note-summary__head,
    body.instructor-dashboard-page .note-content-card__head,
    body.instructor-dashboard-page .note-attachments-card__head,
    body.instructor-dashboard-page .note-settings-card__head,
    body.instructor-dashboard-page .note-review-card__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 18px;
    }

    body.instructor-dashboard-page .note-card__head h2,
    body.instructor-dashboard-page .note-summary__head h3,
    body.instructor-dashboard-page .note-content-card__head h2,
    body.instructor-dashboard-page .note-attachments-card__head h2,
    body.instructor-dashboard-page .note-settings-card__head h2,
    body.instructor-dashboard-page .note-review-card__head h2 {
        margin: 0;
        color: #5b57d6;
        font-size: 24px;
        font-weight: 900;
    }

    body.instructor-dashboard-page .note-card__head p,
    body.instructor-dashboard-page .note-summary__head p,
    body.instructor-dashboard-page .note-content-card__head p,
    body.instructor-dashboard-page .note-attachments-card__head p,
    body.instructor-dashboard-page .note-settings-card__head p,
    body.instructor-dashboard-page .note-review-card__head p {
        margin: 6px 0 0;
        color: #6a7287;
        font-size: 13px;
        line-height: 1.6;
    }

    body.instructor-dashboard-page .note-card__footer,
    body.instructor-dashboard-page .note-content-card__footer,
    body.instructor-dashboard-page .note-attachments-card__footer,
    body.instructor-dashboard-page .note-settings-card__footer,
    body.instructor-dashboard-page .note-review-card__footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 18px;
        padding-top: 18px;
        border-top: 1px solid #edf1f7;
    }

    body.instructor-dashboard-page .note-action,
    body.instructor-dashboard-page .note-edit-button,
    body.instructor-dashboard-page .note-publish-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 46px;
        padding: 0 18px;
        border-radius: 12px;
        border: 1px solid transparent;
        font-size: 14px;
        font-weight: 800;
        transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease, border-color 0.18s ease;
    }

    body.instructor-dashboard-page .note-action:hover,
    body.instructor-dashboard-page .note-edit-button:hover,
    body.instructor-dashboard-page .note-publish-button:hover {
        transform: translateY(-1px);
    }

    body.instructor-dashboard-page .note-action--ghost,
    body.instructor-dashboard-page .note-edit-button {
        background: #fff;
        border-color: #d7ddea;
        color: #24304f;
    }

    body.instructor-dashboard-page .note-action--primary,
    body.instructor-dashboard-page .note-publish-button {
        background: linear-gradient(135deg, #5b57d6, #6d4fff);
        color: #fff;
        box-shadow: 0 16px 30px rgba(91, 87, 214, 0.22);
    }

    body.instructor-dashboard-page .note-status-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 14px;
        border-radius: 999px;
        background: rgba(91, 87, 214, 0.1);
        color: #5b57d6;
        font-size: 13px;
        font-weight: 800;
        white-space: nowrap;
    }

    body.instructor-dashboard-page .note-status-pill--success {
        background: rgba(22, 163, 74, 0.12);
        color: #15945c;
    }

    @media (max-width: 992px) {
        body.instructor-dashboard-page .note-card__head,
        body.instructor-dashboard-page .note-summary__head,
        body.instructor-dashboard-page .note-content-card__head,
        body.instructor-dashboard-page .note-attachments-card__head,
        body.instructor-dashboard-page .note-settings-card__head,
        body.instructor-dashboard-page .note-review-card__head,
        body.instructor-dashboard-page .note-card__footer,
        body.instructor-dashboard-page .note-content-card__footer,
        body.instructor-dashboard-page .note-attachments-card__footer,
        body.instructor-dashboard-page .note-settings-card__footer,
        body.instructor-dashboard-page .note-review-card__footer {
            flex-direction: column;
            align-items: stretch;
        }

        body.instructor-dashboard-page .note-action,
        body.instructor-dashboard-page .note-edit-button,
        body.instructor-dashboard-page .note-publish-button {
            width: 100%;
        }
    }
</style>
