<?php
// Start session at the beginning
session_start();

// Replace with your actual database credentials
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'mysql';

$signin_error = "";
$signin_success = false;
$workMsg = "";
$uploadMsg = "";

// Process all forms and redirects before any HTML output
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $profilePath = __DIR__ . "/p-users/" . $_SESSION['user_firstname'] . "_" . $_SESSION['user_lastname'] . "/profile.json";
    $ppDir = __DIR__ . "/p-users/" . $_SESSION['user_firstname'] . "_" . $_SESSION['user_lastname'] . "/pp";
    $workDir = __DIR__ . "/p-users/" . $_SESSION['user_firstname'] . "_" . $_SESSION['user_lastname'] . "/work";

    // Handle work upload - before any HTML output
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['work_upload'])) {
        if (!file_exists($workDir)) mkdir($workDir, 0777, true);

        $title = trim($_POST['work_title'] ?? "");
        $date = trim($_POST['work_date'] ?? "");
        $bio = trim($_POST['work_bio'] ?? "");
        $file = $_FILES['work_image'] ?? null;

        // Generate a UUID for the work
        function generateUUID() {
            // Use random_bytes if available (PHP 7+) for better randomness
            if (function_exists('random_bytes')) {
                $data = random_bytes(16);
                $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
                $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
                return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
            } else {
                // Fallback to uniqid with more entropy
                return md5(uniqid(mt_rand(), true));
            }
        }
        
        $uuid = generateUUID();

        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
        if ($file && $file['error'] === 0 && isset($allowed[$file['type']])) {
            $ext = $allowed[$file['type']];
            $safeTitle = preg_replace('/[^a-zA-Z0-9_\-]/', '_', strtolower(substr($title, 0, 20)) ?: 'work');
            $filename = $safeTitle . "_" . time() . "." . $ext;
            $dest = $workDir . "/" . $filename;
            $relDest = "/p-users/" . $_SESSION['user_firstname'] . "_" . $_SESSION['user_lastname'] . "/work/" . $filename;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                // Update profile.json
                $profileData = [];
                if (file_exists($profilePath)) {
                    $profileData = json_decode(file_get_contents($profilePath), true);
                    if (!$profileData) $profileData = [];
                }
                if (!isset($profileData['works']) || !is_array($profileData['works'])) $profileData['works'] = [];
                $profileData['works'][] = [
                    'uuid' => $uuid, // Add UUID to work data
                    'title' => $title,
                    'date' => $date,
                    'bio' => $bio,
                    'img' => $relDest,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                file_put_contents($profilePath, json_encode($profileData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                
                // Store success message in session and redirect
                $_SESSION['work_upload_success'] = true;
                header("Location: studio2.php");
                exit;
            } else {
                $workMsg = "<span style='color:#ffbfbf;'>Upload failed.</span>";
            }
        } else {
            $workMsg = "<span style='color:#ffbfbf;'>Invalid file type or error.</span>";
        }
    }

    // Check for success message from session
    if (isset($_SESSION['work_upload_success'])) {
        $workMsg = "<span style='color:#bfffbf;'>Work image uploaded!</span>";
        unset($_SESSION['work_upload_success']);
    }


// Handle comments form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $c_name = trim($_POST['comment_name'] ?? '');
    $c_email = trim($_POST['comment_email'] ?? '');
    $c_message = trim($_POST['comment_message'] ?? '');
    if ($c_name && $c_email && $c_message) {
        $conn_comment = new mysqli($host, $user, $password, $database);
        if (!$conn_comment->connect_error) {
            // First, check if the comments table exists
            
            
            // Now insert the comment
            $stmt = $conn_comment->prepare("INSERT INTO comments (name, email, message) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $c_name, $c_email, $c_message);
            if ($stmt->execute()) {
                // Success! Store in session and redirect
                $_SESSION['comment_success'] = true;
                header("Location: studio2.php");
                exit;
            } else {
                $_SESSION['comment_error'] = "Error submitting comment: " . $stmt->error;
                header("Location: studio2.php");
                exit;
            }
            $stmt->close();
        } else {
            $_SESSION['comment_error'] = "Database connection error";
            header("Location: studio2.php");
            exit;
        }
    } else {
        $_SESSION['comment_error'] = "Please fill in all fields";
        header("Location: studio2.php");
        exit;
    }
}

// Check for comment success or error messages from session
$comment_msg = "";
if (isset($_SESSION['comment_success'])) {
    $comment_msg = "<span style='color:#bfffbf;'>Thank you! Your comment has been submitted.</span>";
    unset($_SESSION['comment_success']);
} else if (isset($_SESSION['comment_error'])) {
    $comment_msg = "<span style='color:#ffbfbf;'>" . $_SESSION['comment_error'] . "</span>";
    unset($_SESSION['comment_error']);
}

    // Handle profile image upload
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
        if (!file_exists($ppDir)) {
            mkdir($ppDir, 0777, true);
        }
        $file = $_FILES['profile_image'];
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
        if ($file['error'] === 0 && isset($allowed[$file['type']])) {
            $ext = $allowed[$file['type']];
            $dest = $ppDir . "/profile." . $ext;
            $relDest = "/p-users/" . $_SESSION['user_firstname'] . "_" . $_SESSION['user_lastname'] . "/pp/profile." . $ext; // relative path for JSON
            // Move the uploaded file
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $uploadMsg = "<span style='color:#bfffbf;'>Profile picture uploaded!</span>";
                // Update JSON file
                if (file_exists($profilePath)) {
                    $profileData = json_decode(file_get_contents($profilePath), true);
                    if (!$profileData) $profileData = [];
                } else {
                    $profileData = [];
                }
                $profileData['pp'] = $relDest;
                file_put_contents($profilePath, json_encode($profileData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            } else {
                $uploadMsg = "<span style='color:#ffbfbf;'>Upload failed.</span>";
            }
        } else {
            $uploadMsg = "<span style='color:#ffbfbf;'>Invalid file type or error.</span>";
        }
    }
}

// Handle sign in POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signin_email'], $_POST['signin_pword'])) {
    $signin_email = trim($_POST['signin_email']);
    $signin_pword = trim($_POST['signin_pword']);

    // Simple lookup (do NOT use plain passwords in production!)
    $conn2 = new mysqli($host, $user, $password, $database);
    if ($conn2->connect_error) {
        $signin_error = 'Connection failed.';
    } else {
        $stmt = $conn2->prepare("SELECT id, firstname, lastname FROM pusers WHERE email=? AND pword=? LIMIT 1");
        $stmt->bind_param("ss", $signin_email, $signin_pword);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($user = $result->fetch_assoc()) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_firstname'] = $user['firstname'];
            $_SESSION['user_lastname'] = $user['lastname'];
            $signin_success = true;
        } else {
            $signin_error = "Email or password is incorrect.";
        }
        $stmt->close();
        $conn2->close();
    }
}

