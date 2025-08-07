<?php

session_start();

// Handle signout immediately - before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signout'])) {
    // Clear all session data
    $_SESSION = array();
    
    // If a session cookie is used, destroy it
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to prevent form resubmission
    header("Location: v4.5.php");
    exit;
}

// Replace with your actual database credentials
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'mysql';

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
// --- SLIDESHOW IMAGES FROM p-users/*/work ---
$images = [];
$pusersDir = __DIR__ . '/p-users';

if (is_dir($pusersDir)) {
    $userFolders = scandir($pusersDir);
    foreach ($userFolders as $userFolder) {
        if ($userFolder === '.' || $userFolder === '..') continue;
        $workDir = $pusersDir . '/' . $userFolder . '/work';
        if (is_dir($workDir)) {
            $workFiles = scandir($workDir);
            foreach ($workFiles as $file) {
                if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $file)) {
                    $images[] = 'p-users/' . $userFolder . '/work/' . $file;
                }
            }
        }
    }
}
// Randomize the order of images
shuffle($images);
?>

<?php
// Handle sign in POST
$signin_error = "";
$signin_success = false;

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

?>

<?php
// Utility function to create user directory, subfolders, and store JSON
function createUserFolderAndJson( $firstname, $lastname, $email, $pword, $date, $country, $why) {
    // Sanitize folder name: only allow letters, numbers, hyphens, underscores
    $folderName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', strtolower($firstname . '_' . $lastname));
    $baseDir = __DIR__ . '/p-users';
    if (!is_dir($baseDir)) mkdir($baseDir, 0777, true);
    $userDir = $baseDir . '/' . $folderName;
    if (!is_dir($userDir)) mkdir($userDir, 0777, true);

    // Create 'pp' and 'work' subfolders
    $ppDir = $userDir . '/pp';
    $workDir = $userDir . '/work';
    if (!is_dir($ppDir)) mkdir($ppDir, 0777, true);
    if (!is_dir($workDir)) mkdir($workDir, 0777, true);

    // Create or update user.json in that folder
    $userData = [
        'firstname' => $firstname,
        'lastname'  => $lastname,
        'email'     => $email,
        'pword'     => $pword,
        'date'      => $date,
        'country'   => $country,
        'why'       => $why
    ];

    $jsonPath = $userDir . '/profile.json';
    file_put_contents($jsonPath, json_encode($userData, JSON_PRETTY_PRINT));
    // Optionally, set permissions
    @chmod($jsonPath, 0666);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['firstname'])) {
    // Get POST data safely
    $firstname = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
    $lastname = isset($_POST['lastname']) ? trim($_POST['lastname']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $pword = isset($_POST['pword']) ? trim($_POST['pword']) : '';
    $date = isset($_POST['date']) ? trim($_POST['date']) : '';
    $country = isset($_POST['country']) ? trim($_POST['country']) : '';
    $why = isset($_POST['why']) ? trim($_POST['why']) : '';

    // Simple validation (optional, improve as needed)
    if ($firstname && $lastname && $email && $pword && $date && $country && $why) {
        // Connect to MySQL
        $conn = new mysqli($host, $user, $password, $database);

        if ($conn->connect_error) {
            die('Connection failed: ' . $conn->connect_error);
        }

        // Prepare statement to avoid SQL injection
        $stmt = $conn->prepare("INSERT INTO pusers (firstname, lastname, email, pword, date, country, why) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $firstname, $lastname, $email, $pword, $date, $country, $why);

        if ($stmt->execute()) {
            // Create user folder, subfolders, and save JSON data
            createUserFolderAndJson($firstname, $lastname, $email, $pword, $date, $country, $why);
           // Redirect to avoid resubmission
            header('Location: v4.5.php?success=1');
            exit;
        } else {
            echo "<p>Error: " . $stmt->error . "</p>";
        }
        $stmt->close();
        $conn->close();
    } else {
        echo "<p>Please fill in all fields!</p>";
    }
}

// Show a message if redirected after successful signup
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $successMsg = "<p>Thank you! Your data has been submitted.</p>";
}

?>







<!--//////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////PHP ABOVE///////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////// -->


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>digital artist database</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" type="text/css" href="style.css">

  <script>

/**
 * Authentication Service
 * Handles both new client-side hashing and legacy authentication
 */

class AuthService {
  // Hash a password using SHA-256
  static async hashPassword(password) {
    const encoder = new TextEncoder();
    const passwordData = encoder.encode(password);
    const hashBuffer = await crypto.subtle.digest('SHA-256', passwordData);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
  }
  
  // Determine if a string is likely already a SHA-256 hash
  static isLikelyHash(str) {
    // SHA-256 hashes are 64 characters long, hex only
    return /^[a-f0-9]{64}$/i.test(str);
  }
}

// Add to window object for global access
window.AuthService = AuthService;

</script>



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

<?php
// Handle signout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signout'])) {
    session_destroy();
    header("Location: v4.5.php");
    exit;
}
?>

