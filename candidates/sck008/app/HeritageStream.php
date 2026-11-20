<?php
declare(strict_types=1);

/** Authorized asset, private cache policy even on public display. Single range for mobile players. */
function stream_asset(string $path, string $mime): never {
    $stream = fopen($path,'rb');
    if ($stream === false) { http_response_code(404); exit; }
    $size = fstat($stream)['size'];
    $start = 0;
    $end = $size - 1;
    $range = $_SERVER['HTTP_RANGE'] ?? '';
    header('Accept-Ranges: bytes');
    header('Cache-Control: private, no-store, max-age=0');
    if ($range !== '') {
        $matched = preg_match('/^bytes=(\d*)-(\d*)$/D', $range, $parts);
        if ($matched && ($parts[1] !== '' || $parts[2] !== '')) {
            if ($parts[1] === '') { $start = max(0, $size - (int)$parts[2]); }
            else {
                $start = (int)$parts[1];
                if ($parts[2] !== '') { $end = min($end, (int)$parts[2]); }
            }
        } else { $start = $size; }
        if ($start > $end || $start >= $size) {
            http_response_code(416);
            header("Content-Range: bytes */$size");
            exit;
        }
        http_response_code(206);
        header("Content-Range: bytes $start-$end/$size");
    }
    header('Content-Type: '.$mime);
    header('Content-Disposition: '.($mime==='application/pdf'?'attachment':'inline').'; filename="heritage-media"');
    $remaining = $end - $start + 1;
    header('Content-Length: '.$remaining);
    session_write_close();
    if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
        fseek($stream, $start);
        while ($remaining > 0 && !connection_aborted()) {
            $chunk = fread($stream, min(65536, $remaining));
            if ($chunk === false || $chunk === '') break;
            echo $chunk;
            $remaining -= strlen($chunk);
        }
    }
    fclose($stream);
    exit;
}
