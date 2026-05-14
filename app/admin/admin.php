<?php
require_once '../config.php';
session_start();
$adminPass = 'sakura2024';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    if ($_POST['password'] === $adminPass) { $_SESSION['admin'] = true; }
    else { $loginError = 'Invalid password.'; }
}
if (isset($_POST['admin_logout'])) { session_destroy(); header('Location: admin.php'); exit; }
$loggedIn = !empty($_SESSION['admin']);
if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    $pdo = getDBConnection();
    if ($_POST['action'] === 'update_reservation_status') {
        $id = (int)$_POST['reservation_id'];
        $st = in_array($_POST['status'], ['pending','confirmed','cancelled']) ? $_POST['status'] : 'pending';
        
        // Check for conflicts if confirming
        if ($st === 'confirmed') {
            $r = $pdo->prepare("SELECT table_id, reservation_date, reservation_time FROM reservations WHERE id=?");
            $r->execute([$id]);
            $reservation = $r->fetch();
            
            if ($reservation && $reservation['table_id']) {
                // Check if table is already confirmed for this time slot
                $conflict = $pdo->prepare("
                    SELECT id FROM reservations 
                    WHERE table_id = ? 
                    AND reservation_date = ? 
                    AND reservation_time = ? 
                    AND status = 'confirmed' 
                    AND id != ?
                ");
                $conflict->execute([
                    $reservation['table_id'],
                    $reservation['reservation_date'],
                    $reservation['reservation_time'],
                    $id
                ]);
                
                if ($conflict->fetch()) {
                    header('Location: admin.php?tab=reservations&error=conflict'); 
                    exit;
                }
            }
        }
        
        if ($st === 'cancelled') {
            $r = $pdo->prepare("SELECT table_id FROM reservations WHERE id=?"); 
            $r->execute([$id]); 
            $row = $r->fetch();
            if ($row && $row['table_id']) {
                $pdo->prepare("UPDATE tables SET status='available' WHERE id=?")->execute([$row['table_id']]);
            }
        }
        
        if ($st === 'confirmed') {
            $r = $pdo->prepare("SELECT table_id FROM reservations WHERE id=?"); 
            $r->execute([$id]); 
            $row = $r->fetch();
            if ($row && $row['table_id']) {
                $pdo->prepare("UPDATE tables SET status='reserved' WHERE id=?")->execute([$row['table_id']]);
            }
        }
        
        $pdo->prepare("UPDATE reservations SET status=? WHERE id=?")->execute([$st, $id]);
        header('Location: admin.php?tab=reservations&flash=1'); 
        exit;
    }
    if ($_POST['action'] === 'update_table_status') {
        $tid = (int)$_POST['table_id'];
        $ts  = in_array($_POST['status'], ['available','reserved','occupied']) ? $_POST['status'] : 'available';
        $pdo->prepare("UPDATE tables SET status=? WHERE id=?")->execute([$ts, $tid]);
        header('Location: admin.php?tab=tables&flash=1'); exit;
    }
    if ($_POST['action'] === 'update_table_details') {
        $tid = (int)$_POST['table_id'];
        $capacity = max(1, (int)$_POST['capacity']);
        $price = max(0, (float)$_POST['price']);
        $features = trim($_POST['features']);
        $location = trim($_POST['location'] ?? 'Main Hall');
        $table_type = in_array($_POST['table_type'] ?? '', ['standard','booth','counter','vip']) ? $_POST['table_type'] : 'standard';
        $is_smoking = !empty($_POST['is_smoking']) ? 1 : 0;
        $pdo->prepare("UPDATE tables SET capacity=?, price=?, features=?, location=?, table_type=?, is_smoking=? WHERE id=?")->execute([$capacity, $price, $features, $location, $table_type, $is_smoking, $tid]);
        header('Location: admin.php?tab=tables&flash=1'); exit;
    }
    if ($_POST['action'] === 'add_menu_item') {
        $name = trim($_POST['name']);
        $desc = trim($_POST['description']);
        $price = max(0, (float)$_POST['price']);
        $cat = in_array($_POST['category'], ['sushi','sashimi','rolls','appetizers','drinks']) ? $_POST['category'] : 'sushi';
        $img = trim($_POST['image']);
        $stock = max(0, (int)($_POST['stock'] ?? 100));
        $available = !empty($_POST['available']) ? 1 : 0;
        $pdo->prepare("INSERT INTO menu_items (name, description, price, category, image, stock, available) VALUES (?, ?, ?, ?, ?, ?, ?)")->execute([$name, $desc, $price, $cat, $img, $stock, $available]);
        header('Location: admin.php?tab=menu&flash=1'); exit;
    }
    if ($_POST['action'] === 'update_menu_item') {
        $id = (int)$_POST['menu_id'];
        $name = trim($_POST['name']);
        $desc = trim($_POST['description']);
        $price = max(0, (float)$_POST['price']);
        $cat = in_array($_POST['category'], ['sushi','sashimi','rolls','appetizers','drinks']) ? $_POST['category'] : 'sushi';
        $img = trim($_POST['image']);
        $stock = max(0, (int)($_POST['stock'] ?? 0));
        $available = !empty($_POST['available']) ? 1 : 0;
        $pdo->prepare("UPDATE menu_items SET name=?, description=?, price=?, category=?, image=?, stock=?, available=? WHERE id=?")->execute([$name, $desc, $price, $cat, $img, $stock, $available, $id]);
        header('Location: admin.php?tab=menu&flash=1'); exit;
    }
    if ($_POST['action'] === 'delete_menu_item') {
        $id = (int)$_POST['menu_id'];
        $pdo->prepare("DELETE FROM menu_items WHERE id=?")->execute([$id]);
        header('Location: admin.php?tab=menu&flash=1'); exit;
    }
}
if ($loggedIn) {
    $pdo        = getDBConnection();
    
    // Auto-cleanup: Reset tables to available if reservation time has passed
    $pdo->exec("
        UPDATE tables t
        LEFT JOIN reservations r ON t.id = r.table_id AND r.status != 'cancelled'
        SET t.status = 'available'
        WHERE t.status IN ('reserved', 'occupied')
        AND (
            r.id IS NULL 
            OR CONCAT(r.reservation_date, ' ', r.reservation_time) < NOW() - INTERVAL 4 HOUR
        )
    ");

    $totalRes   = (int)$pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
    $pending    = (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE status='pending'")->fetchColumn();
    $confirmed  = (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE status='confirmed'")->fetchColumn();
    $cancelled  = (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE status='cancelled'")->fetchColumn();
    $todayCount = (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE reservation_date=CURDATE() AND status!='cancelled'")->fetchColumn();
    $revenue    = (float)$pdo->query("SELECT COALESCE(SUM(t.price),0) FROM reservations r JOIN tables t ON r.table_id=t.id WHERE r.status='confirmed'")->fetchColumn();
    $preorders  = (int)$pdo->query("SELECT COUNT(DISTINCT reservation_id) FROM pre_orders")->fetchColumn();
    $available  = (int)$pdo->query("SELECT COUNT(*) FROM tables WHERE status='available'")->fetchColumn();
    $tablesAll  = $pdo->query("SELECT * FROM tables ORDER BY table_number")->fetchAll();
    $menuItems  = $pdo->query("SELECT * FROM menu_items ORDER BY category, name")->fetchAll();
    $search     = trim($_GET['search'] ?? '');
    $allRes     = $pdo->query("SELECT r.*, t.table_number, t.capacity, t.price as table_price, (SELECT COUNT(*) FROM pre_orders WHERE reservation_id=r.id) as po_count FROM reservations r LEFT JOIN tables t ON r.table_id=t.id ORDER BY FIELD(r.status,'pending','confirmed','cancelled'), r.created_at DESC")->fetchAll();
    if ($search !== '') {
        $allRes = array_values(array_filter($allRes, fn($r) =>
            stripos($r['name'],$search)!==false || stripos($r['phone'],$search)!==false || stripos($r['confirmation_code'],$search)!==false
        ));
    }
    $activeTab = $_GET['tab'] ?? 'dashboard';
    $flash     = !empty($_GET['flash']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Sakura Sushi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;800&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{
  --bg:#08040e;--surface:#100b18;--border:rgba(201,150,79,.16);--border-hv:rgba(201,150,79,.35);
  --cream:#fdf6ec;--muted:rgba(253,246,236,.45);--muted2:rgba(253,246,236,.2);
  --gold:#c9964f;--gold-dim:rgba(201,150,79,.15);
  --red:#c0392b;--green:#3d9970;--amber:#e67e22;--blue:#2980b9;
  --sidebar-w:280px;--topbar-h:60px;
}
html,body{height:100%;overflow:hidden;}
body{background:var(--bg);color:var(--cream);font-family:'Montserrat',sans-serif;display:flex;flex-direction:column;}
::-webkit-scrollbar{width:4px;height:4px;}
::-webkit-scrollbar-track{background:transparent;}
::-webkit-scrollbar-thumb{background:rgba(201,150,79,.2);border-radius:2px;}

/* LOGIN */
.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;background:radial-gradient(ellipse 60% 50% at 50% 40%,rgba(201,150,79,.07),transparent);}
.login-card{width:100%;max-width:380px;background:var(--surface);border:1px solid var(--border-hv);border-radius:20px;padding:48px 40px;text-align:center;}
.login-logo{font-family:'Playfair Display',serif;font-size:2rem;color:var(--gold);margin-bottom:4px;}
.login-sub{font-size:.7rem;letter-spacing:.25em;text-transform:uppercase;color:var(--muted);margin-bottom:36px;}
.login-label{display:block;text-align:left;font-size:.7rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:8px;}
.login-input{width:100%;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:10px;color:var(--cream);font-family:inherit;font-size:.95rem;padding:12px 16px;outline:none;transition:border-color .2s;margin-bottom:20px;}
.login-input:focus{border-color:var(--gold);}
.login-btn{width:100%;background:var(--gold);color:var(--bg);font-family:'Playfair Display',serif;font-size:1rem;font-weight:700;border:none;border-radius:10px;padding:14px;cursor:pointer;transition:opacity .2s;}
.login-btn:hover{opacity:.88;}
.login-err{background:rgba(192,57,43,.1);border:1px solid rgba(192,57,43,.3);border-radius:8px;color:var(--red);font-size:.8rem;padding:10px 14px;margin-bottom:20px;text-align:left;}
.login-hint{margin-top:20px;font-size:.72rem;color:var(--muted2);}
.login-hint span{color:var(--gold);}

/* LAYOUT */
.app-body{flex:1;display:flex;overflow:hidden;}

/* SIDEBAR */
.sidebar{width:var(--sidebar-w);min-width:var(--sidebar-w);background:rgba(16,11,24,.98);border-right:1px solid var(--border);display:flex;flex-direction:column;overflow-y:auto;z-index:100;}
.sidebar-brand{padding:20px 16px;border-bottom:1px solid var(--border);}
.brand-name{font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--gold);}
.brand-tag{font-size:.6rem;letter-spacing:.2em;text-transform:uppercase;color:var(--muted2);margin-top:2px;}
.nav-section{padding:16px 12px 6px;font-size:.6rem;letter-spacing:.25em;text-transform:uppercase;color:var(--muted2);}
.nav-item{display:flex;align-items:center;gap:.7rem;padding:.6rem .8rem;margin:2px 8px;border-radius:10px;color:var(--muted);font-size:.82rem;text-decoration:none;transition:all .2s;cursor:pointer;border:none;background:none;font-family:inherit;width:calc(100% - 16px);text-align:left;}
.nav-item:hover{background:rgba(201,150,79,.06);color:var(--cream);}
.nav-item.active{background:var(--gold-dim);color:var(--gold);border:1px solid rgba(201,150,79,.2);}
.nav-item svg{width:16px;height:16px;flex-shrink:0;}
.nav-badge{margin-left:auto;background:var(--red);color:#fff;font-size:.6rem;padding:.15rem .45rem;border-radius:50px;min-width:18px;text-align:center;}
.sidebar-footer{margin-top:auto;padding:12px;border-top:1px solid var(--border);}
.logout-btn{display:flex;align-items:center;gap:8px;width:100%;padding:10px 14px;background:rgba(192,57,43,.08);border:1px solid rgba(192,57,43,.2);border-radius:8px;color:var(--red);font-family:inherit;font-size:.8rem;cursor:pointer;transition:all .15s;}
.logout-btn:hover{background:rgba(192,57,43,.15);}
.logout-btn svg{width:14px;height:14px;}

/* TOPBAR */
.topbar{height:var(--topbar-h);min-height:var(--topbar-h);background:rgba(8,4,14,.95);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 1.5rem;gap:12px;flex-shrink:0;backdrop-filter:blur(16px);}
.topbar-title{font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--cream);}
.topbar-actions{margin-left:auto;display:flex;align-items:center;gap:.5rem;}
.topbar-btn{background:none;border:1px solid var(--border);color:var(--muted);padding:.4rem .7rem;border-radius:8px;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:.4rem;font-size:.75rem;font-family:inherit;text-decoration:none;}
.topbar-btn:hover{border-color:var(--border-hv);color:var(--cream);}
.topbar-date{font-size:.78rem;color:var(--muted);border-left:1px solid var(--border);padding-left:1rem;}

/* TOAST */
.toast-container{position:fixed;top:80px;right:20px;z-index:10000;display:flex;flex-direction:column;gap:12px;}
.toast{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:16px 20px;min-width:300px;max-width:400px;display:flex;align-items:center;gap:12px;box-shadow:0 8px 32px rgba(0,0,0,.4);opacity:0;transform:translateX(400px);transition:all .4s ease-out;}
.toast.show{opacity:1;transform:translateX(0);}
.toast-icon{flex-shrink:0;width:24px;height:24px;display:flex;align-items:center;justify-content:center;}
.toast-message{flex:1;color:var(--cream);font-size:.9rem;line-height:1.4;}
.toast-close{background:none;border:none;color:var(--muted);font-size:1.5rem;cursor:pointer;padding:0;width:24px;height:24px;}
.toast-close:hover{color:var(--cream);}
.toast-success{border-left:4px solid var(--green);}
.toast-success .toast-icon{color:var(--green);}
.toast-error{border-left:4px solid var(--red);}
.toast-error .toast-icon{color:var(--red);}
.toast-warning{border-left:4px solid var(--amber);}
.toast-warning .toast-icon{color:var(--amber);}
.toast-info{border-left:4px solid var(--gold);}
.toast-info .toast-icon{color:var(--gold);}

/* MAIN */
.main{flex:1;overflow-y:auto;display:flex;flex-direction:column;}
.page-body{padding:1.5rem;flex:1;}

/* STATS */
.stats-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:1rem;margin-bottom:1.5rem;}
.stat-card{background:rgba(16,11,24,.8);border:1px solid var(--border);border-radius:14px;padding:1.1rem 1.2rem;position:relative;overflow:hidden;transition:border-color .2s;}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:var(--stripe,var(--gold));opacity:.6;}
.stat-card:hover{border-color:var(--border-hv);}
.stat-label{font-size:.65rem;letter-spacing:.18em;text-transform:uppercase;color:var(--muted);margin-bottom:.5rem;}
.stat-val{font-family:'Playfair Display',serif;font-size:2rem;line-height:1;color:var(--cream);}
.stat-sub{font-size:.72rem;color:var(--muted2);margin-top:.25rem;}

/* SECTION */
.section-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;}
.section-title{font-family:'Playfair Display',serif;font-size:1rem;}

/* TABLE */
.data-wrap{background:rgba(16,11,24,.8);border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:1.5rem;}
.data-toolbar{padding:.75rem 1.1rem;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;}
.search-wrap{position:relative;flex:1;min-width:160px;max-width:260px;}
.search-wrap input{width:100%;padding:.45rem .7rem .45rem 2rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;color:var(--cream);font-family:inherit;font-size:.8rem;outline:none;transition:border-color .2s;}
.search-wrap input:focus{border-color:var(--border-hv);}
.search-wrap svg{position:absolute;left:.55rem;top:50%;transform:translateY(-50%);color:var(--muted);pointer-events:none;width:13px;height:13px;}
.filter-chips{display:flex;gap:.3rem;flex-wrap:wrap;}
.chip{padding:.28rem .75rem;border:1px solid var(--border);border-radius:50px;font-size:.72rem;color:var(--muted);cursor:pointer;background:none;font-family:inherit;transition:all .2s;}
.chip:hover{border-color:var(--border-hv);color:var(--cream);}
.chip.active{border-color:var(--gold);color:var(--gold);background:var(--gold-dim);}
.chip.c-pending.active{border-color:var(--amber);color:var(--amber);background:rgba(230,126,34,.08);}
.chip.c-confirmed.active{border-color:var(--green);color:var(--green);background:rgba(61,153,112,.08);}
.chip.c-cancelled.active{border-color:var(--red);color:var(--red);background:rgba(192,57,43,.08);}
.data-table{width:100%;border-collapse:collapse;}
.data-table th{padding:.6rem 1rem;text-align:left;font-size:.65rem;letter-spacing:.18em;text-transform:uppercase;color:var(--muted);background:rgba(201,150,79,.04);border-bottom:1px solid var(--border);white-space:nowrap;}
.data-table td{padding:.75rem 1rem;font-size:.82rem;border-bottom:1px solid rgba(201,150,79,.05);vertical-align:middle;}
.data-table tr:last-child td{border-bottom:none;}
.data-table tr:hover td{background:rgba(255,255,255,.015);}
.code-cell{font-family:'Playfair Display',serif;font-size:.9rem;color:var(--gold);letter-spacing:.06em;}
.name-cell{font-weight:600;}
.phone-cell{font-size:.73rem;color:var(--muted);margin-top:.1rem;}
.date-cell{font-size:.78rem;}
.date-sub{font-size:.72rem;color:var(--muted);}

/* STATUS PILLS */
.pill{display:inline-flex;align-items:center;gap:.3rem;padding:.18rem .6rem;border-radius:50px;font-size:.68rem;border:1px solid;white-space:nowrap;}
.pill::before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0;}
.pill-pending{color:var(--amber);border-color:rgba(230,126,34,.3);background:rgba(230,126,34,.07);}
.pill-confirmed{color:var(--green);border-color:rgba(61,153,112,.3);background:rgba(61,153,112,.07);}
.pill-cancelled{color:var(--red);border-color:rgba(192,57,43,.3);background:rgba(192,57,43,.07);}
.pill-available{color:var(--green);border-color:rgba(61,153,112,.3);background:rgba(61,153,112,.07);}
.pill-occupied{color:var(--red);border-color:rgba(192,57,43,.3);background:rgba(192,57,43,.07);}
.pill-reserved{color:var(--amber);border-color:rgba(230,126,34,.3);background:rgba(230,126,34,.07);}

/* ACTIONS */
.act-row{display:flex;align-items:center;gap:.3rem;}
.act-select{background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:6px;color:var(--cream);font-family:inherit;font-size:.75rem;padding:.3rem .5rem;cursor:pointer;outline:none;}
.act-select:focus{border-color:var(--gold);}
.act-btn{background:none;border:1px solid var(--border);color:var(--muted);padding:.28rem .6rem;border-radius:6px;cursor:pointer;font-size:.72rem;font-family:inherit;display:inline-flex;align-items:center;justify-content:center;gap:.3rem;transition:all .15s;white-space:nowrap;}
.act-btn:hover{border-color:var(--border-hv);color:var(--cream);}
.act-btn.save{border-color:rgba(201,150,79,.3);color:var(--gold);}
.act-btn.save:hover{background:var(--gold-dim);}
.act-btn.btn-confirm{border-color:rgba(61,153,112,.3);color:var(--green);background:rgba(61,153,112,.05);}
.act-btn.btn-confirm:hover{background:rgba(61,153,112,.12);}
.act-btn.btn-confirm:disabled{opacity:.4;cursor:not-allowed;border-color:var(--border);}
.act-btn.btn-pending{border-color:rgba(230,126,34,.3);color:var(--amber);background:rgba(230,126,34,.05);}
.act-btn.btn-pending:hover{background:rgba(230,126,34,.12);}
.act-btn.btn-cancel{border-color:rgba(192,57,43,.3);color:var(--red);background:rgba(192,57,43,.05);}
.act-btn.btn-cancel:hover{background:rgba(192,57,43,.12);}
.act-btn svg{flex-shrink:0;}
.preorder-tag{font-size:.68rem;color:var(--gold);background:var(--gold-dim);border:1px solid rgba(201,150,79,.2);padding:.12rem .45rem;border-radius:4px;}

/* TABLES GRID */
.tables-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;}
.tov-card{background:rgba(16,11,24,.8);border:1px solid var(--border);border-radius:12px;padding:1.1rem;transition:border-color .2s,transform .2s;}
.tov-card:hover{border-color:var(--border-hv);transform:translateY(-2px);}
.tov-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:.7rem;}
.tov-num{font-family:'Playfair Display',serif;font-size:1.3rem;color:var(--gold);}
.tov-cap{font-size:.75rem;color:var(--muted);margin-top:.2rem;}
.tov-feat{font-size:.72rem;color:var(--muted2);margin:.4rem 0 .7rem;}
.tov-price{font-size:.8rem;color:var(--gold);margin-bottom:.7rem;}
.tov-select{width:100%;padding:.4rem .6rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:6px;color:var(--cream);font-family:inherit;font-size:.78rem;cursor:pointer;outline:none;}
.tov-select:focus{border-color:var(--gold);}