<br>

 <div style="display:flex; align-content:center; justify-content:center;">
    <div class="nav-button"><a href="signup.php">[sign up]</a></div><div class="nav-button"><a href="contribute.php">[contribute]</a></div><div class="nav-button"><a href="database.php">[database]</a></div><div class="nav-button"><a href="studio.php">[studio]</a></div>
  </div>

  

   <div id="slideshow-container" style="position:relative;">
  <img id="slideshow-img" src="" alt="Slideshow photo" style="object-fit: cover; width:100%; border-radius:7px; height:550px;">
 <!-- First, modify the slideshow overlay HTML structure: -->
<div id="slideshow-overlay"
     style="
       position:absolute;
       right:0;
       bottom:0;
       background: linear-gradient(to top left, rgba(249, 249, 249, 0.92) 70%, rgba(255, 255, 255, 1) 100%);
       color:black;
       padding:30px 32px 24px 38px;
       font-family:monospace;
       border-bottom-right-radius:7px;
       border-top-left-radius:10px;
       min-width:260px;
       max-width:60vw;
       display:flex;
       flex-direction:column;
       justify-content:center;
       align-items:flex-end;
       pointer-events:none;
       text-align:right;
       z-index:2;
     ">
  <div id="slideshow-title" style="font-size:1.3em; line-height:1.3; margin-bottom:12px; font-weight:bold; display:block; width:100%; text-align:right;"></div>
  <div id="slideshow-date" style="font-size:0.9em; color:#555; display:block; width:100%; text-align:right;"></div>
</div>
</div>
    <!--<div class="slideshow-controls">
      <button id="prev-btn" >&larr; Prev</button>
      <button id="next-btn">Next &rarr;</button>
    </div>
    
    <div class="slideshow-caption" id="slideshow-caption"></div>
-->
  </div>

 
<br>
  <br>

<div class="container-container-container" style="display:grid; align-items:center; justify-items: center;"> 
<div class="container-container" style="border: double; border-radius:20px; padding-top:50px; width:90%; align-items:center; justify-items: center; display:grid;   background-color: #f2e9e9; box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.1);">

<div style="display:flex; justify-content: center; align-items:center;">
  <div>
    <input type="text" id="artistSearchBar" placeholder="Search artists..." style="width:60vw; padding:0.6em 1em; font-size:1em; border-radius:7px; border:1px solid #ccc;">
  </div>
</div>

<!-- SORT BUTTONS AND SEARCH BAR ROW (MODIFIED) -->
<div style="display:flex; justify-content:center; align-items:center; margin:1em 0 1em 0;">
  <!-- SEARCH BAR MOVED TO THE LEFT -->
  
 

  <button id="sortAlphaBtn" style="padding:0.7em 1.3em; font-family: monospace; font-size:1em; color: black; background-color: rgba(255, 255, 255, 0); border:none; border-radius:8px; cursor:pointer;">
    name
  </button>
  <button id="sortDateBtn" style="padding:0.7em 1.3em; font-family: monospace; font-size:1em; background-color: rgba(255, 255, 255, 0); color:black; border:none; border-radius:8px; cursor:pointer;">
    date
  </button>
  <button id="sortCountryBtn" style="padding:0.7em 1.3em; font-family: monospace; font-size:1em; background-color: rgba(255, 255, 255, 0); color:black; border:none; border-radius:8px; cursor:pointer;">
    country
  </button>
  <button id="sortGenreBtn" style="padding:0.7em 1.3em; font-family: monospace; font-size:1em; background-color: rgba(255, 255, 255, 0); color:black; border:none; border-radius:8px; cursor:pointer;">
    genre
  </button>
</div>
 

  <div id="container"></div>

  <br><br><br><br><br>

</div>
</div> 

<br><br><br>

