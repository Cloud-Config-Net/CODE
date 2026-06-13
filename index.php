<?php
/**
 * NET-CLOUD-CONFIG - Enhanced Version (V6)
 * Main Upload & Client Sniffer with Advanced Effects
 * File Name: index.php
 */

session_start();
date_default_timezone_set('Africa/Tunis');

$uploadDir = __DIR__ . '/uploads/';
$dbFile = __DIR__ . '/db.json';

if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
if (!file_exists($dbFile)) { file_put_contents($dbFile, json_encode([])); }

$db = json_decode(file_get_contents($dbFile), true) ?: [];

// ==========================================
// Smart Download & Client Sniffer Radar
// ==========================================
if (isset($_GET['c'])) {
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['c']);
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    $isBrowser = preg_match('/(Mozilla|Chrome|Safari|Edge|Opera|Brave|SamsungBrowser)/i', $ua);
    if (preg_match('/okhttp/i', $ua)) {
        $clientLabel = 'HTTP Custom App (Direct Secure Connection)';
    } elseif ($isBrowser) {
        $clientLabel = 'Standard Web Browser (Sniffing Attempt)';
    } else {
        $clientLabel = 'VPN App / Unknown Android Tool';
    }

    if (isset($db[$id])) {
        $entry = $db[$id];
        
        if (time() > $entry['expires'] || ($entry['limit'] > 0 && $entry['downloads'] >= $entry['limit'])) {
            $db[$id]['logs'][] = ['ip' => $ip, 'ua' => $ua, 'client' => $clientLabel . ' (Expired)', 'time' => time(), 'status' => 'Failed'];
            file_put_contents($dbFile, json_encode($db));
            @unlink($entry['real_path']);
            header("HTTP/1.1 410 Gone"); die("This link has expired or reached its download limit.");
        }
        
        if ($isBrowser) {
            $db[$id]['logs'][] = ['ip' => $ip, 'ua' => $ua, 'client' => $clientLabel, 'time' => time(), 'status' => 'Blocked'];
            file_put_contents($dbFile, json_encode($db));
            header("HTTP/1.1 403 Forbidden"); 
            die('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Access Denied</title><style>body{background:#05080f;color:#ef4444;text-align:center;font-family:-apple-system,BlinkMacSystemFont,"Inter",sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:20px;box-sizing:border-box;}img{max-width:100%;height:auto;border-radius:15px;box-shadow:0 0 20px rgba(0,0,0,0.5);margin-bottom:20px;max-height:60vh;border:1px solid #1e2738;}h1{margin:0 0 10px 0;font-size:22px;letter-spacing:-0.5px;font-weight:700;}p{margin:0;color:#8a9bb3;font-size:13px;line-height:1.6;max-width:400px;}</style></head><body><img src="https://i.postimg.cc/TYfpcBy3/IMG-20260612-024446-049.jpg" alt="Tutorial"><h1>🛑 Access Denied</h1><p>This config link cannot be opened in a web browser.<br>Please copy the link and import it directly inside the <b>HTTP Custom</b> app as shown above.</p></body></html>');
        }

        $db[$id]['downloads']++;
        $db[$id]['logs'][] = ['ip' => $ip, 'ua' => $ua, 'client' => $clientLabel, 'time' => time(), 'status' => 'Success'];
        file_put_contents($dbFile, json_encode($db));
        
        if (ob_get_length()) { ob_end_clean(); } 
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream'); 
        header('Content-Disposition: attachment; filename="' . basename($entry['original_name']) . '"');
        header('Content-Transfer-Encoding: binary'); 
        header('Expires: 0');
        header('Cache-Control: must-revalidate'); 
        header('Pragma: public');
        header('Content-Length: ' . filesize($entry['real_path']));
        
        readfile($entry['real_path']); 
        exit;
    }
    header("HTTP/1.1 404 Not Found"); die("File Not Found.");
}

// ==========================================
// UNIFIED UI Login System
// ==========================================
$adminUser = 'Admin';
$adminPass = '38sPcd6Ysr04NGVk'; 

$loginError = false;

