<?php

    class MyAPI {

        #Sensitive information removed for security reasons
        private $username = "";
        private $password = "";
        private $dsn = "";

        #Various stack overflow threads claim that emulation needs to be false for the execute statement to properly escape strings
        #
        private $options = [
            PDO::ATTR_EMULATE_PREPARES => false,                 #several stack overflow threads claim that emulation needs to be false for the prepare statement to be secure against sqlinjections and remove potential data type issues
            PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION #Will throw PDOExceptions on errors
        ];

        private $dbh;
        private $sth;

        function __construct() {
            $this->dbh = new PDO($this->dsn, $this->username, $this->password, $this->options);
        }

        function __destruct() {
            $this->dbh = null;
        }

        public function handleRequest() {

            if ($_SERVER['REQUEST_METHOD'] == 'GET') {

                if (isset($_GET['uid']) && isset($_GET['targetid'])) {

                    $myUID = trim($_GET['uid']);
                    $myTarget = trim($_GET['targetid']);

                    if (strlen($myUID) > 0 && strlen($myUID) <= 32 && strlen($myTarget) > 0 && strlen($myTarget) <= 32) {
                        
                        $this->sth = $this->dbh->prepare('Select * FROM tFollow WHERE uid = :myUID AND targetid = :myTarget');
                        $this->sth->execute(array(':myUID' => $myUID, ':myTarget' => $myTarget));
                        if ($result = $this->sth->fetch(PDO::FETCH_ASSOC)) {
                            header('content-type: application/json');
                            echo json_encode($result, JSON_PRETTY_PRINT);
                            return;
                        }
                        else {
                            http_response_code(204);
                            return;
                        }

                    }
                    
                }
                else if (isset($_GET['uid'])) {

                    $myUID = trim($_GET['uid']);

                    if (strlen($myUID) > 0 && strlen($myUID) <= 32) {

                        $this->sth = $this->dbh->prepare('SELECT targetid FROM tFollow WHERE uid=:myUID');
                        $this->sth->execute(array(':myUID' => $myUID));

                        if ($row = $this->sth->fetch(PDO::FETCH_ASSOC)) {

                            // https://phpdelusions.net/pdo#in cant put a varying array of query parts, so I need to make a string containing the same amount of question marks instead, and pass in the array data values afterwards
                            $result = [$row['targetid']];
                            while ($row = $this->sth->fetch(PDO::FETCH_ASSOC)) {
                                array_push($result, $row['targetid']);
                            }
                            $myValues = str_repeat('?,', count($result) - 1) . '?';
                            
                            $sql = "SELECT tImage.id, uid, filetype, description, date, tUser.name, tUser.profileFiletype from tImage INNER JOIN tUser ON tImage.uid = tUser.id WHERE uid IN ($myValues) ORDER BY date DESC";
                            $this->sth = $this->dbh->prepare($sql);
                            $this->sth->execute($result);

                            if ($row = $this->sth->fetch(PDO::FETCH_ASSOC)) {

                                $myJSON = new stdClass(); #removes PHP Warning:  Creating default object from empty value
                                $myJSON->feedID = $myUID;
                                $myJSON->images = array();
                                do {
                                    $tempJSON = new stdClass();
                                    $tempJSON->posterID = $row['uid'];
                                    $tempJSON->posterName = $row['name'];
                                    $tempJSON->profileFiletype = $row['profileFiletype'];
                                    $tempJSON->imageID = $row['id'];
                                    $tempJSON->filetype = $row['filetype'];
                                    $tempJSON->description = $row['description'];
                                    $tempJSON->date = $row['date'];
                                    array_push($myJSON->images, $tempJSON);
                                } while ($row = $this->sth->fetch(PDO::FETCH_ASSOC));

                                header('content-type: application/json');
                                echo json_encode($myJSON, JSON_PRETTY_PRINT);
                                return;

                            }
                            else {
                                http_response_code(204);
                                return;
                            }

                        }
                        else {
                            http_response_code(204);
                            return;
                        }

                    }

                }
                
            }
            else if ($_SERVER['REQUEST_METHOD'] == 'POST') {

                if (isset($_POST['uid']) && isset($_POST['targetid'])) {

                    $myUID = trim($_POST['uid']);
                    $myTarget = trim($_POST['targetid']);

                    if (strlen($myUID) > 0 && strlen($myUID) <= 32 && strlen($myTarget) > 0 && strlen($myTarget) <= 32 && strcmp($myUID, $myTarget) !== 0) {

                        $this->sth = $this->dbh->prepare('Select * FROM tFollow WHERE uid = :myUID AND targetid = :myTarget');
                        $this->sth->execute(array(':myUID' => $myUID, ':myTarget' => $myTarget));

                        if (!$result = $this->sth->fetch(PDO::FETCH_ASSOC)) { // If this follow does not already exist

                            $this->sth = $this->dbh->prepare('INSERT INTO tFollow (uid, targetid) VALUES (:myUID, :myTarget)');
                            $this->sth->execute(array(':myUID' => $myUID, ':myTarget' => $myTarget));
                            http_response_code(201);
                            $myJSON = new stdClass(); #removes PHP Warning:  Creating default object from empty value
                            $myJSON->id = $this->dbh->lastInsertId();
                            $myJSON->uid = $myUID;
                            $myJSON->targetID = $myTarget;
                            header('content-type: application/json');
                            echo json_encode($myJSON, JSON_PRETTY_PRINT);
                            return;

                        }
                        
                    }

                }
                
            }
            else if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {

                parse_str(file_get_contents('php://input'), $_DELETE);

                if (isset($_DELETE['uid']) && isset($_DELETE['targetid'])) {

                    $myUID = trim($_DELETE['uid']);
                    $myTarget = trim($_DELETE['targetid']);

                    if (strlen($myUID) > 0 && strlen($myUID) <= 32 && strlen($myTarget) > 0 && strlen($myTarget) <= 32) {
                        
                        $this->sth = $this->dbh->prepare('DELETE FROM tFollow WHERE uid = :myUID AND targetid = :myTarget');
                        $this->sth->execute(array(':myUID' => $myUID, ':myTarget' => $myTarget));
                        return;

                    }

                }

            }

            http_response_code(400);
            
        }

    }

    try {
        $myAPI = new MyAPI();
        $myAPI->handleRequest();
    } catch (PDOException $e) {
        http_response_code(500);
    } catch (Exception $e) {
        http_response_code(500);
    }

?>
