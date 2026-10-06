#!/usr/bin/php

<?php
/**
 * sync-app.php — pull the team app from the Dropbox "app" share into the live /app folder.
 *
 * Safe by construction: only the three files in ALLOW are ever written; logo.png and the icons are never touched;
 * a file is only replaced if its content actually changed; nothing is deleted.
 */

const URL   = 'https://www.dropbox.com/scl/fo/wy8u6qxi8fmuoo88tyd8j/AKUw4llNAFGm0qRR1JiTiCs?rlkey=364mj02emzg69lyxclxdor61f&dl=1';
const LIVE  = '/home/dephna/public_html/app';                      // <-- adjust to the real path
const ALLOW = ['index.html', 'sw.js', 'manifest.json'];

$log = fn(string $m) => fwrite(STDOUT, date('Y-m-d H:i:s') . "  $m\n");

// 1. download
$tmp = tempnam(sys_get_temp_dir(), 'dephna-app-') . '.zip';
$ch  = curl_init(URL);
curl_setopt_array($ch, [CURLOPT_FOLLOWLOCATION => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_USERAGENT => 'dephna-sync/1']);
$data = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$type = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);
if ($data === false || $code !== 200) { $log("download failed: HTTP $code"); exit(1); }
if (stripos($type, 'zip') === false && substr($data, 0, 2) !== 'PK') { $log("not a zip (content-type $type) — is the link set to 'anyone with the link'?"); exit(1); }
file_put_contents($tmp, $data);

// 2. open and copy only the allowed files, wherever they sit inside the zip
$zip = new ZipArchive();
if ($zip->open($tmp) !== true) { $log('zip would not open'); unlink($tmp); exit(1); }
$changed = 0; $seen = [];
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    if ($name === false || substr($name, -1) === '/') { continue; }              // directory entries, including the odd "/"
    $base = basename($name);
    if (!in_array($base, ALLOW, true)) { continue; }
    $content = $zip->getFromIndex($i);
    if ($content === false) { $log("could not read $name"); continue; }
    $seen[] = $base;
    $dest = rtrim(LIVE, '/') . '/' . $base;
    if (file_exists($dest) && md5_file($dest) === md5($content)) { continue; }   // unchanged
    if (file_put_contents($dest . '.tmp', $content) === false || !rename($dest . '.tmp', $dest)) { $log("write failed: $dest"); continue; }
    $changed++; $log("updated $base (" . strlen($content) . " bytes)");
}
$zip->close(); unlink($tmp);
if (!$seen) { $log('zip contained none of ' . implode(', ', ALLOW) . ' — check the link points at the app folder'); exit(1); }
if ($changed === 0) { $log('no changes'); }
