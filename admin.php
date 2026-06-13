<?php
/**
 * NET-CLOUD-CONFIG - Enhanced Admin Panel (V2)
 * Secure Admin Panel & Log Radar with Advanced Effects
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

// Calculate Statistics
$totalLinks = count($db);
$totalDownloads = 0;
$totalRequests = 0;
$successfulRequests = 0;
$blockedRequests = 0;
$expiredLinks = 0;

foreach ($db as $d) {
    $totalDownloads += $d['downloads'];
    $totalRequests += count($d['logs']);
    foreach ($d['logs'] as $log) {
        if ($log['status'] === 'Success') $successfulRequests++;
        else $blockedRequests++;
    }
    if (time() > $d['expires'] || ($d['limit'] > 0 && $d['downloads'] >= $d['limit'])) {
        $expiredLinks++;
    }
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
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #05080f; overflow-x: hidden; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .glass-panel { background: rgba(10, 15, 28, 0.85); backdrop-filter: blur(12px); border: 1px solid #1e2738; box-shadow: 0 0 40px rgba(0, 0, 0, 0.8), inset 0 0 20px rgba(81, 192, 192, 0.05); }
        .custom-scroll::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #51C0C0; border-radius: 10px; }
        .neon-text-glow { text-shadow: 0 0 15px rgba(81, 192, 192, 0.6), 0 0 30px rgba(81, 192, 192, 0.2); }
        .btn-glow { box-shadow: 0 0 20px rgba(81, 192, 192, 0.25); transition: all 0.3s ease; }
        .btn-glow:hover { box-shadow: 0 0 30px rgba(81, 192, 192, 0.4); transform: translateY(-2px); }
        
        /* Enhanced Animations */
        .smooth-transition { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .glow-hover { transition: all 0.3s ease; }
        .glow-hover:hover { filter: drop-shadow(0 0 15px rgba(81, 192, 192, 0.4)); }
        .ripple { position: relative; overflow: hidden; }
        .ripple::after { content: ''; position: absolute; top: 50%; left: 50%; width: 0; height: 0; background: rgba(255, 255, 255, 0.5); border-radius: 50%; transform: translate(-50%, -50%); pointer-events: none; }
        .ripple:active::after { animation: ripple-effect 0.6s ease-out; }
        @keyframes ripple-effect { 0% { width: 0; height: 0; opacity: 1; } 100% { width: 300px; height: 300px; opacity: 0; } }
        
        /* Stat Card Animation */
        .stat-card { animation: stat-slide-in 0.5s ease-out; }
        @keyframes stat-slide-in { 0% { transform: translateY(20px); opacity: 0; } 100% { transform: translateY(0); opacity: 1; } }
        
        /* Pulse Animation */
        .pulse-animation { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        
        /* Table Row Hover */
        tbody tr { transition: all 0.2s ease; }
        tbody tr:hover { background-color: rgba(81, 192, 192, 0.05); }
        
        /* Status Badge Animation */
        .status-badge { animation: status-fade-in 0.4s ease; }
        @keyframes status-fade-in { 0% { opacity: 0; transform: scale(0.8); } 100% { opacity: 1; transform: scale(1); } }
        
        /* Floating Background Elements */
        .floating-bg { position: fixed; pointer-events: none; z-index: -1; }
        .glow-circle { animation: glow-pulse 4s ease-in-out infinite; }
        @keyframes glow-pulse { 0%, 100% { opacity: 0.1; } 50% { opacity: 0.3; } }
        
        /* Scroll Animation */
        .scroll-indicator { animation: scroll-bounce 2s infinite; }
        @keyframes scroll-bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(10px); } }
    </style>