<div style="color:black; justify-self:center; width:70%;">
    <h1 style="color:black; justify-self:center;">Join the Digital Artist Database Community!<br><br>
    <h2 style="color:black; width:70%; justify-self:center;"> show your work with artists from around the world</h2>
    <p style="color:black; width:70%; justify-self:center;"> a comprehensive database of artists across time and space, the digital artist database is an all-inclusive and exhuastive resource for artistic research, inspiration, and creation in every tendral.</p>
</div>

<h2 style="color:black; justify-self:center;">▼</h2>

<br><br>

<div class="container-container-container" style="display:grid; align-items:center; justify-items: center;"> 
  <div class="container-container" style="border: double; border-radius:20px; padding:50px; width:70%; align-items:center; justify-items: center; display:grid; background-color: #f2e9e9; box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.1);">
    
    <h1 style="color:black;">Create Your Account</h1>

    <div class="containerone">
      <form id="signupForm" method="POST" action="" autocomplete="off" class="signup-form">
        <div class="form-group">
          <label for="firstname" class="formlabel">First Name:</label>
          <input type="text" id="firstname" name="firstname" required 
                 class="form-control" placeholder="Enter your first name">
        </div>

        <div class="form-group">
          <label for="lastname" class="formlabel">Last Name:</label>
          <input type="text" id="lastname" name="lastname" required 
                 class="form-control" placeholder="Enter your last name">
        </div>

        <div class="form-group">
          <label for="email" class="formlabel">Email:</label>
          <input type="email" id="email" name="email" required 
                 class="form-control" placeholder="Enter your email address">
        </div>

        <div class="form-group">
          <label for="password" class="formlabel">Password:</label>
          <input type="password" id="password" name="pword" required 
                 class="form-control" placeholder="Create a strong password"
                 pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$"
                 title="Password must be at least 8 characters and include uppercase, lowercase, number and special character">
          <div id="password_strength" class="password-strength"></div>
          <small class="form-text text-muted" style="color:black;">
            Password must be at least 8 characters and include uppercase, lowercase, 
            number and special character
          </small>
        </div>

        <div class="form-group">
          <label for="confirm_password" class="formlabel">Confirm Password:</label>
          <input type="password" id="confirm_password" name="confirm_password" required 
                 class="form-control" placeholder="Confirm your password">
        </div>

        <div class="form-group">
          <label for="date" class="formlabel">Date of Birth:</label>
          <input type="date" id="date" name="date" required 
                 class="form-control">
        </div>

        <div class="form-group">
          <label for="country" class="formlabel">Country:</label>
          <select id="country" name="country" required class="form-control">
            <option value="">Select your country</option>
            <option value="Afghanistan">Afghanistan</option>
            <option value="Albania">Albania</option>
            <!-- Add more countries alphabetically -->
            <option value="United Kingdom">United Kingdom</option>
            <option value="United States">United States</option>
            <!-- Continue with remaining countries -->
            <option value="Zimbabwe">Zimbabwe</option>
          </select>
        </div>

        <div class="form-group">
          <label for="why" class="formlabel">Why are you joining?</label>
          <textarea id="why" name="why" required class="form-control" 
                    placeholder="Tell us why you want to join the digital artist database"></textarea>
        </div>
       
        <div class="form-group">
          <button type="submit" class="submit-btn">Create Account</button>
        </div>

      </form>
    </div>
  </div>
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

<!-- First, locate the slideModal HTML and update it: -->
<div id="slideModal" style="display:none; position:fixed; top:0; left:0;right:0;bottom:0; z-index:9999; background:rgba(0,0,0,0.7); align-items:center; justify-content:center;">
  <div id="slideCard" style="background:white; border-radius:14px; padding:24px 28px; max-width:90vw; max-height:90vh; box-shadow:0 8px 32px #0005; display:flex; flex-direction:column; align-items:center; position:relative;">
    <button id="closeSlideModal" style="position:absolute; top:12px; right:18px; font-size:1.3em; background:none; border:none; color:#333; cursor:pointer;">×</button>
    <img id="modalImg" src="" alt="Image" style="max-width:80vw; max-height:60vh; border-radius:8px; margin-bottom:22px;">
    <div id="modalInfo" style="text-align:center; width:100%;">
      <h2 id="modalTitle" style="color:black; margin-bottom:8px; font-size:24px;"></h2>
      <p id="modalDate" style="color:black; margin-bottom:12px; font-size:16px;"></p>
      <p id="modalArtist" style="color:black; font-weight:bold; font-size:18px;"></p>
    </div>
    <button id="visitProfileBtn" style="margin-top:18px; background:#e8bebe; border:none; border-radius:7px; padding:0.7em 2em; font-family:monospace; font-size:1em; cursor:pointer;">visit profile</button>
  </div>
