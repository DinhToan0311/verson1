<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Kết nối MySQLi
$host = "sql307.infinityfree.com";
$username = "if0_39344249";
$password = "GYfJHSjGxKEq";
$database = "if0_39344249_login_movie";

$conn = new mysqli($host, $username, $password, $database);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Danh sách Series</title>
    <style>
        body {
            font-family: sans-serif;
            background: #f0f0f0;
            margin: 0;
            padding: 0;
        }

        .series-container {
            display: flex;
            flex-wrap: wrap;
            padding: 20px;
            gap: 20px;
            justify-content: center;
        }

        .series-card {
            width: 200px;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .series-card img {
            width: 100%;
            height: auto;
        }

        #ai-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            cursor: pointer;
        }

        #ai-button img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
        }

        #chatbot-box {
            position: fixed;
            bottom: 90px;
            right: 20px;
            width: 300px;
            max-height: 400px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
        }

        .hidden {
            display: none;
        }

        .chat-header {
            background: #48a2d6;
            padding: 10px;
            color: white;
            border-radius: 10px 10px 0 0;
        }

        #chat-content {
            padding: 10px;
            flex: 1;
            overflow-y: auto;
        }

        #chat-input {
            border: none;
            padding: 10px;
            border-top: 1px solid #ccc;
            outline: none;
        }
    </style>
</head>

<body>

    <div class="series-container">
        <?php
        $result = $conn->query("SELECT * FROM series ORDER BY views DESC LIMIT 12");
        while ($row = $result->fetch_assoc()):
        ?>
            <div class="series-card">
                <img src="<?= htmlspecialchars($row['poster_url'] ?: $row['poster']) ?>" alt="Poster">
                <h3><?= htmlspecialchars($row['title']) ?></h3>
                <p><?= htmlspecialchars($row['genre']) ?></p>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Nút AI -->
    <div id="ai-button">
        <img src="assets/images/logo.png" alt="AI" />
    </div>

    <!-- Hộp chat -->
    <div id="chatbot-box" class="hidden">
        <div class="chat-header">🤖 Hỏi AI</div>
        <div id="chat-content"></div>
        <input type="text" id="chat-input" placeholder="Hỏi gì đó..." />
    </div>

    <script>
        document.getElementById("ai-button").onclick = () => {
            document.getElementById("chatbot-box").classList.toggle("hidden");
        };

        document.getElementById("chat-input").addEventListener("keypress", function(e) {
            if (e.key === "Enter") {
                const input = this.value.trim();
                if (!input) return;

                const chatContent = document.getElementById("chat-content");
                chatContent.innerHTML += `<div><b>Bạn:</b> ${input}</div>`;
                this.value = "";

                fetch("chatbot.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: "message=" + encodeURIComponent(input)
                    })
                    .then(res => res.text())
                    .then(reply => {
                        chatContent.innerHTML += `<div><b>AI:</b> ${reply}</div>`;
                        chatContent.scrollTop = chatContent.scrollHeight;
                    });
            }
        });
    </script>
</body>

</html>