if (isset($_GET['logout'])) {
    unset($_SESSION['main_logged']);
    header("Location: /"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ui_login'])) {
    if ($_POST['username'] === $adminUser && $_POST['password'] === $adminPass) {
        $_SESSION['main_logged'] = true;
        header("Location: /"); exit;
    } else {
        $loginError = "بيانات الدخول غير صحيحة!";
    }
}

$isLogged = isset($_SESSION['main_logged']) && $_SESSION['main_logged'] === true;

// ==========================================
// Multi-Upload Handling Logic
// ==========================================
$generatedLinks = [];

if ($isLogged && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['files'])) {
    $limit = (int)$_POST['limit']; 
    $duration = (int)$_POST['duration'];
    $timeUnit = $_POST['time_unit'] ?? 'hours';
    
    $seconds = ($timeUnit === 'minutes') ? ($duration * 60) : ($duration * 3600);
    $fileCount = count($_FILES['files']['name']);
    
    for ($i = 0; $i < $fileCount; $i++) {
        if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['files']['tmp_name'][$i];
            $originalName = basename($_FILES['files']['name'][$i]);
            $targetPath = $uploadDir . bin2hex(random_bytes(16)) . '.dat';
            
            if (move_uploaded_file($tmpName, $targetPath)) {
                $shortId = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 5);
                
                $host = $_SERVER['HTTP_HOST'];
                $host = preg_replace('/:\d+$/', '', $host); 
                $link = "https://" . $host . '/' . $shortId . '.hc';
                
                $db[$shortId] = [
                    'original_name' => $originalName,
                    'real_path' => $targetPath,
                    'limit' => $limit,
                    'downloads' => 0,
                    'expires' => time() + $seconds,
                    'upload_date' => time(),
                    'logs' => []
                ];
                $generatedLinks[] = [
                    'original_name' => $originalName, 
                    'link' => $link,
                    'limit' => $limit,
                    'duration' => $duration,
                    'time_unit' => $timeUnit
                ];
            }
        }
    }
    
    if (!empty($generatedLinks)) { 
        file_put_contents($dbFile, json_encode($db)); 
        $_SESSION['temp_generated_links'] = $generatedLinks; 
        header("Location: index.php"); 
        exit;
    }
}

