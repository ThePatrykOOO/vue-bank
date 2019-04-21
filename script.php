<?php
session_start();
$server = 'localhost'; //nazwa serwera
$dbname = 'vuebank'; // nazwa bazy
$user = 'root'; // nazwa usera
$pass = ''; // password do bazy

//polaczenie
$dbh = new PDO('mysql:host=' . $server . ';dbname='.$dbname, $user, $pass, [PDO::MYSQL_ATTR_INIT_COMMAND =>  "SET NAMES 'UTF8'"]);
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
header("Conent-type: aplication/json");
$response = array();

if(isset($_GET['action'])) {
  $action = $_GET['action'];

  if ($action == "checkLogin") {
    if (isset($_SESSION['logged']) > 0) {
      $response['userID'] = $_SESSION['logged'];
      $result = $dbh->query("SELECT * FROM users WHERE user_id=".$_SESSION['logged'])->fetch();
      $response['user'][0] = $result['user_id'];
      $response['user'][1] = $result['user_email'];
      $response['user'][2] = $result['user_cash'];
    } else {
      $response['userID'] = 0;
    }
  }

  if ($action == "newUser") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $response['registerError'] = []; //błędy walidacji
    $response['registerSuccess'] = false;
    // check_pass
    if((strlen($password)<5) || (strlen($password)>20)) {
      array_push($response['registerError'], "Hasło powinno zawierać od 5 do 20 znaków.");
  	}
    // check_email
    if(filter_var($email, FILTER_VALIDATE_EMAIL) == false || $email == '') {
			array_push($response['registerError'], "Podałeś niepoprawny email");
		}
    $question = $dbh->query("SELECT user_email FROM users WHERE user_email='$email'");
    $count = $question->RowCount();
    if($count>0) {
      array_push($response['registerError'], "Email jest zarejestrowany");
    }
    if(strlen($email) > 100) {
    	array_push($response['registerError'], "Email ma za dużo znaków");
    }
    if (count($response['registerError']) == 0) {
      $password = password_hash($password, PASSWORD_BCRYPT);
      $query = $dbh->query("INSERT INTO users VALUES(NULL, '$email', '$password', '0')");
      $result = $dbh->query("SELECT user_id FROM users LIMIT 1")->fetch();
      $count = $question->RowCount();

      $result = $dbh->query("SELECT user_id FROM users ORDER BY user_id DESC LIMIT 1")->fetch();
      $_SESSION['logged'] = $result['user_id'];
      $response['registerSuccess'] = true;
    }
  }
  if ($action == "loginUser") {
    $response['user'] = []; //błędy walidacji
    $email = $_POST['email'];
    $password = $_POST['password'];
    $question = $dbh->query("SELECT * FROM users WHERE user_email='$email'");
    $count = $question->RowCount();
    if ($count > 0) {
      $result = $question->fetch();
      if (password_verify($password, $result['user_password'])) {
        $_SESSION['logged'] = $result['user_id'];
        $response['user'][0] = $result['user_id'];
        $response['user'][1] = $result['user_email'];
        $response['user'][2] = $result['user_cash'];

        $response['loginSuccess'] = true;
      } else {
        $response['loginError'] = true;
      }
    } else {
      $response['loginError'] = true;
    }
  }
  if ($action == "logout") {
    session_destroy();
    $response['logout'] = true;
  }
  if ($action == "new_transfer") {
    $email = $_POST['email'];
    $cash = $_POST['cash'];
    $response['errorTransfer'] = [];

    $response['userID'] = $_SESSION['logged'];
    $result = $dbh->query("SELECT * FROM users WHERE user_id=".$response['userID'])->fetch();
    $response['user'][0] = $result['user_id'];
    $response['user'][1] = $result['user_email'];
    $response['user'][2] = $result['user_cash'];

    $query = $dbh->query("SELECT user_id FROM users WHERE user_email='$email'");
    if ($query->rowCount() == 0) {
      array_push($response['errorTransfer'], "Nie ma takiego użytkownika");
    } else {
      $result = $query->fetch();
      $transferTo = $result['user_id'];
    }

    if ($email == $response['user'][1]) {
      array_push($response['errorTransfer'], "Nie możesz wysła pieniędzy sam do siebie");
    }
    if ($cash > $response['user'][2]) {
      array_push($response['errorTransfer'], "Masz za mało środków na koncie");
    }
    if ($cash == 0) {
      array_push($response['errorTransfer'], "Nie możesz nic wysłac");
    }
    if (count($response['errorTransfer']) == 0) {
      $userID = $_SESSION['logged'];
      $dbh->query("INSERT INTO transfers VALUES(NULL,'$userID','$transferTo','$cash')");
      $dbh->query("UPDATE users SET user_cash=(user_cash - '$cash') WHERE user_id='$userID'");
      $dbh->query("UPDATE users SET user_cash=(user_cash + '$cash') WHERE user_id='$transferTo'");
      $response['successTransfer'] = true;
    }
  }
  if ($action == "add_money") {
    $userID = $_SESSION['logged'];
    $question = $dbh->query("UPDATE users SET user_cash=(user_cash + 1) WHERE user_id='$userID'");
    $response['addMoney'] = true;
  }
  if ($action == "get_history") {
    $userID = $_SESSION['logged'];
    // wychodzące
    $response['sentTransfers'] = [];
    $query = $dbh->query("SELECT u.user_email, t.transfer_cash FROM users as u, transfers as t WHERE u.user_id=t.transfer_to AND t.transfer_from='$userID'");
    foreach ($query as $value) {
      array_push($response['sentTransfers'], [$value['user_email'],$value['transfer_cash']]);
    }

    // wchodzace
    $response['receiveTransfers'] = [];
    $query = $dbh->query("SELECT u.user_email, t.transfer_cash FROM users as u, transfers as t WHERE u.user_id=t.transfer_to AND t.transfer_to='$userID'");
    foreach ($query as $value) {
      array_push($response['receiveTransfers'], [$value['user_email'],$value['transfer_cash']]);
    }
  }
}

echo json_encode($response);
