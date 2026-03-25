<style>
    .connection-persona {
        min-width: 0;
    }

    .connection-persona a {
        transition: color .2s ease, transform .2s ease;
    }

    .connection-persona a:hover {
        color: #0f766e !important;
    }

    .connection-detail-card {
        display: flex;
        gap: 14px;
        align-items: center;
        padding: 14px 16px;
        border-radius: 16px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #e2e8f0;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
    }

    .connection-detail-card:hover {
        border-color: #99f6e4;
        box-shadow: 0 12px 28px rgba(13, 148, 136, 0.12);
        transform: translateY(-1px);
    }

    .connection-detail-avatar {
        width: 52px;
        height: 52px;
        border-radius: 999px;
        object-fit: cover;
        border: 2px solid #ffffff;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.12);
    }

    .legend .form-group {
        margin-bottom: 1.35rem !important;
        border-bottom: 1px solid #edf2f7;
        padding-bottom: 1.35rem;
    }

    .legend .form-group:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
</style>
