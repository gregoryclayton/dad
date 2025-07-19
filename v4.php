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
$result = $conn->query("SELECT id, pp, firstname, lastname, date, genre, country, bio, works, workslink, works2, workslink2, works3, workslink3, works3, workslink4, works4, workslink4, works5, workslink5, works6, workslink6, works7, workslink7, works8, workslink8, link1, link2, link3 FROM users ORDER BY id DESC");

// Create json array from fetched data
$jsonArray = array();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Normalize the row to match expected ARTIST fields.
        // If you want to support "years", "bio", "works", extend your DB and form accordingly.
        
        $jsonArray[] = [
            
            "id" => $row["id"],
            "pp" => $row["pp"],
            "firstname" => $row["firstname"],
            "lastname" => $row["lastname"],
            "date" => $row["date"],
            "genre" => $row["genre"],
            "country" => $row["country"],
            "fact1" => $row["fact1"],
            "fact2" => $row["fact2"],
            "fact3" => $row["fact3"],
            "bio" => $row["bio"], // Use "message" as "bio" for now
            
            "works1" => $row["works1"],
            "workslink1" => $row["workslink1"],
            "works2" => $row["works2"],
            "workslink2" => $row["workslink2"],
            "works3" => $row["works3"],
            "workslink3" => $row["workslink3"],
            "works4" => $row["works4"],
            "workslink4" => $row["workslink4"],
            "works5" => $row["works5"],
            "workslink5" => $row["workslink5"],
            "works6" => $row["works6"],
            "workslink6" => $row["workslink6"],
            "works7" => $row["works7"],
            "workslink7" => $row["workslink7"],
            "works8" => $row["works8"],
            "workslink8" => $row["workslink8"],

            "link1" => $row["link1"],
            "link2" => $row["link2"],
            "link3" => $row["link3"]
           
        ];
    };
};

$jsonArray2 = array();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Normalize the row to match expected ARTIST fields.
        // If you want to support "years", "bio", "works", extend your DB and form accordingly.
        $jsonArray2[] = [
            
            "works" => $row["works"],
            "workslink" => $row["workslink"],
        ];
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
    digital artists database
  </div>

  <div id="slideshow-container">
    <img id="slide-img" src="" alt="Slideshow image">
    <div>
      <button id="prev-btn">Previous</button>
      <button id="next-btn">Next</button>
    </div>
  </div>

  <div id="container"></div>

  <button id="addArtistBtn" title="Add an artist">+</button>
  <div id="addArtistFormOverlay">
    <form id="addArtistForm" autocomplete="off">
      <h2 style="margin-top:0;margin-bottom:0.5em;">Add an Artist</h2>
      <label for="artistName">Name</label>
      <input type="text" id="artistName" required maxlength="60" placeholder="Artist's name" />
      <label for="artistYears">Years</label>
      <input type="text" id="artistYears" maxlength="40" placeholder="e.g. 1881–1973" />
      <label for="artistBio">Bio</label>
      <textarea id="artistBio" maxlength="500" required placeholder="Short biography"></textarea>
      <label>Works</label>
      <div id="worksList"></div>
      <button type="button" class="addWorkBtn" id="addWorkBtn">+ Add Work</button>
      <div class="form-btns">
        <button type="button" class="cancelBtn" id="cancelArtistBtn">Cancel</button>
        <button type="submit">Add</button>
      </div>
    </form>
  </div>

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

    var SLIDES = <?php echo json_encode($jsonArray2, JSON_PRETTY_PRINT); ?>;
    console.log(SLIDES); // You can use ARTISTS in your JS code
  </script>

  <script>
    // Example image JSON data (replace with fetch if using external file)
    const imageData = [
      {"url": "https://picsum.photos/id/1015/400/300"},
      {"url": "https://picsum.photos/id/1016/400/300"},
      {"url": "https://picsum.photos/id/1018/400/300"},
      {"url": "https://picsum.photos/id/1020/400/300"}
    ];

    let current = 0;
    const imgElem = document.getElementById('slide-img');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    let slideshowInterval = null;

    function showSlide(index) {
      if (SLIDES.length === 0) {
        imgElem.src = '';
        imgElem.alt = 'No images';
        return;
      }
      current = (index + SLIDES.length) % SLIDES.length;
      imgElem.src = SLIDES[current].workslink;
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
     // return base;
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
        <img class="artist-pp" src="${artist.pp}" alt="" />
        <span class="artist-firstname">${artist.firstname || ""}</span>
        <span class="artist-lastname">${artist.lastname || ""}</span>
        <span class="artist-date">${artist.date || ""}</span>
        <span class="artist-country">${artist.country || ""}</span>
        <span class="artist-date">${artist.genre || ""}</span>

        <div class="dropdown">
          <h4>About</h4>
          <div>${artist.bio || ""}</div>
          
       <h4>Works</h4>
          <div class="work-container">
          <div class="works-list">
            
              <div class="work-card">
                <span style="width:300px;">${artist.works}</span>
                ${artist.workslink ? `<img src="${artist.workslink}" loading="lazy" alt=""/>` : ''}
              </div>
            
             <div class="work-card">
                <span style="width:300px;">${artist.works}</span>
                ${artist.workslink ? `<img src="${artist.workslink}" loading="lazy" alt=""/>` : ''}
              </div>
            
             <div class="work-card">
                <span style="width:300px;">${artist.works}</span>
                ${artist.workslink ? `<img src="${artist.workslink}" loading="lazy" alt=""/>` : ''}
              </div>
            
            
          </div>
          </div>

        
          


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
