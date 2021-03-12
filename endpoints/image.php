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

                if (isset($_GET['uid']) && strlen(trim($_GET['uid'])) > 0 && strlen(trim($_GET['uid'])) <= 32) {

                    $myID = trim($_GET['uid']);

                    if (strlen($myID) > 0 && strlen($myID) <= 32) {

                        $this->sth = $this->dbh->prepare('SELECT tImage.id, uid, filetype, description, date, tUser.name, tUser.profileFiletype from tImage INNER JOIN tUser ON tImage.uid = tUser.id WHERE uid=:myID ORDER BY date DESC');
                        $this->sth->execute(array(':myID' => $myID));

                        #sth->rowCount() seems to be discouraged, so i check if data exists on the data itself https://stackoverflow.com/questions/883365/row-count-with-pdo/883382#883382
                        if ($row = $this->sth->fetch(PDO::FETCH_ASSOC)) {
                            $myJSON = new stdClass(); #removes PHP Warning:  Creating default object from empty value
                            $myJSON->uid = $row['uid'];
                            $myJSON->name = $row['name'];
                            $myJSON->profileFiletype = $row['profileFiletype'];
                            $myJSON->images = array();
                            do { #We already have the first row, so we will execute once first before getting the next row. do while is perfect for this
                                $tempJSON = new stdClass();
                                $tempJSON->id = $row['id'];
                                $tempJSON->filetype = $row['filetype'];
                                $tempJSON->description = $row['description'];
                                $tempJSON->date = $row['date'];
                                array_push($myJSON->images, $tempJSON);
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

                if (isset($_POST['uid']) && isset($_FILES['file']) && isset($_POST['description'])) {

                    $filesize = filesize($_FILES['file']['tmp_name']);

                    if ($filesize < 500000) {

                        //get mime type, could use $_FILES['file']["type"], but that checks the type received by javascript and might be less secure.
                        //mime_content_type() is another alternative, but it seems to be deprecated, so I use finfo instead.
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $myMime = finfo_file($finfo, $_FILES['file']['tmp_name']);
                        finfo_close($finfo);

                        if($myMime == "image/png" || $myMime == "image/jpeg") {

                            $temp = explode("/", $myMime);
                            $myFiletype = "." . end($temp);
                            $myUID = trim($_POST['uid']);
                            $myDescription = trim($_POST['description']);
                            $myID = $this->getGuid();

                            if ($myID != false && strlen($myUID) > 0 && strlen($myUID) <= 32 && strlen($myDescription) > 0) {

                                // check if file available was uploaded through an HTTP POST and is successfully stored
                                if (move_uploaded_file($_FILES['file']['tmp_name'], '../images/uploaded/' . $myID . $myFiletype)) { 

                                    $this->sth = $this->dbh->prepare('INSERT INTO `tImage` (`id`, `uid`, `filetype`, `description`) VALUES (:myID, :myUID, :myFiletype, :myDescription)');
                                    $this->sth->execute(array(':myID' => $myID, ':myUID' => $myUID, ':myFiletype' => $myFiletype, ':myDescription' => $myDescription));
                                    http_response_code(201);
                                    $myJSON = new stdClass(); #removes PHP Warning:  Creating default object from empty value
                                    $myJSON->id = $myID;
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

            http_response_code(400);
        }

        // I would have liked to use com_create_guid(), but it is only usable on windows based servers
        // This is an alternative found on http://guid.us/GUID/PHP
        private function getGUID() {
            if (function_exists('com_create_guid')){
                return com_create_guid();
            }else{
                mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
                $charid = strtoupper(md5(uniqid(rand(), true)));
                $hyphen = chr(45);// "-"
                $uuid = 
                    substr($charid, 0, 8).$hyphen
                    .substr($charid, 8, 4).$hyphen
                    .substr($charid,12, 4).$hyphen
                    .substr($charid,16, 4).$hyphen
                    .substr($charid,20,12);
                return $uuid;
            }
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
