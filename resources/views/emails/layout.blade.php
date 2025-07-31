{-- =============================================== --}}
{{-- resources/views/emails/layout.blade.php --}}
{{-- Template de base pour tous les emails --}}
{{-- =============================================== --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ClicBillet CI')</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #1a237e, #FF6B35);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .email-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .email-body {
            padding: 30px;
        }
        .email-footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            background: #FF6B35;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 20px 0;
        }
        .btn:hover {
            background: #e55a2b;
        }
        .order-summary {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .order-item {
            border-bottom: 1px solid #ddd;
            padding: 10px 0;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .total-row {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 18px;
            color: #1976d2;
        }
        .highlight {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>@yield('header-title', 'ClicBillet CI')</h1>
            <p>@yield('header-subtitle', 'Votre plateforme de billetterie en ligne')</p>
        </div>
        
        <div class="email-body">
            @yield('content')
        </div>
        
        <div class="email-footer">
            <p><strong>ClicBillet CI</strong><br>
            📧 contact@clicbillet.ci | 📞 +225 XX XX XX XX<br>
            🌐 www.clicbillet.ci</p>
            
            <p><small>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</small></p>
        </div>
    </div>
</body>
</html>