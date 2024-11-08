(function ($, Drupal) {
  'use strict';

  Drupal.tpfOrganizationChart = Drupal.tpfOrganizationChart || {};
  
  Drupal.tpfOrganizationChart.config = {
    getTreegraphConfig: function() {
      return {
        chart: {
          inverted: false,
          height: 800
        },
        series: [{
          type: 'treegraph',
          link: {
            dataLabels: {
              enabled: false
            }
          },
          dataLabels: {
            style: {
              width: '120px',
              color: 'black',
              textAlign: 'center'
            },
            align: 'center',
            verticalAlign: 'middle'
          },
          nodePadding: 10
        }]
      };
    },

    getOrganizationConfig: function() {
      return {
        chart: {
          height: 800,
          inverted: true
        },
        title: {
          text: Drupal.t('Organization Chart')
        },
        tooltip: {
          outside: true
        },
        plotOptions: {
          organization: {
            borderColor: '#d3d3d3',
            borderWidth: 1,
            borderRadius: 5,
            color: 'white',
            colorByPoint: false
          }
        },
        series: [{
          type: 'organization',
          name: Drupal.t('Organization Chart'),
          keys: ['from', 'to'],
          data: [],
          nodes: [],
          dataLabels: {
            color: 'black',
            crop: false,
            textAlign: 'center',
            align: 'center',
            verticalAlign: 'middle',
            useHTML: true,
            nodeFormatter: function() {
              let html = '<div style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; padding: 5px;">';
              
              if (this.point.options.image) {
                html += '<img src="' + this.point.options.image + '" style="width: 30px; height: 30px; margin-right: 10px; object-fit: contain;">';
              }
              
              html += '<a href="' + this.point.options.url + '" class="org-chart-link" style="color: #333; text-decoration: none; font-weight: bold;">' + this.point.name + '</a>';
              html += '</div>';
              
              return html;
            },
            style: {
              whiteSpace: 'normal',
              overflow: 'visible',
              textOverflow: 'ellipsis',
              height: '120px'
            }
          },
          nodeHeight: 150,
        }]
      };
    }
  };

})(jQuery, Drupal);
