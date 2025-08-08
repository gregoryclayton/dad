<?php
session_start();
$signin_error = "";
$signin_success = false;

// Replace with your actual database credentials (only needed for sign-in functionality)
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'mysql';

// Now fetch and display all users (needed for the container listing)
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

// Get artist folder name from URL parameter and sanitize
if (!isset($_GET['artist'])) {
    die('Artist not specified.');
}
$artistFolder = preg_replace('/[^a-z0-9_\-]/', '_', strtolower($_GET['artist']));
$userJson = __DIR__ . "/p-users/$artistFolder/profile.json";

if (!file_exists($userJson)) {
    die('Profile not found.');
}

// Load the artist profile from the JSON file
$data = json_decode(file_get_contents($userJson), true);
if (!$data) {
    die('Profile data invalid.');
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

// Get artist's works from the work directory
$works = [];
$workDir = __DIR__ . "/p-users/$artistFolder/work";
if (is_dir($workDir)) {
    $files = scandir($workDir);
    foreach ($files as $file) {
        if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $file)) {
            $works[] = "p-users/$artistFolder/work/$file";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($data['firstname'] . ' ' . $data['lastname']); ?> — Digital Artist Database</title>
  <link rel="stylesheet" type="text/css" href="style.css">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    .profile-container {
      max-width: 900px;
      margin: 30px auto;
      padding: 30px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 3px 15px rgba(0,0,0,0.08);
    }
    .profile-header {
      display: flex;
      align-items: center;
      margin-bottom: 30px;
      color:black;
    }
    .profile-pp {
      width: 140px;
      height: 140px;
      border-radius: 50%;
      object-fit: cover;
      margin-right: 30px;
      box-shadow: 0 3px 12px rgba(0,0,0,0.1);
    }
    .profile-data {
      line-height: 1.6;
      margin-bottom: 30px;
      color:black;
    }
    .profile-works {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 20px;
      margin-top: 30px;
    }
    .profile-work {
      position: relative;
      overflow: hidden;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .profile-work img {
      width: 100%;
      height: 220px;
      object-fit: cover;
      transition: transform 0.3s;
    }
    .profile-work:hover img {
      transform: scale(1.05);
    }
    .fact-list {
      list-style-type: disc;
      margin-left: 20px;
      margin-bottom: 20px;
    }
    .artist-links {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-top: 20px;
    }
    .artist-links a {
      padding: 8px 15px;
      background: #f5f5f5;
      border-radius: 6px;
      color: #444;
      text-decoration: none;
      transition: background 0.2s;
    }
    .artist-links a:hover {
      background: #e0e0e0;
    }

    .profile-works-horizontal {
  display: flex;
  flex-direction: row;
  overflow:scroll;
  scrollbar-width:none;
  gap: 20px;
  /* Prevent wrapping */
  flex-wrap: nowrap;
  min-height: 260px;
}
.profile-work {
  min-width: 250px;
  max-width: 300px;
  flex: 0 0 auto;
  position: relative;
  overflow: hidden;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  background: #fff;
  margin-bottom: 0;
  /* If you want a fixed height for cards, set it here */
}
.profile-work img {
  width: 100%;
  height: 220px;
  object-fit: cover;
  transition: transform 0.3s;
}
.profile-work:hover img {
  transform: scale(1.05);
}
  </style>
</head>
<body>


<br>
<br>



<div class="profile-container">
  <div class="profile-header">
    <?php
    // Try to find a profile picture in the pp/ folder
    $ppDir = __DIR__ . "/p-users/$artistFolder/pp";
    $ppImg = '';
    if (is_dir($ppDir)) {
        $files = scandir($ppDir);
        foreach ($files as $file) {
            if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $file)) {
                $ppImg = "p-users/$artistFolder/pp/$file";
                break;
            }
        }
    }
    if ($ppImg): ?>
      <img src="<?php echo htmlspecialchars($ppImg); ?>" class="profile-pp" alt="Profile Photo" />
    <?php endif; ?>
    
    <div>
      <h1 style="margin-bottom:0.2em;"><?php echo htmlspecialchars($data['firstname'] . ' ' . $data['lastname']); ?></h1>
       <?php if (!empty($data['nickname'])): ?>
          <?php echo htmlspecialchars($data['nickname']); ?>
          <?php endif; ?>
          
      <div style="font-size:1.1em;color:#888;">
        <br>
        <?php if (!empty($data['country'])): ?>
          <?php echo htmlspecialchars($data['country']); ?>
        <?php endif; ?>
        <?php if (!empty($data['date'])): ?>
          • <?php echo htmlspecialchars($data['date']); ?>
        <?php endif; ?>
        <?php if (!empty($data['genre'])): ?>
          • <?php echo htmlspecialchars($data['genre']); ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <div class="profile-data">
    <?php if (!empty($data['email'])): ?>
      <p><strong></strong> <li style="margin-left:10%;"><?php echo htmlspecialchars($data['email']); ?></li></p>
    <?php endif; ?>
    
    
 <?php if (!empty($data['fact1']) || !empty($data['fact2']) || !empty($data['fact3'])): ?>
      <br>
      <ul class="fact-list">
        <?php if (!empty($data['fact1'])): ?>
          <li><?php echo htmlspecialchars($data['fact1']); ?></li>
        <?php endif; ?>
        <?php if (!empty($data['fact2'])): ?>
          <li><?php echo htmlspecialchars($data['fact2']); ?></li>
        <?php endif; ?>
        <?php if (!empty($data['fact3'])): ?>
          <li><?php echo htmlspecialchars($data['fact3']); ?></li>
        <?php endif; ?>
      </ul>
    <?php endif; ?>

    <?php if (!empty($data['bio'])): ?>
    <br>
      <p><?php echo nl2br(htmlspecialchars($data['bio'])); ?></p>
    <?php endif; ?>
    <?php if (!empty($data['bio2'])): ?>
      <p><?php echo nl2br(htmlspecialchars($data['bio2'])); ?></p>
    <?php endif; ?>
    
   
    
    
    
   
  </div>
  
  <?php 
  
 if (count($works) > 0 || 
    !empty($data['work1']) || !empty($data['work2']) || !empty($data['work3']) || 
    !empty($data['work4']) || !empty($data['work5']) || !empty($data['work6'])): ?>
    <h3>Works</h3>
    
    <!-- Horizontally scrollable works container -->
  <div class="profile-works-scroll">
    <div class="profile-works-horizontal">
      <?php 
      // Display works from the works folder
      $workIndex = 0;
      foreach ($works as $work): ?>
        <div class="profile-work" data-index="<?php echo $workIndex; ?>" data-type="folder">
          <img src="<?php echo htmlspecialchars($work); ?>" alt="Artwork" />
        </div>
      <?php 
        $workIndex++;
      endforeach; 
      
      // Also display works from JSON data if available
      for ($i = 1; $i <= 6; $i++): 
        $workTitle = isset($data["work$i"]) ? $data["work$i"] : '';
        $workLink = isset($data["work{$i}link"]) ? $data["work{$i}link"] : '';
        if ($workTitle && $workLink): ?>
          <div class="profile-work" data-index="<?php echo $workIndex; ?>" data-type="json" data-title="<?php echo htmlspecialchars($workTitle); ?>">
            <img src="<?php echo htmlspecialchars($workLink); ?>" alt="<?php echo htmlspecialchars($workTitle); ?>" />
            <div style="padding: 10px; font-weight: bold;"><?php echo htmlspecialchars($workTitle); ?></div>
          </div>
        <?php 
          $workIndex++;
        endif;
      endfor; ?>
    </div>

     <?php if (!empty($data['link1']) || !empty($data['link2']) || !empty($data['link3'])): ?>
      <br>
      <div class="artist-links">
        <?php if (!empty($data['link1'])): ?>
          <a href="<?php echo htmlspecialchars($data['link1']); ?>" target="_blank" rel="noopener">
            <?php echo htmlspecialchars(parse_url($data['link1'], PHP_URL_HOST) ?: $data['link1']); ?>
          </a>
        <?php endif; ?>
        <?php if (!empty($data['link2'])): ?>
          <a href="<?php echo htmlspecialchars($data['link2']); ?>" target="_blank" rel="noopener">
            <?php echo htmlspecialchars(parse_url($data['link2'], PHP_URL_HOST) ?: $data['link2']); ?>
          </a>
        <?php endif; ?>
        <?php if (!empty($data['link3'])): ?>
          <a href="<?php echo htmlspecialchars($data['link3']); ?>" target="_blank" rel="noopener">
            <?php echo htmlspecialchars(parse_url($data['link3'], PHP_URL_HOST) ?: $data['link3']); ?>
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
    
    <!-- Add modal container for expanded works -->
    <div id="workModal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.85); overflow:auto;">
      <div style="position:relative; margin:5% auto; padding:20px; width:85%; max-width:900px; animation:modalFadeIn 0.3s;">
        <span id="closeModal" style="position:absolute; top:10px; right:20px; color:white; font-size:28px; font-weight:bold; cursor:pointer;">&times;</span>
        <input type="radio" name="WorkSelect" id="WorkRadio" style="width:22px; height:22px; accent-color:#e27979;">
        <div id="modalContent" style="background:#333; padding:25px; border-radius:15px; color:white;"></div>
        
      </div>
    </div>
    
    <!-- Store works data as JSON for JavaScript access -->
    <script>
      // Create arrays to store works data
      const folderWorks = <?php echo json_encode($works); ?>;
      const jsonWorks = [
        <?php for ($i = 1; $i <= 6; $i++): 
          $workTitle = isset($data["work$i"]) ? $data["work$i"] : '';
          $workLink = isset($data["work{$i}link"]) ? $data["work{$i}link"] : '';
          if ($workTitle && $workLink): ?>
            {
              title: <?php echo json_encode($workTitle); ?>,
              img: <?php echo json_encode($workLink); ?>
            },
          <?php endif;
        endfor; ?>
      ];
    </script>
<?php endif; ?>
  
  <div style="margin-top:2em; text-align:center;">
    <a href="v4.5.php" style="color:#222;text-decoration:underline;">Back to home</a>
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
  <div class="nav-button"><a href="signup.php">[sign up]</a></div>
  <div class="nav-button"><a href="contribute.php">[contribute]</a></div>
  <div class="nav-button"><a href="database.php">[database]</a></div>
  <div class="nav-button"><a href="studio.php">[studio]</a></div>
</div>

<!-- KEEPING THE ORIGINAL CONTAINER-CONTAINER-CONTAINER DIV -->
<div class="container-container-container" style="display:grid; align-items:center; justify-items: center;"> 
  <div class="container-container" style="border: double; border-radius:20px; padding-top:50px; width:90%; align-items:center; justify-items: center; display:grid;   background-color: #f2e9e9; box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.1);">

    <div style="display:flex; justify-content: center; align-items:center;">
      <div>
        <input type="text" id="artistSearchBar" placeholder="Search artists..." style="width:60vw; padding:0.6em 1em; font-size:1em; border-radius:7px; border:1px solid #ccc;">
      </div>
    </div>

    <!-- SORT BUTTONS AND SEARCH BAR ROW (MODIFIED) -->
    <div style="display:flex; justify-content:center; align-items:center; margin:1em 0 1em 0;">
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

<div style="max-width:70vw; margin:2em auto; font-family:Segoe UI,Arial,sans-serif;">
  <button id="payBtn" style="width:100%; box-shadow: 0 2px 10px #0004; transition:1s; background-color: rgb(235, 168, 168); color:black; border:none; padding:0.7em 0; border-radius:8px; font-size:1em; cursor:pointer;">
    donate to the digital artist database
  </button>
  <div id="paymentPortal" style="display:none; margin-top:1em; background-color: rgb(235, 168, 168); border-radius:10px; box-shadow:0 2px 8px #eee; padding:1.2em; text-align:center;">
    <stripe-buy-button
      buy-button-id="buy_btn_1RBP8wDKJyMVPD6MNwafC65z"
      publishable-key="pk_live_bcx2iXYjvNT7kmVPZ6k9P3Qy"
      style="opacity: 0.5;">
    </stripe-buy-button>
  </div>
</div>

<footer style="background:#222; color:#eee; padding:2em 0; text-align:center; font-size:0.95em; margin-top: 40px;">
  <div style="margin-bottom:1em;">
    <nav>
      <a href="/index.php" style="color:#eee; margin:0 15px; text-decoration:none;">Home</a>
      <a href="/signup.php" style="color:#eee; margin:0 15px; text-decoration:none;">Sign Up</a>
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

<!-- Add slideModal for image viewing -->
<div id="slideModal" style="display:none; position:fixed; top:0; left:0;right:0;bottom:0; z-index:9999; background:rgba(0,0,0,0.7); align-items:center; justify-content:center;">
  <div id="slideCard" style="background:white; border-radius:14px; padding:24px 28px; max-width:90vw; max-height:90vh; box-shadow:0 8px 32px #0005; display:flex; flex-direction:column; align-items:center; position:relative;">
    <button id="closeSlideModal" style="position:absolute; top:12px; right:18px; font-size:1.3em; background:none; border:none; color:#333; cursor:pointer;">×</button>
    <img id="modalImg" src="" alt="Image" style="max-width:80vw; max-height:60vh; border-radius:8px; margin-bottom:22px;">
    <div id="modalInfo" style="text-align:center;"></div>
    <button id="visitProfileBtn" style="margin-top:18px; background:#e8bebe; border:none; border-radius:7px; padding:0.7em 2em; font-family:monospace; font-size:1em; cursor:pointer;">visit profile</button>
  </div>
</div>

<!-- First, add this full-screen image container element before the closing body tag -->
<div id="fullscreenImage" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.95); z-index:10000; cursor:zoom-out;">
  <div style="position:absolute; top:15px; right:20px; color:white; font-size:30px; cursor:pointer;" id="closeFullscreen">&times;</div>
  <img id="fullscreenImg" src="" alt="Fullscreen Image" style="position:absolute; top:0; left:0; right:0; bottom:0; margin:auto; max-width:95vw; max-height:95vh; object-fit:contain; transition:all 0.3s ease;">
