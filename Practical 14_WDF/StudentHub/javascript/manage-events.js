
const API_URL = "event_api.php";

const form = document.getElementById("event-form");
const idInput = document.getElementById("event-id");
const titleInput = document.getElementById("title");
const categoryInput = document.getElementById("category");
const dateInput = document.getElementById("event_date");
const locationInput = document.getElementById("location");
const descriptionInput = document.getElementById("description");
const posterInput = document.getElementById("poster");

const messageBox = document.getElementById("event-message");
const tableBody = document.getElementById("events-table-body");
const countLabel = document.getElementById("events-count");
const formTitle = document.getElementById("event-form-title");
const saveButton = document.getElementById("event-save-button");
const cancelButton = document.getElementById("event-cancel-button");
const currentPoster = document.getElementById("current-poster");

let eventsCache = [];

function showEventMessage(message, type = "success") {
    messageBox.textContent = message;
    messageBox.className = `event-message ${type}`;
}

function clearEventMessage() {
    messageBox.textContent = "";
    messageBox.className = "event-message";
}

async function apiRequest(url, options = {}) {
    let response;

    try {
        response = await fetch(url, options);
    } catch {
        throw new Error("Could not connect to the server. Check Apache and MySQL.");
    }

    let result;

    try {
        result = await response.json();
    } catch {
        throw new Error("The server returned an invalid response. Check the PHP error log.");
    }

    if (!response.ok || !result.success) {
        throw new Error(result.message || "The operation failed.");
    }

    return result;
}

function makeCell(value) {
    const cell = document.createElement("td");
    cell.textContent = value ?? "";
    return cell;
}

function formatDate(value) {
    if (!value) return "—";

    const date = new Date(value.replace(" ", "T"));

    if (Number.isNaN(date.getTime())) return value;

    return date.toLocaleString();
}

function makeActionButton(label, action, id, className) {
    const button = document.createElement("button");
    button.type = "button";
    button.textContent = label;
    button.dataset.action = action;
    button.dataset.id = id;
    button.className = `event-action-button ${className}`;
    return button;
}

function renderEvents(events) {
    eventsCache = events;
    tableBody.replaceChildren();

    if (!events.length) {
        const row = document.createElement("tr");
        const cell = makeCell("No events found.");
        cell.colSpan = 8;
        cell.className = "events-empty";
        row.appendChild(cell);
        tableBody.appendChild(row);
        countLabel.textContent = "0 events found.";
        return;
    }

    const fragment = document.createDocumentFragment();

    events.forEach((event) => {
        const row = document.createElement("tr");

        row.appendChild(makeCell(event.id));

        const posterCell = document.createElement("td");

        if (event.poster_url) {
            const image = document.createElement("img");
            image.src = event.poster_url;
            image.alt = `Poster for ${event.title}`;
            image.className = "event-poster-thumbnail";
            image.loading = "lazy";
            posterCell.appendChild(image);
        } else {
            posterCell.textContent = "No poster";
        }

        row.appendChild(posterCell);
        row.appendChild(makeCell(event.title));
        row.appendChild(makeCell(event.category));
        row.appendChild(makeCell(formatDate(event.event_date)));
        row.appendChild(makeCell(event.location));
        row.appendChild(makeCell(event.description));

        const actionsCell = document.createElement("td");
        const actions = document.createElement("div");
        actions.className = "event-row-actions";

        actions.appendChild(
            makeActionButton("Edit", "edit", event.id, "event-edit-button")
        );
        actions.appendChild(
            makeActionButton("Delete", "delete", event.id, "event-delete-button")
        );

        actionsCell.appendChild(actions);
        row.appendChild(actionsCell);
        fragment.appendChild(row);
    });

    tableBody.appendChild(fragment);
    countLabel.textContent = `${events.length} event${events.length === 1 ? "" : "s"} found.`;
}

