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

                if (isset($_GET['id'])) {

                    $myID = trim($_GET['id']);

                    if (strlen($myID) > 0 && strlen($myID) <= 32) {

                        $this->sth = $this->dbh->prepare('SELECT * FROM tUser WHERE id=:myID');
                        $this->sth->execute(array(':myID' => $myID));
                        
                        #sth->rowCount() seems to be discouraged, so i check if data exists on the data itself https://stackoverflow.com/questions/883365/row-count-with-pdo/883382#883382
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
                else if (isset($_GET['name'])) {

                    $myName = trim($_GET['name']);

                    if (strlen($myName) > 0 && strlen($myName) <= 255) {

                        $this->sth = $this->dbh->prepare('SELECT * FROM tUser WHERE name LIKE :myName');
                        $this->sth->execute(array(':myName' => '%' . $myName . '%'));

                        if ($row = $this->sth->fetch(PDO::FETCH_ASSOC)) {

                            $myJSON = new stdClass(); #removes PHP Warning:  Creating default object from empty value
                            $myJSON->users = array();

                            do { #We already have the first row, so we will execute once first before getting the next row. do while is perfect for this
                                $tempJSON = new stdClass();
                                $tempJSON->id = $row['id'];
                                $tempJSON->name = $row['name'];
                                $tempJSON->profileFiletype = $row['profileFiletype'];
                                array_push($myJSON->users, $tempJSON);
                            } while ($row = $this->sth->fetch(PDO::FETCH_ASSOC)); #fetchall() is reported to have memory issues on large datasets, and it is recommended to fetch one by one instead.

                            header('content-type: application/json');
                            echo json_encode($myJSON, JSON_PRETTY_PRINT);
                            return;
                        }
                        else {
                            http_response_code(204);
                            return;
                        }

                    }

                }
                
            }
            else if ($_SERVER['REQUEST_METHOD'] == 'POST') {

                if (isset($_POST['id']) && isset($_POST['name']) && isset($_POST['img'])) {

                    $myID = trim($_POST['id']);
                    $myName = trim($_POST['name']);

                    if (strlen($myID) > 0 && strlen($myID) <= 32 && strlen($myName) > 0 && strlen($myName) <= 255) {

                        $tempImage = tempnam(sys_get_temp_dir(), $myID);
                        file_put_contents($tempImage, file_get_contents($_POST['img']));
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $myMime = finfo_file($finfo, $tempImage);
                        finfo_close($finfo);
                        $filesize = filesize($tempImage);
                        unlink($tempImage);

                        if ($filesize < 500000) {

                            if($myMime == "image/png" || $myMime == "image/jpeg") {

                                $temp = explode("/", $myMime);
                                $myFiletype = "." . end($temp);

                                if (copy($_POST['img'], '../images/profile/' . $myID . $myFiletype)) {
                                
                                    $this->sth = $this->dbh->prepare('INSERT INTO tUser (id, name, profileFiletype) VALUES (:myID, :myName, :myFiletype)');
                                    $this->sth->execute(array(':myID' => $myID, ':myName' => $myName, ':myFiletype' => $myFiletype));
                                    
                                    http_response_code(201);
                                    $myJSON = new stdClass(); #removes PHP Warning:  Creating default object from empty value
                                    $myJSON->id = $myID;
                                    $myJSON->name = $myName;
                                    $myJSON->filetype = $myFiletype;

                                    header('content-type: application/json');
                                    echo json_encode($myJSON, JSON_PRETTY_PRINT);

                                    return;

                                }

                            }   

                        }

                    }

                }
                
            }
            else if ($_SERVER['REQUEST_METHOD'] == 'PUT') {

                parse_str(file_get_contents('php://input'), $_PUT);

                if (isset($_PUT['uid']) && isset($_PUT['name']) && isset($_PUT['image'])) {

                    $myUID = trim($_PUT['uid']);
                    $myName = trim($_PUT['name']);

                    if (strlen($myUID) > 0 && strlen($myUID) <= 32 && strlen($myName) > 0 && strlen($myName) <= 255) {

                        $myData = base64_decode($_PUT['image']);
                        $tempImage = tempnam(sys_get_temp_dir(), $myUID);
                        file_put_contents($tempImage, $myData);
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $myMime = finfo_file($finfo, $tempImage);
                        finfo_close($finfo);
                        $filesize = filesize($tempImage);
                        unlink($tempImage);

                        if ($filesize < 500000) {

                            if($myMime == "image/png" || $myMime == "image/jpeg") {

                                $temp = explode("/", $myMime);
                                $myFiletype = "." . end($temp);

                                if (file_put_contents("../images/profile/" . $myUID . $myFiletype, $myData)) {

                                    $this->sth = $this->dbh->prepare('UPDATE tUser SET name=:myName, profileFiletype=:myFiletype WHERE id=:myUID');
                                    $this->sth->execute(array(':myName' => $myName, ':myFiletype' => $myFiletype, ':myUID' => $myUID));
                                    http_response_code(200);
                                    $myJSON = new stdClass(); #removes PHP Warning:  Creating default object from empty value
                                    $myJSON->id = $myUID;
                                    $myJSON->name = $myName;
                                    $myJSON->filetype = $myFiletype;

                                    header('content-type: application/json');
                                    echo json_encode($myJSON, JSON_PRETTY_PRINT);
                                    return;

                                }

                            }

                        }

                    }

                }

            }

            http_response_code(200);
            
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
