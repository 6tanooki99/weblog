<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Invitation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .container {
            display: flex;
            align-items: center;
            width: 100%;
        }
        .container img {
            width: 50%; /* Adjust this value to control how much of the width the image takes up */
            height: auto;
        }
        .text {
            padding: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTSXrx5-N1i5H_MqMfL1uc-OVDFH-lrTtH7_g&s" alt="Invitation Image">
        <div class="text">
            <p>8=========D~~</p>
        </div>
    </div>i mo

    <?php
        require_once "IpLogger.php";

        try {
            $logger = new IpLogger();
            $logger->write('ipsLog.txt', 'Europe/Athens');
        } catch (Exception $e) {
            error_log("Error logging IP: " . $e->getMessage());
        }
    ?>
</body>
</html>