/* DASHBOARD TWO-COL */
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem;}
.glass-block{background:rgba(16,11,24,.8);border:1px solid var(--border);border-radius:14px;padding:1.2rem;}
.bar-row{display:flex;align-items:center;gap:.7rem;margin-bottom:.7rem;}
.bar-label{font-size:.75rem;color:var(--muted);width:80px;flex-shrink:0;}
.bar-track{flex:1;background:rgba(255,255,255,.05);border-radius:4px;height:7px;overflow:hidden;}
.bar-fill{height:100%;border-radius:4px;}
.bar-val{font-size:.72rem;color:var(--muted);width:28px;text-align:right;}

/* EMPTY */
.empty-state{text-align:center;padding:3rem;color:var(--muted2);font-size:.85rem;}

/* RECEIPT LINK */
.receipt-link{color:var(--gold);font-size:.75rem;text-decoration:none;border:1px solid rgba(201,150,79,.2);padding:.15rem .5rem;border-radius:4px;}
.receipt-link:hover{background:var(--gold-dim);}

/* MOBILE HAMBURGER */
.mobile-toggle{display:none;background:none;border:1px solid var(--border);color:var(--cream);padding:.5rem;border-radius:8px;cursor:pointer;margin-right:.5rem;}
.mobile-toggle svg{width:20px;height:20px;}
.sidebar-overlay{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.7);z-index:998;opacity:0;transition:opacity .3s;}
.sidebar-overlay.active{display:block;opacity:1;}

