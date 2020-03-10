<?php

namespace App\Client;
use Exception;

/**
 * Common factory for HTML clients
 *
 * @package Client
 * @subpackage Html
 */
class Html
{
	
	public static function create( \App\Contract\Context\Iface $context, $path, $name = null )
	{
		if( empty( $path ) ) {
			throw new Exception( sprintf( 'Client path is empty' ) );
		}

		$parts = explode( '/', $path );

		foreach( $parts as $key => $part )
		{
			if( ctype_alnum( $part ) === false )
			{
				$msg = sprintf( 'Invalid characters in client name "%1$s"', $path );
				throw new Exception( $msg, 400 );
			}

			$parts[$key] = ucfirst( $part );
		}
		
		$factory = '\\App\\Client\\Html\\' . join( '\\', $parts ) . '\\Factory';

		if( class_exists( $factory ) === false ) {
			throw new Exception( sprintf( 'Class "%1$s" not available', $factory ) );	
		}

		if( ( $client = @call_user_func_array( [$factory, 'create'], [$context, $name] ) ) === false ) {
			throw new Exception( sprintf( 'Invalid factory "%1$s"', $factory ) );
		}

		return $client;
	}
}
