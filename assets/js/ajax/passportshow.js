
// Submit
document.getElementById("passportInput").addEventListener("change", function (e) {

    const file = e.target.files[0];
    const preview = document.getElementById("passportPreview");

    if (!file) return;

    // Validate file type
    if (!file.type.startsWith("image/")) {
        alert("Please select a valid image file");
        this.value = "";
        return;
    }

    // Preview image
    const reader = new FileReader();

    reader.onload = function (e) {
        preview.src = e.target.result;
    };

    reader.readAsDataURL(file);
});