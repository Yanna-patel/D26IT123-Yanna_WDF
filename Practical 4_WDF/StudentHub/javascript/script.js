// ==========================================
// StudentHub - JavaScript
// ==========================================

console.log("StudentHub JavaScript loaded Successfully");
console.log("Welcome to the StudentHub");
console.log("Practical 4 - JavaScript");

// Variables
let studentname = "Yanna";
let course = "Information Technology";
let semester = "3";

console.log(studentname);
console.log(course);
console.log(semester);

let college = "Charusat";
let year = "2026";
let isstudent = true;

console.log(college);
console.log(year);
console.log(isstudent);

// Functions
function welcomemessage() {
    console.log("Welcome to StudentHub!");
}
welcomemessage();

function welcomeStudent(name) {
    console.log("Welcome " + name);
}
welcomeStudent("Yanna");
welcomeStudent("Keya");
welcomeStudent("Dhruvi");

// ==========================================
// DOM Manipulations & Event Listeners
// ==========================================

document.addEventListener('DOMContentLoaded', () => {

    // --- 1. Dark Mode Switcher (with LocalStorage) ---
    const themeBtn = document.getElementById('themeBtn');
    const sliderIcon = document.querySelector('.slider-icon');

    // Check saved theme preference on page load
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        if (themeBtn) themeBtn.checked = true;
        if (sliderIcon) sliderIcon.textContent = '☀️';
    } else {
        if (sliderIcon) sliderIcon.textContent = '🌙';
    }

    // Toggle theme on switch change
    if (themeBtn) {
        themeBtn.addEventListener('change', () => {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');

            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            
            if (sliderIcon) {
                sliderIcon.textContent = isDark ? '☀️' : '🌙';
            }
        });
    }

    // --- 2. Change Notification Text ---
    const changeBtn = document.getElementById('changeBtn');
    const notificationText = document.getElementById('notificationText');

    if (changeBtn && notificationText) {
        changeBtn.addEventListener('click', () => {
            if (notificationText.textContent.trim() === "📢 Welcome! Registration is Open!") {
                notificationText.textContent = "🎓 Welcome to CHARUSAT StudentHub!";
            } else {
                notificationText.textContent = "📢 Welcome! Registration is Open!";
            }
        });
    }

    // --- 3. Close Notification Banner ---
    const closeBtn = document.getElementById('closeBtn');
    const notification = document.getElementById('notification');

    if (closeBtn && notification) {
        closeBtn.addEventListener('click', () => {
            notification.style.display = 'none';
        });
    }

    // --- 4. Announcement Modal ---
    const openModalBtn = document.getElementById('openModalBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const modal = document.getElementById('announcementModal');

    if (openModalBtn && modal) {
        openModalBtn.addEventListener('click', () => {
            modal.style.display = 'flex';
        });
    }

    if (closeModalBtn && modal) {
        closeModalBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });
    }

    // Close modal when clicking outside box
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    // --- 5. FAQ Accordion Logic ---
    const faqQuestions = document.querySelectorAll('.faq-question');

    faqQuestions.forEach(question => {
        question.addEventListener('click', () => {
            const answer = question.nextElementSibling;
            if (!answer) return;

            const isOpen = answer.style.display === 'block';

            // Close all other FAQ answers first
            document.querySelectorAll('.faq-answer').forEach(item => {
                item.style.display = 'none';
            });

            // Open clicked answer if it was closed
            if (!isOpen) {
                answer.style.display = 'block';
            }
        });
    });

    // --- 6. Manual Hero Background Change --- 
    const hero = document.querySelector('.hero'); 
    const prevBgBtn = document.getElementById('prevBgBtn'); 
    const nextBgBtn = document.getElementById('nextBgBtn'); 
    const heroImages = [ 
        "../images/banner.jpg", 
        "../images/banner2.jpeg", 
        "../images/banner3.jpeg", 
        "../images/banner4.jpeg" 
    ]; 
    let imageIndex = 0; 

    // Function to change background 
    function changeHeroBackground() { 
        hero.style.backgroundImage = 
        `linear-gradient(
         rgba(0, 0, 0, 0.4),
         rgba(0, 0, 0, 0.4) 
         ), url("${heroImages[imageIndex]}")`; 
    } 

    // Next button 
    if (nextBgBtn && hero) { 
        nextBgBtn.addEventListener('click', () => { 
            imageIndex++; 
            if (imageIndex >= heroImages.length) { 
                imageIndex = 0; 
            } 
            changeHeroBackground(); 
        }); 
    } 

    // Previous button 
    if (prevBgBtn && hero) { 
        prevBgBtn.addEventListener('click', () => { 
            imageIndex--; 
            if (imageIndex < 0) { 
                imageIndex = heroImages.length - 1; 
            } 
            changeHeroBackground(); 
        }); 
    }

});