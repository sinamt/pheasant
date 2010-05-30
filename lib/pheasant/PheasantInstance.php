<?php

namespace pheasant;

class PheasantInstance
{
	private $_connectionManager;
	private $_schema=array();

	public function __construct()
	{
	}

	public function setup($dsn)
	{
		$this->connectionManager()
			->addConnection('default', $dsn)
			;
	}

	public function connectionManager()
	{
		if(!isset($this->_connectionManager))
		{
			$this->_connectionManager = new database\ConnectionManager();
		}

		return $this->_connectionManager;
	}

	public function connection($name='default')
	{
		return $this->_connectionManager->connection($name);
	}

	/**
	 * Generates a closure that invokes a method regardless of whether it's private
	 * or protected. Treat with extreme caution.
	 * @return closure
	 */
	public function methodClosure($object, $method)
	{
		return function() use($object, $method) {
			$refObj = new \ReflectionObject($object);
			$refMethod = $refObj->getMethod($method);
			$refMethod->setAccessible(true);
			return $refMethod->invokeArgs($object, func_get_args());
		};
	}

	/**
	 * Returns the Schema instance for an object
	 */
	public function schema($object)
	{
		$class = get_class($object);

		if(!isset($this->_schema[$class]))
			throw new Exception("No schema created for $class");

		return $this->_schema[$class];
	}

	/**
	 * Constructs a domain object, invoking DomainObject::configure() if required
	 * and also DomainObject::construct()
	 */
	public function construct($object, $params)
	{
		$class = get_class($object);

		if(!isset($this->_schema[$class]))
		{
			$schema = $this->_schema[$class] = new Schema();
			$configure = $this->methodClosure($object, 'configure');
			$configure(
				$schema,
				$schema->properties(),
				$schema->relationships()
				);
		}

		$construct = $this->methodClosure($object, 'construct');
		call_user_func_array($construct, $params);
		return $object;
	}
}