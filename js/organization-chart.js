(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.tpfOrganizationChartDisplay = {
    attach: function (context, settings) {
      
        const chartType = settings.tpfOrganizationChart?.chartType;

        // Handle table view
        if (chartType === 'table') {
          once('collapse-button', '[data-bs-toggle="collapse"]', context).forEach(button => {
            button.addEventListener('click', function() {
              const icon = this.querySelector('i');
              icon.classList.toggle('bi-chevron-down');
              icon.classList.toggle('bi-chevron-up');
            });
          });
          return;
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
  
            const chartConfig = chartType === 'treegraph' 
              ? { ...baseConfig, ...Drupal.tpfOrganizationChart.config.getTreegraphConfig() }
              : { ...baseConfig, ...Drupal.tpfOrganizationChart.config.getOrganizationConfig() };
  
            if (chartType === 'organization') {
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

})(jQuery, Drupal); 