// Handle signout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signout'])) {
    session_destroy();
    header("Location: v4.5.php");
    exit;
}
$user = 'root';
// Now fetch and display all users
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
$result = $conn->query("SELECT id, firstname, lastname, date, genre, country, bio, pp, fact1, fact2, fact3, link1, link2, link3, work1, work1link, work2, work2link, work3, work3link, work4, work4link, work5, work5link, work6, work6link FROM users ORDER BY id DESC");

// Create json array from fetched data
$jsonArray = array();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $jsonArray[] = [
            "id" => $row["id"],
            "firstname" => $row["firstname"],
            "lastname" => $row["lastname"],
            "date" => $row["date"],
            "genre" => $row["genre"],
            "country" => $row["country"],
            "fact1" => $row["fact1"],
            "fact2" => $row["fact2"],
            "fact3" => $row["fact3"],
            "bio" => $row["bio"],
            "pp" => $row["pp"],
            "link1" => $row["link1"],
            "link2" => $row["link2"],
            "link3" => $row["link3"],
            "work1" => $row["work1"],
            "work1link" => $row["work1link"],
            "work2" => $row["work2"],
            "work2link" => $row["work2link"],
            "work3" => $row["work3"],
            "work3link" => $row["work3link"],
            "work4" => $row["work4"],
            "work4link" => $row["work4link"],
            "work5" => $row["work5"],
            "work5link" => $row["work5link"],
            "work6" => $row["work6"],
            "work6link" => $row["work6link"]
        ];
    }
}
?>

