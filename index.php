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
        /* إعدادات الخلفية والخط */
        * { -webkit-tap-highlight-color: transparent; } 
        body { 
            background-color: #05080f; /* لون خلفية داكن جداً يطابق صورتك */
            font-family: 'Oswald', sans-serif; 
            letter-spacing: 1px; 
            overflow-x: hidden; 
        }
        input, button, select { font-family: 'Oswald', sans-serif; letter-spacing: 1px; }

        /* التصميم الزجاجي الرئيسي */
        .glass-panel {
            background: #0a0f1c; /* لون الصندوق */
            border: 1px solid #1e2738; /* إطار أزرق خافت */
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
        }

        /* توهج النيون للعنوان (CLOUD CONFIG) */
        .blue-glow-text {
            text-shadow: 0 0 15px rgba(103, 232, 249, 0.6);
        }

        /* إصلاح خلفية الإكمال التلقائي */
        input:-webkit-autofill { 
            -webkit-box-shadow: 0 0 0 30px #05080f inset !important; 
            -webkit-text-fill-color: white !important; 
        }
        input[type="number"]::-webkit-inner-spin-button, input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type="number"] { -moz-appearance: textfield; }

        .custom-scroll::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #67e8f9; border-radius: 10px; }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4 relative">

    <?php if(!$isLogged): ?>
        <div class="w-full max-w-[24rem] flex flex-col items-center z-10">
            <div class="text-center mb-8 w-full">
                <h1 class="text-[42px] font-bold uppercase tracking-widest blue-glow-text">
                    <span class="text-[#93c5fd]">SYSTEM</span> <span class="text-[#67e8f9]">LOGIN</span>
                </h1>
            </div>

            <form method="POST" class="glass-panel w-full rounded-[2.5rem] p-7 sm:p-8 space-y-7 transition-all duration-500 hover:border-[#67e8f9]/40">
                <input type="hidden" name="ui_login" value="1">
                
                <?php if($loginError): ?>
                    <div class="bg-[#05080f] border border-red-900/50 text-red-400 text-[13px] p-3 rounded-2xl text-center font-bold tracking-widest shadow-lg">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i> <?= $loginError ?>
                    </div>
                <?php endif; ?>

                <div>
                    <label class="flex items-center text-[12px] text-[#67e8f9] font-bold tracking-widest mb-2.5 ml-2 uppercase">
                        <i class="fa-solid fa-user mr-2 text-[14px]"></i> USERNAME
                    </label>
                    <input type="text" name="username" required placeholder="" class="w-full bg-[#05080f] border border-[#1e2738] rounded-full px-6 py-4 text-[16px] text-white font-bold focus:outline-none focus:border-[#67e8f9] transition-all text-center">
                </div>

                <div>
                    <label class="flex items-center text-[12px] text-[#67e8f9] font-bold tracking-widest mb-2.5 ml-2 uppercase">
                        <i class="fa-solid fa-lock mr-2 text-[14px]"></i> PASSWORD
                    </label>
                    <input type="password" name="password" required placeholder="" class="w-full bg-[#05080f] border border-[#1e2738] rounded-full px-6 py-4 text-[16px] text-white font-bold focus:outline-none focus:border-[#67e8f9] transition-all text-center">
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-[#3b82f6] to-[#06b6d4] text-white font-bold py-4 rounded-full transition-all duration-300 text-[16px] tracking-widest mt-6 shadow-[0_10px_20px_rgba(6,182,212,0.2)] hover:shadow-[0_15px_30px_rgba(6,182,212,0.4)] uppercase">
                    LOGIN
                </button>
            </form>
        </div>

    <?php else: ?>
        <a href="admin.php" class="absolute top-6 left-6 w-11 h-11 flex items-center justify-center bg-[#05080f] border border-[#1e2738] hover:border-[#67e8f9] text-[#67e8f9] rounded-xl shadow-inner transition-all z-20" title="Radar Analytics">
            <i class="fa-solid fa-chart-line text-[16px]"></i>
        </a>
        <a href="?logout=1" class="absolute top-6 right-6 w-11 h-11 flex items-center justify-center bg-[#05080f] border border-[#1e2738] hover:border-red-500 text-red-500 rounded-xl shadow-inner transition-all z-20" title="Logout">
            <i class="fa-solid fa-right-from-bracket text-[16px]"></i>
        </a>

        <div class="w-full max-w-[24rem] flex flex-col items-center z-10 w-full">
            
            <?php if(!empty($generatedLinks)): ?>
            <div class="text-center mb-8 w-full mt-4">
                <h1 class="text-[42px] font-bold uppercase tracking-widest blue-glow-text">
                    <span class="text-[#93c5fd]">LINK</span> <span class="text-[#67e8f9]">CODE</span>
                </h1>
            </div>

            <div class="bg-transparent mb-6 max-h-[55vh] overflow-y-auto custom-scroll flex flex-col gap-6 w-full pb-4 px-1">
                <?php foreach($generatedLinks as $idx => $item): ?>
                <div class="glass-panel rounded-[2rem] p-5 shadow-2xl w-full flex flex-col gap-5 shrink-0 transition-all duration-300 hover:border-[#67e8f9]/50">
                    
                    <div class="flex justify-between items-center px-1">
                        <div class="flex flex-col items-center justify-center w-1/3 border-r border-[#1e2738]">
                            <div class="w-8 h-8 rounded border border-[#1e2738] flex items-center justify-center mb-2 bg-[#05080f]">
                                <i class="fa-solid fa-file-code text-[#67e8f9] text-[13px]"></i>
                            </div>
                            <span class="text-slate-400 text-[12px] font-bold truncate w-[90%] text-center uppercase">
                                <?= htmlspecialchars($item['original_name']) ?>
                            </span>
                        </div>
                        
                        <div class="flex flex-col items-center justify-center w-1/3 border-r border-[#1e2738]">
                            <div class="w-8 h-8 rounded border border-[#1e2738] flex items-center justify-center mb-2 bg-[#05080f]">
                                <i class="fa-solid fa-download text-[#67e8f9] text-[13px]"></i>
                            </div>
                            <span class="text-slate-300 text-[14px] font-bold uppercase">
                                <?= $item['limit'] > 0 ? $item['limit'] : '∞' ?>
                            </span>
                        </div>
                        
                        <div class="flex flex-col items-center justify-center w-1/3">
                            <div class="w-8 h-8 rounded border border-[#1e2738] flex items-center justify-center mb-2 bg-[#05080f]">
                                <i class="fa-regular fa-clock text-[#67e8f9] text-[13px]"></i>
                            </div>
                            <span class="text-slate-400 text-[12px] font-bold uppercase">
                                <?= $item['duration'] ?> <?= strtoupper($item['time_unit'] === 'minutes' ? 'MIN' : 'HRS') ?>
                            </span>
                        </div>
                    </div>

                    <div class="bg-[#05080f] border border-[#1e2738] rounded-2xl py-4 px-4 flex items-center justify-center gap-3 overflow-x-auto custom-scroll shadow-inner">
                        <i class="fa-solid fa-link text-[#67e8f9] text-[13px]"></i>
                        <span class="text-[#67e8f9] text-[13px] font-bold whitespace-nowrap tracking-wider uppercase" id="link-<?= $idx ?>">
                            <?= $item['link'] ?>
                        </span>
                    </div>

                    <button onclick="copySingle('link-<?= $idx ?>', this)" class="w-full bg-[#05080f] hover:bg-[#1e2738] text-[#67e8f9] border border-[#1e2738] hover:border-[#67e8f9]/50 py-4 rounded-full text-[15px] font-bold tracking-widest transition-all flex items-center justify-center gap-2.5">
                        <i class="fa-regular fa-copy text-[16px]"></i> COPY
                    </button>
                </div>
                <?php endforeach; ?>
            </div>

            <button onclick="window.location.href='index.php'" class="w-full bg-gradient-to-r from-[#3b82f6] to-[#06b6d4] text-white font-bold py-4 rounded-full transition-all text-[16px] tracking-widest flex items-center justify-center shadow-[0_10px_20px_rgba(6,182,212,0.2)] hover:shadow-[0_15px_30px_rgba(6,182,212,0.4)]">
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
                            icon.className = 'fa-solid fa-check text-[#67e8f9] text-[16px]';
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
            <div class="text-center mb-8 w-full mt-4">
                <h1 class="text-[42px] font-bold uppercase tracking-widest blue-glow-text">
                    <span class="text-[#93c5fd]">CLOUD</span> <span class="text-[#67e8f9]">CONFIG</span>
                </h1>
            </div>

            <form method="POST" enctype="multipart/form-data" class="glass-panel w-full rounded-[2.5rem] p-7 space-y-7 shadow-2xl transition-all duration-300 hover:border-[#67e8f9]/40">
                
                <div class="relative border border-dashed border-[#1e2738] bg-[#05080f] rounded-2xl p-8 text-center hover:border-[#67e8f9] transition-all group cursor-pointer">
                    <input type="file" name="files[]" multiple required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" id="fileInput" accept=".hc,.ovpn,.ehi,.nm">
                    <div class="flex flex-col items-center justify-center pointer-events-none">
                        <div class="w-14 h-14 rounded-full border border-[#1e2738] flex items-center justify-center mb-4 bg-[#0a0f1c] group-hover:border-[#67e8f9]/50 transition-all">
                            <i class="fa-solid fa-cloud-arrow-up text-[#67e8f9] text-[20px]"></i>
                        </div>
                        <p class="text-[13px] text-slate-400 tracking-widest font-bold uppercase" id="fileName">SELECT OR DROP FILES HERE</p>
                    </div>
                </div>

                <div>
                    <label class="flex items-center text-[12px] text-[#67e8f9] tracking-widest mb-2.5 font-bold ml-2 uppercase">
                        <i class="fa-solid fa-download mr-2 text-[14px]"></i> LIMIT
                    </label>
                    <input type="number" name="limit" placeholder="1" value="1" class="w-full bg-[#05080f] border border-[#1e2738] rounded-full px-6 py-4 text-[16px] text-white font-bold focus:outline-none focus:border-[#67e8f9] text-center transition-all">
                </div>

                <div>
                    <label class="flex items-center text-[12px] text-[#67e8f9] tracking-widest mb-2.5 font-bold ml-2 uppercase">
                        <i class="fa-regular fa-clock mr-2 text-[14px]"></i> VALIDITY TIME
                    </label>
                    <div class="grid grid-cols-2 gap-4">
                        <input type="number" name="duration" placeholder="5" value="5" min="1" required class="w-full bg-[#05080f] border border-[#1e2738] rounded-full px-6 py-4 text-[16px] text-center text-white font-bold focus:outline-none focus:border-[#67e8f9] transition-all">
                        
                        <div class="relative w-full">
                            <select name="time_unit" class="w-full h-full bg-[#05080f] border border-[#1e2738] rounded-full px-6 py-4 text-[15px] text-[#67e8f9] font-bold focus:outline-none focus:border-[#67e8f9] appearance-none cursor-pointer text-center uppercase transition-all">
                                <option value="minutes" selected>MINUTES</option>
                                <option value="hours">HOURS</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 pr-5 text-[#67e8f9]">
                                <i class="fa-solid fa-chevron-down text-[12px]"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-[#3b82f6] to-[#06b6d4] text-white font-bold py-4 rounded-full transition-all duration-300 text-[16px] tracking-widest mt-2 shadow-[0_10px_20px_rgba(6,182,212,0.2)] hover:shadow-[0_15px_30px_rgba(6,182,212,0.4)] uppercase">
                    GENERATE LINK
                </button>
            </form>

            <script>
                document.getElementById('fileInput').addEventListener('change', function(e) {
                    const count = e.target.files.length;
                    const fileNameElem = document.getElementById('fileName');
                    if(count === 1) { 
                        fileNameElem.innerHTML = '<span class="text-[#67e8f9] font-bold">' + e.target.files[0].name.toUpperCase() + '</span>'; 
                    }
                    else if(count > 1) { 
                        fileNameElem.innerHTML = '<span class="text-[#67e8f9] font-bold">' + count + ' FILES SELECTED</span>'; 
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
