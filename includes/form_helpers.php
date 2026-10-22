<?php
/**
 * StudentHub Portal - Form Sanitization, Validation & File Storage Helpers
 * Practical 7: PHP Form Processing with Server-Side Validation and CSV/JSON Storage
 */

/**
 * Sanitize text input: trim whitespace, strip backslashes, escape HTML special chars.
 * @param mixed $data
 * @return string
 */
function sanitize_text($data): string {
    if (!isset($data) || is_array($data)) {
        return '';
    }
    return htmlspecialchars(stripslashes(trim((string)$data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize and validate email address.
 * @param mixed $email
 * @return string
 */
function sanitize_email_input($email): string {
    if (!isset($email) || is_array($email)) {
        return '';
    }
    return filter_var(trim((string)$email), FILTER_SANITIZE_EMAIL);
}

/**
 * Safely append a record to a CSV file using exclusive advisory lock (flock).
 * If file does not exist, automatically writes headers first.
 *
 * @param string $filePath Full path to the CSV file
 * @param array $headers Column headers array
 * @param array $row Associative array or indexed array of row data
 * @return bool True on success, false on failure
 */
function append_to_csv_safe(string $filePath, array $headers, array $row): bool {
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $isNewFile = !file_exists($filePath) || filesize($filePath) === 0;

    $fp = fopen($filePath, 'a');
    if (!$fp) {
        return false;
    }

    // Acquire exclusive lock before writing
    if (flock($fp, LOCK_EX)) {
        if ($isNewFile) {
            fputcsv($fp, $headers);
        }
        fputcsv($fp, $row);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        return true;
    } else {
        fclose($fp);
        return false;
    }
}

/**
 * Safely append a record to a JSON file using exclusive file locking.
 * Reads existing records, appends new record, and writes back formatted JSON.
 *
 * @param string $filePath Full path to the JSON file
 * @param array $record Associative array of record data
 * @return bool True on success, false on failure
 */
function append_to_json_safe(string $filePath, array $record): bool {
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $records = [];
    if (file_exists($filePath) && filesize($filePath) > 0) {
        $content = file_get_contents($filePath);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $records = $decoded;
        }
    }

    $records[] = $record;

    $jsonString = json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($jsonString === false) {
        return false;
    }

    // Write file with exclusive advisory lock
    return file_put_contents($filePath, $jsonString, LOCK_EX) !== false;
}

/**
 * Read all records from a CSV file into an array of associative arrays.
 *
 * @param string $filePath
 * @return array
 */
function read_csv_records(string $filePath): array {
    if (!file_exists($filePath) || filesize($filePath) === 0) {
        return [];
    }

    $records = [];
    $fp = fopen($filePath, 'r');
    if (!$fp) {
        return [];
    }

    if (flock($fp, LOCK_SH)) {
        $headers = fgetcsv($fp);
        if ($headers !== false) {
            while (($row = fgetcsv($fp)) !== false) {
                if (count($row) === count($headers)) {
                    $records[] = array_combine($headers, $row);
                } else {
                    $records[] = $row;
                }
            }
        }
        flock($fp, LOCK_UN);
    }
    fclose($fp);
    return $records;
}

/**
 * Read all records from a JSON file.
 *
 * @param string $filePath
 * @return array
 */
function read_json_records(string $filePath): array {
    if (!file_exists($filePath) || filesize($filePath) === 0) {
        return [];
    }
    $content = file_get_contents($filePath);
    $decoded = json_decode($content, true);
    return is_array($decoded) ? $decoded : [];
}