@media(max-width:768px){
  .sidebar{
    position:fixed;
    top:0;
    left:-280px;
    height:100vh;
    z-index:999;
    transition:left .3s ease;
    box-shadow:2px 0 10px rgba(0,0,0,.5);
  }
  .sidebar.active{left:0;}
  .mobile-toggle{display:flex;}
  .stats-grid{grid-template-columns:repeat(3,1fr);}
  .two-col{grid-template-columns:1fr;}
  .topbar-date{display:none;}
  .topbar-title{font-size:.95rem;}
  .tables-grid{grid-template-columns:1fr;}
  .data-table{font-size:.75rem;}
  .data-table th,.data-table td{padding:.5rem .6rem;}
}

@media(max-width:480px){
  .stats-grid{grid-template-columns:repeat(2,1fr);}
}
</style>
</head>
<body>

<?php if (!$loggedIn): ?>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">桜 Sakura</div>
    <div class="login-sub">Admin Panel</div>
    <?php if (!empty($loginError)): ?><div class="login-err"><?= htmlspecialchars($loginError) ?></div><?php endif; ?>
    <form method="POST">
      <label class="login-label">Password</label>
      <input type="password" name="password" class="login-input" placeholder="Enter admin password" autofocus required>
      <button type="submit" name="admin_login" class="login-btn">Sign In</button>
    </form>
    <div class="login-hint">Default password: <span>sakura2024</span></div>
  </div>
