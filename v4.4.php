<?php

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
        // Normalize the row to match expected ARTIST fields.
        // If you want to support "years", "bio", "works", extend your DB and form accordingly.
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
            "bio" => $row["bio"], // Use "message" as "bio" for now
            
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
// Folder containing images (relative to this PHP file)
$photoDir = __DIR__ . '/pics';

// Get all image files in the folder (jpg, jpeg, png, gif)
$images = [];
if (is_dir($photoDir)) {
    $files = scandir($photoDir);
    foreach ($files as $file) {
        if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $file)) {
            // Use relative path for browser
            $images[] = 'pics/' . $file;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Infinite Scroll: Famous Artists</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
   <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>

  <div class="title-container">
    digital <br>artist <br>database.
  </div>

  <div class="nav-container">
    <div class="nav-button" onclick="">sign up</div><div class="nav-button">contribute</div>

</div>

  <div id="slideshow-container">
    <img id="slideshow-img" src="" alt="Slideshow photo">
    <div class="slideshow-controls">
      <button id="prev-btn">&larr; Prev</button>
      <button id="next-btn">Next &rarr;</button>
    </div>
    <div class="slideshow-caption" id="slideshow-caption"></div>
  </div>
  <!--
  <div id="slideshow-container">
    <img id="slide-img" src="" alt="Slideshow image">
    <div>
      <button id="prev-btn">Previous</button>
      <button id="next-btn">Next</button>
    </div>
  </div>
-->
  <script>
    // Images array populated from PHP
    var images = <?php echo json_encode($images, JSON_PRETTY_PRINT); ?>;
    var current = 0;
    var timer = null;
    var imgElem = document.getElementById('slideshow-img');
    var captionElem = document.getElementById('slideshow-caption');
    var prevBtn = document.getElementById('prev-btn');
    var nextBtn = document.getElementById('next-btn');
    var interval = 4000; // 4 seconds

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
      captionElem.textContent = 'Photo ' + (current + 1) + ' of ' + images.length;
    }

    function nextImage() {
      showImage(current + 1);
    }
    function prevImage() {
      showImage(current - 1);
    }

    prevBtn.onclick = function() {
      prevImage();
      resetTimer();
    }
    nextBtn.onclick = function() {
      nextImage();
      resetTimer();
    }

    function startTimer() {
      if (timer) clearInterval(timer);
      timer = setInterval(nextImage, interval);
    }
    function resetTimer() {
      startTimer();
    }

    showImage(0);
    startTimer();
  </script>

  <div id="container"></div>


  <br><br><br><br><br>
  
  <div style="max-width:320px; margin:2em auto; font-family:Segoe UI,Arial,sans-serif;">
  <button id="payBtn" style="width:100%; background:#2962ff; color:#fff; border:none; padding:0.7em 0; border-radius:8px; font-size:1em; cursor:pointer;">
    Show Payment Portal
  </button>
  <div id="paymentPortal" style="display:none; margin-top:1em; background:#fff; border-radius:10px; box-shadow:0 2px 8px #eee; padding:1.2em; text-align:center;">
    <!-- Add payment portal details here -->
    <h3>Payment Portal</h3>
    <p>Payment details form will appear here.</p>
  </div>
</div>
<script>
  document.getElementById('payBtn').onclick = function() {
    var portal = document.getElementById('paymentPortal');
    portal.style.display = (portal.style.display === 'none' || portal.style.display === '') ? 'block' : 'none';
  };
</script>

  <footer style="background:#222; color:#eee; padding:2em 0; text-align:center; font-size:0.95em;">
  <div style="margin-bottom:1em;">
    <nav>
      <a href="/index.html" style="color:#eee; margin:0 15px; text-decoration:none;">Home</a>
      <a href="/about.html" style="color:#eee; margin:0 15px; text-decoration:none;">About</a>
      <a href="/gallery.html" style="color:#eee; margin:0 15px; text-decoration:none;">Gallery</a>
      <a href="/contact.html" style="color:#eee; margin:0 15px; text-decoration:none;">Contact</a>
      <a href="/sitemap.xml" style="color:#eee; margin:0 15px; text-decoration:none;">Sitemap</a>
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
    &copy; 2025 Your Website Name. All Rights Reserved.
  </div>
