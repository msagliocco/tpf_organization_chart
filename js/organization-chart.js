(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.tpfOrganizationChart = {
    attach: function (context, settings) {
      // Get current path
      const currentPath = window.location.pathname;

      // Handle table view collapse buttons for both routes
      if (currentPath === '/organization/table' || settings.tpfOrganizationChart?.chartType === 'table') {
        once('collapse-button', '[data-bs-toggle="collapse"]', context).forEach(button => {
          button.addEventListener('click', function() {
            const icon = this.querySelector('i');
            if (icon) {
              icon.classList.toggle('bi-chevron-down');
              icon.classList.toggle('bi-chevron-up');
            }
          });
        });

        // If this is the table view route, we can return early
        if (currentPath === '/organization/table') {
          return;
        }
      }

      // Handle chart views (treegraph and organization)
      once('custom-chart', '#treegraph-container', context).forEach(function (element) {
        const organigramData = settings.tpfOrganizationChart.organigramData;
        let chart;
  
        function updateChart(rootId) {
          const rootNode = organigramData.find(item => item.id === rootId);
          if (!rootNode) return;
  
          const filteredData = organigramData.filter(function(item) {
            return item.id === rootId || Drupal.tpfOrganizationChart.utils.isDescendant(item, rootId, organigramData);
          });
  
          const processedData = filteredData.map(item => ({
            id: item.id,
            name: item.name,
            parent: item.id !== rootId ? item.parent : '',
            url: `/taxonomy/term/${item.id}`,
            logo: item.logo
          }));
  
          if (chart) {
            chart.destroy();
          }
  
          const baseConfig = {
            title: {
              text: Drupal.t('Organigram Chart')
            },
            tooltip: {
              pointFormat: '{point.name}'
            }
          };
  
          const chartConfig = settings.tpfOrganizationChart?.chartType === 'treegraph' 
            ? { ...baseConfig, ...Drupal.tpfOrganizationChart.config.getTreegraphConfig() }
            : { ...baseConfig, ...Drupal.tpfOrganizationChart.config.getOrganizationConfig() };
  
          if (settings.tpfOrganizationChart?.chartType === 'organization') {
            const connections = processedData
              .filter(item => item.id !== rootId)
              .map(item => [item.parent, item.id]);
  
            if (!processedData.some(item => item.parent === rootId)) {
              connections.push([rootId, rootId]);
            }
  
            chartConfig.series[0].data = connections;
            chartConfig.series[0].nodes = processedData.map(item => ({
              id: item.id,
              name: item.name,
              url: item.url,
              image: item.logo || null,
              color: 'white',
              events: {
                click: function() {
                  window.location.href = item.url;
                }
              }
            }));
          } else {
            chartConfig.series[0].data = processedData;
          }
  
          chart = Highcharts.chart('treegraph-container', chartConfig);
        }
  
        $('#root-selector').on('change', function() {
          updateChart(this.value);
        });
  
        updateChart($('#root-selector').val());
      });
    }
  };

})(jQuery, Drupal, once); 