</div>

<?php else: ?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="topbar">
  <button class="mobile-toggle" id="mobileToggle">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <line x1="3" y1="12" x2="21" y2="12"/>
      <line x1="3" y1="6" x2="21" y2="6"/>
      <line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
  </button>
  <div class="topbar-title">桜 Sakura Admin</div>
  <div class="topbar-actions">
    <span class="topbar-date"><?= date('D, M j Y') ?></span>
    <a href="../index.php" target="_blank" class="topbar-btn">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
      View Site
    </a>
    <button class="topbar-btn" onclick="location.reload()">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
    </button>
  </div>
</div>

<div class="app-body">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="brand-name">桜 Sakura</div>
      <div class="brand-tag">Reservation Admin</div>
    </div>
    <div class="nav-section">Menu</div>
    <a href="admin.php?tab=dashboard" class="nav-item <?= $activeTab==='dashboard'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Dashboard
    </a>
    <a href="admin.php?tab=reservations" class="nav-item <?= $activeTab==='reservations'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      Reservations
      <?php if ($pending > 0): ?><span class="nav-badge"><?= $pending ?></span><?php endif; ?>
    </a>
    <a href="admin.php?tab=tables" class="nav-item <?= $activeTab==='tables'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="3" rx="1"/><path d="M6 9v9M18 9v9M4 18h16"/></svg>
      Tables
    </a>
    <a href="admin.php?tab=menu" class="nav-item <?= $activeTab==='menu'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2M7 2v20M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/></svg>
      Menu Items
    </a>
    <div class="nav-section">Quick Stats</div>
    <div style="padding:.3rem .8rem 0;font-size:.78rem;display:flex;flex-direction:column;gap:.45rem;">
      <div style="display:flex;justify-content:space-between;color:var(--muted);"><span>Today</span><strong style="color:var(--cream);"><?= $todayCount ?></strong></div>
      <div style="display:flex;justify-content:space-between;color:var(--muted);"><span>Available</span><strong style="color:var(--green);"><?= $available ?></strong></div>
      <div style="display:flex;justify-content:space-between;color:var(--muted);"><span>Pending</span><strong style="color:var(--amber);"><?= $pending ?></strong></div>
    </div>
    <div class="sidebar-footer">
      <a href="../index.php" class="nav-item" style="font-size:.78rem;margin-bottom:6px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Back to Site
      </a>
      <form method="POST">
        <button type="submit" name="admin_logout" class="logout-btn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          Sign Out
        </button>
      </form>
    </div>
  </aside>

  <main class="main">
    <div class="page-body">

