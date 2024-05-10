<?php
  session_start();
?>
<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Authentificaton - Dashboard</title>
    <link rel="stylesheet" href="assets/css/main/app.css" />
    <link rel="stylesheet" href="assets/css/pages/auth.css" />
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
  </head>

  <body>
    <div id="auth">
      <div class="row h-100">
        <div class="col-lg-5 col-12">
          <div id="auth-left">
            <h1 class="auth-title">Page d'authentificaton.</h1>
            <form action="" method="POST">
              <div class="form-group position-relative has-icon-left mb-4">
                <input
                  type="email"
                  class="form-control form-control-xl"
                  placeholder="Email"
                  name="email"
                />
                <div class="form-control-icon">
                  <i class="bi bi-envelope"></i>
                </div>
              </div>
              <div class="form-group position-relative has-icon-left mb-4">
                <input
                  type="password"
                  class="form-control form-control-xl"
                  placeholder="Mot de passe"
                  name="password"
                />
                <div class="form-control-icon">
                  <i class="bi bi-shield-lock"></i>
                </div>
              </div>
              <button class="btn btn-primary btn-block btn-lg shadow-lg mt-2"
                type="submit"
                name="submit">
                Se connecter
              </button>
            </form>
            <div id="erreur" class ="btn-block badge badge-danger bg-danger mt-4"></div>
          </div>
        </div>
        <div class="col-lg-7 d-none d-lg-block">
          <div id="auth-right"></div>
        </div>
      </div>
    </div>
    <?php
        if(isset($_POST['submit'])) {
          $email = $_POST['email'];
          $password = $_POST['password'];
          $bdd = new PDO("mysql:hostname=localhost;dbname=SuiviCours", "root", "");
          $req = "SELECT * FROM admin WHERE email = ? AND password = ?";
          $stmt = $bdd->prepare($req);
          $stmt->execute(array($email, $password));
          if($stmt->rowCount() == 1) {
            $row = $stmt->fetch();
            $_SESSION['idAdmin'] = $row['id'];
            $_SESSION['prenom'] = $row['prenom'];
            $_SESSION['nom'] = $row['nom'];
            header("Location: admin.php");
          }
          else {
            $req2 = "SELECT * FROM enseignant WHERE email = ? AND password = ? AND matricule NOT IN (SELECT matricule FROM `resped-classe`)";
            $stmt2 = $bdd->prepare($req2);
            $stmt2->execute(array($email, $password));
            if($stmt2->rowCount() == 1) {
              $row = $stmt2->fetch();
              $_SESSION['matricule'] = $row['matricule'];
              $_SESSION['prenom'] = $row['prenom'];
              $_SESSION['nom'] = $row['nom'];
              header("Location: prof.php");
            }
            else {
              $req3 = "SELECT * FROM enseignant WHERE email = ? AND password = ? AND matricule IN (SELECT matricule FROM `resped-classe`)";
              $stmt3 = $bdd->prepare($req3);
              $stmt3->execute(array($email, $password));
              if($stmt3->rowCount() == 1) {
                $row = $stmt3->fetch();
                $_SESSION['matriculeP'] = $row['matricule'];
                $_SESSION['prenom'] = $row['prenom'];
                $_SESSION['nom'] = $row['nom'];
                header("Location: resped.php");
              }
              else {
              ?>
              <script>
                let erreur = document.getElementById('erreur');
                erreur.innerHTML = "Identifiants incorrets";
              </script>
              <?php
              }
            }
            
          }
        }
    ?>
  </body>
</html>
