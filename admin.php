<?php
/**
 * NET-CLOUD-CONFIG - Secure Admin Panel & Log Radar (Cyberpunk UI)
 * File Name: admin.php
 */

session_start();
date_default_timezone_set('Africa/Tunis');

// === UNIFIED SECURITY CHECK ===
if (!isset($_SESSION['main_logged']) || $_SESSION['main_logged'] !== true) {
    header("Location: index.php"); 
    exit;
}

$dbFile = __DIR__ . '/db.json';
$db = json_decode(file_exists($dbFile) ? file_get_contents($dbFile) : '[]', true) ?: [];

// Logout action
if (isset($_GET['logout'])) {
    unset($_SESSION['main_logged']);
    header("Location: index.php"); exit;
}

// Delete SINGLE item action
if (isset($_GET['delete'])) {
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['delete']);
    if (isset($db[$id])) { 
        @unlink($db[$id]['real_path']); 
        unset($db[$id]); 
        file_put_contents($dbFile, json_encode($db)); 
    }
    header("Location: admin.php"); exit;
}

// Delete ALL items action (NEW)
if (isset($_GET['delete_all'])) {
    foreach ($db as $id => $d) {
        @unlink($d['real_path']); 
    }
    $db = []; 
    file_put_contents($dbFile, json_encode($db)); 
    header("Location: admin.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NETCLOUD | Radar Space</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #05080f; overflow-x: hidden; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .glass-panel { background: rgba(10, 15, 28, 0.85); backdrop-filter: blur(12px); border: 1px solid #1e2738; box-shadow: 0 0 40px rgba(0, 0, 0, 0.8), inset 0 0 20px rgba(81, 192, 192, 0.05); }
        .custom-scroll::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #51C0C0; border-radius: 10px; }
        .neon-text-glow { text-shadow: 0 0 15px rgba(81, 192, 192, 0.6), 0 0 30px rgba(81, 192, 192, 0.2); }
        .btn-glow { box-shadow: 0 0 20px rgba(81, 192, 192, 0.25); transition: all 0.3s ease; }
        .btn-glow:hover { box-shadow: 0 0 30px rgba(81, 192, 192, 0.4); transform: translateY(-2px); }
        .bg-animations { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; pointer-events: none; z-index: -1; overflow: hidden; }
        .floating-element { position: absolute; animation: float-up linear infinite; opacity: 0.15; filter: drop-shadow(0 0 10px rgba(81,192,192,0.5)); }
        @keyframes float-up { 0% { transform: translateY(100vh) rotate(0deg) scale(0.8); opacity: 0; } 10% { opacity: 0.2; } 90% { opacity: 0.2; } 100% { transform: translateY(-20vh) rotate(360deg) scale(1.2); opacity: 0; } }
    </style>
</head>
<body class="min-h-screen text-slate-200 flex items-center justify-center p-4 relative">

    <div class="bg-animations">
        <div class="floating-element text-3xl" style="left: 15%; animation-duration: 18s; animation-delay: 2s;"></div>
        <div class="floating-element text-4xl text-[#51C0C0]" style="left: 45%; animation-duration: 22s; animation-delay: 5s;"><i class="fa-solid fa-shield-halved"></i></div>
        <div class="floating-element text-3xl" style="left: 80%; animation-duration: 20s; animation-delay: 0s;"></div>
        <div class="floating-element text-5xl" style="left: 65%; animation-duration: 25s; animation-delay: 10s;"></div>
        <div class="floating-element text-4xl text-[#51C0C0]" style="left: 25%; animation-duration: 19s; animation-delay: 12s;"><i class="fa-solid fa-network-wired"></i></div>
    </div>

    <div class="w-full max-w-5xl my-auto py-8 z-10 relative">
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-6 glass-panel p-6 rounded-2xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-[#51C0C0] rounded-full blur-[120px] opacity-10 pointer-events-none"></div>

            <div class="text-center md:text-left z-10 flex flex-col md:items-start items-center">
                <h1 class="text-3xl font-extrabold tracking-widest text-white flex flex-col md:flex-row items-center gap-2 drop-shadow-lg">
                    <span class="text-[#51C0C0] neon-text-glow">Radar Link Pro</span>
                </h1>
                <p class="text-[11px] tracking-[0.1em] text-[#8a9bb3] mt-2 font-mono uppercase bg-[#0d131f] border border-[#1e2738] px-3 py-1 rounded-full">
                    <i class="fa-solid fa-satellite-dish animate-pulse text-[#51C0C0] mr-1"></i> Links & Traffic
                </p>
            </div>
            
            <div class="flex flex-wrap gap-3 z-10 justify-center md:justify-end mt-4 md:mt-0">
                <a href="index.php" class="bg-[#0d131f] hover:bg-[#151e2e] border border-[#1e2738] hover:border-[#51C0C0] text-[#51C0C0] px-4 py-2.5 rounded-xl transition-all duration-300 text-[11px] font-bold uppercase tracking-wider flex items-center shadow-[0_0_10px_rgba(81,192,192,0.1)]">
                    <i class="fa-solid fa-cloud-arrow-up mr-2"></i> Upload
                </a>
                
                <a href="?delete_all=1" onclick="return confirm('⚠️ WARNING: Are you sure you want to delete ALL configurations and logs? This cannot be undone.')" class="bg-[#1a0f14] hover:bg-red-900/40 border border-red-900/50 hover:border-red-500/50 text-red-400 px-4 py-2.5 rounded-xl transition-all duration-300 text-[11px] font-bold uppercase tracking-wider flex items-center shadow-[0_0_10px_rgba(220,38,38,0.1)]">
                    <i class="fa-solid fa-dumpster-fire mr-2"></i> Delete All
                </a>

                <a href="?logout=1" class="bg-[#1a0f14] hover:bg-red-900/40 border border-red-900/50 hover:border-red-500/50 text-red-400 px-4 py-2.5 rounded-xl transition-all duration-300 text-[11px] font-bold uppercase tracking-wider flex items-center shadow-[0_0_10px_rgba(220,38,38,0.1)]">
                    <i class="fa-solid fa-power-off mr-2"></i> Logout
                </a>
            </div>
        </div>

        <div class="space-y-6">
            <?php foreach(array_reverse($db, true) as $id => $d): 
                $isExpired = time() > $d['expires'];
                $isLimited = $d['limit'] > 0 && $d['downloads'] >= $d['limit'];
                $statusHtml = ($isExpired || $isLimited) 
                    ? '<span class="bg-[#1a0f14] text-red-400 px-3 py-1.5 rounded-lg text-[10px] font-mono uppercase tracking-widest border border-red-900/50 shadow-[0_0_10px_rgba(220,38,38,0.1)]"><i class="fa-solid fa-ban mr-1"></i> Expired</span>' 
                    : '<span class="bg-[#51C0C0]/10 text-[#51C0C0] px-3 py-1.5 rounded-lg text-[10px] font-mono uppercase tracking-widest border border-[#51C0C0]/30 shadow-[0_0_10px_rgba(81,192,192,0.1)]"><i class="fa-solid fa-circle-check mr-1"></i> Active</span>';
            ?>
            <div class="glass-panel rounded-[1.5rem] p-5 relative overflow-hidden group hover:border-[#51C0C0]/50 transition-colors duration-300">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-[#1e2738] pb-5 mb-5">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-[#0d131f] border border-[#1e2738] flex items-center justify-center text-[#51C0C0]">
                            <i class="fa-regular fa-file-code text-xl"></i>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase tracking-widest text-[#425975] block mb-1 font-mono">Original: <?= htmlspecialchars($d['original_name']) ?></span>
                            <span class="font-mono text-[#51C0C0] font-bold text-lg neon-text-glow"><?= $id ?>.hc</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 w-full md:w-auto justify-between md:justify-end">
                        <div class="text-right font-mono bg-[#0d131f] px-4 py-2.5 rounded-xl border border-[#1e2738]">
                            <span class="text-[9px] uppercase tracking-widest text-[#425975] block mb-0.5">Downloads</span>
                            <span class="text-white font-bold text-sm"><?= $d['downloads'] ?> <span class="text-[#425975] mx-1">/</span> <span class="<?= $d['limit'] > 0 ? 'text-[#51C0C0]' : 'text-slate-500' ?>"><?= $d['limit'] > 0 ? $d['limit'] : '∞' ?></span></span>
                        </div>
                        <div><?= $statusHtml ?></div>
                        <a href="?delete=<?= $id ?>" onclick="return confirm('Delete this link permanently?')" class="bg-[#1a0f14] hover:bg-red-900/40 border border-red-900/50 text-red-400 w-10 h-10 flex items-center justify-center rounded-xl transition-all duration-300 shadow-[0_0_10px_rgba(220,38,38,0.1)]">
                            <i class="fa-solid fa-trash text-sm"></i>
                        </a>
                    </div>
                </div>

                <div>
                    <h4 class="text-[11px] font-mono uppercase tracking-widest text-[#8a9bb3] mb-4 flex items-center bg-[#0d131f] inline-block px-3 py-1.5 rounded-lg border border-[#1e2738]">
                        <i class="fa-solid fa-terminal mr-2 text-[#51C0C0] animate-pulse"></i> Live Request Feed
                    </h4>
                    <div class="bg-[#05080f] rounded-xl overflow-hidden border border-[#1e2738] overflow-x-auto custom-scroll shadow-inner">
                        <table class="w-full text-left whitespace-nowrap">
                            <tbody class="divide-y divide-[#1e2738]/50">
                                <?php if(!empty($d['logs'])): ?>
                                    <?php foreach(array_reverse($d['logs']) as $log): 
                                        $badge = ($log['status'] === 'Success') ? 'bg-[#51C0C0]/10 text-[#51C0C0] border-[#51C0C0]/30' : 'bg-[#1a0f14] text-red-400 border-red-900/50';
                                        $clientClass = (strpos($log['client'], 'HTTP Custom') !== false) ? 'text-[#51C0C0] font-bold' : 'text-slate-400';
                                        $icon = ($log['status'] === 'Success') ? '<i class="fa-solid fa-check text-[10px] mr-1"></i>' : '<i class="fa-solid fa-xmark text-[10px] mr-1"></i>';
                                    ?>
                                    <tr class="hover:bg-[#0d131f] transition duration-200">
                                        <td class="px-5 py-3.5 text-[#425975] font-mono text-[10px]"><i class="fa-regular fa-clock mr-1 opacity-50"></i> <?= date('Y-m-d H:i', $log['time']) ?></td>
                                        <td class="px-5 py-3.5 font-mono text-white text-[11px]"><i class="fa-solid fa-location-dot mr-1 text-[#425975]"></i> <?= htmlspecialchars($log['ip']) ?></td>
                                        <td class="px-5 py-3.5 text-[11px] <?= $clientClass ?>">
                                            <?= htmlspecialchars($log['client']) ?>
                                            <span class="text-[9px] text-[#425975] block truncate max-w-xs mt-1 font-mono bg-[#0a0f1c] px-2 py-0.5 rounded"><?= htmlspecialchars($log['ua']) ?></span>
                                        </td>
                                        <td class="px-5 py-3.5 text-right">
                                            <span class="px-2.5 py-1.5 rounded-lg font-mono uppercase tracking-widest border text-[9px] <?= $badge ?> inline-flex items-center">
                                                <?= $icon ?> <?= $log['status'] === 'Success' ? 'Fetched' : 'Blocked' ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td class="px-4 py-8 text-center text-[#425975] font-mono text-[11px] uppercase tracking-widest">
                                            <i class="fa-solid fa-ghost text-2xl block mb-2 opacity-30"></i>
                                            No request activity tracked yet.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if(empty($db)): ?>
                <div class="text-center py-20 border-2 border-dashed border-[#1e2738] rounded-[2rem] bg-gradient-to-b from-[#0d131f]/50 to-[#05080f]">
                    <div class="w-20 h-20 bg-[#0d131f] border border-[#1e2738] rounded-full flex items-center justify-center mx-auto mb-5 shadow-[0_0_15px_rgba(0,0,0,0.5)]">
                        <i class="fa-solid fa-satellite text-3xl text-[#1e2738]"></i>
                    </div>
                    <p class="text-[#425975] font-mono text-[12px] uppercase tracking-widest">Radar is empty. No configurations active.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
