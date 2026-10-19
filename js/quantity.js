/* Boutons − et + autour du champ quantité (fiche produit) */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.woocommerce div.product form.cart .quantity').forEach(function (wrapper) {
    var input = wrapper.querySelector('input.qty');
    if (!input || wrapper.querySelector('.gb-quantity__button')) { return; }

    function creer(signe, libelle, pas) {
      var bouton = document.createElement('button');
      bouton.type = 'button';
      bouton.className = 'gb-quantity__button gb-quantity__button--' + signe;
      bouton.setAttribute('aria-label', libelle);
      bouton.textContent = signe === 'minus' ? '−' : '+';
      bouton.addEventListener('click', function () {
        var min = parseFloat(input.min) || 0;
        var max = parseFloat(input.max) || Infinity;
        var valeur = (parseFloat(input.value) || 0) + pas;
        input.value = Math.min(max, Math.max(min, valeur));
        input.dispatchEvent(new Event('change', { bubbles: true }));
      });
      return bouton;
    }

    wrapper.insertBefore(creer('minus', 'Diminuer la quantité', -1), input);
    wrapper.appendChild(creer('plus', 'Augmenter la quantité', 1));
  });
});