<?php
$photoDir = __DIR__ . '/slideworks';
$images = [];
if (is_dir($photoDir)) {
    $files = scandir($photoDir);
    foreach ($files as $file) {
        if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $file)) {
            $images[] = 'slideworks/' . $file;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>digital artist database</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>





<div style="display:flex;">
  <div class="title-container" id="mainTitleContainer" style="background-image: linear-gradient(135deg, #e27979 60%, #ed8fd1 100%); transition: background-image 0.7s;">
    <br>
    <a href="index.php" style="text-decoration:none; color: white;">digital <br>artist <br>database</a>
  </div>
  
   <div id="dotMenuContainer" style="position:relative; align-self:end; margin-bottom:50px; margin-left:-30px;">
    <div id="dot" style="color:black; background: linear-gradient(135deg, #e27979 60%, #ed8fd1 100%); transition: background 0.7s;"></div>
    <div id="dotMenu" style="display:none; position:absolute; left:50%; top:-380%; transform:translateX(-50%); background-image: linear-gradient(to bottom right, rgba(226, 121, 121, 0.936), rgba(237, 143, 209, 0.936)); border-radius:50%; box-shadow:0 4px 24px #0002; padding:1.4em 2em; min-width:120px; z-index:1000;">
      <!-- Your menu content here -->
      <a href="v4.5.php" style="color:#777; text-decoration:none; display:block; margin-bottom:0.5em;">Home</a>
      <a href="about.php" style="color:#777; text-decoration:none; display:block; margin-bottom:0.5em;">About</a>
      <a href="signup.php" style="color:#b44; text-decoration:none; display:block; margin-bottom:0.5em;">Sign Up</a>
      <a href="contribute.php" style="color:#a56; text-decoration:none; display:block; margin-bottom:0.5em;">Contribute</a>
      <a href="database.php" style="color:#555; text-decoration:none; display:block; margin-bottom:0.5em;">Database</a>
      <a href="studio.php" style="color:#777; text-decoration:none; display:block;">Studio</a>
      <!-- New buttons for changing color -->
      <button id="changeTitleBgBtn" style="margin-top:1em; background:#e27979; color:#fff; border:none; border-radius:8px; padding:0.6em 1.1em; font-family:monospace; font-size:1em; cursor:pointer; display:block; width:100%;">Change Colors</button>
      <button id="bwThemeBtn" style="margin-top:0.7em; background:#232323; color:#fff; border:none; border-radius:8px; padding:0.6em 1.1em; font-family:monospace; font-size:1em; cursor:pointer; display:block; width:100%;">Black & White Theme</button>
    </div>
  </div>
  <p style="color:black; font-size:15px; margin-left:10px; align-self:end;">[alpha]</p>
</div>


<!-- Pop-out menu for quick nav, hidden by default -->
<div id="titleMenuPopout" style="display:none; position:fixed; z-index:10000; top:65px; left:40px; background: white; border-radius:14px; box-shadow:0 4px 24px #0002; padding:1.4em 2em; min-width:80px; font-family:monospace;">
  <div style="display:flex; flex-direction:column; gap:0.5em;">
    <a href="v4.5.php" style="color:#777; text-decoration:none; font-size:1.1em;">home</a>
    <a href="v4.5.php" style="color:#777; text-decoration:none; font-size:1.1em;">about</a>
    <a href="signup.php" style="color:#b44; text-decoration:none; font-size:1.1em;">sign up</a>
    <a href="contribute.php" style="color:#a56; text-decoration:none; font-size:1.1em;">contribute</a>
    <a href="database.php" style="color:#555; text-decoration:none; font-size:1.1em;">database</a>
    <a href="studio.php" style="color:#777; text-decoration:none; font-size:1.1em;">studio</a>
   
  </div>
</div>


<!-- SIGN IN BAR AT TOP -->
<div class="signin-bar" style="width:88vw; justify-content: baseline;border-bottom-right-radius:10px; border-top-right-radius:10px;">
  <?php if (isset($_SESSION['user_id'])): ?>
    <span class="signed-in">Signed in as <?php echo htmlspecialchars($_SESSION['user_firstname'] . ' ' . $_SESSION['user_lastname']); ?></span>
    <form method="post" style="margin:0;">
      <input type="hidden" name="signout" value="1" />
      <input type="submit" value="Sign Out" />
    </form>
  <?php else: ?>
    <form method="post" autocomplete="off">
      <input type="email" style="width:100px;" name="signin_email" required placeholder="email" />
      <input type="password" style="width:100px;" name="signin_pword" required placeholder="password" />
      <input type="submit" value="sign in" />
    </form>
    <?php if ($signin_error): ?>
      <span class="signin-msg"><?php echo htmlspecialchars($signin_error); ?></span>
    <?php elseif ($signin_success): ?>
      <span class="signin-msg signin-success">Signed in!</span>
    <?php endif; ?>
  <?php endif; ?>
</div>

<br>


 <div style="display:flex; align-content:center; justify-content:center;">
    <div class="nav-button"><a href="signup.php">[sign up]</a></div><div class="nav-button"><a href="contribute.php">[contribute]</a></div><div class="nav-button"><a href="database.php">[database]</a></div><div class="nav-button"><a href="studio.php">[studio]</a></div>
  </div>

<br>
 
<div class="profileinfo" style="width:100%; min-height:30vh; background-color:lightgrey; color:white; display:flex; flex-direction:column; align-items:center; justify-content:center;">
<?php
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $profilePath = __DIR__ . "/p-users/" . $_SESSION['user_firstname'] . "_" . $_SESSION['user_lastname'] . "/profile.json";
    $ppDir = __DIR__ . "/p-users/" . $_SESSION['user_firstname'] . "_" . $_SESSION['user_lastname'] . "/pp";

 // --- BEGIN: VISIT PROFILE BUTTON ---
    // Show "visit profile" button at the top of profile info
  // Show "visit profile" button at the top of profile info
$profile_url = "profile.php?artist=" . urlencode($_SESSION['user_firstname'] . "_" . $_SESSION['user_lastname']);
echo '<div style="margin-top:16px; margin-bottom:18px; text-align:center;">';
echo '<a href="' . htmlspecialchars($profile_url) . '" style="background:#e8bebe; color:#222; padding:0.7em 2em; border-radius:7px; font-family:monospace; font-size:1.06em; display:inline-block; text-decoration:none; font-weight:bold; box-shadow:0 2px 8px #0002; margin-bottom:8px;">visit profile</a>';
echo '</div>';
    // --- END: VISIT PROFILE BUTTON ---

    // Show profile info
    if (file_exists($profilePath)) {
        $profileData = json_decode(file_get_contents($profilePath), true);
        if ($profileData) {
            echo '<div style="text-align:center;">';
            if (!empty($profileData['firstname']) || !empty($profileData['lastname'])) {
                echo '<h2 style="margin:0;">' . 
                     htmlspecialchars($profileData['firstname'] ?? '') . ' ' . 
                     htmlspecialchars($profileData['lastname'] ?? '') . 
                     '</h2>';
            }
            
            if (!empty($profileData['email'])) {
                echo '<p>Email: ' . htmlspecialchars($profileData['email']) . '</p>';
            }
            // Display current profile pic if exists and is set in JSON
            if (!empty($profileData['pp']) && file_exists(__DIR__ . '/' . $profileData['pp'])) {
                echo '<img src="' . htmlspecialchars($profileData['pp']) . '" alt="Profile Picture" style="width:100px;height:100px;object-fit:cover;border-radius:50%;margin:10px auto;display:block;background:#fff;" />';
            }
            echo '</div>';
        } else {
            echo '<span>Profile data could not be loaded.</span>';
        }
    } else {
        echo '<span>No profile found for this user.</span>';
    }

   // Display works if present
if (!empty($profileData['works']) && is_array($profileData['works'])) {
    echo '<div style="margin-top:18px; text-align:center;">';
    echo '<h3 style="margin-bottom:8px;">My Works</h3>';
    echo '<div style="display:flex; flex-wrap:wrap; gap:20px; justify-content:center;">';
    
    $workIndex = 0;
    foreach ($profileData['works'] as $work) {
        // Only show if image exists
        $imgPath = !empty($work['img']) ? __DIR__ . '/' . $work['img'] : '';
        if (!empty($work['img']) && file_exists($imgPath)) {
            echo '<div class="work-card" data-index="' . $workIndex . '" style="background:#222; padding:12px; border-radius:12px; min-width:160px; max-width:220px; color:#fff; box-shadow:0 2px 8px #0008; cursor:pointer;">';
            echo '<img src="' . htmlspecialchars($work['img']) . '" alt="Work Image" style="width:120px;height:120px;object-fit:cover;border-radius:8px; background:#fff; display:block; margin:0 auto 8px auto;">';
            if (!empty($work['title'])) {
                echo '<div style="font-weight:bold; margin-bottom:2px;">' . htmlspecialchars($work['title']) . '</div>';
            }
            if (!empty($work['date'])) {
                echo '<div style="font-size:0.92em; color:#b0e0ff; margin-bottom:4px;">' . htmlspecialchars($work['date']) . '</div>';
            }
            if (!empty($work['bio'])) {
                echo '<div style="font-size:0.97em; color:#e0e0e0;">' . nl2br(htmlspecialchars($work['bio'])) . '</div>';
            }
            echo '</div>';
            
            $workIndex++;
        }
    }
    echo '</div></div>';
    
    // Add hidden modal container at the end of this section
    echo '<div id="workModal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.85); overflow:auto;">
        <div style="position:relative; margin:5% auto; padding:20px; width:85%; max-width:900px; animation:modalFadeIn 0.3s;">
            <span id="closeModal" style="position:absolute; top:10px; right:20px; color:white; font-size:28px; font-weight:bold; cursor:pointer;">&times;</span>
            <div id="modalContent" style="background:#333; padding:25px; border-radius:15px; color:white;"></div>
        </div>
    </div>';
    
    // Store works data as JSON for JavaScript access
    echo '<script>
        const worksData = ' . json_encode($profileData['works']) . ';
    </script>';
}

    // Show upload message, if any
    if (!empty($uploadMsg)) {
        echo '<div style="margin:10px 0;">' . $uploadMsg . '</div>';
    }
    
    // Create a flex container for the forms
    echo '<div style="display: flex; flex-wrap: wrap; gap: 20px; width: 90%; max-width: 1200px; margin-top: 20px;">';
    
    // Left side container for image upload and work upload forms
    echo '<div style="flex: 1; min-width: 300px;">';
    
    // Image upload form:
    ?>
    <form action="" method="post" enctype="multipart/form-data" style="margin-top:12px;">
      <label for="profile_image">Upload profile image:</label>
      <input type="file" name="profile_image" id="profile_image" accept="image/*" required>
      <input type="submit" value="Upload">
      <div style="font-size:0.9em;color:#eee;">JPEG, PNG, GIF only.</div>
    </form>
    <?php
    
    if (isset($_SESSION['user_id'])) {
        // Show upload message, if any
        if (!empty($workMsg)) {
            echo '<div style="margin:10px 0;">' . $workMsg . '</div>';
        }
        ?>
        <form action="" method="post" enctype="multipart/form-data" style="margin-top:16px; border-top:1px solid #fff; padding-top:12px;">
          <input type="hidden" name="work_upload" value="1" />
          <div><b>Add a new work</b></div>
          <div style="margin:6px 0;">
            <label for="work_title">Title:</label><br>
            <input type="text" name="work_title" id="work_title" maxlength="80" required style="width:90%;">
          </div>
          <div style="margin:6px 0;">
            <label for="work_date">Date:</label><br>
            <input type="text" name="work_date" id="work_date" maxlength="40" style="width:90%;" placeholder="e.g. 2025-08-02">
          </div>
          <div style="margin:6px 0;">
            <label for="work_bio">Bio / Description:</label><br>
            <textarea name="work_bio" id="work_bio" maxlength="500" rows="2" style="width:90%;"></textarea>
          </div>
          <div style="margin:6px 0;">
            <label for="work_image">Upload work image:</label><br>
            <input type="file" name="work_image" id="work_image" accept="image/*" required>
          </div>
          <input type="submit" value="Upload Work">
          <div style="font-size:0.9em;color:#eee;">JPEG, PNG, GIF only.</div>
        </form>
        
        
    <?php
    // Close the left side container
    echo '</div>';
    
    // --- BEGIN: Editable 'pusers' row form - now positioned to the right ---
    // Fetch the user's current row from 'pusers' table to prefill the form
    $edit_columns = [
        'firstname', 'lastname','email', 'date', 'country', 'why'
    ];
    $user_edit_data = [];

    $user = 'root';
    $conn3 = new mysqli($host, $user, $password, $database);
    if (!$conn3->connect_error) {
        $stmt = $conn3->prepare("SELECT ".implode(',', $edit_columns)." FROM pusers WHERE id=? LIMIT 1");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $user_edit_data = $row;
        }
        $stmt->close();
        $conn3->close();
    }

    // Handle the POST of the edit form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_profile_submit'])) {
    $profilePath = __DIR__ . "/p-users/" . $_SESSION['user_firstname'] . "_" . $_SESSION['user_lastname'] . "/profile.json";
    
    // Load existing profile data or create new empty array
    $profileData = [];
    if (file_exists($profilePath)) {
        $profileData = json_decode(file_get_contents($profilePath), true);
        if (!$profileData) $profileData = []; // Handle invalid JSON
    }
    
    // Update profile data with form values
    $fieldsToUpdate = [
        'nickname', 'country', 'bio', 'bio2', 'fact1', 'fact2', 'fact3', 'link1', 'link2', 'link3'
    ];
    
    foreach ($fieldsToUpdate as $field) {
        $profileData[$field] = trim($_POST[$field] ?? '');
    }
    
    // Preserve existing fields that weren't in the form
    // Make sure firstname and lastname are always set
    if (!isset($profileData['firstname'])) {
        $profileData['firstname'] = $_SESSION['user_firstname'];
    }
    if (!isset($profileData['lastname'])) {
        $profileData['lastname'] = $_SESSION['user_lastname'];
    }
    
    // Save profile data back to JSON file
    $dirPath = dirname($profilePath);
    if (!file_exists($dirPath)) {
        mkdir($dirPath, 0777, true);
    }
    
    if (file_put_contents($profilePath, json_encode($profileData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
        // Success message
        echo "<div style='margin:14px 0;color:#bfffbf;'>Profile updated successfully!</div>";
        
        // Update the user_edit_data variable to show updated values in the form
        $user_edit_data = [];
        foreach ($fieldsToUpdate as $field) {
            $user_edit_data[$field] = $profileData[$field];
        }
    } else {
        echo "<div style='margin:14px 0;color:#ffbfbf;'>Error updating profile. Could not write to file.</div>";
    }
}

// We still need to load the existing data for displaying in the form
$user_edit_data = [];
$profilePath = __DIR__ . "/p-users/" . $_SESSION['user_firstname'] . "_" . $_SESSION['user_lastname'] . "/profile.json";
if (file_exists($profilePath)) {
    $profileData = json_decode(file_get_contents($profilePath), true);
    if ($profileData) {
        $fieldsToLoad = ['nickname', 'country', 'bio', 'bio2', 'fact1', 'fact2', 'fact3', 'link1', 'link2', 'link3'];
        foreach ($fieldsToLoad as $field) {
            $user_edit_data[$field] = $profileData[$field] ?? '';
        }
    }
}

    // Handle the POST of the delete form (deletes user from pusers)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_profile_submit'])) {
        $conn5 = new mysqli($host, $user, $password, $database);
        if (!$conn5->connect_error) {
            $stmt = $conn5->prepare("DELETE FROM pusers WHERE id=?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            if ($stmt->execute()) {
                // Log the user out and show a message before redirecting
                session_destroy();
                echo "<div style='margin:18px 0; color:#bfffbf; background:#223; border-radius:7px; padding:1.5em; font-size:1.1em; max-width:500px;'>Your profile has been deleted. You have been signed out.</div>";
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'v4.5.php';
                    }, 2500);
                </script>";
                // Stop further output for this request
                exit;
            } else {
                echo "<div style='margin:14px 0;color:#ffbfbf;'>Error deleting profile.</div>";
            }
            $stmt->close();
            $conn5->close();
        }
    }
    
    // Right side container - Edit Profile form
    echo '<div style="flex: 1; min-width: 300px;">';
    ?>
    <form action="" method="post" style="margin-top:30px; padding-top:30px; background:lightgrey; border-radius:12px; max-width:600px;">
  <input type="hidden" name="edit_profile_submit" value="1" />
  <h3 style="color:black; margin-bottom:10px;">Edit Profile Details</h3>
  
  <!-- First name, last name, date, and email inputs have been removed -->
  <div style="margin-top:8px;">
  <label for="nickname" style="color:#fff;">Nickname:</label>
  <input type="text" name="nickname" id="nickname" maxlength="40" style="width:99%;" value="<?php echo htmlspecialchars($user_edit_data['nickname'] ?? ''); ?>">
