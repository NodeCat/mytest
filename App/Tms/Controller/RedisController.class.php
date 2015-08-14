<?php
namespace Tms\Controller;

class RedisController{
	public $handler;
	protected $options = array();
	public function index() {
		if ( !extension_loaded('redis') ) {
            E(L('_NOT_SUPPORT_').':redis');
        }
        $options = array();
        $options = array_merge(array (
            'host'          => C('REDIS_HOST') ? : '127.0.0.1',
            'port'          => C('REDIS_PORT') ? : 6379,
            'timeout'       => C('DATA_CACHE_TIMEOUT') ? : false,
            'persistent'    => true,
        ),$options);

        $this->options =  $options;
        $this->options['expire'] =  isset($options['expire'])?  $options['expire']  :   C('DATA_CACHE_TIME');
        $this->options['prefix'] =  isset($options['prefix'])?  $options['prefix']  :   C('DATA_CACHE_PREFIX');        
        $this->options['length'] =  isset($options['length'])?  $options['length']  :   0;        
        $func = $options['persistent'] ? 'pconnect' : 'connect';
        $this->handler  = new \Redis;
        $options['timeout'] === false ?
            $this->handler->$func($options['host'], $options['port']) :
            $this->handler->$func($options['host'], $options['port'], $options['timeout']);
   		$name = 'abc';
   		$this->handler->hset($this->options['prefix'].$name, 'abcd');
   		$value = $this->handler->append($this->options['prefix'].$name, '1234');
   		$value = $this->handler->get($this->options['prefix'].$name);

        
	}	
}