</head>
<body class="min-h-screen text-slate-200 flex items-center justify-center p-4 relative">

    <!-- Floating Background Elements -->
    <div class="floating-bg top-10 right-10 w-96 h-96 bg-[#51C0C0] rounded-full blur-[150px] opacity-10 glow-circle"></div>
    <div class="floating-bg bottom-20 left-10 w-80 h-80 bg-[#51C0C0] rounded-full blur-[120px] opacity-5 glow-circle" style="animation-delay: 2s;"></div>

    <div class="w-full max-w-6xl my-auto py-8 z-10 relative">
        
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-6 glass-panel p-6 rounded-2xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-[#51C0C0] rounded-full blur-[120px] opacity-10 pointer-events-none"></div>

            <div class="text-center md:text-left z-10 flex flex-col md:items-start items-center">
                <h1 class="text-3xl font-extrabold tracking-widest text-white flex flex-col md:flex-row items-center gap-2 drop-shadow-lg smooth-transition">
                    <span class="text-[#51C0C0] neon-text-glow">Radar Link Pro</span>
                </h1>
                <p class="text-[11px] tracking-[0.1em] text-[#8a9bb3] mt-2 font-mono uppercase bg-[#0d131f] border border-[#1e2738] px-3 py-1 rounded-full smooth-transition">
                    <i class="fa-solid fa-satellite-dish text-[#51C0C0] mr-1"></i> Links & Traffic Analytics
                </p>
            </div>
            
            <div class="flex flex-row gap-4 z-10 justify-center md:justify-end mt-6 md:mt-0 flex-wrap">
                <a href="index.php" title="Back to Upload" class="bg-[#0d131f] hover:bg-[#151e2e] border border-[#1e2738] hover:border-[#51C0C0] text-[#51C0C0] w-12 h-12 flex items-center justify-center rounded-xl transition-all duration-300 shadow-[0_0_10px_rgba(81,192,192,0.1)] hover:shadow-[0_0_15px_rgba(81,192,192,0.3)] ripple smooth-transition glow-hover">
                    <i class="fa-solid fa-arrow-left text-xl"></i>
                </a>

                <a href="index.php" title="Upload Files" class="bg-[#0d131f] hover:bg-[#151e2e] border border-[#1e2738] hover:border-[#51C0C0] text-[#51C0C0] w-12 h-12 flex items-center justify-center rounded-xl transition-all duration-300 shadow-[0_0_10px_rgba(81,192,192,0.1)] hover:shadow-[0_0_15px_rgba(81,192,192,0.3)] ripple smooth-transition glow-hover">
                    <i class="fa-solid fa-cloud-arrow-up text-xl"></i>
                </a>
                
                <a href="?delete_all=1" title="Delete All" onclick="return confirm('⚠️ WARNING: Are you sure you want to delete ALL configurations and logs? This cannot be undone.')" class="bg-[#1a0f14] hover:bg-red-900/40 border border-red-900/50 hover:border-red-500/50 text-red-400 w-12 h-12 flex items-center justify-center rounded-xl transition-all duration-300 shadow-[0_0_10px_rgba(220,38,38,0.1)] hover:shadow-[0_0_15px_rgba(220,38,38,0.3)] ripple smooth-transition">
                    <i class="fa-solid fa-dumpster-fire text-xl"></i>
                </a>

                <a href="?logout=1" title="Logout" class="bg-[#1a0f14] hover:bg-red-900/40 border border-red-900/50 hover:border-red-500/50 text-red-400 w-12 h-12 flex items-center justify-center rounded-xl transition-all duration-300 shadow-[0_0_10px_rgba(220,38,38,0.1)] hover:shadow-[0_0_15px_rgba(220,38,38,0.3)] ripple smooth-transition">
                    <i class="fa-solid fa-power-off text-xl"></i>
                </a>
            </div>
        </div>

        <!-- Statistics Section -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <div class="glass-panel rounded-xl p-4 stat-card smooth-transition glow-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[#8a9bb3] text-xs font-mono uppercase">Total Links</p>
                        <p class="text-[#51C0C0] text-2xl font-bold mt-1"><?= $totalLinks ?></p>
                    </div>
                    <i class="fa-solid fa-link text-[#51C0C0] text-3xl opacity-30"></i>
                </div>
            </div>

            <div class="glass-panel rounded-xl p-4 stat-card smooth-transition glow-hover" style="animation-delay: 0.1s;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[#8a9bb3] text-xs font-mono uppercase">Downloads</p>
                        <p class="text-[#51C0C0] text-2xl font-bold mt-1"><?= $totalDownloads ?></p>
                    </div>
                    <i class="fa-solid fa-download text-[#51C0C0] text-3xl opacity-30"></i>
                </div>
            </div>

            <div class="glass-panel rounded-xl p-4 stat-card smooth-transition glow-hover" style="animation-delay: 0.2s;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[#8a9bb3] text-xs font-mono uppercase">Requests</p>
                        <p class="text-[#51C0C0] text-2xl font-bold mt-1"><?= $totalRequests ?></p>
                    </div>
                    <i class="fa-solid fa-radar text-[#51C0C0] text-3xl opacity-30"></i>
                </div>
            </div>

            <div class="glass-panel rounded-xl p-4 stat-card smooth-transition glow-hover" style="animation-delay: 0.3s;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[#8a9bb3] text-xs font-mono uppercase">Success</p>
                        <p class="text-green-400 text-2xl font-bold mt-1"><?= $successfulRequests ?></p>
                    </div>
                    <i class="fa-solid fa-check-circle text-green-400 text-3xl opacity-30"></i>
                </div>
            </div>

            <div class="glass-panel rounded-xl p-4 stat-card smooth-transition glow-hover" style="animation-delay: 0.4s;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[#8a9bb3] text-xs font-mono uppercase">Blocked</p>
                        <p class="text-red-400 text-2xl font-bold mt-1"><?= $blockedRequests ?></p>
                    </div>
                    <i class="fa-solid fa-ban text-red-400 text-3xl opacity-30"></i>
                </div>
            </div>
        </div>

        <!-- Links List -->
        <div class="space-y-6">
            <?php if(!empty($db)): ?>
                <?php foreach(array_reverse($db, true) as $id => $d): 
                    $isExpired = time() > $d['expires'];
                    $isLimited = $d['limit'] > 0 && $d['downloads'] >= $d['limit'];
                    $statusHtml = ($isExpired || $isLimited) 
                        ? '<span class="bg-[#1a0f14] text-red-400 px-3 py-1.5 rounded-lg text-[10px] font-mono uppercase tracking-widest border border-red-900/50 shadow-[0_0_10px_rgba(220,38,38,0.1)] status-badge"><i class="fa-solid fa-ban mr-1"></i> Expired</span>' 
                        : '<span class="bg-[#51C0C0]/10 text-[#51C0C0] px-3 py-1.5 rounded-lg text-[10px] font-mono uppercase tracking-widest border border-[#51C0C0]/30 shadow-[0_0_10px_rgba(81,192,192,0.1)] status-badge"><i class="fa-solid fa-circle-check mr-1"></i> Active</span>';
                    
                    $expiresIn = $d['expires'] - time();
                    $expiresInHours = floor($expiresIn / 3600);
                    $expiresInMins = floor(($expiresIn % 3600) / 60);
                ?>
                <div class="glass-panel rounded-[1.5rem] p-5 relative overflow-hidden group hover:border-[#51C0C0]/50 transition-colors duration-300 smooth-transition">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-[#1e2738] pb-5 mb-5">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-[#0d131f] border border-[#1e2738] flex items-center justify-center text-[#51C0C0] glow-hover">
                                <i class="fa-regular fa-file-code text-xl"></i>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase tracking-widest text-[#425975] block mb-1 font-mono">Original: <?= htmlspecialchars($d['original_name']) ?></span>
                                <span class="font-mono text-[#51C0C0] font-bold text-lg neon-text-glow"><?= $id ?>.hc</span>
                            </div>
                        </div>
                        <!-- <div class="flex items-center gap-4 w-full md:w-auto justify-between md:justify-end flex-wrap"> -->
                        <div class="flex items-center gap-3 w-full md:w-auto justify-center md:justify-end flex-wrap md:flex-nowrap">
                            <!-- <div class="text-right font-mono bg-[#0d131f] px-4 py-2.5 rounded-xl border border-[#1e2738] smooth-transition glow-hover"> -->
                            <div class="flex-1 md:flex-none text-center md:text-right font-mono bg-[#0d131f] px-3 py-2 rounded-xl border border-[#1e2738] smooth-transition glow-hover min-w-[100px]">
                                <span class="text-[9px] uppercase tracking-widest text-[#425975] block mb-0.5">Downloads</span>
                                <span class="text-white font-bold text-sm"><?= $d['downloads'] ?> <span class="text-[#425975] mx-1">/</span> <span class="<?= $d['limit'] > 0 ? 'text-[#51C0C0]' : 'text-slate-500' ?>"><?= $d['limit'] > 0 ? $d['limit'] : '∞' ?></span></span>
                            </div>
                            <!-- <div class="text-right font-mono bg-[#0d131f] px-4 py-2.5 rounded-xl border border-[#1e2738] smooth-transition glow-hover"> -->
                            <div class="flex-1 md:flex-none text-center md:text-right font-mono bg-[#0d131f] px-3 py-2 rounded-xl border border-[#1e2738] smooth-transition glow-hover min-w-[100px]">
                                <span class="text-[9px] uppercase tracking-widest text-[#425975] block mb-0.5">Expires In</span>
                                <span class="text-white font-bold text-sm"><?= $expiresInHours ?>h <?= $expiresInMins ?>m</span>
                            </div>
                            <!-- <div><?= $statusHtml ?></div> -->
                            <div class="w-full md:w-auto flex justify-center md:block"><?= $statusHtml ?></div>
                            
                            <div class="flex items-center gap-2 mt-2 md:mt-0">
                                <!-- <RADAR-LINK-PRO-NEW-CODE> -->
                                <button onclick="viewLinkDetails('<?= $id ?>', '<?= htmlspecialchars($d['original_name']) ?>')" class="bg-[#0d131f] hover:bg-[#51C0C0]/20 border border-[#51C0C0]/30 text-[#51C0C0] w-10 h-10 flex items-center justify-center rounded-xl transition-all duration-300 shadow-[0_0_10px_rgba(81,192,192,0.1)] ripple smooth-transition" title="Search/View Details">
                                    <i class="fa-solid fa-magnifying-glass text-sm"></i>
                                </button>
                                
                                <a href="?delete=<?= $id ?>" onclick="return confirm('Delete this link permanently?')" class="bg-[#1a0f14] hover:bg-red-900/40 border border-red-900/50 text-red-400 w-10 h-10 flex items-center justify-center rounded-xl transition-all duration-300 shadow-[0_0_10px_rgba(220,38,38,0.1)] ripple smooth-transition" title="Delete Link">
                                    <i class="fa-solid fa-trash text-sm"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-[11px] font-mono uppercase tracking-widest text-[#8a9bb3] mb-4 flex items-center bg-[#0d131f] inline-block px-3 py-1.5 rounded-lg border border-[#1e2738]">
                            <i class="fa-solid fa-terminal mr-2 text-[#51C0C0]"></i> Live Request Feed
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
                                        <tr class="hover:bg-[#0d131f] transition duration-200 smooth-transition">
                                            <td class="px-5 py-3.5 text-[#425975] font-mono text-[10px]"><i class="fa-regular fa-clock mr-1 opacity-50"></i> <?= date('Y-m-d H:i', $log['time']) ?></td>
                                            <td class="px-5 py-3.5 font-mono text-white text-[11px]"><i class="fa-solid fa-location-dot mr-1 text-[#425975]"></i> <?= htmlspecialchars($log['ip']) ?></td>
                                            <td class="px-5 py-3.5 text-[11px] <?= $clientClass ?>">
                                                <?= htmlspecialchars($log['client']) ?>
                                                <span class="text-[9px] text-[#425975] block truncate max-w-xs mt-1 font-mono bg-[#0a0f1c] px-2 py-0.5 rounded"><?= htmlspecialchars($log['ua']) ?></span>
                                            </td>
                                            <td class="px-5 py-3.5 text-right">
                                                <span class="px-2.5 py-1.5 rounded-lg font-mono uppercase tracking-widest border text-[9px] <?= $badge ?> inline-flex items-center status-badge">
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
            <?php else: ?>
                <div class="text-center py-20 border-2 border-dashed border-[#1e2738] rounded-[2rem] bg-gradient-to-b from-[#0d131f]/50 to-[#05080f] smooth-transition">
                    <div class="w-20 h-20 bg-[#0d131f] border border-[#1e2738] rounded-full flex items-center justify-center mx-auto mb-5 shadow-[0_0_15px_rgba(0,0,0,0.5)]">
                        <i class="fa-solid fa-satellite text-3xl text-[#1e2738]"></i>
                    </div>
                    <p class="text-[#425975] font-mono text-[12px] uppercase tracking-widest">No links created yet. Start by uploading files!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        /* <RADAR-LINK-PRO-NEW-CODE> */
        function viewLinkDetails(id, name) {
            console.log("Viewing details for Link ID: " + id + " (File: " + name + ")");
            alert("Radar Link Pro - Link Details\n\nID: " + id + "\nFile: " + name + "\n\n(Logic to be expanded later)");
        }

        // Auto-refresh statistics every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                location.reload();
            }
            if (e.ctrlKey && e.key === 'h') {
                e.preventDefault();
                window.location.href = 'index.php';
            }
        });

        // Add smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';
    </script>
</body>
</html>
