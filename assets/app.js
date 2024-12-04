import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

document.addEventListener('DOMContentLoaded', function() {
    // Scroll button smooth behavior
    document.querySelector('.scroll-button').addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelector('#tricks-section').scrollIntoView({ 
            behavior: 'smooth' 
        });
    });

    // Back to top button visibility
    const backToTop = document.getElementById('backToTop');
    window.addEventListener('scroll', function() {
        if (window.scrollY > 500) {
            backToTop.classList.add('visible');
        } else {
            backToTop.classList.remove('visible');
        }
    });

    // Back to top smooth scroll
    backToTop.addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({ 
            top: 0, 
            behavior: 'smooth' 
        });
    });

    // Load more functionality
    // Note: This is a placeholder. You'll need to implement the actual loading logic
    const loadMore = document.getElementById('loadMore');
    loadMore.addEventListener('click', function() {
        // Add your load more logic here
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const mediaUpload = document.getElementById('figure_media');
    const mediaPreview = document.getElementById('media-preview');

    if (mediaUpload) {
        mediaUpload.addEventListener('change', function(event) {
            mediaPreview.innerHTML = ''; // Clear previous previews
            Array.from(event.target.files).forEach(file => {
                const mediaItem = document.createElement('div');
                mediaItem.classList.add('media-item');

                // Prévisualisation de l'image
                if (file.type.startsWith('image/')) {
                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(file);
                    img.alt = 'Aperçu de l\'image';
                    img.onload = () => URL.revokeObjectURL(img.src); // Free memory
                    mediaItem.appendChild(img);
                } else if (file.type.startsWith('video/')) {
                    const video = document.createElement('video');
                    video.controls = true;
                    const source = document.createElement('source');
                    source.src = URL.createObjectURL(file);
                    source.type = file.type;
                    video.appendChild(source);
                    video.onload = () => URL.revokeObjectURL(source.src); // Free memory
                    mediaItem.appendChild(video);
                }

                // Bouton de suppression de l'image
                const removeButton = document.createElement('button');
                removeButton.innerHTML = '❌';
                removeButton.classList.add('remove-button');
                removeButton.addEventListener('click', () => {
                    mediaItem.remove();
                });

                mediaItem.appendChild(removeButton);
                mediaPreview.appendChild(mediaItem);
            });
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const deletedMediaIds = new Set();
    const form = document.getElementById('figure-form');
    const deletedMediaInput = document.getElementById('deleted-media');
    const existingMediaContainer = document.getElementById('existing-media');
    const newMediaPreview = document.getElementById('new-media-preview');
    const newMediaUpload = document.getElementById('new-media-upload');
    const uploadZone = document.getElementById('upload-zone');

    // Gestion de la suppression des médias existants
    existingMediaContainer.addEventListener('click', function(e) {
        if (e.target.closest('.delete')) {
            const mediaItem = e.target.closest('.media-item');
            const mediaId = mediaItem.dataset.mediaId;
            
            // Animation de suppression
            mediaItem.style.opacity = '0';
            setTimeout(() => {
                mediaItem.remove();
            }, 300);

            // Ajouter l'ID à la liste des médias à supprimer
            deletedMediaIds.add(mediaId);
            deletedMediaInput.value = Array.from(deletedMediaIds).join(',');
        }
    });

    // Gestion du remplacement des médias
    existingMediaContainer.addEventListener('change', function(e) {
        if (e.target.classList.contains('media-replace')) {
            const file = e.target.files[0];
            if (file) {
                const mediaItem = e.target.closest('.media-item');
                const preview = mediaItem.querySelector('img, video');
                
                const reader = new FileReader();
                reader.onload = function(e) {
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
            }
        }
    });

    // Gestion du drag & drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        uploadZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        uploadZone.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
        uploadZone.classList.add('drag-hover');
    }

    function unhighlight(e) {
        uploadZone.classList.remove('drag-hover');
    }

    uploadZone.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    }

    // Gestion de l'ajout de nouveaux médias
    newMediaUpload.addEventListener('change', function(e) {
        handleFiles(this.files);
    });

    function handleFiles(files) {
        [...files].forEach(file => {
            if (file.type.startsWith('image/') || file.type.startsWith('video/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const mediaItem = document.createElement('div');
                    mediaItem.className = 'media-item new-media';
                    
                    if (file.type.startsWith('image/')) {
                        mediaItem.innerHTML = `
                            <img src="${e.target.result}" alt="New media">
                            <div class="media-actions">
                                <button class="action-button delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        `;
                    } else {
                        mediaItem.innerHTML = `
                            <video controls>
                                <source src="${e.target.result}" type="${file.type}">
                            </video>
                            <div class="media-actions">
                                <button class="action-button delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        `;
                    }
                    
                    newMediaPreview.appendChild(mediaItem);
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Gestion de la suppression des nouveaux médias
    newMediaPreview.addEventListener('click', function(e) {
        if (e.target.closest('.delete')) {
            const mediaItem = e.target.closest('.media-item');
            mediaItem.style.opacity = '0';
            setTimeout(() => {
                mediaItem.remove();
            }, 300);
        }
    });
});

// Pagination
document.getElementById("load-more").addEventListener("click", function() {
    let button = this;
    let page = button.getAttribute("data-page");

    fetch(`?page=${page}`, { headers: { "X-Requested-With": "XMLHttpRequest" }})
        .then(response => response.text())
        .then(html => {
            let tempDiv = document.createElement("div");
            tempDiv.innerHTML = html;
            let newFigures = tempDiv.querySelector("#figure-list").innerHTML;

            document.getElementById("figure-list").insertAdjacentHTML("beforeend", newFigures);
            button.setAttribute("data-page", parseInt(page) + 1);

            if (!tempDiv.querySelector("#load-more")) {
                button.style.display = "none";
            }
        })
        .catch(error => console.log("Erreur lors du chargement des figures:", error));
});