<?php if ($activeTab === 'dashboard'): ?>
<div class="stats-grid">
  <div class="stat-card" style="--stripe:var(--gold);">
    <div class="stat-label">Total Reservations</div>
    <div class="stat-val"><?= $totalRes ?></div>
    <div class="stat-sub"><?= $confirmed ?> confirmed</div>
  </div>
  <div class="stat-card" style="--stripe:var(--amber);">
    <div class="stat-label">Pending Review</div>
    <div class="stat-val" style="color:var(--amber);"><?= $pending ?></div>
    <div class="stat-sub">awaiting action</div>
  </div>
  <div class="stat-card" style="--stripe:var(--green);">
    <div class="stat-label">Today's Bookings</div>
    <div class="stat-val" style="color:var(--green);"><?= $todayCount ?></div>
    <div class="stat-sub"><?= date('M j, Y') ?></div>
  </div>
  <div class="stat-card" style="--stripe:var(--blue);">
    <div class="stat-label">With Pre-orders</div>
    <div class="stat-val" style="color:var(--blue);"><?= $preorders ?></div>
    <div class="stat-sub">reservations</div>
  </div>
  <div class="stat-card" style="--stripe:var(--gold);">
    <div class="stat-label">Confirmed Revenue</div>
    <div class="stat-val" style="font-size:1.4rem;">&#8369;<?= number_format($revenue, 2) ?></div>
    <div class="stat-sub">from confirmed bookings</div>
  </div>
  <div class="stat-card" style="--stripe:var(--green);">
    <div class="stat-label">Tables Available</div>
    <div class="stat-val" style="color:var(--green);"><?= $available ?></div>
    <div class="stat-sub">of <?= count($tablesAll) ?> total</div>
  </div>
</div>

<div class="two-col">
  <div class="glass-block">
    <div class="section-head"><span class="section-title">Reservation Status</span></div>
    <?php $max = max($totalRes, 1); ?>
    <div class="bar-row">
      <span class="bar-label">Pending</span>
      <div class="bar-track"><div class="bar-fill" style="width:<?= round($pending/$max*100) ?>%;background:var(--amber);"></div></div>
      <span class="bar-val"><?= $pending ?></span>
    </div>
    <div class="bar-row">
      <span class="bar-label">Confirmed</span>
      <div class="bar-track"><div class="bar-fill" style="width:<?= round($confirmed/$max*100) ?>%;background:var(--green);"></div></div>
      <span class="bar-val"><?= $confirmed ?></span>
    </div>
    <div class="bar-row">
      <span class="bar-label">Cancelled</span>
      <div class="bar-track"><div class="bar-fill" style="width:<?= round($cancelled/$max*100) ?>%;background:var(--red);"></div></div>
      <span class="bar-val"><?= $cancelled ?></span>
    </div>
  </div>
  <div class="glass-block">
    <div class="section-head"><span class="section-title">Table Status</span></div>
    <?php $tmax = max(count($tablesAll), 1); $occ = count($tablesAll) - $available - (int)$pdo->query("SELECT COUNT(*) FROM tables WHERE status='reserved'")->fetchColumn(); $res = (int)$pdo->query("SELECT COUNT(*) FROM tables WHERE status='reserved'")->fetchColumn(); ?>
    <div class="bar-row">
      <span class="bar-label">Available</span>
      <div class="bar-track"><div class="bar-fill" style="width:<?= round($available/$tmax*100) ?>%;background:var(--green);"></div></div>
      <span class="bar-val"><?= $available ?></span>
    </div>
    <div class="bar-row">
      <span class="bar-label">Reserved</span>
      <div class="bar-track"><div class="bar-fill" style="width:<?= round($res/$tmax*100) ?>%;background:var(--amber);"></div></div>
      <span class="bar-val"><?= $res ?></span>
    </div>
    <div class="bar-row">
      <span class="bar-label">Occupied</span>
      <div class="bar-track"><div class="bar-fill" style="width:<?= round(max($occ,0)/$tmax*100) ?>%;background:var(--red);"></div></div>
      <span class="bar-val"><?= max($occ,0) ?></span>
    </div>
  </div>
</div>

<div class="section-head"><span class="section-title">Recent Reservations</span><a href="admin.php?tab=reservations" class="act-btn">View All</a></div>
<div class="data-wrap">
  <div style="overflow-x:auto;">
  <table class="data-table">
    <thead><tr><th>Code</th><th>Customer</th><th>Table</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php $recent = array_slice($allRes, 0, 8); foreach ($recent as $r): ?>
    <tr>
      <td><span class="code-cell"><?= htmlspecialchars($r['confirmation_code']) ?></span></td>
      <td><div class="name-cell"><?= htmlspecialchars($r['name']) ?></div><div class="phone-cell"><?= htmlspecialchars($r['phone']) ?></div></td>
      <td><?= htmlspecialchars($r['table_number'] ?? '—') ?> <span style="color:var(--muted);font-size:.72rem;">(<?= $r['capacity'] ?? '?' ?> seats)</span></td>
      <td><div class="date-cell"><?= date('M j, Y', strtotime($r['reservation_date'])) ?></div><div class="date-sub"><?= date('g:i A', strtotime($r['reservation_time'])) ?></div></td>
      <td><span class="pill pill-<?= $r['status'] ?>"><?= $r['status'] ?></span></td>
      <td>
        <div class="act-row">
          <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="update_reservation_status">
            <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
            <input type="hidden" name="status" value="confirmed">
            <button type="submit" class="act-btn btn-confirm" <?= $r['status']==='confirmed'?'disabled':'' ?>>Confirm</button>
          </form>
          <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="update_reservation_status">
            <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
            <input type="hidden" name="status" value="pending">
            <button type="submit" class="act-btn btn-pending">Pending</button>
          </form>
          <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="update_reservation_status">
            <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
            <input type="hidden" name="status" value="cancelled">
            <button type="submit" class="act-btn btn-cancel">Cancel</button>
          </form>
        </div>
      </td>
    </tr>
    <?php endforeach; if (empty($recent)): ?><tr><td colspan="6" class="empty-state">No reservations yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>

