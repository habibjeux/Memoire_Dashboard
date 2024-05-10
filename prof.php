<?php
  session_start();
  if(isset($_SESSION['matricule']))
    $matricule = $_SESSION['matricule'];
  else
    header("Location: index.php");
  
  $prenom = $_SESSION['prenom'];
  $nom = $_SESSION['nom'];
  $con = new PDO("mysql:hostname=localhost;dbname=suivicours", "root", "");

  $stmt = $con->prepare("SELECT DISTINCT A.annee FROM annee A, enseigner E WHERE A.annee = E.annee AND matricule = ? ORDER BY E.annee ASC");
  // Je choisi ASC parce que l'année 2022-2023 n'a rien
  $stmt->execute(array($matricule));
  $annee = $stmt->fetch()['annee'];



  $stmt = $con->prepare("SELECT COUNT(*) AS nbUE FROM `ue-annee` WHERE annee = ?");
  $stmt->execute(array($annee));
  $nbUE = $stmt->fetch()[0];


  $stmt2 = $con->prepare("SELECT DISTINCT A.annee FROM annee A, enseigner E WHERE A.annee = E.annee AND matricule = ? ORDER BY E.annee DESC");
  $stmt2->execute(array($matricule));

  $stmt4 = $con->prepare("SELECT annee FROM annee ORDER BY annee DESC");
  $stmt4->execute();


  if(isset($_POST['submit'])) {
    $annee = $_POST['annee'];
  }

  $stmt5 = $con->prepare("SELECT * FROM classe");
  $_SESSION['annee'] = $annee;


  // Nombre Module Non débutés
  $stmt10 = $con->prepare("SELECT COUNT(DISTINCT E.idModule) AS cou FROM enseigner E WHERE E.annee = ? AND E.matricule = ? AND E.dateDebut IS NULL");
  $stmt10->execute(array($annee, $matricule));
  $nbModuleNotStated = $stmt10->fetch()['cou'];

  // Nombre Module Terminé
  $stmt11 = $con->prepare("SELECT COUNT(DISTINCT E.idModule) AS cou FROM enseigner E WHERE E.annee = ? AND E.matricule = ? AND E.dateFin IS NOT NULL");
  $stmt11->execute(array($annee, $matricule));
  $nbModuleTermanated = $stmt11->fetch()['cou'];

  // Nombre Module Terminé et examenFait
  $stmt12 = $con->prepare("SELECT COUNT(DISTINCT E.idModule) AS cou FROM enseigner E WHERE E.annee = ? AND E.matricule = ? AND E.dateFin IS NOT NULL AND E.examenFait = '1'");
  $stmt12->execute(array($annee, $matricule));
  $nbModuleTExamDone = $stmt12->fetch()['cou'];

  // Nombre Module Terminé et examenNonFait
  $stmt13 = $con->prepare("SELECT COUNT(DISTINCT E.idModule) AS cou FROM enseigner E WHERE E.annee = ? AND E.matricule = ? AND E.dateFin IS NOT NULL AND E.examenFait = '0'");
  $stmt13->execute(array($annee, $matricule));
  $nbModuleTExamDoneNo = $stmt13->fetch()['cou'];

  //Pourcentage total de modules enseignées
  $stmt11 = $con->prepare("SELECT COUNT(DISTINCT E.idModule) AS cou FROM enseigner E WHERE E.annee = ? AND E.matricule = ?");
  $stmt11->execute(array($annee, $matricule));
  $nbModuleAll = $stmt11->fetch()['cou'];
  $idModule = [];
  if($nbModuleAll == 0)
  $per[] = 0;
  else
    $per[] = round(($nbModuleTermanated * 100 ) / $nbModuleAll);

