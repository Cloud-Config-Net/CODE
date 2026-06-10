<?php
/**
 * NET-CLOUD-CONFIG - Main Upload & Client Sniffer (English)
 * File Name: index.php
 */

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
        
        // Expiration or Limits check
        if (time() > $entry['expires'] || ($entry['limit'] > 0 && $entry['downloads'] >= $entry['limit'])) {
            $db[$id]['logs'][] = ['ip' => $ip, 'ua' => $ua, 'client' => $clientLabel . ' (Expired)', 'time' => time(), 'status' => 'Failed'];
            file_put_contents($dbFile, json_encode($db));
            @unlink($entry['real_path']); unset($db[$id]); file_put_contents($dbFile, json_encode($db)); 
            header("HTTP/1.1 410 Gone"); die("This link has expired or reached its download limit.");
        }
        
        // Block Browser attempts
        if ($isBrowser) {
            $db[$id]['logs'][] = ['ip' => $ip, 'ua' => $ua, 'client' => $clientLabel, 'time' => time(), 'status' => 'Blocked'];
            file_put_contents($dbFile, json_encode($db));
            header("HTTP/1.1 403 Forbidden"); 
            die('<!DOCTYPE html><html><body style="background:#0f172a; color:#ef4444; text-align:center; padding-top:20%; font-family:sans-serif;"><h1>🛑 403 Access Denied</h1><p>This config file must be imported inside the HTTP Custom app directly.</p></body></html>');
        }

        // Success Download (HTTP Custom)
        $db[$id]['downloads']++;
        $db[$id]['logs'][] = ['ip' => $ip, 'ua' => $ua, 'client' => $clientLabel, 'time' => time(), 'status' => 'Success'];
        file_put_contents($dbFile, json_encode($db));
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($entry['original_name']) . '"');
        header('Content-Length: ' . filesize($entry['real_path']));
        readfile($entry['real_path']); exit;
    }
    header("HTTP/1.1 404 Not Found"); die("File Not Found.");
}