</div>

<!-- Add this modal container for expanded work cards to the HTML part of the file, just before the closing body tag -->
<div id="workModal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.85); overflow:auto;">
  <div style="position:relative; margin:5% auto; padding:20px; width:85%; max-width:900px; animation:modalFadeIn 0.3s;">
    <span id="closeModal" style="position:absolute; top:10px; right:20px; color:white; font-size:28px; font-weight:bold; cursor:pointer;">&times;</span>
    <div id="modalContent" style="background:#333; padding:25px; border-radius:15px; color:white;"></div>
  </div>
</div>

<!-- First, add this full-screen image container element before the closing body tag -->
<div id="fullscreenImage" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.95); z-index:10000; cursor:zoom-out;">
  <div style="position:absolute; top:15px; right:20px; color:white; font-size:30px; cursor:pointer;" id="closeFullscreen">&times;</div>
  <img id="fullscreenImg" src="" alt="Fullscreen Image" style="position:absolute; top:0; left:0; right:0; bottom:0; margin:auto; max-width:95vw; max-height:95vh; object-fit:contain; transition:all 0.3s ease;">
</div>

<!--//////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////SCRIPTS BELOW///////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////// -->




<script>
// Add this code to your existing slideModal script or as a new script before the closing body tag
document.addEventListener('DOMContentLoaded', function() {
  // Get DOM elements
  const fullscreenContainer = document.getElementById('fullscreenImage');
  const fullscreenImg = document.getElementById('fullscreenImg');
  const closeFullscreenBtn = document.getElementById('closeFullscreen');
  
  // Function to toggle fullscreen mode
  function showFullscreen(imgSrc) {
    fullscreenImg.src = imgSrc;
    fullscreenContainer.style.display = 'block';
    document.body.style.overflow = 'hidden'; // Prevent scrolling
    
    // Animation effect
    fullscreenImg.style.opacity = '0';
    fullscreenImg.style.transform = 'scale(0.9)';
    setTimeout(() => {
      fullscreenImg.style.opacity = '1';
      fullscreenImg.style.transform = 'scale(1)';
    }, 10);
  }
  
  // Function to close fullscreen
  function closeFullscreen() {
    fullscreenImg.style.opacity = '0';
    fullscreenImg.style.transform = 'scale(0.9)';
    setTimeout(() => {
      fullscreenContainer.style.display = 'none';
      document.body.style.overflow = ''; // Restore scrolling
    }, 200);
  }
  
  // Close fullscreen on X button click
  if (closeFullscreenBtn) {
    closeFullscreenBtn.addEventListener('click', closeFullscreen);
  }
  
  // Close fullscreen on background click
  if (fullscreenContainer) {
    fullscreenContainer.addEventListener('click', function(e) {
      if (e.target === fullscreenContainer || e.target === fullscreenImg) {
        closeFullscreen();
      }
    });
  }
  
  // Close on ESC key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && fullscreenContainer.style.display === 'block') {
      closeFullscreen();
    }
  });
  
  // Connect to modal image
  const modalImg = document.getElementById('modalImg');
  if (modalImg) {
    modalImg.style.cursor = 'zoom-in';
    modalImg.addEventListener('click', function(e) {
      e.stopPropagation(); // Prevent closing the modal
      showFullscreen(this.src);
    });
  }
  
  // Also connect to work card images in the main content
  document.addEventListener('click', function(e) {
    // If it's an image in a work-card that's already in the expanded modal view
    if (e.target.tagName === 'IMG' && e.target.closest('#modalContent')) {
      showFullscreen(e.target.src);
    }
  });
});
</script>


