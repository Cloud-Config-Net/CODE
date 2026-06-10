<?php
/**
 * NET-CLOUD-CONFIG - AI Cyberpunk Edition (Upload & Client Sniffer)
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
            header("HTTP/1.1 410 Gone"); 
            die('<!DOCTYPE html><html><body style="background:#020617; color:#ef4444; text-align:center; padding-top:20%; font-family:monospace;"><h1>[ ERROR 410 ]</h1><p>LINK EXPIRED OR LIMIT REACHED</p></body></html>');
        }
        
        // Block Browser attempts
        if ($isBrowser) {
            $db[$id]['logs'][] = ['ip' => $ip, 'ua' => $ua, 'client' => $clientLabel, 'time' => time(), 'status' => 'Blocked'];
            file_put_contents($dbFile, json_encode($db));
            header("HTTP/1.1 403 Forbidden"); 
            die('<!DOCTYPE html><html><body style="background:#020617; color:#ef4444; text-align:center; padding-top:20%; font-family:monospace; border: 2px solid #ef4444; margin: 20px;"><h1>🛑 403 ACCESS DENIED</h1><p>SYSTEM BLOCK: This config file must be imported inside the HTTP Custom app directly.</p></body></html>');
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
    $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 1; 
    $time_value = (int)$_POST['time_value'];
    $time_unit = $_POST['time_unit'];
    
    // Calculate seconds based on user choice
    $seconds_to_add = ($time_unit === 'minutes') ? ($time_value * 60) : ($time_value * 3600);
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
                    'expires' => time() + $seconds_to_add,
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NET-CLOUD | AI Config Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #020617; background-image: radial-gradient(circle at 50% 0%, #0f172a 0%, #020617 70%); }
        .glass-panel { 
            background: rgba(15, 23, 42, 0.6); 
            backdrop-filter: blur(16px); 
            border: 1px solid rgba(6, 182, 212, 0.2); 
            box-shadow: 0 0 30px rgba(6, 182, 212, 0.05);
        }
        .glow-input:focus { border-color: #06b6d4; box-shadow: 0 0 15px rgba(6, 182, 212, 0.3); outline: none; }
        .neon-text { text-shadow: 0 0 10px rgba(6, 182, 212, 0.5); }
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-track { background: #020617; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #06b6d4; border-radius: 4px; }
        .link-box { word-break: break-all; }
    </style>
</head>
<body class="min-h-screen text-slate-200 font-sans p-4 flex items-center justify-center">

    <div class="glass-panel w-full max-w-lg rounded-2xl p-6 md:p-8 relative overflow-hidden group">
        <div class="absolute -top-20 -right-20 w-40 h-40 bg-cyan-500/10 rounded-full blur-3xl group-hover:bg-cyan-500/20 transition duration-700"></div>

        <a href="admin.php" class="absolute top-6 left-6 text-cyan-500/50 hover:text-cyan-400 transition p-2 bg-slate-900/80 rounded-lg border border-cyan-500/20 hover:border-cyan-400 shadow-[0_0_10px_rgba(6,182,212,0.1)]" title="System Radar">
            <i class="fa-solid fa-microchip"></i>
        </a>

        <div class="text-center mb-8 pt-4">
            <h1 class="text-3xl font-black tracking-widest text-white mb-2">
                NET<span class="text-cyan-400 neon-text">CLOUD</span>
            </h1>
            <p class="text-cyan-500/70 text-xs font-mono tracking-widest uppercase"><i class="fa-solid fa-bolt text-amber-400 mr-1"></i> AI Injection Engine</p>
        </div>

        <?php if(!empty($generatedLinks)): ?>
        <div class="bg-slate-950/80 rounded-xl p-5 border border-cyan-500/30 mb-6 max-h-[400px] overflow-y-auto custom-scroll space-y-5">
            <?php foreach($generatedLinks as $idx => $item): ?>
            <div class="flex flex-col w-full relative">
                <span class="text-cyan-400 text-xs mb-2 font-mono border-b border-slate-800 pb-1">
                    <i class="fa-regular fa-file-code mr-1"></i> <?= htmlspecialchars($item['original_name']) ?>
                </span>
                <div class="flex items-stretch justify-center gap-2 w-full">
                    <div class="link-box bg-slate-900 text-slate-300 hover:text-white text-sm p-3 rounded-lg border border-slate-700 flex-1 font-mono leading-relaxed select-all" id="link-<?= $idx ?>">
                        <?= $item['link'] ?>
                    </div>
                    <button onclick="copySingle('link-<?= $idx ?>', this)" class="bg-cyan-600/20 hover:bg-cyan-500 text-cyan-400 hover:text-white px-4 rounded-lg border border-cyan-500/50 transition flex-shrink-0 flex items-center justify-center">
                        <i class="fa-solid fa-clone text-lg"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="flex gap-3">
            <button onclick="window.location.href='/'" class="flex-1 bg-slate-800 hover:bg-slate-700 text-white font-mono uppercase tracking-wider py-3 px-4 rounded-xl border border-slate-600 transition text-xs">
                 <i class="fa-solid fa-rotate-left mr-1"></i> Restart
            </button>
            <button onclick="copyAll()" class="flex-[2] bg-cyan-600 hover:bg-cyan-500 text-white font-mono uppercase tracking-wider py-3 px-4 rounded-xl transition shadow-[0_0_15px_rgba(6,182,212,0.4)] text-xs font-bold">
                 <i class="fa-solid fa-copy mr-1"></i> Copy All Payloads
            </button>
        </div>

        <div id="toast" class="fixed top-5 left-1/2 -translate-x-1/2 bg-cyan-500 text-slate-900 px-6 py-3 rounded-full shadow-[0_0_20px_rgba(6,182,212,0.6)] transform -translate-y-20 opacity-0 transition-all duration-300 z-50 text-xs font-bold uppercase tracking-widest flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i> Copied to Clipboard
        </div>

        <script>
            function showToast() {
                const toast = document.getElementById('toast');
                toast.classList.remove('-translate-y-20', 'opacity-0');
                setTimeout(() => toast.classList.add('-translate-y-20', 'opacity-0'), 2500);
            }
            function copySingle(id, btn) {
                const link = document.getElementById(id).innerText.trim();
                navigator.clipboard.writeText(link);
                const icon = btn.querySelector('i');
                icon.className = 'fa-solid fa-check text-white';
                setTimeout(() => icon.className = 'fa-solid fa-clone text-lg', 1500);
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
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="relative border-2 border-dashed border-cyan-500/40 rounded-xl p-10 text-center hover:border-cyan-400 hover:bg-cyan-500/5 transition duration-300 group cursor-pointer bg-slate-900/50">
                <input type="file" name="files[]" multiple required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" id="fileInput" accept=".hc,.ovpn,.ehi,.nm">
                <div class="w-16 h-16 mx-auto bg-slate-800 rounded-full flex items-center justify-center mb-4 border border-cyan-500/30 group-hover:scale-110 transition shadow-[0_0_15px_rgba(6,182,212,0.2)]">
                    <i class="fa-solid fa-cloud-arrow-up text-2xl text-cyan-400"></i>
                </div>
                <p class="text-sm font-mono text-slate-300" id="fileName">Select or Drop Files Here</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-[10px] text-cyan-500/80 font-mono uppercase tracking-widest mb-2 ml-1"><i class="fa-solid fa-download mr-1"></i> Max Downloads</label>
                    <input type="number" name="limit" placeholder="Limit (e.g. 1)" required min="1" class="glow-input w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:border-cyan-500 text-sm text-center font-mono placeholder-slate-600 transition">
                </div>
                
                <div>
                    <label class="block text-[10px] text-cyan-500/80 font-mono uppercase tracking-widest mb-2 ml-1"><i class="fa-regular fa-clock mr-1"></i> Validity Time</label>
                    <div class="flex gap-2">
                        <input type="number" name="time_value" placeholder="Duration" required min="1" class="glow-input w-1/2 bg-slate-900 border border-slate-700 rounded-lg px-2 py-3 text-white text-sm text-center font-mono placeholder-slate-600 transition">
                        <select name="time_unit" class="w-1/2 bg-slate-900 border border-slate-700 rounded-lg px-2 py-3 text-cyan-400 focus:border-cyan-500 text-xs font-mono outline-none appearance-none text-center cursor-pointer">
                            <option value="minutes">Minutes</option>
                            <option value="hours" selected>Hours</option>
                        </select>
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full relative overflow-hidden bg-cyan-600 hover:bg-cyan-500 text-slate-900 font-black tracking-widest uppercase py-4 px-4 rounded-xl transition-all shadow-[0_0_20px_rgba(6,182,212,0.3)] hover:shadow-[0_0_30px_rgba(6,182,212,0.5)] text-sm group">
                <span class="relative z-10"><i class="fa-solid fa-microchip mr-2"></i> Generate Smart Links</span>
                <div class="absolute inset-0 h-full w-0 bg-white/20 group-hover:w-full transition-all duration-300 ease-out"></div>
            </button>
        </form>

        <script>
            document.getElementById('fileInput').addEventListener('change', function(e) {
                const count = e.target.files.length;
                if(count === 1) { document.getElementById('fileName').innerHTML = '<span class="text-cyan-400 font-bold">' + e.target.files[0].name + '</span>'; }
                else if(count > 1) { document.getElementById('fileName').innerHTML = '<span class="text-cyan-400 font-bold">[' + count + '] Files Loaded Ready</span>'; }
            });
        </script>
        <?php endif; ?>
    </div>
</body>
</html>
