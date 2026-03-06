<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/menu.php';
requireLogin();

$items = getMenuItems();
$cats = getCategories();

$user = array_values(array_filter(loadUsers(), fn($u) => $u['id'] === getCurrentUser()['id']))[0] ?? getCurrentUser();
$_SESSION['user'] = $user;

$cart = $user['cart'] ?? [];
$orders = $user['orders'] ?? [];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

$saveCart = function($c) { 
    updateUser($user['id'], ['cart' => $c]); 
    $_SESSION['user']['cart'] = $c;
};
$json = fn($data) => !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && print json_encode($data) . exit;

if ($action === 'add_to_cart' && isset($_POST['item_id'])) {
    $id = (int)$_POST['item_id'];
    $found = array_filter($cart, fn($c) => $c['id'] === $id);
    if ($found) { $cart[key($found)]['qty']++; }
    else { $item = array_filter($items, fn($i) => $i['id'] === $id)[0] ?? []; if ($item) $cart[] = ['id' => $id, 'name' => $item['name'], 'price' => $item['price'], 'emoji' => $item['emoji'], 'qty' => 1]; }
    $saveCart($cart);
    $json(['cart' => $cart, 'count' => array_sum(array_column($cart, 'qty'))]);
}

if ($action === 'remove_from_cart' && isset($_POST['item_id'])) {
    $cart = array_values(array_filter($cart, fn($c) => $c['id'] !== (int)$_POST['item_id']));
    $saveCart($cart);
    $json(['cart' => $cart, 'count' => array_sum(array_column($cart, 'qty'))]);
}

if ($action === 'update_qty' && isset($_POST['item_id'], $_POST['qty'])) {
    $id = (int)$_POST['item_id'];
    $qty = max(0, (int)$_POST['qty']);
    $cart = $qty === 0 ? array_values(array_filter($cart, fn($c) => $c['id'] !== $id)) : array_map(fn($c) => $c['id'] === $id ? ['qty' => $qty] + $c : $c, $cart);
    $saveCart($cart);
    $json(['cart' => $cart, 'count' => array_sum(array_column($cart, 'qty'))]);
}

if ($action === 'place_order' && $cart) {
    $subtotal = array_sum(array_map(fn($c) => $c['price'] * $c['qty'], $cart));
    $delivery = 2.99;
    $tax = round($subtotal * 0.08, 2);
    $order = ['id' => strtoupper(substr(uniqid(), -6)), 'items' => $cart, 'subtotal' => $subtotal, 'delivery' => $delivery, 'tax' => $tax, 'total' => round($subtotal + $delivery + $tax, 2), 'status' => 'Confirmed', 'date' => date('M j, Y g:i A')];
    updateUser($user['id'], ['cart' => [], 'orders' => [...$orders, $order]]);
    $_SESSION['user']['cart'] = $_SESSION['user']['orders'] = [];
    $json(['success' => true, 'order' => $order]);
    header('Location: dashboard.php?tab=orders&placed=1');
    exit;
}

