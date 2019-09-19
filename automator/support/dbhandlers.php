<?php

namespace Automator\Support;
use PDO;

class DbHandlers
{
    //PROPERTIES
    private $conn;

    public $tableName = '';
    public $fieldSel = '';
    public $limitOffset = '';
    public $returnDebug = '';
    public $oneEqualOne = '';
    public $condtionArr = array();
    private $sqlQry = '';
    public $orderby = '';
    public $orderprecedence = '';
    private $tableRow = array();
    private $tableAssoc = array();
    private $inQuery = '';

    private $Qres = false;
    private $errLine = '';
    private $errFile = '';
    private $errMsg = '';

    public $returnVal = array();

    //public function DbHandlers(){}

    public function __constructor()
    {
        // ...
    }

    //CONNECTION TO DATABASE
    private function do_conn()
    {
        global $servername, $dbname, $username, $password;
        try {
            $this->conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $this->errLine = $e->getLine();
            $this->errFile = $e->getFile();
            $this->errMsg = $e->getMessage();
            $this->errorLogg();
        }
    }

    //TO EXECUTE ANY QUERY PASSED TO IT
    public function executeQuery($in_query)
    {
        $this->do_conn();
        try {
            if ($in_query != '') {
                $stmt = $this->conn->prepare($in_query);
                $this->Qres = $stmt->execute();
            }
        } catch (Exception $e) {
            $this->errLine = $e->getLine();
            $this->errFile = $e->getFile();
            $this->errMsg = $e->getMessage();
            $this->errorLogg();
        }

        return $this->Qres;
        $this->conn = null;
    }

    //TO DESCRIBE A TABLE
    private function desc_table($tablename)
    {
        $this->do_conn();
        try {
            if ($tablename != '') {
                $stmt = $this->conn->prepare("DESCRIBE $tablename ");
                $stmt->execute();
                $each_rec = $stmt->fetchAll(PDO::FETCH_ASSOC);

                return $each_rec;
            }
        } catch (Exception $e) {
            $this->errLine = $e->getLine();
            $this->errFile = $e->getFile();
            $this->errMsg = $e->getMessage();
            $this->errorLogg();
        }
        $this->conn = null;
    }

    //TO LIST ALL THE TABLES IN A DATABASE
    public function show_dbTables($db=null)
    {
        $this->do_conn();
        try {
            $stmt = $this->conn->prepare("SHOW TABLES");
            $stmt->execute();
            $each_rec = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $each_rec;
        } catch (Exception $e) {
            $this->errLine = $e->getLine();
            $this->errFile = $e->getFile();
            $this->errMsg = $e->getMessage();
            $this->errorLogg();
        }
        $this->conn = null;
    }

    public function tableDesc($tablename)
    {
        return $this->desc_table($tablename);
    }

    public function arraylog($title, $arrayname)
    {
        if (!empty($arrayname)) {
            echo " <pre> $title<br>";
            print_r($arrayname);
            echo ' </pre><br>';
        } else {
            echo "<br />$title<br />";
        }
    }

    //ERROR LOG
    public function errorLogg()
    {
        try {
            $errorMsg = "Error on line $this->errLine in $this->errMsg : \n $this->errMsg \n\n ";
            $myfile = fopen('error/errorlog.txt', 'a+');
            fwrite($myfile, $errorMsg);
            fclose($myfile);
        } catch (Exception $e) {
            $this->errLine = $e->getLine();
            $this->errFile = $e->getFile();
            $this->errMsg = $e->getMessage();
            $this->errorLogg();
        }
    }