async function loadEvents() {
    tableBody.innerHTML =
        '<tr><td colspan="8" class="events-empty">Loading events...</td></tr>';

    try {
        const result = await apiRequest(API_URL);
        renderEvents(result.data || []);
    } catch (error) {
        tableBody.innerHTML =
            '<tr><td colspan="8" class="events-empty">Could not load events.</td></tr>';
        countLabel.textContent = "Unable to load events.";
        showEventMessage(error.message, "error");
    }
}

function resetEventForm() {
    form.reset();
    idInput.value = "";
    formTitle.textContent = "Add Event";
    saveButton.textContent = "Add Event";
    cancelButton.hidden = true;
    currentPoster.replaceChildren();
    posterInput.required = false;
    clearEventMessage();
}

function startEventEdit(event) {
    idInput.value = event.id;
    titleInput.value = event.title;
    categoryInput.value = event.category;
    dateInput.value = event.event_date.replace(" ", "T").slice(0, 16);
    locationInput.value = event.location;
    descriptionInput.value = event.description;
    posterInput.value = "";

    formTitle.textContent = `Edit Event #${event.id}`;
    saveButton.textContent = "Save Changes";
    cancelButton.hidden = false;

    currentPoster.replaceChildren();

    if (event.poster_url) {
        const image = document.createElement("img");
        image.src = event.poster_url;
        image.alt = "Current event poster";
        image.className = "event-current-poster";
        currentPoster.appendChild(image);

        const note = document.createElement("p");
        note.textContent = "Choose a new image to replace this poster, or leave the upload empty to keep it.";
        currentPoster.appendChild(note);
    } else {
        currentPoster.textContent = "No poster uploaded for this event.";
    }

    clearEventMessage();
    form.scrollIntoView({ behavior: "smooth", block: "start" });
    titleInput.focus();
}

form.addEventListener("submit", async (event) => {
    event.preventDefault();
    clearEventMessage();

    const isEditing = idInput.value !== "";
    const formData = new FormData(form);
    formData.set("action", isEditing ? "update" : "add");

    saveButton.disabled = true;
    saveButton.textContent = isEditing ? "Saving..." : "Adding...";

    try {
        const result = await apiRequest(API_URL, {
            method: "POST",
            body: formData
        });

        resetEventForm();
        showEventMessage(result.message, "success");
        await loadEvents();
    } catch (error) {
        showEventMessage(error.message, "error");
    } finally {
        saveButton.disabled = false;
        saveButton.textContent = idInput.value ? "Save Changes" : "Add Event";
    }
});

tableBody.addEventListener("click", async (event) => {
    const button = event.target.closest("button[data-action]");

    if (!button) return;

    const id = button.dataset.id;
    const action = button.dataset.action;

    if (action === "edit") {
        const selectedEvent = eventsCache.find(
            (item) => String(item.id) === String(id)
        );

        if (!selectedEvent) {
            showEventMessage("Event not found. Refresh the list.", "error");
            return;
        }

        startEventEdit(selectedEvent);
        return;
    }

    if (action === "delete") {
        if (!window.confirm(
            `Are you sure you want to delete event #${id}?\nThis action cannot be undone.`
        )) {
            return;
        }

        const formData = new FormData();
        formData.set("action", "delete");
        formData.set("id", id);
        button.disabled = true;

        try {
            const result = await apiRequest(API_URL, {
                method: "POST",
                body: formData
            });

            if (idInput.value === String(id)) {
                resetEventForm();
            }

            showEventMessage(result.message, "success");
            await loadEvents();
        } catch (error) {
            showEventMessage(error.message, "error");
            button.disabled = false;
        }
    }
});

cancelButton.addEventListener("click", resetEventForm);

document.getElementById("event-clear-button").addEventListener("click", () => {
    window.setTimeout(resetEventForm, 0);
});

document.getElementById("event-refresh-button").addEventListener("click", () => {
    clearEventMessage();
    loadEvents();
});

loadEvents();
