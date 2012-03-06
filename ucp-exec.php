<?php
/********************************************************************************
*                                                                               *
*   Copyright 2012 Nicolas CARPi (nicolas.carpi@gmail.com)                      *
*   http://www.elabftw.net/                                                     *
*                                                                               *
********************************************************************************/

/********************************************************************************
*  This file is part of eLabFTW.                                                *
*                                                                               *
*    eLabFTW is free software: you can redistribute it and/or modify            *
*    it under the terms of the GNU Affero General Public License as             *
*    published by the Free Software Foundation, either version 3 of             *
*    the License, or (at your option) any later version.                        *
*                                                                               *
*    eLabFTW is distributed in the hope that it will be useful,                 *
*    but WITHOUT ANY WARRANTY; without even the implied                         *
*    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR                    *
*    PURPOSE.  See the GNU Affero General Public License for more details.      *
*                                                                               *
*    You should have received a copy of the GNU Affero General Public           *
*    License along with eLabFTW.  If not, see <http://www.gnu.org/licenses/>.   *
*                                                                               *
********************************************************************************/
session_start();
require_once('inc/connect.php');

// INFO BOX
$errmsg_arr = array();
$infomsg_arr =array();
$errflag = false;
$infoflag = false;

// Main form
if (isset($_POST['main'])){
// 1. Check that we were given a good password
// Get salt
$sql = "SELECT salt FROM users WHERE userid=".$_SESSION['userid'];
$result = $bdd->prepare($sql);
$result->execute();
$data = $result->fetch();
$salt = $data['salt'];
// Create hash
$passwordHash = hash("sha512", $salt.$_POST['currpass']);
$sql = "SELECT * FROM users WHERE userid = :userid AND password = :password";
$req = $bdd->prepare($sql);
$result = $req->execute(array(
    'userid' => $_SESSION['userid'],
    'password' => $passwordHash));
$numrows = $req->rowCount();
if( ($result) && ($numrows === 1) ) {
    // Old password is good. Continue

    // PASSWORD CHANGE
    if ((isset($_POST['cnewpass'])) && (!empty($_POST['cnewpass']))) {
        $cpassword = filter_var($_POST['cnewpass'], FILTER_SANITIZE_STRING);
        if ((isset($_POST['newpass'])) && (!empty($_POST['newpass']))) {
            // Good to go
            $password = filter_var($_POST['newpass'], FILTER_SANITIZE_STRING);
            // Check for password length
            if (strlen($password) <= 3) {
                $errmsg_arr[] = 'Password must contain at least 4 characters';
                $errflag = true;
            }
            if (strcmp($password, $cpassword) != 0 ) {
                $errmsg_arr[] = 'Passwords do not match';
                $errflag = true;
            }
            // Create salt
            $salt = hash("sha512", uniqid(rand(), TRUE));
            // Create hash
            $passwordHash = hash("sha512", $salt.$password);
            $sql = "UPDATE users SET salt = :salt, 
                password = :password 
                WHERE userid = :userid";
            $req = $bdd->prepare($sql);
            $result = $req->execute(array(
                'salt' => $salt,
                'password' => $passwordHash,
                'userid' => $_SESSION['userid']));
            if($result){
                $infomsg_arr[] = 'Password updated !';
                $infoflag = true;
            } else {
                die('SQL failed');
            }
        }
    }
    // Check USERNAME (sanitize and validate)
        if ((isset($_POST['username'])) && (!empty($_POST['username']))) {
        $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
        // Check for duplicate username in DB
        $sql = "SELECT * FROM users WHERE username='$username'";
        $result = $bdd->query($sql);
        $numrows = $result->rowCount();
        $data = $result->fetch();
        if($result) {
            if($numrows > 0) {
                if($data['userid'] != $_SESSION['userid']){
                $errmsg_arr[] = 'Username already in use';
                $errflag = true;
            }
        }
        }
    } else {
        $errmsg_arr[] = 'Username missing ! What were you thinking about ?';
        $errflag = true;
    }
    // Check FIRSTNAME (sanitize only)
        if ((isset($_POST['firstname'])) && (!empty($_POST['firstname']))) {
        $firstname = filter_var($_POST['firstname'], FILTER_SANITIZE_STRING);
    } else {
        $errmsg_arr[] = 'Please put your firstname.';
        $errflag = true;
    }
    // Check LASTNAME (sanitize only)
        if ((isset($_POST['lastname'])) && (!empty($_POST['lastname']))) {
        $lastname = filter_var($_POST['lastname'], FILTER_SANITIZE_STRING);
    } else {
        $errmsg_arr[] = 'Please put your lastname.';
        $errflag = true;
    }

    // Check EMAIL (sanitize and validate)
    if ((isset($_POST['email'])) && (!empty($_POST['email']))) {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errmsg_arr[] = 'Email seems to be invalid';
            $errflag = true;
        } else {
            // Check for duplicate email in DB
            $sql = "SELECT * FROM users WHERE email='$email'";
            $result = $bdd->query($sql);
            $numrows = $result->rowCount(); 
            $data = $result->fetch();
            if($result) {
                if($numrows > 0) {
                    if($data['userid'] != $_SESSION['userid']){
                    $errmsg_arr[] = 'Someone is already using that email address !';
                    $errflag = true;
                    }
                }
            }
        }
    } else {
        $errmsg_arr[] = 'Email missing';
        $errflag = true;
    }
    // Check phone
    if (isset($_POST['phone']) && !empty($_POST['phone'])) {
        $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
    } else {
        $phone = NULL;
    }
    // Check cellphone
    if (isset($_POST['cellphone']) && !empty($_POST['cellphone'])) {
        $cellphone = filter_var($_POST['cellphone'], FILTER_SANITIZE_STRING);
    } else {
        $cellphone = NULL;
    }
    // Check skype
    if (isset($_POST['skype']) && !empty($_POST['skype'])) {
        $skype = filter_var($_POST['skype'], FILTER_SANITIZE_STRING);
    } else {
        $skype = NULL;
    }
    // Check website
    if (isset($_POST['website']) && !empty($_POST['website'])) {
        if  (filter_var($_POST['website'], FILTER_VALIDATE_URL)) {
            $website = $_POST['website'];
        } else { // do not validate as url
            $errmsg_arr[] = 'Please input a valid URL !';
            $errflag = true;
        }
    } else {
        $website = NULL;
    }

    //If there are input validations, redirect back to the registration form
    if($errflag) {
        $_SESSION['errors'] = $errmsg_arr;
        session_write_close();
        header("location: ucp.php");
        exit();
    }

    // SQL for update profile
    $sql = "UPDATE users SET salt = :salt, 
        password = :password,
        email = :email,
        username = :username,
        firstname = :firstname,
        lastname = :lastname,
        phone = :phone,
        cellphone = :cellphone,
        skype = :skype,
        website = :website
        WHERE userid = :userid";
    $req = $bdd->prepare($sql);
    $result = $req->execute(array(
        'salt' => $salt,
        'password' => $passwordHash,
        'email' => $email,
        'username' => $username,
        'firstname' => $firstname,
        'lastname' => $lastname,
        'phone' => $phone,
        'cellphone' => $cellphone,
        'skype' => $skype,
        'website' => $website,
        'userid' => $_SESSION['userid']));
    if($result){
        $infomsg_arr[] = 'Profile updated !';
        $infoflag = true;
    } else {
        die('SQL failed');
    }
}else{ //end if result and numrow > 1
    $errmsg_arr[] = 'Enter your password to edit anything !';
    $errflag = true;
}
}// end if first form submitted