</div>

<script>
  // Set up JSON data for JS
  var ARTISTS = <?php echo json_encode($jsonArray, JSON_PRETTY_PRINT); ?>;
  let filteredArtists = ARTISTS.slice(); // Current filtered list (default: all)
  console.log(ARTISTS); // You can use ARTISTS in your JS code
</script>

<script>
  // Payment button functionality
  document.getElementById('payBtn').onclick = function() {
    var portal = document.getElementById('paymentPortal');
    portal.style.display = (portal.style.display === 'none' || portal.style.display === '') ? 'block' : 'none';
  };

  // Artists container functionality
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
      <div style="display:flex; align-items:center; justify-content:space-between;">
        <div style="display:flex; align-items:center; gap:12px;">
          <img class="artist-pp" src="${artist.pp}" alt="Artist" />
          <span class="artist-firstname">${artist.firstname || ""}</span>
          <span class="artist-lastname">${artist.lastname || ""}</span>
          <span class="artist-date">${artist.date || ""}</span>
          <span class="artist-country">${artist.country || ""}</span>
          <span class="artist-genre">${artist.genre || ""}</span>
        </div>
       
      </div>
      
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
        e.target.closest('.dropdown') ||
        e.target.type === 'radio'
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

  // Sort buttons
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

  // Search bar
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

  // Slideshow modal
  document.getElementById('closeSlideModal').onclick = function() {
    document.getElementById('slideModal').style.display = 'none';
  };
  document.getElementById('slideModal').onclick = function(e) {
    if (e.target === this) this.style.display = 'none';
  };
