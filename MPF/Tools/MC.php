<?php
/**
//store the variable
MC::set('key', 'abc');

//increment/decrement the integer value
MC::increment('key');
MC::decrement('key');

//fetch the value by it's key
echo MC::get('key');

//delete the data
echo MC::delete('key');

//Clear the cache memory on all servers
MC::flush();

MC::replace() and Cache::add are implemented also.

*/

class MC {

	protected $_memcache;
	static $instance;

	/**
 * Singleton to call from all other functions
 */
	static function singleton(){
		self::$instance = new MC();
		return self::$instance;
	}

	/**
 * Accepts the 2-d array with details of memcached servers
 *
 * @param array $servers
 */
	protected function __construct(){
		$servers = unserialize(MY_MEMCACHE_HOSTS);
		if (!$servers){
			trigger_error('No memcache servers to connect',E_USER_WARNING);
		}
		$this->_memcache = new Memcache();
//		for ($i = 0, $n = count($servers); $i < $n; ++$i) {
//			$this->_memcache->addServer(key($servers[$i]), current($servers[$i]), true);
//		}
		foreach ($servers as $server){
			$this->_memcache->addServer($server['host'],$server['port'],true);
		} 
	}

	/**
 * Clear the cache
 *
 * @return void
 */
	static function flush() {
		self::singleton()->_memcache->flush();
	}

	/**
 * Returns the value stored in the memory by it's key
 *
 * @param string $key
 * @return mix
 */
	static function get($key) {
		return self::singleton()->_memcache->get($key);
	}

	/**
 * Store the value in the memcache memory (overwrite if key exists)
 *
 * @param string $key
 * @param mix $var
 * @param bool $compress
 * @param int $expire (seconds before item expires)
 * @return bool
 */
	static function set($key, $var, $expire=0, $compress=1) {
		//return self::singleton()->_memcache->set($key, $var, $compress, $expire);
		return self::singleton()->_memcache->set($key, $var);
	}

	/**
 * Set the value in memcache if the value does not exist; returns FALSE if value exists
 *
 * @param sting $key
 * @param mix $var
 * @param bool $compress
 * @param int $expire
 * @return bool
 */
	static function add($key, $var, $expire=0, $compress=1) {
		return self::singleton()->_memcache->add($key, $var, $compress, $expire);
	}

	/**
 * Replace an existing value
 *
 * @param string $key
 * @param mix $var
 * @param bool $compress
 * @param int $expire
 * @return bool
 */
	static function replace($key, $var, $expire=0, $compress=1) {
		return self::singleton()->_memcache->replace($key, $var, $compress, $expire);
	}
	/**
 * Delete a record or set a timeout
 *
 * @param string $key
 * @param int $timeout
 * @return bool
 */
	static function delete($key, $timeout=0) {
		return self::singleton()->_memcache->delete($key, $timeout);
	}
	/**
 * Increment an existing integer value
 *
 * @param string $key
 * @param mix $value
 * @return bool
 */
	static function increment($key, $value=1) {
		return self::singleton()->_memcache->increment($key, $value);
	}

	/**
 * Decrement an existing value
 *
 * @param string $key
 * @param mix $value
 * @return bool
 */
	static function decrement($key, $value=1) {
		return self::singleton()->_memcache->decrement($key, $value);
	}

	//class end
}

?>