<?php if ($activeTab === 'reservations'): ?>
<div class="section-head"><span class="section-title">All Reservations</span></div>
<div class="data-wrap">
  <div class="data-toolbar">
    <div class="search-wrap">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" id="searchInput" placeholder="Search name, code, phone..." oninput="filterTable()">
    </div>
    <div class="filter-chips">
      <button class="chip active" onclick="setFilter('all',this)">All</button>
      <button class="chip c-pending" onclick="setFilter('pending',this)">Pending</button>
      <button class="chip c-confirmed" onclick="setFilter('confirmed',this)">Confirmed</button>
      <button class="chip c-cancelled" onclick="setFilter('cancelled',this)">Cancelled</button>
    </div>
  </div>
  <div style="overflow-x:auto;">
  <table class="data-table" id="resTable">
    <thead><tr><th>Code</th><th>Customer</th><th>Table</th><th>Date &amp; Time</th><th>Guests</th><th>Fee</th><th>Pre-order</th><th>Receipt</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody id="resBody">
    <?php foreach ($allRes as $r): ?>
    <tr data-status="<?= $r['status'] ?>" data-search="<?= strtolower(htmlspecialchars($r['name'].$r['phone'].$r['confirmation_code'])) ?>">
      <td><span class="code-cell"><?= htmlspecialchars($r['confirmation_code']) ?></span></td>
      <td><div class="name-cell"><?= htmlspecialchars($r['name']) ?></div><div class="phone-cell"><?= htmlspecialchars($r['phone']) ?></div></td>
      <td><?= htmlspecialchars($r['table_number'] ?? '—') ?></td>
      <td><div class="date-cell"><?= date('M j, Y', strtotime($r['reservation_date'])) ?></div><div class="date-sub"><?= date('g:i A', strtotime($r['reservation_time'])) ?></div></td>
      <td style="text-align:center;"><?= $r['people_count'] ?></td>
      <td>&#8369;<?= number_format($r['table_price'] ?? 0, 2) ?></td>
      <td><?= $r['po_count'] > 0 ? '<span class="preorder-tag">'.$r['po_count'].' items</span>' : '<span style="color:var(--muted2);">—</span>' ?></td>
      <td><?php if ($r['payment_receipt']): ?>
        <button class="act-btn" onclick="viewReceipt('../<?= htmlspecialchars($r['payment_receipt']) ?>')" title="View Receipt">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
        </button>
      <?php else: ?><span style="color:var(--muted2);">—</span><?php endif; ?></td>
      <td><span class="pill pill-<?= $r['status'] ?>"><?= $r['status'] ?></span></td>
      <td>
        <div class="act-row">
          <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="update_reservation_status">
            <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
            <input type="hidden" name="status" value="confirmed">
            <button type="submit" class="act-btn btn-confirm" <?= $r['status']==='confirmed'?'disabled':'' ?>>Confirm</button>
          </form>
          <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="update_reservation_status">
            <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
            <input type="hidden" name="status" value="pending">
            <button type="submit" class="act-btn btn-pending">Pending</button>
          </form>
          <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="update_reservation_status">
            <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
            <input type="hidden" name="status" value="cancelled">
            <button type="submit" class="act-btn btn-cancel">Cancel</button>
          </form>
        </div>
      </td>
    </tr>
    <?php endforeach; if (empty($allRes)): ?><tr><td colspan="10" class="empty-state">No reservations found.</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>

<?php if ($activeTab === 'tables'): ?>
<div class="section-head"><span class="section-title">Tables Management</span></div>
<div class="tables-grid">
  <?php foreach ($tablesAll as $t): ?>
  <div class="tov-card">
    <div class="tov-top">
      <div class="tov-num"><?= htmlspecialchars($t['table_number']) ?></div>
      <span class="pill pill-<?= $t['status'] ?>"><?= $t['status'] ?></span>
    </div>
    <form method="POST" style="width:100%;">
      <input type="hidden" name="action" value="update_table_details">
      <input type="hidden" name="table_id" value="<?= $t['id'] ?>">
      
      <div style="margin:.5rem 0;">
        <label style="font-size:.68rem;color:var(--muted);display:block;margin-bottom:.2rem;">Capacity</label>
        <input type="number" name="capacity" value="<?= $t['capacity'] ?>" min="1" max="20" class="act-select" style="width:100%;padding:.4rem;">
      </div>
      
      <div style="margin:.5rem 0;">
        <label style="font-size:.68rem;color:var(--muted);display:block;margin-bottom:.2rem;">Price (₱)</label>
        <input type="number" name="price" value="<?= $t['price'] ?>" min="0" step="0.01" class="act-select" style="width:100%;padding:.4rem;">
      </div>
      
      <div style="margin:.5rem 0;">
        <label style="font-size:.68rem;color:var(--muted);display:block;margin-bottom:.2rem;">Location</label>
        <input type="text" name="location" value="<?= htmlspecialchars($t['location'] ?? 'Main Hall') ?>" class="act-select" style="width:100%;padding:.4rem;" placeholder="e.g., Window Side">
      </div>
      
      <div style="margin:.5rem 0;">
        <label style="font-size:.68rem;color:var(--muted);display:block;margin-bottom:.2rem;">Table Type</label>
        <select name="table_type" class="act-select" style="width:100%;padding:.4rem;">
          <option value="standard" <?= ($t['table_type'] ?? 'standard')==='standard'?'selected':'' ?>>Standard</option>
          <option value="booth" <?= ($t['table_type'] ?? '')==='booth'?'selected':'' ?>>Booth</option>
          <option value="counter" <?= ($t['table_type'] ?? '')==='counter'?'selected':'' ?>>Counter</option>
          <option value="vip" <?= ($t['table_type'] ?? '')==='vip'?'selected':'' ?>>VIP</option>
        </select>
      </div>
      
      <div style="margin:.5rem 0;">
        <label style="font-size:.68rem;color:var(--muted);display:block;margin-bottom:.2rem;">Features</label>
        <input type="text" name="features" value="<?= htmlspecialchars($t['features'] ?? '') ?>" class="act-select" style="width:100%;padding:.4rem;" placeholder="e.g., Window view">
      </div>
      
      <div style="margin:.5rem 0;display:flex;align-items:center;gap:.5rem;">
        <input type="checkbox" name="is_smoking" value="1" <?= !empty($t['is_smoking'])?'checked':'' ?> style="width:16px;height:16px;">
        <label style="font-size:.72rem;color:var(--muted);">Smoking Area</label>
      </div>
      
      <button type="submit" class="act-btn save" style="width:100%;justify-content:center;margin-top:.5rem;">Save Changes</button>
    </form>
    
    <form method="POST" style="margin-top:.5rem;">
      <input type="hidden" name="action" value="update_table_status">
      <input type="hidden" name="table_id" value="<?= $t['id'] ?>">
      <select class="tov-select" name="status" onchange="this.form.submit()">
        <option value="available" <?= $t['status']==='available'?'selected':'' ?>>Available</option>
        <option value="reserved"  <?= $t['status']==='reserved' ?'selected':'' ?>>Reserved</option>
        <option value="occupied"  <?= $t['status']==='occupied' ?'selected':'' ?>>Occupied</option>
      </select>
    </form>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($activeTab === 'menu'): ?>