</div>
  <div style="margin-top:8px;">
    <label for="country" style="color:#fff;">Country:</label>
    <input type="text" name="country" id="country" maxlength="40" style="width:99%;" value="<?php echo htmlspecialchars($user_edit_data['country'] ?? ''); ?>">
  </div>
  <div style="margin-top:8px;">
    <label for="bio" style="color:#fff;">Bio:</label>
    <textarea name="bio" id="bio" maxlength="800" rows="3" style="width:99%;"><?php echo htmlspecialchars($user_edit_data['bio'] ?? ''); ?></textarea>
  </div>
  <div style="margin-top:8px;">
  <label for="bio2" style="color:#fff;">Bio 2:</label>
  <textarea name="bio2" id="bio2" maxlength="800" rows="3" style="width:99%;"><?php echo htmlspecialchars($user_edit_data['bio2'] ?? ''); ?></textarea>
</div>
  <div style="margin-top:8px;">
    <label for="fact1" style="color:#fff;">Fact 1:</label>
    <input type="text" name="fact1" id="fact1" maxlength="120" style="width:99%;" value="<?php echo htmlspecialchars($user_edit_data['fact1'] ?? ''); ?>">
  </div>
  <div style="margin-top:8px;">
    <label for="fact2" style="color:#fff;">Fact 2:</label>
    <input type="text" name="fact2" id="fact2" maxlength="120" style="width:99%;" value="<?php echo htmlspecialchars($user_edit_data['fact2'] ?? ''); ?>">
  </div>
  <div style="margin-top:8px;">
    <label for="fact3" style="color:#fff;">Fact 3:</label>
    <input type="text" name="fact3" id="fact3" maxlength="120" style="width:99%;" value="<?php echo htmlspecialchars($user_edit_data['fact3'] ?? ''); ?>">
  </div>
  <div style="margin-top:8px;">
    <label for="link1" style="color:#fff;">Link 1:</label>
    <input type="text" name="link1" id="link1" maxlength="250" style="width:99%;" value="<?php echo htmlspecialchars($user_edit_data['link1'] ?? ''); ?>">
  </div>
  <div style="margin-top:8px;">
    <label for="link2" style="color:#fff;">Link 2:</label>
    <input type="text" name="link2" id="link2" maxlength="250" style="width:99%;" value="<?php echo htmlspecialchars($user_edit_data['link2'] ?? ''); ?>">
  </div>
  <div style="margin-top:8px;">
    <label for="link3" style="color:#fff;">Link 3:</label>
    <input type="text" name="link3" id="link3" maxlength="250" style="width:99%;" value="<?php echo htmlspecialchars($user_edit_data['link3'] ?? ''); ?>">
  </div>
  <input type="submit" value="Save Details" style="margin:18px 0 0 0; padding:9px 2em; background:#bfffbf; color:#222; border:none; border-radius:6px; font-size:1.05em; cursor:pointer;">
