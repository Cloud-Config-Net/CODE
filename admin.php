<?php
/**
 * CLOUD-CONFIG 
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
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NETCLOUD | Radar Space</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { -webkit-tap-highlight-color: transparent; } 
        body { background-color: #030a14; overflow-x: hidden; font-family: 'Oswald', sans-serif; letter-spacing: 0.5px; text-transform: uppercase; color: #cbd5e1; }
        input, button, select, textarea { font-family: 'Oswald', sans-serif; letter-spacing: 0.5px; text-transform: uppercase; }
        
        /* ------------------ التصميم الزجاجي الموحد (أزرق) ------------------ */
        .glass-panel { 
            background: rgba(10, 15, 30, 0.65); 
            backdrop-filter: blur(16px); 
            border: 1px solid rgba(56, 189, 248, 0.2); 
            box-shadow: 0 15px 35px rgba(0,0,0,0.5), inset 0 0 20px rgba(56, 189, 248, 0.05); 
        }
        .blue-glow-text { text-shadow: 0 0 10px rgba(56, 189, 248, 0.6), 0 0 20px rgba(56, 189, 248, 0.3); }

        .custom-scroll::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #38bdf8; border-radius: 10px; }
        .smooth-transition { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .glow-hover { transition: all 0.3s ease; }
        .glow-hover:hover { filter: drop-shadow(0 0 15px rgba(56, 189, 248, 0.4)); }
        tbody tr { transition: all 0.2s ease; }
        tbody tr:hover { background-color: rgba(56, 189, 248, 0.05); }

        /* حركات التصميم الأزرق */
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }
        .bg-orb { position: fixed; border-radius: 50%; filter: blur(150px); z-index: -1; animation: float 6s ease-in-out infinite alternate; pointer-events: none; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 relative">

    <div class="bg-orb top-[-10%] left-[-10%] w-[500px] h-[500px] bg-blue-600 opacity-20"></div>
    <div class="bg-orb bottom-[-10%] right-[-10%] w-[450px] h-[450px] bg-cyan-400 opacity-20" style="animation-delay: -3s;"></div>

    <div class="w-full max-w-6xl my-auto py-8 z-10 relative">
        
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-6 glass-panel p-6 rounded-2xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-cyan-500 rounded-full blur-[120px] opacity-10 pointer-events-none"></div>

            <div class="text-center md:text-left z-10 flex flex-col md:items-start items-center">
                <h1 class="text-4xl font-bold flex flex-col md:flex-row items-center gap-2 drop-shadow-lg smooth-transition">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-300 blue-glow-text">RADAR LINK PRO</span>
                </h1>
                <p class="text-[13px] tracking-widest text-blue-300/70 mt-2 bg-[#080f1e]/80 border border-blue-900/40 px-3 py-1 rounded-full smooth-transition font-bold">
                    <i class="fa-solid fa-satellite-dish text-cyan-400 mr-1"></i> LINKS & TRAFFIC ANALYTICS
                </p>
            </div>
            
            <div class="flex flex-row gap-4 z-10 justify-center md:justify-end mt-6 md:mt-0 flex-wrap items-center">
                <a href="index.php" title="Back to Upload" class="bg-[#080f1e]/80 hover:bg-[#0a1428] border border-blue-900/40 hover:border-cyan-400 text-cyan-400 w-12 h-12 flex items-center justify-center rounded-xl transition-all duration-300 shadow-[0_0_15px_rgba(56,189,248,0.1)] smooth-transition glow-hover">
                    <i class="fa-solid fa-arrow-left text-xl"></i>
                </a>

                <a href="index.php" title="Upload Files" class="bg-[#080f1e]/80 hover:bg-[#0a1428] border border-blue-900/40 hover:border-cyan-400 text-cyan-400 w-12 h-12 flex items-center justify-center rounded-xl transition-all duration-300 shadow-[0_0_15px_rgba(56,189,248,0.1)] smooth-transition glow-hover">
                    <i class="fa-solid fa-cloud-arrow-up text-xl"></i>
                </a>
                
                <a href="?delete_all=1" title="Delete All" onclick="return confirm('Delete')" class="bg-[#1a0f14]/80 hover:bg-red-900/40 border border-red-900/40 hover:border-red-500/80 text-red-500 w-12 h-12 flex items-center justify-center rounded-xl transition-all duration-300 shadow-[0_0_15px_rgba(239,68,68,0.1)] smooth-transition">
                    <i class="fa-solid fa-dumpster-fire text-xl"></i>
                </a>

                <div class="relative group">
                    <input type="text" id="searchInput" oninput="searchLinks()" placeholder="PASTE LINK OR ID..." class="bg-[#080f1e]/80 hover:bg-[#0a1428] border border-blue-900/40 focus:border-cyan-400 text-cyan-400 h-12 px-4 pr-10 rounded-xl transition-all duration-300 shadow-[0_0_15px_rgba(56,189,248,0.1)] focus:shadow-[0_0_20px_rgba(56,189,248,0.3)] text-[14px] font-bold outline-none w-48 md:w-64 placeholder-blue-800/60">
                    <i class="fa-solid fa-magnifying-glass absolute right-4 top-1/2 -translate-y-1/2 text-cyan-400 text-lg pointer-events-none"></i>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <div class="glass-panel rounded-xl p-4 smooth-transition glow-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-300/70 text-[13px] font-bold">TOTAL LINKS</p>
                        <p class="text-cyan-400 text-3xl font-bold mt-1"><?= $totalLinks ?></p>
                    </div>
                    <i class="fa-solid fa-link text-cyan-400 text-3xl opacity-30"></i>
                </div>
            </div>

            <div class="glass-panel rounded-xl p-4 smooth-transition glow-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-300/70 text-[13px] font-bold">DOWNLOADS</p>
                        <p class="text-cyan-400 text-3xl font-bold mt-1"><?= $totalDownloads ?></p>
                    </div>
                    <i class="fa-solid fa-download text-cyan-400 text-3xl opacity-30"></i>
                </div>
            </div>

            <div class="glass-panel rounded-xl p-4 smooth-transition glow-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-300/70 text-[13px] font-bold">REQUESTS</p>
                        <p class="text-cyan-400 text-3xl font-bold mt-1"><?= $totalRequests ?></p>
                    </div>
                    <i class="fa-solid fa-radar text-cyan-400 text-3xl opacity-30"></i>
                </div>
            </div>

            <div class="glass-panel rounded-xl p-4 smooth-transition glow-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-300/70 text-[13px] font-bold">SUCCESS</p>
                        <p class="text-[#22c55e] text-3xl font-bold mt-1"><?= $successfulRequests ?></p>
                    </div>
                    <i class="fa-solid fa-check-circle text-[#22c55e] text-3xl opacity-30"></i>
                </div>
            </div>

            <div class="glass-panel rounded-xl p-4 smooth-transition glow-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-300/70 text-[13px] font-bold">BLOCKED</p>
                        <p class="text-red-500 text-3xl font-bold mt-1"><?= $blockedRequests ?></p>
                    </div>
                    <i class="fa-solid fa-ban text-red-500 text-3xl opacity-30"></i>
                </div>
            </div>
        </div>

        <div class="space-y-6" id="linksContainer">
            <?php if(!empty($db)): ?>
                <?php foreach(array_reverse($db, true) as $id => $d): 
                    $isExpired = time() > $d['expires'];
                    $isLimited = $d['limit'] > 0 && $d['downloads'] >= $d['limit'];
                    $statusHtml = ($isExpired || $isLimited) 
                        ? '<span class="bg-[#1a0f14]/80 text-red-500 px-3 py-1.5 rounded-lg text-[12px] font-bold tracking-widest border border-red-900/50 shadow-[0_0_10px_rgba(239,68,68,0.1)] status-badge inline-flex items-center gap-1.5"><i class="fa-solid fa-ban"></i> EXPIRED</span>' 
                        : '<span class="bg-cyan-500/10 text-cyan-400 px-3 py-1.5 rounded-lg text-[12px] font-bold tracking-widest border border-cyan-400/30 shadow-[0_0_10px_rgba(56,189,248,0.1)] status-badge inline-flex items-center gap-1.5"><i class="fa-solid fa-circle-check"></i> ACTIVE</span>';
                    
                    $expiresIn = $d['expires'] - time();
                    $expiresInHours = floor($expiresIn / 3600);
                    $expiresInMins = floor(($expiresIn % 3600) / 60);
                ?>
                <div class="glass-panel rounded-[1.5rem] p-5 relative overflow-hidden group hover:border-cyan-500/40 transition-colors duration-300 smooth-transition config-card" data-link-id="<?= htmlspecialchars($id) ?>" data-original-name="<?= strtolower(htmlspecialchars($d['original_name'])) ?>">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-blue-900/40 pb-5 mb-5">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-xl bg-[#080f1e]/80 border border-blue-900/40 flex items-center justify-center text-cyan-400 glow-hover">
                                <i class="fa-regular fa-file-code text-2xl"></i>
                            </div>
                            <div>
                                <span class="text-[12px] font-bold tracking-widest text-blue-300/70 block mb-1">ORIGINAL: <?= htmlspecialchars($d['original_name']) ?></span>
                                <span class="font-bold text-cyan-400 text-xl blue-glow-text"><?= htmlspecialchars($id) ?>.HC</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 w-full md:w-auto justify-between md:justify-end flex-wrap">
                            <div class="text-right bg-[#080f1e]/80 px-4 py-2.5 rounded-xl border border-blue-900/40 smooth-transition glow-hover">
                                <span class="text-[11px] font-bold tracking-widest text-blue-300/70 block mb-0.5">DOWNLOADS</span>
                                <span class="text-white font-bold text-[16px]"><?= $d['downloads'] ?> <span class="text-blue-900/60 mx-1">/</span> <span class="<?= $d['limit'] > 0 ? 'text-cyan-400' : 'text-slate-500' ?>"><?= $d['limit'] > 0 ? $d['limit'] : '∞' ?></span></span>
                            </div>
                            <div class="text-right bg-[#080f1e]/80 px-4 py-2.5 rounded-xl border border-blue-900/40 smooth-transition glow-hover">
                                <span class="text-[11px] font-bold tracking-widest text-blue-300/70 block mb-0.5">EXPIRES IN</span>
                                <span class="text-white font-bold text-[16px]"><?= $expiresInHours ?>H <?= $expiresInMins ?>M</span>
                            </div>
                            <div><?= $statusHtml ?></div>
                            <a href="?delete=<?= $id ?>" onclick="return confirm('DELETE THIS LINK PERMANENTLY?')" class="bg-[#1a0f14]/80 hover:bg-red-900/40 border border-red-900/40 text-red-500 w-12 h-12 flex items-center justify-center rounded-xl transition-all duration-300 shadow-[0_0_10px_rgba(239,68,68,0.1)] smooth-transition hover:border-red-500/80">
                                <i class="fa-solid fa-trash text-[16px]"></i>
                            </a>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-[13px] font-bold tracking-widest text-blue-300/70 mb-4 flex items-center bg-[#080f1e]/80 inline-block px-4 py-2 rounded-lg border border-blue-900/40">
                            <i class="fa-solid fa-terminal mr-2 text-cyan-400"></i> LIVE REQUEST FEED
                        </h4>
                        <div class="bg-[#030a14] rounded-xl overflow-hidden border border-blue-900/40 overflow-x-auto custom-scroll shadow-inner">
                            <table class="w-full text-left whitespace-nowrap">
                                <tbody class="divide-y divide-blue-900/30">
                                    <?php if(!empty($d['logs'])): ?>
                                        <?php foreach(array_reverse($d['logs']) as $log): 
                                            $badge = ($log['status'] === 'Success') ? 'bg-[#22c55e]/10 text-[#22c55e] border-[#22c55e]/30' : 'bg-red-500/10 text-red-500 border-red-500/30';
                                            $clientClass = (strpos($log['client'], 'HTTP Custom') !== false) ? 'text-cyan-400 font-bold' : 'text-slate-400';
                                            $icon = ($log['status'] === 'Success') ? '<i class="fa-solid fa-check text-[14px]"></i>' : '<i class="fa-solid fa-xmark text-[14px]"></i>';
                                            $statusText = ($log['status'] === 'Success') ? 'OPEN' : 'BLOCKED';
                                        ?>
                                        <tr class="hover:bg-[#080f1e]/80 transition duration-200 smooth-transition">
                                            <td class="px-5 py-4 align-middle">
                                                <div class="flex items-center gap-2 text-blue-300/70 text-[13px] font-bold">
                                                    <i class="fa-regular fa-clock opacity-50"></i>
                                                    <span><?= date('Y-M-D H:i', $log['time']) ?></span>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 align-middle">
                                                <div class="flex items-center gap-2 text-white text-[14px] font-bold">
                                                    <i class="fa-solid fa-location-dot text-blue-300/50"></i>
                                                    <span><?= htmlspecialchars($log['ip']) ?></span>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 text-[14px] font-bold <?= $clientClass ?> align-middle">
                                                <?= htmlspecialchars($log['client']) ?>
                                                <span class="text-[11px] text-blue-300/70 block truncate max-w-xs mt-1 bg-[#080f1e] px-2 py-0.5 rounded border border-blue-900/30 font-bold w-fit"><?= htmlspecialchars($log['ua']) ?></span>
                                            </td>
                                            <td class="px-5 py-4 text-right align-middle">
                                                <div class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg font-bold tracking-widest border text-[12px] <?= $badge ?> w-[100px] status-badge">
                                                    <?= $icon ?>
                                                    <span><?= $statusText ?></span>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td class="px-4 py-8 text-center text-blue-300/50 font-bold text-[13px] tracking-widest">
                                                <i class="fa-solid fa-ghost text-3xl block mb-2 opacity-30"></i>
                                                NO REQUEST ACTIVITY TRACKED YET.
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
                <div class="text-center py-20 border-2 border-dashed border-blue-900/50 rounded-[2rem] bg-gradient-to-b from-[#080f1e]/80 to-[#030a14] smooth-transition">
                    <div class="w-20 h-20 bg-[#080f1e] border border-blue-900/40 rounded-full flex items-center justify-center mx-auto mb-5 shadow-[0_0_15px_rgba(0,0,0,0.5)]">
                        <i class="fa-solid fa-satellite text-4xl text-cyan-400"></i>
                    </div>
                    <p class="text-blue-300/70 text-[15px] font-bold tracking-widest"></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function searchLinks() {
            let input = document.getElementById('searchInput').value.trim();
            let match = input.match(/\/([a-zA-Z0-9_-]+)\.hc/);
            let searchTerm = match ? match[1] : input.replace('.hc', '');
            searchTerm = searchTerm.toLowerCase();

            let cards = document.querySelectorAll('.config-card');
            cards.forEach(card => {
                let id = card.getAttribute('data-link-id').toLowerCase();
                let originalName = card.getAttribute('data-original-name').toLowerCase();
                
                if (id.includes(searchTerm) || originalName.includes(searchTerm) || searchTerm === '') {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        setInterval(() => {
            let searchVal = document.getElementById('searchInput');
            if (searchVal && searchVal.value.trim() === '') {
                location.reload();
            }
        }, 30000);

        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                location.reload();
            }
            if (e.ctrlKey && e.key === 'h') {
                e.preventDefault();
                window.location.href = 'index.php';
            }
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                document.getElementById('searchInput').focus();
            }
        });

        document.documentElement.style.scrollBehavior = 'smooth';
    </script>
</body>
</html>
