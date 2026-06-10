<?php
/**
 * NET-CLOUD-CONFIG - Secure Admin Panel & Log Radar (English)
 * File Name: admin.php
 */

session_start();

$dbFile = __DIR__ . '/db.json';
$db = json_decode(file_exists($dbFile) ? file_get_contents($dbFile) : '[]', true) ?: [];

// SECURE CREDENTIALS SETUP
$adminUser = 'Admin';
$adminPass = '38sPcd6Ysr04NGVk'; 

// Logout action
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged']);
    session_destroy();
    header("Location: admin.php"); exit;
}

// Authentication check
$loginError = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    if ($_POST['username'] === $adminUser && $_POST['password'] === $adminPass) {
        $_SESSION['admin_logged'] = true;
        header("Location: admin.php"); exit;
    } else {
        $loginError = "Invalid Account Credentials!";
    }
}

// Delete item action
if (isset($_GET['delete']) && isset($_SESSION['admin_logged'])) {
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['delete']);
    if (isset($db[$id])) { 
        @unlink($db[$id]['real_path']); 
        unset($db[$id]); 
        file_put_contents($dbFile, json_encode($db)); 
    }
    header("Location: admin.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NETCLOUD | Admin Space</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .glass-panel { 
            background: #0f1524; 
            border: 1px solid #1e2738; 
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.6); 
        }
        .custom-scroll::-webkit-scrollbar { width: 4px; height: 4px; }
        .custom-scroll::-webkit-scrollbar-track { background: #0a0f1c; border-radius: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #51C0C0; border-radius: 4px; }
        
        .neon-text-glow { text-shadow: 0 0 10px rgba(81, 192, 192, 0.5); }
        .btn-glow { box-shadow: 0 0 15px rgba(81, 192, 192, 0.2); }
    </style>
</head>
<body class="bg-[#0a0f1c] min-h-screen text-slate-200 p-4 flex items-center justify-center">

    <?php if (!isset($_SESSION['admin_logged'])): ?>
        <div class="glass-panel w-full max-w-[24rem] rounded-2xl p-6 md:p-8 relative">
            <div class="text-center mt-3 mb-8">
                <div class="w-14 h-14 rounded-full border border-[#1e2738] bg-[#0f1524] flex items-center justify-center mx-auto mb-4 relative">
                    <div class="absolute inset-2 rounded-full border border-[#51C0C0]/30 bg-[#51C0C0]/5"></div>
                    <i class="fa-solid fa-shield-halved text-[#51C0C0] text-xl z-10"></i>
                </div>
                <h2 class="text-[22px] font-bold tracking-widest text-white">SYSTEM <span class="text-[#51C0C0] neon-text-glow">LOGIN</span></h2>
                <p class="text-[10px] tracking-[0.1em] text-[#425975] mt-2 font-mono uppercase">Identity validation required</p>
            </div>

            <?php if($loginError): ?>
                <div class="bg-[#1a0f14] border border-red-900/50 text-red-400 text-xs p-3 rounded-xl text-center mb-5 font-mono uppercase tracking-wide">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i> <?= $loginError ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <input type="hidden" name="login_submit" value="1">
                <div>
                    <label class="flex items-center text-[10px] text-[#51C0C0] uppercase tracking-widest font-mono mb-2 ml-1">
                        <i class="fa-solid fa-user-astronaut mr-2"></i> Username
                    </label>
                    <input type="text" name="username" required class="w-full bg-[#0a0f1c] border border-[#1e2738] rounded-xl px-4 py-3.5 text-sm text-white font-mono focus:outline-none focus:border-[#51C0C0] transition placeholder-[#2e3c50]" placeholder="Enter Admin Username">
                </div>
                <div>
                    <label class="flex items-center text-[10px] text-[#51C0C0] uppercase tracking-widest font-mono mb-2 ml-1">
                        <i class="fa-solid fa-key mr-2"></i> Password
                    </label>
                    <input type="password" name="password" required class="w-full bg-[#0a0f1c] border border-[#1e2738] rounded-xl px-4 py-3.5 text-sm text-white font-mono focus:outline-none focus:border-[#51C0C0] transition placeholder-[#2e3c50]" placeholder="••••••••••••">
                </div>
                <button type="submit" class="w-full bg-[#51C0C0] hover:bg-[#43a3a3] text-[#0a0f1c] font-bold py-4 rounded-xl transition btn-glow text-[12px] flex items-center justify-center uppercase tracking-widest mt-4">
                    Access Dashboard <i class="fa-solid fa-arrow-right ml-2"></i>
                </button>
            </form>
        </div>
    <?php else: ?>
        <div class="w-full max-w-5xl my-auto py-8">
            <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4 glass-panel p-6 rounded-2xl relative overflow-hidden">
                <div class="absolute top-0 right-0 w-64 h-64 bg-[#51C0C0] rounded-full blur-[100px] opacity-10 pointer-events-none"></div>

                <div class="text-center md:text-left z-10">
                    <h1 class="text-2xl font-bold tracking-widest text-white flex flex-col md:flex-row items-center gap-2">
                        RADAR<span class="text-[#51C0C0] neon-text-glow">ANALYTICS</span>
                    </h1>
                    <p class="text-[10px] tracking-[0.1em] text-[#8a9bb3] mt-2 font-mono uppercase">
                        <i class="fa-solid fa-satellite-dish animate-pulse text-[#51C0C0] mr-1"></i> Live Sniffer & Traffic Inspection
                    </p>
                </div>
                <div class="flex gap-3 z-10">
                    <a href="/" class="bg-[#0d131f] hover:bg-[#151e2e] border border-[#1e2738] text-[#51C0C0] px-5 py-3 rounded-xl transition text-[11px] font-bold uppercase tracking-wider flex items-center">
                        <i class="fa-solid fa-cloud-arrow-up mr-2"></i> Upload
                    </a>
                    <a href="?logout=1" class="bg-[#1a0f14] hover:bg-[#2a1215] border border-red-900/50 text-red-400 px-5 py-3 rounded-xl transition text-[11px] font-bold uppercase tracking-wider flex items-center">
                        <i class="fa-solid fa-power-off mr-2"></i> Logout
                    </a>
                </div>
            </div>

            <div class="space-y-5">
                <?php foreach(array_reverse($db, true) as $id => $d): 
                    $isExpired = time() > $d['expires'];
                    $isLimited = $d['limit'] > 0 && $d['downloads'] >= $d['limit'];
                    $statusHtml = ($isExpired || $isLimited) 
                        ? '<span class="bg-[#1a0f14] text-red-400 px-3 py-1 rounded-md text-[10px] font-mono uppercase tracking-widest border border-red-900/50">Expired</span>' 
                        : '<span class="bg-[#51C0C0]/10 text-[#51C0C0] px-3 py-1 rounded-md text-[10px] font-mono uppercase tracking-widest border border-[#51C0C0]/30">Active</span>';
                ?>
                <div class="bg-[#0d131f] rounded-2xl border border-[#1e2738] p-5 shadow-lg relative overflow-hidden group">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-[#1e2738] pb-4 mb-4">
                        <div>
                            <span class="text-[10px] uppercase tracking-widest text-[#425975] block mb-1 font-mono">Original File: <?= htmlspecialchars($d['original_name']) ?></span>
                            <span class="font-mono text-[#51C0C0] font-bold text-lg neon-text-glow"><?= $id ?>.hc</span>
                        </div>
                        <div class="flex items-center gap-4 w-full md:w-auto justify-between md:justify-end">
                            <div class="text-right font-mono bg-[#0a0f1c] px-4 py-2 rounded-xl border border-[#1e2738]">
                                <span class="text-[9px] uppercase tracking-widest text-[#425975] block mb-0.5">Downloads</span>
                                <span class="text-white font-bold text-xs"><?= $d['downloads'] ?> <span class="text-[#425975]">/</span> <?= $d['limit'] > 0 ? $d['limit'] : '∞' ?></span>
                            </div>
                            <div><?= $statusHtml ?></div>
                            <a href="?delete=<?= $id ?>" onclick="return confirm('Delete this link permanently?')" class="bg-[#1a0f14] hover:bg-[#2a1215] border border-red-900/50 text-red-400 p-3 rounded-xl transition text-xs">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-[10px] font-mono uppercase tracking-widest text-[#8a9bb3] mb-3 flex items-center">
                            <i class="fa-solid fa-terminal mr-2 text-[#51C0C0]"></i> Live Request Feed:
                        </h4>
                        <div class="bg-[#0a0f1c] rounded-xl overflow-hidden border border-[#1e2738] overflow-x-auto custom-scroll">
                            <table class="w-full text-left whitespace-nowrap">
                                <tbody class="divide-y divide-[#1e2738]">
                                    <?php if(!empty($d['logs'])): ?>
                                        <?php foreach(array_reverse($d['logs']) as $log): 
                                            $badge = ($log['status'] === 'Success') ? 'bg-[#51C0C0]/10 text-[#51C0C0] border-[#51C0C0]/30' : 'bg-[#1a0f14] text-red-400 border-red-900/50';
                                            $clientClass = (strpos($log['client'], 'HTTP Custom') !== false) ? 'text-[#51C0C0] font-bold' : 'text-slate-400';
                                        ?>
                                        <tr class="hover:bg-[#0d131f] transition duration-200">
                                            <td class="px-4 py-3 text-[#425975] font-mono text-[10px]"><?= date('Y-m-d H:i:s', $log['time']) ?></td>
                                            <td class="px-4 py-3 font-mono text-white text-[11px]"><?= htmlspecialchars($log['ip']) ?></td>
                                            <td class="px-4 py-3 text-[11px] <?= $clientClass ?>">
                                                <?= htmlspecialchars($log['client']) ?>
                                                <span class="text-[9px] text-[#425975] block truncate max-w-xs mt-0.5 font-mono"><?= htmlspecialchars($log['ua']) ?></span>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <span class="px-2 py-1 rounded font-mono uppercase tracking-widest border text-[9px] <?= $badge ?>">
                                                    <?= $log['status'] === 'Success' ? 'Fetched' : 'Blocked' ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td class="px-4 py-6 text-center text-[#425975] font-mono text-[10px] uppercase tracking-widest">No request activity tracked yet.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if(empty($db)): ?>
                    <div class="text-center py-16 border-2 border-dashed border-[#1e2738] rounded-2xl bg-[#0d131f]/50">
                        <i class="fa-solid fa-satellite text-4xl text-[#1e2738] mb-4"></i>
                        <p class="text-[#425975] font-mono text-[11px] uppercase tracking-widest">Radar is empty. No configurations active.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