// ==========================================
// Multi-Upload Handling Logic
// ==========================================
$generatedLinks = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['files'])) {
    $limit = (int)$_POST['limit']; 
    $duration = (int)$_POST['duration'];
    $timeUnit = $_POST['time_unit'] ?? 'hours';
    
    // Calculate expiration based on Minutes or Hours
    $seconds = ($timeUnit === 'minutes') ? ($duration * 60) : ($duration * 3600);
    $fileCount = count($_FILES['files']['name']);
    
    for ($i = 0; $i < $fileCount; $i++) {
        if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['files']['tmp_name'][$i];
            $originalName = basename($_FILES['files']['name'][$i]);
            $targetPath = $uploadDir . bin2hex(random_bytes(16)) . '.dat';
            
            if (move_uploaded_file($tmpName, $targetPath)) {
                $shortId = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 5);
                $db[$shortId] = [
                    'original_name' => $originalName,
                    'real_path' => $targetPath,
                    'limit' => $limit,
                    'downloads' => 0,
                    'expires' => time() + $seconds,
                    'upload_date' => time(),
                    'logs' => []
                ];
                $link = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . '/' . $shortId . '.hc';
                $generatedLinks[] = ['original_name' => $originalName, 'link' => $link];
            }
        }
    }
    if (!empty($generatedLinks)) { file_put_contents($dbFile, json_encode($db)); }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>CLOUD CONFIG</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #05080f; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .glass-panel { 
            background: #0a0f1c; 
            border: 1px solid #141c2b; 
        }
        
        /* تصميم شريط التمرير الجديد ليطابق الصورة */
        .custom-scroll::-webkit-scrollbar { width: 5px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #51C0C0; border-radius: 10px; }
        
        .neon-text-glow { text-shadow: 0 0 12px rgba(81, 192, 192, 0.4); }
        .btn-glow { box-shadow: 0 0 15px rgba(81, 192, 192, 0.15); }
    </style>
</head>
<body class="min-h-screen text-slate-200 flex items-center justify-center p-0 sm:p-4">

    <div class="glass-panel w-full h-full min-h-screen sm:min-h-0 sm:max-w-[28rem] sm:rounded-[2rem] p-6 sm:p-8 relative flex flex-col justify-center shadow-2xl">
        
        <div class="absolute top-6 left-6 w-9 h-9 flex items-center justify-center bg-[#0d131f] border border-[#1e2738] rounded-xl shadow-inner">
            <i class="fa-solid fa-microchip text-[#2d4b69]"></i>
        </div>

        <div class="text-center mt-6 mb-10">
            <h1 class="text-[28px] sm:text-[32px] font-bold tracking-widest text-white">
                CLOUD<span class="text-[#51C0C0] neon-text-glow">CONFIG</span>
            </h1>
        </div>

        <?php if(!empty($generatedLinks)): ?>
        <div class="bg-[#0a0f1c] border border-[#141c2b] rounded-2xl p-4 sm:p-6 mb-8 max-h-[55vh] sm:max-h-[26rem] overflow-y-auto custom-scroll flex flex-col gap-8">
            <?php foreach($generatedLinks as $idx => $item): ?>
            <div class="flex flex-col items-center">
                <span class="text-[#64748b] text-[13px] mb-3 font-mono"><?= htmlspecialchars($item['original_name']) ?></span>
                
                <div class="flex items-center w-full gap-3">
                    <div class="flex-1 text-center">
                        <a href="<?= $item['link'] ?>" target="_blank" class="text-[#cbd5e1] text-[12px] sm:text-[13px] underline decoration-[#334155] underline-offset-[6px] font-mono leading-[1.8] break-all block px-1" id="link-<?= $idx ?>">
                            <?= $item['link'] ?>
                        </a>
                    </div>
                    
                    <button onclick="copySingle('link-<?= $idx ?>', this)" class="bg-[#0f1524] hover:bg-[#1e2738] text-slate-300 w-11 h-11 rounded-xl border border-[#1e2738] transition flex-shrink-0 flex items-center justify-center shadow-sm">
                        <i class="fa-regular fa-copy text-[15px]"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="flex gap-4 mt-auto">
            <button onclick="window.location.href='/'" class="flex-[1] bg-[#0a0f1c] border border-[#1e2738] hover:bg-[#141c2b] text-white font-bold py-4 rounded-xl transition text-[12px] uppercase tracking-wider">
                 BACK
            </button>
            <button onclick="copyAll()" class="flex-[2] bg-[#51C0C0] hover:bg-[#43a3a3] text-[#0a0f1c] font-bold py-4 rounded-xl transition btn-glow text-[12px] uppercase tracking-wider flex items-center justify-center">
                 <i class="fa-regular fa-copy mr-2 text-[15px]"></i> COPY ALL LINKS
            </button>
        </div>

        <div id="toast" class="fixed bottom-10 right-1/2 translate-x-1/2 sm:right-5 sm:translate-x-0 bg-[#51C0C0] text-[#0a0f1c] px-6 py-3 rounded-lg shadow-lg transform translate-y-20 opacity-0 transition-all duration-300 z-50 text-xs font-bold uppercase tracking-wide border border-[#43a3a3]">
            Links Copied!
        </div>

        <script>
            function showToast() {
                const toast = document.getElementById('toast');
                toast.classList.remove('translate-y-20', 'opacity-0');
                setTimeout(() => toast.classList.add('translate-y-20', 'opacity-0'), 2000);
            }
            function copySingle(id, btn) {
                const link = document.getElementById(id).innerText.trim();
                navigator.clipboard.writeText(link);
                const icon = btn.querySelector('i');
                icon.className = 'fa-solid fa-check text-[#51C0C0] text-[15px]';
                setTimeout(() => icon.className = 'fa-regular fa-copy text-[15px]', 1500);
                showToast();
            }
            function copyAll() {
                let allLinks = [];
                <?php foreach($generatedLinks as $idx => $item): ?> allLinks.push("<?= $item['link'] ?>"); <?php endforeach; ?>
                navigator.clipboard.writeText(allLinks.join("\n"));
                showToast();
            }
        </script>

        <?php else: ?>
        <form method="POST" enctype="multipart/form-data" class="space-y-6 mt-auto mb-auto">
            
            <div class="relative border-[1.5px] border-dashed border-[#1e2738] rounded-2xl p-10 text-center hover:border-[#51C0C0] transition-colors duration-300 group cursor-pointer bg-gradient-to-b from-[#0d131f] to-[#0a0f1c]">
                <input type="file" name="files[]" multiple required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" id="fileInput" accept=".hc,.ovpn,.ehi,.nm">
                
                <div class="flex flex-col items-center justify-center">
                    <div class="w-[64px] h-[64px] rounded-full border border-[#1e2738] bg-[#0f1524] flex items-center justify-center mb-5 relative group-hover:border-[#51C0C0]/50 transition-colors shadow-lg">
                        <div class="absolute inset-2 rounded-full border border-[#51C0C0]/30 bg-[#51C0C0]/5"></div>
                        <i class="fa-solid fa-cloud-arrow-up text-[#51C0C0] text-2xl z-10"></i>
                    </div>
                    <p class="text-[14px] font-mono text-[#8a9bb3] tracking-wide" id="fileName">Select or Drop Files Here</p>
                </div>
            </div>

            <div class="space-y-5 pt-2">
                <div>
                    <label class="flex items-center text-[11px] text-[#51C0C0] uppercase tracking-widest font-mono mb-2">
                        <i class="fa-solid fa-download mr-2 text-[12px]"></i> Limit
                    </label>
                    <input type="number" name="limit" placeholder="Limit (e.g. 1)" class="w-full bg-[#0d131f] border border-[#1e2738] rounded-xl px-5 py-4 text-[15px] text-[#8a9bb3] focus:outline-none focus:border-[#51C0C0] transition font-mono placeholder-[#2e3c50]">
                </div>

                <div>
                    <label class="flex items-center text-[11px] text-[#51C0C0] uppercase tracking-widest font-mono mb-2">
                        <i class="fa-regular fa-clock mr-2 text-[12px]"></i> Validity Time
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <input type="number" name="duration" placeholder="Duration" value="" min="1" required class="w-full bg-[#0d131f] border border-[#1e2738] rounded-xl px-4 py-4 text-[15px] text-center text-white focus:outline-none focus:border-[#51C0C0] transition font-mono placeholder-[#2e3c50]">
                        
                        <div class="relative w-full">
                            <select name="time_unit" class="w-full h-full bg-[#0d131f] border border-[#1e2738] rounded-xl px-4 py-4 text-[15px] text-[#51C0C0] font-mono focus:outline-none focus:border-[#51C0C0] transition appearance-none cursor-pointer" style="text-align-last: center;">
                                <option value="minutes">Minutes</option>
                                <option value="hours" selected>Hours</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-[#425975]">
                                <i class="fa-solid fa-chevron-down text-[12px]"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full bg-[#51C0C0] hover:bg-[#43a3a3] text-[#0a0f1c] font-bold py-4 rounded-xl transition btn-glow text-[13px] flex items-center justify-center uppercase tracking-widest mt-4">
                <i class="fa-solid fa-microchip mr-2 text-[16px]"></i> Generate Smart Links
            </button>
        </form>

        <script>
            document.getElementById('fileInput').addEventListener('change', function(e) {
                const count = e.target.files.length;
                const fileNameElem = document.getElementById('fileName');
                if(count === 1) { 
                    fileNameElem.innerHTML = '<span class="text-[#51C0C0] font-bold">' + e.target.files[0].name + '</span>'; 
                }
                else if(count > 1) { 
                    fileNameElem.innerHTML = '<span class="text-[#51C0C0] font-bold">' + count + ' Files Selected</span>'; 
                } else {
                    fileNameElem.innerHTML = 'Select or Drop Files Here';
                }
            });
        </script>
        <?php endif; ?>
    </div>
</body>
</html>
