<?php

namespace App\Repository\Logger;

use PDO;

use PDOException;

class Database
{
	
    private $db;

	static protected $master = null;
	static protected $slave = null;
	static protected $sth;
	static protected $isAdminZone = false;
	static protected $queryLog = array();
	static protected $_queryTime = 0;


    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
	 * Prepare $bind to array or null for use pdo statment execute
	 * 
	 * @param mixed $bind. May be boolean, string, or array
	 * @return mixed
	 */
	private function getBind($bind)
	{
		if(!is_bool($bind)) {
			if(!is_array($bind)) {
				$bind = array($bind);
			}				 
		} else {
			$bind = null;
		}
		return $bind;
	}
	
	/**
	* Inserts a table row with specified data.
	*
	* @param mixed $table The table to insert data into.
	* @param array $bind Column-value pairs.
	* @return int Last insert Id
	*/
	public function insert($table, array $bind)
	{
		$start = microtime(true);
		$rez = null;

		// extract col names from the array keys
		$cols = array();
		$vals = array();
		$valsIns = array();
	
		foreach($bind as $col => $val) {
			$cols[] = '`' . $col . '`';
			$valsIns[] = ':' . $col;
		}
		
		// build the statement
		$sql = "INSERT INTO `" .  $table . "` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $valsIns) . ")";

		// execute the statement and return the number of last insert id
		//$a->setAttribute(PDO_MYSQL_ATTR_USE_BUFFERED_QUERY, 0);			
		$stmt = $this->tfcpdo->prepare($sql);
		$bind = $this->getBind($bind);
        try{
            $stmt->execute($bind);
        } catch (PDOException $e){
            return $e;
        }
		$id = $stmt->lastInsertId($table);
		return $id;	
	}
	
	/**
	* Updates a table with specified data.
	*
	* @param mixed $table The table to insert data into.
	* @param array $bind Column-value pairs.
	* @param string $where where condition.
	* @return int The number of affected rows.
	*/
	public function update($table, array $bind, $where = false)
	{
		$start = microtime(true);
		$rez = null;

		// Build "col = ?" pairs for the statement
		$set = array();
		$newBind = array();
		foreach($bind as $col => $val) {
			$set[] = '`' . $col . '` = ?';
			$newBind[] = $val;
		}
			
		// Build the UPDATE statement
		$sql = "UPDATE `" . $table . "` SET " . implode(', ', $set) . (($where) ? " WHERE $where" : '');

		// Execute the statement and return the number of affected rows
		$stmt = $this->tfcpdo->prepare($sql);
		$rez = $stmt->execute($newBind);
		
		return $rez;
	}
	
}