<!-- Then add this JavaScript to handle the work card clicks, just before the closing body tag -->
<script>
// Work card expansion functionality
document.addEventListener('DOMContentLoaded', function() {
  // Set up delegated event listener for work cards
  document.addEventListener('click', function(e) {
    // Find if the clicked element is a work card or an image inside a work card
    const workCard = e.target.closest('.work-card');
    if (!workCard) return;
    
    // Stop event propagation to prevent entry from toggling open/closed
    e.stopPropagation();
    
    // Get the work data
    const title = workCard.querySelector('span')?.textContent || 'Artwork';
    const img = workCard.querySelector('img')?.src || '';
    
    if (!img) return; // No image to show
    
    // Find artist info from parent entry
    const entry = workCard.closest('.artist-entry');
    let artistName = 'Artist';
    if (entry) {
      const firstname = entry.querySelector('.artist-firstname')?.textContent || '';
      const lastname = entry.querySelector('span:nth-child(3)')?.textContent || '';
      artistName = (firstname + ' ' + lastname).trim();
    }
    
    // Populate modal content
    const modalContent = document.getElementById('modalContent');
    modalContent.innerHTML = `
      <div style="display:flex; flex-direction:column; align-items:center;">
        <h2 style="margin-bottom:15px; font-size:24px; color:white;">${title}</h2>
        
        <div style="width:100%; max-width:750px; margin-bottom:20px; text-align:center;">
          <img src="${img}" alt="${title}" 
            style="max-width:100%; max-height:70vh; object-fit:contain; border-radius:10px; box-shadow:0 4px 20px rgba(0,0,0,0.3);">
        </div>
        
        <div style="margin-top:10px; color:#aaa; font-size:16px;">
          By ${artistName}
        </div>
        
        <div style="margin-top:30px;">
          <button id="closeModalBtn" style="background:#e27979; color:white; border:none; padding:10px 20px; border-radius:5px; cursor:pointer; font-size:16px;">Close</button>
        </div>
      </div>
    `;
    
    // Show modal
    const modal = document.getElementById('workModal');
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden'; // Prevent scrolling while modal is open
    
    // Add event listener to the close button
    document.getElementById('closeModalBtn').addEventListener('click', closeModal);
  });
  
  // Close modal when clicking X button
  const closeBtn = document.getElementById('closeModal');
  if (closeBtn) {
    closeBtn.addEventListener('click', closeModal);
  }
  
  // Close modal when clicking outside content
  const modal = document.getElementById('workModal');
  if (modal) {
    modal.addEventListener('click', function(e) {
      if (e.target === modal) {
        closeModal();
      }
    });
  }
  
  // Close on ESC key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && modal && modal.style.display === 'block') {
      closeModal();
    }
  });
  
  function closeModal() {
    const modal = document.getElementById('workModal');
    if (modal) {
      modal.style.display = 'none';
      document.body.style.overflow = ''; // Restore scrolling
    }
  }
  
  // Add CSS for animation
  const style = document.createElement('style');
  style.textContent = `
    @keyframes modalFadeIn {
      from {opacity: 0; transform: translateY(-20px);}
      to {opacity: 1; transform: translateY(0);}
    }
    .work-card {
      cursor: pointer;
      transition: transform 0.2s ease-in-out;
    }
    .work-card:hover {
      transform: scale(1.03);
    }
  `;
  document.head.appendChild(style);
});
</script>

<script>
// Function to generate a random linear gradient and apply to various elements
function randomLinearGradient() {
  function randColor() {
    const h = Math.floor(Math.random() * 360);
    const s = 65 + Math.random() * 20;
    const l = 63 + Math.random() * 15;
    return `hsl(${h},${s}%,${l}%)`;
  }
  const color1 = randColor();
  const color2 = randColor();
  return [color1, color2];
}

function setThemeGradient() {
  var titleDiv = document.getElementById('mainTitleContainer');
  var dotDiv = document.getElementById('dot');
  var entries = document.querySelectorAll('.artist-entry');
  var signInBtns = Array.from(document.querySelectorAll("input[type='submit'][value='sign in'], input[type='submit'][value='Sign In']"));

  var [color1, color2] = randomLinearGradient();
  var grad = `linear-gradient(135deg, ${color1} 60%, ${color2} 100%)`;
  var gradDot = `linear-gradient(135deg, ${color1} 40%, ${color2} 100%)`;

  if (titleDiv) titleDiv.style.backgroundImage = grad;
  if (dotDiv) dotDiv.style.background = gradDot;

  entries.forEach(function(entry) {
    entry.style.transition = "background 0.7s, box-shadow 0.3s";
    entry.style.background = `linear-gradient(120deg, ${color1} 0%, ${color2} 100%)`;
    entry.style.boxShadow = "0 2px 18px 0 rgba(0,0,0,0.12)";
    entry.style.color = "#fff";
  });

  signInBtns.forEach(function(btn){
    btn.style.background = grad;
    btn.style.transition = "background 0.7s, color 0.2s";
    btn.style.color = "#fff";
    btn.style.fontWeight = "bold";
    btn.style.boxShadow = "0 2px 14px #0002";
    btn.onmouseover = function() { btn.style.filter = "brightness(1.13)"; };
    btn.onmouseleave = function() { btn.style.filter = ""; };
  });
}

