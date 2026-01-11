
    // Hamburger
    document.getElementById("hamburger").onclick = () => {
      document.getElementById("mobileMenu").classList.toggle("show");
    };

  
    document.getElementById("dateInfo").innerText = new Date().toDateString();

    // Background slideshow
    const slides = document.querySelectorAll(".bg-container div");
    let index = 0;
    setInterval(() => {
      slides[index].classList.remove("show");
      index = (index+1) % slides.length;
      slides[index].classList.add("show");
    }, 10000);
 