</footer>

  <script>
    // Set a JS variable to the PHP-generated JSON array
    var ARTISTS = <?php echo json_encode($jsonArray, JSON_PRETTY_PRINT); ?>;
    console.log(ARTISTS); // You can use ARTISTS in your JS code
  </script>

  <script>
    // Example image JSON data (replace with fetch if using external file)
    const imageData = [
     // {"url": "https://picsum.photos/id/1015/400/300"},
      //{"url": "https://picsum.photos/id/1016/400/300"},
     // {"url": "https://picsum.photos/id/1018/400/300"},
     // {"url": "https://picsum.photos/id/1020/400/300"}
    ];

    let current = 0;
    const imgElem = document.getElementById('slide-img');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    let slideshowInterval = null;

    function showSlide(index) {
      if (imageData.length === 0) {
        imgElem.src = '';
        imgElem.alt = 'No images';
        return;
      }
      current = (index + imageData.length) % imageData.length;
      imgElem.src = imageData[current].url;
      imgElem.alt = `Slide ${current + 1}`;
    }

    function nextSlide() {
      showSlide(current + 1);
    }

    prevBtn.onclick = () => {
      showSlide(current - 1);
      resetInterval();
    };
    nextBtn.onclick = () => {
      showSlide(current + 1);
      resetInterval();
    };

    function startInterval() {
      if (slideshowInterval) clearInterval(slideshowInterval);
      slideshowInterval = setInterval(nextSlide, 5000); // 5 seconds
    }

    function resetInterval() {
      startInterval();
    }

    // Initial display and start automatic slideshow
    showSlide(0);
    startInterval();
  </script>

  <script>
    // For infinite scroll, repeat the list with offset for demonstration purposes.
    function getArtist(index) {
      if (index < ARTISTS.length) return ARTISTS[index];
      let base = ARTISTS[index % ARTISTS.length];
      //return base;
    }

    // DOM Elements
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

        <div style="display: flex; align-items: center; background: #f5f7fa; padding: 1em 1.5em; border-radius: 12px; max-width: 400px;">
          <img src="${artist.pp}" alt="Profile Picture" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-right: 1.5em; box-shadow: 0 2px 8px rgba(0,0,0,0.07);">
          <ul style="list-style: disc inside; margin: 0; padding: 0;">
             <li>${artist.fact1 || ""}</li>
             <li>${artist.fact2 || ""}</li>
             <li>${artist.fact3 || ""}</li>
    
          </ul>
       </div>

      
          
          <div>${artist.bio || ""}</div>
          
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

          <div class="links-container">
          <a href="https://www.google.com" target="_blank" rel="noopener">Visit Google</a>
         <a class="artist-link1" href="${artist.link1}">${artist.link1 || ""}</a>
         <span class="artist-link2">${artist.link2 || ""}</span>
         <span class="artist-link3">${artist.link3 || ""}</span>
         </div>S
          
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

    function loadMore() {
      if (isLoading) return;
      isLoading = true;
      for (let i=loadedCount; i<loadedCount+BATCH_SIZE; i++) {
        container.appendChild(renderArtist(i));
      }
      loadedCount += BATCH_SIZE;
      isLoading = false;
    }

    // Infinite scroll handler
    container.addEventListener('scroll', function() {
      if (container.scrollTop + container.clientHeight >= container.scrollHeight - 80) {
        loadMore();
      }
    });

    // Initial fill
    loadMore();
    // Fill enough to cover viewport
    setTimeout(function fillToScreen() {
      if (container.scrollHeight < window.innerHeight+80) {
        loadMore();
        setTimeout(fillToScreen, 10);
      }
    }, 10);

    // --------------------- Add Artist Modal ---------------------
    const addArtistBtn = document.getElementById('addArtistBtn');
    const addArtistFormOverlay = document.getElementById('addArtistFormOverlay');
    const addArtistForm = document.getElementById('addArtistForm');
    const worksListDiv = document.getElementById('worksList');
    const addWorkBtn = document.getElementById('addWorkBtn');
    const cancelArtistBtn = document.getElementById('cancelArtistBtn');

    let workFields = [];

    // Show form
    addArtistBtn.onclick = function() {
      addArtistFormOverlay.style.display = 'flex';
      addArtistForm.reset();
      worksListDiv.innerHTML = '';
      workFields = [];
      addWorkField();
      document.getElementById('artistName').focus();
    };

    // Cancel form
    cancelArtistBtn.onclick = function() {
      addArtistFormOverlay.style.display = 'none';
    };
    addArtistFormOverlay.onclick = function(e) {
      if (e.target === addArtistFormOverlay) addArtistFormOverlay.style.display = 'none';
    };

    // Add work field
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

    // Submit form
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
      const newEntry = renderArtist(0);
      container.insertBefore(newEntry, container.firstChild);
      loadedCount++;
      addArtistFormOverlay.style.display = 'none';
      setTimeout(()=>{
        newEntry.classList.add('open');
        newEntry.scrollIntoView({behavior:'smooth', block:'center'});
      }, 80);
    };
  </script>
</body>
</html>
