<!DOCTYPE html>
<html>
    <head>
        <title>___TITLE___</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1" />
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    </head>
    <body>
        <div class="container" style="width:90%">
            <div class="row">
                <div class="col-xs-12">
                    <canvas id="chart"></canvas>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12">
                    <center>
                        <a id="benzin" class="btn btn-default" href="javascript:toggleLegend('benzin')">Skrýt benzin</a>
                        <a id="nafta" class="btn btn-default" href="javascript:toggleLegend('nafta')">Skrýt naftu</a>
                        <a class="btn btn-default" href="javascript:reset()">Reset</a>
                    </center>
                </div>
            </div>
        </div>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.bundle.min.js"></script>
        <script src="https://hammerjs.github.io/dist/hammer.min.js"></script>
        <script src="https://cdn.rawgit.com/chartjs/chartjs-plugin-zoom/master/chartjs-plugin-zoom.min.js"></script>
        <script>
            var options = {
              type: 'bubble',
              options: {
                scales: {
                  yAxes: [{
                    scaleLabel: {
                      display: true,
                      labelString: 'Najeto (km)'
                    }
                  }],
                  xAxes: [{
                    scaleLabel: {
                      display: true,
                      labelString: 'Cena (kč)'
                    }
                  }]
                },
                tooltips: {
                  callbacks: {
                    label: function(t, d) {
                      return d.datasets[t.datasetIndex].data[t.index].title;
                    },
                    labelColor: function(t, d) {
                      var c = d.data.datasets[t.datasetIndex].data[t.index].color;
                      if (c !== null) {
                        return {
                          backgroundColor: 'rgb(' + c.r + ', ' + c.g +', ' + c.b + ')'
                        };
                      } else {
                        return false;
                      }
                    }
                  }
                },
                legend: {
                  labels: {
                    usePointStyle: true
                  }
                },
                pan: {
                  enabled: true,
                  mode: 'xy',
                  rangeMin: {
                    x: 0,
                    y: 0 
                  }
                },
                zoom: {
                  enabled: true,
                  drag: false,
                  mode: 'xy',
                  rangeMin: {
                    x: 0,
                    y: 0 
                  }
                },
                onClick: function(e) {
                  var element = this.getElementAtEvent(e);
                  if (element.length > 0) {
                    var dataset = this.config.data.datasets[element[0]._datasetIndex];
                    var data = this.config.data.datasets[element[0]._datasetIndex].data[element[0]._index];
                    window.open(data.url, '_blank');
                  }
                }
              },
              data: {
                datasets: ___DATA___
              }
            };

            var ctx = document.getElementById('chart').getContext('2d');
            var chart = new Chart(ctx, options);

            function toggleLegend(content) {
                chart.data.datasets.forEach(function (dataset) {
                    if (dataset.label.includes(content)) {
                        if (dataset._meta[0].hidden) {
                            dataset._meta[0].hidden = false;
                        } else {
                            dataset._meta[0].hidden = true;
                        }
                    }
                });
                $('a#' + content).toggleClass('active');
                chart.update();
            }

            function reset() {
                chart.resetZoom();
                $('a#benzin').removeClass('active');
                $('a#nafta').removeClass('active');
            }
        </script>
    </body>
</html>
