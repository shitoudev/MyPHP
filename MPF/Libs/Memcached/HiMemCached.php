<?php
/**
 * @file HiMemCached.class.php
 * @desc 簡單的將memcached class封裝
 * @author tml
 * @version 2009-02-20
 */

class HiMemCached {

	private $_memcached = null;
	private $_config;
	private $_err_handle;

	public function __construct($config, $err_handle='web') {
		$this->_config = $config;
		$this->_err_handle = $err_handle;	
	}

	public function __destruct() {
	}

	public function set($key , $value, $expire=0) {
		if (!$this->connect()) {
			return false;
		}
		return $this->_memcache->set($key , $value, $expire);
	}

	public function get($key) {
	    if (!$this->connect()) {
            return false;
        }
        return $this->_memcache->get($key);
	}

	public function delete($key) {
	    if ($this->connect()) {
            $this->_memcache->delete($key);
            return true;
        }
        return false;
	}
	
	public function fetchAll($key_arrary){
		if ($this->connect()) {
            $this->_memcache->getDelayed($key_arrary, true);			
            return $this->_memcache->fetchAll();
        }//fi
        return false;
	}
	
	public function setMulti($items, $expire=0){
		if ($this->connect()) {
            return $this->_memcache->setMulti($items, $expire);
        }//fi
        return false;
	}
	
	public function getMulti($key_arrary,$cas=''){
		if ($this->connect()) {
            return $this->_memcache->getMulti($key_arrary,$cas);
        }//fi
        return false;
	}
	
	public function incr($key, $offset=1){
		if ($this->connect()) {
            return $this->_memcache->increment($key, $offset);
        }//fi
        return false;		
	}
	
	public function decr($key, $offset=1){
		if ($this->connect()) {
            return $this->_memcache->decrement($key, $offset);
        }//fi
        return false;		
	}
		
	private function connect() {
		if (null !== $this->_memcache) { return true; }
		try {
			$this->_memcache = new Memcached();
			foreach($this->_config as $server){
				//Controls the use of a persistent connection. Default to TRUE. 				
				$this->_memcache->addServer($server['host'], $server['port'], true);	
			}//foreach
			return true;
		} catch (Exception $e) {
			$this->_memcache = null;
		}//catche
		return false;
	}
}
?>