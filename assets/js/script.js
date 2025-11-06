// ==========================================================
// KODE SLIDER PORTFOLIO (Hanya berjalan jika #sliderTrack ada)
// ==========================================================
const positions = [
    { height: 620, z: 220, rotateY: 48, y: 0, clip: "polygon(0px 0px, 100% 10%, 100% 90%, 0px 100%)" },
    { height: 580, z: 165, rotateY: 35, y: 0, clip: "polygon(0px 0px, 100% 8%, 100% 92%, 0px 100%)" },
    { height: 495, z: 110, rotateY: 15, y: 0, clip: "polygon(0px 0px, 100% 7%, 100% 93%, 0px 100%)" },
    { height: 420, z: 66, rotateY: 15, y: 0, clip: "polygon(0px 0px, 100% 7%, 100% 93%, 0px 100%)" },
    { height: 353, z: 46, rotateY: 6, y: 0, clip: "polygon(0px 0px, 100% 7%, 100% 93%, 0px 100%)" },
    { height: 310, z: 0, rotateY: 0, y: 0, clip: "polygon(0 0, 100% 0, 100% 100%, 0 100%)" },
    { height: 353, z: 54, rotateY: 348, y: 0, clip: "polygon(0px 7%, 100% 0px, 100% 100%, 0px 93%)" },
    { height: 420, z: 89, rotateY: -15, y: 0, clip: "polygon(0px 7%, 100% 0px, 100% 100%, 0px 93%)" },
    { height: 495, z: 135, rotateY: -15, y: 1, clip: "polygon(0px 7%, 100% 0px, 100% 100%, 0px 93%)" },
    { height: 580, z: 195, rotateY: 325, y: 0, clip: "polygon(0px 8%, 100% 0px, 100% 100%, 0px 92%)" },
    { height: 620, z: 240, rotateY: 312, y: 0, clip: "polygon(0px 10%, 100% 0px, 100% 100%, 0px 90%)" }
];

class CircularSlider {
    constructor(cards) { // Terima 'cards' sebagai argumen
        this.container = document.getElementById("sliderContainer");
        this.track = document.getElementById("sliderTrack");
        this.cards = cards; // Gunakan argumen 'cards'
        // Cek jika container ada sebelum melanjutkan
        if (!this.container || !this.track || !this.cards || this.cards.length === 0) {
            console.warn("Slider elements not found, skipping initialization.");
            return; // Hentikan jika elemen tidak ada
        }
        this.totalCards = this.cards.length;
        this.isDragging = false;
        this.startX = 0;
        this.dragDistance = 0;
        this.threshold = 60;
        this.processedSteps = 0;
        this.expandedCard = null;
        this.cardInfo = document.getElementById("cardInfo");
        this.cardTitle = document.getElementById("cardTitle");
        this.cardDesc = document.getElementById("cardDesc");
        this.closeBtn = document.getElementById("closeBtn");
        this.cardClone = null; // Tambahkan properti ini

        this.init();
    }

    init() {
        if (!this.container) return; // Cek lagi
        this.applyPositions();
        this.attachEvents();
    }

    applyPositions() {
        if (!this.cards) return;
        this.cards.forEach((card, index) => {
            // Pastikan posisi ada untuk index ini
            if(positions[index]) {
                const pos = positions[index];
                gsap.set(card, {
                    height: pos.height,
                    clipPath: pos.clip,
                    transform: `translateZ(${pos.z}px) rotateY(${pos.rotateY}deg) translateY(${pos.y}px)`
                });
            } else {
                console.warn(`Position definition missing for card index: ${index}`);
            }
        });
    }

