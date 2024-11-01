(function ($, Drupal) {
  'use strict';

  Drupal.tpfOrganizationChart = Drupal.tpfOrganizationChart || {};
  
  Drupal.tpfOrganizationChart.utils = {
    isDescendant: function (item, rootId, organigramData) {
      if (item.id === rootId) return true;
      if (item.parent === rootId) return true;
      const parent = organigramData.find(d => d.id === item.parent);
      return parent ? this.isDescendant(parent, rootId, organigramData) : false;
    }
  };

}(jQuery, Drupal));
