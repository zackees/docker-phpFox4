<h1>This demo using Google Charts</h1>
<p>
    Reference link: <a href="https://developers.google.com/chart/" target="_blank">Click here</a>
</p>
<p>
    <strong>Support: </strong>Cross-browser compatibility and cross-platform portability to iPhones, iPads, and Android. It also includes VML for supporting older IE versions.
</p>
<div class="mb-2">
    <h3>Note: </h3>
    <ul>
        <li>
            <i class="ico ico-circle"></i> Based on Google's TOS, we must have access to https://www.gstatic.com/charts/ in order to use the interactive features of Google Charts. We cannot use the chart offline. <a href="https://developers.google.com/chart/interactive/faq#offline" target="_blank">Link ref</a>
        </li>
        <li>
            <i class="ico ico-circle"></i> There is a limit of charts on 1 page. In this demo, we display 53 charts, it's work!. <a href="https://www.en.advertisercommunity.com/t5/Data-Studio/Number-of-charts-would-exceed-the-allowed-limit-in-this-page/td-p/1122737" target="_blank">Link ref</a>
        </li>
    </ul>
</div>

<h3>Pie chart</h3>
<div id="chart_div"></div>

<h3>Column chart</h3>
<div id="chart_div1"></div>

<h3>Line chart</h3>
<div id="chart_div2"></div>

<h3>Bar chart</h3>
<div id="chart_div_1"></div>

<h3>Area chart</h3>
><div id="chart_div_2"></div>

<h3>GEO chart</h3>
<div id="chart_div_3"></div>

<h1>The charts below for test limit (the same data)</h1>

<h3>Line chart 1</h3><div id="chart_div_4"></div>
<h3>Line chart 2</h3><div id="chart_div_5"></div>
<h3>Line chart 3</h3><div id="chart_div_6"></div>
<h3>Line chart 4</h3><div id="chart_div_7"></div>
<h3>Line chart 5</h3><div id="chart_div_8"></div>

{literal}
<script type="text/javascript">
  $Behavior.loadStatiticChart = function() {
    // Load the Visualization API and the corechart package.
    google.charts.load('current', {'packages':['corechart'], 'mapsApiKey': '{$mapsApiKey}'});

    // Set a callback to run when the Google Visualization API is loaded.
    google.charts.setOnLoadCallback(drawChart);
  }
  // Callback that creates and populates a data table,
  // instantiates the pie chart, passes in the data and
  // draws it.
  function drawChart() {
    // Create the data table.
    var data = new google.visualization.DataTable();
    data.addColumn('string', 'Action');
    data.addColumn('number', 'Total');
    data.addRows([
      ['Like', 15],
      ['Comment', 22],
      ['Share', 17],
      ['View', 29],
      ['Others', 20]
    ]);

    // Pie chart
    // Set chart options
    var options = {'title':'Statitic of a blog: Blog ABC',
      'width':'auto',
      'height':'300'};

    // Instantiate and draw our chart, passing in some options.
    var chart = new google.visualization.PieChart(document.getElementById('chart_div'));
    chart.draw(data, options);

    // Column chart
    var options1 = {'title':'Statitic of a blog: Blog ABC',
      'width':'auto',
      'height':'500'};
    var columnsVisualization = new google.visualization.ColumnChart(document.getElementById('chart_div1'));
    columnsVisualization.draw(data, options1);

    // Add our over/out handlers.
    google.visualization.events.addListener(columnsVisualization, 'onmouseover', barMouseOver);
    google.visualization.events.addListener(columnsVisualization, 'onmouseout', barMouseOut);

    function barMouseOver(e) {
      columnsVisualization.setSelection([e]);
    }
    function barMouseOut(e) {
      columnsVisualization.setSelection([{'row': null, 'column': null}]);
    }

    // Line chart
    chart = new google.visualization.LineChart(document.getElementById('chart_div2'));
    chart.draw(data, options);

    // Bar chart
    var data_bar = google.visualization.arrayToDataTable([
      ['City', '2010 Population', '2000 Population'],
      ['New York City, NY', 8175000, 8008000],
      ['Los Angeles, CA', 3792000, 3694000],
      ['Chicago, IL', 2695000, 2896000],
      ['Houston, TX', 2099000, 1953000],
      ['Philadelphia, PA', 1526000, 1517000]
    ]);

    var materialOptions = {
      height: 400,
      chart: {
        title: 'Population of Largest U.S. Cities',
        subtitle: 'Based on most recent and previous census data'
      },
      hAxis: {
        title: 'Total Population'
      },
      vAxis: {
        title: 'City'
      },
      bars: 'horizontal',
      series: {
        0: {axis: '2010'},
        1: {axis: '2000'}
      },
      axes: {
        x: {
          2010: {label: '2010 Population (in millions)', side: 'top'},
          2000: {label: '2000 Population'}
        }
      }
    };
    var materialChart = new google.visualization.BarChart(document.getElementById('chart_div_1'));
    materialChart.draw(data_bar, materialOptions);

    // Area chart
    var data_area = google.visualization.arrayToDataTable([
      ['Year', 'Sales', 'Expenses'],
      ['2013',  1000,      400],
      ['2014',  1170,      460],
      ['2015',  660,       1120],
      ['2016',  1030,      540]
    ]);

    var options_area = {
      title: 'Company Performance',
      hAxis: {title: 'Year',  titleTextStyle: {color: '#333'}},
      vAxis: {minValue: 0},
      height:'300'
    };
    chart = new google.visualization.AreaChart(document.getElementById('chart_div_2'));
    chart.draw(data_area, options_area);

    // Geo chart
    var data_geo = google.visualization.arrayToDataTable([
      ['Country', 'Popularity'],
      ['Germany', 200],
      ['United States', 300],
      ['Brazil', 400],
      ['Canada', 500],
      ['France', 600],
      ['RU', 700]
    ]);
    var options_geo = {};
    chart = new google.visualization.GeoChart(document.getElementById('chart_div_3'));
    chart.draw(data_geo, options_geo);

    // start demo multi line chart
    chart = new google.visualization.LineChart(document.getElementById('chart_div_4'));
    chart.draw(data, options);
    chart = new google.visualization.LineChart(document.getElementById('chart_div_5'));
    chart.draw(data, options);
    chart = new google.visualization.LineChart(document.getElementById('chart_div_6'));
    chart.draw(data, options);
    chart = new google.visualization.LineChart(document.getElementById('chart_div_7'));
    chart.draw(data, options);
    chart = new google.visualization.LineChart(document.getElementById('chart_div_8'));
    chart.draw(data, options);
  }
</script>
{/literal}