</form>


       <!-- NEW: Comments Dropdown with Form -->
<div style="max-width:600px; margin:18px auto;">
  <details style="background:#23235e; border-radius:10px; color:#fff; padding:14px; margin-top:14px;">
    <summary style="cursor:pointer; font-size:1.1em; font-weight:bold; outline:none;">Leave a comment or question</summary>
    <div style="margin-top:18px;">
    <?php
    // Display any comment messages
    if (!empty($comment_msg)) {
        echo "<div style='margin-bottom:12px;'>$comment_msg</div>";
    }
    ?>
      <form method="post" style="display:flex; flex-direction:column; gap:14px;">
        <input type="hidden" name="submit_comment" value="1" />
        <label>
          Name:<br>
          <input type="text" name="comment_name" maxlength="60" required style="width:100%; padding:6px; border-radius:6px; border:1px solid #bbb;">
        </label>
        <label>
          Email:<br>
          <input type="email" name="comment_email" maxlength="80" required style="width:100%; padding:6px; border-radius:6px; border:1px solid #bbb;">
        </label>
        <label>
          Message:<br>
          <textarea name="comment_message" maxlength="800" rows="3" required style="width:100%; padding:6px; border-radius:6px; border:1px solid #bbb;"></textarea>
        </label>
        <button type="submit" style="background:#bfffbf; color:#222; border:none; border-radius:6px; font-size:1em; padding:9px 0; cursor:pointer;">Send Comment</button>
      </form>
    </div>
  </details>
</div>
        
        <!-- Delete Profile Button -->
        <form action="" method="post" onsubmit="return confirm('Are you sure you want to delete your profile? This cannot be undone!');" style="margin-top:18px; max-width:600px;">
          <input type="hidden" name="delete_profile_submit" value="1" />
          <button type="submit" style="background:#f55; color:#fff; padding:10px 2em; border:none; border-radius:6px; font-size:1em; margin-top:7px; cursor:pointer;">
            Delete My Profile
          </button>
        </form>

    <?php
    // Close the right side container
    echo '</div>';
    
    // Close the flex container for all forms
    echo '</div>';
        
    } // End session check for work form
    
} else {
    echo '<span style="color:black;">Please <b>sign in</b> to view your profile information.</span>';
}
?>

</div>




  <script async
  src="https://js.stripe.com/v3/buy-button.js">
</script>

 
  
  <div style="max-width:70vw; margin:2em auto; font-family:Segoe UI,Arial,sans-serif;">
    <button id="payBtn" style="width:100%;  box-shadow: 0 2px 10px #0004; trnsition:1s;  background-color: rgb(235, 168, 168); color:black; border:none; padding:0.7em 0; border-radius:8px; font-size:1em; cursor:pointer;">
      donate to the digital artist database
    </button>
    <div id="paymentPortal" style="display:none; margin-top:1em;  background-color: rgb(235, 168, 168); border-radius:10px; box-shadow:0 2px 8px #eee; padding:1.2em; text-align:center;">
      <stripe-buy-button
  buy-button-id="buy_btn_1RBP8wDKJyMVPD6MNwafC65z"
  publishable-key="pk_live_bcx2iXYjvNT7kmVPZ6k9P3Qy"
 style="opacity: 0.5;">
