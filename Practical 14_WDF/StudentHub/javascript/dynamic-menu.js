// Practical 14: Dynamic navigation based on user role

document.addEventListener("DOMContentLoaded", loadDynamicMenu);

async function loadDynamicMenu() {
    const menu = document.getElementById("navigationMenu");

    if (!menu) {
        return;
    }

    try {
        const response = await fetch("../php/session_status.php", {
            credentials: "same-origin",
            headers: {
                Accept: "application/json"
            },
            cache: "no-store"
        });

        if (!response.ok) {
            throw new Error("Could not retrieve session status.");
        }

        const session = await response.json();

        const role = session.logged_in
            ? String(session.role || "").toLowerCase()
            : "guest";

        const base = "/StudentHub/";
        const pages = base + "pages/";
        const php = base + "php/";

        const menus = {
            admin: [
                ["Home", pages + "index.html"],
                ["About", pages + "about.html"],
                ["Events", pages + "events.html"],
                ["Students", pages + "students.html"],
                ["Admin Dashboard", php + "dashboard.php"],
                ["Manage Events", php + "manage-events.php"],
                ["Contact", pages + "contact.html"],
                ["FAQ", pages + "faq.html"],
                ["Logout", php + "logout.php"]
            ],

            faculty: [
                ["Home", pages + "index.html"],
                ["About", pages + "about.html"],
                ["Events", pages + "events.html"],
                ["Faculty Dashboard", php + "faculty-dashboard.php"],
                ["Contact", pages + "contact.html"],
                ["FAQ", pages + "faq.html"],
                ["Logout", php + "logout.php"]
            ],

            student: [
                ["Home", pages + "index.html"],
                ["About", pages + "about.html"],
                ["Events", pages + "events.html"],
                ["Student Dashboard", php + "student-dashboard.php"],
                ["Student Profile", pages + "student-profile.html"],
                ["Contact", pages + "contact.html"],
                ["FAQ", pages + "faq.html"],
                ["Logout", php + "logout.php"]
            ],

            guest: [
                ["Home", pages + "index.html"],
                ["About", pages + "about.html"],
                ["Events", pages + "events.html"],
                ["Contact", pages + "contact.html"],
                ["FAQ", pages + "faq.html"],
                ["Login", pages + "login.html"],
                ["Register", pages + "register.html"]
            ]
        };

        const links = menus[role] || menus.guest;
        menu.replaceChildren();

        links.forEach(function (item) {
            const link = document.createElement("a");
            link.href = item[1];
            link.textContent = item[0];

            if (item[0] === "Logout") {
                link.addEventListener("click", function (event) {
                    if (!window.confirm("Are you sure you want to log out?")) {
                        event.preventDefault();
                    }
                });
            }

            menu.appendChild(link);
        });

        menu.dataset.role = role;

    } catch (error) {
        console.error("Dynamic menu error:", error);
    }
}