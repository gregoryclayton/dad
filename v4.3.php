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

  <!--
  <div id="slideshow-container">
    <img id="slide-img" src="" alt="Slideshow image">
    <div>
      <button id="prev-btn">Previous</button>
      <button id="next-btn">Next</button>
    </div>
  </div>
-->

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

        <div class="facts-container">
          <span class="artist-fact1">${artist.fact1 || ""}</span>
          <span class="artist-fact2">${artist.fact2 || ""}</span>
          <span class="artist-fact3">${artist.fact3 || ""}</span>
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
         <span class="artist-link1">${artist.link1 || ""}</span>
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