if (isset($_SESSION['temp_generated_links'])) {
    $generatedLinks = $_SESSION['temp_generated_links'];
    unset($_SESSION['temp_generated_links']); 
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SYSTEM LOGIN | CLOUD CONFIG</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700;800;900&family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        /* Modern Clean System Font as requested */
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Inter", Roboto, Helvetica, Arial, sans-serif; background-color: #05080f; overflow-x: hidden; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        
        /* Modern Apple-like Bold Headings */
        .modern-heading { font-weight: 700; letter-spacing: -0.04em; }
        
        .glass-panel { background: rgba(10, 15, 28, 0.85); backdrop-filter: blur(12px); border: 1px solid #1e2738; box-shadow: 0 0 40px rgba(0, 0, 0, 0.8), inset 0 0 20px rgba(81, 192, 192, 0.05); }
        .custom-scroll::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #51C0C0; border-radius: 10px; }
        .neon-text-glow { text-shadow: 0 0 15px rgba(81, 192, 192, 0.6), 0 0 30px rgba(81, 192, 192, 0.2); }
        .btn-glow { box-shadow: 0 0 20px rgba(81, 192, 192, 0.25); transition: all 0.3s ease; }
        .btn-glow:hover { box-shadow: 0 0 30px rgba(81, 192, 192, 0.4); transform: translateY(-2px); }
        
        /* Enhanced Background Animations */
        .bg-animations { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; pointer-events: none; z-index: -1; overflow: hidden; }
        .floating-element { position: absolute; animation: float-up linear infinite; opacity: 0.15; filter: drop-shadow(0 0 10px rgba(81,192,192,0.5)); }
        @keyframes float-up { 0% { transform: translateY(100vh) rotate(0deg) scale(0.8); opacity: 0; } 10% { opacity: 0.2; } 90% { opacity: 0.2; } 100% { transform: translateY(-20vh) rotate(360deg) scale(1.2); opacity: 0; } }
        
        /* Particle Effect */
        .particle-bg { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2; }
        .particle { position: absolute; width: 2px; height: 2px; background: #51C0C0; border-radius: 50%; opacity: 0.3; animation: particle-float 20s infinite linear; }
        @keyframes particle-float { 0% { transform: translateY(0) translateX(0); opacity: 0.3; } 50% { opacity: 0.6; } 100% { transform: translateY(-100vh) translateX(100px); opacity: 0; } }
        
        /* Input Focus Effects */
        input:focus { animation: input-focus 0.3s ease; }
        @keyframes input-focus { 0% { box-shadow: 0 0 0 0 rgba(81, 192, 192, 0.1); } 100% { box-shadow: 0 0 20px 0 rgba(81, 192, 192, 0.2); } }
        
        /* Button Ripple Effect */
        .ripple { position: relative; overflow: hidden; }
        .ripple::after { content: ''; position: absolute; top: 50%; left: 50%; width: 0; height: 0; background: rgba(255, 255, 255, 0.5); border-radius: 50%; transform: translate(-50%, -50%); pointer-events: none; }
        .ripple:active::after { animation: ripple-effect 0.6s ease-out; }
        @keyframes ripple-effect { 0% { width: 0; height: 0; opacity: 1; } 100% { width: 300px; height: 300px; opacity: 0; } }
        
        /* Smooth Transitions */
        .smooth-transition { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        
        /* Copy Button Animation */
        .copy-btn { position: relative; }
        .copy-btn.copied { animation: copy-success 0.5s ease; }
        @keyframes copy-success { 0% { transform: scale(1); } 50% { transform: scale(1.1); } 100% { transform: scale(1); } }
        
        /* Glow Effect on Hover */
        .glow-hover { transition: all 0.3s ease; }
        .glow-hover:hover { filter: drop-shadow(0 0 15px rgba(81, 192, 192, 0.4)); }
        
        /* Loading Animation */
        .loading-spinner { animation: spin 2s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        /* Pulse Animation */
        .pulse-animation { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    </style>
</head>
<body class="min-h-screen text-slate-200 flex items-center justify-center p-0 sm:p-4 relative">

    <div class="particle-bg" id="particleContainer"></div>

    <div class="bg-animations">
        <div class="floating-element text-4xl" style="left: 10%; animation-duration: 15s; animation-delay: 0s;">☁️</div>
        <div class="floating-element text-3xl" style="left: 30%; animation-duration: 20s; animation-delay: 5s;">🚀</div>
        <div class="floating-element text-5xl text-[#51C0C0]" style="left: 70%; animation-duration: 18s; animation-delay: 2s;"><i class="fa-solid fa-microchip"></i></div>
        <div class="floating-element text-3xl" style="left: 85%; animation-duration: 25s; animation-delay: 8s;">⚡</div>
        <div class="floating-element text-4xl text-[#51C0C0]" style="left: 50%; animation-duration: 22s; animation-delay: 12s;"><i class="fa-solid fa-satellite-dish"></i></div>
        <div class="floating-element text-2xl" style="left: 20%; animation-duration: 19s; animation-delay: 15s;">🔒</div>
    </div>

    <div class="glass-panel w-full h-full min-h-screen sm:min-h-0 sm:max-w-[28rem] sm:rounded-[2rem] p-6 sm:p-8 relative flex flex-col justify-center transition-all duration-500 z-10 ripple">
        
        <?php if(!$isLogged): ?>
        <div class="text-center mt-6 mb-8 w-full flex flex-col items-center relative z-10">
            <div class="w-16 h-16 rounded-full border border-[#1e2738] bg-[#0f1524] flex items-center justify-center mx-auto mb-5 relative group shadow-[0_0_15px_rgba(81,192,192,0.2)] glow-hover">
                <div class="absolute inset-2 rounded-full border border-[#51C0C0]/30 bg-[#51C0C0]/5 pulse-animation"></div>
                <i class="fa-solid fa-user-shield text-[#51C0C0] text-2xl z-10"></i>
            </div>
            <h2 class="text-[32px] modern-heading text-white drop-shadow-lg smooth-transition">SYSTEM <span class="text-[#51C0C0] neon-text-glow">LOGIN</span></h2>
            <div class="h-[3px] w-12 bg-[#51C0C0] mt-3 mx-auto rounded-full shadow-[0_0_10px_#51C0C0] smooth-transition"></div>
        </div>

        <form method="POST" class="space-y-6 mt-auto mb-auto bg-[#0a0f1c]/50 p-6 sm:p-8 rounded-[2rem] border border-[#1e2738] shadow-[0_0_20px_rgba(0,0,0,0.3)] smooth-transition">
            <input type="hidden" name="ui_login" value="1">
            
            <?php if($loginError): ?>
                <div class="bg-[#1a0f14] border border-red-900/50 text-red-400 text-xs p-3 rounded-xl text-center font-mono animate-pulse uppercase tracking-wide smooth-transition">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i> <?= $loginError ?>
                </div>
            <?php endif; ?>

            <div>
                <label class="flex items-center text-[10px] text-[#51C0C0] uppercase tracking-widest font-mono mb-2 ml-1 smooth-transition">
                    <i class="fa-solid fa-user-astronaut mr-2"></i> Username
                </label>
                <input type="text" name="username" required class="w-full bg-[#0d131f] border border-[#1e2738] rounded-xl px-4 py-4 text-sm text-white font-mono focus:outline-none focus:border-[#51C0C0] transition placeholder-[#2e3c50] smooth-transition glow-hover" placeholder="Enter Admin">
            </div>
            
            <div>
                <label class="flex items-center text-[10px] text-[#51C0C0] uppercase tracking-widest font-mono mb-2 ml-1 smooth-transition">
                    <i class="fa-solid fa-key mr-2"></i> Password
                </label>
                <input type="password" name="password" required class="w-full bg-[#0d131f] border border-[#1e2738] rounded-xl px-4 py-4 text-sm text-white font-mono focus:outline-none focus:border-[#51C0C0] transition placeholder-[#2e3c50] smooth-transition glow-hover" placeholder="••••••••••••">
            </div>
            
            <button type="submit" class="w-full bg-[#51C0C0] hover:bg-[#43a3a3] text-[#0a0f1c] font-bold py-4 rounded-xl transition btn-glow text-[13px] flex items-center justify-center uppercase tracking-widest mt-6 ripple smooth-transition">
                Access System <i class="fa-solid fa-arrow-right-to-bracket ml-2 text-[15px]"></i>
            </button>
        </form>

        <?php else: ?>
        <a href="admin.php" class="absolute top-6 left-6 w-10 h-10 flex items-center justify-center bg-[#0d131f] border border-[#1e2738] hover:border-[#51C0C0] text-[#51C0C0] rounded-xl shadow-inner transition-all hover:shadow-[0_0_10px_rgba(81,192,192,0.2)] z-20 ripple smooth-transition glow-hover" title="Radar Analytics">
            <i class="fa-solid fa-chart-line text-[15px]"></i>
        </a>

        <a href="?logout=1" class="absolute top-6 right-6 w-10 h-10 flex items-center justify-center bg-[#1a0f14] border border-red-900/50 hover:bg-red-900/20 text-red-400 rounded-xl shadow-inner transition z-20 ripple smooth-transition" title="Logout">
            <i class="fa-solid fa-right-from-bracket text-[15px]"></i>
        </a>

        <?php if(!empty($generatedLinks)): ?>
        <div class="text-center mt-12 mb-10 w-full flex flex-col items-center relative z-10">
            <h1 class="text-[36px] sm:text-[40px] modern-heading text-white drop-shadow-lg smooth-transition">
                LINK<span class="text-[#51C0C0] neon-text-glow ml-1">CODE</span>
            </h1>
            <div class="h-[3px] w-16 bg-[#51C0C0] mt-3 rounded-full shadow-[0_0_10px_#51C0C0] smooth-transition"></div>
        </div>

        <div class="bg-transparent mb-8 max-h-[55vh] sm:max-h-[26rem] overflow-y-auto custom-scroll flex flex-col gap-5 relative z-20 px-2 pb-4">
            <?php foreach($generatedLinks as $idx => $item): ?>
            <div class="bg-[#0a0f1c] border border-[#1e2738] rounded-2xl p-5 hover:border-[#51C0C0]/50 transition-colors shadow-lg w-full overflow-hidden smooth-transition glow-hover shrink-0">
                
                <div class="flex justify-between items-center bg-[#0d131f] rounded-xl py-3 px-2 mb-4 border border-[#141c2b]">
                    <div class="flex flex-col items-center justify-center w-1/3 border-r border-[#1e2738]" title="Original File">
                        <i class="fa-regular fa-file-code text-[#51C0C0] text-[15px] mb-1.5"></i>
                        <span class="text-[#8a9bb3] text-[9px] font-mono truncate w-[90%] text-center">
                            <?= htmlspecialchars($item['original_name']) ?>
                        </span>
                    </div>
                    <div class="flex flex-col items-center justify-center w-1/3 border-r border-[#1e2738]" title="Limit">
                        <i class="fa-solid fa-download text-[#51C0C0] text-[15px] mb-1.5"></i>
                        <span class="text-[#8a9bb3] text-[10px] font-mono font-bold"><?= $item['limit'] > 0 ? $item['limit'] : '∞' ?></span>
                    </div>
                    <div class="flex flex-col items-center justify-center w-1/3" title="Expiry">
                        <i class="fa-regular fa-hourglass-end text-[#51C0C0] text-[15px] mb-1.5"></i>
                        <span class="text-[#8a9bb3] text-[10px] font-mono font-bold"><?= $item['duration'] . ' ' . ($item['time_unit'] === 'minutes' ? 'min' : 'h') ?></span>
                    </div>
                </div>

                <div class="bg-[#0d131f] border border-[#1e2738] rounded-xl p-3 mb-3 font-mono text-[11px] text-[#51C0C0] text-center break-all hover:bg-[#141c2b] smooth-transition cursor-pointer copy-btn" onclick="copyToClipboard('<?= htmlspecialchars($item['link']) ?>', this)" title="Click to copy">
                    <i class="fa-solid fa-link mr-2"></i><?= htmlspecialchars($item['link']) ?>
                </div>

                <div class="flex justify-center mt-2">
                    <button onclick="copyToClipboard('<?= htmlspecialchars($item['link']) ?>', event.target)" class="w-[60%] bg-[#51C0C0]/20 hover:bg-[#51C0C0]/30 text-[#51C0C0] border border-[#51C0C0]/50 py-2.5 px-4 rounded-xl text-[12px] font-bold uppercase tracking-widest transition-all ripple smooth-transition btn-glow">
                        <i class="fa-solid fa-copy mr-2"></i> Copy
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="flex gap-3 mt-6 z-20">
            <button onclick="document.getElementById('fileInput').click()" class="flex-1 bg-[#51C0C0] hover:bg-[#43a3a3] text-[#0a0f1c] font-bold py-3 px-4 rounded-xl transition btn-glow text-[12px] flex items-center justify-center uppercase tracking-widest ripple smooth-transition">
                <i class="fa-solid fa-cloud-arrow-up mr-2"></i> Upload More
            </button>
            <a href="?logout=1" class="flex-1 bg-red-900/30 hover:bg-red-900/50 text-red-400 font-bold py-3 px-4 rounded-xl transition text-[12px] flex items-center justify-center uppercase tracking-widest border border-red-900/50 ripple smooth-transition">
                <i class="fa-solid fa-right-from-bracket mr-2"></i> Logout
            </a>
        </div>

        <?php else: ?>
        <div class="text-center mt-12 mb-10 w-full flex flex-col items-center relative z-10">
            <h1 class="text-[36px] sm:text-[40px] modern-heading text-white drop-shadow-lg smooth-transition">
                FILE<span class="text-[#51C0C0] neon-text-glow ml-1">UPLOAD</span>
            </h1>
            <div class="h-[3px] w-16 bg-[#51C0C0] mt-3 rounded-full shadow-[0_0_10px_#51C0C0] smooth-transition"></div>
        </div>

        <form method="POST" enctype="multipart/form-data" class="space-y-6 mt-auto mb-auto bg-[#0a0f1c]/50 p-6 sm:p-8 rounded-[2rem] border border-[#1e2738] shadow-[0_0_20px_rgba(0,0,0,0.3)] smooth-transition">
            
            <div class="border-2 border-dashed border-[#51C0C0]/30 rounded-xl p-6 text-center hover:border-[#51C0C0]/60 transition cursor-pointer smooth-transition" onclick="document.getElementById('fileInput').click()">
                <i class="fa-solid fa-cloud-arrow-up text-[#51C0C0] text-3xl mb-3 block"></i>
                <p class="text-[#51C0C0] font-mono text-sm mb-1">Click to select files</p>
                <p class="text-[#8a9bb3] text-xs">or drag and drop</p>
                <input type="file" id="fileInput" name="files[]" multiple hidden onchange="updateFileCount()">
            </div>

            <div id="fileList" class="space-y-2 max-h-[150px] overflow-y-auto custom-scroll"></div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="flex items-center text-[10px] text-[#51C0C0] uppercase tracking-widest font-mono mb-2 ml-1">
                        <i class="fa-solid fa-infinity mr-1"></i> Download Limit
                    </label>
                    <input type="number" name="limit" value="0" min="0" class="w-full bg-[#0d131f] border border-[#1e2738] rounded-xl px-3 py-2 text-sm text-white font-mono focus:outline-none focus:border-[#51C0C0] transition smooth-transition">
                </div>
                <div>
                    <label class="flex items-center text-[10px] text-[#51C0C0] uppercase tracking-widest font-mono mb-2 ml-1">
                        <i class="fa-solid fa-hourglass-end mr-1"></i> Duration
                    </label>
                    <input type="number" name="duration" value="24" min="1" class="w-full bg-[#0d131f] border border-[#1e2738] rounded-xl px-3 py-2 text-sm text-white font-mono focus:outline-none focus:border-[#51C0C0] transition smooth-transition">
                </div>
            </div>

            <div>
                <label class="flex items-center text-[10px] text-[#51C0C0] uppercase tracking-widest font-mono mb-2 ml-1">
                    <i class="fa-solid fa-clock mr-1"></i> Time Unit
                </label>
                <select name="time_unit" class="w-full bg-[#0d131f] border border-[#1e2738] rounded-xl px-3 py-2 text-sm text-white font-mono focus:outline-none focus:border-[#51C0C0] transition smooth-transition">
                    <option value="hours">Hours</option>
                    <option value="minutes">Minutes</option>
                </select>
            </div>

            <button type="submit" class="w-full bg-[#51C0C0] hover:bg-[#43a3a3] text-[#0a0f1c] font-bold py-4 rounded-xl transition btn-glow text-[13px] flex items-center justify-center uppercase tracking-widest mt-6 ripple smooth-transition">
                Generate Links <i class="fa-solid fa-rocket ml-2 text-[15px]"></i>
            </button>
        </form>

        <a href="?logout=1" class="text-center text-red-400 hover:text-red-300 text-xs font-mono mt-6 transition smooth-transition">
            <i class="fa-solid fa-right-from-bracket mr-1"></i> Logout
        </a>

        <?php endif; ?>

        <?php endif; ?>
    </div>

    <script>
        // Particle Background Generator
        function createParticles() {
            const container = document.getElementById('particleContainer');
            const particleCount = 50;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                particle.style.animationDuration = (Math.random() * 20 + 10) + 's';
                particle.style.animationDelay = Math.random() * 5 + 's';
                container.appendChild(particle);
            }
        }

        // Copy to Clipboard (Clean, without toast notification)
        function copyToClipboard(text, element) {
            navigator.clipboard.writeText(text).then(() => {
                const btn = element.tagName === 'BUTTON' ? element : element.closest('.copy-btn');
                if (btn) {
                    btn.classList.add('copied');
                    setTimeout(() => btn.classList.remove('copied'), 500);
                }
            });
        }

        // Update File Count
        function updateFileCount() {
            const input = document.getElementById('fileInput');
            const fileList = document.getElementById('fileList');
            fileList.innerHTML = '';
            
            if (input.files.length > 0) {
                for (let file of input.files) {
                    const item = document.createElement('div');
                    item.className = 'bg-[#0d131f] border border-[#1e2738] rounded-lg p-2 text-[#51C0C0] text-xs font-mono flex justify-between items-center';
                    item.innerHTML = `
                        <span class="truncate"><i class="fa-solid fa-file mr-1"></i>${file.name}</span>
                        <span class="text-[#8a9bb3]">${(file.size / 1024 / 1024).toFixed(2)} MB</span>
                    `;
                    fileList.appendChild(item);
                }
            }
        }

        // Drag and Drop
        document.addEventListener('dragover', (e) => {
            e.preventDefault();
            document.querySelector('[onclick="document.getElementById(\'fileInput\').click()"]')?.parentElement?.classList.add('border-[#51C0C0]');
        });

        document.addEventListener('dragleave', (e) => {
            e.preventDefault();
            document.querySelector('[onclick="document.getElementById(\'fileInput\').click()"]')?.parentElement?.classList.remove('border-[#51C0C0]');
        });

        document.addEventListener('drop', (e) => {
            e.preventDefault();
            const input = document.getElementById('fileInput');
            if (input) {
                input.files = e.dataTransfer.files;
                updateFileCount();
            }
        });

        // Initialize
        createParticles();

        // Keyboard Shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'k') {
                e.preventDefault();
                document.getElementById('fileInput')?.click();
            }
        });
    </script>
</body>
</html>
