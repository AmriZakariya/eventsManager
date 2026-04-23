<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f4f6f8;
            --card: #ffffff;
            --text: #163247;
            --muted: #5d7384;
            --accent: #006d77;
            --accent-hover: #00545c;
            --border: #d9e2e8;
            --error-bg: #fcebea;
            --error-text: #9b1c1c;
            --success-bg: #e6fffa;
            --success-text: #0f5132;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background:
                radial-gradient(circle at top right, rgba(0, 109, 119, 0.16), transparent 30%),
                linear-gradient(180deg, #f7fafb 0%, var(--bg) 100%);
            color: var(--text);
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .card {
            width: 100%;
            max-width: 440px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 20px 50px rgba(22, 50, 71, 0.08);
            padding: 28px;
        }

        h1 {
            margin: 0 0 12px;
            font-size: 28px;
            line-height: 1.1;
        }

        p {
            margin: 0 0 20px;
            color: var(--muted);
            line-height: 1.5;
        }

        .field {
            margin-bottom: 16px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 16px;
        }

        input:focus {
            outline: 2px solid rgba(0, 109, 119, 0.2);
            border-color: var(--accent);
        }

        button {
            width: 100%;
            border: 0;
            border-radius: 12px;
            padding: 13px 16px;
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            background: var(--accent);
            cursor: pointer;
        }

        button:hover {
            background: var(--accent-hover);
        }

        button:disabled {
            opacity: 0.7;
            cursor: wait;
        }

        .alert {
            display: none;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 16px;
            font-size: 14px;
            line-height: 1.5;
        }

        .alert.show {
            display: block;
        }

        .alert.error {
            background: var(--error-bg);
            color: var(--error-text);
        }

        .alert.success {
            background: var(--success-bg);
            color: var(--success-text);
        }

        .footer {
            margin-top: 16px;
            font-size: 13px;
            color: var(--muted);
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Reset Password</h1>
        <p>Enter your new password below to complete the password reset request.</p>

        <div id="message" class="alert"></div>

        <form id="reset-form">
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ request('email') }}" readonly>
            </div>

            <div class="field">
                <label for="password">New Password</label>
                <input id="password" name="password" type="password" minlength="6" required>
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm New Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" minlength="6" required>
            </div>

            <input id="token" name="token" type="hidden" value="{{ request('token') }}">

            <button id="submit-button" type="submit">Update Password</button>
        </form>

        <div class="footer">This page is secure and linked to your password reset email.</div>
    </div>

    <script>
        const form = document.getElementById('reset-form');
        const messageBox = document.getElementById('message');
        const submitButton = document.getElementById('submit-button');
        const token = document.getElementById('token').value;
        const email = document.getElementById('email').value;

        const showMessage = (text, type) => {
            messageBox.textContent = text;
            messageBox.className = `alert ${type} show`;
        };

        if (!token || !email) {
            showMessage('This password reset link is invalid or incomplete.', 'error');
            submitButton.disabled = true;
        }

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const password = document.getElementById('password').value;
            const passwordConfirmation = document.getElementById('password_confirmation').value;

            if (password !== passwordConfirmation) {
                showMessage('The password confirmation does not match.', 'error');
                return;
            }

            submitButton.disabled = true;
            showMessage('Updating your password...', 'success');

            try {
                const response = await fetch('/api/auth/reset-password', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        token,
                        email,
                        password,
                        password_confirmation: passwordConfirmation,
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Unable to reset your password.');
                }

                form.reset();
                document.getElementById('email').value = email;
                document.getElementById('token').value = token;
                showMessage(data.message || 'Your password has been reset successfully.', 'success');
            } catch (error) {
                showMessage(error.message || 'Unable to reset your password.', 'error');
            } finally {
                submitButton.disabled = false;
            }
        });
    </script>
</body>
</html>
