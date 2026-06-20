<?php
/**
 * NET-CLOUD-CONFIG - Main Upload & Client Sniffer 
 * File Name: index.php
 */

session_start();
date_default_timezone_set('Africa/Tunis');

$uploadDir = __DIR__ . '/uploads/';
$dbFile = __DIR__ . '/db.json';
$bruteFile = __DIR__ . '/brute.json'; // ملف تسجيل محاولات الاختراق

if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
if (!file_exists($dbFile)) { file_put_contents($dbFile, json_encode([])); }
if (!file_exists($bruteFile)) { file_put_contents($bruteFile, json_encode([])); }

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
            die('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Access Denied</title><link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet"><style>body{background:#05080f;color:#ef4444;text-align:center;font-family:"Oswald",sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:20px;box-sizing:border-box; -webkit-tap-highlight-color:transparent; text-transform:uppercase;}img{max-width:100%;height:auto;border-radius:15px;box-shadow:0 0 20px rgba(0,0,0,0.5);margin-bottom:20px;max-height:60vh;border:1px solid #1e2738;}h1{margin:0 0 10px 0;font-size:28px;letter-spacing:1px;font-weight:700;}p{margin:0;color:#8a9bb3;font-size:16px;line-height:1.6;max-width:400px;}</style></head><body><img src="https://i.postimg.cc/rF68cX0x/IMG-20260619-033656.jpg" alt="Tutorial"></body></html>');
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
// UNIFIED UI Login System & Anti Brute-Force
// ==========================================
$adminUser = 'Admin';
$adminPass = '38sPcd6Ysr04NGVk'; 

$loginError = false;
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

// فحص وتنظيف الحظر القديم (بعد 15 دقيقة)
$bruteData = json_decode(file_get_contents($bruteFile), true) ?: [];
$saveBrute = false;
foreach ($bruteData as $bIp => $data) {
    if (time() - $data['last_attempt'] > 900) { 
        unset($bruteData[$bIp]); 
        $saveBrute = true; 
    }
}
if ($saveBrute) { file_put_contents($bruteFile, json_encode($bruteData)); }

// إيقاف التنفيذ إذا كان الـ IP محظوراً
if (isset($bruteData[$clientIP]) && $bruteData[$clientIP]['attempts'] >= 3) {
    header('HTTP/1.1 403 Forbidden');
    die('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Blocked</title><link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500&display=swap" rel="stylesheet"><style>body{background:#05080f;color:#ef4444;text-align:center;font-family:"Oswald",sans-serif;padding:50px;display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;margin:0;}</style></head><body><h1 style="font-size:50px;margin-bottom:10px;">🛑 IP BLOCKED</h1><p style="color:#8a9bb3;font-size:20px;">TOO MANY FAILED LOGIN ATTEMPTS.<br>PLEASE TRY AGAIN AFTER 15 MINUTES.</p></body></html>');
}