</stripe-buy-button>
    </div>
  </div>


  

  <footer style="background:#222; color:#eee; padding:2em 0; text-align:center; font-size:0.95em;">
  <div style="margin-bottom:1em;">
    <nav>
      <a href="/index.php" style="color:#eee; margin:0 15px; text-decoration:none;">Home</a>
      <a href="/signup.php.html" style="color:#eee; margin:0 15px; text-decoration:none;">Sign Up</a>
      <a href="/contribute.php" style="color:#eee; margin:0 15px; text-decoration:none;">Contribute</a>
      <a href="/database.php" style="color:#eee; margin:0 15px; text-decoration:none;">Database</a>
      
    </nav>
  </div>
  <div style="margin-bottom:1em;">
    <a href="https://twitter.com/" target="_blank" rel="noopener" style="margin:0 8px;">
      <img src="https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/twitter.svg" alt="Twitter" height="22" style="vertical-align:middle; filter:invert(1);">
    </a>
    <a href="https://facebook.com/" target="_blank" rel="noopener" style="margin:0 8px;">
      <img src="https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/facebook.svg" alt="Facebook" height="22" style="vertical-align:middle; filter:invert(1);">
    </a>
    <a href="https://instagram.com/" target="_blank" rel="noopener" style="margin:0 8px;">
      <img src="https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/instagram.svg" alt="Instagram" height="22" style="vertical-align:middle; filter:invert(1);">
    </a>
    <a href="https://github.com/" target="_blank" rel="noopener" style="margin:0 8px;">
      <img src="https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/github.svg" alt="GitHub" height="22" style="vertical-align:middle; filter:invert(1);">
    </a>
  </div>
  <div>
    &copy; 2025 Digital Artist Database. All Rights Reserved.
  </div>
</footer>



<script>
    document.getElementById('payBtn').onclick = function() {
      var portal = document.getElementById('paymentPortal');
      portal.style.display = (portal.style.display === 'none' || portal.style.display === '') ? 'block' : 'none';
    };
  </script>


