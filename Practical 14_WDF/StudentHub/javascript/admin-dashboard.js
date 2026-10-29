// Practical 14: Load dashboard statistics asynchronously

document.addEventListener("DOMContentLoaded", loadAdminDashboard);

async function loadAdminDashboard() {
    const message = document.getElementById("dashboardMessage");
    const tableBody = document.getElementById("registrationsTableBody");

    try {
        const response = await fetch("../php/dashboard_stats.php", {
            method: "GET",
            credentials: "same-origin",
            headers: {
                Accept: "application/json"
            },
            cache: "no-store"
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || "Unable to load dashboard.");
        }

        const data = result.data;

        document.getElementById("totalUsers").textContent = data.total_users;
        document.getElementById("studentCount").textContent = data.student_count;
        document.getElementById("facultyCount").textContent = data.faculty_count;
        document.getElementById("eventCount").textContent = data.event_count;
        document.getElementById("registrationCount").textContent =
            data.registration_count;

        renderLatestRegistrations(data.latest_registrations || []);

        message.textContent = "Dashboard statistics loaded successfully.";

    } catch (error) {
        console.error("Admin dashboard error:", error);

        message.textContent =
            "Could not load dashboard data. Please check your login and PHP error log.";

        tableBody.replaceChildren();

        const row = document.createElement("tr");
        const cell = document.createElement("td");

        cell.colSpan = 6;
        cell.textContent = "Registrations could not be loaded.";

        row.appendChild(cell);
        tableBody.appendChild(row);
    }
}

function renderLatestRegistrations(registrations) {
    const tableBody = document.getElementById("registrationsTableBody");
    tableBody.replaceChildren();

    if (registrations.length === 0) {
        const row = document.createElement("tr");
        const cell = document.createElement("td");

        cell.colSpan = 6;
        cell.textContent = "No registrations have been recorded yet.";

        row.appendChild(cell);
        tableBody.appendChild(row);
        return;
    }

    registrations.forEach(function (registration) {
        const row = document.createElement("tr");

        const values = [
            registration.registration_id,
            registration.student_name,
            registration.student_email,
            registration.event_title,
            formatRegistrationDate(registration.registered_at),
            registration.status || "Pending"
        ];

        values.forEach(function (value) {
            const cell = document.createElement("td");
            cell.textContent = value == null || value === "" ? "—" : String(value);
            row.appendChild(cell);
        });

        tableBody.appendChild(row);
    });
}

function formatRegistrationDate(value) {
    if (!value) {
        return "—";
    }

    const date = new Date(String(value).replace(" ", "T"));

    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return date.toLocaleString();
}