if (isset($_GET['logout'])) {
    unset($_SESSION['main_logged']);
    header("Location: index.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ui_login'])) {
    if ($_POST['username'] === $adminUser && $_POST['password'] === $adminPass) {
        $_SESSION['main_logged'] = true;
        // تصفير المحاولات عند تسجيل الدخول بنجاح
        if (isset($bruteData[$clientIP])) {
            unset($bruteData[$clientIP]);
            file_put_contents($bruteFile, json_encode($bruteData));
        }
        header("Location: index.php"); exit;
    } else {
        // تسجيل محاولة فاشلة
        $bruteData[$clientIP]['attempts'] = ($bruteData[$clientIP]['attempts'] ?? 0) + 1;
        $bruteData[$clientIP]['last_attempt'] = time();
        file_put_contents($bruteFile, json_encode($bruteData));
        
        $attemptsLeft = 3 - $bruteData[$clientIP]['attempts'];
        if ($attemptsLeft <= 0) {
            header("Refresh:0"); exit;
        } else {
            $loginError = "INVALID CREDENTIALS! " . $attemptsLeft . " ATTEMPTS LEFT";
        }
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
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NETCLOUD | SYSTEM</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * { -webkit-tap-highlight-color: transparent; } 
        body { 
            background-color: #030a14; 
            font-family: 'Oswald', sans-serif; 
            letter-spacing: 0.5px; 
            text-transform: uppercase; 
            overflow-x: hidden;
            color: #cbd5e1;
        }
        input, button, select, textarea { font-family: 'Oswald', sans-serif; letter-spacing: 0.5px; text-transform: uppercase; }
        
        .glass-panel { 
            background: rgba(10, 15, 30, 0.65); 
            backdrop-filter: blur(16px); 
            border: 1px solid rgba(56, 189, 248, 0.2); 
            box-shadow: 0 15px 35px rgba(0,0,0,0.5), inset 0 0 20px rgba(56, 189, 248, 0.05); 
        }
        .blue-glow-text { text-shadow: 0 0 10px rgba(56, 189, 248, 0.6), 0 0 20px rgba(56, 189, 248, 0.3); }
        input:-webkit-autofill { -webkit-box-shadow: 0 0 0 30px #080f1e inset !important; -webkit-text-fill-color: white !important; }
        
        .custom-scroll::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #38bdf8; border-radius: 10px; }
        
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }
        .animate-float { animation: float 4s ease-in-out infinite; }
        @keyframes pulse-ring { 0% { transform: scale(0.8); opacity: 0.8; } 100% { transform: scale(1.5); opacity: 0; } }
        .ring-effect::before { content: ''; position: absolute; inset: 0; border-radius: inherit; border: 2px solid #38bdf8; animation: pulse-ring 2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite; z-index: -1; }
        .bg-orb { position: absolute; border-radius: 50%; filter: blur(120px); z-index: -1; animation: float 6s ease-in-out infinite alternate; }
        
        input[type="number"]::-webkit-inner-spin-button, input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type="number"] { -moz-appearance: textfield; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-0 sm:p-4 relative">

    <div class="bg-orb top-[-10%] left-[-10%] w-[400px] h-[400px] bg-blue-600 opacity-20"></div>
    <div class="bg-orb bottom-[-10%] right-[-10%] w-[350px] h-[350px] bg-cyan-400 opacity-20" style="animation-delay: -3s;"></div>

    <?php if(!$isLogged): ?>
        <div class="w-full max-w-[26rem] flex flex-col items-center z-10 p-4">
            <div class="text-center mb-10 flex flex-col items-center">
                <div class="animate-float relative">
                    <div class="ring-effect w-20 h-20 rounded-[2rem] bg-gradient-to-tr from-blue-600 to-cyan-400 flex items-center justify-center shadow-[0_0_30px_rgba(56,189,248,0.5)]">
                        <i class="fa-solid fa-fingerprint text-white text-4xl"></i>
                    </div>
                </div>
                <h1 class="text-[34px] font-bold uppercase tracking-widest text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-300 drop-shadow-lg mt-6 blue-glow-text">
                   P A N E L
                </h1>
            </div>

            <form method="POST" class="glass-panel w-full rounded-[2.5rem] p-8 space-y-7 transition-all duration-500 hover:border-cyan-500/40 relative">
                <input type="hidden" name="ui_login" value="1">
                
                <?php if($loginError): ?>
                    <div class="bg-[#1a0f14]/80 border border-red-900/50 text-red-400 text-[13px] p-3 rounded-xl text-center font-bold tracking-widest shadow-lg">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i> <?= $loginError ?>
                    </div>
                <?php endif; ?>

                <div>
                    <label class="flex items-center text-[12px] text-cyan-400 font-bold tracking-widest mb-2 ml-4 uppercase">
                        <i class="fa-solid fa-user mr-2 text-[14px]"></i> USERNAME
                    </label>
                    <input type="text" name="username" required placeholder="" class="w-full bg-[#080f1e]/80 border border-blue-900/40 rounded-full px-6 py-4 text-[16px] text-white font-bold focus:outline-none focus:border-cyan-500 focus:shadow-[0_0_20px_rgba(56,189,248,0.2)] transition-all placeholder-blue-800/60 uppercase tracking-wide text-center">
                </div>

                <div>
                    <label class="flex items-center text-[12px] text-cyan-400 font-bold tracking-widest mb-2 ml-4 uppercase">
                        <i class="fa-solid fa-lock mr-2 text-[14px]"></i> PASSWORD
                    </label>
                    <input type="password" name="password" required placeholder="" class="w-full bg-[#080f1e]/80 border border-blue-900/40 rounded-full px-6 py-4 text-[16px] text-white font-bold focus:outline-none focus:border-cyan-500 focus:shadow-[0_0_20px_rgba(56,189,248,0.2)] transition-all placeholder-blue-800/60 tracking-widest text-center">
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-cyan-400 hover:from-blue-500 hover:to-cyan-300 text-white font-bold py-4 rounded-full transition-all duration-300 text-[18px] flex items-center justify-center tracking-widest mt-6 shadow-[0_10px_20px_rgba(56,189,248,0.3)] hover:shadow-[0_15px_30px_rgba(56,189,248,0.5)] uppercase transform hover:-translate-y-1">
                    LOGIN
                </button>
            </form>
        </div>

    <?php else: ?>
        <a href="admin.php" class="absolute top-6 left-6 w-11 h-11 flex items-center justify-center bg-[#080f1e]/80 border border-blue-900/40 hover:border-cyan-400 text-cyan-400 rounded-xl shadow-inner transition-all z-20" title="Radar Analytics">
            <i class="fa-solid fa-chart-line text-[16px]"></i>
        </a>
        <a href="?logout=1" class="absolute top-6 right-6 w-11 h-11 flex items-center justify-center bg-[#080f1e]/80 border border-red-900/40 hover:border-red-500 hover:bg-red-900/20 text-red-500 rounded-xl shadow-inner transition-all z-20" title="Logout">
            <i class="fa-solid fa-right-from-bracket text-[16px]"></i>
        </a>

        <div class="w-full max-w-[28rem] flex flex-col items-center justify-center z-10 text-slate-200 w-full px-2">
            
            <?php if(!empty($generatedLinks)): ?>
            <div class="text-center mb-8 w-full mt-4">
                <h1 class="text-[40px] font-bold text-white drop-shadow-lg uppercase tracking-widest">
                    LINK<span class="text-cyan-400 neon-text-glow ml-2">CODE</span>
                </h1>
            </div>

            <div class="bg-transparent mb-6 max-h-[55vh] overflow-y-auto custom-scroll flex flex-col gap-6 w-full pb-4 px-1">
                <?php foreach($generatedLinks as $idx => $item): ?>
                <div class="glass-panel rounded-[2rem] p-5 shadow-2xl w-full flex flex-col gap-5 shrink-0 transition-transform duration-300 hover:border-cyan-500/40">
                    
                    <div class="flex justify-between items-center px-1">
                        <div class="flex flex-col items-center justify-center w-1/3 border-r border-blue-900/40">
                            <div class="w-8 h-8 rounded border border-blue-900/40 flex items-center justify-center mb-2 bg-[#05080f]">
                                <i class="fa-solid fa-file-code text-cyan-400 text-[13px]"></i>
                            </div>
                            <span class="text-slate-400 text-[12px] font-bold truncate w-[90%] text-center uppercase">
                                <?= htmlspecialchars($item['original_name']) ?>
                            </span>
                        </div>
                        
                        <div class="flex flex-col items-center justify-center w-1/3 border-r border-blue-900/40">
                            <div class="w-8 h-8 rounded border border-blue-900/40 flex items-center justify-center mb-2 bg-[#05080f]">
                                <i class="fa-solid fa-download text-cyan-400 text-[13px]"></i>
                            </div>
                            <span class="text-slate-300 text-[14px] font-bold uppercase">
                                <?= $item['limit'] > 0 ? $item['limit'] : '∞' ?>
                            </span>
                        </div>
                        
                        <div class="flex flex-col items-center justify-center w-1/3">
                            <div class="w-8 h-8 rounded border border-blue-900/40 flex items-center justify-center mb-2 bg-[#05080f]">
                                <i class="fa-regular fa-clock text-cyan-400 text-[13px]"></i>
                            </div>
                            <span class="text-slate-400 text-[12px] font-bold uppercase">
                                <?= $item['duration'] ?> <?= strtoupper($item['time_unit'] === 'minutes' ? 'MIN' : 'HRS') ?>
                            </span>
                        </div>
                    </div>

                    <div class="bg-[#05080f] border border-blue-900/40 rounded-2xl py-4 px-4 flex items-center justify-center gap-3 overflow-x-auto custom-scroll shadow-inner">
                        <i class="fa-solid fa-link text-cyan-400 text-[13px]"></i>
                        <span class="text-cyan-400 text-[13px] font-bold whitespace-nowrap uppercase" id="link-<?= $idx ?>">
                            <?= $item['link'] ?>
                        </span>
                    </div>

                    <button onclick="copySingle('link-<?= $idx ?>', this)" class="w-full bg-[#05080f] hover:bg-blue-900/20 text-cyan-400 border border-blue-900/40 hover:border-cyan-500/50 py-4 rounded-full text-[15px] font-bold tracking-widest transition-all flex items-center justify-center gap-2.5">
                        <i class="fa-regular fa-copy text-[16px]"></i> COPY
                    </button>
                </div>
                <?php endforeach; ?>
            </div>

            <button onclick="window.location.href='index.php'" class="w-full bg-gradient-to-r from-blue-600 to-cyan-400 hover:from-blue-500 hover:to-cyan-300 text-white font-bold py-4 rounded-full transition-all text-[16px] tracking-widest flex items-center justify-center shadow-[0_10px_20px_rgba(56,189,248,0.2)]">
                 BACK
            </button>

            <script>
                function fallbackCopyText(text) {
                    var textArea = document.createElement("textarea");
                    textArea.value = text;
                    textArea.style.top = "0"; textArea.style.left = "0"; textArea.style.position = "fixed";
                    document.body.appendChild(textArea); textArea.focus(); textArea.select();
                    try { document.execCommand('copy'); } catch (err) {}
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
                            icon.className = 'fa-solid fa-check text-cyan-400 text-[16px]';
                            setTimeout(() => icon.className = 'fa-regular fa-copy text-[16px]', 1500);
                        }
                    }
                }

                function copySingle(id, btn) {
                    const linkText = document.getElementById(id).textContent.trim();
                    copyData(linkText, btn);
                }
            </script>

            <?php else: ?>
            <div class="text-center mt-4 mb-8 w-full">
                <h1 class="text-[40px] font-bold text-white drop-shadow-lg uppercase tracking-widest">
                    CLOUD<span class="text-cyan-400 neon-text-glow ml-2">CONFIG</span>
                </h1>
            </div>

            <form method="POST" enctype="multipart/form-data" class="glass-panel w-full rounded-[2.5rem] p-7 shadow-2xl flex flex-col gap-7 relative z-20 transition-all duration-300 hover:border-cyan-500/40">
                
                <div class="relative border border-dashed border-blue-900/50 bg-[#05080f] rounded-2xl p-8 text-center hover:border-cyan-400 transition-all group cursor-pointer">
                    <input type="file" name="files[]" multiple required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" id="fileInput" accept=".hc,.ovpn,.ehi,.nm">
                    <div class="flex flex-col items-center justify-center pointer-events-none">
                        <div class="w-14 h-14 rounded-full border border-blue-900/50 flex items-center justify-center mb-4 bg-[#080f1e] group-hover:border-cyan-500/50 transition-all">
                            <div class="w-10 h-10 rounded-full bg-blue-900/30 flex items-center justify-center group-hover:bg-cyan-500/20 transition-all">
                                <i class="fa-solid fa-cloud-arrow-up text-cyan-400 text-[18px]"></i>
                            </div>
                        </div>
                        <p class="text-[13px] text-blue-300/70 tracking-widest font-bold uppercase" id="fileName">SELECT OR DROP FILES HERE</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div>
                        <label class="flex items-center text-[12px] text-cyan-400 tracking-widest mb-2.5 font-bold ml-4 uppercase">
                            <i class="fa-solid fa-download mr-2 text-[14px]"></i> LIMIT
                        </label>
                        <input type="number" name="limit" placeholder="1" value="1" class="w-full bg-[#030a14] border border-blue-900/40 rounded-full px-6 py-4 text-[15px] text-white font-bold focus:outline-none focus:border-cyan-500 transition-all text-center">
                    </div>

                    <div>
                        <label class="flex items-center text-[12px] text-cyan-400 tracking-widest mb-2.5 font-bold ml-4 uppercase">
                            <i class="fa-regular fa-clock mr-2 text-[14px]"></i> VALIDITY TIME
                        </label>
                        <div class="grid grid-cols-2 gap-4">
                            <input type="number" name="duration" placeholder="5" value="5" min="1" required class="w-full bg-[#030a14] border border-blue-900/40 rounded-full px-6 py-4 text-[15px] text-center text-white font-bold focus:outline-none focus:border-cyan-500 transition-all">
                            
                            <div class="relative w-full">
                                <select name="time_unit" class="w-full h-full bg-[#030a14] border border-blue-900/40 rounded-full px-6 py-4 text-[15px] text-cyan-400 font-bold focus:outline-none focus:border-cyan-500 transition-all appearance-none cursor-pointer text-center uppercase">
                                    <option value="minutes" selected>MINUTES</option>
                                    <option value="hours">HOURS</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 pr-5 text-cyan-600">
                                    <i class="fa-solid fa-chevron-down text-[12px]"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-cyan-400 hover:from-blue-500 hover:to-cyan-300 text-white font-bold py-4 rounded-full transition-all duration-300 text-[16px] flex items-center justify-center tracking-widest mt-2 shadow-[0_10px_20px_rgba(56,189,248,0.2)] uppercase">
                    GENERATE LINK
                </button>
            </form>

            <script>
                document.getElementById('fileInput').addEventListener('change', function(e) {
                    const count = e.target.files.length;
                    const fileNameElem = document.getElementById('fileName');
                    if(count === 1) { 
                        fileNameElem.innerHTML = '<span class="text-cyan-400 font-bold">' + e.target.files[0].name.toUpperCase() + '</span>'; 
                    }
                    else if(count > 1) { 
                        fileNameElem.innerHTML = '<span class="text-cyan-400 font-bold">' + count + ' FILES SELECTED</span>'; 
                    } else {
                        fileNameElem.innerHTML = 'SELECT OR DROP FILES HERE';
                    }
                });
            </script>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</body>
</html>
