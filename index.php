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
                $shortId = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 5);
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
    <title>NETCLOUD | Config Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .glass-panel { 
            background: #0f1524; 
            border: 1px solid #1e2738; 
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.6); 
        }
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-track { background: #0a0f1c; border-radius: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #51C0C0; border-radius: 4px; }
        
        /* Subtle glow effects matching the image */
        .neon-text-glow { text-shadow: 0 0 10px rgba(81, 192, 192, 0.5); }
        .btn-glow { box-shadow: 0 0 15px rgba(81, 192, 192, 0.2); }
    </style>
</head>
<body class="bg-[#0a0f1c] min-h-screen text-slate-200 p-4 flex items-center justify-center">

    <div class="glass-panel w-full max-w-[24rem] rounded-2xl p-6 md:p-8 relative">
        
        <div class="absolute top-6 left-6 w-9 h-9 flex items-center justify-center bg-[#0d131f] border border-[#1e2738] rounded-xl shadow-inner">
            <i class="fa-solid fa-microchip text-[#2d4b69]"></i>
        </div>

        <div class="text-center mt-3 mb-8">
            <h1 class="text-[28px] font-bold tracking-widest text-white">
                NET<span class="text-[#51C0C0] neon-text-glow">CLOUD</span>
            </h1>
            <p class="text-[10px] tracking-[0.2em] text-[#425975] mt-2 font-mono font-semibold uppercase">
                <i class="fa-solid fa-bolt text-[#facc15] mr-1"></i> AI INJECTION ENGINE
            </p>
        </div>

        <?php if(!empty($generatedLinks)): ?>
        <div class="bg-[#0d131f] border border-[#1e2738] rounded-xl p-4 mb-6 max-h-64 overflow-y-auto custom-scroll space-y-4 text-center">
            <?php foreach($generatedLinks as $idx => $item): ?>
            <div class="flex flex-col items-center justify-center">
                <span class="text-[#51C0C0] text-xs mb-2 truncate w-full px-2 text-center font-mono"><?= htmlspecialchars($item['original_name']) ?></span>
                <div class="flex items-center justify-center gap-2 w-full">
                    <a href="<?= $item['link'] ?>" target="_blank" class="text-slate-300 hover:text-white text-[11px] truncate bg-[#0a0f1c] px-3 py-3 rounded-lg border border-[#1e2738] w-full text-left font-mono" id="link-<?= $idx ?>">
                        <?= $item['link'] ?>
                    </a>
                    <button onclick="copySingle('link-<?= $idx ?>', this)" class="bg-[#1e2738] hover:bg-[#2a364d] text-[#51C0C0] p-3 rounded-lg border border-[#1e2738] transition flex-shrink-0">
                        <i class="fa-solid fa-copy"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="flex gap-3">
            <button onclick="window.location.href='/'" class="flex-1 bg-[#0d131f] hover:bg-[#151e2e] border border-[#1e2738] text-slate-300 font-bold py-3 px-4 rounded-xl transition text-[11px] uppercase tracking-wider">
                 Back
            </button>
            <button onclick="copyAll()" class="flex-[2] bg-[#51C0C0] hover:bg-[#43a3a3] text-[#0a0f1c] font-bold py-3 px-4 rounded-xl transition btn-glow text-[11px] uppercase tracking-wider flex items-center justify-center">
                 <i class="fa-solid fa-clone mr-2"></i> Copy All Links
            </button>
        </div>

        <div id="toast" class="fixed bottom-5 right-5 bg-[#51C0C0] text-[#0a0f1c] px-6 py-3 rounded-lg shadow-lg transform translate-y-20 opacity-0 transition-all duration-300 z-50 text-xs font-bold uppercase tracking-wide border border-[#43a3a3]">
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
                icon.className = 'fa-solid fa-check text-white';
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
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            
            <div class="relative border-[1.5px] border-dashed border-[#1e2738] rounded-2xl p-8 text-center hover:border-[#51C0C0] transition-colors duration-300 group cursor-pointer bg-gradient-to-b from-[#0d131f] to-[#0a0f1c]">
                <input type="file" name="files[]" multiple required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" id="fileInput" accept=".hc,.ovpn,.ehi,.nm">
                
                <div class="flex flex-col items-center justify-center">
                    <div class="w-[60px] h-[60px] rounded-full border border-[#1e2738] bg-[#0f1524] flex items-center justify-center mb-4 relative group-hover:border-[#51C0C0]/50 transition-colors">
                        <div class="absolute inset-2 rounded-full border border-[#51C0C0]/30 bg-[#51C0C0]/5"></div>
                        <i class="fa-solid fa-cloud-arrow-up text-[#51C0C0] text-xl z-10"></i>
                    </div>
                    <p class="text-[13px] font-mono text-[#8a9bb3]" id="fileName">Select or Drop Files Here</p>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="flex items-center text-[10px] text-[#51C0C0] uppercase tracking-widest font-mono mb-2">
                        <i class="fa-solid fa-download mr-2"></i> Max Downloads
                    </label>
                    <input type="number" name="limit" placeholder="Limit (e.g. 1)" class="w-full bg-[#0a0f1c] border border-[#1e2738] rounded-xl px-4 py-3.5 text-sm text-[#8a9bb3] focus:outline-none focus:border-[#51C0C0] transition font-mono placeholder-[#2e3c50]">
                </div>

                <div>
                    <label class="flex items-center text-[10px] text-[#51C0C0] uppercase tracking-widest font-mono mb-2">
                        <i class="fa-regular fa-clock mr-2"></i> Validity Time
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <input type="number" name="hours" placeholder="Duration" value="1" min="1" class="w-full bg-[#0a0f1c] border border-[#1e2738] rounded-xl px-4 py-3.5 text-sm text-center text-white focus:outline-none focus:border-[#51C0C0] transition font-mono placeholder-[#2e3c50]">
                        <div class="w-full bg-[#0a0f1c] border border-[#1e2738] rounded-xl px-4 py-3.5 text-sm text-[#51C0C0] text-center font-mono cursor-default flex items-center justify-center">
                            Hours
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full bg-[#51C0C0] hover:bg-[#43a3a3] text-[#0a0f1c] font-bold py-4 px-4 rounded-xl transition btn-glow text-[12px] flex items-center justify-center uppercase tracking-widest mt-2">
                <i class="fa-solid fa-microchip mr-2 text-[14px]"></i> Generate Smart Links
            </button>
        </form>

        <script>
            // Script to update the dropzone text dynamically when a file is selected
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