$cartCount = array_sum(array_column($cart, 'qty'));
$cartTotal = array_sum(array_map(fn($c) => $c['price'] * $c['qty'], $cart));
$activeTab = $_GET['tab'] ?? 'menu';
$profilePicUrl = 'uploads/profiles/' . ($user['profile_pic'] ?? 'default.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Sheriffs</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --cream:#FFF8F0; --orange:#FF5722; --orange-dark:#E64A19; --orange-light:#FF8A65; --brown:#3E2723; --gold:#FFB300; --green:#2E7D32; --gray:#757575; --sidebar-w:260px; }
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif; background:#F5F0EB; color:var(--brown); min-height:100vh; display:flex; }
        
        .sidebar { width:var(--sidebar-w); background:var(--brown); min-height:100vh; position:fixed; left:0; top:0; bottom:0; display:flex; flex-direction:column; z-index:50; padding:0 0 2rem; box-shadow:4px 0 20px rgba(0,0,0,0.15); }
        .sidebar-logo { font-family:'Playfair Display',serif; font-size:1.6rem; font-weight:900; color:#fff; text-decoration:none; padding:1.8rem 1.6rem; border-bottom:1px solid rgba(255,255,255,0.08); display:block; }
        .sidebar-logo span { color:var(--orange-light); }
        .sidebar-profile { padding:1.5rem 1.6rem; border-bottom:1px solid rgba(255,255,255,0.08); display:flex; align-items:center; gap:0.85rem; }
        .sidebar-avatar, .sidebar-avatar-fallback { width:46px; height:46px; border-radius:50%; object-fit:cover; border:2px solid var(--orange-light); flex-shrink:0; }
        .sidebar-avatar-fallback { background:linear-gradient(135deg,var(--orange-light),var(--orange)); display:flex; align-items:center; justify-content:center; font-size:1.3rem; }
        .sidebar-username { font-weight:600; font-size:0.9rem; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .sidebar-email { font-size:0.75rem; color:rgba(255,255,255,0.5); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .sidebar-nav { flex:1; padding:1.2rem 0; }
        .nav-section-title { font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:1.5px; color:rgba(255,255,255,0.35); padding:0 1.6rem; margin:1rem 0 0.5rem; }
        .nav-item { display:flex; align-items:center; gap:0.85rem; padding:0.75rem 1.6rem; color:rgba(255,255,255,0.7); text-decoration:none; font-size:0.92rem; font-weight:500; transition:all 0.2s; cursor:pointer; border:none; background:none; width:100%; text-align:left; position:relative; }
        .nav-item:hover { color:#fff; background:rgba(255,255,255,0.06); }
        .nav-item.active { color:#fff; background:rgba(255,87,34,0.2); }
        .nav-item.active::before { content:''; position:absolute; left:0; top:0; bottom:0; width:3px; background:var(--orange); border-radius:0 2px 2px 0; }
        .nav-icon { font-size:1.1rem; width:22px; text-align:center; }
        .nav-badge { margin-left:auto; background:var(--orange); color:#fff; border-radius:50px; padding:0.15rem 0.55rem; font-size:0.72rem; font-weight:700; min-width:20px; text-align:center; }
        .sidebar-logout { padding:0 1rem; }
        .logout-btn { display:flex; align-items:center; gap:0.75rem; color:rgba(255,255,255,0.6); text-decoration:none; padding:0.85rem 1rem; border-radius:12px; font-size:0.9rem; transition:all 0.2s; width:100%; }
        .logout-btn:hover { color:#FF5252; background:rgba(255,82,82,0.1); }

        .main { margin-left:var(--sidebar-w); flex:1; min-height:100vh; display:flex; flex-direction:column; }
        .topbar { background:#fff; padding:1rem 2.5rem; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid rgba(62,39,35,0.08); position:sticky; top:0; z-index:40; }
        .topbar-title { font-family:'Playfair Display',serif; font-size:1.4rem; font-weight:700; }
        .topbar-right { display:flex; align-items:center; gap:1rem; }
        .cart-btn { background:var(--orange); color:#fff; border:none; cursor:pointer; padding:0.6rem 1.2rem; border-radius:50px; font-family:'DM Sans',sans-serif; font-size:0.88rem; font-weight:600; display:flex; align-items:center; gap:0.5rem; transition:all 0.2s; box-shadow:0 3px 12px rgba(255,87,34,0.3); }
        .cart-btn:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(255,87,34,0.35); }
        .cart-count { background:#fff; color:var(--orange); border-radius:50%; width:20px; height:20px; display:flex; align-items:center; justify-content:center; font-size:0.72rem; font-weight:700; }
        .content { padding:2rem 2.5rem; flex:1; }
        .tab-pane { display:none; }
        .tab-pane.active { display:block; }

        .welcome-banner { background:linear-gradient(135deg,var(--orange),var(--orange-dark)); border-radius:24px; padding:2rem 2.5rem; color:#fff; display:flex; align-items:center; justify-content:space-between; margin-bottom:2rem; overflow:hidden; position:relative; }
        .welcome-banner::after { content:'🍽️'; position:absolute; right:120px; font-size:6rem; opacity:0.15; top:-10px; }
        .welcome-name { font-family:'Playfair Display',serif; font-size:1.8rem; font-weight:700; margin-bottom:0.4rem; }
        .welcome-sub { opacity:0.85; font-size:0.95rem; }
        .quick-stats { display:flex; gap:1.5rem; }
        .quick-stat { text-align:center; }
        .quick-stat-num { font-size:1.5rem; font-weight:700; }
        .quick-stat-label { font-size:0.78rem; opacity:0.8; }

        .filter-bar { display:flex; gap:0.75rem; flex-wrap:wrap; margin-bottom:1.5rem; }
        .filter-btn { background:#fff; border:2px solid rgba(62,39,35,0.1); color:var(--brown); padding:0.5rem 1.2rem; border-radius:50px; font-size:0.85rem; font-weight:500; cursor:pointer; transition:all 0.2s; font-family:'DM Sans',sans-serif; }
        .filter-btn:hover, .filter-btn.active { background:var(--orange); border-color:var(--orange); color:#fff; }

        .menu-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:1.5rem; }
        .food-card { background:#fff; border-radius:20px; overflow:hidden; transition:transform 0.3s,box-shadow 0.3s; position:relative; }
        .food-card:hover { transform:translateY(-4px); box-shadow:0 15px 40px rgba(0,0,0,0.1); }
        .food-emoji-bg { background:linear-gradient(135deg,#FF8A65,#FF5722); height:130px; display:flex; align-items:center; justify-content:center; font-size:3.5rem; position:relative; }
        .food-badge { position:absolute; top:10px; right:10px; background:rgba(255,255,255,0.9); color:var(--orange); font-size:0.7rem; font-weight:700; padding:0.2rem 0.6rem; border-radius:50px; }
        .food-info { padding:1.2rem; }
        .food-cat { font-size:0.72rem; font-weight:600; color:var(--orange); text-transform:uppercase; letter-spacing:1px; margin-bottom:0.3rem; }
        .food-name { font-weight:600; font-size:1rem; margin-bottom:0.4rem; }
        .food-desc { color:var(--gray); font-size:0.82rem; line-height:1.5; margin-bottom:0.8rem; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .food-meta { display:flex; align-items:center; gap:1rem; margin-bottom:1rem; }
        .food-rating { font-size:0.82rem; color:var(--gold); font-weight:600; }
        .food-time { font-size:0.78rem; color:var(--gray); }
        .food-footer { display:flex; align-items:center; justify-content:space-between; }
        .food-price { font-family:'Playfair Display',serif; font-size:1.2rem; font-weight:700; color:var(--orange); }
        .add-btn { background:var(--orange); color:#fff; border:none; width:36px; height:36px; border-radius:50%; font-size:1.3rem; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.2s; box-shadow:0 3px 10px rgba(255,87,34,0.35); }
        .add-btn:hover { background:var(--orange-dark); transform:scale(1.1); }
        .add-btn.added { background:var(--green); }

        .cart-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:200; }
        .cart-overlay.open { display:block; }
        .cart-drawer { position:fixed; right:-420px; top:0; bottom:0; width:420px; background:#fff; z-index:201; transition:right 0.35s cubic-bezier(.4,0,.2,1); display:flex; flex-direction:column; box-shadow:-10px 0 40px rgba(0,0,0,0.15); }
        .cart-drawer.open { right:0; }
        .cart-header { padding:1.5rem; border-bottom:1px solid rgba(62,39,35,0.1); display:flex; align-items:center; justify-content:space-between; }
        .cart-header h2 { font-family:'Playfair Display',serif; font-size:1.3rem; font-weight:700; }
        .cart-close { background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--gray); transition:color 0.2s; }
        .cart-close:hover { color:var(--brown); }
        .cart-items { flex:1; overflow-y:auto; padding:1rem 1.5rem; }
        .cart-empty { text-align:center; padding:3rem 0; color:var(--gray); }
        .cart-empty div { font-size:3.5rem; margin-bottom:1rem; }
        .cart-item { display:flex; align-items:center; gap:1rem; padding:1rem 0; border-bottom:1px solid rgba(62,39,35,0.07); }
        .cart-item-emoji { font-size:2rem; width:45px; text-align:center; flex-shrink:0; }
        .cart-item-info { flex:1; }
        .cart-item-name { font-weight:600; font-size:0.9rem; }
        .cart-item-price { color:var(--orange); font-size:0.85rem; font-weight:500; }
        .qty-control { display:flex; align-items:center; gap:0.5rem; }
        .qty-btn { background:rgba(62,39,35,0.08); border:none; width:26px; height:26px; border-radius:50%; cursor:pointer; font-size:1rem; font-weight:700; display:flex; align-items:center; justify-content:center; transition:all 0.2s; }
        .qty-btn:hover { background:var(--orange); color:#fff; }
        .qty-num { font-weight:600; font-size:0.9rem; min-width:20px; text-align:center; }
        .cart-remove { background:none; border:none; color:#FF5252; cursor:pointer; font-size:1.1rem; transition:transform 0.2s; padding:0.25rem; }
        .cart-remove:hover { transform:scale(1.2); }
        .cart-footer { padding:1.5rem; border-top:1px solid rgba(62,39,35,0.1); }
        .cart-totals { margin-bottom:1.2rem; }
        .cart-row { display:flex; justify-content:space-between; font-size:0.88rem; color:var(--gray); margin-bottom:0.5rem; }
        .cart-row.total { font-size:1rem; font-weight:700; color:var(--brown); border-top:1px solid rgba(62,39,35,0.1); padding-top:0.75rem; margin-top:0.75rem; }
        .checkout-btn { width:100%; background:var(--orange); color:#fff; border:none; padding:1rem; border-radius:14px; font-family:'DM Sans',sans-serif; font-size:1rem; font-weight:600; cursor:pointer; transition:all 0.3s; box-shadow:0 4px 15px rgba(255,87,34,0.35); }
        .checkout-btn:hover { background:var(--orange-dark); transform:translateY(-2px); }
        .checkout-btn:disabled { background:#ccc; cursor:not-allowed; transform:none; box-shadow:none; }

        .orders-list { display:flex; flex-direction:column; gap:1.5rem; }
        .order-card-full { background:#fff; border-radius:20px; padding:1.5rem 2rem; box-shadow:0 2px 10px rgba(0,0,0,0.04); }
        .order-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.2rem; }
        .order-id { font-family:'Playfair Display',serif; font-size:1.1rem; font-weight:700; }
        .order-date { font-size:0.82rem; color:var(--gray); }
        .order-status { background:rgba(46,125,50,0.1); color:var(--green); padding:0.3rem 1rem; border-radius:50px; font-size:0.8rem; font-weight:600; }
        .order-items-list { display:flex; flex-wrap:wrap; gap:0.5rem; margin-bottom:1.2rem; }
        .order-item-chip { background:var(--cream); padding:0.35rem 0.85rem; border-radius:50px; font-size:0.82rem; }
        .order-total-row { display:flex; align-items:center; justify-content:space-between; border-top:1px solid rgba(62,39,35,0.08); padding-top:1rem; }
        .order-total-label { font-size:0.88rem; color:var(--gray); }
        .order-total-amount { font-family:'Playfair Display',serif; font-size:1.2rem; font-weight:700; color:var(--orange); }
        .empty-state { text-align:center; padding:4rem 2rem; color:var(--gray); }
        .empty-state .es-icon { font-size:4rem; margin-bottom:1rem; }
        .empty-state h3 { font-size:1.2rem; color:var(--brown); margin-bottom:0.5rem; }

        .profile-card { background:#fff; border-radius:24px; padding:3rem; max-width:600px; box-shadow:0 2px 12px rgba(0,0,0,0.05); }
        .profile-top { display:flex; align-items:center; gap:2rem; margin-bottom:2.5rem; }
        .profile-pic-large { width:90px; height:90px; border-radius:50%; object-fit:cover; border:4px solid var(--orange); }
        .profile-pic-fallback { width:90px; height:90px; border-radius:50%; background:linear-gradient(135deg,var(--orange-light),var(--orange)); display:flex; align-items:center; justify-content:center; font-size:2.5rem; border:4px solid var(--orange); }
        .profile-name { font-family:'Playfair Display',serif; font-size:1.6rem; font-weight:700; }
        .profile-email { color:var(--gray); font-size:0.9rem; margin-top:0.3rem; }
        .profile-joined { font-size:0.8rem; color:var(--orange); font-weight:500; margin-top:0.4rem; }
        .profile-fields { display:grid; grid-template-columns:1fr 1fr; gap:1.2rem; }
        .profile-field label { font-size:0.78rem; font-weight:600; color:var(--gray); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:0.3rem; }
        .profile-field p { font-weight:500; font-size:0.95rem; }
        .profile-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; margin-top:2rem; }
        .pstat { background:var(--cream); border-radius:16px; padding:1.2rem; text-align:center; }
        .pstat-num { font-family:'Playfair Display',serif; font-size:1.8rem; font-weight:700; color:var(--orange); }
        .pstat-label { font-size:0.78rem; color:var(--gray); margin-top:0.2rem; }

        .toast { position:fixed; bottom:2rem; right:2rem; background:var(--brown); color:#fff; padding:1rem 1.5rem; border-radius:14px; font-size:0.9rem; font-weight:500; display:flex; align-items:center; gap:0.75rem; transform:translateY(80px); opacity:0; transition:all 0.35s cubic-bezier(.4,0,.2,1); z-index:300; box-shadow:0 10px 30px rgba(0,0,0,0.2); }
        .toast.show { transform:translateY(0); opacity:1; }
.toast.success { background: var(--green); }
        .toast.error { background: #FF5252; }

        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:500; align-items:center; justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal { background:#fff; border-radius:28px; padding:3rem; text-align:center; max-width:400px; width:90%; animation:popIn 0.4s cubic-bezier(.34,1.56,.64,1) both; }
        .modal-icon { font-size:4rem; margin-bottom:1rem; }
        .modal h2 { font-family:'Playfair Display',serif; font-size:1.6rem; font-weight:700; margin-bottom:0.5rem; }
        .modal p { color:var(--gray); font-size:0.95rem; margin-bottom:1.5rem; }
        .modal-order-id { background:var(--cream); border-radius:12px; padding:0.75rem 1rem; font-family:monospace; font-size:1.2rem; font-weight:700; color:var(--orange); margin-bottom:1.5rem; }
        .modal-close { background:var(--orange); color:#fff; border:none; padding:0.85rem 2.5rem; border-radius:50px; font-family:'DM Sans',sans-serif; font-size:0.95rem; font-weight:600; cursor:pointer; transition:all 0.2s; }
        .modal-close:hover { background:var(--orange-dark); }
        @keyframes popIn { from { transform:scale(0.85); opacity:0; } to { transform:scale(1); opacity:1; } }

        @media (max-width:900px) {
            .sidebar { transform:translateX(-100%); transition:transform 0.3s; }
            .sidebar.open { transform:translateX(0); }
            .main { margin-left:0; }
            .cart-drawer { width:100%; right:-100%; }
        }
    </style>
</head>
<body>
<aside class="sidebar" id="sidebar">
    <a href="index.html" class="sidebar-logo">Sheriff<span>s</span></a>
    <div class="sidebar-profile">
        <?php if ($user['profile_pic'] && $user['profile_pic'] !== 'default.png' && file_exists(__DIR__ . '/uploads/profiles/' . $user['profile_pic'])): ?>
            <img src="<?= htmlspecialchars($profilePicUrl) ?>" alt="Profile" class="sidebar-avatar">
        <?php else: ?>
            <div class="sidebar-avatar-fallback">👤</div>
        <?php endif; ?>
        <div><div class="sidebar-username"><?= htmlspecialchars($user['name']) ?></div><div class="sidebar-email"><?= htmlspecialchars($user['email']) ?></div></div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-title">Main</div>
        <a class="nav-item <?= $activeTab==='menu'?'active':'' ?>" onclick="switchTab('menu')"><span class="nav-icon">🍽️</span> Menu</a>
<a class="nav-item <?= $activeTab==='cart'?'active':'' ?>" onclick="openCart()"><span class="nav-icon">🛒</span> My Cart<?php if ($cartCount): ?><span class="nav-badge"><?= $cartCount ?></span><?php endif; ?></a>
        <a class="nav-item <?= $activeTab==='orders'?'active':'' ?>" onclick="switchTab('orders')"><span class="nav-icon">📦</span> My Orders<?php if (count($orders)): ?><span class="nav-badge"><?= count($orders) ?></span><?php endif; ?></a>
        <div class="nav-section-title">Account</div>
        <a class="nav-item <?= $activeTab==='profile'?'active':'' ?>" onclick="switchTab('profile')"><span class="nav-icon">👤</span> Profile</a>
    </nav>
    <div class="sidebar-logout"><a href="logout.php" class="logout-btn"><span>🚪</span> Sign Out</a></div>
</aside>

<div class="main">
    <div class="topbar">
        <div class="topbar-title" id="pageTitle">🍽️ Menu</div>
        <div class="topbar-right"><button class="cart-btn" onclick="openCart()">🛒 Cart<span class="cart-count" id="cartCountBadge"><?= $cartCount ?></span></button></div>
    </div>
    <div class="content">
        <div class="tab-pane <?= $activeTab==='menu'?'active':'' ?>" id="tab-menu">
            <div class="welcome-banner">
                <div><div class="welcome-name">Hey, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>! 👋</div><div class="welcome-sub">What are we eating today? Browse our menu below.</div></div>
                <div class="quick-stats"><div class="quick-stat"><div class="quick-stat-num"><?= count($orders) ?></div><div class="quick-stat-label">Orders</div></div><div class="quick-stat"><div class="quick-stat-num"><?= $cartCount ?></div><div class="quick-stat-label">In Cart</div></div></div>
            </div>
            <div class="filter-bar"><?php foreach ($cats as $cat): ?><button class="filter-btn <?= $cat==='All'?'active':'' ?>" onclick="filterMenu('<?= $cat ?>', this)"><?= $cat ?></button><?php endforeach; ?></div>
            <div class="menu-grid"><?php foreach ($items as $item): ?>
                <div class="food-card" data-category="<?= htmlspecialchars($item['category']) ?>">
                    <div class="food-emoji-bg"><?= $item['emoji'] ?><div class="food-badge"><?= htmlspecialchars($item['badge']) ?></div></div>
                    <div class="food-info"><div class="food-cat"><?= htmlspecialchars($item['category']) ?></div><div class="food-name"><?= htmlspecialchars($item['name']) ?></div><div class="food-desc"><?= htmlspecialchars($item['description']) ?></div><div class="food-meta"><span class="food-rating">★ <?= $item['rating'] ?></span><span class="food-time">⏱ <?= htmlspecialchars($item['time']) ?></span></div><div class="food-footer"><div class="food-price">$<?= number_format($item['price'], 2) ?></div><button class="add-btn" id="add-<?= $item['id'] ?>" onclick="addToCart(<?= $item['id'] ?>, this)" title="Add to cart">+</button></div></div>
                </div><?php endforeach; ?></div>
        </div>

        <div class="tab-pane <?= $activeTab==='orders'?'active':'' ?>" id="tab-orders">
            <h2 style="font-family:'Playfair Display',serif;margin-bottom:1.5rem;font-size:1.5rem;">My Orders</h2>
            <?php if (empty($orders)): ?>
            <div class="empty-state"><div class="es-icon">📦</div><h3>No orders yet</h3><p>Start by adding items to your cart!</p><br><button class="cart-btn" onclick="switchTab('menu')" style="margin:0 auto">Browse Menu →</button></div>
            <?php else: ?>
            <div class="orders-list"><?php foreach (array_reverse($orders) as $order): ?>
                <div class="order-card-full"><div class="order-header"><div><div class="order-id">Order #<?= htmlspecialchars($order['id']) ?></div><div class="order-date"><?= htmlspecialchars($order['date']) ?></div></div><div class="order-status">✓ <?= htmlspecialchars($order['status']) ?></div></div><div class="order-items-list"><?php foreach ($order['items'] as $oi): ?><span class="order-item-chip"><?= $oi['emoji'] ?> <?= htmlspecialchars($oi['name']) ?> ×<?= $oi['qty'] ?></span><?php endforeach; ?></div><div class="order-total-row"><div><div class="order-total-label">Subtotal $<?= number_format($order['subtotal'],2) ?> + Delivery $<?= number_format($order['delivery'],2) ?> + Tax $<?= number_format($order['tax'],2) ?></div></div><div class="order-total-amount">$<?= number_format($order['total'],2) ?></div></div></div>
            <?php endforeach; ?></div><?php endif; ?>
        </div>

        <div class="tab-pane <?= $activeTab==='profile'?'active':'' ?>" id="tab-profile">
            <h2 style="font-family:'Playfair Display',serif;margin-bottom:1.5rem;font-size:1.5rem;">My Profile</h2>
            <div class="profile-card">
                <div class="profile-top">
                    <?php if ($user['profile_pic'] && $user['profile_pic'] !== 'default.png' && file_exists(__DIR__ . '/uploads/profiles/' . $user['profile_pic'])): ?><img src="<?= htmlspecialchars($profilePicUrl) ?>" alt="Profile" class="profile-pic-large"><?php else: ?><div class="profile-pic-fallback">👤</div><?php endif; ?>
                    <div><div class="profile-name"><?= htmlspecialchars($user['name']) ?></div><div class="profile-email"><?= htmlspecialchars($user['email']) ?></div><div class="profile-joined">Member since <?= date('F Y', strtotime($user['created_at'])) ?></div></div>
                </div>
                <div class="profile-fields">
                    <div class="profile-field"><label>Full Name</label><p><?= htmlspecialchars($user['name']) ?></p></div>
                    <div class="profile-field"><label>Email</label><p><?= htmlspecialchars($user['email']) ?></p></div>
                    <div class="profile-field"><label>Phone</label><p><?= $user['phone'] ? htmlspecialchars($user['phone']) : '—' ?></p></div>
                    <div class="profile-field"><label>Account ID</label><p style="font-family:monospace;font-size:0.8rem;"><?= htmlspecialchars($user['id']) ?></p></div>
                </div>
                <div class="profile-stats"><div class="pstat"><div class="pstat-num"><?= count($orders) ?></div><div class="pstat-label">Total Orders</div></div><div class="pstat"><div class="pstat-num">$<?= number_format(array_sum(array_column($orders, 'total')), 0) ?></div><div class="pstat-label">Total Spent</div></div><div class="pstat"><div class="pstat-num"><?= $cartCount ?></div><div class="pstat-label">Items in Cart</div></div></div>
            </div>
        </div>
    </div>
</div>

<div class="cart-overlay" id="cartOverlay" onclick="closeCart()"></div>
<div class="cart-drawer" id="cartDrawer">
    <div class="cart-header"><h2>🛒 My Cart</h2><button class="cart-close" onclick="closeCart()">×</button></div>
    <div class="cart-items" id="cartItems"><?php if (empty($cart)): ?><div class="cart-empty"><div>🛒</div><p>Your cart is empty.<br>Add some delicious items!</p></div><?php else: ?><?php foreach ($cart as $ci): ?><div class="cart-item" id="cart-item-<?= $ci['id'] ?>"><div class="cart-item-emoji"><?= $ci['emoji'] ?></div><div class="cart-item-info"><div class="cart-item-name"><?= htmlspecialchars($ci['name']) ?></div><div class="cart-item-price">$<?= number_format($ci['price'] * $ci['qty'], 2) ?></div></div><div class="qty-control"><button class="qty-btn" onclick="updateQty(<?= $ci['id'] ?>, <?= $ci['qty']-1 ?>)">−</button><span class="qty-num" id="qty-<?= $ci['id'] ?>"><?= $ci['qty'] ?></span><button class="qty-btn" onclick="updateQty(<?= $ci['id'] ?>, <?= $ci['qty']+1 ?>)">+</button></div><button class="cart-remove" onclick="removeFromCart(<?= $ci['id'] ?>)">🗑</button></div><?php endforeach; ?><?php endif; ?></div>
    <div class="cart-footer"><div class="cart-totals"><div class="cart-row"><span>Subtotal</span><span id="cartSubtotal">$<?= number_format($cartTotal, 2) ?></span></div><div class="cart-row"><span>Delivery</span><span>$2.99</span></div><div class="cart-row"><span>Tax (8%)</span><span id="cartTax">$<?= number_format($cartTotal * 0.08, 2) ?></span></div><div class="cart-row total"><span>Total</span><span id="cartGrandTotal">$<?= number_format($cartTotal + 2.99 + $cartTotal*0.08, 2) ?></span></div></div><button class="checkout-btn" id="checkoutBtn" onclick="placeOrder()" <?= empty($cart)?'disabled':'' ?>><?= empty($cart) ? 'Cart is Empty' : '🎉 Place Order' ?></button></div>
</div>

<div class="modal-overlay" id="orderModal"><div class="modal"><div class="modal-icon">🎉</div><h2>Order Placed!</h2><p>Your food is being prepared. Estimated delivery: 25-30 minutes.</p><div class="modal-order-id" id="modalOrderId">#XXXXXX</div><button class="modal-close" onclick="closeOrderModal()">View My Orders</button></div></div>
<div class="toast" id="toast"></div>

<script>
const cartData = <?= json_encode($cart) ?>, currentCart = [...cartData];
const showToast = (msg, type = '') => { const t = document.getElementById('toast'); t.textContent = msg; t.className = 'toast show ' + type; setTimeout(() => t.className = 'toast', 2800); };
const switchTab = tab => { document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active')); document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active')); document.getElementById('tab-' + tab).classList.add('active'); const titles = { menu: '🍽️ Menu', orders: '📦 My Orders', profile: '👤 Profile' }; document.getElementById('pageTitle').textContent = titles[tab] || ''; document.querySelectorAll('.nav-item')[['menu','orders','profile'].indexOf(tab)].classList.add('active'); };
const openCart = () => { document.getElementById('cartOverlay').classList.add('open'); document.getElementById('cartDrawer').classList.add('open'); };
const closeCart = () => { document.getElementById('cartOverlay').classList.remove('open'); document.getElementById('cartDrawer').classList.remove('open'); };
const filterMenu = (cat, btn) => { document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active')); btn.classList.add('active'); document.querySelectorAll('.food-card').forEach(c => c.style.display = (cat === 'All' || c.dataset.category === cat) ? '' : 'none'); };
const api = async (action, data = {}) => {
    try {
        const fd = new FormData();
        fd.append('action', action);
        Object.entries(data).forEach(([k, v]) => fd.append(k, v));
        const response = await fetch('dashboard.php', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (!response.ok) throw new Error('Server error: ' + response.status);
        const result = await response.json();
        if (result.error) throw new Error(result.error);
        return result;
    } catch (error) {
        console.error('API Error:', error);
        showToast('Error: ' + error.message, 'error');
        throw error;
    }
};

const addToCart = async (id, btn) => {
    try {
        btn.disabled = true;
        btn.textContent = '...';
        const data = await api('add_to_cart', { item_id: id });
        currentCart = data.cart;
        updateCartUI(data.cart, data.count);
        btn.textContent = '✓';
        btn.classList.add('added');
        setTimeout(() => { btn.textContent = '+'; btn.classList.remove('added'); }, 1200);
        showToast('Added to cart! 🛒');
    } catch (error) {
        btn.textContent = '+';
    } finally {
        btn.disabled = false;
    }
};
const removeFromCart = async id => { const data = await api('remove_from_cart', { item_id: id }); currentCart = data.cart; document.getElementById('cart-item-' + id)?.remove(); updateCartUI(data.cart, data.count); showToast('Item removed.'); };
const updateQty = async (id, qty) => { if (qty < 0) return; const data = await api('update_qty', { item_id: id, qty }); currentCart = data.cart; qty === 0 ? document.getElementById('cart-item-' + id)?.remove() : document.getElementById('qty-' + id).textContent = qty; updateCartUI(data.cart, data.count); };
const updateCartUI = (cart, count) => { document.getElementById('cartCountBadge').textContent = count; const navBadges = document.querySelectorAll('.nav-badge'); if (navBadges[0]) navBadges[0].textContent = count; const subtotal = cart.reduce((s, c) => s + c.price * c.qty, 0), tax = subtotal * 0.08, total = subtotal + 2.99 + tax; document.getElementById('cartSubtotal').textContent = '$' + subtotal.toFixed(2); document.getElementById('cartTax').textContent = '$' + tax.toFixed(2); document.getElementById('cartGrandTotal').textContent = '$' + total.toFixed(2); const btn = document.getElementById('checkoutBtn'); cart.length ? (btn.disabled = false, btn.textContent = '🎉 Place Order') : (btn.disabled = true, btn.textContent = 'Cart is Empty', document.getElementById('cartItems').innerHTML = '<div class="cart-empty"><div>🛒</div><p>Your cart is empty.<br>Add some delicious items!</p></div>'); };
const placeOrder = async () => { const btn = document.getElementById('checkoutBtn'); btn.disabled = true; btn.textContent = '⏳ Placing order...'; const data = await api('place_order'); if (data.success) { closeCart(); currentCart = []; updateCartUI([], 0); document.getElementById('modalOrderId').textContent = '#' + data.order.id; document.getElementById('orderModal').classList.add('open'); } };
const closeOrderModal = () => { document.getElementById('orderModal').classList.remove('open'); switchTab('orders'); location.reload(); };
<?= isset($_GET['placed']) ? "document.getElementById('orderModal').classList.add('open');" : '' ?>
</script>
</body>
</html>