    expandCard(card) {
        if (this.expandedCard || !this.cardInfo || !this.cardTitle || !this.cardDesc || !this.closeBtn) return;

        this.expandedCard = card;
        const title = card.dataset.title;
        const desc = card.dataset.desc;

        this.cardTitle.textContent = title;
        this.cardDesc.textContent = desc;

        const rect = card.getBoundingClientRect();
        const clone = card.cloneNode(true);
        const overlay = clone.querySelector(".hover-overlay");
        if (overlay) overlay.remove();

        clone.style.position = "fixed";
        clone.style.left = rect.left + "px";
        clone.style.top = rect.top + "px";
        clone.style.width = rect.width + "px";
        clone.style.height = rect.height + "px";
        clone.style.margin = "0";
        clone.style.zIndex = "1031"; // z-index yang sudah diperbaiki
        clone.classList.add("clone");

        document.body.appendChild(clone);
        this.cardClone = clone; // Simpan referensi ke clone

        gsap.set(card, { opacity: 0 });
        if (this.track) this.track.classList.add("blurred");

        const maxHeight = window.innerHeight * 0.8;
        const finalWidth = 500;
        const finalHeight = Math.min(650, maxHeight);
        const centerX = window.innerWidth / 2;
        const centerY = window.innerHeight / 2;

        gsap.to(clone, {
            width: finalWidth,
            height: finalHeight,
            left: centerX - finalWidth / 2,
            top: centerY - finalHeight / 2,
            clipPath: "polygon(0 0, 100% 0, 100% 100%, 0 100%)",
            transform: "translateZ(0) rotateY(0deg)",
            duration: 0.8,
            ease: "power2.out",
            onComplete: () => {
                if (this.cardInfo) this.cardInfo.classList.add("visible");
                if (this.closeBtn) this.closeBtn.classList.add("visible");
            }
        });
    }

    closeCard() {
        if (!this.expandedCard || !this.cardClone || !this.cardInfo || !this.closeBtn) return;

        if (this.cardInfo) this.cardInfo.classList.remove("visible");
        if (this.closeBtn) this.closeBtn.classList.remove("visible");

        const card = this.expandedCard;
        const clone = this.cardClone;
        const rect = card.getBoundingClientRect(); // Ambil posisi ASLI card (yang tersembunyi)
        const index = this.cards.indexOf(card);
        
        // Pastikan posisi ada
        if (positions[index]) {
            const pos = positions[index];

            gsap.to(clone, {
                width: rect.width,
                height: rect.height,
                left: rect.left,
                top: rect.top,
                clipPath: pos.clip,
                transform: `translateZ(${pos.z}px) rotateY(${pos.rotateY}deg) translateY(${pos.y}px)`, // Kembali ke transform 3D
                duration: 0.8,
                ease: "power2.out",
                onComplete: () => {
                    clone.remove();
                    gsap.set(card, { opacity: 1 });
                    if (this.track) this.track.classList.remove("blurred");
                    this.expandedCard = null;
                    this.cardClone = null;
                }
            });
        } else {
             // Fallback jika posisi tidak ada
             clone.remove();
             gsap.set(card, { opacity: 1 });
             if (this.track) this.track.classList.remove("blurred");
             this.expandedCard = null;
             this.cardClone = null;
        }
    }


    rotate(direction) {
        if (this.expandedCard || !this.cards || !this.track) return;

        this.cards.forEach((card, index) => {
            let newIndex;
            if (direction === "next") {
                newIndex = (index - 1 + this.totalCards) % this.totalCards;
            } else {
                newIndex = (index + 1) % this.totalCards;
            }

            // Pastikan posisi ada
            if(positions[newIndex]) {
                const pos = positions[newIndex];
                gsap.set(card, { clipPath: pos.clip });
                gsap.to(card, {
                    height: pos.height,
                    duration: 0.5,
                    ease: "power2.out"
                });
                gsap.to(card, {
                    transform: `translateZ(${pos.z}px) rotateY(${pos.rotateY}deg) translateY(${pos.y}px)`,
                    duration: 0.5,
                    ease: "power2.out"
                });
            }
        });

        if (direction === "next") {
            const firstCard = this.cards.shift();
            this.cards.push(firstCard);
            this.track.appendChild(firstCard);
        } else {
            const lastCard = this.cards.pop();
            this.cards.unshift(lastCard);
            this.track.prepend(lastCard);
        }
    }

