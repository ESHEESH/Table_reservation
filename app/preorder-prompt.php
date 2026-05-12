<?php
/**
 * Pre-Order Prompt - Ask if customer wants to pre-order food
 */
$confirmationCode = isset($_GET['code']) ? $_GET['code'] : '';
$tableId = isset($_GET['table_id']) ? (int)$_GET['table_id'] : 0;

if (empty($confirmationCode)) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Order Food? - Sakura Sushi</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #0A0A0F;
            color: #FDF6EC;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal-content {
            background: #14141B;
            border: 1px solid rgba(201,150,79,.2);
            border-radius: 20px;
            padding: 48px 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            animation: slideUp .3s ease;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: rgba(201,150,79,.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .icon svg {
            width: 40px;
            height: 40px;
            color: #C9964F;
        }
        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: #C9964F;
            margin-bottom: 16px;
        }
        p {
            color: rgba(253,246,236,.7);
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        .buttons {
            display: flex;
            gap: 12px;
            flex-direction: column;
        }
        .btn {
            padding: 16px 32px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
            border: none;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #C9964F;
            color: #0A0A0F;
        }
        .btn-primary:hover {
            opacity: .9;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: rgba(255,255,255,.05);
            color: #FDF6EC;
            border: 1px solid rgba(201,150,79,.2);
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,.08);
            border-color: rgba(201,150,79,.4);
        }
        .confirmation-code {
            background: rgba(201,150,79,.1);
            border: 1px solid rgba(201,150,79,.3);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 24px;
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            color: #C9964F;
            letter-spacing: 0.1em;
        }
    </style>
</head>
<body>
    <div class="modal-overlay">
        <div class="modal-content">
            <div class="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2M7 2v20M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/>
                </svg>
            </div>
            
            <h1>Reservation Confirmed!</h1>
            
            <div class="confirmation-code">
                <?php echo htmlspecialchars($confirmationCode); ?>
            </div>
            
            <p>Would you like to pre-order your food? Your meal will be ready when you arrive, saving you time!</p>
            
            <div class="buttons">
                <a href="menu.php?table_id=<?php echo $tableId; ?>&confirmation_code=<?php echo urlencode($confirmationCode); ?>" class="btn btn-primary">
                    Yes, Pre-Order Food
                </a>
                <a href="confirmation.php?code=<?php echo urlencode($confirmationCode); ?>" class="btn btn-secondary">
                    No, Skip to Receipt
                </a>
            </div>
        </div>
    </div>
</body>
</html>
