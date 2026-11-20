<?php
declare(strict_types=1);

/** Shared, single-host login throttling; requires a private writable directory. */
final class LoginThrottle {
    private string $file;
    private int $window = 900;
    private int $limit = 5;

    public function __construct(string $directory) {
        $directory = rtrim($directory, "\/ ");
        if ($directory === '' || !is_dir($directory) || !is_writable($directory)) {
            throw new RuntimeException('Private login throttle directory is not configured');
        }
        $real = realpath($directory);
        $public = realpath(dirname(__DIR__) . '/public');
        if ($real === false || $public === false || $real === $public || str_starts_with($real . DIRECTORY_SEPARATOR, $public . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Login throttle storage must be outside the public webroot');
        }
        $this->file = $real . DIRECTORY_SEPARATOR . 'sck_login_throttle.json';
    }

    /** Authenticate under a cross-process lock. Returns user ID or null. */
    public function attempt(string $email, string $ip, callable $verify): ?int {
        $stream = @fopen($this->file, 'c+b');
        if ($stream === false) { throw new RuntimeException('Login throttle storage unavailable'); }
        try {
            if (!flock($stream, LOCK_EX)) { throw new RuntimeException('Login throttle lock unavailable'); }
            $contents = stream_get_contents($stream);
            $data = ($contents === '' || $contents === false) ? [] : json_decode($contents, true);
            if (!is_array($data)) { throw new RuntimeException('Invalid login throttle state'); }
            $now = time();
            foreach ($data as $key => $entry) {
                if (!is_array($entry) || !isset($entry['at'], $entry['failed']) || !is_int($entry['at']) || !is_int($entry['failed'])) {
                    throw new RuntimeException('Invalid login throttle entry');
                }
                if ($entry['at'] + $this->window <= $now) { unset($data[$key]); }
            }
            // Limit state growth: a flooded single-host service fails closed.
            if (count($data) > 10000) { throw new RuntimeException('Login throttle capacity exceeded'); }
            $keys = ['ip:' . hash('sha256', $ip), 'email:' . hash('sha256', strtolower(trim($email)))];
            foreach ($keys as $key) {
                if (($data[$key]['failed'] ?? 0) >= $this->limit) {
                    throw new DomainException('Terlalu banyak percobaan masuk. Coba kembali nanti.');
                }
            }
            $id = $verify();
            if ($id !== null && (!is_int($id) || $id <= 0)) { throw new RuntimeException('Invalid authentication result'); }
            if ($id === null) {
                foreach ($keys as $key) {
                    $entry = $data[$key] ?? ['at' => $now, 'failed' => 0];
                    $data[$key] = ['at' => $entry['at'], 'failed' => $entry['failed'] + 1];
                }
            } else {
                // A successful login resets its email bucket; it does not reset the shared IP bucket.
                unset($data[$keys[1]]);
            }
            $json = json_encode($data, JSON_THROW_ON_ERROR);
            rewind($stream);
            if (!ftruncate($stream, 0) || fwrite($stream, $json) !== strlen($json) || !fflush($stream)) {
                throw new RuntimeException('Login throttle state could not be persisted');
            }
            return $id;
        } finally {
            flock($stream, LOCK_UN);
            fclose($stream);
        }
    }
}
