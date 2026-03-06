<style>
    /* Styling for the Participant Cards */
    .participant-card {
        background-color: #f8fafc;
        border: 1px solid transparent;
        transition: all 0.2s ease-in-out;
        margin-left: -8px; /* Offset the padding for alignment */
    }
    .participant-card:hover {
        background-color: #ffffff;
        border-color: #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        transform: translateY(-2px);
    }

    /* Avatar Circles */
    .avatar-circle {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 18px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    /* Better Legend Spacing */
    .legend .form-group {
        margin-bottom: 1.5rem !important;
        border-bottom: 1px dashed #e2e8f0;
        padding-bottom: 1.5rem;
    }
    .legend .form-group:last-child {
        border-bottom: none;
    }
</style>
