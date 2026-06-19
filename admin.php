<?php
/**
 * CLOUD-CONFIG 
 * Secure Admin Panel & Log Radar (PRO EDITION)
 * File Name: admin.php
 */

// إعداد استقرار الجلسات لمنع الخروج التلقائي
ini_set('session.cookie_lifetime', 2592000);
ini_set('session.gc_maxlifetime', 2592000);
session_start();
date_default_timezone_set('Africa/Tunis');

if (!isset($_SESSION['main_logged']) || $_SESSION['main_logged'] !== true) {
    header("Location: /"); 
    exit;
}

$dbFile = __DIR__ . '/db.json';
$db = json_decode(file_exists($dbFile) ? file_get_contents($dbFile) : '[]', true) ?: [];

if (isset($_GET['logout'])) {
    unset($_SESSION['main_logged']);
    header("Location: /"); exit;
}

if (isset($_GET['delete'])) {
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['delete']);
    if (isset($db[$id])) { 
        @unlink($db[$id]['real_path']); 
        unset($db[$id]); 
        file_put_contents($dbFile, json_encode($db)); 
    }
    header("Location: /admin"); exit;
}

if (isset($_GET['delete_all'])) {
    foreach ($db as $id => $d) { @unlink($d['real_path']); }
    $db = []; 
    file_put_contents($dbFile, json_encode($db)); 
    header("Location: /admin"); exit;
}

// Statistics Logic (نفسه)
$totalLinks = count($db);
$totalDownloads = 0; $totalRequests = 0; $successfulRequests = 0; $blockedRequests = 0; $expiredLinks = 0;
foreach ($db as $d) {
    $totalDownloads += $d['downloads'] ?? 0;
    $totalRequests += count($d['logs']);
    foreach ($d['logs'] as $log) {
        if ($log['status'] === 'Success') $successfulRequests++; else $blockedRequests++;
    }
    if (time() > $d['expires'] || ($d['limit'] > 0 && ($d['downloads'] ?? 0) >= $d['limit'])) { $expiredLinks++; }
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
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { -webkit-tap-highlight-color: transparent; } 
        body { background-color: #05080f; overflow-x: hidden; font-family: 'Oswald', sans-serif; letter-spacing: 0.5px; text-transform: uppercase; color: #cbd5e1; }
        .glass-panel { background: rgba(10, 15, 28, 0.85); backdrop-filter: blur(12px); border: 1px solid #1e2738; box-shadow: 0 0 40px rgba(0, 0, 0, 0.8), inset 0 0 20px rgba(81, 192, 192, 0.05); }
        .neon-text-glow { text-shadow: 0 0 15px rgba(81, 192, 192, 0.6), 0 0 30px rgba(81, 192, 192, 0.2); }
        .smooth-transition { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .glow-hover:hover { filter: drop-shadow(0 0 15px rgba(81, 192, 192, 0.4)); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 relative">
    <div class="w-full max-w-6xl my-auto py-8 z-10 relative">
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-6 glass-panel p-6 rounded-2xl relative overflow-hidden">
            <div class="text-center md:text-left z-10 flex flex-col md:items-start items-center">
                <h1 class="text-4xl font-bold text-white flex flex-col md:flex-row items-center gap-2 drop-shadow-lg smooth-transition">
                    <span class="text-[#51C0C0] neon-text-glow">RADAR LINK PRO</span>
                </h1>
            </div>
            <div class="flex flex-row gap-4 z-10 justify-center md:justify-end mt-6 md:mt-0 flex-wrap items-center">
                <a href="/" title="Back to Upload" class="bg-[#0d131f] hover:bg-[#151e2e] border border-[#1e2738] hover:border-[#51C0C0] text-[#51C0C0] w-12 h-12 flex items-center justify-center rounded-xl transition-all duration-300 shadow-[0_0_10px_rgba(81,192,192,0.1)] smooth-transition glow-hover"><i class="fa-solid fa-arrow-left text-xl"></i></a>
                <a href="?delete_all=1" title="Delete All" onclick="return confirm('⚠️ WARNING: Are you sure you want to delete ALL configurations and logs? This cannot be undone.')" class="bg-[#1a0f14] hover:bg-red-900/40 border border-red-900/50 hover:border-red-500/50 text-red-400 w-12 h-12 flex items-center justify-center rounded-xl transition-all duration-300 shadow-[0_0_10px_rgba(220,38,38,0.1)] smooth-transition"><i class="fa-solid fa-dumpster-fire text-xl"></i></a>
            </div>
        </div>
        </div>
</body>
</html>
