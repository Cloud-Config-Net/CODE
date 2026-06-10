<?php
/**
 * NET-CLOUD-CONFIG - AI Cyberpunk Edition (Tracking Radar)
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
        $loginError = "CRITICAL: Authentication Failed!";
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
    <title>NET-CLOUD | System Radar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #020617; background-image: radial-gradient(circle at top right, #0f172a 0%, #020617 100%); }
        .glass-panel { background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(6, 182, 212, 0.2); }
        .glow-input:focus { border-color: #06b6d4; box-shadow: 0 0 10px rgba(6, 182, 212, 0.3); outline: none; }
        .radar-pulse { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); animation: pulse-red 2s infinite; }
        @keyframes pulse-red { 0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); } 70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); } 100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }
        .custom-scroll::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scroll::-webkit-scrollbar-track { background: #020617; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
    </style>
</head>
<body class="min-h-screen text-slate-200 font-sans p-4 flex items-center justify-center">

    <?php if (!isset($_SESSION['admin_logged'])): ?>
        <div class="glass-panel w-full max-w-md rounded-2xl p-8 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-cyan-500 via-blue-500 to-purple-600"></div>
            <div class="text-center mb-8">
                <div class="inline-flex bg-slate-900 p-5 rounded-full border border-cyan-500/30 mb-4 text-cyan-400 text-3xl shadow-[0_0_20px_rgba(6,182,212,0.2)]">
                    <i class="fa-solid fa-fingerprint"></i>
                </div>
                <h2 class="text-2xl font-black text-white tracking-widest uppercase font-mono">System Core</h2>
                <p class="text-cyan-500/60 text-[10px] uppercase tracking-[0.2em] mt-2">Encrypted Radar Access</p>
            </div>

            <?php if($loginError): ?>
                <div class="bg-red-500/10 border-l-4 border-red-500 text-red-400 text-xs font-mono p-3 mb-6 animate-pulse">
                    <i class="fa-solid fa-triangle-exclamation mr-2"></i> <?= $loginError ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <input type="hidden" name="login_submit" value="1">
                <div>
                    <input type="text" name="username" placeholder="ROOT ID" required class="glow-input w-full bg-slate-900/80 border border-slate-700 rounded-lg px-4 py-3 text-white font-mono text-sm tracking-widest placeholder-slate-600">
                </div>
                <div>
                    <input type="password" name="password" placeholder="KEY PHRASE" required class="glow-input w-full bg-slate-900/80 border border-slate-700 rounded-lg px-4 py-3 text-white font-mono text-sm tracking-widest placeholder-slate-600">
                </div>
                <button type="submit" class="w-full bg-cyan-600 hover:bg-cyan-500 text-slate-900 font-black py-3 rounded-lg transition shadow-[0_0_15px_rgba(6,182,212,0.3)] text-sm uppercase tracking-widest mt-4">
                    Initialize ➔
                </button>
            </form>
        </div>
    <?php else: ?>
        <div class="w-full max-w-6xl my-auto">
            <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4 bg-slate-900/80 p-6 rounded-2xl border border-slate-700/50 shadow-2xl relative overflow-hidden">
                <div class="absolute right-0 top-0 w-32 h-32 bg-red-500/5 rounded-full blur-3xl"></div>
                <div class="flex items-center gap-4 relative z-10">
                    <div class="w-12 h-12 rounded-full bg-slate-800 border border-red-500/50 flex items-center justify-center radar-pulse text-red-500">
                        <i class="fa-solid fa-satellite-dish text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl md:text-2xl font-black text-white tracking-widest uppercase">Tracking <span class="text-red-500">Radar</span></h1>
                        <p class="text-cyan-500/70 text-[10px] font-mono uppercase tracking-[0.1em] mt-1">Live AI Sniffer & Package Analytics</p>
                    </div>
                </div>
                <div class="flex gap-3 relative z-10">
                    <a href="/" class="bg-slate-800 hover:bg-slate-700 border border-slate-600 px-5 py-2.5 rounded-lg transition text-xs font-mono uppercase tracking-wider flex items-center gap-2"><i class="fa-solid fa-plus text-cyan-400"></i> New</a>
                    <a href="?logout=1" class="bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/30 px-5 py-2.5 rounded-lg transition text-xs font-mono uppercase tracking-wider flex items-center gap-2"><i class="fa-solid fa-power-off"></i> Exit</a>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6">
                <?php foreach(array_reverse($db, true) as $id => $d): 
                    $isExpired = time() > $d['expires'];
                    $isLimited = $d['limit'] > 0 && $d['downloads'] >= $d['limit'];
                    $statusColor = ($isExpired || $isLimited) ? 'red' : 'cyan';
                    $statusText = ($isExpired || $isLimited) ? 'OFFLINE / EXPIRED' : 'ACTIVE / LISTENING';
                ?>
                <div class="bg-slate-900/60 rounded-xl border border-slate-800 hover:border-slate-600 transition duration-300 shadow-lg overflow-hidden group">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center p-4 border-b border-slate-800 bg-slate-950/50 gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-2 rounded-full bg-<?= $statusColor ?>-500 <?= $statusColor == 'cyan' ? 'animate-pulse shadow-[0_0_8px_rgba(6,182,212,1)]' : '' ?>"></div>
                            <div>
                                <span class="text-[10px] text-slate-500 font-mono uppercase tracking-wider block mb-1">Payload Target:</span>
                                <span class="font-mono text-<?= $statusColor ?>-400 font-bold text-sm tracking-widest"><?= htmlspecialchars($d['original_name']) ?> <span class="text-slate-600">|</span> <?= $id ?>.hc</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-6 w-full md:w-auto justify-between md:justify-end">
                            <div class="text-center font-mono">
                                <span class="text-[9px] text-slate-500 block uppercase tracking-wider">Status</span>
                                <span class="text-<?= $statusColor ?>-400 font-bold text-[10px] tracking-widest"><?= $statusText ?></span>
                            </div>
                            <div class="text-center font-mono">
                                <span class="text-[9px] text-slate-500 block uppercase tracking-wider">Usage</span>
                                <span class="text-white font-bold text-xs"><?= $d['downloads'] ?> <span class="text-slate-600">/</span> <?= $d['limit'] > 0 ? $d['limit'] : '∞' ?></span>
                            </div>
                            <a href="?delete=<?= $id ?>" onclick="return confirm('WARNING: Destroy payload data permanently?')" class="text-slate-600 hover:text-red-500 transition p-2">
                                <i class="fa-solid fa-trash-can"></i>
                            </a>
                        </div>
                    </div>

                    <div class="p-0">
                        <div class="overflow-x-auto custom-scroll">
                            <table class="w-full text-left whitespace-nowrap">
                                <thead class="bg-slate-950/80 font-mono text-[9px] text-slate-500 uppercase tracking-widest border-b border-slate-800">
                                    <tr>
                                        <th class="px-5 py-3 font-medium">Timestamp</th>
                                        <th class="px-5 py-3 font-medium">Client IP (Target)</th>
                                        <th class="px-5 py-3 font-medium">Sniffed Package / Tool</th>
                                        <th class="px-5 py-3 font-medium text-right">Radar Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/50 text-xs font-mono">
                                    <?php if(!empty($d['logs'])): ?>
                                        <?php foreach(array_reverse($d['logs']) as $log): 
                                            $isSuccess = ($log['status'] === 'Success');
                                            $textColor = $isSuccess ? 'text-cyan-400' : 'text-red-400';
                                            $badgeClass = $isSuccess ? 'bg-cyan-500/10 text-cyan-400 border-cyan-500/30' : 'bg-red-500/10 text-red-500 border-red-500/30';
                                        ?>
                                        <tr class="hover:bg-slate-800/40 transition">
                                            <td class="px-5 py-3 text-slate-400 text-[10px]"><?= date('y-m-d H:i:s', $log['time']) ?></td>
                                            <td class="px-5 py-3 <?= $textColor ?> font-bold tracking-wider"><i class="fa-solid fa-network-wired text-slate-600 mr-2 text-[10px]"></i><?= htmlspecialchars($log['ip']) ?></td>
                                            <td class="px-5 py-3">
                                                <div class="<?= $textColor ?> font-semibold"><?= htmlspecialchars($log['client']) ?></div>
                                                <div class="text-[9px] text-slate-600 truncate max-w-[250px] mt-0.5"><?= htmlspecialchars($log['ua']) ?></div>
                                            </td>
                                            <td class="px-5 py-3 text-right">
                                                <span class="px-2 py-1 rounded-sm border text-[9px] font-bold tracking-wider <?= $badgeClass ?>">
                                                    <?= $isSuccess ? 'PASS' : 'BLOCKED' ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="px-5 py-6 text-center text-slate-600 text-[10px] uppercase tracking-widest font-mono">No network traffic detected on this port yet.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if(empty($db)): ?>
                    <div class="flex flex-col items-center justify-center py-20 text-slate-600">
                        <i class="fa-solid fa-satellite text-4xl mb-4 opacity-50"></i>
                        <p class="text-xs font-mono uppercase tracking-widest">Radar is clear. No payloads deployed.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
