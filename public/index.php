<?php


session_start();

$database = require '../credentials.php';
$user = $database["database"];
$_SESSION['user'] = $user;

$fileType = '*';

if (isset($_POST['fileType'])) {
    // Fix: Validate and sanitize the file type input
    $fileType .= '.' . htmlspecialchars($_POST['fileType'], ENT_QUOTES, 'UTF-8');
}

$directory = realpath(__DIR__ . "/../files");
$files = glob($directory . "/*" . $fileType);

if (isset($_GET['action']) && $_GET['action'] === 'download') {
    $filename = basename($_GET['filename']);
    $filePath = $directory . '/' . $filename;

    if (!isUserAuthorized()) {
        $unauthorized = true;
    }

    // Fix: Check if the file exists and is within the expected directory
    if (isFileValid($filePath) && isUserAuthorized()) {
        header('Content-Type: application/octet-stream');
        header('Content-Transfer-Encoding: Binary');
        header('Content-disposition: attachment; filename="' . $filename . '"');
        readfile($filePath);
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $filename = basename($_GET['filename']);
    $filePath = $directory . '/' . $filename;

    // Fix: Check if the file exists and is within the expected directory
    if (isFileValid($filePath) && isUserAuthorized()) {
        unlink($filePath);

        // Redirect to the same page without the url parameters
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
}

function isFileValid(string $filePath): bool
{
    $validExtensions = ['png', 'jpg', 'jpeg', 'gif', 'pdf'];
    $maxFileSize = 5 * 1024 * 1024; // 5 MB

    // Check if the file exists
    if (!file_exists($filePath)) {
        return false;
    }

    // Check if the file extension is valid
    $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
    if (!in_array($fileExtension, $validExtensions)) {
        return false;
    }

    // Check if the file size is within the limit
    $fileSize = filesize($filePath);
    if ($fileSize > $maxFileSize) {
        return false;
    }

    // Add any other validation checks as per your requirements

    return true;
}

function isUserAuthorized(): bool
{
    //authorization logic here

    // Example: Check if the user is logged in
    if (!isset($_SESSION['user'])) {
        return false;
    }

    // Example: Check if the user has the necessary role or permissions
    $user = $_SESSION['user'];
    if ($user['role'] !== 'admin') {
        return false;
    }

    // Add any other authorization checks 

    return true;
}

function generateCSRFToken(): string
{
    // Generate a unique CSRF token
    $token = bin2hex(random_bytes(32));

    // Store the token in the session or a secure location
    $_SESSION['csrf_token'] = $token;

    return $token;
}

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Directory Viewer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="./style.css">

</head>

<body>
    <?php
    if (!empty($_SESSION)) {
        foreach ($_SESSION["user"] as $key => $value) {
            echo $key . ': ';
            if (is_array($value)) {
                echo implode(', ', $value); // Convert the array elements to a string
            } else {
                echo $value; // Print the non-array value directly
            }
            echo '<br>';
        }
    } else {
        echo 'No session variables set';
    }
    ?>
    <?php if (isset($unauthorized) && $unauthorized) { ?>
        <script>
            // JavaScript code to display the unauthorized alert
            alert('Unauthorized access! Please log in.');
        </script>
    <?php } ?>
    <h1>Directory Viewer</h1>

    <?php if (isset($_POST['fileType'])) { ?>
        <p>Currently filtered by <?= htmlspecialchars($_POST['fileType'], ENT_QUOTES, 'UTF-8'); ?></p>
    <?php } ?>

    <form method="post" action="index.php">
        <!-- Fix: Add CSRF token field -->
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
        Filter: <select name="fileType">
            <option value="">Please select</option>
            <option value="png">PNG files</option>
            <option value="pdf">PDF files</option>
        </select>
        <input type="submit" value="Filter!">
    </form>

    <table>
        <tr>
            <th>Filename</th>
            <th>&nbsp;</th>
            <th>&nbsp;</th>
        </tr>
        <?php foreach ($files as $x) { ?>
            <tr>
                <td><?php
                    $fileExtension = pathinfo($x, PATHINFO_EXTENSION);
                    if ($fileExtension === 'pdf') {
                        echo '<i class="far fa-file-pdf"></i>';
                    } elseif (in_array($fileExtension, ['png', 'jpg', 'jpeg', 'gif'])) {
                        echo '<i class="far fa-file-image"></i>';
                    } else {
                        echo '<i class="far fa-file"></i>';
                    }
                    ?>
                    <?= htmlspecialchars(basename($x), ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <a href="?action=delete&filename=<?= htmlspecialchars(basename($x), ENT_QUOTES, 'UTF-8'); ?>&csrf_token=<?= generateCSRFToken(); ?>">
                        Delete file
                    </a>
                </td>
                <td>
                    <a href="?action=download&filename=<?= htmlspecialchars(basename($x), ENT_QUOTES, 'UTF-8'); ?>&csrf_token=<?= generateCSRFToken(); ?>">
                        Download file
                    </a>
                </td>
            </tr>
        <?php } ?>
    </table>
</body>

</html>