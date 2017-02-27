jQuery(document).ready(function() {
  // Stateful record display
  try {
    if (
      Drupal.settings.edan_search.mini_fields &&
      Drupal.settings.edan_search.mini_fields.length > 0 &&
      Drupal.settings.edan_search.mini_fields[0] != ''
    ) {
      // Set state & add button
      jQuery('li.edan-search-result')
        .addClass('mini')
        .prepend('<span class="edan-search-mini-toggle button">Expand</span>');

      // Add mini modifier to DLs
      jQuery.each(Drupal.settings.edan_search.mini_fields, function(key, fld) {
        jQuery('dl.edan-search-' + fld).addClass('mini');
      });

      // Add button event
      jQuery('span.edan-search-mini-toggle').click(function(ev) {
        ev.preventDefault();
        var me = jQuery(this);
        var txt = me.text();
        if (txt == 'Expand') {
          me.text('Collapse');
          me.parents('li.edan-search-result').addClass('active');
        } else {
          me.text('Expand');
          me.parents('li.edan-search-result').removeClass('active');
        }
      });
    }
  } catch (err) {
  }

  // Facet hiding
  jQuery('ul.facets').hide();
  jQuery('a.category').click(function(ev) {
    ev.preventDefault();
    var tar = jQuery(this).attr('href').split('#')[1];
    jQuery('ul.facets:not(#facet-' + tar + ')').hide();
    jQuery('#facet-' + tar).toggle();
  });
});