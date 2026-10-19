/* Fiche produit variable : le prix affiché en haut suit la variante choisie (comme sur la maquette) */
jQuery(function ($) {
  var $formulaire = $('form.variations_form');
  var $prix = $('.single-product div.product .summary > .price');
  if (!$formulaire.length || !$prix.length) { return; }

  var prixInitial = $prix.html();
  // Le prix de variante natif de WooCommerce (sous le sélecteur) devient inutile : il est masqué par le CSS.
  $prix.closest('.summary').addClass('gb-price-synced');

  $formulaire.on('found_variation', function (evenement, variante) {
    if (variante && variante.price_html) {
      $prix.html($(variante.price_html).html());
    } else {
      $prix.html(prixInitial);
    }
  });

  $formulaire.on('hide_variation reset_data', function () {
    $prix.html(prixInitial);
  });
});
