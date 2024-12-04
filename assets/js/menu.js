document.addEventListener('DOMContentLoaded', function () {
    // Sélectionne le bouton de basculement du menu et la navigation principale
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const mainNav = document.querySelector('.main-nav');

    /**
     * Gestion de l'affichage/masquage du menu mobile.
     * Ajoute ou retire la classe "active" à la navigation principale lorsque le bouton est cliqué.
     */
    menuToggle.addEventListener('click', function () {
        mainNav.classList.toggle('active');
    });
});