</script>

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
  document.querySelectorAll('.profile-work').forEach(card => {
    card.addEventListener('click', function() {
      const index = parseInt(this.getAttribute('data-index'));
      const type = this.getAttribute('data-type');
      
      let work, imgSrc, title;
      
      if (type === 'folder') {
        imgSrc = folderWorks[index];
        title = 'Artwork';
        
        // Try to extract a file name from the path
        const parts = imgSrc.split('/');
        if (parts.length > 0) {
          const fileName = parts[parts.length - 1];
          // Remove extension and replace underscores with spaces
          title = fileName.replace(/\.[^/.]+$/, "").replace(/_/g, " ");
          // Capitalize first letter
          title = title.charAt(0).toUpperCase() + title.slice(1);
        }
      } else if (type === 'json') {
        work = jsonWorks[index - folderWorks.length]; // Adjust index for json works
        imgSrc = work.img;
        title = work.title || this.getAttribute('data-title') || 'Artwork';
      }
      
      if (imgSrc) {
        let content = `
          <div style="display:flex; flex-direction:column; align-items:center;">
            <h2 style="margin-bottom:15px; font-size:24px; color:white;">${title}</h2>
            
            <div style="width:100%; max-width:1000px; margin-bottom:20px; text-align:center;">
              <img src="${imgSrc}" alt="${title}" 
                style="max-width:100%; max-height:70vh; object-fit:contain; border-radius:10px; box-shadow:0 4px 20px rgba(0,0,0,0.3);">
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
  if (closeBtn) {
    closeBtn.addEventListener('click', closeModal);
  }
  
  // Close modal when clicking outside content
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
    .profile-work {
      cursor: pointer;
      transition: transform 0.2s ease-in-out;
    }
    .profile-work:hover {
      transform: scale(1.03);
    }
  `;
  document.head.appendChild(style);
});
</script>

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

</body>
</html>
