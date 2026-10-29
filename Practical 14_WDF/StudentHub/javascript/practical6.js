let events = [];


// Fetch events from JSON file
fetch("../data/events.json")
    .then(response => response.json())
    .then(data => {

        events = data;

        createCategoryFilter();

        displayEvents(events);

    })
    .catch(error => {

        console.log("Error loading events:", error);

    });


// Display events
function displayEvents(eventData) {

    const eventList = document.getElementById("eventList");

    eventList.innerHTML = "";

    eventData.forEach(event => {

        const card = document.createElement("article");

        card.className = "card";

        card.innerHTML = `
            <h3>${event.title}</h3>

            <p>
                <strong>Category:</strong>
                ${event.category}
            </p>

            <p>
                <strong>Date:</strong>
                ${formatDate(event.date)}
            </p>

            <p>
                ${event.description}
            </p>

            <p>
                <strong>Location:</strong>
                ${event.location}
            </p>

            <button
                type="button"
                class="event-register-btn"
                data-event-id="${event.id}"
                data-event="${event.title}">
                Register
            </button>
        `;

        eventList.appendChild(card);

    });

}


// Format date
function formatDate(date) {

    const d = new Date(date);

    return d.toLocaleDateString("en-GB", {
        day: "numeric",
        month: "long",
        year: "numeric"
    });

}


// Create category dropdown
function createCategoryFilter() {

    const categoryFilter =
        document.getElementById("categoryFilter");

    const categories = [];

    events.forEach(event => {

        if (!categories.includes(event.category)) {

            categories.push(event.category);

        }

    });

    categories.sort();

    categories.forEach(category => {

        const option = document.createElement("option");

        option.value = category;

        option.textContent = category;

        categoryFilter.appendChild(option);

    });

}


// Search events
document
    .getElementById("searchEvent")
    .addEventListener("input", filterEvents);


// Category filter
document
    .getElementById("categoryFilter")
    .addEventListener("change", filterEvents);


// Search + category filter
function filterEvents() {

    const searchText =
        document
            .getElementById("searchEvent")
            .value
            .toLowerCase();

    const selectedCategory =
        document
            .getElementById("categoryFilter")
            .value;


    const filteredEvents = events.filter(event => {

        const matchesSearch =

            event.title.toLowerCase().includes(searchText)

            ||

            event.description.toLowerCase().includes(searchText)

            ||

            event.location.toLowerCase().includes(searchText)

            ||

            event.category.toLowerCase().includes(searchText);


        const matchesCategory =

            selectedCategory === "all"

            ||

            event.category === selectedCategory;


        return matchesSearch && matchesCategory;

    });


    displayEvents(filteredEvents);

}


// Open event registration form
document
    .getElementById("eventList")
    .addEventListener("click", function (event) {

        if (
            event.target.classList.contains(
                "event-register-btn"
            )
        ) {

            const eventName =
                event.target.getAttribute("data-event");

            const eventId =
                event.target.getAttribute("data-event-id");

            document.getElementById( "eventName").value = eventName;

            document.getElementById("eventId").value = eventId;

            document.getElementById("eventFormMessage").textContent = "";

            document.getElementById("eventRegistrationModal" ).style.display = "block";

        }

    });


// Close event registration form
document
    .getElementById("closeEventForm")
    .addEventListener("click", function () {

        document.getElementById(
            "eventRegistrationModal"
        ).style.display = "none";

    });


// Close modal when clicking outside
window.addEventListener("click", function (event) {

    const modal =
        document.getElementById(
            "eventRegistrationModal"
        );

    if (event.target === modal) {

        modal.style.display = "none";

    }

});