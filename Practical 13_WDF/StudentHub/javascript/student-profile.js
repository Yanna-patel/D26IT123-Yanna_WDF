
let students = [];
let currentPage = 1;

const studentsPerPage = 4;

const studentList = document.getElementById("studentList");
const searchStudent = document.getElementById("searchStudent");
const departmentFilter = document.getElementById("departmentFilter");
const studentSort = document.getElementById("studentSort");
const studentResults = document.getElementById("studentResults");

const prevPage = document.getElementById("prevPage");
const nextPage = document.getElementById("nextPage");
const pageInfo = document.getElementById("pageInfo");


// Fetch students from JSON
fetch("../data/student.json")
    .then(response => {
        if (!response.ok) {
            throw new Error(
                `Failed to load student.json: ${response.status}`
            );
        }

        return response.json();
    })
    .then(data => {
        if (!Array.isArray(data)) {
            throw new Error("student.json must contain an array.");
        }

        students = data;

        populateDepartments();
        displayStudents();
    })
    
.catch(error => {
    console.error("Student loading failed:", error);

    studentList.replaceChildren();

    const message = document.createElement("p");
    message.className = "student-empty";
    message.textContent = "Error: " + error.message;

    studentList.appendChild(message);
    studentResults.textContent = "";

    pageInfo.textContent = "Page 0 of 0";
    prevPage.disabled = true;
    nextPage.disabled = true;
});



// Populate department dropdown
function populateDepartments() {
    const departments = [
        ...new Set(
            students
                .map(student => student.department)
                .filter(Boolean)
        )
    ].sort((a, b) => a.localeCompare(b));

    departments.forEach(department => {
        const option = document.createElement("option");

        option.value = department;
        option.textContent = department;

        departmentFilter.appendChild(option);
    });
}


// Search, filter, sort and paginate
function displayStudents() {
    const searchText = searchStudent.value.toLowerCase().trim();
    const selectedDepartment = departmentFilter.value;
    const sortOrder = studentSort.value;

    let filteredStudents = students.filter(student => {
        const searchableText = [
            student.name,
            student.enrollment,
            student.email,
            student.phone,
            student.department,
            student.semester
        ]
            .map(value => String(value ?? "").toLowerCase())
            .join(" ");

        const matchesSearch = searchableText.includes(searchText);

        const matchesDepartment =
            selectedDepartment === "all" ||
            student.department === selectedDepartment;

        return matchesSearch && matchesDepartment;
    });

    // Sorting
    if (sortOrder === "name-asc") {
        filteredStudents.sort((a, b) =>
            (a.name || "").localeCompare(b.name || "")
        );
    } else if (sortOrder === "name-desc") {
        filteredStudents.sort((a, b) =>
            (b.name || "").localeCompare(a.name || "")
        );
    } else if (sortOrder === "semester-asc") {
        filteredStudents.sort((a, b) =>
            Number(a.semester) - Number(b.semester)
        );
    } else if (sortOrder === "semester-desc") {
        filteredStudents.sort((a, b) =>
            Number(b.semester) - Number(a.semester)
        );
    }

    // Pagination
    const totalStudents = filteredStudents.length;
    const totalPages = Math.ceil(totalStudents / studentsPerPage);

    if (totalStudents === 0) {
        currentPage = 1;
    } else {
        currentPage = Math.min(
            currentPage,
            totalPages
        );
    }

    const startIndex = (currentPage - 1) * studentsPerPage;

    const pageStudents = filteredStudents.slice(
        startIndex,
        startIndex + studentsPerPage
    );

    renderStudents(pageStudents);

    const firstStudent = totalStudents === 0 ? 0 : startIndex + 1;
    const lastStudent = Math.min(
        startIndex + studentsPerPage,
        totalStudents
    );

    studentResults.textContent =
        `Showing ${firstStudent}–${lastStudent} of ${totalStudents} students`;

    pageInfo.textContent =
        `Page ${totalStudents === 0 ? 0 : currentPage} of ${totalPages}`;

    prevPage.disabled = currentPage <= 1;
    nextPage.disabled =
        totalStudents === 0 || currentPage >= totalPages;
}


// Create profile cards dynamically
function renderStudents(studentData) {
    studentList.replaceChildren();

    if (studentData.length === 0) {
        const message = document.createElement("p");
        message.className = "student-empty";
        message.textContent =
            "No students found. Try another name, email, phone, or enrollment number.";

        studentList.appendChild(message);
        return;
    }

    studentData.forEach(student => {
        const card = document.createElement("article");
        card.className = "student-card";

        const photo = document.createElement("img");
        photo.className = "student-card-photo";
        photo.src = student.photo || "../images/logo.png";
        photo.alt = `Photo of ${student.name}`;
        photo.loading = "lazy";

        // Fallback if the photo cannot be loaded
        photo.addEventListener("error", () => {
            photo.src = "../images/logo.png";
        }, { once: true });

        const name = document.createElement("h3");
        name.textContent = student.name || "Unknown Student";

        const enrollment = document.createElement("p");
        enrollment.textContent =
            `Enrollment Number: ${student.enrollment || "N/A"}`;

        const department = document.createElement("p");
        department.textContent =
            `Department: ${student.department || "N/A"}`;

        const semester = document.createElement("p");
        semester.textContent =
            `Semester: ${student.semester ?? "N/A"}`;

        const email = document.createElement("p");
        email.textContent =
            `Email: ${student.email || "N/A"}`;

        const phone = document.createElement("p");
        phone.textContent =
            `Phone: ${student.phone || "N/A"}`;

        card.append(
            photo,
            name,
            enrollment,
            department,
            semester,
            email,
            phone
        );

        studentList.appendChild(card);
    });
}


// Search
searchStudent.addEventListener("input", () => {
    currentPage = 1;
    displayStudents();
});

// Department filter
departmentFilter.addEventListener("change", () => {
    currentPage = 1;
    displayStudents();
});

// Sorting
studentSort.addEventListener("change", () => {
    currentPage = 1;
    displayStudents();
});

// Previous page
prevPage.addEventListener("click", () => {
    if (currentPage > 1) {
        currentPage--;
        displayStudents();
    }
});

// Next page
nextPage.addEventListener("click", () => {
    currentPage++;
    displayStudents();
});