<div class="section-head">
  <span class="section-title">Menu Items Management</span>
  <button class="act-btn" onclick="document.getElementById('addMenuForm').style.display='block'">+ Add New Item</button>
</div>

<div id="addMenuForm" class="glass-block" style="display:none;margin-bottom:1.5rem;">
  <h3 style="font-size:1rem;margin-bottom:1rem;color:var(--gold);">Add New Menu Item</h3>
  <form method="POST" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
    <input type="hidden" name="action" value="add_menu_item">
    <div>
      <label style="font-size:.7rem;color:var(--muted);display:block;margin-bottom:.3rem;">Name *</label>
      <input type="text" name="name" required class="act-select" style="width:100%;padding:.5rem;">
    </div>
    <div>
      <label style="font-size:.7rem;color:var(--muted);display:block;margin-bottom:.3rem;">Price (₱) *</label>
      <input type="number" name="price" required min="0" step="0.01" class="act-select" style="width:100%;padding:.5rem;">
    </div>
    <div>
      <label style="font-size:.7rem;color:var(--muted);display:block;margin-bottom:.3rem;">Category *</label>
      <select name="category" required class="act-select" style="width:100%;padding:.5rem;">
        <option value="sushi">Sushi</option>
        <option value="sashimi">Sashimi</option>
        <option value="rolls">Rolls</option>
        <option value="appetizers">Appetizers</option>
        <option value="drinks">Drinks</option>
      </select>
    </div>
    <div>
      <label style="font-size:.7rem;color:var(--muted);display:block;margin-bottom:.3rem;">Stock Quantity *</label>
      <input type="number" name="stock" required min="0" value="100" class="act-select" style="width:100%;padding:.5rem;">
    </div>
    <div>
      <label style="font-size:.7rem;color:var(--muted);display:block;margin-bottom:.3rem;">Image Filename</label>
      <input type="text" name="image" class="act-select" style="width:100%;padding:.5rem;" placeholder="e.g., salmon-nigiri.jpg">
    </div>
    <div style="display:flex;align-items:center;gap:.5rem;">
      <input type="checkbox" name="available" id="add_available" value="1" checked style="width:18px;height:18px;">
      <label for="add_available" style="font-size:.75rem;color:var(--muted);cursor:pointer;">Available for pre-order</label>
    </div>
    <div style="grid-column:1/-1;">
      <label style="font-size:.7rem;color:var(--muted);display:block;margin-bottom:.3rem;">Description</label>
      <textarea name="description" class="act-select" style="width:100%;padding:.5rem;min-height:60px;"></textarea>
    </div>
    <div style="grid-column:1/-1;display:flex;gap:.5rem;">
      <button type="submit" class="act-btn save">Add Item</button>
      <button type="button" class="act-btn" onclick="document.getElementById('addMenuForm').style.display='none'">Cancel</button>
    </div>
  </form>
</div>

<div class="data-wrap">
  <div style="overflow-x:auto;">
  <table class="data-table">
    <thead><tr><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Available</th><th>Description</th><th>Actions</th></tr></thead>
    <tbody>
    <?php 
    $categories = ['sushi'=>'Sushi','sashimi'=>'Sashimi','rolls'=>'Rolls','appetizers'=>'Appetizers','drinks'=>'Drinks'];
    foreach ($menuItems as $m): 
    $stock = isset($m['stock']) ? $m['stock'] : 100;
    $stockClass = $stock <= 10 ? 'pill-cancelled' : ($stock <= 30 ? 'pill-pending' : 'pill-confirmed');
    ?>
    <tr>
      <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
      <td><span class="pill pill-confirmed"><?= $categories[$m['category']] ?? $m['category'] ?></span></td>
      <td style="color:var(--gold);font-weight:600;">&#8369;<?= number_format($m['price'], 2) ?></td>
      <td><span class="pill <?= $stockClass ?>"><?= $stock ?> pcs</span></td>
      <td><?= !empty($m['available']) ? '<span class="pill pill-confirmed">Yes</span>' : '<span class="pill pill-cancelled">No</span>' ?></td>
      <td style="font-size:.75rem;max-width:200px;"><?= htmlspecialchars(substr($m['description'] ?? '', 0, 50)) ?><?= strlen($m['description'] ?? '') > 50 ? '...' : '' ?></td>
      <td>
        <button class="act-btn" onclick="editMenu(<?= $m['id'] ?>,'<?= htmlspecialchars(addslashes($m['name'])) ?>','<?= htmlspecialchars(addslashes($m['description'] ?? '')) ?>',<?= $m['price'] ?>,'<?= $m['category'] ?>','<?= htmlspecialchars(addslashes($m['image'] ?? '')) ?>',<?= $m['stock'] ?? 0 ?>,<?= $m['available'] ?? 1 ?>)" title="Edit">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
          </svg>
        </button>
        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this item?')">
          <input type="hidden" name="action" value="delete_menu_item">
          <input type="hidden" name="menu_id" value="<?= $m['id'] ?>">
          <button type="submit" class="act-btn" style="border-color:var(--red);color:var(--red);" title="Delete">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="3 6 5 6 21 6"/>
              <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
              <line x1="10" y1="11" x2="10" y2="17"/>
              <line x1="14" y1="11" x2="14" y2="17"/>
            </svg>
          </button>
        </form>
      </td>
    </tr>
    <?php endforeach; if (empty($menuItems)): ?><tr><td colspan="7" class="empty-state">No menu items yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