// DISPLAY PREFS
if (isset($_POST['theme']) && $_POST['theme'] != $_SESSION['prefs']['theme']) {
    if ($_POST['theme'] === 'default'){
        $new_theme = 'default';
    } elseif ($_POST['theme'] === 'l33t'){
        $new_theme = 'l33t';
    } else {
        die('Tampering data much ?');
    }
    // SQL to update theme 
    $sql = "UPDATE users SET theme = :new_theme WHERE userid = ".$_SESSION['userid'];
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'new_theme' => $new_theme
    ));
    // Put it in session
    $_SESSION['prefs']['theme'] = $new_theme;
    $infomsg_arr[] = 'Your theme is now : '.$new_theme;
    $infoflag = true;
}

// VIEW MODE
if (isset($_POST['display']) && $_POST['display'] != $_SESSION['prefs']['display']) {
    if ($_POST['display'] === 'default'){
        $new_display = 'default';
    } elseif ($_POST['display'] === 'compact'){
        $new_display = 'compact';
    } else {
        die('Tampering data much ?');
    }
    // SQL to update display mode
    $sql = "UPDATE users SET display = :new_display WHERE userid = ".$_SESSION['userid'];
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'new_display' => $new_display
    ));
    // Put it in session
    $_SESSION['prefs']['display'] = $new_display;
    $infomsg_arr[] = 'Your display mode is now : '.$new_display;
    $infoflag = true;
}

