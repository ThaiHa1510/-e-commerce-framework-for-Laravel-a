<?php

interface Handler
{
    public function handle($value, Closure $next);
}

// handler if the value
class NumberHandler implements Handler
{
    // method for handler
    public function handle($value, Closure $next)
    {
        if (gettype($value) != 'number')
        {
            //in the case where a value of another type to pass it on
            echo "ok phai so";
            return $next($value);
        }
        return round($value);
    }
}

//similarly for string processing
class StringHandler implements Handler 
{
    public function handle($value, Closure $next)
    {
        if (gettype($value) != "string")
        {
            echo "ok phai string";   
            return $next($value);
            
        }
       return strtoupper($value);
    }
}

//similarly for string array
class ArrayHandler implements Handler
{
    public function handle($value, Closure $next)
    {
        if (gettype($value) != "array")
        {   
            echo "ok phai mang";
            return $next($value);
        }
        
        sort($value);
        print_r($value);
        return $value;
    }
}

// create class Pipeline and pass inside Container
$test ='ki ta';
//$test=new \Illuminate\Container\Container();
$pipeline = new \Illuminate\Pipeline\Pipeline(new \Illuminate\Container\Container());

// in method send pass out value 
// in method through operations we will process
$pipeline->send($test)->through([new NumberHandler(), new StringHandler(), new ArrayHandler()]);

// method then pass closure function which works if the value is never used
$response = $pipeline->then(function ($some){
    echo "<br>ok chay then";
  });

// in this case, we get the sorted array
//var_dump($response);