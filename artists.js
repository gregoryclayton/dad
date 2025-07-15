const ARTISTS = [
      {
        name: "Leonardo da Vinci",
        years: "1452–1519",
        bio: "Italian polymath of the Renaissance, known for his works in painting, science, and engineering.",
        pp: "https://upload.wikimedia.org/wikipedia/commons/6/6a/Mona_Lisa.jpg",
        works: [
          { title: "Mona Lisa", img: "https://upload.wikimedia.org/wikipedia/commons/6/6a/Mona_Lisa.jpg" },
          { title: "Mona Lisa", img: "https://upload.wikimedia.org/wikipedia/commons/6/6a/Mona_Lisa.jpg" },
          { title: "The Last Supper", img: "https://upload.wikimedia.org/wikipedia/commons/4/4b/Leonardo_da_Vinci_-_Ultima_cena_-_ca._1495-1498.jpg" }
        ]
      },
      {
        name: "Vincent van Gogh",
        years: "1853–1890",
        bio: "Dutch Post-Impressionist painter, famed for bold color and emotive brushwork.",
        works: [
          { title: "Starry Night", img: "https://upload.wikimedia.org/wikipedia/commons/e/ee/The_Starry_Night.jpg" },
          { title: "Sunflowers", img: "https://upload.wikimedia.org/wikipedia/commons/4/47/Vincent_Willem_van_Gogh_127.jpg" }
        ]
      },
      {
        name: "Pablo Picasso",
        years: "1881–1973",
        bio: "Spanish painter, sculptor, and one of the founders of Cubism.",
        works: [
          { title: "Guernica", img: "https://upload.wikimedia.org/wikipedia/en/7/74/PicassoGuernica.jpg" },
          { title: "Guernica", img: "https://upload.wikimedia.org/wikipedia/en/7/74/PicassoGuernica.jpg" },
          { title: "Guernica", img: "https://upload.wikimedia.org/wikipedia/en/7/74/PicassoGuernica.jpg" },
          { title: "Les Demoiselles d'Avignon", img: "https://upload.wikimedia.org/wikipedia/en/d/d1/Les_Demoiselles_d%27Avignon.jpg" }
        ]
      },
      {
        name: "Claude Monet",
        years: "1840–1926",
        bio: "French painter, a founder of Impressionism, known for his landscape series.",
        works: [
          { title: "Water Lilies", img: "https://upload.wikimedia.org/wikipedia/commons/0/08/Claude_Monet_-_Nymph%C3%A9as_-_Google_Art_Project.jpg" },
          { title: "Impression, Sunrise", img: "https://upload.wikimedia.org/wikipedia/commons/0/05/Claude_Monet%2C_Impression%2C_soleil_levant.jpg" }
        ]
      },
      {
        name: "Rembrandt van Rijn",
        years: "1606–1669",
        bio: "Dutch Golden Age painter and etcher, master of light and shadow.",
        works: [
          { title: "The Night Watch", img: "https://upload.wikimedia.org/wikipedia/commons/2/2a/Rembrandt_van_Rijn-De_Nachtwacht-1642.jpg" },
          { title: "Self-Portrait", img: "https://upload.wikimedia.org/wikipedia/commons/a/a8/Rembrandt_-_Self-Portrait_-_Google_Art_Project.jpg" }
        ]
      },
      {
        name: "Frida Kahlo",
        years: "1907–1954",
        bio: "Mexican painter known for vivid self-portraits and works inspired by nature and Mexican artifacts.",
        works: [
          { title: "Self-Portrait with Thorn Necklace and Hummingbird", img: "https://upload.wikimedia.org/wikipedia/commons/6/68/Frida_Kahlo_%281907-1954%29_001.jpg" },
          { title: "The Two Fridas", img: "https://upload.wikimedia.org/wikipedia/commons/1/1e/Las_dos_Fridas_%281939%29.png" }
        ]
      },
      {
        name: "Salvador Dalí",
        years: "1904–1989",
        bio: "Spanish Surrealist known for bizarre, dreamlike imagery.",
        works: [
          { title: "The Persistence of Memory", img: "https://upload.wikimedia.org/wikipedia/en/d/dd/The_Persistence_of_Memory.jpg" },
          { title: "Swans Reflecting Elephants", img: "https://upload.wikimedia.org/wikipedia/en/f/ff/Salvador_Dal%C3%AD_-_Swans_Reflecting_Elephants.jpg" }
        ]
      },
      {
        name: "Georgia O'Keeffe",
        years: "1887–1986",
        bio: "American modernist painter, best known for flowers and southwestern landscapes.",
        works: [
          { title: "Jimson Weed", img: "https://upload.wikimedia.org/wikipedia/commons/3/33/O%27Keeffe_-_Jimson_Weed_Black_Vessel.jpg" },
          { title: "Black Iris", img: "https://upload.wikimedia.org/wikipedia/commons/8/84/Georgia_OKeeffe_-_Black_Iris_1906.jpg" }
        ]
      },
      {
        name: "Michelangelo",
        years: "1475–1564",
        bio: "Italian Renaissance sculptor, painter, architect, and poet.",
        works: [
          { title: "David (sculpture)", img: "https://upload.wikimedia.org/wikipedia/commons/e/e7/%27David%27_by_Michelangelo_Fir_JBU005_denoised.jpg" },
          { title: "The Creation of Adam", img: "https://upload.wikimedia.org/wikipedia/commons/2/2d/Michelangelo_-_Creation_of_Adam_%28cropped%29.jpg" }
        ]
      },
      {
        name: "Edvard Munch",
        years: "1863–1944",
        bio: "Norwegian Symbolist painter, best known for 'The Scream'.",
        works: [
          { title: "The Scream", img: "https://upload.wikimedia.org/wikipedia/commons/f/f4/The_Scream.jpg" },
          { title: "The Dance of Life", img: "https://upload.wikimedia.org/wikipedia/commons/3/3b/Edvard_Munch_-_The_Dance_of_Life_-_Google_Art_Project.jpg" }
        ]
      },
      {
        name: "Leonardo da Vinci",
        years: "1452–1519",
        bio: "Italian polymath of the Renaissance, known for his works in painting, science, and engineering.",
        works: [
          { title: "Mona Lisa", img: "https://upload.wikimedia.org/wikipedia/commons/6/6a/Mona_Lisa.jpg" },
          { title: "The Last Supper", img: "https://upload.wikimedia.org/wikipedia/commons/4/4b/Leonardo_da_Vinci_-_Ultima_cena_-_ca._1495-1498.jpg" }
        ]
      },
      {
        name: "Vincent van Gogh",
        years: "1853–1890",
        bio: "Dutch Post-Impressionist painter, famed for bold color and emotive brushwork.",
        works: [
          { title: "Starry Night", img: "https://upload.wikimedia.org/wikipedia/commons/e/ee/The_Starry_Night.jpg" },
          { title: "Sunflowers", img: "https://upload.wikimedia.org/wikipedia/commons/4/47/Vincent_Willem_van_Gogh_127.jpg" }
        ]
      },
      {
        name: "Pablo Picasso",
        years: "1881–1973",
        bio: "Spanish painter, sculptor, and one of the founders of Cubism.",
        works: [
          { title: "Guernica", img: "https://upload.wikimedia.org/wikipedia/en/7/74/PicassoGuernica.jpg" },
          { title: "Les Demoiselles d'Avignon", img: "https://upload.wikimedia.org/wikipedia/en/d/d1/Les_Demoiselles_d%27Avignon.jpg" }
        ]
      },
      {
        name: "Claude Monet",
        years: "1840–1926",
        bio: "French painter, a founder of Impressionism, known for his landscape series.",
        works: [
          { title: "Water Lilies", img: "https://upload.wikimedia.org/wikipedia/commons/0/08/Claude_Monet_-_Nymph%C3%A9as_-_Google_Art_Project.jpg" },
          { title: "Impression, Sunrise", img: "https://upload.wikimedia.org/wikipedia/commons/0/05/Claude_Monet%2C_Impression%2C_soleil_levant.jpg" }
        ]
      },
      {
        name: "Rembrandt van Rijn",
        years: "1606–1669",
        bio: "Dutch Golden Age painter and etcher, master of light and shadow.",
        works: [
          { title: "The Night Watch", img: "https://upload.wikimedia.org/wikipedia/commons/2/2a/Rembrandt_van_Rijn-De_Nachtwacht-1642.jpg" },
          { title: "Self-Portrait", img: "https://upload.wikimedia.org/wikipedia/commons/a/a8/Rembrandt_-_Self-Portrait_-_Google_Art_Project.jpg" }
        ]
      },
      {
        name: "Frida Kahlo",
        years: "1907–1954",
        bio: "Mexican painter known for vivid self-portraits and works inspired by nature and Mexican artifacts.",
        works: [
          { title: "Self-Portrait with Thorn Necklace and Hummingbird", img: "https://upload.wikimedia.org/wikipedia/commons/6/68/Frida_Kahlo_%281907-1954%29_001.jpg" },
          { title: "The Two Fridas", img: "https://upload.wikimedia.org/wikipedia/commons/1/1e/Las_dos_Fridas_%281939%29.png" }
        ]
      },
      {
        name: "Salvador Dalí",
        years: "1904–1989",
        bio: "Spanish Surrealist known for bizarre, dreamlike imagery.",
        works: [
          { title: "The Persistence of Memory", img: "https://upload.wikimedia.org/wikipedia/en/d/dd/The_Persistence_of_Memory.jpg" },
          { title: "Swans Reflecting Elephants", img: "https://upload.wikimedia.org/wikipedia/en/f/ff/Salvador_Dal%C3%AD_-_Swans_Reflecting_Elephants.jpg" }
        ]
      },
      {
        name: "Georgia O'Keeffe",
        years: "1887–1986",
        bio: "American modernist painter, best known for flowers and southwestern landscapes.",
        works: [
          { title: "Jimson Weed", img: "https://upload.wikimedia.org/wikipedia/commons/3/33/O%27Keeffe_-_Jimson_Weed_Black_Vessel.jpg" },
          { title: "Black Iris", img: "https://upload.wikimedia.org/wikipedia/commons/8/84/Georgia_OKeeffe_-_Black_Iris_1906.jpg" }
        ]
      },
      {
        name: "Michelangelo",
        years: "1475–1564",
        bio: "Italian Renaissance sculptor, painter, architect, and poet.",
        works: [
          { title: "David (sculpture)", img: "https://upload.wikimedia.org/wikipedia/commons/e/e7/%27David%27_by_Michelangelo_Fir_JBU005_denoised.jpg" },
          { title: "The Creation of Adam", img: "https://upload.wikimedia.org/wikipedia/commons/2/2d/Michelangelo_-_Creation_of_Adam_%28cropped%29.jpg" }
        ]
      },
      {
        name: "Edvard Munch",
        years: "1863–1944",
        bio: "Norwegian Symbolist painter, best known for 'The Scream'.",
        works: [
          { title: "The Scream", img: "https://upload.wikimedia.org/wikipedia/commons/f/f4/The_Scream.jpg" },
          { title: "The Dance of Life", img: "https://upload.wikimedia.org/wikipedia/commons/3/3b/Edvard_Munch_-_The_Dance_of_Life_-_Google_Art_Project.jpg" }
        ]
      }
    ];
