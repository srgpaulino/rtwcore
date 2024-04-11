<?php

namespace App\Repository\Logger;

use PDO;

use App\Exception\InvalidLogException;

use App\Handler\DB;

class DBLogger
{

    private $tfcpdo;
	private $db;
	private $shop;

    public $table = 'mc_logs';
	public $apiTable = 'mc_logs_api';
	protected $_data = array();

	private $logApiKey = '620C89CD2058CCED62A9784D3DD9B2E5838E7D85D902CD732A381C8E831B8945';

	private $message;
	private $status;
	private $errorType;
	private $order;

    public function __construct(PDO $db) 
    {
		$this->tfcpdo = $db;
    }

	public function setDB(PDO $db) 
	{
		$this->tfcpdo = $db;
	}

    public function setShop($shop) {
		$this->shop = $shop;
        $this->_data['shop_id'] = $shop;
    }

	public function startOrder($order) {
		$this->_data['provider'] = $order['provider'];
		$this->_data['content_name'] = $order['content_name'];
		$this->_data['user_id'] = $order['user_id'];
		$this->_data['user_name'] = $order['user_name'];
		$this->_data['product_id'] = $order['product_id'];
		$this->_data['product_name'] = $order['product_name'];
		$this->_data['order_id'] = $order['order_id'];
		$this->order = $order['order_id'];
	}
	
	public function message(string $message) {
		$this->_data['message'] = date('Y-m-d H:i:s') . ": " . $message;
	}

	public function status(string $status) {
		$this->_data['status'] = $status;
	}

	public function errorType( string $errorType) {
		$this->_data['error_type'] = $errorType;
	}

	public function setData(array $data) 
	{
		$this->_data = $data;
	}

	public function setOrderId(string $orderId) {
		$this->order = $orderId;
	}

	public function setLogId(int $logId)
	{
		$this->_data['log_id'] = $logId;
	}

	public function getData()
	{
		return $this->_data;
	}

	public function getLogId()
	{
		return $this->_data['log_id'];
	}

	public function logIt()
	{
		if(!empty($this->_data)) {
			if(isset($this->_data['log_id'])) {
				$logId = $this->_data['log_id'];
				unset($this->_data['log_id']);

				if(!empty($this->_data)) {

					sleep(1);

					// Changing logging so that certain columns are overwritten and not appended to - Logging updates 2017-07-10
                    $sql = "SELECT `id`, `message` FROM `mc_logs` WHERE `id`=:logId";
                    $stmt = $this->tfcpdo->prepare($sql);
                    $stmt->bindValue(":logId", trim($logId), PDO::PARAM_STR);
					$stmt->execute();

					$dbData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
					
                    if (!isset($dbData['id'])) {
                        throw new InvalidLogException("Log not found. ID: " . $logId);
                    }
                    
                    foreach($this->_data as $key => $val) {
						if(!empty($dbData[$key])) {
							$this->_data[$key] = $dbData[$key] . " \n " . $val;
						}
					}


					$this->update($this->table, $this->_data, 'id=' . $logId);
					$this->reset($logId);
				}
			} else {
    			if(empty($this->_data['created_at'])) {
    				$this->_data['created_at'] = date('Y-m-d H:i:s');
    			}
    			if(empty($this->_data['shop_id']) && !empty($this->shop)) {
    				$this->_data['shop_id'] = $this->shop;
    			}
    			
    			$log_id = $this->insert($this->table, $this->_data);
    			$this->reset($log_id);
			}
		}
		
		return $this;
	}
	
	public function reset($id = 0)
	{
		$this->_data = array();
		if(!empty($id)) {
			$this->_data['log_id'] = $id;
		}
		return $this;
	}
	
	public function __call($name, $args)
	{
		if(substr($name, 0, 3) == 'add') {
			$this->_data[strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', substr($name, 3)))] = $args[0];
		}
		
		return $this;
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
		$id = $this->tfcpdo->lastInsertId($table);
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

}