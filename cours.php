<?php
  session_start();
  if(!$_SESSION['idAdmin'])
    header("Location: index.php");
  $id = $_SESSION['idAdmin'];
  $prenom = $_SESSION['prenom'];
  $nom = $_SESSION['nom'];

  function formaterDateFrancais($date) {
    $date = new DateTime($date);
    return $date->format('d/m/Y');
  }

  $con = new PDO("mysql:hostname=localhost;dbname=suivicours", "root", "");

  $stmt = $con->prepare("SELECT annee FROM annee ORDER BY annee ASC LIMIT 1");
  $stmt->execute();
  $annee = $stmt->fetch()['annee'];

  $stmt = $con->prepare("SELECT * FROM classe LIMIT 1");
  $stmt->execute();
  $classe = $stmt->fetch()['idClasse'];

  $stmt = $con->prepare("SELECT COUNT(*) AS nbUE FROM `ue-annee` WHERE annee = ?");
  $stmt->execute(array($annee));
  $nbUE = $stmt->fetch()[0];


  $stmt2 = $con->prepare("SELECT annee FROM annee ORDER BY annee DESC");
  $stmt2->execute();

  $stmt4 = $con->prepare("SELECT annee FROM annee ORDER BY annee DESC");
  $stmt4->execute();

  $sem = 1;

  if(isset($_POST['submit'])) {
    $annee = $_POST['annee'];
    $classe = $_POST['classe'];
    $sem = $_POST['sem'];

  }

  $stmt3 = $con->prepare("SELECT EN.matricule, EN.prenom, EN.nom, C.dateCours, C.nbHeure, C.estFait, M.libelleModule, 
  C.idClasse FROM enseigner E, cours C, module M, enseignant EN, `module-annee` MA, `ue-annee` UA WHERE E.matricule = C.matricule 
  AND E.idModule = C.idModule AND E.idClasse = C.idClasse AND E.annee = C.annee AND C.annee = ? AND M.idModule = C.idModule 
  AND E.matricule = EN.matricule AND MA.idModule = M.idModule AND MA.annee = E.annee AND MA.codeUE = UA.codeUE AND MA.annee = UA.annee 
  AND C.idClasse = ? AND UA.codeSem = ? ORDER BY C.dateCours");
  $stmt3->execute(array($annee, $classe, $sem));

  $stmt5 = $con->prepare("SELECT * FROM classe");

  //Module par année
  $stmt6 = $con->prepare("SELECT M.libelleModule, COUNT(E.matricule) AS moduleProf FROM `enseigner` E, module M, `module-annee` MA,
  `ue-annee` UA WHERE E.idModule = M.idModule AND E.annee = ? AND E.idClasse = ? AND MA.idModule = E.idModule
  AND MA.annee = E.annee AND MA.codeUE = UA.codeUE AND UA.annee = MA.annee AND UA.codeSem = ? GROUP BY M.idModule ORDER BY moduleProf DESC");
  $stmt6->execute(array($annee, $classe, $sem));
  while($row = $stmt6->fetch()) {
    $module[] = $row['libelleModule'];
    $nbH[] = $row['moduleProf'];
  }

  // Nombre de Seance  par Module
  $stmt7 = $con->prepare("SELECT M.idModule, M.libelleModule, MA.nbHeureModule, COUNT(C.idCours) AS nbSeanceFait FROM module M, 
  `module-annee` MA, `ue-annee` UA, Cours C WHERE M.idModule = MA.idModule AND MA.annee = C.annee 
  AND M.idModule = C.idModule AND C.estFait = '1' AND C.idClasse = ? AND C.annee = ? 
  AND UA.codeUE = MA.codeUE AND UA.codeSem = ? AND UA.annee = MA.annee GROUP BY M.idModule");
  $stmt7->execute(array($classe, $annee, $sem));
  while($row = $stmt7->fetch()) {
    $module2[] = $row['libelleModule'];
    $nbH2[] = $row['nbSeanceFait'];
  }
?>
<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard - Cours</title>

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
        <!-- formulaire selection année classe sem  -->
        <section>
          <form class="row" action="" method="POST">
            <div class="col-3">
                <select class="form-select" name="annee">
                  <option value="">ANNEE UNIVERSITAIRE</option>
                  <?php $stmt5->execute(); ?>
                  <?php while($row = $stmt2->fetch()) : ?>
                    <option value="<?php echo $row['annee']; ?>"><?php echo $row['annee']; ?></option>
                  <?php endwhile; ?>
                </select>
            </div>
            <div class="col-3">
                <select class="form-select" name="classe">
                  <option value="">CLASSE</option>
                  <?php while($row = $stmt5->fetch()) : ?>
                    <option value="<?php echo $row['idClasse']; ?>"><?php echo $row['libelleClasse']; ?></option>
                  <?php endwhile; ?>
                </select>
            </div>
            <div class="col-3">
                <select class="form-select" name="sem">
                  <option value="">Semestre</option>
                  <option value="1">Sem 1</option>
                  <option value="2">Sem 2</option>
                </select>
            </div>
            <button class="btn btn-primary col-2" type="submit" name="submit">Charger</button>
          </form>
        </section>
        <div class="page-heading mt-4">
          <h3><?php echo "Année : $annee Classe : $classe Semestre : $sem"; ?></h3>
        </div>
        <div class="page-content">
          <!-- Derniers cours -->
          <section class="section">
            <div class="card">
              <div class="card-header text-primary" id="cours"><h4>Liste des cours</h4></div>
              <div class="card-body">
                <table class="table table-striped" id="table">
                  <thead>
                    <tr class="text-primary">
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
                        <td><?php echo formaterDateFrancais($row['dateCours']); ?></td>
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

    <script src="assets/js/bootstrap.js"></script>
    <script src="assets/js/app.js"></script>
    <!-- Need: Apexcharts -->
    <script src="assets/extensions/apexcharts/apexcharts.min.js"></script>
    <script src="assets/extensions/jquery/jquery.min.js"></script>
    <script src="assets/extensions\simple-datatables\jquery.dataTables.min.js"></script>
    <script src="assets\extensions\simple-datatables\dataTables.bootstrap5.min.js"></script>
    <script>
      document.getElementById('cours').classList.add('active');
        $(document).ready(function() {
          $('#table').DataTable({
            "language": {
              "url": "assets//extensions//simple-datatables//French.json"
            }
          });
        });
    </script>
  </body>
</html>
