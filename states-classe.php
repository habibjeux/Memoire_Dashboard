<?php
  session_start();
  if(!$_SESSION['idAdmin'])
    header("Location: index.php");
  $id = $_SESSION['idAdmin'];
  $prenom = $_SESSION['prenom'];
  $nom = $_SESSION['nom'];
  $con = new PDO("mysql:hostname=localhost;dbname=suivicours", "root", "");

  $stmt = $con->prepare("SELECT annee FROM annee ORDER BY annee DESC LIMIT 2");
  $stmt->execute();
  $annee = $stmt->fetch()['annee'];
  $anneePre = $stmt->fetch()['annee'];

  $stmt = $con->prepare("SELECT COUNT(*) AS nbEns FROM enseignant");
  $stmt->execute();
  $nbEns = $stmt->fetch()[0];

  $stmt = $con->prepare("SELECT COUNT(*) AS nbUE FROM `ue-annee` WHERE annee = ?");
  $stmt->execute(array($annee));
  $nbUE = $stmt->fetch()[0];
  
  $stmt = $con->prepare("SELECT COUNT(*) AS nbModule FROM `module-annee` WHERE annee = ?");
  $stmt->execute(array($annee));
  $nbModule = $stmt->fetch()[0];

  $stmt = $con->prepare("SELECT COUNT(*) AS nbModule FROM `module-annee` WHERE annee = ?");
  $stmt->execute(array($anneePre));
  $nbModulePre = $stmt->fetch()[0];

  $stmt2 = $con->prepare("SELECT annee FROM annee ORDER BY annee DESC");
  $stmt2->execute();

  $stmt4 = $con->prepare("SELECT annee FROM annee ORDER BY annee DESC");
  $stmt4->execute();

  $stmt3 = $con->prepare("SELECT E.matricule, E.prenom, E.nom, C.dateCours, C.nbHeure, C.estFait, M.libelleModule, C.idClasse FROM 
  cours C, enseignant E, Module M  WHERE C.matricule = E.matricule AND M.idModule = C.idModule 
  AND annee = ? ORDER BY C.dateCours DESC");
  $stmt3->execute(array($anneePre));
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
      <?php include_once("sidebar.php");  ?>
      <div id="main">
        <header class="mb-3">
          <a href="#" class="burger-btn d-block d-xl-none">
            <i class="bi bi-justify fs-3"></i>
          </a>
        </header>
        <div class="container d-flex align-items-end justify-content-end">
          <div class="selectYear col-lg-2 col-md-3 col-sm-4">
              <select class="form-select" name="selectYear">
                <?php while($row = $stmt2->fetch()) : ?>
                  <option value="<?php echo $row['annee']; ?>"><?php echo $row['annee']; ?></option>
                <?php endwhile; ?>
              </select>
          </div>
        </div>
        <div class="page-heading">
          <h3>Statistique annuelle</h3>
        </div>
        <div class="page-content">
          <section class="row">
            <div class="col-12 col-lg-9">
              <div class="row">
                <div class="col-6 col-lg-3 col-md-6">
                  <div class="card">
                    <div class="card-body px-4 py-4-5">
                      <div class="row">
                        <div
                          class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start"
                        >
                          <div class="stats-icon purple mb-2">
                            <i class="iconly-boldShow"></i>
                          </div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                          <h6 class="text-muted font-semibold">
                            Enseignant
                          </h6>
                          <h6 class="font-extrabold mb-0"><?php echo $nbEns; ?></h6>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-6 col-lg-3 col-md-6">
                  <div class="card">
                    <div class="card-body px-4 py-4-5">
                      <div class="row">
                        <div
                          class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start"
                        >
                          <div class="stats-icon blue mb-2">
                            <i class="iconly-boldProfile"></i>
                          </div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                          <h6 class="text-muted font-semibold">Nombre UE</h6>
                          <h6 class="font-extrabold mb-0"><?php echo $nbUE; ?></h6>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-6 col-lg-3 col-md-6">
                  <div class="card">
                    <div class="card-body px-4 py-4-5">
                      <div class="row">
                        <div
                          class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start"
                        >
                          <div class="stats-icon green mb-2">
                            <i class="iconly-boldAdd-User"></i>
                          </div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                          <h6 class="text-muted font-semibold">Nouveau module</h6>
                          <h6 class="font-extrabold mb-0"><?php if(($nbModule - $nbModulePre) >=0 ) echo $nbModule - $nbModulePre; else echo "0"; ?></h6>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-6 col-lg-3 col-md-6">
                  <div class="card">
                    <div class="card-body px-4 py-4-5">
                      <div class="row">
                        <div
                          class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start"
                        >
                          <div class="stats-icon red mb-2">
                            <i class="iconly-boldBookmark"></i>
                          </div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                          <h6 class="text-muted font-semibold">Nombre de module</h6>
                          <h6 class="font-extrabold mb-0"><?php echo $nbModule; ?></h6>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-12">
                  <div class="card">
                    <div class="card-header">
                      <h4>Profile Visit</h4>
                    </div>
                    <div class="card-body">
                      <div id="chartAll"></div>
                    </div>
                  </div>
                </div>
              </div>
              
            </div>
            <div class="col-12 col-lg-3">
              <div class="card">
                <div class="card-body py-4 px-4">
                  <div class="d-flex align-items-center">
                    <div class="avatar avatar-xl">
                    </div>
                    <div class="ms-3 name">
                      <h5 class="font-bold word-wrap"><?php echo $prenom.' '.$nom; ?></h5>
                      <h6 class="text-muted mb-0">@admin</h6>
                        <a class="btn btn-danger" href="deconnexion.php"><i class="bi bi-box-arrow-left text-light"></i> Déconnexion</a>
                    </div>
                  </div>
                </div>
              </div>
              <div class="card">
                <div class="card-header">
                  <h4>Recent Messages</h4>
                </div>
                <div class="card-content pb-4">
                  <div class="recent-message d-flex px-4 py-3">
                    <div class="avatar avatar-lg">
                      <img src="assets/images/faces/4.jpg" />
                    </div>
                    <div class="name ms-4">
                      <h5 class="mb-1">Hank Schrader</h5>
                      <h6 class="text-muted mb-0">@johnducky</h6>
                    </div>
                  </div>
                  <div class="recent-message d-flex px-4 py-3">
                    <div class="avatar avatar-lg">
                      <img src="assets/images/faces/5.jpg" />
                    </div>
                    <div class="name ms-4">
                      <h5 class="mb-1">Dean Winchester</h5>
                      <h6 class="text-muted mb-0">@imdean</h6>
                    </div>
                  </div>
                  <div class="recent-message d-flex px-4 py-3">
                    <div class="avatar avatar-lg">
                      <img src="assets/images/faces/1.jpg" />
                    </div>
                    <div class="name ms-4">
                      <h5 class="mb-1">John Dodol</h5>
                      <h6 class="text-muted mb-0">@dodoljohn</h6>
                    </div>
                  </div>
                  <div class="px-4">
                    <button
                      class="btn btn-block btn-xl btn-outline-primary font-bold mt-3"
                    >
                      Start Conversation
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </section>
          <section class="section">
            <div class="card">
              <div class="card-header">Dernières cours</div>
              <div class="card-body">
                <table class="table table-striped" id="table1">
                  <thead>
                    <tr>
                      <th>Module</th>
                      <th>Professeur</th>
                      <th>Classe</th>
                      <th>Date</th>
                      <th>Nombre Heure</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while($row = $stmt3->fetch()) : ?>
                      <tr>
                        <td><?php echo $row['libelleModule']; ?></td>
                        <td><?php echo $row['prenom'].' '.$row['nom']; ?></td>
                        <td><?php echo $row['idClasse']; ?></td>
                        <td><?php echo $row['dateCours']; ?></td>
                        <td class="text-center"><?php echo $row['nbHeure']; ?></td>
                        <td>
                          <?php if($row['estFait']) :?>
                            <span class="badge bg-success btn-block">Fait</span>
                          <?php else: ?>
                            <span class="badge bg-danger btn-block">Non Fait</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </section>
        </div>

        <footer>
            <?php include_once("footer.php"); ?>
        </footer>
      </div>
    </div>
    <?php
      try {
        $req = "SELECT M.libelleModule, SUM(C.nbHeure) AS nbHeureFait FROM module M, cours C WHERE M.idModule = C.idModule AND C.estFait = 1 AND C.idClasse='L3' GROUP BY C.idModule ORDER BY nbHeureFait";
        $stmt = $con->prepare($req);
        $stmt->execute(array());
        while ($row = $stmt->fetch()) {
          $module[] = $row['libelleModule'];
          $nbH[] = $row['nbHeureFait'];
        }
      } catch (PDOException $th) {
        echo $th->getMessage();
      }
    ?>

 

    <script src="assets/js/bootstrap.js"></script>
    <script src="assets/js/app.js"></script>
    <script src="assets/extensions/jsPDF/jspdf.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.2/jquery.min.js"></script>
    <!-- Need: Apexcharts -->
    <script src="assets/extensions/apexcharts/apexcharts.min.js"></script>
    <script src="assets/js/pages/ui-apexchart.js"></script>
    <script src="assets/extensions/simple-datatables/umd/simple-datatables.js"></script>
    <script src="assets/js/pages/simple-datatables.js"></script>
    <!-- Srcipt chart -->
    <script>
      const cat = <?php echo json_encode($module); ?>;
      const nbH = <?php echo json_encode($nbH);  ?>;
      var options = {
        series: [{
        name: 'Nombre Heure Module',
        data: nbH
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
        text: 'Nombre heure par module',
        floating: true,
        offsetY: 330,
        align: 'center',
        style: {
          color: '#444'
        }
      }
      };

      var chart = new ApexCharts(document.querySelector("#chartAll"), options);
      chart.render();

    </script>
      
  </body>
</html>
