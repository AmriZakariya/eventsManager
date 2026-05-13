<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account | HYGIE-CLEAN EXPO 2026</title>
    <style>
        :root {
            --bg: #101624;
            --panel: #151d2e;
            --panel-soft: #1b263b;
            --accent: #02ca67;
            --accent-blue: #00a1ec;
            --text: #f8fafc;
            --muted: #a7b4c8;
            --border: rgba(255, 255, 255, 0.1);
            --danger: #f97373;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(2, 202, 103, 0.16), transparent 28rem),
                linear-gradient(135deg, #101624 0%, #0f172a 56%, #122033 100%);
            color: var(--text);
            line-height: 1.65;
        }

        main {
            width: min(1040px, calc(100% - 32px));
            margin: 0 auto;
            padding: 64px 0;
        }

        .hero {
            max-width: 780px;
            margin-bottom: 34px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
            padding: 8px 14px;
            border: 1px solid rgba(2, 202, 103, 0.28);
            border-radius: 999px;
            color: var(--accent);
            background: rgba(2, 202, 103, 0.1);
            font-size: 13px;
            font-weight: 700;
        }

        h1 {
            margin: 0 0 14px;
            font-size: clamp(34px, 5vw, 56px);
            line-height: 1.05;
            letter-spacing: 0;
        }

        h2 {
            margin: 0 0 14px;
            font-size: 22px;
        }

        p {
            margin: 0 0 16px;
            color: var(--muted);
        }

        a {
            color: var(--accent);
        }

        .grid {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(320px, 0.9fr);
            gap: 24px;
            align-items: start;
        }

        .panel {
            border: 1px solid var(--border);
            border-radius: 8px;
            background: rgba(21, 29, 46, 0.88);
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.28);
            padding: 28px;
        }

        .section {
            padding: 24px 0;
            border-top: 1px solid var(--border);
        }

        .section:first-child {
            padding-top: 0;
            border-top: 0;
        }

        ol, ul {
            margin: 0;
            padding-left: 22px;
            color: var(--muted);
        }

        li {
            margin-bottom: 10px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 700;
        }

        input, textarea {
            width: 100%;
            margin-bottom: 16px;
            padding: 13px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--panel-soft);
            color: var(--text);
            font: inherit;
        }

        input:focus, textarea:focus {
            border-color: var(--accent);
            outline: 2px solid rgba(2, 202, 103, 0.2);
        }

        textarea {
            min-height: 130px;
            resize: vertical;
        }

        button {
            width: 100%;
            border: 0;
            border-radius: 8px;
            padding: 14px 18px;
            background: linear-gradient(135deg, var(--accent), var(--accent-blue));
            color: #04111d;
            cursor: pointer;
            font: inherit;
            font-weight: 800;
        }

        .notice {
            margin-bottom: 18px;
            border: 1px solid rgba(2, 202, 103, 0.32);
            border-radius: 8px;
            background: rgba(2, 202, 103, 0.12);
            color: #d7ffe9;
            padding: 14px;
        }

        .errors {
            margin-bottom: 18px;
            border: 1px solid rgba(249, 115, 115, 0.35);
            border-radius: 8px;
            background: rgba(249, 115, 115, 0.12);
            color: #ffd5d5;
            padding: 14px 14px 14px 34px;
        }

        .small {
            font-size: 14px;
        }

        footer {
            margin-top: 34px;
            color: #7f8da3;
            font-size: 14px;
        }

        @media (max-width: 820px) {
            main {
                padding: 42px 0;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .panel {
                padding: 22px;
            }
        }
    </style>
</head>
<body>
<main>
    <section class="hero">
        <div class="badge">HYGIE-CLEAN EXPO 2026</div>
        <h1>Request account deletion</h1>
        <p>
            Use this page to request deletion of your HYGIE-CLEAN EXPO 2026 mobile app account and associated personal data.
            This request is handled by the Event manager support team.
        </p>
    </section>

    <div class="grid">
        <div class="panel">
            <div class="section">
                <h2>Steps to delete your account</h2>
                <ol>
                    <li>Submit the request form on this page using the email address linked to your mobile app account.</li>
                    <li>Our support team may contact you by email to verify account ownership before deletion.</li>
                    <li>After verification, we delete or anonymize eligible account data and confirm completion by email.</li>
                </ol>
            </div>

            <div class="section">
                <h2>Data deleted</h2>
                <ul>
                    <li>Account profile details, including name, email address, phone number, role, and company details linked to your profile.</li>
                    <li>App preferences, favorites, device notification tokens, and personal notification records.</li>
                    <li>Networking data linked to your account, including connection requests, conversations, and appointment participation where deletion is allowed.</li>
                </ul>
            </div>

            <div class="section">
                <h2>Data kept and retention</h2>
                <p>
                    Some information may be kept when required for security, legal compliance, dispute resolution, audit logs,
                    or event operation records. We may keep limited deletion request records for up to 12 months, and backup
                    copies may remain for up to 90 days before they are overwritten. Aggregated or anonymized event analytics
                    may be kept because they no longer identify you.
                </p>
            </div>

            <div class="section">
                <h2>Processing time</h2>
                <p>
                    We normally complete verified deletion requests within 30 days. If more time is required because of legal
                    or operational obligations, we will explain the reason by email.
                </p>
            </div>
        </div>

        <aside class="panel">
            <h2>Submit your request</h2>

            @if (session('status'))
                <div class="notice">{{ session('status') }}</div>
            @endif

            @if (isset($errors) && $errors->any())
                <ul class="errors">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <form method="POST" action="{{ route('account.delete.submit') }}">
                @csrf

                <label for="name">Name</label>
                <input id="name" name="name" type="text" autocomplete="name" value="{{ old('name') }}">

                <label for="email">Account email</label>
                <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}">

                <label for="message">Additional details</label>
                <textarea id="message" name="message" placeholder="Optional: add your company, phone number, or any details that help us identify your account.">{{ old('message') }}</textarea>

                <button type="submit">Request deletion</button>
            </form>

            <p class="small" style="margin-top: 18px;">
                You can also email <a href="mailto:support@hygiecleanexpo.com?subject=Account%20deletion%20request">support@hygiecleanexpo.com</a>
                with the subject "Account deletion request".
            </p>
        </aside>
    </div>

    <footer>
        &copy; {{ date('Y') }} HYGIE-CLEAN EXPO. Account deletion URL: {{ url('/delete-account') }}
    </footer>
</main>
</body>
</html>
