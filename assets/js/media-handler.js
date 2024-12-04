document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('figure-form');
    const deletedMediaIds = new Set();
    const deletedMediaInput = document.getElementById('deleted-media');
    const existingMediaContainer = document.getElementById('existing-media');
    const newMediaPreview = document.getElementById('new-media-preview');
    const newMediaUpload = document.getElementById('new-media-upload');

    /**
     * Gestion de la suppression des médias existants.
     * Ajoute l'ID du média supprimé à un input caché pour le traitement backend.
     */
    existingMediaContainer.addEventListener('click', function (e) {
        const deleteButton = e.target.closest('.delete');
        if (deleteButton) {
            const mediaItem = deleteButton.closest('.media-item');
            const mediaId = deleteButton.dataset.mediaId;

            // Animation de suppression
            mediaItem.style.opacity = '0';
            setTimeout(() => {
                mediaItem.remove();
            }, 300);

            // Ajout de l'ID du média à la liste des médias supprimés
            deletedMediaIds.add(mediaId);
            deletedMediaInput.value = Array.from(deletedMediaIds).join(',');
        }
    });

    /**
     * Gestion du remplacement des médias existants.
     * Met à jour l'interface et les inputs pour inclure les fichiers remplacés.
     */
    existingMediaContainer.addEventListener('change', function (e) {
        if (e.target.classList.contains('media-replace')) {
            const file = e.target.files[0];
            if (file) {
                const mediaItem = e.target.closest('.media-item');
                const mediaId = mediaItem.dataset.mediaId;

                // Crée une prévisualisation du nouveau média
                const reader = new FileReader();
                reader.onload = function (e) {
                    const preview = mediaItem.querySelector('img, video');
                    if (file.type.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        preview.replaceWith(img);
                    } else if (file.type.startsWith('video/')) {
                        const video = document.createElement('video');
                        video.controls = true;
                        const source = document.createElement('source');
                        source.src = e.target.result;
                        source.type = file.type;
                        video.appendChild(source);
                        preview.replaceWith(video);
                    }
                };
                reader.readAsDataURL(file);

                // Met à jour l'input pour la soumission du fichier
                const newInput = document.createElement('input');
                newInput.type = 'file';
                newInput.name = `media_replacements[${mediaId}]`;
                newInput.style.display = 'none';

                // Remplace l'ancien input s'il existe
                const existingInput = form.querySelector(`input[name="media_replacements[${mediaId}]"]`);
                if (existingInput) {
                    existingInput.remove();
                }

                // Ajoute le fichier au formulaire
                form.appendChild(newInput);
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                newInput.files = dataTransfer.files;
            }
        }
    });

    /**
     * Gestion de la suppression des nouveaux médias ajoutés.
     * Supprime l'élément de prévisualisation correspondant.
     */
    newMediaPreview.addEventListener('click', function (e) {
        if (e.target.closest('.delete')) {
            const mediaItem = e.target.closest('.media-item');
            mediaItem.style.opacity = '0';
            setTimeout(() => {
                mediaItem.remove();
            }, 300);
        }
    });
});

/**
 * Gestion de l'affichage/masquage des médias via un bouton (mode responsive).
 */
document.addEventListener('DOMContentLoaded', () => {
    const seeMediaBtn = document.getElementById('seeMediaBtn');
    const mediaList = document.getElementById('mediaList');

    // Ajoute l'événement uniquement si le bouton est visible
    if (window.getComputedStyle(seeMediaBtn).display !== 'none') {
        seeMediaBtn.addEventListener('click', () => {
            mediaList.classList.toggle('hidden');
            seeMediaBtn.textContent = mediaList.classList.contains('hidden') ? 'Afficher les Médias' : 'Masquer les Médias';
        });
    }
});
