<?php
session_start();
require_once 'db_connect.php';
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// --- Fetch subjects ---
$subjects = [];
$res = $conn->query("SELECT id, name FROM subjects ORDER BY name ASC");
while ($row = $res->fetch_assoc()) $subjects[] = $row;

// --- Fetch topics grouped by subject ---
$topicsBySubject = [];
$res = $conn->query("SELECT id, name, subject_id FROM topics ORDER BY name ASC");
while ($row = $res->fetch_assoc()) {
    $topicsBySubject[$row['subject_id']][] = $row;
}

// --- Handle form submit ---
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $subject_id  = intval($_POST['subject_id']);
    $topic_id    = intval($_POST['topic_id']);
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $url         = trim($_POST['url']);
    $file_path   = "";

    // Handle local file upload
    if (!empty($_FILES['video_file']['name'])) {
        $uploadDir = "uploads/videos/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileName = time() . "_" . basename($_FILES['video_file']['name']);
        $target   = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['video_file']['tmp_name'], $target)) {
            $file_path = $target;
        } else {
            $message = "❌ Failed to upload video file.";
        }
    }

    if ($topic_id && $title && ($url || $file_path)) {
        $stmt = $conn->prepare("INSERT INTO videos (topic_id, title, description, url, file_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $topic_id, $title, $description, $url, $file_path);
        if ($stmt->execute()) {
            $message = "✅ Video uploaded successfully!";
        } else {
            $message = "❌ Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "⚠️ Please complete all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Upload Video - SkillSync</title>
  <style>
    body { font-family: 'Segoe UI', sans-serif; background: #f7f9fb; padding: 40px; }
    .form-container { max-width: 700px; margin: auto; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    h2 { color: #27ae60; margin-bottom: 20px; text-align: center; }
    label { display: block; margin-top: 15px; font-weight: bold; }
    select, input, textarea, button { width: 100%; padding: 10px; margin-top: 8px; border: 1px solid #ccc; border-radius: 8px; font-size: 15px; }
    button { background: #27ae60; color: white; font-weight: bold; cursor: pointer; transition: background 0.3s; }
    button:hover { background: #219150; }
    .msg { margin-top: 15px; text-align: center; font-weight: bold; }
  </style>
</head>
<body>
  <div class="form-container">
    <h2>📹 Upload Video Material</h2>
    <?php if ($message): ?><div class="msg"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <label for="subject">Select Subject</label>
      <select name="subject_id" id="subject" required>
        <option value="">-- Select Subject --</option>
        <?php foreach ($subjects as $s): ?>
          <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label for="topic">Select Topic</label>
      <select name="topic_id" id="topic" required>
        <option value="">-- Select Topic --</option>
      </select>

      <label for="title">Video Title</label>
      <input type="text" name="title" id="title" required>

      <label for="description">Description</label>
      <textarea name="description" id="description" rows="4"></textarea>

      <label for="url">YouTube Embed URL (optional)</label>
      <input type="text" name="url" id="url" placeholder="https://www.youtube.com/embed/VIDEO_ID">

      <label for="video_file">Upload Local Video (optional)</label>
      <input type="file" name="video_file" id="video_file" accept="video/*">

      <button type="submit">Upload Video</button>
    </form>
  </div>

  <script>
    const topicsBySubject = <?= json_encode($topicsBySubject) ?>;
    document.getElementById("subject").addEventListener("change", function() {
        const subjectId = this.value;
        const topicSelect = document.getElementById("topic");
        topicSelect.innerHTML = '<option value="">-- Select Topic --</option>';
        if (topicsBySubject[subjectId]) {
            topicsBySubject[subjectId].forEach(t => {
                const opt = document.createElement("option");
                opt.value = t.id;
                opt.textContent = t.name;
                topicSelect.appendChild(opt);
            });
        }
    });
  </script>
</body>
</html>