?>
<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard - Professeur</title>

    <link rel="stylesheet" href="assets/css/main/app.css" />
    <link rel="stylesheet" href="assets/css/main/app-dark.css" />
    <link
      rel="shortcut icon"
      href="assets/images/logo/favicon.svg"
      type="image/x-icon"
    />
    <link
      rel="shortcut icon"
      href="assets/images/logo/favicon.png"
      type="image/png"
    />

    <link rel="stylesheet" href="assets/css/shared/iconly.css" />
  </head>

  <body>
    <script src="assets/js/initTheme.js"></script>
    <div id="app">
      <?php include_once("sidebar-prof.php"); ?>
      <?php
        try {
          $req = "SELECT M.idModule, M.libelleModule, SUM(C.nbHeure) AS nbHeureFait, MA.nbHeureModule FROM enseigner E, cours C, module M, `module-annee` MA WHERE 
          E.matricule = C.matricule AND E.idModule = C.idModule AND E.idClasse = C.idClasse AND 
          E.annee = C.annee AND E.annee = ? AND C.estFait = '1' AND E.matricule = ? 
          AND M.idModule = E.idModule AND M.idModule = MA.idModule AND MA.annee = E.annee GROUP BY M.idModule";

          $stmt = $con->prepare($req);
          $stmt->execute(array($annee, $matricule));
          if($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch()) {
              $idModule[] = $row['idModule'];
              $module[] = $row['libelleModule'];
              $nbHFait[] = $row['nbHeureFait'];
              $nbH[] = $row['nbHeureModule'];
            }
          }
          else {
          ?>
          <script>
            document.getElementById('chartAll').innerHTML = "Pas de contenu pour cette selection";
          </script>
          <?php
          }
          
        } catch (PDOException $th) {
          echo $th->getMessage();
        }
      ?>
      <div id="main">
        <header class="mb-3">
          <a href="#" class="burger-btn d-block d-xl-none">
            <i class="bi bi-justify fs-3"></i>
          </a>
        </header>
        <section>
          <form class="row" action="" method="POST">
            <div class="col-3">
                <select id="annee" class="form-select" name="annee">
                  <option value="">ANNEE UNIVERSITAIRE</option>
                  <?php $stmt5->execute(); ?>
                  <?php while($row = $stmt2->fetch()) : ?>
                    <option value="<?php echo $row['annee']; ?>"><?php echo $row['annee']; ?></option>
                  <?php endwhile; ?>
                </select>
            </div>
            <button class="btn btn-primary col-2" type="submit" name="submit">Charger</button>
          </form>
        </section>
        <div class="page-heading mt-4">
          <h3><?php echo "Année : $annee"; ?></h3>
        </div>
        <div class="page-content">
          <section class="row">
            <div class="col-12 col-lg-12">
              <div class="row">
                <div class="col-6 col-lg-3 col-md-6">
                  <div class="card" data-bs-toggle="modal" data-bs-target="#moduleNotStarted">
                    <div class="card-body px-4 py-4-5">
                      <div class="row">
                        <div
                          class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start"
                        >
                          <div class="stats-icon red text-light mb-2 bi bi-bookmark-x-fill">
                          </div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                          <h6 class="text-muted font-semibold">
                            Modules non commencés
                          </h6>
                          <h6 class="font-extrabold mb-0"><?php echo $nbModuleNotStated; ?></h6>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-6 col-lg-3 col-md-6">
                  <div class="card" data-bs-toggle="modal" data-bs-target="#moduleTerminated">
                    <div class="card-body px-4 py-4-5">
                      <div class="row">
                        <div
                          class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start"
                        >
                          <div class="stats-icon blue mb-2 text-light bi bi-bookmark-check">
                          </div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                          <h6 class="text-muted font-semibold">Modules ayant Terminés</h6>
                          <h6 class="font-extrabold mb-0"><?php echo $nbModuleTermanated; ?></h6>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-6 col-lg-3 col-md-6">
                  <div class="card" data-bs-toggle="modal" data-bs-target="#moduleTExamDone">
                    <div class="card-body px-4 py-4-5">
                      <div class="row">
                        <div
                          class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start"
                        >
                          <div class="stats-icon green text-light bi bi-bookmark-check-fill mb-2">
                          </div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                          <h6 class="text-muted font-semibold">Modules Terminés et Examen Fait</h6>
                          <h6 class="font-extrabold mb-0"><?php echo $nbModuleTExamDone; ?></h6>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-6 col-lg-3 col-md-6">
                  <div class="card" data-bs-toggle="modal" data-bs-target="#moduleTExamDoneNo">
                    <div class="card-body px-4 py-4-5">
                      <div class="row">
                        <div
                          class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start"
                        >
                          <div class="stats-icon dark text-light bi bi-bookmarks-fill mb-2">
                          </div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                          <h6 class="text-muted font-semibold">Modules Terminés et Examen Non Fait</h6>
                          <h6 class="font-extrabold mb-0"><?php echo $nbModuleTExamDoneNo; ?></h6>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-12">
                  <div class="card">
                    <div class="card-body">
                      <div id="chartAll"></div>
                    </div>
                  </div>
                </div>
              </div>
              
            </div>
          </section>
          <section class="row">
            <div class="col-8 col-lg-8">
              <div class="card">
                <div class="card-header">
                  <select class="form-select" name="choiceModule" id="choiceModule" onchange="getModule(this.value);">
                    <?php for ($i=0; $i < count($module); $i++) : ?>
                      <option value="<?php echo $idModule[$i]; ?>"><?php echo $module[$i]; ?></option>
                    <?php endfor; ?>
                  </select>
                </div>
                <div class="card-body">
                  <div id="chart2"></div>
                </div>
              </div>
            </div>
            <div class="col-4 col-lg-4">
            <div class="card">
                <div class="card-body">
                  <div id="chart3"></div>
                </div>
              </div>
            </div>
          </section>
        </div>

        <footer>
            <?php include_once("footer.php"); ?>
        </footer>
      </div>
    </div>


    <!-- Modal Module Not Started -->
    <div class="modal fade" id="moduleNotStarted" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Modules non démarrés</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <table>
              <thead>
                <tr><th>Modules non démarrés</th></tr>
              </thead>
              <?php
                 $stmt10 = $con->prepare("SELECT M.libelleModule FROM enseigner E, Module M WHERE E.annee = ? AND E.matricule = ? AND E.dateDebut IS NULL AND M.idModule = E.idModule");
                 $stmt10->execute(array($annee, $matricule));
                while($row = $stmt10->fetch()) : 
              ?>
              <tr>
                <td><?php echo $row['libelleModule']; ?></td>
              </tr>
              <?php endwhile; ?>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Module Terminated -->
    <div class="modal fade" id="moduleTerminated" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Modules terminés</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <table>
              <thead>
                <tr><th>Module terminés</th></tr>
              </thead>
              <?php
                  $stmt11 = $con->prepare("SELECT M.libelleModule FROM enseigner E, Module M WHERE E.annee = ? AND E.matricule = ? AND E.dateFin IS NOT NULL AND M.idModule = E.idModule");
                  $stmt11->execute(array($annee, $matricule));
                while($row = $stmt11->fetch()) : 
              ?>
              <tr>
                <td><?php echo $row['libelleModule']; ?></td>
              </tr>
              <?php endwhile; ?>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Module Terminated and Exam Done -->
    <div class="modal fade" id="moduleTExamDone" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Modules terminés et examen fait</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <table>
              <thead>
                <tr><th>Module terminés et examen fait</th></tr>
              </thead>
              <?php
                  $stmt12 = $con->prepare("SELECT M.libelleModule FROM enseigner E, Module M WHERE E.annee = ? AND E.matricule = ? AND E.dateFin IS NOT NULL AND E.examenFait = '1' AND M.idModule = E.idModule");
                  $stmt12->execute(array($annee, $matricule));
                while($row = $stmt12->fetch()) : 
              ?>
              <tr>
                <td><?php echo $row['libelleModule']; ?></td>
              </tr>
              <?php endwhile; ?>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Module Terminated and Exam Done No -->
    <div class="modal fade" id="moduleTExamDoneNo" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Modules terminés et examen non fait</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <table>
              <thead>
                <tr><th>Module terminés et examen non fait</th></tr>
              </thead>
              <?php
                $stmt13 = $con->prepare("SELECT M.libelleModule FROM enseigner E, Module M WHERE E.annee = ? AND E.matricule = ? AND E.dateFin IS NOT NULL AND E.examenFait = '0' AND M.idModule = E.idModule");
                $stmt13->execute(array($annee, $matricule));
                while($row = $stmt13->fetch()) : 
              ?>
              <tr>
                <td><?php echo $row['libelleModule']; ?></td>
              </tr>
              <?php endwhile; ?>
            </table>
          </div>
        </div>
      </div>
    </div>

    <?php

      $reqDer = "SELECT MONTH(dateCours) AS mois, SUM(nbHeure) AS nbHeureTotal FROM cours WHERE idModule = ?  AND annee = ? AND estFait = 1 GROUP BY mois";
      $stmt = $con->prepare($reqDer);
      if($idModule) {
        $stmt->execute(array($idModule[0], $annee));
      
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
      }
      
    ?>

    <script src="assets/js/bootstrap.js"></script>
    <script src="assets/js/app.js"></script>
    <script src="assets/extensions/jquery/jquery.min.js"></script>
    <!-- Need: Apexcharts -->
    <script src="assets/extensions/apexcharts/apexcharts.min.js"></script>
    <!-- Srcipt chart -->
    <script>
      const cat = <?php echo json_encode($module); ?>;
      const nbH = <?php echo json_encode($nbH);  ?>;
      const nbHFait = <?php echo json_encode($nbHFait);  ?>;
      
      var options = {
          colors : ['#435ebe', '#26e7a6'],
          series: [{
          name: 'Nombre Heure Module',
          data: nbH
        },
        {
          name: 'Nombre Heure Fait',
          data: nbHFait
        }],
          chart: {
          height: 350,
          type: 'bar',
        },
        plotOptions: {
          bar: {
            dataLabels: {
              position: 'top', // top, center, bottom
            },
          }
        },
        dataLabels: {
          enabled: true,
          offsetY: -20,
          style: {
            fontSize: '12px',
            colors: ["#304758"]
          }
        },
        
        xaxis: {
          categories: cat,
          position: 'top',
          axisBorder: {
            show: false
          },
          axisTicks: {
            show: false
          },
          crosshairs: {
            fill: {
              type: 'gradient',
              gradient: {
                colorFrom: '#D8E3F0',
                colorTo: '#BED1E6',
                stops: [0, 100],
                opacityFrom: 0.4,
                opacityTo: 0.5,
              }
            }
          },
          tooltip: {
            enabled: true,
          }
        },
        yaxis: {
          axisBorder: {
            show: false
          },
          axisTicks: {
            show: false,
          },
          labels: {
            show: false,
          }
        
        },
        title: {
          text: 'Graphe : nombre Heure Fait / Nombre Heure Total',
          align: 'left',
          style: {
            color:  '#435ebe'
          },
        },

      };
      var chart = new ApexCharts(document.querySelector("#chartAll"), options);
      chart.render();
    </script>

    <!-- Script chart 3 -->
    <script>
       per = <?php echo json_encode($per); ?>;
       var options = {
          series: per,
          chart: {
          height: 350,
          type: 'radialBar',
          offsetY: -10
        },
        plotOptions: {
          radialBar: {
            startAngle: -135,
            endAngle: 135,
            dataLabels: {
              name: {
                fontSize: '16px',
                color: undefined,
                offsetY: 120
              },
              value: {
                offsetY: 76,
                fontSize: '22px',
                color: undefined,
                formatter: function (val) {
                  return val + "%";
                }
              }
            }
          }
        },
        fill: {
          type: 'gradient',
          gradient: {
              shade: 'dark',
              shadeIntensity: 0.15,
              inverseColors: false,
              opacityFrom: 1,
              opacityTo: 1,
              stops: [0, 50, 65, 91]
          },
        },
        stroke: {
          dashArray: 4
        },
        labels: ['Pourcentage de cours terminés'],
      };

      var chart = new ApexCharts(document.querySelector("#chart3"), options);
      chart.render();
    </script>

    <!-- Script chart 2 : Courbe d'évolution des cours-->
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
      
    <script>

      function getModule(val) {
          $.ajax({
              type: "POST",
              url: 'getModuleProf.php',
              data : 'module='+val,
              success : function(data) {
                  $("#chart2").html(data);
              }
          })
      }
    </script>
  </body>
</html>