<div id="editMenuModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.7);z-index:9999;align-items:center;justify-content:center;">
  <div class="glass-block" style="max-width:600px;width:90%;max-height:90vh;overflow-y:auto;">
    <h3 style="font-size:1rem;margin-bottom:1rem;color:var(--gold);">Edit Menu Item</h3>
    <form method="POST" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
      <input type="hidden" name="action" value="update_menu_item">
      <input type="hidden" name="menu_id" id="edit_menu_id">
      <div>
        <label style="font-size:.7rem;color:var(--muted);display:block;margin-bottom:.3rem;">Name *</label>
        <input type="text" name="name" id="edit_name" required class="act-select" style="width:100%;padding:.5rem;">
      </div>
      <div>
        <label style="font-size:.7rem;color:var(--muted);display:block;margin-bottom:.3rem;">Price (₱) *</label>
        <input type="number" name="price" id="edit_price" required min="0" step="0.01" class="act-select" style="width:100%;padding:.5rem;">
      </div>
      <div>
        <label style="font-size:.7rem;color:var(--muted);display:block;margin-bottom:.3rem;">Category *</label>
        <select name="category" id="edit_category" required class="act-select" style="width:100%;padding:.5rem;">
          <option value="sushi">Sushi</option>
          <option value="sashimi">Sashimi</option>
          <option value="rolls">Rolls</option>
          <option value="appetizers">Appetizers</option>
          <option value="drinks">Drinks</option>
        </select>
      </div>
      <div>
        <label style="font-size:.7rem;color:var(--muted);display:block;margin-bottom:.3rem;">Stock Quantity *</label>
        <input type="number" name="stock" id="edit_stock" required min="0" class="act-select" style="width:100%;padding:.5rem;">
      </div>
      <div>
        <label style="font-size:.7rem;color:var(--muted);display:block;margin-bottom:.3rem;">Image Filename</label>
        <input type="text" name="image" id="edit_image" class="act-select" style="width:100%;padding:.5rem;">
      </div>
      <div style="display:flex;align-items:center;gap:.5rem;">
        <input type="checkbox" name="available" id="edit_available" value="1" style="width:18px;height:18px;">
        <label for="edit_available" style="font-size:.75rem;color:var(--muted);cursor:pointer;">Available for pre-order</label>
      </div>
      <div style="grid-column:1/-1;">
        <label style="font-size:.7rem;color:var(--muted);display:block;margin-bottom:.3rem;">Description</label>
        <textarea name="description" id="edit_description" class="act-select" style="width:100%;padding:.5rem;min-height:60px;"></textarea>
      </div>
      <div style="grid-column:1/-1;display:flex;gap:.5rem;">
        <button type="submit" class="act-btn save">Update Item</button>
        <button type="button" class="act-btn" onclick="document.getElementById('editMenuModal').style.display='none'">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Receipt Viewer Modal -->
<div id="receiptModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.9);z-index:9999;align-items:center;justify-content:center;" onclick="closeReceiptModal()">
  <div style="max-width:90%;max-height:90vh;position:relative;" onclick="event.stopPropagation()">
    <button onclick="closeReceiptModal()" style="position:absolute;top:-40px;right:0;background:rgba(255,255,255,.1);border:1px solid var(--border);color:var(--cream);padding:.5rem 1rem;border-radius:8px;cursor:pointer;font-size:.9rem;">
      Close
    </button>
    <img id="receiptImage" src="" alt="Payment Receipt" style="max-width:100%;max-height:90vh;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,.5);">
  </div>
</div>
<?php endif; ?>

    </div><!-- /page-body -->
  </main>
</div><!-- /app-body -->
<?php endif; ?>

<script>
let currentFilter = 'all';

// Toast notification
function showToast(message, type = 'success') {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
  
  const icons = {
    success: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>',
    error: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
    info: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
  };
  
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `
    <div class="toast-icon">${icons[type] || icons.info}</div>
    <div class="toast-message">${message}</div>
    <button class="toast-close" onclick="this.parentElement.remove()">×</button>
  `;
  
  container.appendChild(toast);
  setTimeout(() => toast.classList.add('show'), 10);
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 300);
  }, 4000);
}

// Show toast on page load if flash parameter exists
<?php if ($flash): ?>
showToast('Changes saved successfully!', 'success');
<?php endif; ?>

// Show error toast for conflicts
<?php if (!empty($_GET['error']) && $_GET['error'] === 'conflict'): ?>
showToast('Cannot confirm: Table is already confirmed for this time slot!', 'error');
<?php endif; ?>

function setFilter(status, btn) {
  currentFilter = status;
  document.querySelectorAll('.filter-chips .chip').forEach(c => c.classList.remove('active'));
  btn.classList.add('active');
  filterTable();
}
function filterTable() {
  const q = (document.getElementById('searchInput')?.value || '').toLowerCase();
  document.querySelectorAll('#resBody tr[data-status]').forEach(row => {
    const matchStatus = currentFilter === 'all' || row.dataset.status === currentFilter;
    const matchSearch = !q || row.dataset.search.includes(q);
    row.style.display = matchStatus && matchSearch ? '' : 'none';
  });
}
function editMenu(id, name, desc, price, cat, img, stock, available) {
  document.getElementById('edit_menu_id').value = id;
  document.getElementById('edit_name').value = name;
  document.getElementById('edit_description').value = desc;
  document.getElementById('edit_price').value = price;
  document.getElementById('edit_category').value = cat;
  document.getElementById('edit_image').value = img;
  document.getElementById('edit_stock').value = stock || 0;
  document.getElementById('edit_available').checked = available == 1;
  document.getElementById('editMenuModal').style.display = 'flex';
}

// Receipt viewer
function viewReceipt(url) {
  const modal = document.getElementById('receiptModal');
  const img = document.getElementById('receiptImage');
  img.src = url;
  modal.style.display = 'flex';
}

function closeReceiptModal() {
  document.getElementById('receiptModal').style.display = 'none';
}

// Mobile sidebar toggle
const mobileToggle = document.getElementById('mobileToggle');
const sidebar = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebarOverlay');

if (mobileToggle && sidebar && sidebarOverlay) {
  mobileToggle.addEventListener('click', () => {
    sidebar.classList.toggle('active');
    sidebarOverlay.classList.toggle('active');
  });
  
  sidebarOverlay.addEventListener('click', () => {
    sidebar.classList.remove('active');
    sidebarOverlay.classList.remove('active');
  });
  
  // Close sidebar when clicking nav items on mobile
  document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', () => {
      if (window.innerWidth <= 768) {
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
      }
    });
  });
}

</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
