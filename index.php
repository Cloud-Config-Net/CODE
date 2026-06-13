<?php
/**
 * NET-CLOUD-CONFIG - Main Upload & Client Sniffer 
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
    $isHttpCustom = preg_match('/okhttp/i', $ua);
    
    if ($isHttpCustom) {
        $clientLabel = 'HTTP Custom App (Direct Secure Connection)';
        $isBrowser = false; 
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
            die('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Access Denied</title><style>body{background:#05080f;color:#ef4444;text-align:center;font-family:-apple-system,BlinkMacSystemFont,"Inter",sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:20px;box-sizing:border-box; -webkit-tap-highlight-color:transparent;}img{max-width:100%;height:auto;border-radius:15px;box-shadow:0 0 20px rgba(0,0,0,0.5);margin-bottom:20px;max-height:60vh;border:1px solid #1e2738;}h1{margin:0 0 10px 0;font-size:22px;letter-spacing:-0.04em;font-weight:700;}p{margin:0;color:#8a9bb3;font-size:13px;line-height:1.6;max-width:400px;}</style></head><body><img src="https://i.postimg.cc/TYfpcBy3/IMG-20260612-024446-049.jpg" alt="Tutorial"><h1>🛑 Access Denied</h1><p>This config link cannot be opened in a web browser.<br>Please copy the link and import it directly inside the <b>HTTP Custom</b> app as shown above.</p></body></html>');
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
        $loginError = "Invalid Login Credentials!";
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
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { -webkit-tap-highlight-color: transparent; } 
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Inter", Roboto, Helvetica, Arial, sans-serif; background-color: #05080f; overflow-x: hidden; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .modern-heading { font-weight: 700; letter-spacing: -0.04em; }
        .glass-panel { background: rgba(10, 15, 28, 0.85); backdrop-filter: blur(12px); border: 1px solid #1e2738; box-shadow: 0 0 40px rgba(0, 0, 0, 0.8), inset 0 0 20px rgba(81, 192, 192, 0.05); }
        .custom-scroll::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #51C0C0; border-radius: 10px; }
        .neon-text-glow { text-shadow: 0 0 15px rgba(81, 192, 192, 0.6), 0 0 30px rgba(81, 192, 192, 0.2); }
        .bg-animations { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; pointer-events: none; z-index: -1; overflow: hidden; }
        .floating-element { position: absolute; animation: float-up linear infinite; opacity: 0.15; filter: drop-shadow(0 0 10px rgba(81,192,192,0.5)); }
        @keyframes float-up { 0% { transform: translateY(100vh) rotate(0deg) scale(0.8); opacity: 0; } 10% { opacity: 0.2; } 90% { opacity: 0.2; } 100% { transform: translateY(-20vh) rotate(360deg) scale(1.2); opacity: 0; } }
    </style>
</head>
<body class="min-h-screen text-slate-200 flex items-center justify-center p-0 sm:p-4 relative">

    <div class="bg-animations">
        <div class="floating-element text-4xl" style="left: 10%; animation-duration: 15s; animation-delay: 0s;">☁️</div>
        <div class="floating-element text-3xl" style="left: 30%; animation-duration: 20s; animation-delay: 5s;">🚀</div>
        <div class="floating-element text-5xl text-[#51C0C0]" style="left: 70%; animation-duration: 18s; animation-delay: 2s;"><i class="fa-solid fa-microchip"></i></div>
        <div class="floating-element text-3xl" style="left: 85%; animation-duration: 25s; animation-delay: 8s;">⚡</div>
        <div class="floating-element text-4xl text-[#51C0C0]" style="left: 50%; animation-duration: 22s; animation-delay: 12s;"><i class="fa-solid fa-satellite-dish"></i></div>
        <div class="floating-element text-2xl" style="left: 20%; animation-duration: 19s; animation-delay: 15s;">🔒</div>
    </div>

    <div class="glass-panel w-full h-full min-h-screen sm:min-h-0 sm:max-w-[28rem] sm:rounded-[2rem] p-6 sm:p-8 relative flex flex-col justify-center transition-all duration-500 z-10">
        
        <?php if(!$isLogged): ?>
        <div class="text-center mt-6 mb-8 w-full flex flex-col items-center relative z-10">
            <div class="w-16 h-16 rounded-full border border-[#1e2738] bg-[#0f1524] flex items-center justify-center mx-auto mb-5 relative shadow-[0_0_15px_rgba(81,192,192,0.2)]">
                <div class="absolute inset-2 rounded-full border border-[#51C0C0]/30 bg-[#51C0C0]/5"></div>
                <i class="fa-solid fa-user-shield text-[#51C0C0] text-2xl z-10"></i>
            </div>
            <h2 class="text-[28px] modern-heading text-white drop-shadow-lg">SYSTEM <span class="text-[#51C0C0] neon-text-glow">LOGIN</span></h2>
            <div class="h-[3px] w-12 bg-[#51C0C0] mt-3 mx-auto rounded-full shadow-[0_0_10px_#51C0C0]"></div>
        </div>

        <form method="POST" class="space-y-6 mt-auto mb-auto bg-[#0a0f1c]/50 p-6 sm:p-8 rounded-[2rem] border border-[#1e2738] shadow-[0_0_20px_rgba(0,0,0,0.3)]">
            <input type="hidden" name="ui_login" value="1">
            
            <?php if($loginError): ?>
                <div class="bg-[#1a0f14] border border-red-900/50 text-red-400 text-xs p-3 rounded-xl text-center font-mono uppercase font-bold tracking-wide">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i> <?= $loginError ?>
                </div>
            <?php endif; ?>

            <div>
                <label class="flex items-center text-[10px] text-[#51C0C0] uppercase font-bold tracking-widest font-mono mb-2 ml-1">
                    <i class="fa-solid fa-user-astronaut mr-2"></i> USERNAME
                </label>
                <input type="text" name="username" required class="w-full bg-[#0d131f] border border-[#1e2738] rounded-xl px-4 py-4 text-sm text-white font-mono font-bold uppercase focus:outline-none focus:border-[#51C0C0] transition placeholder-[#2e3c50]" placeholder="ENTER ADMIN">
            </div>
            
            <div>
                <label class="flex items-center text-[10px] text-[#51C0C0] uppercase font-bold tracking-widest font-mono mb-2 ml-1">
                    <i class="fa-solid fa-key mr-2"></i> PASSWORD
                </label>
                <input type="password" name="password" required class="w-full bg-[#0d131f] border border-[#1e2738] rounded-xl px-4 py-4 text-sm text-white font-mono focus:outline-none focus:border-[#51C0C0] transition placeholder-[#2e3c50]" placeholder="••••••••••••">
            </div>
            
            <button type="submit" class="w-full bg-[#51C0C0] hover:bg-[#43a3a3] text-[#0a0f1c] font-bold py-4 rounded-xl transition text-[13px] flex items-center justify-center uppercase tracking-widest mt-6">
                ACCESS SYSTEM <i class="fa-solid fa-arrow-right-to-bracket ml-2 text-[15px]"></i>
            </button>
        </form>

        <?php else: ?>
        <a href="admin.php" class="absolute top-6 left-6 w-10 h-10 flex items-center justify-center bg-[#0d131f] border border-[#1e2738] hover:border-[#51C0C0] text-[#51C0C0] rounded-xl shadow-inner transition z-20" title="Radar Analytics">
            <i class="fa-solid fa-chart-line text-[15px]"></i>
        </a>

        <a href="?logout=1" class="absolute top-6 right-6 w-10 h-10 flex items-center justify-center bg-[#1a0f14] border border-red-900/50 hover:bg-red-900/20 text-red-400 rounded-xl shadow-inner transition z-20" title="Logout">
            <i class="fa-solid fa-right-from-bracket text-[15px]"></i>
        </a>

        <?php if(!empty($generatedLinks)): ?>
        <div class="text-center mt-12 mb-10 w-full flex flex-col items-center relative z-10">
            <h1 class="text-[32px] sm:text-[36px] modern-heading text-white drop-shadow-lg">
                LINK<span class="text-[#51C0C0] neon-text-glow ml-1">CODE</span>
            </h1>
            <div class="h-[3px] w-16 bg-[#51C0C0] mt-3 rounded-full shadow-[0_0_10px_#51C0C0]"></div>
        </div>

        <div class="bg-transparent mb-8 max-h-[55vh] sm:max-h-[26rem] overflow-y-auto custom-scroll flex flex-col gap-5 relative z-20 px-2 pb-4">
            <?php foreach($generatedLinks as $idx => $item): ?>
            <div class="bg-[#0a0f1c] border border-[#1e2738] rounded-2xl p-5 shadow-lg w-full overflow-hidden shrink-0">
                
                <div class="flex justify-between items-center bg-[#0d131f] rounded-xl py-4 px-2 mb-4 border border-[#141c2b]">
                    <div class="flex flex-col items-center justify-center w-1/3 border-r border-[#1e2738]" title="Original File">
                        <i class="fa-regular fa-file-code text-[#51C0C0] text-[18px] mb-2"></i>
                        <span class="text-[#8a9bb3] text-[10px] font-mono font-bold uppercase truncate w-[90%] text-center tracking-wide">
                            <?= htmlspecialchars($item['original_name']) ?>
                        </span>
                    </div>
                    <div class="flex flex-col items-center justify-center w-1/3 border-r border-[#1e2738]" title="Limit">
                        <i class="fa-solid fa-download text-[#51C0C0] text-[18px] mb-2"></i>
                        <span class="text-[#8a9bb3] text-[11px] font-mono font-bold uppercase tracking-wide">
                            <?= $item['limit'] > 0 ? $item['limit'] : '∞' ?>
                        </span>
                    </div>
                    <div class="flex flex-col items-center justify-center w-1/3" title="Validity Time">
                        <i class="fa-regular fa-rectangle-xmark text-[#51C0C0] text-[18px] mb-2"></i>
                        <span class="text-[#8a9bb3] text-[11px] font-mono font-bold uppercase tracking-wide">
                            <?= $item['duration'] ?> <?= $item['time_unit'] === 'minutes' ? 'MIN' : 'HR' ?>
                        </span>
                    </div>
                </div>
                
                <div class="bg-[#0d131f] border border-[#1e2738] rounded-xl py-4 px-4 mb-4 flex items-center justify-start sm:justify-center gap-3 overflow-x-auto custom-scroll">
                    <i class="fa-solid fa-link text-[#51C0C0] text-[14px]"></i>
                    <span class="text-[#51C0C0] text-[13px] font-mono font-bold whitespace-nowrap tracking-wide" id="link-<?= $idx ?>">
                        <?= $item['link'] ?>
                    </span>
                </div>

                <div class="flex justify-center">
                    <button onclick="copySingle('link-<?= $idx ?>', this)" class="w-full sm:w-[60%] bg-[#51C0C0]/10 hover:bg-[#51C0C0]/20 text-[#51C0C0] border border-[#51C0C0]/30 py-3 rounded-xl text-[12px] font-bold uppercase tracking-widest transition-colors flex items-center justify-center gap-2">
                        <i class="fa-regular fa-copy text-[15px]"></i> COPY
                    </button>
                </div>

            </div>
            <?php endforeach; ?>
        </div>

        <div class="flex gap-4 mt-auto">
            <button onclick="window.location.href='/'" class="flex-[1] bg-[#0a0f1c] border border-[#1e2738] hover:bg-[#141c2b] text-white font-bold py-4 rounded-xl transition text-[12px] uppercase tracking-wider">
                 BACK
            </button>
            <button onclick="copyAll()" class="flex-[2] bg-[#51C0C0] hover:bg-[#43a3a3] text-[#0a0f1c] font-bold py-4 rounded-xl transition text-[12px] uppercase tracking-wider flex items-center justify-center">
                 <i class="fa-solid fa-clone mr-2 text-[15px]"></i> COPY ALL
            </button>
        </div>

        <div id="toast" class="fixed bottom-10 right-1/2 translate-x-1/2 sm:right-5 sm:translate-x-0 bg-[#51C0C0] text-[#0a0f1c] px-6 py-3 rounded-lg shadow-[0_0_20px_rgba(81,192,192,0.4)] transform translate-y-20 opacity-0 transition-all duration-300 z-50 text-xs font-bold uppercase tracking-wide border border-[#43a3a3] pointer-events-none">
            <i class="fa-solid fa-check-circle mr-1"></i> COPIED!
        </div>

        <script>
            function showToast() {
                const toast = document.getElementById('toast');
                toast.classList.remove('translate-y-20', 'opacity-0');
                setTimeout(() => toast.classList.add('translate-y-20', 'opacity-0'), 2000);
            }

            function fallbackCopyText(text) {
                var textArea = document.createElement("textarea");
                textArea.value = text;
                textArea.style.top = "0";
                textArea.style.left = "0";
                textArea.style.position = "fixed";
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                } catch (err) {}
                document.body.removeChild(textArea);
            }

            function copyData(text, btnElement) {
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).catch(() => fallbackCopyText(text));
                } else {
                    fallbackCopyText(text);
                }
                
                if (btnElement) {
                    const icon = btnElement.querySelector('i');
                    if(icon) {
                        icon.className = 'fa-solid fa-check text-[#51C0C0] text-[16px]';
                        setTimeout(() => icon.className = 'fa-regular fa-copy text-[16px]', 1500);
                    }
                }
                showToast();
            }

            function copySingle(id, btn) {
                const linkText = document.getElementById(id).innerText.trim();
                copyData(linkText, btn);
            }

            function copyAll() {
                let allLinks = [];
                <?php foreach($generatedLinks as $idx => $item): ?> 
                allLinks.push("<?= $item['link'] ?>"); 
                <?php endforeach; ?>
                copyData(allLinks.join("\n"), null);
            }
        </script>

        <?php else: ?>
        <div class="text-center mt-12 mb-10 w-full flex flex-col items-center relative z-10">
            <h1 class="text-[32px] sm:text-[36px] modern-heading text-white drop-shadow-lg">
                CLOUD<span class="text-[#51C0C0] neon-text-glow ml-1">CONFIG</span>
            </h1>
            <div class="h-[3px] w-16 bg-[#51C0C0] mt-3 rounded-full shadow-[0_0_10px_#51C0C0]"></div>
        </div>

        <form method="POST" enctype="multipart/form-data" class="space-y-6 mt-auto mb-auto relative z-20 w-full">
            
            <div class="relative border border-dashed border-[#1e2738] rounded-2xl p-10 sm:p-12 text-center hover:border-[#51C0C0] transition-colors group cursor-pointer bg-transparent">
                <input type="file" name="files[]" multiple required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" id="fileInput" accept=".hc,.ovpn,.ehi,.nm">
                
                <div class="flex flex-col items-center justify-center pointer-events-none">
                    <div class="w-16 h-16 rounded-full border border-[#1e2738] flex items-center justify-center mb-4 transition-colors">
                        <div class="w-12 h-12 rounded-full bg-[#0d131f] flex items-center justify-center group-hover:bg-[#151e2e] transition-colors">
                            <i class="fa-solid fa-cloud-arrow-up text-[#51C0C0] text-[18px]"></i>
                        </div>
                    </div>
                    <p class="text-[14px] font-mono text-[#8a9bb3] tracking-wide" id="fileName">Select or Drop Files Here</p>
                </div>
            </div>

            <div class="space-y-5 pt-2">
                <div>
                    <label class="flex items-center text-[10px] text-[#51C0C0] uppercase tracking-widest font-mono mb-2 font-bold">
                        <i class="fa-solid fa-download mr-2 text-[12px]"></i> LIMIT
                    </label>
                    <input type="number" name="limit" placeholder="Limit (e.g. 1)" class="w-full bg-transparent border border-[#1e2738] rounded-xl px-4 py-3.5 text-[14px] text-[#8a9bb3] font-mono focus:outline-none focus:border-[#51C0C0] transition placeholder-[#2e3c50]">
                </div>

                <div>
                    <label class="flex items-center text-[10px] text-[#51C0C0] uppercase tracking-widest font-mono mb-2 font-bold">
                        <i class="fa-regular fa-clock mr-2 text-[12px]"></i> VALIDITY TIME
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <input type="number" name="duration" placeholder="Duration" value="" min="1" required class="w-full bg-transparent border border-[#1e2738] rounded-xl px-4 py-3.5 text-[14px] text-center text-white font-mono focus:outline-none focus:border-[#51C0C0] transition placeholder-[#2e3c50]">
                        
                        <div class="relative w-full">
                            <select name="time_unit" class="w-full h-full bg-transparent border border-[#1e2738] rounded-xl px-4 py-3.5 text-[14px] text-[#51C0C0] font-mono focus:outline-none focus:border-[#51C0C0] transition appearance-none cursor-pointer text-center">
                                <option value="minutes" class="bg-[#05080f]">Minutes</option>
                                <option value="hours" selected class="bg-[#05080f]">Hours</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-[#425975]">
                                <i class="fa-solid fa-chevron-down text-[12px]"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full bg-[#51C0C0] hover:bg-[#43a3a3] text-[#0a0f1c] font-bold py-3.5 rounded-xl transition text-[13px] flex items-center justify-center uppercase tracking-widest mt-2">
                <i class="fa-solid fa-gear mr-2 text-[15px]"></i> GENERATE LINK
            </button>
        </form>

        <script>
            document.getElementById('fileInput').addEventListener('change', function(e) {
                const count = e.target.files.length;
                const fileNameElem = document.getElementById('fileName');
                if(count === 1) { 
                    fileNameElem.innerHTML = '<span class="text-[#51C0C0]">' + e.target.files[0].name + '</span>'; 
                }
                else if(count > 1) { 
                    fileNameElem.innerHTML = '<span class="text-[#51C0C0]">' + count + ' Files Selected</span>'; 
                } else {
                    fileNameElem.innerHTML = 'Select or Drop Files Here';
                }
            });
        </script>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
