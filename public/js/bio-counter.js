document.addEventListener("DOMContentLoaded", function () {
    const bioTextarea = document.querySelector("#user_profile_bio");
    const counterElement = document.querySelector("#bio-counter");

    if (bioTextarea && counterElement) {
        updateCounter();

        bioTextarea.addEventListener("input", updateCounter);

        function updateCounter() {
            const length = bioTextarea.value.length;
            counterElement.textContent = length + "/500 caractères";

            if (length > 450) {
                counterElement.classList.add("text-red-600");
                counterElement.classList.remove("text-gray-500");
            } else {
                counterElement.classList.add("text-gray-500");
                counterElement.classList.remove("text-red-600");
            }
        }
    }
});
