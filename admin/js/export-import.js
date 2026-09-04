(function () {
    'use strict';

    var dropzone     = document.getElementById('wpa9-import-dropzone');
    var fileInput    = document.getElementById('wpa9-import-file');
    var selectedInfo = document.getElementById('wpa9-file-selected');
    var filenameSpan = document.getElementById('wpa9-filename');
    var previewBtn   = document.getElementById('wpa9-preview-btn');

    if ( ! dropzone || ! fileInput ) {
        return;
    }

    dropzone.addEventListener('click', function () {
        fileInput.click();
    });

    dropzone.addEventListener('dragover', function (e) {
        e.preventDefault();
        dropzone.classList.add('is-dragover');
    });

    dropzone.addEventListener('dragleave', function () {
        dropzone.classList.remove('is-dragover');
    });

    dropzone.addEventListener('drop', function (e) {
        e.preventDefault();
        dropzone.classList.remove('is-dragover');
        fileInput.files = e.dataTransfer.files;
        updateFileSelected();
    });

    fileInput.addEventListener('change', updateFileSelected);

    function updateFileSelected() {
        if ( fileInput.files.length > 0 ) {
            filenameSpan.textContent = fileInput.files[0].name;
            selectedInfo.hidden = false;
            previewBtn.disabled = false;
        } else {
            selectedInfo.hidden = true;
            previewBtn.disabled = true;
        }
    }
})();
