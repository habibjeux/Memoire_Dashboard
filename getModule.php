<?php
    session_start();
    
    $con = new PDO("mysql:hostname=localhost;dbname=suivicours", "root", "");
    $reqDer = "SELECT MONTH(dateCours) AS mois, SUM(nbHeure) AS nbHeureTotal FROM cours WHERE idModule = ? AND idClasse = ? AND annee = ? AND estFait = 1 GROUP BY mois";
    $stmt = $con->prepare($reqDer);
    $stmt->execute(array($_POST['module'], $_SESSION['classe'], $_SESSION['annee']));

    $moisT = [];
    while($row = $stmt->fetch()) {
    $moisT [] = $row['mois'];
    $nbHeureTotal [] = $row['nbHeureTotal'];
    }
    $months = array(
    1 => 'Janvier',
    2 => 'Février',
    3 => 'Mars',
    4 => 'Avril',
    5 => 'Mai',
    6 => 'Juin',
    7 => 'Juillet',
    8 => 'Août',
    9 => 'Septembre',
    10 => 'Octobre',
    11 => 'Novembre',
    12 => 'Décembre'
    );
    $result = array_intersect_key($months, array_flip($moisT));
    $month = array_values($result);

?>
<script>
      nbHeureTotal = <?php echo json_encode($nbHeureTotal) ?>;
      month = <?php echo json_encode($month) ?>;

      var options = {
          colors : ['#26e7a6'],
          series: [{
          name: 'Heure Fait',
          data: nbHeureTotal
        }],
          chart: {
          height: 350,
          type: 'area'
        },
        dataLabels: {
          enabled: false
        },
        stroke: {
          curve: 'smooth'
        },
        xaxis: {
          categories: month
        },
        tooltip: {
          x: {
            format: 'dd/MM/yy'
          },
        },
        title: {
          text: 'Courbe d\'évolution des cours en fonction des mois',
          align: 'left',
          style: {
            color:  '#435ebe'
          },
        },
      };
    
      var chart = new ApexCharts(document.querySelector("#chart2"), options);
      chart.render();
    </script>