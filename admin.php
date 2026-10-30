<?php
/**
 * NETCLOUD - Secure Admin Panel & Smart Truth Radar (PRO EDITION)
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
        file_put_contents($dbFile, json_encode($db), LOCK_EX); 
    }
    header("Location: admin.php"); exit;
}

// Re-Open / Reset Link Action (NEW: Fixes client issues instantly without re-uploading)
if (isset($_GET['reopen'])) {
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['reopen']);
    if (isset($db[$id])) {
        $db[$id]['downloads'] = 0;
        $db[$id]['expires'] = time() + 300; // Extend 5 minutes
        $db[$id]['locked_ua'] = null;
        $db[$id]['locked_device'] = null;
        $db[$id]['locked_ip'] = null;
        $db[$id]['first_success_time'] = 0;
        $db[$id]['logs'][] = [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Admin',
            'ua' => 'Admin Panel',
            'device' => 'ADMINISTRATOR',
            'client' => 'Admin Action',
            'time' => time(),
            'status' => 'Success',
            'reason' => 'MANUAL RE-OPEN'
        ];
        file_put_contents($dbFile, json_encode($db), LOCK_EX);
    }
    header("Location: admin.php"); exit;
}

// Delete ALL items action
if (isset($_GET['delete_all'])) {
    foreach ($db as $id => $d) {
        @unlink($d['real_path']); 
    }
    $db = []; 
    file_put_contents($dbFile, json_encode($db), LOCK_EX); 
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NETCLOUD | RADAR SPACE PRO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { -webkit-tap-highlight-color: transparent; } 
        body { background-color: #030a14; overflow-x: hidden; font-family: 'Oswald', sans-serif; letter-spacing: 0.5px; text-transform: uppercase; color: #cbd5e1; }
        input, button, select, textarea { font-family: 'Oswald', sans-serif; letter-spacing: 0.5px; text-transform: uppercase; }
        
        .glass-panel { 
            background: rgba(10, 15, 30, 0.75); 
            backdrop-filter: blur(16px); 
            border: 1px solid rgba(56, 189, 248, 0.2); 
            box-shadow: 0 15px 35px rgba(0,0,0,0.5), inset 0 0 20px rgba(56, 189, 248, 0.05); 
        }
        .blue-glow-text { text-shadow: 0 0 10px rgba(56, 189, 248, 0.6), 0 0 20px rgba(56, 189, 248, 0.3); }

        .custom-scroll::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #38bdf8; border-radius: 10px; }
        .smooth-transition { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .glow-hover:hover { filter: drop-shadow(0 0 15px rgba(56, 189, 248, 0.4)); }
        tbody tr { transition: all 0.2s ease; }
        tbody tr:hover { background-color: rgba(56, 189, 248, 0.05); }

        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }
        .bg-orb { position: fixed; border-radius: 50%; filter: blur(150px); z-index: -1; animation: float 6s ease-in-out infinite alternate; pointer-events: none; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-3 sm:p-6 relative">

    <div class="bg-orb top-[-10%] left-[-10%] w-[500px] h-[500px] bg-blue-600 opacity-20"></div>
    <div class="bg-orb bottom-[-10%] right-[-10%] w-[450px] h-[450px] bg-cyan-400 opacity-20" style="animation-delay: -3s;"></div>

    <div class="w-full max-w-6xl my-auto py-6 z-10 relative">
        
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-6 glass-panel p-6 rounded-2xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-cyan-500 rounded-full blur-[120px] opacity-10 pointer-events-none"></div>

            <div class="text-center md:text-left z-10 flex flex-col md:items-start items-center">
                <h1 class="text-3xl sm:text-4xl font-bold flex flex-col md:flex-row items-center gap-2 drop-shadow-lg smooth-transition">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-300 blue-glow-text">RADAR LINK PRO</span>
                </h1>
                <p class="text-[12px] sm:text-[13px] tracking-widest text-blue-300/75 mt-2 bg-[#080f1e]/90 border border-blue-900/40 px-3.5 py-1.5 rounded-full font-bold">
                    <i class="fa-solid fa-satellite-dish text-cyan-400 mr-1.5"></i> SMART TRUTH & TRAFFIC ANALYTICS
                </p>
            </div>
            
            <div class="flex flex-row gap-3 z-10 justify-center md:justify-end flex-wrap items-center">
                <a href="index.php" title="Upload Files" class="bg-[#080f1e]/80 hover:bg-[#0a1428] border border-blue-900/40 hover:border-cyan-400 text-cyan-400 w-12 h-12 flex items-center justify-center rounded-xl transition-all shadow-lg glow-hover">
                    <i class="fa-solid fa-cloud-arrow-up text-xl"></i>
                </a>
                
                <a href="?delete_all=1" title="Delete All Links" onclick="return confirm('WARNING: DELETE ALL LINKS AND LOGS?')" class="bg-[#1a0f14]/80 hover:bg-red-900/40 border border-red-900/40 hover:border-red-500 text-red-500 w-12 h-12 flex items-center justify-center rounded-xl transition-all shadow-lg">
                    <i class="fa-solid fa-dumpster-fire text-xl"></i>
                </a>

                <div class="relative group">
                    <input type="text" id="searchInput" oninput="searchLinks()" placeholder="SEARCH LINK OR FILE..." class="bg-[#080f1e]/90 border border-blue-900/40 focus:border-cyan-400 text-cyan-400 h-12 px-4 pr-10 rounded-xl shadow-lg text-[13px] font-bold outline-none w-44 sm:w-60 placeholder-blue-800/60">
                    <i class="fa-solid fa-magnifying-glass absolute right-4 top-1/2 -translate-y-1/2 text-cyan-400 text-base pointer-events-none"></i>
                </div>
            </div>
        </div>

        <!-- Statistics Grid -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 sm:gap-4 mb-8">
            <div class="glass-panel rounded-xl p-4 glow-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-300/70 text-[12px] font-bold">TOTAL LINKS</p>
                        <p class="text-cyan-400 text-2xl sm:text-3xl font-bold mt-1"><?= $totalLinks ?></p>
                    </div>
                    <i class="fa-solid fa-link text-cyan-400 text-2xl sm:text-3xl opacity-30"></i>
                </div>
            </div>

            <div class="glass-panel rounded-xl p-4 glow-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-300/70 text-[12px] font-bold">DOWNLOADS</p>
                        <p class="text-cyan-400 text-2xl sm:text-3xl font-bold mt-1"><?= $totalDownloads ?></p>
                    </div>
                    <i class="fa-solid fa-download text-cyan-400 text-2xl sm:text-3xl opacity-30"></i>
                </div>
            </div>

            <div class="glass-panel rounded-xl p-4 glow-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-300/70 text-[12px] font-bold">REQUESTS</p>
                        <p class="text-cyan-400 text-2xl sm:text-3xl font-bold mt-1"><?= $totalRequests ?></p>
                    </div>
                    <i class="fa-solid fa-radar text-cyan-400 text-2xl sm:text-3xl opacity-30"></i>
                </div>
            </div>

            <div class="glass-panel rounded-xl p-4 glow-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-300/70 text-[12px] font-bold">SUCCESS</p>
                        <p class="text-[#22c55e] text-2xl sm:text-3xl font-bold mt-1"><?= $successfulRequests ?></p>
                    </div>
                    <i class="fa-solid fa-check-circle text-[#22c55e] text-2xl sm:text-3xl opacity-30"></i>
                </div>
            </div>

            <div class="glass-panel rounded-xl p-4 glow-hover col-span-2 md:col-span-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-300/70 text-[12px] font-bold">BLOCKED</p>
                        <p class="text-red-500 text-2xl sm:text-3xl font-bold mt-1"><?= $blockedRequests ?></p>
                    </div>
                    <i class="fa-solid fa-ban text-red-500 text-2xl sm:text-3xl opacity-30"></i>
                </div>
            </div>
        </div>

        <!-- Links Feed Container -->
        <div class="space-y-6" id="linksContainer">
            <?php if(!empty($db)): ?>
                <?php foreach(array_reverse($db, true) as $id => $d): 
                    $isExpired = time() > $d['expires'];
                    $isLimited = $d['limit'] > 0 && $d['downloads'] >= $d['limit'];
                    $statusHtml = ($isExpired || $isLimited) 
                        ? '<span class="bg-[#1a0f14]/80 text-red-500 px-3 py-1.5 rounded-lg text-[11px] sm:text-[12px] font-bold tracking-widest border border-red-900/50 inline-flex items-center gap-1.5"><i class="fa-solid fa-ban"></i> EXPIRED</span>' 
                        : '<span class="bg-cyan-500/10 text-cyan-400 px-3 py-1.5 rounded-lg text-[11px] sm:text-[12px] font-bold tracking-widest border border-cyan-400/30 inline-flex items-center gap-1.5"><i class="fa-solid fa-circle-check"></i> ACTIVE</span>';
                    
                    $expiresIn = $d['expires'] - time();
                    $expiresInHours = floor($expiresIn / 3600);
                    $expiresInMins = floor(($expiresIn % 3600) / 60);

                    // Host extraction for copy button
                    $host = $_SERVER['HTTP_HOST'];
                    $host = preg_replace('/:\d+$/', '', $host);
                    $fullLink = "https://" . $host . '/' . $id . '.hc';

                    // Truth Verdict Calculation for Customer Claims
                    $verdictText = "NO ACTIVITY YET";
                    $verdictClass = "text-slate-400 bg-slate-800/40 border-slate-700/40";
                    if (!empty($d['logs'])) {
                        $lastLog = end($d['logs']);
                        $successCount = 0;
                        $diffDeviceAttempt = false;
                        foreach($d['logs'] as $lg) {
                            if ($lg['status'] === 'Success') $successCount++;
                            if (isset($lg['reason']) && $lg['reason'] === 'DIFF DEVICE') $diffDeviceAttempt = true;
                        }
                        if ($diffDeviceAttempt) {
                            $verdictText = "⚠ LIAR: SHARED WITH ANOTHER DEVICE";
                            $verdictClass = "text-red-400 bg-red-950/40 border-red-900/60 animate-pulse";
                        } elseif ($successCount > 0 && $lastLog['status'] === 'Failed') {
                            $verdictText = "ℹ CUSTOMER TELLS TRUTH: RETRY AFTER SUCCESS";
                            $verdictClass = "text-amber-400 bg-amber-950/40 border-amber-900/60";
                        } elseif ($successCount > 0) {
                            $verdictText = "✔ DELIVERED & DOWNLOADED SUCCESSFULLY";
                            $verdictClass = "text-emerald-400 bg-emerald-950/40 border-emerald-900/60";
                        } elseif ($lastLog['status'] === 'Blocked') {
                            $verdictText = "⛔ BLOCKED (BROWSER OR BOT ATTEMPT)";
                            $verdictClass = "text-rose-400 bg-rose-950/40 border-rose-900/60";
                        }
                    }
                ?>
                <div class="glass-panel rounded-[1.5rem] p-4 sm:p-6 relative overflow-hidden group hover:border-cyan-500/40 transition-colors config-card shadow-xl" data-link-id="<?= htmlspecialchars($id) ?>" data-original-name="<?= strtolower(htmlspecialchars($d['original_name'])) ?>">
                    
                    <!-- Top Info Card Header -->
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 border-b border-blue-900/40 pb-5 mb-5">
                        <div class="flex items-center gap-4 w-full lg:w-auto">
                            <div class="w-14 h-14 rounded-xl bg-[#080f1e]/90 border border-blue-900/40 flex items-center justify-center text-cyan-400 shrink-0 glow-hover">
                                <i class="fa-regular fa-file-code text-2xl"></i>
                            </div>
                            <div class="overflow-hidden">
                                <span class="text-[11px] sm:text-[12px] font-bold tracking-widest text-blue-300/70 block mb-1 truncate">ORIGINAL: <?= htmlspecialchars($d['original_name']) ?></span>
                                <span class="font-bold text-cyan-400 text-lg sm:text-xl blue-glow-text cursor-pointer select-all" onclick="copyText('<?= $fullLink ?>', this)"><?= htmlspecialchars($id) ?>.HC <i class="fa-regular fa-copy text-xs ml-1 text-cyan-400/70"></i></span>
                            </div>
                        </div>

                        <!-- Metrics & Action Buttons -->
                        <div class="flex items-center gap-3 w-full lg:w-auto justify-between lg:justify-end flex-wrap">
                            <div class="text-right bg-[#080f1e]/80 px-3.5 py-2 rounded-xl border border-blue-900/40 glow-hover">
                                <span class="text-[10px] font-bold tracking-widest text-blue-300/70 block mb-0.5">DOWNLOADS</span>
                                <span class="text-white font-bold text-[14px] sm:text-[15px]"><?= $d['downloads'] ?> / <span class="<?= $d['limit'] > 0 ? 'text-cyan-400' : 'text-slate-500' ?>"><?= $d['limit'] > 0 ? $d['limit'] : '∞' ?></span></span>
                            </div>
                            
                            <div class="text-right bg-[#080f1e]/80 px-3.5 py-2 rounded-xl border border-blue-900/40 glow-hover">
                                <span class="text-[10px] font-bold tracking-widest text-blue-300/70 block mb-0.5">EXPIRES</span>
                                <span class="text-white font-bold text-[14px] sm:text-[15px]"><?= $expiresInHours ?>H <?= $expiresInMins ?>M</span>
                            </div>

                            <div><?= $statusHtml ?></div>

                            <div class="flex items-center gap-2">
                                <!-- Re-Open / Reset Link Button -->
                                <a href="?reopen=<?= $id ?>" title="Re-Open / Reset Link" onclick="return confirm('RESET DOWNLOAD LIMIT AND EXTEND 5 MINUTES FOR THIS LINK?')" class="bg-blue-600/20 hover:bg-blue-600/40 border border-blue-500/40 text-cyan-400 w-11 h-11 flex items-center justify-center rounded-xl transition-all shadow-md">
                                    <i class="fa-solid fa-rotate-right text-sm"></i>
                                </a>
                                <!-- Delete Single Link -->
                                <a href="?delete=<?= $id ?>" title="Delete Link" onclick="return confirm('DELETE THIS LINK PERMANENTLY?')" class="bg-[#1a0f14]/80 hover:bg-red-900/40 border border-red-900/40 text-red-500 w-11 h-11 flex items-center justify-center rounded-xl transition-all shadow-md">
                                    <i class="fa-solid fa-trash text-sm"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Truth Verdict Banner -->
                    <div class="mb-5 p-3 rounded-xl border flex items-center gap-2.5 text-[12px] sm:text-[13px] font-bold tracking-widest <?= $verdictClass ?>">
                        <i class="fa-solid fa-shield-halved text-base"></i>
                        <span>TRUTH RADAR VERDICT: <?= $verdictText ?></span>
                    </div>

                    <!-- Live Request Feed Table -->
                    <div>
                        <h4 class="text-[12px] sm:text-[13px] font-bold tracking-widest text-blue-300/75 mb-3 flex items-center bg-[#080f1e]/80 px-3.5 py-2 rounded-lg border border-blue-900/40 w-fit">
                            <i class="fa-solid fa-terminal mr-2 text-cyan-400"></i> LIVE REQUEST FEED (RADAR)
                        </h4>
                        <div class="bg-[#030a14] rounded-xl overflow-hidden border border-blue-900/40 overflow-x-auto custom-scroll shadow-inner">
                            <table class="w-full text-left whitespace-nowrap">
                                <tbody class="divide-y divide-blue-900/30">
                                    <?php if(!empty($d['logs'])): ?>
                                        <?php foreach(array_reverse($d['logs']) as $log): 
                                            $statusVal = $log['status'];
                                            $reasonStr = $log['reason'] ?? '';
                                            
                                            if ($statusVal === 'Success') {
                                                $badge = 'bg-[#22c55e]/10 text-[#22c55e] border-[#22c55e]/30';
                                                $icon = '<i class="fa-solid fa-check text-xs"></i>';
                                                $statusText = 'OPEN';
                                            } elseif ($statusVal === 'Dropped') {
                                                $badge = 'bg-amber-500/10 text-amber-400 border-amber-500/30';
                                                $icon = '<i class="fa-solid fa-triangle-exclamation text-xs"></i>';
                                                $statusText = 'NET DROP';
                                            } else {
                                                $badge = 'bg-red-500/10 text-red-500 border-red-500/30';
                                                $icon = '<i class="fa-solid fa-xmark text-xs"></i>';
                                                $statusText = 'BLOCKED';
                                            }
                                        ?>
                                        <tr class="hover:bg-[#080f1e]/70 transition duration-200">
                                            <td class="px-4 py-3.5 align-middle">
                                                <div class="flex items-center gap-2 text-blue-300/70 text-[12px] font-bold">
                                                    <i class="fa-regular fa-clock opacity-50"></i>
                                                    <span><?= date('Y-m-d H:i:s', $log['time']) ?></span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3.5 align-middle">
                                                <div class="flex items-center gap-2 text-white text-[13px] font-bold">
                                                    <i class="fa-solid fa-location-dot text-blue-300/50"></i>
                                                    <span><?= htmlspecialchars($log['ip']) ?></span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3.5 text-[13px] font-bold text-cyan-300 align-middle">
                                                <?= htmlspecialchars($log['client']) ?>
                                                <?php if(!empty($reasonStr)): ?>
                                                    <span class="text-[10px] text-amber-300 ml-1 bg-amber-950/60 px-2 py-0.5 rounded border border-amber-800/40 font-bold"><?= $reasonStr ?></span>
                                                <?php endif; ?>
                                                <span class="text-[10px] text-blue-300/60 block truncate max-w-xs mt-1 bg-[#080f1e] px-2 py-0.5 rounded border border-blue-900/30 font-mono font-normal w-fit"><?= htmlspecialchars($log['ua']) ?></span>
                                            </td>
                                            <td class="px-4 py-3.5 text-right align-middle">
                                                <div class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg font-bold tracking-widest border text-[11px] <?= $badge ?> w-[110px]">
                                                    <?= $icon ?>
                                                    <span><?= $statusText ?></span>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td class="px-4 py-8 text-center text-blue-300/50 font-bold text-[12px] tracking-widest">
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
                <div class="text-center py-20 border-2 border-dashed border-blue-900/50 rounded-[2rem] bg-gradient-to-b from-[#080f1e]/80 to-[#030a14]">
                    <div class="w-20 h-20 bg-[#080f1e] border border-blue-900/40 rounded-full flex items-center justify-center mx-auto mb-5 shadow-lg">
                        <i class="fa-solid fa-satellite text-4xl text-cyan-400"></i>
                    </div>
                    <p class="text-blue-300/70 text-[14px] font-bold tracking-widest">NO CLOUD CONFIG LINKS CREATED YET.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript Tools -->
    <script>
        function searchLinks() {
            let input = document.getElementById('searchInput').value.trim().toLowerCase();
            let cards = document.querySelectorAll('.config-card');
            cards.forEach(card => {
                let id = card.getAttribute('data-link-id').toLowerCase();
                let originalName = card.getAttribute('data-original-name').toLowerCase();
                if (id.includes(input) || originalName.includes(input) || input === '') {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function copyText(text, elem) {
            navigator.clipboard.writeText(text).then(() => {
                let originalHTML = elem.innerHTML;
                elem.innerHTML = '<span class="text-emerald-400"><i class="fa-solid fa-check"></i> COPIED!</span>';
                setTimeout(() => { elem.innerHTML = originalHTML; }, 1500);
            });
        }

        // Auto refresh feed every 30 seconds if search is empty
        setInterval(() => {
            let searchVal = document.getElementById('searchInput');
            if (searchVal && searchVal.value.trim() === '') {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>
