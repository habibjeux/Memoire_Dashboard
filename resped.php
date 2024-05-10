<?php
  session_start();
  if(!$_SESSION['matriculeP'])
    header("Location: index.php");
  $matriculeP = $_SESSION['matriculeP'];
  $prenom = $_SESSION['prenom'];
  $nom = $_SESSION['nom'];
  $con = new PDO("mysql:hostname=localhost;dbname=suivicours", "root", "");

  $stmt = $con->prepare("SELECT DISTINCT A.annee FROM annee A, `resped-classe` RC WHERE A.annee = RC.annee AND matricule = ? ORDER BY annee ASC");
  // Je choisi ASC parce que l'année 2022-2023 n'a rien
  $stmt->execute(array($_SESSION['matriculeP']));
  $annee = $stmt->fetch()['annee'];

  $stmt = $con->prepare("SELECT DISTINCT C.idClasse, C.libelleClasse FROM `resped-classe` RC, classe C WHERE RC.idClasse = C.idClasse AND RC.annee = ? AND RC.matricule = ?");
  $stmt->execute(array($annee, $_SESSION['matriculeP']));
  $classe = $stmt->fetch()['idClasse'];

  $stmt = $con->prepare("SELECT COUNT(*) AS nbUE FROM `ue-annee` WHERE annee = ?");
  $stmt->execute(array($annee));
  $nbUE = $stmt->fetch()[0];


  $stmt2 = $con->prepare("SELECT DISTINCT A.annee FROM annee A, `resped-classe` RC WHERE A.annee = RC.annee AND matricule = ? ORDER BY annee DESC");
  $stmt2->execute(array($_SESSION['matriculeP']));

  $stmt4 = $con->prepare("SELECT annee FROM annee ORDER BY annee DESC");
  $stmt4->execute();

  $sem = 1;

  if(isset($_POST['submit'])) {
    $annee = $_POST['annee'];
    $sem = $_POST['sem'];
  }
  $_SESSION['annee'] = $annee;
  $_SESSION['classe'] = $classe;

  $stmt5 = $con->prepare("SELECT * FROM classe");


  // Nombre Module Non débutés
  $stmt10 = $con->prepare("SELECT COUNT(DISTINCT E.idModule) AS cou FROM enseigner E, Module M, `module-annee` MA, `ue-annee` UA WHERE 
  E.dateDebut IS NULL AND E.idClasse = ? AND E.annee = ? AND E.idModule = MA.idModule AND E.annee = MA.annee
  AND M.idModule = MA.idModule AND UA.codeUE = MA.codeUE AND UA.annee = MA.annee AND UA.codeSem = ?");
  $stmt10->execute(array($classe, $annee, $sem));
  $nbModuleNotStated = $stmt10->fetch()['cou'];

  // Nombre Module Terminé
  $stmt11 = $con->prepare("SELECT COUNT(DISTINCT E.idModule) AS cou FROM enseigner E, Module M, `module-annee` MA, `ue-annee` UA WHERE 
  E.dateFin IS NOT NULL AND E.idClasse = ? AND E.annee = ? AND E.idModule = MA.idModule AND E.annee = MA.annee
  AND M.idModule = MA.idModule AND UA.codeUE = MA.codeUE AND UA.annee = MA.annee AND UA.codeSem = ?");
  $stmt11->execute(array($classe, $annee, $sem));
  $nbModuleTermanated = $stmt11->fetch()['cou'];

  // Nombre Module Terminé et examenFait
  $stmt12 = $con->prepare("SELECT COUNT(DISTINCT E.idModule) AS cou FROM enseigner E, Module M, `module-annee` MA, `ue-annee` UA WHERE 
  E.dateFin IS NOT NULL AND E.examenFait = '1' AND E.idClasse = ? AND E.annee = ? AND E.idModule = MA.idModule AND E.annee = MA.annee
  AND M.idModule = MA.idModule AND UA.codeUE = MA.codeUE AND UA.annee = MA.annee AND UA.codeSem = ?");
  $stmt12->execute(array($classe, $annee, $sem));
  $nbModuleTExamDone = $stmt12->fetch()['cou'];

  // Nombre Module Terminé et examenNonFait
  $stmt13 = $con->prepare("SELECT COUNT(DISTINCT E.idModule) AS cou FROM enseigner E, Module M, `module-annee` MA, `ue-annee` UA WHERE 
  E.dateFin IS NOT NULL AND E.examenFait = '0' AND E.idClasse = ? AND E.annee = ? AND E.idModule = MA.idModule AND E.annee = MA.annee
  AND M.idModule = MA.idModule AND UA.codeUE = MA.codeUE AND UA.annee = MA.annee AND UA.codeSem = ?");
  $stmt13->execute(array($classe, $annee, $sem));
  $nbModuleTExamDoneNo = $stmt13->fetch()['cou'];

  //Pourcentage total de modules enseignées
  $stmt11 = $con->prepare("SELECT COUNT(DISTINCT E.idModule) AS cou FROM enseigner E, Module M, `module-annee` MA, `ue-annee` UA WHERE 
  E.idClasse = ? AND E.annee = ? AND E.idModule = MA.idModule AND E.annee = MA.annee
  AND M.idModule = MA.idModule AND UA.codeUE = MA.codeUE AND UA.annee = MA.annee AND UA.codeSem = ?");
  $stmt11->execute(array($classe, $annee, $sem));
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
    <title>Dashboard - Accueil</title>

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
      <?php include_once("sidebar-resped.php");  ?>
      <?php
        try {
          $req = "SELECT M.idModule, M.libelleModule, MA.nbHeureModule, SUM(C.nbHeure) AS nbHeureFait FROM module M, 
          `module-annee` MA, `ue-annee` UA, Cours C WHERE M.idModule = MA.idModule AND MA.annee = C.annee 
          AND M.idModule = C.idModule AND C.estFait = '1' AND C.idClasse = ? AND C.annee = ? 
          AND UA.codeUE = MA.codeUE AND UA.codeSem = ? AND UA.annee = MA.annee GROUP BY M.idModule";

          $stmt = $con->prepare($req);
          $stmt->execute(array($classe, $annee, $sem));
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
                <select id="annee" class="form-select" name="annee" required onchange="getClasse(this.value);">
                  <option value="">ANNEE UNIVERSITAIRE</option>
                  <?php $stmt5->execute(); ?>
                  <?php while($row = $stmt2->fetch()) : ?>
                    <option value="<?php echo $row['annee']; ?>"><?php echo $row['annee']; ?></option>
                  <?php endwhile; ?>
                </select>
            </div>
            <div class="col-3">
                <select id="classe" class="form-select" name="classe" required>
                  <option value="">CLASSE</option>
                </select>
            </div>
            <div class="col-3">
                <select class="form-select" name="sem" required>
                  <option value="">Semestre</option>
                  <option value="1">Semestre 1</option>
                  <option value="2">Semestre 2</option>
                </select>
            </div>
            <button class="btn btn-primary col-2" type="submit" name="submit">Charger</button>
          </form>
        </section>
        <div class="page-heading mt-4">
          <h3><?php echo "Année : $annee Classe : $classe Semestre : $sem"; ?></h3>
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
                $stmt10 = $con->prepare("SELECT DISTINCT M.libelleModule FROM enseigner E, Module M, `module-annee` MA, `ue-annee` UA WHERE 
                E.dateDebut IS NULL AND E.idClasse = ? AND E.annee = ? AND E.idModule = MA.idModule AND E.annee = MA.annee
                AND M.idModule = MA.idModule AND UA.codeUE = MA.codeUE AND UA.annee = MA.annee AND UA.codeSem = ?"); 
                $stmt10->execute(array($classe, $annee, $sem)); 
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
                $stmt11 = $con->prepare("SELECT DISTINCT M.libelleModule FROM enseigner E, Module M, `module-annee` MA, `ue-annee` UA WHERE 
                E.dateFin IS NOT NULL AND E.idClasse = ? AND E.annee = ? AND E.idModule = MA.idModule AND E.annee = MA.annee
                AND M.idModule = MA.idModule AND UA.codeUE = MA.codeUE AND UA.annee = MA.annee AND UA.codeSem = ?"); 
                $stmt11->execute(array($classe, $annee, $sem)); 
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
                $stmt12 = $con->prepare("SELECT DISTINCT M.libelleModule FROM enseigner E, Module M, `module-annee` MA, `ue-annee` UA WHERE 
                E.dateFin IS NOT NULL AND E.examenFait = '1' AND E.idClasse = ? AND E.annee = ? AND E.idModule = MA.idModule AND E.annee = MA.annee
                AND M.idModule = MA.idModule AND UA.codeUE = MA.codeUE AND UA.annee = MA.annee AND UA.codeSem = ?"); 
                $stmt12->execute(array($classe, $annee, $sem)); 
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
                $stmt13 = $con->prepare("SELECT DISTINCT M.libelleModule FROM enseigner E, Module M, `module-annee` MA, `ue-annee` UA WHERE 
                E.dateFin IS NOT NULL AND E.examenFait = '0' AND E.idClasse = ? AND E.annee = ? AND E.idModule = MA.idModule AND E.annee = MA.annee
                AND M.idModule = MA.idModule AND UA.codeUE = MA.codeUE AND UA.annee = MA.annee AND UA.codeSem = ?"); 
                $stmt13->execute(array($classe, $annee, $sem)); 
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

      $reqDer = "SELECT MONTH(dateCours) AS mois, SUM(nbHeure) AS nbHeureTotal FROM cours WHERE idModule = ? AND idClasse = ? AND annee = ? AND estFait = 1 GROUP BY mois";
      $stmt = $con->prepare($reqDer);
      if($idModule) {
        $stmt->execute(array($idModule[0], $classe, $annee));
      
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
      document.getElementById('dashboard').classList.add('active');
      function getClasse(val) {
        $.ajax({
            type: "POST",
            url: 'getClasse.php',
            data : 'annee='+val,
            success : function(data) {
                $("#classe").html(data);
            }
        })
      }
      function getModule(val) {
          $.ajax({
              type: "POST",
              url: 'getModule.php',
              data : 'module='+val,
              success : function(data) {
                  $("#chart2").html(data);
              }
          })
      }
    </script>
  </body>
</html>