function setBWTheme() {
  var titleDiv = document.getElementById('mainTitleContainer');
  var dotDiv = document.getElementById('dot');
  var entries = document.querySelectorAll('.artist-entry');
  var signInBtns = Array.from(document.querySelectorAll("input[type='submit'][value='sign in'], input[type='submit'][value='Sign In']"));

  if (titleDiv) {
    titleDiv.style.backgroundImage = "linear-gradient(135deg, #222 0%, #e0e0e0 100%)";
    titleDiv.style.color = "#222";
  }
  if (dotDiv) {
    dotDiv.style.background = "linear-gradient(135deg, #111 40%, #e0e0e0 100%)";
  }

  entries.forEach(function(entry) {
    entry.style.transition = "background 0.7s, box-shadow 0.3s";
    entry.style.background = "linear-gradient(120deg, #222 0%, #e0e0e0 100%)";
    entry.style.boxShadow = "0 2px 18px 0 rgba(0,0,0,0.22)";
    entry.style.color = "#111";
  });

  signInBtns.forEach(function(btn){
    btn.style.background = "linear-gradient(135deg, #111 60%, #e0e0e0 100%)";
    btn.style.color = "#111";
    btn.style.fontWeight = "bold";
    btn.style.boxShadow = "0 2px 14px #0002";
    btn.onmouseover = function() { btn.style.filter = "brightness(1.13)"; };
    btn.onmouseleave = function() { btn.style.filter = ""; };
  });
}

// Attach click handler to the circular dot menu buttons
document.addEventListener('DOMContentLoaded', function() {
  var btn = document.getElementById('changeTitleBgBtn');
  if (btn) {
    btn.addEventListener('click', function(e){
      setThemeGradient();
      e.stopPropagation();
    });
  }
  var bwBtn = document.getElementById('bwThemeBtn');
  if (bwBtn) {
    bwBtn.addEventListener('click', function(e){
      setBWTheme();
      e.stopPropagation();
    });
  }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const dot = document.getElementById('dot');
  const menu = document.getElementById('dotMenu');
  let expanded = false;

  dot.addEventListener('click', function(e) {
    expanded = !expanded;
    if (expanded) {
      dot.classList.add('expanded');
      menu.style.display = 'block';
      setTimeout(()=>menu.style.opacity="1", 10); // fade in
    } else {
      dot.classList.remove('expanded');
      menu.style.opacity="0";
      setTimeout(()=>menu.style.display="none", 300);
    }
    e.stopPropagation();
  });

  // Clicking outside dot/menu closes it
  document.addEventListener('mousedown', function(e) {
    if (expanded && !dot.contains(e.target) && !menu.contains(e.target)) {
      dot.classList.remove('expanded');
      menu.style.opacity="0";
      setTimeout(()=>menu.style.display="none", 300);
      expanded = false;
    }
  });

  // ESC closes menu
  document.addEventListener('keydown', function(e) {
    if (expanded && e.key === 'Escape') {
      dot.classList.remove('expanded');
      menu.style.opacity="0";
      setTimeout(()=>menu.style.display="none", 300);
      expanded = false;
    }
  });
});
</script>


<script>
// ...existing scripts...

// --- Title-container popout menu functionality ---
document.addEventListener('DOMContentLoaded', function() {
  var titleContainer = document.getElementById('mainTitleContainer');
  var menu = document.getElementById('titleMenuPopout');
  var closeBtn = document.getElementById('closeTitleMenu');

  function closeMenu() {
    menu.style.display = 'none';
  }

  if (titleContainer && menu) {
    titleContainer.style.cursor = "pointer";
    titleContainer.addEventListener('click', function(e) {
      // Position menu relative to the titleContainer (left, below)
      var rect = titleContainer.getBoundingClientRect();
      menu.style.left = (rect.left + window.scrollX + rect.width + 18) + "px";
      menu.style.top = (rect.top + window.scrollY) + "px";
      menu.style.display = 'block';
    });
  }

  // Close button in menu
  if (closeBtn) {
    closeBtn.onclick = function(e) {
      closeMenu();
    };
  }

  // Clicking anywhere outside the menu closes it
  document.addEventListener('mousedown', function(e) {
    if (menu.style.display === 'block' && !menu.contains(e.target) && !titleContainer.contains(e.target)) {
      closeMenu();
    }
  });

  // Escape key closes menu
  document.addEventListener('keydown', function(e) {
    if (e.key === "Escape") closeMenu();
  });
});
</script>

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

    // Add this JS below your existing slideshow JS
