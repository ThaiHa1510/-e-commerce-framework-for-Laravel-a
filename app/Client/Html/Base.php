<?php

namespace Aimeos\Client\Html;
use Exception;

abstract class Base
	implements \App\Client\Html\Iface
{
	private $view;
	private $cache;
	private $object;
	private $context;
	private $subclients;

	public function __construct( \App\Contract\Context\Iface $context )
	{
		$this->context = $context;
	}


	public function __call( $name, array $param )
	{
		throw new \Aimeos\Client\Html\Exception( sprintf( 'Unable to call method "%1$s"', $name ) );
	}


	public function addData( \App\Contract\View\Iface $view, array &$tags = [], &$expire = null )
	{
		foreach( $this->getSubClients() as $name => $subclient ) {
			$view = $subclient->addData( $view, $tags, $expire );
		}

		return $view;
	}


	public function getHeader( $uid = '' )
	{
		$html = '';

		foreach( $this->getSubClients() as $subclient ) {
			$html .= $subclient->setView( $this->view )->getHeader( $uid );
		}

		return $html;
	}

	protected function getObject()
	{
		if( $this->object !== null ) {
			return $this->object;
		}

		return $this;
	}

	public function getView()
	{
		if( !isset( $this->view ) ) {
			throw new Exception( sprintf( 'No view available' ) );
		}

		return $this->view;
	}


	/**
	 * Modifies the cached body content to replace content based on sessions or cookies.
	 *
	 * @param string $content Cached content
	 * @param string $uid Unique identifier for the output if the content is placed more than once on the same page
	 * @return string Modified body content
	 */
	public function modifyBody( $content, $uid )
	{
		$view = $this->getView();

		foreach( $this->getSubClients() as $subclient )
		{
			$subclient->setView( $view );
			$content = $subclient->modifyBody( $content, $uid );
		}

		return $content;
	}


	/**
	 * Modifies the cached header content to replace content based on sessions or cookies.
	 *
	 * @param string $content Cached content
	 * @param string $uid Unique identifier for the output if the content is placed more than once on the same page
	 * @return string Modified header content
	 */
	public function modifyHeader( $content, $uid )
	{
		$view = $this->getView();

		foreach( $this->getSubClients() as $subclient )
		{
			$subclient->setView( $view );
			$content = $subclient->modifyHeader( $content, $uid );
		}

		return $content;
	}


	/**
	 * Processes the input, e.g. store given values.
	 * A view must be available and this method doesn't generate any output
	 * besides setting view variables.
	 *
	 * @return boolean False if processing is stopped, otherwise all processing was completed successfully
	 */
	public function process()
	{
		$view = $this->getView();

		foreach( $this->getSubClients() as $subclient )
		{
			$subclient->setView( $view );

			if( $subclient->process() === false ) {
				return false;
			}
		}

		return true;
	}

	public function setObject( \App\Client\Html\Iface $object )
	{
		$this->object = $object;
		return $this;
	}

	public function setView(\App\Contract\View\Iface $view )
	{
		$this->view = $view;
		return $this;
	}

	protected function addDecorators( \App\Client\Html\Iface $client, array $decorators, $classprefix )
	{
		foreach( $decorators as $name )
		{
			if( ctype_alnum( $name ) === false )
			{
				$classname = is_string( $name ) ? $classprefix . $name : '<not a string>';
				throw new \Aimeos\Client\Html\Exception( sprintf( 'Invalid class name "%1$s"', $classname ) );
			}

			$classname = $classprefix . $name;

			if( class_exists( $classname ) === false ) {
				throw new \Aimeos\Client\Html\Exception( sprintf( 'Class "%1$s" not found', $classname ) );
			}

			$client = new $classname( $client, $this->context );

			\Aimeos\MW\Common\Base::checkClass( '\\Aimeos\\Client\\Html\\Common\\Decorator\\Iface', $client );
		}

		return $client;
	}


	protected function addClientDecorators( \App\Client\Html\Iface $client, $path )
	{
		if( !is_string( $path ) || $path === '' ) {
			throw new \Aimeos\Client\Html\Exception( sprintf( 'Invalid domain "%1$s"', $path ) );
		}

		$localClass = str_replace( ' ', '\\', ucwords( str_replace( '/', ' ', $path ) ) );
		$config = $this->context->getConfig();

		$decorators = $config->get( 'client/html/common/decorators/default', [] );
		$excludes = $config->get( 'client/html/' . $path . '/decorators/excludes', [] );

		foreach( $decorators as $key => $name )
		{
			if( in_array( $name, $excludes ) ) {
				unset( $decorators[$key] );
			}
		}

		$classprefix = '\\Aimeos\\Client\\Html\\Common\\Decorator\\';
		$client = $this->addDecorators( $client, $decorators, $classprefix );

		$classprefix = '\\Aimeos\\Client\\Html\\Common\\Decorator\\';
		$decorators = $config->get( 'client/html/' . $path . '/decorators/global', [] );
		$client = $this->addDecorators( $client, $decorators, $classprefix );

		$classprefix = '\\Aimeos\\Client\\Html\\' . $localClass . '\\Decorator\\';
		$decorators = $config->get( 'client/html/' . $path . '/decorators/local', [] );
		$client = $this->addDecorators( $client, $decorators, $classprefix );

		return $client;
	}


	protected function addMetaItems( $items, &$expire, array &$tags, array $custom = [] )
	{
		
		$tagAll = $this->context->getConfig()->get( 'client/html/common/cache/tag-all', false );

		if( !is_array( $items ) ) {
			$items = array( $items );
		}

		$expires = $idMap = [];

		foreach( $items as $item )
		{
			if( $item instanceof \App\Client\Common\Item\ListRef\Iface )
			{
				$this->addMetaItemRef( $item, $expires, $tags, $tagAll );
				$idMap[$item->getResourceType()][] = $item->getId();
			}

			$this->addMetaItemSingle( $item, $expires, $tags, $tagAll );
		}

		if( $expire !== null ) {
			$expires[] = $expire;
		}

		if( !empty( $expires ) ) {
			$expire = min( $expires );
		}

		$tags = array_unique( array_merge( $tags, $custom ) );
	}


	
	private function addMetaItemSingle( \App\Client\Common\Iface $item, array &$expires, array &$tags, $tagAll )
	{
		$domain = str_replace( '/', '_', $item->getResourceType() ); // maximum compatiblity

		if( $tagAll === true ) {
			$tags[] = $domain . '-' . $item->getId();
		} else {
			$tags[] = $domain;
		}

		if( $item instanceof \App\Client\Common\Item\Time\Iface && ( $date = $item->getDateEnd() ) !== null ) {
			$expires[] = $date;
		}
	}


	private function addMetaItemRef( \App\Client\Common\Item\ListRef\Iface $item, array &$expires, array &$tags, $tagAll )
	{
		foreach( $item->getListItems() as $listitem )
		{
			if( ( $refItem = $listitem->getRefItem() ) === null ) {
				continue;
			}

			if( $tagAll === true ) {
				$tags[] = str_replace( '/', '_', $listitem->getDomain() ) . '-' . $listitem->getRefId();
			}

			if( ( $date = $listitem->getDateEnd() ) !== null ) {
				$expires[] = $date;
			}

			$this->addMetaItemSingle( $refItem, $expires, $tags, $tagAll );
		}
	}


	
	protected function createSubClient( $path, $name )
	{
		$path = strtolower( $path );

		if( $name === null ) {
			$name = $this->context->getConfig()->get( 'client/html/' . $path . '/name', 'Standard' );
		}

		if( empty( $name ) || ctype_alnum( $name ) === false ) {
			throw new \Aimeos\Client\Html\Exception( sprintf( 'Invalid characters in client name "%1$s"', $name ) );
		}

		$subnames = str_replace( ' ', '\\', ucwords( str_replace( '/', ' ', $path ) ) );
		$classname = '\\Aimeos\\Client\\Html\\' . $subnames . '\\' . $name;

		if( class_exists( $classname ) === false ) {
			throw new \Aimeos\Client\Html\Exception( sprintf( 'Class "%1$s" not available', $classname ) );
		}

		$object = new $classname( $this->context );

		\Aimeos\MW\Common\Base::checkClass( '\\Aimeos\\Client\\Html\\Iface', $object );

		return $this->addClientDecorators( $object, $path );
	}


	/**
	 * Returns the minimal expiration date.
	 *
	 * @param string|null $first First expiration date or null
	 * @param string|null $second Second expiration date or null
	 * @return string|null Expiration date
	 */
	protected function expires( $first, $second )
	{
		return ( $first !== null ? ( $second !== null ? min( $first, $second ) : $first ) : $second );
	}

	/**
	 * Returns the parameters used by the html client.
	 *
	 * @param array $params Associative list of all parameters
	 * @param array $prefixes List of prefixes the parameters must start with
	 * @return array Associative list of parameters used by the html client
	 */
	protected function getClientParams( array $params, array $prefixes = array( 'f', 'l', 'd', 'a' ) )
	{
		$list = [];

		foreach( $params as $key => $value )
		{
			if( in_array( $key[0], $prefixes ) && $key[1] === '_' ) {
				$list[$key] = $value;
			}
		}

		return $list;
	}


	/**
	 * Returns the context object.
	 *
	 * @return \App\Client\Context\Item\Iface Context object
	 */
	protected function getContext()
	{
		return $this->context;
	}


	/**
	 * Generates an unique hash from based on the input suitable to be used as part of the cache key
	 *
	 * @param array $prefixes List of prefixes the parameters must start with
	 * @param string $key Unique identifier if the content is placed more than once on the same page
	 * @param array $config Multi-dimensional array of configuration options used by the client and sub-clients
	 * @return string Unique hash
	 */
	protected function getParamHash( array $prefixes = array( 'f', 'l', 'd' ), $key = '', array $config = [] )
	{
		$locale = $this->getContext()->getLocale();
		$params = $this->getClientParams( $this->getView()->param(), $prefixes );
		ksort( $params );

		if( ( $pstr = json_encode( $params ) ) === false || ( $cstr = json_encode( $config ) ) === false ) {
			throw new \Aimeos\Client\Html\Exception( 'Unable to encode parameters or configuration options' );
		}

		return md5( $key . $pstr . $cstr . $locale->getLanguageId() . $locale->getCurrencyId() );
	}


	/**
	 * Returns the list of sub-client names configured for the client.
	 *
	 * @return array List of HTML client names
	 */
	abstract protected function getSubClientNames();


	/**
	 * Returns the configured sub-clients or the ones named in the default parameter if none are configured.
	 *
	 * @return array List of sub-clients implementing \App\Client\Html\Iface	ordered in the same way as the names
	 */
	protected function getSubClients()
	{
		if( !isset( $this->subclients ) )
		{
			$this->subclients = [];

			foreach( $this->getSubClientNames() as $name ) {
				$this->subclients[$name] = $this->getSubClient( $name );
			}
		}

		return $this->subclients;
	}


	/**
	 * Returns the template for the given configuration key
	 *
	 * If the "l_type" parameter is present, a specific template for this given
	 * type is used if available.
	 *
	 * @param string $confkey Key to the configuration setting for the template
	 * @param string $default Default template if none is configured or not found
	 * @return string Relative template path
	 */
	protected function getTemplatePath( $confkey, $default )
	{
		if( ( $type = $this->view->param( 'l_type' ) ) !== null && ctype_alnum( $type ) !== false ) {
			return $this->view->config( $confkey . '-' . $type, $this->view->config( $confkey, $default ) );
		}

		return $this->view->config( $confkey, $default );
	}


	/**
	 * Returns the cache entry for the given unique ID and type.
	 *
	 * @param string $type Type of the cache entry, i.e. "body" or "header"
	 * @param string $uid Unique identifier for the output if the content is placed more than once on the same page
	 * @param string[] $prefixes List of prefixes of all parameters that are relevant for generating the output
	 * @param string $confkey Configuration key prefix that matches all relevant settings for the component
	 * @return string|null Cached entry or null if not available
	 */
	protected function getCached( $type, $uid, array $prefixes, $confkey )
	{
		$context = $this->getContext();
		$config = $context->getConfig();

		/** client/html/common/cache/force
		 * Enforces content caching regardless of user logins
		 *
		 * Caching the component output is normally disabled as soon as the
		 * user has logged in. This enables displaying user or user group
		 * specific content without mixing standard and user specific output.
		 *
		 * If you don't have any user or user group specific content
		 * (products, categories, attributes, media, prices, texts, etc.),
		 * you can enforce content caching nevertheless to keep response
		 * times as low as possible.
		 *
		 * @param boolean True to cache output regardless of login, false for no caching
		 * @since 2015.08
		 * @category Developer
		 * @category User
		 * @see client/html/common/cache/tag-all
		 */
		$force = $config->get( 'client/html/common/cache/force', false );
		$enable = $config->get( $confkey . '/cache', true );

		if( $enable == false || $force == false && $context->getUserId() !== null ) {
			return null;
		}

		$cfg = array_merge( $config->get( 'client/html', [] ), $this->getSubClientNames() );

		$keys = array(
			'body' => $this->getParamHash( $prefixes, $uid . ':' . $confkey . ':body', $cfg ),
			'header' => $this->getParamHash( $prefixes, $uid . ':' . $confkey . ':header', $cfg ),
		);

		if( !isset( $this->cache[$keys[$type]] ) ) {
			$this->cache = $context->getCache()->getMultiple( $keys );
		}

		return ( isset( $this->cache[$keys[$type]] ) ? $this->cache[$keys[$type]] : null );
	}


	/**
	 * Returns the cache entry for the given type and unique ID.
	 *
	 * @param string $type Type of the cache entry, i.e. "body" or "header"
	 * @param string $uid Unique identifier for the output if the content is placed more than once on the same page
	 * @param string[] $prefixes List of prefixes of all parameters that are relevant for generating the output
	 * @param string $confkey Configuration key prefix that matches all relevant settings for the component
	 * @param string $value Value string that should be stored for the given key
	 * @param array $tags List of tag strings that should be assoicated to the given value in the cache
	 * @param string|null $expire Date/time string in "YYYY-MM-DD HH:mm:ss"	format when the cache entry expires
	 */
	protected function setCached( $type, $uid, array $prefixes, $confkey, $value, array $tags, $expire )
	{
		$context = $this->getContext();
		$config = $context->getConfig();

		$force = $config->get( 'client/html/common/cache/force', false );
		$enable = $config->get( $confkey . '/cache', true );

		if( $enable == false || $force == false && $context->getUserId() !== null ) {
			return;
		}

		try
		{
			$cfg = array_merge( $config->get( 'client/html', [] ), $this->getSubClientNames() );
			$key = $this->getParamHash( $prefixes, $uid . ':' . $confkey . ':' . $type, $cfg );

			$context->getCache()->set( $key, $value, $expire, array_unique( $tags ) );
		}
		catch( \Exception $e )
		{
			$msg = sprintf( 'Unable to set cache entry: %1$s', $e->getMessage() );
			$context->getLogger()->log( $msg, \Aimeos\MW\Logger\Base::NOTICE );
		}
	}


	/**
	 * Writes the exception details to the log
	 *
	 * @param \Exception $e Exception object
	 */
	protected function logException( \Exception $e )
	{
		$logger = $this->context->getLogger();

		$logger->log( $e->getMessage(), \Aimeos\MW\Logger\Base::WARN, 'client/html' );
		$logger->log( $e->getTraceAsString(), \Aimeos\MW\Logger\Base::WARN, 'client/html' );
	}


	/**
	 * Replaces the section in the content that is enclosed by the marker.
	 *
	 * @param string $content Cached content
	 * @param string $section New section content
	 * @param string $marker Name of the section marker without "<!-- " and " -->" parts
	 */
	protected function replaceSection( $content, $section, $marker )
	{
		$start = 0;
		$len = strlen( $section );
		$marker = '<!-- ' . $marker . ' -->';

		while( ( $start = @strpos( $content, $marker, $start ) ) !== false )
		{
			if( ( $end = strpos( $content, $marker, $start + 1 ) ) !== false ) {
				$content = substr_replace( $content, $section, $start, $end - $start + strlen( $marker ) );
			}

			$start += 2 * strlen( $marker ) + $len;
		}

		return $content;
	}


	/**
	 * Translates the plugin error codes to human readable error strings.
	 *
	 * @param array $codes Associative list of scope and object as key and error code as value
	 * @return array List of translated error messages
	 */
	protected function translatePluginErrorCodes( array $codes )
	{
		$errors = [];
		$i18n = $this->getContext()->getI18n();

		foreach( $codes as $scope => $list )
		{
			foreach( $list as $object => $errcode )
			{
				$key = $scope . ( !in_array( $scope, ['coupon', 'product'] ) ? '.' . $object : '' ) . '.' . $errcode;
				$errors[] = sprintf( $i18n->dt( 'mshop/code', $key ), $object );
			}
		}

		return $errors;
	}
}
