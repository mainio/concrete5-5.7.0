<?

namespace Concrete\Core\Events;

/**
 * @package Core
 * @author Andrew Embler <andrew@concrete5.org>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 *
 */

/**
 * An events framework for Concrete. System events like "on_user_add" can be hooked into, so that when a user is added to the system, the new UserInfo object is passed to developers' custom functions.
 * Current events include:
 * on_user_add
 * @package Core
 * @author Andrew Embler <andrew@concrete5.org>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 *
 */

class Events {
	
	const EVENT_TYPE_PAGETYPE = "page_type";
	const EVENT_TYPE_GLOBAL = "global";
	
	/** 
	 * Returns an instance of the systemwide Events object.
	 */
	public static function getInstance() {
		static $instance;
		if (!isset($instance)) {
			$instance = new Events;
		}
		return $instance;
	}		

	protected $registeredEvents = array();
	
	
	/** 
	 * When passed an "event" as a string, a user-defined method will be run INSIDE this page's controller
	 * whenever an event takes place. The name/location of this event is not customizable. If you want more
	 * customization, used extend() below.
	 */
	public static function extendPageType($ctHandle, $event = false, $params = array()) {
		if ($event == false) {
			// then we're registering ALL the page type events for this particular page type
			static::extendPageType($ctHandle, 'on_page_add', $params);
			static::extendPageType($ctHandle, 'on_page_update', $params);
			static::extendPageType($ctHandle, 'on_page_duplicate', $params);
			static::extendPageType($ctHandle, 'on_page_move', $params);
			static::extendPageType($ctHandle, 'on_page_view', $params);
			static::extendPageType($ctHandle, 'on_page_version_approve', $params);
			static::extendPageType($ctHandle, 'on_page_delete', $params);
			static::extendPageType($ctHandle, 'on_composer_publish', $params);
			static::extendPageType($ctHandle, 'on_composer_save_draft', $params);
			static::extendPageType($ctHandle, 'on_composer_delete_draft', $params);
		} else {
			$ce = static::getInstance();
			$class = Object::camelcase($ctHandle) . 'PageTypeController';
			$method = $event;
			$filename = Loader::pageTypeControllerPath($ctHandle);
			$ce->registeredEvents[$event][] = array(
				static::EVENT_TYPE_PAGETYPE,
				$class,
				$method,
				$filename,
				$params
			);
		}
	}
	/**
	 * When passed an "event" as a string (e.g. "on_user_add"), a user-defined method can be run whenever this event
	 * takes place.
	 * <code>
	 * Events::extend('on_user_add', 'MySpecialClass', 'createSpecialUserInfo', 'models/my_special_class.php', array('foo' => 'bar'))
	 * </code>
	 * @param string $event
	 * @param string $class
	 * @param string $method
	 * @param string $filename
	 * @param array $params
	 * $param int $priority
	 * @return void
	 */
	public static function extend($event, $class, $method='', $filename='', $params = array(), $priority = 5) {
		$ce = static::getInstance();
		$ce->registeredEvents[$event][] = array(
			static::EVENT_TYPE_GLOBAL,
			$class,
			$method,
			$filename,
			$params,
			$priority
		);
		static::sortByPriority();
	}
	
	/** 
	 * An internal function used by Concrete to "fire" a system-wide event. Any time this happens, events that 
	 * a developer has hooked into will be run.
	 * @param string $event
	 * @return void
	 */
	public static function fire($event) {

		// any additional arguments passed to the fire function will get passed FIRST to the method, with the method's own registered
		// params coming at the end. e.g. if I fire Events::fire('on_login', $userObject) it will come in with user object first
		$args = func_get_args();
		if (count($args) > 1) {
			array_shift($args);
		} else {
			$args = false;
		}

		$ce = static::getInstance();
		$events = array_key_exists($event, $ce->registeredEvents) ? $ce->registeredEvents[$event] : array();

		$eventReturn = false;
		
		foreach($events as $ev) {
			$type = $ev[0];
			$proceed = true;
			if ($type == static::EVENT_TYPE_PAGETYPE) {
				// then the first argument in the event fire() method will be the page
				// that this applies to. We check to see if the page type is the right type
				$proceed = false;
				if (is_object($args[0]) && $args[0] instanceof Page && $args[0]->getCollectionTypeID() > 0) {
					if ($ev[3] == Loader::pageTypeControllerPath($args[0]->getCollectionTypeHandle())) {
						$proceed = true;
					}
				}
			}

			if ($proceed) {
				if ($ev[3] != false) {
					// HACK - second part is for windows and its paths
				
					if (substr($ev[3], 0, 1) == '/' || substr($ev[3], 1, 1) == ':') {
						// then this means that our path is a full one
						require_once($ev[3]);
					} else {
						require_once(DIR_BASE . '/' . $ev[3]);
					}
				}
				$params = (is_array($ev[4])) ? $ev[4] : array();

				// now if args has any values we put them FIRST
				if (is_array($args)) {
					$params = array_merge($args, $params);
				}

				if ($ev[1] instanceof Closure) {
					$func = $ev[1];
					$eventReturn = call_user_func_array($func, $params);
				} else {
					if (method_exists($ev[1], $ev[2])) {
						// Note: DO NOT DO RETURN HERE BECAUSE THEN MULTIPLE EVENTS WON'T WORK
						$response = call_user_func_array(array($ev[1], $ev[2]), $params);
						if(!is_null($response)) {
							$eventReturn = $response;
						}
					}
				}
			}
		}
		return $eventReturn;
	}

	/**
	 * Sorts registered events by priority
	 * @return void
	 */
	protected static function sortByPriority() {
		$ce = static::getInstance();
		foreach(array_keys($ce->registeredEvents) as $event) {
			usort($ce->registeredEvents[$event],'static::comparePriority');
		}
	}

	/**
	 * compare function to be used with usort
	 * for sorting the events by priority
	 * @param array $a
	 * @param array $b
	 * @return number|number|number
	 */
	public static function comparePriority($a,$b) {
		if($a[5] > $b[5]) return 1;
		if($a[5] < $b[5]) return -1;
		return 0;
	}

}
