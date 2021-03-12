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

                if (isset($_GET['iid'])) {

                    $myIID = trim($_GET['iid']);

                    if (strlen($myIID) > 0 && strlen($myIID) <= 36) {

                        $this->sth = $this->dbh->prepare('SELECT tUser.id, tUser.name, tUser.profileFiletype, comment, date FROM tComment INNER JOIN tUser ON tComment.uid = tUser.id WHERE iid=:myIID ORDER BY date ASC');
                        $this->sth->execute(array(':myIID' => $myIID));

                        #sth->rowCount() seems to be discouraged, so i check if data exists on the data itself https://stackoverflow.com/questions/883365/row-count-with-pdo/883382#883382
                        if ($row = $this->sth->fetch(PDO::FETCH_ASSOC)) {
                            $myJSON = new stdClass(); #removes PHP Warning:  Creating default object from empty value
                            $myJSON->iid = $myIID;
                            $myJSON->comments = array();
                            do { #We already have the first row, so we will execute once first before getting the next row. do while is perfect for this
                                $tempJSON = new stdClass();
                                $tempJSON->uid = $row['id'];
                                $tempJSON->name = $row['name'];
                                $tempJSON->profileFiletype = $row['profileFiletype'];
                                $tempJSON->comment = $row['comment'];
                                $tempJSON->date = $row['date'];
                                array_push($myJSON->comments, $tempJSON);
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

                if (isset($_POST['uid']) && isset($_POST['iid']) && isset($_POST['comment'])) {

                    $myUID = trim($_POST['uid']);
                    $myIID = trim($_POST['iid']);
                    $myComment = trim($_POST['comment']);

                    if (strlen($myUID) > 0 && strlen($myUID) <= 32 && strlen($myIID) > 0 && strlen($myIID) <= 36 && strlen($myComment) > 0) {

                        $this->sth = $this->dbh->prepare('INSERT INTO tComment (uid, iid, comment) VALUES (:myUID, :myIID, :myComment)');
                        $this->sth->execute(array(':myUID' => $myUID, ':myIID' => $myIID, ':myComment' => $myComment));
                        http_response_code(201);
                        $myJSON = new stdClass(); #removes PHP Warning:  Creating default object from empty value
                        $myJSON->id = $this->dbh->lastInsertId();
                        $myJSON->uid = $myUID;
                        $myJSON->iid = $myIID;
                        $myJSON->comment = $myComment;
                        header('content-type: application/json');
                        echo json_encode($myJSON, JSON_PRETTY_PRINT);
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
