<?php

namespace App\Handler\Loggers;

use PDO;

use App\Exception\InvalidLogException;

use App\Handler\DB;

class ShopLogger
{

    private $tfcpdo;
	private $db;
	private $shop;

    public $table = 'mc_logs';
	public $apiTable = 'mc_logs_api';
	protected $_data = array();

	private $logApiKey = '620C89CD2058CCED62A9784D3DD9B2E5838E7D85D902CD732A381C8E831B8945';

    public function __construct(PDO $tfcpdo, $shop) 
    {
        $this->tfcpdo = $tfcpdo;
        $this->shop = $shop;
		$db = new DB($tfcpdo);
    }
	
	public function logIt()
	{
		if(!empty($this->_data)) {
			if(isset($this->_data['log_id'])) {
				$logId = $this->_data['log_id'];
				unset($this->_data['log_id']);
				if(!empty($this->_data)) {
					// Changing logging so that certain columns are overwritten and not appended to - Logging updates 2017-07-10
                    $sql = "SELECT id, message FROM mc_logs WHERE id=:logId";
                    $stmt = $this->tfcpdo->prepare($sql);
                    $stmt->bindValue(":logId", $logId, PDO::PARAM_STR);
					$dbData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
					
                    if (count($dbData) <= 0) {
                        throw new InvalidLogException("Log not found");
                    }
                    
                    foreach($this->_data as $key => $val) {
						if(!empty($dbData[$key])) {
							$this->_data[$key] = $dbData[$key] . " \n " . $val;
						}
					}


					$this->db->update($this->table, $this->_data, 'id=' . $logId);
					$this->reset($logId);
				}
			} else {
    			if(empty($this->_data['created_at'])) {
    				$this->_data['created_at'] = date('Y-m-d H:i:s');
    			}
    			if(empty($this->_data['shop_id']) && !empty($shop)) {
    				$this->_data['shop_id'] = $shop;
    			}
    			
    			$log_id = $this->db->insert($this->table, $this->_data);
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

}