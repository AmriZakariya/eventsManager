<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions | HYGIE-CLEAN EXPO</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --accent-color: #02CA67; /* Le vert de votre app */
            --blue-accent: #00A1EC;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background-color: var(--bg-color);
            margin: 0;
            padding: 0;
        }

        .header-bg {
            background: linear-gradient(135deg, rgba(2, 202, 103, 0.15) 0%, rgba(15, 23, 42, 0) 100%);
            padding: 60px 20px;
            text-align: center;
        }

        .container {
            max-width: 800px;
            margin: -40px auto 60px;
            padding: 20px;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
        }

        h1 {
            font-size: 32px;
            margin-bottom: 10px;
            background: linear-gradient(to right, #fff, var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .last-updated {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 0;
        }

        .section {
            margin-top: 40px;
            display: flex;
            align-items: flex-start;
        }

        .icon-box {
            background: rgba(2, 202, 103, 0.1);
            color: var(--accent-color);
            padding: 10px;
            border-radius: 12px;
            margin-right: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .content {
            flex: 1;
        }

        h2 {
            font-size: 18px;
            color: var(--accent-color);
            margin: 0 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        p, li {
            font-size: 15px;
            color: var(--text-secondary);
            margin-bottom: 15px;
        }

        ul {
            padding-left: 20px;
            margin: 0;
        }

        li {
            margin-bottom: 8px;
        }

        .footer-note {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            font-size: 13px;
            color: var(--text-secondary);
        }

        /* Style mobile */
        @media (max-width: 600px) {
            .card {
                padding: 25px;
                border-radius: 0;
                border: none;
                background: transparent;
                box-shadow: none;
            }
            .container {
                margin-top: -20px;
            }
            .header-bg {
                padding: 40px 20px;
            }
        }
    </style>
</head>
<body>

<div class="header-bg">
    <h1>Conditions d'Utilisation</h1>
    <p class="last-updated">Dernière mise à jour : {{ date('d F Y') }}</p>
</div>

<div class="container">
    <div class="card">

        <div class="section">
            <div class="icon-box">
                <span class="material-icons-round">info</span>
            </div>
            <div class="content">
                <h2>1. Introduction</h2>
                <p>Bienvenue sur notre application. En accédant ou en utilisant notre application mobile et les services associés, vous acceptez d'être lié par ces conditions. Si vous n'acceptez pas une partie de ces conditions, vous ne pouvez pas accéder au service.</p>
            </div>
        </div>

        <div class="section">
            <div class="icon-box">
                <span class="material-icons-round">account_circle</span>
            </div>
            <div class="content">
                <h2>2. Comptes Utilisateurs</h2>
                <ul>
                    <li>Vous êtes responsable de la protection du mot de passe utilisé pour accéder au service.</li>
                    <li>Vous acceptez de ne pas divulguer votre mot de passe à des tiers.</li>
                    <li>Vous devez nous informer immédiatement de toute violation de la sécurité.</li>
                </ul>
            </div>
        </div>

        <div class="section">
            <div class="icon-box">
                <span class="material-icons-round">event</span>
            </div>
            <div class="content">
                <h2>3. Participation à l'Événement</h2>
                <p>En tant que visiteur, exposant ou conférencier, vous acceptez de vous comporter de manière professionnelle. Nous nous réservons le droit de révoquer l'accès en cas de violation.</p>
            </div>
        </div>

        <div class="section">
            <div class="icon-box">
                <span class="material-icons-round">security</span>
            </div>
            <div class="content">
                <h2>4. Confidentialité</h2>
                <p>Votre vie privée est importante. L'utilisation de vos informations personnelles est régie par notre Politique de Confidentialité, disponible dans les réglages de l'application.</p>
            </div>
        </div>

        <div class="section">
            <div class="icon-box">
                <span class="material-icons-round">gavel</span>
            </div>
            <div class="content">
                <h2>5. Propriété Intellectuelle</h2>
                <p>Le service et son contenu original, ses caractéristiques et ses fonctionnalités restent la propriété exclusive de notre société et de ses concédants de licence.</p>
            </div>
        </div>

        <div class="section">
            <div class="icon-box">
                <span class="material-icons-round">mail</span>
            </div>
            <div class="content">
                <h2>6. Contact</h2>
                <p>Pour toute question concernant ces conditions, contactez-nous à : <br>
                    <strong style="color: white;">support@hygiecleanexpo.com</strong></p>
            </div>
        </div>

        <div class="footer-note">
            &copy; {{ date('Y') }} HYGIE-CLEAN EXPO - Tous droits réservés.
        </div>
    </div>
</div>

</body>
</html>