    attachEvents() {
        if (!this.container || !this.cards || !this.closeBtn) return; // Cek elemen penting

        this.cards.forEach((card) => {
            card.addEventListener("click", (e) => {
                if (!this.isDragging && !this.expandedCard) {
                    this.expandCard(card);
                }
            });
        });

        this.closeBtn.addEventListener("click", () => this.closeCard());

        this.container.addEventListener("mousedown", (e) => this.handleDragStart(e));
        this.container.addEventListener("touchstart", (e) => this.handleDragStart(e), { passive: false });

        document.addEventListener("mousemove", (e) => this.handleDragMove(e));
        document.addEventListener("touchmove", (e) => this.handleDragMove(e), { passive: false });

        document.addEventListener("mouseup", () => this.handleDragEnd());
        document.addEventListener("touchend", () => this.handleDragEnd());

        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && this.expandedCard) {
                this.closeCard();
            } else if (e.key === "ArrowLeft" && !this.expandedCard) {
                this.rotate("prev");
            } else if (e.key === "ArrowRight" && !this.expandedCard) {
                this.rotate("next");
            }
        });
    }

    handleDragStart(e) {
        if (this.expandedCard || !this.container) return;
        this.isDragging = true;
        this.container.classList.add("dragging");
        this.startX = e.type.includes("mouse") ? e.clientX : e.touches[0].clientX;
        this.dragDistance = 0;
        this.processedSteps = 0;
    }

    handleDragMove(e) {
        if (!this.isDragging) return;
        e.preventDefault();
        const currentX = e.type.includes("mouse") ? e.clientX : e.touches[0].clientX;
        this.dragDistance = currentX - this.startX;
        const steps = Math.floor(Math.abs(this.dragDistance) / this.threshold);
        if (steps > this.processedSteps) {
            const direction = this.dragDistance > 0 ? "prev" : "next";
            this.rotate(direction);
            this.processedSteps = steps;
        }
    }

    handleDragEnd() {
        if (!this.isDragging || !this.container) return;
        this.isDragging = false;
        this.container.classList.remove("dragging");
    }
}


// ==========================================================
// SATU EVENT LISTENER UNTUK SEMUA INISIALISASI
// ==========================================================
document.addEventListener('DOMContentLoaded', function() {

    // --- Kode Navbar Scroll (Hanya jika #home-banner ada) ---
    var navbar = document.getElementById('main-navbar');
    var banner = document.getElementById('home-banner');

    function updateNavbar() {
        // Cek lagi di dalam fungsi jika banner atau navbar hilang
        if (!banner || !navbar) return; 

        var navHeight = navbar.offsetHeight;
        var bannerHeight = banner.offsetHeight;
        var scrollY = window.scrollY;
        
        // Pastikan bannerHeight valid sebelum membandingkan
        if (bannerHeight > 0 && scrollY > (bannerHeight - navHeight)) {
            navbar.classList.remove('navbar-transparent');
            navbar.classList.add('navbar-solid');
        } else {
            navbar.classList.add('navbar-transparent');
            navbar.classList.remove('navbar-solid');
        }
    }

    // Hanya jalankan fungsi navbar scroll jika banner ADA saat load
    if (banner && navbar) {
        window.addEventListener('scroll', updateNavbar);
        updateNavbar(); // run once on page load
    } else if (navbar) {
        // Jika tidak ada banner, pastikan navbar langsung solid
         navbar.classList.remove('navbar-transparent');
         navbar.classList.add('navbar-solid');
    }


    // --- Kode Smooth Scroll (Ini aman, bisa jalan di semua halaman) ---
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const hrefAttribute = this.getAttribute('href');
            // Cek jika linknya bukan hanya '#' dan elemen targetnya ada
            if (hrefAttribute && hrefAttribute.length > 1 && document.querySelector(hrefAttribute)) {
                e.preventDefault();
                document.querySelector(hrefAttribute).scrollIntoView({ behavior: 'smooth' });
            }
        });
    });


    // --- Kode Typed.js (Hanya jika #typed ada) ---
    var typedElement = document.getElementById('typed');
    if (typedElement) {
        try {
            var typed = new Typed('#typed', {
                strings: [
                    'Procurement and Sale of Indonesian PKS and PALM OIL',
                    'International Network Palm Oil Supplier',
                    'Export & Domestic PKS Solutions',
                    'Trusted Partnership Indonesia'
                ],
                typeSpeed: 47,
                backSpeed: 37,
                backDelay: 1900,
                startDelay: 400,
                loop: true,
                showCursor: true,
                smartBackspace: true
            });
        } catch (e) {
            console.error("Typed.js initialization failed:", e);
        }
    }


    // --- Kode Slider Portofolio (Hanya jika #sliderTrack ada) ---
    var sliderTrackElement = document.getElementById("sliderTrack");
    if (sliderTrackElement) {
        try {
            const sliderCards = Array.from(sliderTrackElement.querySelectorAll(".card"));
            if (sliderCards.length > 0) {
                 new CircularSlider(sliderCards);
            } else {
                 console.warn("Slider track found, but no cards inside.");
            }
        } catch (e) {
             console.error("CircularSlider initialization failed:", e);
        }
    }

}); // <-- Akhir dari event listener DOMContentLoaded