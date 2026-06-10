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
    <title>NET-CLOUD | Admin Space</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .glass-panel { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .custom-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: #0f172a; border-radius: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
    </style>
</head>
<body class="bg-slate-900 min-h-screen text-slate-200 font-sans bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-slate-800 via-slate-900 to-black p-4 flex items-center justify-center">

    <?php if (!isset($_SESSION['admin_logged'])): ?>
        <div class="glass-panel w-full max-w-md rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-6">
                <div class="inline-flex bg-slate-800 p-4 rounded-full border border-slate-700 mb-3 text-emerald-400 text-2xl">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <h2 class="text-2xl font-bold text-white">Admin Authentication</h2>
                <p class="text-slate-400 text-xs mt-1">Identity validation required to access tracking analytics.</p>
            </div>

            <?php if($loginError): ?>
                <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm p-3 rounded-lg text-center mb-4">
                    <?= $loginError ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="login_submit" value="1">
                <div>
                    <label class="block text-xs text-slate-400 mb-1 ml-1">Username</label>
                    <input type="text" name="username" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white font-mono focus:outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1 ml-1">Password</label>
                    <input type="password" name="password" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white font-mono focus:outline-none focus:border-emerald-500">
                </div>
                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 rounded-xl transition shadow-lg shadow-emerald-500/20 text-sm mt-2">
                    Access Dashboard ➔
                </button>
            </form>
        </div>
    <?php else: ?>
        <div class="w-full max-w-5xl my-auto">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4 bg-slate-800/60 p-5 rounded-2xl border border-slate-700 shadow-xl">
                <div class="text-left">
                    <h1 class="text-2xl font-bold text-emerald-400 flex items-center gap-2">
                        <i class="fa-solid fa-satellite-dish animate-pulse"></i> Tracking Radar & Analytics
                    </h1>
                    <p class="text-slate-400 text-xs mt-1">Live traffic sniffer inspecting app packages and client remote IPs</p>
                </div>
                <div class="flex gap-2">
                    <a href="/" class="bg-slate-700 hover:bg-slate-600 px-4 py-2.5 rounded-xl transition text-xs font-bold"><i class="fa-solid fa-cloud-arrow-up mr-1"></i> Upload Center</a>
                    <a href="?logout=1" class="bg-red-600/20 hover:bg-red-600/40 text-red-400 border border-red-500/30 px-4 py-2.5 rounded-xl transition text-xs font-bold"><i class="fa-solid fa-power-off mr-1"></i> Logout</a>
                </div>
            </div>

            <div class="space-y-4">
                <?php foreach(array_reverse($db, true) as $id => $d): 
                    $isExpired = time() > $d['expires'];
                    $isLimited = $d['limit'] > 0 && $d['downloads'] >= $d['limit'];
                    $statusHtml = ($isExpired || $isLimited) ? '<span class="bg-red-500/20 text-red-400 px-2 py-0.5 rounded text-[10px] border border-red-500/30">Expired</span>' : '<span class="bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded text-[10px] border border-emerald-500/30">Active</span>';
                ?>
                <div class="bg-slate-800/80 rounded-xl border border-slate-700 p-4 shadow-md">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2 border-b border-slate-700 pb-3 mb-3">
                        <div>
                            <span class="text-xs text-slate-400 block mb-0.5">Original File Name: <?= htmlspecialchars($d['original_name']) ?></span>
                            <span class="font-mono text-cyan-400 font-bold text-sm"><?= $id ?>.hc</span>
                        </div>
                        <div class="flex items-center gap-3 w-full md:w-auto justify-between md:justify-end">
                            <div class="text-right font-mono">
                                <span class="text-[10px] text-slate-400 block">Downloads Tracker</span>
                                <span class="text-white font-bold text-xs"><?= $d['downloads'] ?> / <?= $d['limit'] > 0 ? $d['limit'] : '∞' ?></span>
                            </div>
                            <div><?= $statusHtml ?></div>
                            <a href="?delete=<?= $id ?>" onclick="return confirm('Delete this link permanently?')" class="bg-red-500/10 hover:bg-red-500/20 text-red-400 p-2 rounded-lg transition text-xs">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-[11px] font-semibold text-slate-400 mb-2"><i class="fa-solid fa-clock-rotate-left mr-1 text-cyan-500"></i> Live Request Log Feed:</h4>
                        <div class="bg-slate-900/50 rounded-lg overflow-hidden border border-slate-700 overflow-x-auto custom-scroll">
                            <table class="w-full text-xs text-left whitespace-nowrap">
                                <tbody class="divide-y divide-slate-800">
                                    <?php if(!empty($d['logs'])): ?>
                                        <?php foreach(array_reverse($d['logs']) as $log): 
                                            $badge = ($log['status'] === 'Success') ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30' : 'bg-red-500/20 text-red-400 border-red-500/30';
                                            $clientClass = (strpos($log['client'], 'HTTP Custom') !== false) ? 'text-emerald-400 font-bold' : 'text-slate-300';
                                        ?>
                                        <tr class="hover:bg-slate-800/30 transition">
                                            <td class="px-3 py-2 text-slate-400 font-mono text-[11px]"><?= date('Y-m-d H:i:s', $log['time']) ?></td>
                                            <td class="px-3 py-2 font-mono text-cyan-300 text-[11px]"><?= htmlspecialchars($log['ip']) ?></td>
                                            <td class="px-3 py-2 text-[11px] <?= $clientClass ?>">
                                                <?= htmlspecialchars($log['client']) ?>
                                                <span class="text-[9px] text-slate-500 block truncate max-w-xs"><?= htmlspecialchars($log['ua']) ?></span>
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                <span class="px-2 py-0.5 rounded border text-[9px] <?= $badge ?>"><?= $log['status'] === 'Success' ? 'Fetched' : 'Blocked 🛑' ?></span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td class="px-3 py-3 text-center text-slate-600 text-xs">No request activity tracked yet.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(empty($db)): ?>
                    <div class="text-center py-12 text-slate-500 text-sm">No configurations generated yet.</div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
