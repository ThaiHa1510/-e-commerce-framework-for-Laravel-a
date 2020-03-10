<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Facades\Shop;

class HomeController extends Controller
{
    //
    public function __contruct(){}
    public function index(){
		$params;
        foreach( app( 'config' )->get( 'shop.page.catalog-list' ) as $name )
		{
			$params['aiheader'][$name] = Shop::get( $name );
			$params['aibody'][$name] = Shop::get( $name );
		}

		//return Response::view( 'shop::catalog.list', $params )->header( 'Cache-Control', 'private, max-age=10' );
		print_r($params);
		
    }
}
