<?php
/**
 * StudentHub Portal - Asynchronous RESTful JSON API for Campus Events
 * 
 * Subject: Web Development Frameworks (ITUE203)
 * Practical 13: PHP JSON API and AJAX/Fetch-Based CRUD without Page Reload
 * - Problem Definition: JSON API endpoints handling GET, POST, PUT, DELETE asynchronously
 * - Intermediate Extension: Debounced live search capability
 * - Advanced Extension: Explicit HTTP status codes (200, 201, 400, 404, 405, 500) & centralized error handling
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Preflight CORS response
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

/**
 * Centralized JSON Response Handler (Practical 13 Advanced Extension)
 *
 * @param int $statusCode Standard HTTP Status Code
 * @param bool $success Operation status
 * @param mixed $data Payload data
 * @param string $message Status message
 */
function send_api_response(int $statusCode, bool $success, $data = null, string $message = ''): void {
    http_response_code($statusCode);
    echo json_encode([
        'status_code' => $statusCode,
        'success'     => $success,
        'message'     => $message,
        'data'        => $data,
        'timestamp'   => time()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Helper: Load fallback events from data/events.json
 */
function get_events_from_json(): array {
    $file = __DIR__ . '/../data/events.json';
    if (!file_exists($file)) return [];
    $raw = @file_get_contents($file);
    $data = json_decode($raw, true) ?: [];
    foreach ($data as &$ev) {
        if (!isset($ev['event_date']) && isset($ev['date'])) {
            $ev['event_date'] = $ev['date'];
        }
        if (!isset($ev['status'])) {
            $ev['status'] = 'open';
        } else {
            $s = strtolower($ev['status']);
            $ev['status'] = ($s === 'upcoming' || $s === 'open' || $s === 'active') ? 'open' : 'closed';
        }
    }
    return $data;
}

/**
 * Helper: Save events to data/events.json
 */
function save_events_to_json(array $events): bool {
    $file = __DIR__ . '/../data/events.json';
    return (bool)file_put_contents($file, json_encode(array_values($events), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Connect to Database with graceful fallback to events.json
$pdo = null;
$useJson = false;

try {
    $pdo = get_pdo_connection();
} catch (Throwable $e) {
    // Graceful fallback to data/events.json so API always functions smoothly
    $useJson = true;
}

$method = $_SERVER['REQUEST_METHOD'];

// Parse incoming JSON body for POST/PUT/DELETE
$rawInput = file_get_contents('php://input');
$jsonBody = json_decode($rawInput, true) ?: [];

switch ($method) {

    // ------------------------------------------------------------------------
    // 1. GET: List Events with Search & Category Filter
    // ------------------------------------------------------------------------
    case 'GET':
        $search   = trim($_GET['search'] ?? '');
        $category = trim($_GET['category'] ?? '');
        $id       = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if ($useJson || !$pdo) {
            $allEvents = get_events_from_json();

            // Single item lookup
            if ($id) {
                foreach ($allEvents as $ev) {
                    if ((int)($ev['id'] ?? 0) === $id) {
                        send_api_response(200, true, $ev, 'Event details retrieved successfully.');
                    }
                }
                send_api_response(404, false, null, "Event with ID #{$id} not found.");
            }

            // Filtering
            $filtered = [];
            foreach ($allEvents as $ev) {
                $evTitle = $ev['title'] ?? '';
                $evDesc  = $ev['description'] ?? '';
                $evVenue = $ev['venue'] ?? '';
                $evCat   = $ev['category'] ?? '';

                if ($search !== '') {
                    $sLower = mb_strtolower($search);
                    $match = (mb_stripos($evTitle, $sLower) !== false) ||
                             (mb_stripos($evDesc, $sLower) !== false) ||
                             (mb_stripos($evVenue, $sLower) !== false) ||
                             (mb_stripos($evCat, $sLower) !== false);
                    if (!$match) continue;
                }

                if ($category !== '' && strcasecmp($evCat, $category) !== 0) {
                    continue;
                }

                $filtered[] = $ev;
            }

            // Sort by event date
            usort($filtered, function ($a, $b) {
                return strcmp($a['event_date'] ?? '', $b['event_date'] ?? '');
            });

            send_api_response(200, true, $filtered, 'Events retrieved successfully (' . count($filtered) . ' records).');
        }

        // Database Mode
        // Fetch single event by ID
        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM events WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $event = $stmt->fetch();
            if ($event) {
                send_api_response(200, true, $event, 'Event details retrieved successfully.');
            } else {
                send_api_response(404, false, null, "Event with ID #{$id} not found.");
            }
        }

        // Fetch filtered list
        $sql = "SELECT * FROM events WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (title LIKE :s OR description LIKE :s2 OR venue LIKE :s3 OR category LIKE :s4)";
            $params[':s']  = "%{$search}%";
            $params[':s2'] = "%{$search}%";
            $params[':s3'] = "%{$search}%";
            $params[':s4'] = "%{$search}%";
        }
        if ($category !== '') {
            $sql .= " AND category = :cat";
            $params[':cat'] = $category;
        }

        $sql .= " ORDER BY event_date ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $events = $stmt->fetchAll();

        send_api_response(200, true, $events, 'Events retrieved successfully (' . count($events) . ' records).');
        break;

    // ------------------------------------------------------------------------
    // 2. POST: Create New Event (Returns 201 Created)
    // ------------------------------------------------------------------------
    case 'POST':
        $data = !empty($_POST) ? $_POST : $jsonBody;

        $title       = trim($data['title'] ?? '');
        $category    = trim($data['category'] ?? 'Technical');
        $description = trim($data['description'] ?? '');
        $eventDate   = trim($data['event_date'] ?? ($data['date'] ?? ''));
        $venue       = trim($data['venue'] ?? '');
        $status      = trim($data['status'] ?? 'open');

        // Validation (Practical 13: 400 Bad Request on missing mandatory fields)
        if (empty($title) || empty($eventDate) || empty($venue)) {
            send_api_response(400, false, null, 'Validation error: Title, event_date, and venue are required.');
        }

        if ($useJson || !$pdo) {
            $allEvents = get_events_from_json();
            $maxId = 0;
            foreach ($allEvents as $e) {
                if ((int)($e['id'] ?? 0) > $maxId) $maxId = (int)$e['id'];
            }
            $newId = $maxId + 1;
            $newEvent = [
                'id'          => $newId,
                'title'       => $title,
                'category'    => $category,
                'description' => $description ?: 'No detailed description available.',
                'event_date'  => $eventDate,
                'venue'       => $venue,
                'status'      => strtolower($status),
                'poster_image'=> 'default_event.png',
                'created_at'  => date('Y-m-d H:i:s')
            ];
            $allEvents[] = $newEvent;
            save_events_to_json($allEvents);

            send_api_response(201, true, $newEvent, 'Event created successfully via API');
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO events (title, category, description, event_date, venue, status, poster_image)
                VALUES (:title, :cat, :desc, :edate, :venue, :status, 'default_event.png')
            ");
            $stmt->execute([
                ':title'  => $title,
                ':cat'    => $category,
                ':desc'   => $description ?: 'No detailed description available.',
                ':edate'  => $eventDate,
                ':venue'  => $venue,
                ':status' => $status
            ]);
            $newId = (int)$pdo->lastInsertId();

            $fetchStmt = $pdo->prepare("SELECT * FROM events WHERE id = :id");
            $fetchStmt->execute([':id' => $newId]);
            $createdEvent = $fetchStmt->fetch();

            // Sync with events.json
            $allEvents = get_events_from_json();
            $allEvents[] = $createdEvent;
            save_events_to_json($allEvents);

            if (function_exists('log_audit_event')) {
                $curUser = function_exists('get_logged_in_user') ? get_logged_in_user() : null;
                $uid = $curUser ? (int)$curUser['id'] : null;
                log_audit_event($uid, 'EVENT_CREATE_AJAX', 'events', $newId, "Created event via AJAX API: '{$title}' ({$category})");
            }

            send_api_response(201, true, $createdEvent, 'Event created successfully via API');
        } catch (Exception $e) {
            // Fallback to JSON if database write encounters any issue
            $allEvents = get_events_from_json();
            $maxId = 0;
            foreach ($allEvents as $evItem) {
                if ((int)($evItem['id'] ?? 0) > $maxId) $maxId = (int)$evItem['id'];
            }
            $newId = $maxId + 1;
            $newEvent = [
                'id'          => $newId,
                'title'       => $title,
                'category'    => $category,
                'description' => $description ?: 'No detailed description available.',
                'event_date'  => $eventDate,
                'venue'       => $venue,
                'status'      => strtolower($status),
                'poster_image'=> 'default_event.png',
                'created_at'  => date('Y-m-d H:i:s')
            ];
            $allEvents[] = $newEvent;
            save_events_to_json($allEvents);

            send_api_response(201, true, $newEvent, 'Event created successfully via API');
        }
        break;

    // ------------------------------------------------------------------------
    // 3. PUT: Update Existing Event (Returns 200 OK or 404 Not Found)
    // ------------------------------------------------------------------------
    case 'PUT':
        $data = $jsonBody;
        $id = (int)($data['id'] ?? ($_GET['id'] ?? 0));

        if ($id <= 0) {
            send_api_response(400, false, null, 'Validation error: A valid event ID is required for updates.');
        }

        $title       = trim($data['title'] ?? '');
        $category    = trim($data['category'] ?? 'Technical');
        $description = trim($data['description'] ?? '');
        $eventDate   = trim($data['event_date'] ?? ($data['date'] ?? ''));
        $venue       = trim($data['venue'] ?? '');
        $status      = trim($data['status'] ?? 'open');

        if (empty($title)) {
            send_api_response(400, false, null, 'Validation error: Title cannot be empty.');
        }

        if ($useJson || !$pdo) {
            $allEvents = get_events_from_json();
            $found = false;
            $updatedEvent = null;
            foreach ($allEvents as &$ev) {
                if ((int)($ev['id'] ?? 0) === $id) {
                    $ev['title'] = $title;
                    $ev['category'] = $category;
                    $ev['description'] = $description;
                    if (!empty($eventDate)) $ev['event_date'] = $eventDate;
                    if (!empty($venue)) $ev['venue'] = $venue;
                    $ev['status'] = strtolower($status);
                    $found = true;
                    $updatedEvent = $ev;
                    break;
                }
            }
            if (!$found) {
                send_api_response(404, false, null, "Event with ID #{$id} does not exist.");
            }
            save_events_to_json($allEvents);
            send_api_response(200, true, $updatedEvent, "Event updated successfully via API");
        }

        // Database Mode
        $check = $pdo->prepare("SELECT id FROM events WHERE id = :id");
        $check->execute([':id' => $id]);
        if (!$check->fetch()) {
            send_api_response(404, false, null, "Event with ID #{$id} does not exist.");
        }

        try {
            $stmt = $pdo->prepare("
                UPDATE events 
                SET title = :t, category = :c, description = :d, event_date = :ed, venue = :v, status = :s
                WHERE id = :id
            ");
            $stmt->execute([
                ':t' => $title, ':c' => $category, ':d' => $description,
                ':ed' => $eventDate, ':v' => $venue, ':s' => $status, ':id' => $id
            ]);

            $fetchStmt = $pdo->prepare("SELECT * FROM events WHERE id = :id");
            $fetchStmt->execute([':id' => $id]);
            $updatedEvent = $fetchStmt->fetch();

            if (function_exists('log_audit_event')) {
                $curUser = function_exists('get_logged_in_user') ? get_logged_in_user() : null;
                $uid = $curUser ? (int)$curUser['id'] : null;
                log_audit_event($uid, 'EVENT_UPDATE_AJAX', 'events', $id, "Updated event via AJAX API ID: #{$id} ({$title})");
            }

            send_api_response(200, true, $updatedEvent, "Event updated successfully via API");
        } catch (Exception $e) {
            send_api_response(500, false, null, 'Update failed: ' . $e->getMessage());
        }
        break;

    // ------------------------------------------------------------------------
    // 4. DELETE: Delete Event (Returns 200 OK or 404 Not Found)
    // ------------------------------------------------------------------------
    case 'DELETE':
        $id = (int)($jsonBody['id'] ?? ($_GET['id'] ?? 0));

        if ($id <= 0) {
            send_api_response(400, false, null, 'Validation error: Valid event ID is required to delete.');
        }

        if ($useJson || !$pdo) {
            $allEvents = get_events_from_json();
            $newEvents = [];
            $found = false;
            foreach ($allEvents as $ev) {
                if ((int)($ev['id'] ?? 0) === $id) {
                    $found = true;
                } else {
                    $newEvents[] = $ev;
                }
            }
            if (!$found) {
                send_api_response(404, false, null, "Event with ID #{$id} not found.");
            }
            save_events_to_json($newEvents);
            send_api_response(200, true, ['deleted_id' => $id], "Event deleted successfully via API.");
        }

        // Database Mode
        $check = $pdo->prepare("SELECT title FROM events WHERE id = :id");
        $check->execute([':id' => $id]);
        $ev = $check->fetch();

        if (!$ev) {
            send_api_response(404, false, null, "Event with ID #{$id} not found.");
        }

            $del = $pdo->prepare("DELETE FROM events WHERE id = :id");
            $del->execute([':id' => $id]);

            if (function_exists('log_audit_event')) {
                $curUser = function_exists('get_logged_in_user') ? get_logged_in_user() : null;
                $uid = $curUser ? (int)$curUser['id'] : null;
                log_audit_event($uid, 'EVENT_DELETE_AJAX', 'events', $id, "Deleted event via AJAX API: '{$ev['title']}'");
            }

            send_api_response(200, true, ['deleted_id' => $id], "Event deleted successfully via API.");
        } catch (Exception $e) {
            send_api_response(500, false, null, 'Failed to delete event: ' . $e->getMessage());
        }
        break;

    default:
        send_api_response(405, false, null, "HTTP method {$method} is not supported.");
        break;
}
