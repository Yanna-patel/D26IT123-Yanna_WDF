
let faqData = [];

const faqContainer = document.getElementById("faqContainer");
const searchFAQ = document.getElementById("searchFAQ");
const faqSort = document.getElementById("faqSort");
const faqResults = document.getElementById("faqResults");

// Load FAQs from JSON
fetch("../data/faq.json")
    .then(response => {
        if (!response.ok) {
            throw new Error("Unable to load FAQ data.");
        }

        return response.json();
    })
    .then(data => {
        faqData = data;
        applyFAQFilters();
    })
    .catch(error => {
        console.error("Error loading FAQs:", error);
        faqContainer.textContent = "Unable to load FAQs.";
    });


// Search and sort FAQs
function applyFAQFilters() {
    const searchText = searchFAQ.value.toLowerCase().trim();
    const sortOrder = faqSort.value;

    let filteredFAQs = faqData.filter(faq => {
        return (
            (faq.question || "").toLowerCase().includes(searchText) ||
            (faq.answer || "").toLowerCase().includes(searchText) ||
            (faq.category || "").toLowerCase().includes(searchText)
        );
    });

    if (sortOrder === "az") {
        filteredFAQs.sort((a, b) =>
            (a.question || "").localeCompare(b.question || "")
        );
    } else if (sortOrder === "za") {
        filteredFAQs.sort((a, b) =>
            (b.question || "").localeCompare(a.question || "")
        );
    }

    // Display only five matching questions
    filteredFAQs = filteredFAQs.slice(0, 5);

    displayFAQs(filteredFAQs);
}


// Display questions with accordion answers
function displayFAQs(faqs) {
    faqContainer.replaceChildren();

    faqResults.textContent =
        `Showing ${faqs.length} of ${faqData.filter(faq => {
            const searchText = searchFAQ.value.toLowerCase().trim();

            return (
                (faq.question || "").toLowerCase().includes(searchText) ||
                (faq.answer || "").toLowerCase().includes(searchText) ||
                (faq.category || "").toLowerCase().includes(searchText)
            );
        }).length} matching FAQs`;

    if (faqs.length === 0) {
        const message = document.createElement("p");
        message.className = "faq-empty";
        message.textContent = "No FAQs found.";
        faqContainer.appendChild(message);
        return;
    }

    faqs.forEach(faq => {
        const item = document.createElement("div");
        item.className = "faq-item";

        const question = document.createElement("button");
        question.type = "button";
        question.className = "faq-question";
        question.textContent = faq.question;
        question.setAttribute("aria-expanded", "false");

        const answer = document.createElement("div");
        answer.className = "faq-answer";
        answer.style.display = "none";

        const category = document.createElement("span");
        category.className = "faq-category";
        category.textContent = faq.category || "General";

        const answerText = document.createElement("p");
        answerText.textContent = faq.answer || "No answer available.";

        answer.append(category, answerText);

        question.addEventListener("click", () => {
            const wasOpen =
                question.getAttribute("aria-expanded") === "true";

            // Close every currently open answer
            faqContainer.querySelectorAll(".faq-question").forEach(btn => {
                btn.setAttribute("aria-expanded", "false");
            });

            faqContainer.querySelectorAll(".faq-answer").forEach(panel => {
                panel.style.display = "none";
            });

            // Open this answer only if it was previously closed
            if (!wasOpen) {
                question.setAttribute("aria-expanded", "true");
                answer.style.display = "block";
            }
        });

        item.append(question, answer);
        faqContainer.appendChild(item);
    });
}


// Search FAQs
searchFAQ.addEventListener("input", applyFAQFilters);

// Sort FAQs
faqSort.addEventListener("change", applyFAQFilters);