imgElem.onclick = function () {
  showModal(current);
};

function getImageInfo(path) {
  // Example: "p-users/username/work/image.jpg"
  var info = {};
  var parts = path.split('/');
  if (parts.length >= 4) {
    info.userFolder = parts[1]; // username or user folder
    info.filename = parts[3];
    info.relativePath = path;
  } else {
    info.filename = path.split('/').pop();
    info.relativePath = path;
  }
  return info;
}

function showModal(idx) {
  var modal = document.getElementById('slideModal');
  var modalImg = document.getElementById('modalImg');
  var modalInfo = document.getElementById('modalInfo');
  var imgPath = images[idx];
  modalImg.src = imgPath;
  var info = getImageInfo(imgPath);
  // You can expand this info if you have more data
  modalInfo.innerHTML = `
    <div style="font-weight:bold; font-size:1.1em;">${info.filename}</div>
    <div style="color:#777; margin-top:2px;">User Folder: ${info.userFolder ? info.userFolder : 'Unknown'}</div>
    <div style="font-size:0.95em; color:#aaa;">Path: ${info.relativePath}</div>
  `;
  modal.style.display = 'flex';
}

// Close modal on click of close button or background
document.getElementById('closeSlideModal').onclick = function() {
  document.getElementById('slideModal').style.display = 'none';
};
document.getElementById('slideModal').onclick = function(e) {
  if (e.target === this) this.style.display = 'none';
};

  </script>