// ORDER
if (isset($_POST['order']) && $_POST['order'] != $_SESSION['prefs']['order']) {
    if ($_POST['order'] === 'date' || $_POST['order'] === 'id' || $_POST['order'] === 'title') {
        $new_order = $_POST['order'];
    } else {
        die('Tampering data much ?');
    }
    // SQL to update order
    $sql = "UPDATE users SET order_by = :new_order WHERE userid = ".$_SESSION['userid'];
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'new_order' => $new_order
    ));
    // Put it in session
    $_SESSION['prefs']['order'] = $new_order;
    $infomsg_arr[] = 'Items will now be ordered by '.$new_order;
    $infoflag = true;
}
// SORT
if (isset($_POST['sort']) && $_POST['sort'] != $_SESSION['prefs']['sort']) {
    if ($_POST['sort'] === 'asc') {
        $infomsg_arr[] = 'Items will be sorted with the oldest item first.';
        $new_sort = $_POST['sort'];
    } elseif ($_POST['sort'] === 'desc') {
        $infomsg_arr[] = 'Items will be sorted with the newest item first.';
        $new_sort = $_POST['sort'];
    } else {
        die('Tampering data much ?');
    }
    // SQL to update sort
    $sql = "UPDATE users SET sort_by = :new_sort WHERE userid = ".$_SESSION['userid'];
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'new_sort' => $new_sort
    ));
    // Put it in session
    $_SESSION['prefs']['sort'] = $new_sort;
    $infoflag = true;
}

// LIMIT
if (isset($_POST['limit']) && !empty($_POST['limit']) && $_POST['limit'] != $_SESSION['prefs']['limit']) {
    $filter_options = array(
        'options' => array(
            'default' => 15,
            'min_range' => 1,
            'max_range' => 50
        ));
    $new_limit = filter_var($_POST['limit'], FILTER_VALIDATE_INT, $filter_options);
    // SQL to update limit
    $sql = "UPDATE users SET limit_nb = :new_limit WHERE userid = ".$_SESSION['userid'];
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'new_limit' => $new_limit
    ));
    // Put it in session
    $_SESSION['prefs']['limit'] = $new_limit;
    $infomsg_arr[] = 'You will now see '.$new_limit.' items per page.';
    $infoflag = true;
}

// KEYBOARD SHORTCUTS
if (isset($_POST['shortcuts'])) {
    // check we got only one char
    $new_sc_create = substr($_POST['create'], 0, 1);
    $new_sc_edit = substr($_POST['edit'], 0, 1);
    $new_sc_submit = substr($_POST['submit'], 0, 1);
    // SQL
    $sql = "UPDATE users SET sc_create = :new_sc_create, sc_edit = :new_sc_edit, sc_submit = :new_sc_submit WHERE userid = ".$_SESSION['userid'];
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'new_sc_create' => $new_sc_create,
        'new_sc_edit' => $new_sc_edit,
        'new_sc_submit' => $new_sc_submit
    ));
    // put it in session
    $_SESSION['prefs']['shortcuts']['create'] = $new_sc_create;
    $_SESSION['prefs']['shortcuts']['edit'] = $new_sc_edit;
    $_SESSION['prefs']['shortcuts']['submit'] = $new_sc_submit;
    $infomsg_arr[] = 'Your shortcuts preferences have been updated.';
    $infoflag = true;
}



// EXPORT EXPERIMENTS
if (isset($_POST['export'])) {
    echo "This functionality is not yet implemented.";
}

// INFO BOX
if($errflag) {
    $_SESSION['errors'] = $errmsg_arr;
    session_write_close();
    header("location: ucp.php");
    exit();
}
elseif($infoflag){
    $_SESSION['infos'] = $infomsg_arr;
    session_write_close();
    header("location: ucp.php");
    // end infobox
} else {
    header("location: ucp.php");
}
