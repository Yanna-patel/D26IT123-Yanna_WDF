
const API_URL = "../php/student_api.php";

const form = document.getElementById("student-form");
const messageBox = document.getElementById("message");
const tableBody = document.getElementById("student-table-body");
const searchInput = document.getElementById("search");
const courseFilter = document.getElementById("course-filter");
const roleFilter = document.getElementById("role-filter");
const recordCount = document.getElementById("record-count");

const idInput = document.getElementById("student-id");
const fullnameInput = document.getElementById("fullname");
const emailInput = document.getElementById("email");
const courseInput = document.getElementById("course");
const roleInput = document.getElementById("role");
const passwordInput = document.getElementById("password");

const formTitle = document.getElementById("form-title");
const saveButton = document.getElementById("save-button");
const cancelButton = document.getElementById("cancel-button");
const passwordLabel = document.getElementById("password-label");
const passwordHelp = document.getElementById("password-help");

let searchTimer;

function showMessage(message, type = "success") {
    messageBox.textContent = message;
    messageBox.className = `message ${type}`;
}

function clearMessage() {
    messageBox.textContent = "";
    messageBox.className = "message";
}

async function requestJson(url, options = {}) {
    let response;

    try {
        response = await fetch(url, options);
    } catch (error) {
        throw new Error(
            "Could not connect to the server. Make sure Apache and MySQL are running."
        );
    }

    let result;

    try {
        result = await response.json();
    } catch (error) {
        throw new Error(
            "The server returned an invalid response. Check the PHP error logs and db.php."
        );
    }

    if (!response.ok || !result.success) {
        throw new Error(result.message || "The request failed.");
    }

    return result;
}

function createCell(value) {
    const cell = document.createElement("td");
    cell.textContent = value ?? "";
    return cell;
}

function createActionButton(label, className, action, id) {
    const button = document.createElement("button");
    button.type = "button";
    button.textContent = label;
    button.className = `action-button ${className}`;
    button.dataset.action = action;
    button.dataset.id = id;
    return button;
}

function renderStudents(students) {
    tableBody.replaceChildren();

    if (!students.length) {
        const row = document.createElement("tr");
        const cell = document.createElement("td");

        cell.colSpan = 6;
        cell.className = "empty-state";
        cell.textContent = "No student records found.";

        row.appendChild(cell);
        tableBody.appendChild(row);
        recordCount.textContent = "0 student records found.";
        return;
    }

    const fragment = document.createDocumentFragment();

    students.forEach((student) => {
        const row = document.createElement("tr");

        row.appendChild(createCell(student.id));
        row.appendChild(createCell(student.fullname));
        row.appendChild(createCell(student.email));
        row.appendChild(createCell(student.course));
        row.appendChild(createCell(student.role));

        const actionsCell = document.createElement("td");
        const actions = document.createElement("div");
        actions.className = "row-actions";

        actions.appendChild(
            createActionButton("Edit", "edit-button", "edit", student.id)
        );

        actions.appendChild(
            createActionButton("Delete", "delete-button", "delete", student.id)
        );

        actionsCell.appendChild(actions);
        row.appendChild(actionsCell);
        fragment.appendChild(row);
    });

    tableBody.appendChild(fragment);
    recordCount.textContent =
        `${students.length} student record${students.length === 1 ? "" : "s"} found.`;
}

async function loadStudents() {
    tableBody.innerHTML =
        '<tr><td colspan="6" class="empty-state">Loading records...</td></tr>';

    const params = new URLSearchParams();

    if (searchInput.value.trim()) {
        params.set("search", searchInput.value.trim());
    }

    if (courseFilter.value) {
        params.set("course", courseFilter.value);
    }

    if (roleFilter.value) {
        params.set("role", roleFilter.value);
    }

    try {
        const result = await requestJson(
            `${API_URL}?${params.toString()}`
        );

        renderStudents(result.data || []);
    } catch (error) {
        tableBody.innerHTML =
            '<tr><td colspan="6" class="empty-state">Could not load records.</td></tr>';
        recordCount.textContent = "Unable to retrieve student records.";
        showMessage(error.message, "error");
    }
}

