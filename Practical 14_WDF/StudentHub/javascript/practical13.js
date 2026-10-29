
const API_URL = "event_api.php";

const form = document.getElementById("event-form");
const idInput = document.getElementById("event-id");
const titleInput = document.getElementById("title");
const categoryInput = document.getElementById("category");
const dateInput = document.getElementById("event-date");
const locationInput = document.getElementById("location");
const descriptionInput = document.getElementById("description");

const messageBox = document.getElementById("api-message");
const tableBody = document.getElementById("event-table-body");
const eventCount = document.getElementById("event-count");
const formHeading = document.getElementById("form-heading");
const saveButton = document.getElementById("save-button");
const cancelButton = document.getElementById("cancel-button");

let events = [];

function showMessage(message, type = "success") {
    messageBox.textContent = message;
    messageBox.className = `p13-message ${type}`;
}

function clearMessage() {
    messageBox.textContent = "";
    messageBox.className = "p13-message";
}

async function requestApi(method = "GET", data = null) {
    const options = {
        method,
        headers: { Accept: "application/json" }
    };

    if (data !== null) {
        options.headers["Content-Type"] = "application/json";
        options.body = JSON.stringify(data);
    }

    let response;

    try {
        response = await fetch(API_URL, options);
    } catch {
        throw new Error("Could not connect to the server. Check Apache and MySQL.");
    }

    let result;

    try {
        result = await response.json();
    } catch {
        throw new Error("Invalid server response. Check the PHP error log.");
    }

    if (!response.ok || !result.success) {
        throw new Error(result.message || `Request failed (${response.status}).`);
    }

    return result;
}

function addCell(row, value) {
    const cell = document.createElement("td");
    cell.textContent = value ?? "";
    row.appendChild(cell);
    return cell;
}

function formatDate(value) {
    if (!value) return "—";

    const date = new Date(value.replace(" ", "T"));

    return Number.isNaN(date.getTime())
        ? value
        : date.toLocaleString();
}

function makeButton(label, action, id, className) {
    const button = document.createElement("button");
    button.type = "button";
    button.textContent = label;
    button.dataset.action = action;
    button.dataset.id = id;
    button.className = `p13-action-button ${className}`;
    return button;
}

function renderEvents(records) {
    events = records;
    tableBody.replaceChildren();

    if (!records.length) {
        const row = document.createElement("tr");
        const cell = addCell(row, "No events found.");
        cell.colSpan = 7;
        cell.className = "p13-empty";
        tableBody.appendChild(row);
        eventCount.textContent = "No event records.";
        return;
    }

    const fragment = document.createDocumentFragment();

    records.forEach((event) => {
        const row = document.createElement("tr");

        addCell(row, event.id);
        addCell(row, event.title);
        addCell(row, event.category);
        addCell(row, formatDate(event.event_date));
        addCell(row, event.location);
        addCell(row, event.description);

        const actionCell = document.createElement("td");
        const group = document.createElement("div");
        group.className = "p13-row-actions";

        group.appendChild(makeButton("Edit", "edit", event.id, "p13-edit"));
        group.appendChild(makeButton("Delete", "delete", event.id, "p13-delete"));

        actionCell.appendChild(group);
        row.appendChild(actionCell);
        fragment.appendChild(row);
    });

    tableBody.appendChild(fragment);
    eventCount.textContent =
        `${records.length} event${records.length === 1 ? "" : "s"} retrieved from the API.`;
}

async function loadEvents(showSuccess = false) {
    tableBody.innerHTML =
        '<tr><td colspan="7" class="p13-empty">Loading events...</td></tr>';

    try {
        const result = await requestApi("GET");
        renderEvents(result.data || []);

        if (showSuccess) showMessage(result.message);
    } catch (error) {
        tableBody.innerHTML =
            '<tr><td colspan="7" class="p13-empty">Could not load events.</td></tr>';
        eventCount.textContent = "Unable to retrieve events.";
        showMessage(error.message, "error");
    }
}

function resetForm() {
    form.reset();
    idInput.value = "";
    formHeading.textContent = "Add Event";
    saveButton.textContent = "Add Event";
    cancelButton.hidden = true;
    clearMessage();
}

function startEditing(event) {
    idInput.value = event.id;
    titleInput.value = event.title;
    categoryInput.value = event.category;
    dateInput.value = event.event_date.replace(" ", "T").slice(0, 16);
    locationInput.value = event.location;
    descriptionInput.value = event.description;

    formHeading.textContent = `Edit Event #${event.id}`;
    saveButton.textContent = "Save Changes";
    cancelButton.hidden = false;

    clearMessage();
    form.scrollIntoView({ behavior: "smooth", block: "start" });
    titleInput.focus();
}

form.addEventListener("submit", async (event) => {
    event.preventDefault();
    clearMessage();

    const editing = idInput.value !== "";
    const payload = {
        title: titleInput.value.trim(),
        category: categoryInput.value,
        event_date: dateInput.value,
        location: locationInput.value.trim(),
        description: descriptionInput.value.trim()
    };

    if (editing) payload.id = Number(idInput.value);

    saveButton.disabled = true;
    saveButton.textContent = editing ? "Updating..." : "Adding...";

    try {
        const result = await requestApi(editing ? "PUT" : "POST", payload);

        resetForm();
        showMessage(result.message);
        await loadEvents();
    } catch (error) {
        showMessage(error.message, "error");
    } finally {
        saveButton.disabled = false;
        saveButton.textContent = idInput.value ? "Save Changes" : "Add Event";
    }
});

tableBody.addEventListener("click", async (event) => {
    const button = event.target.closest("button[data-action]");
    if (!button) return;

    const id = Number(button.dataset.id);

    if (button.dataset.action === "edit") {
        const selected = events.find(item => Number(item.id) === id);

        if (!selected) {
            showMessage("Event not found. Refresh the list.", "error");
            return;
        }

        startEditing(selected);
        return;
    }

    if (button.dataset.action === "delete") {
        if (!window.confirm(`Delete event #${id}? This cannot be undone.`)) {
            return;
        }

        button.disabled = true;

        try {
            const result = await requestApi("DELETE", { id });

            if (Number(idInput.value) === id) resetForm();

            showMessage(result.message);
            await loadEvents();
        } catch (error) {
            showMessage(error.message, "error");
            button.disabled = false;
        }
    }
});

cancelButton.addEventListener("click", resetForm);

document.getElementById("clear-button").addEventListener("click", () => {
    window.setTimeout(resetForm, 0);
});

document.getElementById("refresh-button").addEventListener("click", () => {
    clearMessage();
    loadEvents(true);
});

loadEvents();
