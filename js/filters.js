/* Boutique sur mobile : les filtres se déplient depuis un bouton « Filtres » placé avant la grille. */
(function () {
	var zone = document.getElementById('secondary');
	if (!zone) {
		return;
	}
	document.documentElement.classList.add('gb-js');

	var bouton = document.createElement('button');
	bouton.type = 'button';
	bouton.className = 'gb-filtres-bouton';
	bouton.setAttribute('aria-controls', 'secondary');
	bouton.innerHTML = '<span>Filtres</span><span class="gb-filtres-signe" aria-hidden="true">+</span>';
	var signe = bouton.querySelector('.gb-filtres-signe');
	zone.parentNode.insertBefore(bouton, zone);

	function ouvrir(ouvert) {
		zone.classList.toggle('is-open', ouvert);
		bouton.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
		signe.textContent = ouvert ? '−' : '+';
	}

	// Filtres déjà appliqués : le panneau s'ouvre pour les montrer.
	ouvrir(/[?&](filter_|min_price|max_price)/.test(window.location.search));

	bouton.addEventListener('click', function () {
		ouvrir(!zone.classList.contains('is-open'));
	});
})();