async function loadCourseOptions() {
    try {
        const result = await requestJson(API_URL);
        const students = result.data || [];
        const selectedCourse = courseFilter.value;

        const courses = [
            ...new Set(
                students
                    .map((student) => student.course)
                    .filter((course) => course && course.trim())
            )
        ].sort((a, b) => a.localeCompare(b));

        courseFilter.replaceChildren();

        const allOption = document.createElement("option");
        allOption.value = "";
        allOption.textContent = "All Courses";
        courseFilter.appendChild(allOption);

        courses.forEach((course) => {
            const option = document.createElement("option");
            option.value = course;
            option.textContent = course;
            courseFilter.appendChild(option);
        });

        if (courses.includes(selectedCourse)) {
            courseFilter.value = selectedCourse;
        }
    } catch (error) {
        showMessage(error.message, "error");
    }
}

function resetForm() {
    form.reset();
    idInput.value = "";
    roleInput.value = "Student";

    formTitle.textContent = "Add Student";
    saveButton.textContent = "Add Student";
    cancelButton.hidden = true;

    passwordInput.required = true;
    passwordLabel.textContent = "(required for new students)";
    passwordHelp.textContent =
        "At least 8 characters. A password is required when adding a student.";

    clearMessage();
}

function startEditing(student) {
    idInput.value = student.id;
    fullnameInput.value = student.fullname;
    emailInput.value = student.email;
    courseInput.value = student.course;
    roleInput.value = student.role;
    passwordInput.value = "";
    passwordInput.required = false;

    formTitle.textContent = `Edit Student #${student.id}`;
    saveButton.textContent = "Save Changes";
    cancelButton.hidden = false;

    passwordLabel.textContent = "(optional)";
    passwordHelp.textContent =
        "Leave blank to keep the current password. Enter at least 8 characters to change it.";

    clearMessage();
    form.scrollIntoView({ behavior: "smooth", block: "start" });
    fullnameInput.focus();
}

form.addEventListener("submit", async (event) => {
    event.preventDefault();
    clearMessage();

    const isEditing = idInput.value !== "";
    const formData = new FormData(form);
    formData.set("action", isEditing ? "update" : "add");

    saveButton.disabled = true;
    saveButton.textContent = isEditing ? "Saving..." : "Adding...";

    try {
        const result = await requestJson(API_URL, {
            method: "POST",
            body: formData
        });

        resetForm();
        showMessage(result.message, "success");

        await loadCourseOptions();
        await loadStudents();
    } catch (error) {
        showMessage(error.message, "error");
    } finally {
        saveButton.disabled = false;
        saveButton.textContent = idInput.value ? "Save Changes" : "Add Student";
    }
});

tableBody.addEventListener("click", async (event) => {
    const button = event.target.closest("button[data-action]");

    if (!button) {
        return;
    }

    const studentId = button.dataset.id;
    const action = button.dataset.action;

    if (action === "edit") {
        try {
            const params = new URLSearchParams();
            const result = await requestJson(API_URL);

            const student = (result.data || []).find(
                (item) => String(item.id) === String(studentId)
            );

            if (!student) {
                showMessage("Student record not found. Refresh the list.", "error");
                return;
            }

            startEditing(student);
        } catch (error) {
            showMessage(error.message, "error");
        }
    }

    if (action === "delete") {
        const confirmed = window.confirm(
            `Are you sure you want to delete student ID ${studentId}?\n\n` +
            "This permanently deletes the record and removes that account's login details."
        );

        if (!confirmed) {
            return;
        }

        const formData = new FormData();
        formData.set("action", "delete");
        formData.set("id", studentId);

        button.disabled = true;

        try {
            const result = await requestJson(API_URL, {
                method: "POST",
                body: formData
            });

            if (idInput.value === String(studentId)) {
                resetForm();
            }

            showMessage(result.message, "success");
            await loadCourseOptions();
            await loadStudents();
        } catch (error) {
            showMessage(error.message, "error");
            button.disabled = false;
        }
    }
});

searchInput.addEventListener("input", () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadStudents, 300);
});

courseFilter.addEventListener("change", loadStudents);
roleFilter.addEventListener("change", loadStudents);

document.getElementById("refresh-button").addEventListener("click", async () => {
    clearMessage();
    await loadCourseOptions();
    await loadStudents();
});

cancelButton.addEventListener("click", resetForm);

document.getElementById("clear-button").addEventListener("click", () => {
    window.setTimeout(resetForm, 0);
});

async function initializePage() {
    await loadCourseOptions();
    await loadStudents();
}

initializePage();
