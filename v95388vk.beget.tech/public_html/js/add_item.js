document.getElementById('fileInput').addEventListener('change', function() {
    var file    = this.files[0];
    var preview = document.getElementById('imagePreview');

    if (!file) {
        preview.style.display = 'none';
        preview.src = '';
        return;
    }

    if (!file.type.startsWith('image/')) {
        alert('Можно выбрать только изображение');
        this.value = '';
        preview.style.display = 'none';
        preview.src = '';
        return;
    }

    var reader = new FileReader();
    reader.onload = function(e) {
        preview.src = e.target.result;
        preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
});