<script>
    var images = <?php echo json_encode($images, JSON_PRETTY_PRINT); ?>;
    var current = 0;
    var timer = null;
    var imgElem = document.getElementById('slideshow-img');
    //var captionElem = document.getElementById('slideshow-caption');
    //var prevBtn = document.getElementById('prev-btn');
   // var nextBtn = document.getElementById('next-btn');
    var interval = 10000;

    function showImage(idx) {
      if (!images.length) {
        imgElem.src = '';
        imgElem.alt = 'No photos found';
        captionElem.textContent = 'No photos found in folder.';
        return;
      }
      current = (idx + images.length) % images.length;
      imgElem.src = images[current];
      imgElem.alt = 'Photo ' + (current + 1);
      //captionElem.textContent = 'Photo ' + (current + 1) + ' of ' + images.length;
    }

    function nextImage() { showImage(current + 1); }
    function prevImage() { showImage(current - 1); }

    //prevBtn.onclick = function() { prevImage(); resetTimer(); }
    //nextBtn.onclick = function() { nextImage(); resetTimer(); }

    function startTimer() { if (timer) clearInterval(timer); timer = setInterval(nextImage, interval); }
    function resetTimer() { startTimer(); }

    showImage(0);
    startTimer();
  </script>

  <script>
    var ARTISTS = <?php echo json_encode($jsonArray, JSON_PRETTY_PRINT); ?>;
    let filteredArtists = ARTISTS.slice(); // Current filtered list (default: all)
    console.log(ARTISTS); // You can use ARTISTS in your JS code
  </script>

  <script>
    function getArtist(index) {
      if (index < filteredArtists.length) return filteredArtists[index];
      let base = filteredArtists[index % filteredArtists.length];
      return base;
    }

    const container = document.getElementById('container');
    let loadedCount = 0;
    const BATCH_SIZE = 8;
    let openIndex = null;
    let isLoading = false;

    function renderArtist(index) {
      const artist = getArtist(index);
      const entry = document.createElement('div');
      entry.className = 'artist-entry';
      entry.setAttribute('data-idx', index);

      entry.innerHTML = `
        <img class="artist-pp" src="${artist.pp}" alt="Artist" />
        <span class="artist-firstname">${artist.firstname || ""}</span>
        <span class="artist-firstname">${artist.lastname || ""}</span>
        <span class="artist-date">${artist.date || ""}</span>
        <span class="artist-country">${artist.country || ""}</span>
        <span class="artist-genre">${artist.genre || ""}</span>
        
        <div class="dropdown">

        <div style="display: flex; align-items: center; background: #f5f7fa; padding: 1em 1.5em; border-radius: 12px; ">
          <img src="${artist.pp}" alt="Profile Picture" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-right: 1.5em; box-shadow: 0 2px 8px rgba(0,0,0,0.07);">
          <ul style="list-style: disc inside; margin: 0; padding: 0;">
             <li style="font-family: sans-serif;">${artist.fact1 || ""}</li>
             <li style="font-family: sans-serif;">${artist.fact2 || ""}</li>
             <li style="font-family: sans-serif;">${artist.fact3 || ""}</li>
          </ul>
          
       </div>

       <br>

          <div style="font-family: sans-serif;">${artist.bio || ""}</div>

      <br>
          
          <div class="work-container">
            <div class="works-list">
             <div class="work-card">
                <span style="width:300px;">${artist.work1}</span>
                ${artist.work1link ? `<img src="${artist.work1link}" loading="lazy" alt=""/>` : ''}
              </div>
            <div class="work-card">
                <span style="width:300px;">${artist.work2}</span>
                ${artist.work2link ? `<img src="${artist.work2link}" loading="lazy" alt=""/>` : ''}
              </div>
            <div class="work-card">
                <span style="width:300px;">${artist.work3}</span>
                ${artist.work3link ? `<img src="${artist.work3link}" loading="lazy" alt=""/>` : ''}
              </div>
            <div class="work-card">
                <span style="width:300px;">${artist.work4}</span>
                ${artist.work4link ? `<img src="${artist.work4link}" loading="lazy" alt=""/>` : ''}
              </div>
            <div class="work-card">
                <span style="width:300px;">${artist.work5}</span>
                ${artist.work5link ? `<img src="${artist.work5link}" loading="lazy" alt=""/>` : ''}
              </div>
            <div class="work-card">
                <span style="width:300px;">${artist.work6}</span>
                ${artist.work6link ? `<img src="${artist.work6link}" loading="lazy" alt=""/>` : ''}
              </div>
            </div>
          </div>

            <p style="padding:5px;" onclick="">visit profile</p>

          <div class="links-container">
          <a href="https://www.google.com" target="_blank" rel="noopener">Visit Google</a>
         <a class="artist-link1" href="${artist.link1}">${artist.link1 || ""}</a>
         <span class="artist-link2">${artist.link2 || ""}</span>
         <span class="artist-link3">${artist.link3 || ""}</span>
         </div>

         <br>


        </div>
      `;
      entry.addEventListener('click', function(e) {
        if (
          e.target.classList.contains('work-card') ||
          e.target.tagName === 'IMG' || 
          e.target.closest('.dropdown')
        ) return;
        entry.classList.toggle('open');
      });
      return entry;
    }

    function clearContainer() {
      container.innerHTML = '';
      loadedCount = 0;
    }

    function loadMore() {
      if (isLoading) return;
      isLoading = true;
      for (let i=loadedCount; i<loadedCount+BATCH_SIZE && i<filteredArtists.length; i++) {
        container.appendChild(renderArtist(i));
      }
      loadedCount += BATCH_SIZE;
      isLoading = false;
    }

    container.addEventListener('scroll', function() {
      if (container.scrollTop + container.clientHeight >= container.scrollHeight - 80) {
        loadMore();
      }
    });

    function fillToScreen() {
      if (container.scrollHeight < window.innerHeight+80 && loadedCount < filteredArtists.length) {
        loadMore();
        setTimeout(fillToScreen, 10);
      }
    }

    // Initial fill
    loadMore();
    setTimeout(fillToScreen, 10);

    // ----------- ALPHABETICAL SORT BUTTON FUNCTIONALITY ----------
    document.getElementById('sortAlphaBtn').onclick = function() {
      filteredArtists.sort(function(a, b) {
        var lnameA = (a.lastname || '').toLowerCase();
        var lnameB = (b.lastname || '').toLowerCase();
        if (lnameA < lnameB) return -1;
        if (lnameA > lnameB) return 1;
        var fnameA = (a.firstname || '').toLowerCase();
        var fnameB = (b.firstname || '').toLowerCase();
        if (fnameA < fnameB) return -1;
        if (fnameA > fnameB) return 1;
        return 0;
      });
      clearContainer();
      loadMore();
      setTimeout(fillToScreen, 10);
    };

    // ----------- DATE SORT BUTTON FUNCTIONALITY ----------
    document.getElementById('sortDateBtn').onclick = function() {
      filteredArtists.sort(function(a, b) {
        var dateA = Date.parse(a.date) || 0;
        var dateB = Date.parse(b.date) || 0;
        if (dateA && dateB) {
          return dateA - dateB;
        } else {
          var strA = (a.date || '').toLowerCase();
          var strB = (b.date || '').toLowerCase();
          if (strA < strB) return -1;
          if (strA > strB) return 1;
          return 0;
        }
      });
      clearContainer();
      loadMore();
      setTimeout(fillToScreen, 10);
    };

    // ----------- COUNTRY SORT BUTTON FUNCTIONALITY ----------
    document.getElementById('sortCountryBtn').onclick = function() {
      filteredArtists.sort(function(a, b) {
        var countryA = (a.country || '').toLowerCase();
        var countryB = (b.country || '').toLowerCase();
        if (countryA < countryB) return -1;
        if (countryA > countryB) return 1;
        return 0;
      });
      clearContainer();
      loadMore();
      setTimeout(fillToScreen, 10);
    };

    // ----------- gENRE SORT BUTTON FUNCTIONALITY ----------
    document.getElementById('sortGenreBtn').onclick = function() {
      filteredArtists.sort(function(a, b) {
        var genreA = (a.genre || '').toLowerCase();
        var genreB = (b.genre || '').toLowerCase();
        if (genreA < genreB) return -1;
        if (genreA > genreB) return 1;
        return 0;
      });
      clearContainer();
      loadMore();
      setTimeout(fillToScreen, 10);
    };

    // ----------- SEARCH BAR FUNCTIONALITY -----------
    const searchBar = document.getElementById('artistSearchBar');
    searchBar.addEventListener('input', function() {
      const query = searchBar.value.trim().toLowerCase();
      if (query === '') {
        filteredArtists = ARTISTS.slice();
      } else {
        filteredArtists = ARTISTS.filter(function(artist) {
          return (
            (artist.firstname && artist.firstname.toLowerCase().includes(query)) ||
            (artist.lastname && artist.lastname.toLowerCase().includes(query)) ||
            (artist.country && artist.country.toLowerCase().includes(query)) ||
            (artist.genre && artist.genre.toLowerCase().includes(query)) ||
            (artist.bio && artist.bio.toLowerCase().includes(query)) ||
            (artist.fact1 && artist.fact1.toLowerCase().includes(query)) ||
            (artist.fact2 && artist.fact2.toLowerCase().includes(query)) ||
            (artist.fact3 && artist.fact3.toLowerCase().includes(query)) ||
            (artist.date && artist.date.toLowerCase().includes(query))
          );
        });
      }
      clearContainer();
      loadMore();
      setTimeout(fillToScreen, 10);
    });

    // --------------------- Add Artist Modal ---------------------
    const addArtistBtn = document.getElementById('addArtistBtn');
    const addArtistFormOverlay = document.getElementById('addArtistFormOverlay');
    const addArtistForm = document.getElementById('addArtistForm');
    const worksListDiv = document.getElementById('worksList');
    const addWorkBtn = document.getElementById('addWorkBtn');
    const cancelArtistBtn = document.getElementById('cancelArtistBtn');

    let workFields = [];

    addArtistBtn.onclick = function() {
      addArtistFormOverlay.style.display = 'flex';
      addArtistForm.reset();
      worksListDiv.innerHTML = '';
      workFields = [];
      addWorkField();
      document.getElementById('artistName').focus();
    };

    cancelArtistBtn.onclick = function() {
      addArtistFormOverlay.style.display = 'none';
    };
    addArtistFormOverlay.onclick = function(e) {
      if (e.target === addArtistFormOverlay) addArtistFormOverlay.style.display = 'none';
    };

    function addWorkField(defaults={}) {
      const idx = workFields.length;
      const div = document.createElement('div');
      div.className = 'works-list-entry';
      div.innerHTML = `
        <input type="text" required maxlength="60" placeholder="Work Title" value="${defaults.title||''}" style="margin-bottom:0.2em;width:43%;" />
        <input type="text" maxlength="250" placeholder="Image URL (optional)" value="${defaults.img||''}" style="margin-bottom:0.2em;width:50%;" />
        <button type="button" class="removeWorkBtn" title="Remove work">×</button>
      `;
      const [titleField, imgField, removeBtn] = div.children;
      removeBtn.onclick = function() {
        worksListDiv.removeChild(div);
        workFields = workFields.filter(f => f !== fieldObj);
      };
      worksListDiv.appendChild(div);
      const fieldObj = {titleField, imgField, div};
      workFields.push(fieldObj);
    }

    addWorkBtn.onclick = function(e) {
      addWorkField();
    };

    addArtistForm.onsubmit = function(e) {
      e.preventDefault();
      const name = document.getElementById('artistName').value.trim();
      const years = document.getElementById('artistYears').value.trim();
      const bio = document.getElementById('artistBio').value.trim();
      if (!name || !bio) return;

      const works = [];
      for (const wf of workFields) {
        const title = wf.titleField.value.trim();
        const img = wf.imgField.value.trim();
        if (!title) continue;
        works.push({title, img});
      }
      if (works.length === 0) {
        alert('Please add at least one work.');
        return;
      }
      ARTISTS.unshift({name, years, bio, works});
      filteredArtists = ARTISTS.slice();
      clearContainer();
      loadMore();
      setTimeout(fillToScreen, 10);
      addArtistFormOverlay.style.display = 'none';
      setTimeout(()=>{
        container.firstChild.classList.add('open');
        container.firstChild.scrollIntoView({behavior:'smooth', block:'center'});
      }, 80);
    };
  </script>
