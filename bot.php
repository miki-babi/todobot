<?php
include 'dbconfig.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log incoming updates
file_put_contents('php://stderr', print_r(json_decode(file_get_contents("php://input"), TRUE), true));

$botToken = '7308152072:AAGonPZxOdngCt4Opn7xY_IpvKr9m4-bq34';
$apiUrl = "https://api.telegram.org/bot$botToken/";

// Get updates from Telegram
$update = json_decode(file_get_contents("php://input"), TRUE);
file_put_contents('php://stderr', print_r($update, true));

if (isset($update["message"])) {
    $chatId = $update["message"]["chat"]["id"];
    $message = $update["message"]["text"];

    if (isset($message) && strpos($message, '/start') === 0) {
        sendMessage($chatId, "Welcome to your To-Do list bot! Use /add, /list, and /remove commands to manage your tasks.");
    } elseif (isset($message) && strpos($message, '/add') === 0) {
        $task = substr($message, 5);
        if ($task) {
            $stmt = $conn->prepare("INSERT INTO todos (chat_id, task) VALUES (?, ?)");
            if ($stmt) {
                $stmt->bind_param("is", $chatId, $task);
                if ($stmt->execute()) {
                    sendMessage($chatId, "Task added: $task");
                    file_put_contents('php://stderr', "Task added successfully: $task\n");
                } else {
                    sendMessage($chatId, "Failed to add task.");
                    file_put_contents('php://stderr', "Execute failed: " . $stmt->error . "\n");
                }
                $stmt->close();
            } else {
                sendMessage($chatId, "Failed to prepare statement.");
                file_put_contents('php://stderr', "Prepare failed: " . $conn->error . "\n");
            }
        } else {
            sendMessage($chatId, "Usage: /add task_description");
        }
    } elseif (isset($message) && strpos($message, '/list') === 0) {
        $result = $conn->query("SELECT id, task FROM todos WHERE chat_id = $chatId");
        if ($result) {
            $tasks = "";
            while ($row = $result->fetch_assoc()) {
                $tasks .= $row['id'] . ". " . $row['task'] . "\n";
            }
            sendMessage($chatId, $tasks ? $tasks : "No tasks found.");
            file_put_contents('php://stderr', "Tasks listed: $tasks\n");
        } else {
            sendMessage($chatId, "Failed to retrieve tasks.");
            file_put_contents('php://stderr', "Query failed: " . $conn->error . "\n");
        }
    } elseif (isset($message) && strpos($message, '/remove') === 0) {
        $taskId = intval(substr($message, 8));
        if ($taskId) {
            if ($conn->query("DELETE FROM todos WHERE chat_id = $chatId AND id = $taskId")) {
                sendMessage($chatId, "Task removed: $taskId");
                file_put_contents('php://stderr', "Task removed: $taskId\n");
            } else {
                sendMessage($chatId, "Failed to remove task.");
                file_put_contents('php://stderr', "Delete failed: " . $conn->error . "\n");
            }
        } else {
            sendMessage($chatId, "Usage: /remove task_id");
        }
    }
} else {
    file_put_contents('php://stderr', "No message in update\n");
}

function sendMessage($chatId, $message) {
    global $apiUrl;
    $url = $apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode($message);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $output = curl_exec($ch);
    if ($output === FALSE) {
        file_put_contents('php://stderr', "cURL error: " . curl_error($ch) . "\n");
    } else {
        file_put_contents('php://stderr', "Message sent: $message\n");
    }
    curl_close($ch);
}

?>
