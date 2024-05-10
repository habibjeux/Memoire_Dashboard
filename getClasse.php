<?php
    session_start();
    $con = new PDO("mysql:hostname=localhost;dbname=suivicours", "root", "");
    $annee = $_POST['annee'];
    $req = "SELECT C.idClasse, C.libelleClasse FROM `resped-classe` RC, classe C WHERE RC.idClasse = C.idClasse AND RC.annee = ? AND RC.matricule = ?";
    $stmt = $con->prepare($req);
    $stmt->execute(array($annee, $_SESSION['matriculeP']));
    echo "<option value=''>CLASSE</option>";
    if($stmt->rowCount() > 0) {
        while($row = $stmt->fetch()) : 
        ?>
            <option value="<?php echo $row['idClasse']; ?>"><?php echo $row['libelleClasse']; ?></option>
        <?php
        endwhile;
    }

?>