<!--
<script>
    // background-fader.js
// Gradually transitions the background color from one random color to another on page load.

function randomColor() {
  // Generate a random color in rgb format
  const r = Math.floor(Math.random() * 256)
  const g = Math.floor(Math.random() * 256)
  const b = Math.floor(Math.random() * 256)
  return { r, g, b }
}

function colorToString({ r, g, b }) {
  return `rgb(${r},${g},${b})`
}

function lerp(a, b, t) {
  // Linear interpolation between a and b
  return a + (b - a) * t
}

function lerpColor(c1, c2, t) {
  return {
    r: Math.round(lerp(c1.r, c2.r, t)),
    g: Math.round(lerp(c1.g, c2.g, t)),
    b: Math.round(lerp(c1.b, c2.b, t))
  }
}

window.onload = function () {
  const startColor = randomColor()
  const endColor = randomColor()
  let t = 0
  const duration = 20000 // ms (2 seconds)
  const interval = 20 // ms per frame
  const steps = duration / interval

  function step() {
    t += 1 / steps
    if (t > 1) t = 1
    const currentColor = lerpColor(startColor, endColor, t)
    document.body.style.backgroundColor = colorToString(currentColor)
    if (t < 1) {
      setTimeout(step, interval)
    }
  }
  step()
}
</script>
-->

<script>
// Add this at the end of your file, before closing body tag
document.addEventListener('DOMContentLoaded', function() {
    // Work card expansion functionality
    const modal = document.getElementById('workModal');
    const modalContent = document.getElementById('modalContent');
    const closeBtn = document.getElementById('closeModal');
    
    // Exit if modal elements aren't found (might happen if user has no works)
    if (!modal || !modalContent || !closeBtn) return;
    
    // Add click handler to all work cards
    document.querySelectorAll('.work-card').forEach(card => {
        card.addEventListener('click', function() {
            const index = parseInt(this.getAttribute('data-index'));
            const work = worksData[index];
            
            if (work) {
                let content = `
                    <div style="display:flex; flex-direction:column; align-items:center;">
                        <h2 style="margin-bottom:15px; font-size:24px;">${work.title || 'Untitled Work'}</h2>
                        
                        <div style="width:100%; max-width:600px; margin-bottom:20px; text-align:center;">
                            <img src="${work.img}" alt="${work.title || 'Work Image'}" 
                                style="max-width:100%; max-height:70vh; object-fit:contain; border-radius:10px; box-shadow:0 4px 20px rgba(0,0,0,0.3);">
                        </div>
                        
                        <div style="width:100%; max-width:700px; text-align:left;">
                            ${work.date ? '<p style="color:#b0e0ff; margin-bottom:15px; font-size:16px;">Created: ' + work.date + '</p>' : ''}
                            ${work.bio ? '<div style="line-height:1.6; font-size:16px; margin-top:15px;">' + work.bio.replace(/\n/g, '<br>') + '</div>' : ''}
                        </div>
                        
                        <div style="margin-top:30px;">
                            <button id="closeModalBtn" style="background:#e27979; color:white; border:none; padding:10px 20px; border-radius:5px; cursor:pointer; font-size:16px;">Close</button>
                        </div>
                    </div>
                `;
                
                modalContent.innerHTML = content;
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden'; // Prevent scrolling while modal is open
                
                // Add event listener to the new close button inside modal
                document.getElementById('closeModalBtn').addEventListener('click', closeModal);
            }
        });
    });
    
    // Close modal when clicking X button
    closeBtn.addEventListener('click', closeModal);
    
    // Close modal when clicking outside content
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
    
    // Close on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.style.display === 'block') {
            closeModal();
        }
    });
    
    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = ''; // Restore scrolling
    }
    
    // Add this CSS for animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes modalFadeIn {
            from {opacity: 0; transform: translateY(-20px);}
            to {opacity: 1; transform: translateY(0);}
        }
    `;
    document.head.appendChild(style);
});
</script>

</body>

</html>