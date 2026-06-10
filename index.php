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
    $hours = (int)$_POST['hours'];
    $fileCount = count($_FILES['files']['name']);
    
    for ($i = 0; $i < $fileCount; $i++) {
        if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['files']['tmp_name'][$i];
            $originalName = basename($_FILES['files']['name'][$i]);
            $targetPath = $uploadDir . bin2hex(random_bytes(16)) . '.dat';
            
            if (move_uploaded_file($tmpName, $targetPath)) {
                $shortId =  . substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 5);
                $db[$shortId] = [
                    'original_name' => $originalName,
                    'real_path' => $targetPath,
                    'limit' => $limit,
                    'downloads' => 0,
                    'expires' => time() + ($hours * 3600),
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
    <title>NET-CLOUD | Config Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .glass-panel { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: #0f172a; border-radius: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
    </style>
</head>
<body class="bg-slate-900 min-h-screen text-slate-200 font-sans bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-slate-800 via-slate-900 to-black p-4 flex items-center justify-center">

    <div class="glass-panel w-full max-w-lg rounded-2xl shadow-2xl p-6 md:p-8 relative">
        
        <a href="admin.php" class="absolute top-6 left-6 text-slate-400 hover:text-white transition p-2 bg-slate-800/80 rounded-lg border border-slate-700/60" title="Analytics Dashboard">
            <div class="w-5 h-4 flex flex-col justify-between items-center cursor-pointer">
                <span class="w-full h-[2px] bg-current rounded"></span>
                <span class="w-full h-[2px] bg-current rounded"></span>
                <span class="w-full h-[2px] bg-current rounded"></span>
            </div>
        </a>

        <div class="text-center mb-6 pt-4">
            <h1 class="text-3xl font-bold tracking-wider text-white">NET-CLOUD<span class="text-emerald-400">CONFIG</span></h1>
            <p class="text-slate-400 text-xs mt-1">Multi-Upload & Secure Client Tracker</p>
        </div>

        <?php if(!empty($generatedLinks)): ?>
        <div class="bg-slate-900/50 rounded-xl p-4 border border-slate-700 mb-6 max-h-80 overflow-y-auto custom-scroll space-y-5 text-center">
            <?php foreach($generatedLinks as $idx => $item): ?>
            <div class="flex flex-col items-center justify-center">
                <span class="text-slate-400 text-xs mb-1 truncate w-full px-4 text-left font-mono"><?= htmlspecialchars($item['original_name']) ?></span>
                <div class="flex items-center justify-center gap-2 w-full max-w-sm">
                    <a href="<?= $item['link'] ?>" target="_blank" class="text-emerald-400 hover:text-emerald-300 hover:underline text-xs truncate bg-slate-800 px-3 py-2 rounded border border-slate-700 w-full text-left font-mono" id="link-<?= $idx ?>">
                        <?= $item['link'] ?>
                    </a>
                    <button onclick="copySingle('link-<?= $idx ?>', this)" class="bg-slate-700 hover:bg-slate-600 text-white p-2 rounded border border-slate-600 transition flex-shrink-0">
                        <i class="fa-solid fa-copy"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="flex gap-3">
            <button onclick="window.location.href='/'" class="flex-1 bg-slate-700 hover:bg-slate-600 text-white font-bold py-3 px-4 rounded-xl transition text-xs">
                 Back to Upload
            </button>
            <button onclick="copyAll()" class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 px-4 rounded-xl transition shadow-lg shadow-emerald-500/20 text-xs">
                 Copy All Links
            </button>
        </div>

        <div id="toast" class="fixed bottom-5 right-5 bg-emerald-500 text-white px-6 py-3 rounded shadow-lg transform translate-y-20 opacity-0 transition-all duration-300 z-50 text-xs font-semibold">
            Links copied to clipboard!
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
                icon.className = 'fa-solid fa-check text-emerald-400';
                setTimeout(() => icon.className = 'fa-solid fa-copy', 1500);
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
        <form method="POST" enctype="multipart/form-data" class="space-y-5">
            <div class="relative border-2 border-dashed border-slate-600 rounded-xl p-8 text-center hover:border-emerald-400 transition group cursor-pointer bg-slate-800/50">
                <input type="file" name="files[]" multiple required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" id="fileInput" accept=".hc,.ovpn,.ehi,.nm">
                <i class="fa-solid fa-cloud-arrow-up text-4xl text-slate-500 group-hover:text-emerald-400 transition mb-3"></i>
                <p class="text-sm font-semibold text-slate-300" id="fileName">Drop or paste file</p>
                <p class="text-xs text-slate-500 mt-2"></p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs text-slate-400 uppercase mb-1 ml-1">Max Downloads</label>
                    <input type="number" name="limit" value="1" min="0" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500 text-sm text-center font-mono">
                </div>
                <div>
                    <label class="block text-xs text-slate-400 uppercase mb-1 ml-1">Validity Files</label>
                    <input type="number" name="hours" value="24" min="1" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500 text-sm text-center font-mono">
                </div>
            </div>

            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 px-4 rounded-xl transition shadow-lg shadow-emerald-500/20 text-sm">
                Generate Links
            </button>
        </form>

        <script>
            document.getElementById('fileInput').addEventListener('change', function(e) {
                const count = e.target.files.length;
                if(count === 1) { document.getElementById('fileName').innerHTML = '<span class="text-emerald-400">' + e.target.files[0].name + '</span>'; }
                else if(count > 1) { document.getElementById('fileName').innerHTML = '<span class="text-emerald-400">' + count + ' files selected</span>'; }
            });
        </script>
        <?php endif; ?>
    </div>
</body>
</html>
