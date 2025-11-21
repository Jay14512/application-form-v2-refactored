document.getElementById('resume').addEventListener('change', function () {
    const fileNameSpan = document.getElementById('resume-file-name');
    const file = this.files[0];

    if (file) {
        fileNameSpan.textContent = file.name;
    } else {
        fileNameSpan.textContent = ".pdf";
    }
});