    public function writeToFile()
    {
        if (($this->fcontent == '') || ($this->fileName == '')) {
            return 0;
        }
        try {
            $myfile = fopen($this->fileName, 'a');
            $riteAction = fwrite($myfile, $this->fcontent);
            if ($riteAction) {
                return true;
            }
            if (!$riteAction) {
                return false;
            }
            fclose($myfile);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    //PRIMARY, UNIQUE AND INDEX KEY
    public function tabKeys($tabArr, $kyType = '')
    {
        $priKey = '';
        if ($kyType == '') {
            $kyType = 'PRI';
        }
        $cnt = 0;
        foreach ($tabArr as $cols) {
            if ($cols['Key'] == "$kyType") {
                if ($cnt == 0) {
                    $priKey = $cols['Field'];
                }
                if ($cnt > 0) {
                    $priKey = ','.$cols['Field'];
                }
            }
            ++$cnt;
        }

        return $priKey;
    }

    //RECORD INSERT
    public function saveRec($inn_arr = array(), $tablename = '', $showdebug = false)
    {
        $this->do_conn();
        $paramArr = array();
        try {
            if (!empty($inn_arr)) {
                $tab_desc = ($tablename != '') ? $this->desc_table($tablename) : array();
                if (!empty($tab_desc)) {
                    $this->sqlQry .= " INSERT INTO $tablename ( ";
                    $rt = 0;
                    foreach ($inn_arr as $ky => $each_elm) {
                        if ($rt === 0) {
                            $this->sqlQry .= "$ky";
                        }
                        if ($rt !== 0) {
                            $this->sqlQry .= ", $ky";
                        }
                        ++$rt;
                    }
                    $this->sqlQry .= ' ) VALUES ( ';
                    $tr = 0;
                    foreach ($inn_arr as $ky => $each_elm) {
                        if ($tr === 0) {
                            $this->sqlQry .= ":$ky";
                        }
                        if ($tr !== 0) {
                            $this->sqlQry .= ", :$ky";
                        }
                        ++$tr;
                    }
                    $this->sqlQry .= ' ) ';
                    $stmt = $this->conn->prepare($this->sqlQry);
                    foreach ($inn_arr as $ky => $each_elm) {
                        $stmt->bindValue(":$ky", $each_elm);
                        $paramArr[$ky] = $each_elm;
                    }
                    $this->Qres = $stmt->execute();
                }
            }
        } catch (Exception $e) {
            $this->errLine = $e->getLine();
            $this->errFile = $e->getFile();
            $this->errMsg = $e->getMessage();
            $this->errorLogg();
        }

        $this->returnVal['errLine'] = $this->errLine;
        $this->returnVal['errFile'] = $this->errFile;
        $this->returnVal['errMsg'] = $this->errMsg;
        $this->returnVal['actRes'] = $this->Qres;
        $this->returnVal['resArr'] = array();
        if ($showdebug) {
            $this->returnVal['query'] = $this->sqlQry;
            $this->returnVal['param'] = $paramArr;
        }

        return $this->returnVal;
        $this->conn = null;
    }

    //RECORD UPDATE
    public function updateRec($inn_arr = array(), $tablename = '', $otherCond = array(), $showdebug = false)
    {
        $this->do_conn();
        $paramArr = array();
        try {
            if (!empty($inn_arr)) {
                $tab_desc = ($tablename != '') ? $this->desc_table($tablename) : array();
                if (!empty($tab_desc)) {
                    $this->sqlQry .= " UPDATE $tablename SET ";
                    $rt = 0;
                    foreach ($inn_arr as $ky => $each_elm) {
                        if ($rt === 0) {
                            $this->sqlQry .= " $ky = '$each_elm' ";
                        }
                        if ($rt !== 0) {
                            $this->sqlQry .= " , $ky = '$each_elm' ";
                        }
                        ++$rt;
                    }

                    $priKey = '';
                    $priValue = '';
                    $priKey = $this->tabKeys($tab_desc, 'PRI');
                    foreach ($inn_arr as $ky => $each_elm) {
                        if ($ky == $priKey) {
                            $priValue = $each_elm;
                        }
                    }

                    $paramArr[] = $priValue;
                    $this->sqlQry .= " WHERE $priKey = ? ";

                    if (!empty($otherCond)) {
                        foreach ($otherCond as $ck => $otherC) {
                            $this->sqlQry .= " AND $ck = ? ";
                            $paramArr[] = $otherC;
                        }
                    }
                    $stmt = $this->conn->prepare($this->sqlQry);
                    $this->Qres = $stmt->execute($paramArr);
                }
            }
        } catch (Exception $e) {
            $this->errLine = $e->getLine();
            $this->errFile = $e->getFile();
            $this->errMsg = $e->getMessage();
            $this->errorLogg();
        }

        $this->returnVal['errLine'] = $this->errLine;
        $this->returnVal['errFile'] = $this->errFile;
        $this->returnVal['errMsg'] = $this->errMsg;
        $this->returnVal['actRes'] = $this->Qres;
        $this->returnVal['resArr'] = array();
        if ($showdebug) {
            $this->returnVal['query'] = $this->sqlQry;
            $this->returnVal['param'] = $paramArr;
        }

        return $this->returnVal;
        $this->conn = null;
    }

    public function fetchRec($condtionArr = array(), $paramArr = array(), $showdebug = false)
    {
        $this->do_conn();
        $param2bind = array();
        try {
            if (!empty($paramArr)) {
                $this->condtionArr = (!empty($condtionArr)) ? $condtionArr : array();
                $this->tableName = ((isset($paramArr['tableName'])) && ($paramArr['tableName'] != '')) ? $paramArr['tableName'] : '';
                $this->fieldSel = ((isset($paramArr['fldsel'])) && ($paramArr['fldsel'] != '')) ? $paramArr['fldsel'] : '*';
                $this->limitOffset = ((isset($paramArr['limitOffset'])) && ($paramArr['limitOffset'] != '')) ? $paramArr['limitOffset'] : '';
                $this->returnDebug = ((isset($paramArr['returnDebug'])) && ($paramArr['returnDebug'] != '')) ? $paramArr['returnDebug'] : '';
                $this->oneEqualOne = ((isset($paramArr['oneEqualsOne'])) && ($paramArr['oneEqualsOne'] == true)) ? ' (1=1) ' : '';
                $this->orderby = ((isset($paramArr['orderby'])) && ($paramArr['orderby'] != '')) ? $paramArr['orderby'] : '';
                $this->orderprecedence = ((isset($paramArr['orderprecedence'])) && ($paramArr['orderprecedence'] != '')) ? $paramArr['orderprecedence'] : '';
                if ($this->tableName != '') {
                    $this->sqlQry = "SELECT $this->fieldSel FROM $this->tableName WHERE $this->oneEqualOne ";
                    if (!empty($this->condtionArr)) {
                        $icnt = 0;
                        foreach ($this->condtionArr as $ky => $valu) {
                            if ($valu == '') {
                                $this->sqlQry .= '';
                            } else {
                                $spltky = explode('%', $ky);
                                $ky = $spltky[1];
                                if ($this->oneEqualOne != '' || $icnt != 0) {
                                    $this->sqlQry .= " $ky ";
                                }
                                $this->sqlQry .= ($this->oneEqualOne != '') ? ' ( ' : ' ';
                                if ($this->oneEqualOne == '') {
                                    $this->sqlQry .= ' ( ';
                                }
                                $jcnt = 0;
                                foreach ($valu as $kt => $valt) {
                                    $splky = explode('=>', $kt);
                                    $spval = explode('~', $valt);
                                    $op = $spval[0];
                                    $sval = $spval[1];
                                    if (count($splky) == 1) {
                                        $this->sqlQry .= " $kt $op ? ";
                                    } elseif (count($splky) > 1) {
                                        $nwcond = $splky[0];
                                        $nwkey = $splky[1];
                                        if ($jcnt == 0) {
                                            $this->sqlQry .= " $nwkey $op ? ";
                                        } elseif ($jcnt > 0) {
                                            $this->sqlQry .= " $nwcond $nwkey $op ? ";
                                        }
                                    }
                                    if ($op == 'LIKE') {
                                        $param2bind[] = "%$sval%";
                                    } else {
                                        $param2bind[] = $sval;
                                    }
                                    ++$jcnt;
                                }
                                $this->sqlQry .= ' ) ';
                                ++$icnt;
                            }
                        }

                        if ($this->orderby != '') {
                            $this->sqlQry .= " ORDER BY $this->orderby ";
                        }
                        if ($this->orderprecedence != '') {
                            $this->sqlQry .= " $this->orderprecedence ";
                        }

                        //LIMIT AND OFFSET START HERE
                        $limit = '';
                        $offset = '';
                        if ($this->limitOffset != '') {
                            $offLim = explode(',', $this->limitOffset);
                            $limit = (isset($offLim[0])) ? $offLim[0] : '';
                            $offset = (isset($offLim[1])) ? $offLim[1] : '';
                        }
                        $limitQry = ($limit != '') ? " LIMIT $limit " : '';
                        $ffsetQry = ($offset != '') ? " OFFSET $offset " : '';
                        $this->sqlQry .= $limitQry;
                        $this->sqlQry .= $ffsetQry;
                        //LIMIT AND OFFSET END HERE

                        //PREPARING
                        $stmt = $this->conn->prepare($this->sqlQry);
                        $this->Qres = $stmt->execute($param2bind);
                        $result = ($this->Qres) ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array();
                    }
                }
            }
        } catch (Exception $e) {
            $this->errLine = $e->getLine();
            $this->errFile = $e->getFile();
            $this->errMsg = $e->getMessage();
            $this->errorLogg();
        }

        $this->returnVal['errLine'] = $this->errLine;
        $this->returnVal['errFile'] = $this->errFile;
        $this->returnVal['errMsg'] = $this->errMsg;
        $this->returnVal['actRes'] = $this->Qres;
        $this->returnVal['resArr'] = $result;
        if ($showdebug) {
            $this->returnVal['query'] = $this->sqlQry;
            $this->returnVal['param'] = $param2bind;
        }

        return $this->returnVal;
        $this->conn = null;
    }

    //RECORD UPDATE
    public function deleteRec($inn_arr = array(), $tablename = '', $showdebug = false)
    {
        $this->do_conn();
        try {
            if (!empty($inn_arr)) {
                $tab_desc = ($tablename != '') ? $this->desc_table($tablename) : array();
                if (!empty($tab_desc)) {
                    $this->sqlQry = " DELETE FROM $tablename WHERE ";
                    $priKey = '';
                    $priValue = '';
                    $priKey = $this->tabKeys($tab_desc, 'PRI');

                    foreach ($inn_arr as $ky => $each_elm) {
                        if ($ky != 'returnDebug') {
                            if ($ky == $priKey) {
                                $priValue = $each_elm;
                            }
                        }
                    }
                    $condParam[] = $priValue;
                    $this->sqlQry .= " $priKey = ? ";

                    $rt = 0;
                    foreach ($inn_arr as $ky => $each_elm) {
                        if ($ky != 'returnDebug') {
                            if ($ky != $priKey) {
                                if ($priKey == '' && $rt == 0) {
                                    $this->sqlQry .= " $ky = ? ";
                                }
                                if ($priKey == '' && $rt > 0) {
                                    $this->sqlQry .= " AND $ky = ? ";
                                }
                                if ($priKey != '') {
                                    $this->sqlQry .= " AND $ky = ? ";
                                }
                                $condParam[] = $each_elm;
                            }
                            ++$rt;
                        }
                    }
                    $stmt = $this->conn->prepare($this->sqlQry);
                    $this->Qres = $stmt->execute($condParam);
                }
            }
        } catch (Exception $e) {
            $this->errLine = $e->getLine();
            $this->errFile = $e->getFile();
            $this->errMsg = $e->getMessage();
            $this->errorLogg();
        }

        $this->returnVal['errLine'] = $this->errLine;
        $this->returnVal['errFile'] = $this->errFile;
        $this->returnVal['errMsg'] = $this->errMsg;
        $this->returnVal['actRes'] = $this->Qres;
        $this->returnVal['resArr'] = array();
        if ($showdebug) {
            $this->returnVal['query'] = $this->sqlQry;
            $this->returnVal['param'] = $condParam;
        }

        return $this->returnVal;
        $this->conn = null;
    }

    public function getRows($q)
    {
        $this->do_conn();
        $this->tableRow = (array) null;
        $this->inQuery = $q;
        $stmt = $this->conn->prepare($this->inQuery);
        $stmt->execute();
        $this->tableRow = $stmt->fetchAll(PDO::FETCH_NUM);

        return $this->tableRow;
    }

    public function getRowAssoc($q)
    {
        $this->do_conn();
        $this->tableAssoc = (array) null;
        $this->inQuery = $q;
        $stmt = $this->conn->prepare($this->inQuery);
        $stmt->execute();
        $this->tableAssoc = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->tableAssoc;
    }
}