<script>
    // ... (keep your images, current, timer, etc. definitions)

    // These will overlay the title/date
    var titleElem = document.getElementById('slideshow-title');
    var dateElem = document.getElementById('slideshow-date');

    function getWorkInfoFromImagePath(path) {
      // Try to match image to an artist and work entry in ARTISTS array
      // ARTISTS and their workNlink fields are available via PHP/JS bridge
      var match = {
        title: '',
        date: ''
      };
      if (!path || !window.ARTISTS) return match;
      
      // First try to match with artists' works in the database
      for (var i=0; i<ARTISTS.length; ++i) {
        var a = ARTISTS[i];
        for (var n=1; n<=6; ++n) {
          var link = a['work'+n+'link'];
          if (link && path.indexOf(link.replace(/^\//,'')) !== -1) {
            match.title = a['work'+n] || '';
            match.date = a['date'] || '';
            return match;
          }
        }
      }
      
      // If no match in database, extract a cleaner title from filename
      var filename = path.split('/').pop();
      // Remove file extension
      filename = filename.replace(/\.[^/.]+$/, "");
      // Replace underscores with spaces
      filename = filename.replace(/_/g, " ");
      // Capitalize first letter of each word
      filename = filename.replace(/\b\w/g, function(l){ return l.toUpperCase() });
      
      match.title = filename;
      return match;
    }

    function showImage(idx) {
      if (!images.length) {
        imgElem.src = '';
        imgElem.alt = 'No photos found';
        if(titleElem) titleElem.textContent = '';
        if(dateElem) dateElem.textContent = '';
        return;
      }
      current = (idx + images.length) % images.length;
      var imgPath = images[current];
      imgElem.src = imgPath;
      imgElem.alt = 'Photo ' + (current + 1);
      
      // Overlay title/date only - no path information
      var info = getWorkInfoFromImagePath(imgPath);
      if(titleElem) titleElem.textContent = info.title || '';
      if(dateElem) dateElem.textContent = info.date || '';
    }

    // ... rest of slideshow code (leave as is) ...
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

     

    function getProfileFolderName(artist) {
  // Should match PHP's: strtolower(firstname_lastname), non-alphanumeric replaced by _
  let folder = (artist.firstname + '_' + artist.lastname).toLowerCase();
  return folder.replace(/[^a-z0-9_\-]/g, '_');
}

  

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

            <p style="padding:5px; cursor:pointer; color:blue; text-decoration:underline;" onclick="window.location.href='profile.php?artist=${artist.firstname}_${artist.lastname}'">
            visit profile
            </p>

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
document.addEventListener('DOMContentLoaded', function() {
  var closeBtn = document.getElementById('closeSlideModal');
  var modal = document.getElementById('slideModal');
  var visitProfileBtn = document.getElementById('visitProfileBtn');
  var modalUserProfile = "";

  if (closeBtn && modal) {
    closeBtn.onclick = function(e) {
      modal.style.display = 'none';
    };
    // Also allow clicking the dark background to close
    modal.onclick = function(e) {
      if (e.target === modal) modal.style.display = 'none';
    };
  }

  if (visitProfileBtn) {
    visitProfileBtn.onclick = function visiting() {
      if (modalUserProfile) {
        window.location.href = 'profile.php?artist=' + encodeURIComponent(modalUserProfile);
      }
    };
  }

  // Updated showModal function with better information display
  window.showModal = function(idx) {
    var modal = document.getElementById('slideModal');
    var modalImg = document.getElementById('modalImg');
    var modalTitle = document.getElementById('modalTitle');
    var modalDate = document.getElementById('modalDate');
    var modalArtist = document.getElementById('modalArtist');
    var imgPath = images[idx];
    
    modalImg.src = imgPath;
    
    // Get enhanced information about the work and artist
    var info = getEnhancedWorkInfo(imgPath);
    
    // Set the information in the modal
    modalTitle.textContent = info.title || 'Untitled Work';
    modalDate.textContent = info.date ? 'Created: ' + info.date : '';
    modalArtist.textContent = info.artistName ? 'By: ' + info.artistName : '';
    
    // Store the user profile for the visit button
    modalUserProfile = info.userFolder || '';
    
    // Display the modal
    modal.style.display = 'flex';
  };

  // Enhanced function to get better work information
  window.getEnhancedWorkInfo = function(path) {
    var info = {
      title: '',
      date: '',
      artistName: '',
      userFolder: ''
    };
    
    // Extract user folder from path (e.g., "p-users/username/work/image.jpg")
    var parts = path.split('/');
    if (parts.length >= 3 && parts[0] === 'p-users') {
      info.userFolder = parts[1];
      
      // Try to convert user folder to artist name (firstname_lastname → Firstname Lastname)
      var nameParts = info.userFolder.split('_');
      if (nameParts.length >= 2) {
        var formattedName = nameParts.map(function(part) {
          return part.charAt(0).toUpperCase() + part.slice(1).toLowerCase();
        }).join(' ');
        info.artistName = formattedName;
      }
    }
    
    // Look for matching work in ARTISTS array for better info
    if (window.ARTISTS) {
      var foundMatch = false;
      
      // First try exact match by path
      for (var i = 0; i < ARTISTS.length; i++) {
        var artist = ARTISTS[i];
        for (var j = 1; j <= 6; j++) {
          var workLink = artist['work' + j + 'link'];
          if (workLink && path.indexOf(workLink.replace(/^\//, '')) !== -1) {
            info.title = artist['work' + j] || '';
            info.date = artist.date || '';
            info.artistName = (artist.firstname + ' ' + artist.lastname).trim();
            foundMatch = true;
            break;
          }
        }
        if (foundMatch) break;
      }
      
      // If no exact match but we have a userFolder, try to match by name
      if (!foundMatch && info.userFolder) {
        for (var i = 0; i < ARTISTS.length; i++) {
          var artist = ARTISTS[i];
          var artistFolder = (artist.firstname + '_' + artist.lastname).toLowerCase();
          artistFolder = artistFolder.replace(/[^a-z0-9_\-]/g, '_');
          
          if (artistFolder === info.userFolder.toLowerCase()) {
            info.date = artist.date || '';
            info.artistName = (artist.firstname + ' ' + artist.lastname).trim();
            break;
          }
        }
      }
    }
    
    // If we still don't have a title, extract one from the filename
    if (!info.title && parts.length > 0) {
      var filename = parts[parts.length - 1];
      // Remove extension and format nicely
      filename = filename.replace(/\.[^/.]+$/, "");
      filename = filename.replace(/_/g, " ");
      // Capitalize first letter of each word
      filename = filename.replace(/\b\w/g, function(l) { 
        return l.toUpperCase(); 
      });
      info.title = filename;
    }
    
    return info;
  };

  // Attach to slideshow image (if not already)
  var imgElem = document.getElementById('slideshow-img');
  if (imgElem) {
    imgElem.onclick = function() {
      var current = window.current || 0;
      window.showModal(current);
    };
  }
});
</script>

</body>

</html>
