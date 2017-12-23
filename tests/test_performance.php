<?php

require_once __DIR__ .'/../vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$cache = new Crusse\ShmCache();
$memcached = new Memcached();
$memcached->addServer( 'localhost', 11211 );

if ( ( @$argc > 1 && $argv[ 1 ] === 'clear' ) || isset( $_REQUEST[ 'clear' ] ) ) {
  if ( $memcached->flush() && $cache->deleteAll() )
    echo 'Deleted all'. PHP_EOL;
  else
    echo 'ERROR: Failed to delete all'. PHP_EOL;
}

$itemsToCreate = 2000;
$totalSetTimeShm = 0;
$totalSetTimeMemcached = 0;

for ( $i = 0; $i < $itemsToCreate; ++$i ) {

  echo 'Set foobar'. $i . PHP_EOL;
  $valuePre = rand();
  $valuePost = str_repeat( 'x', 1000 );

  $start = microtime( true );
  if ( !$cache->set( 'foobar'. $i, $valuePre .' '. $valuePost ) ) {
    echo 'ERROR: Failed setting ShmCache value foobar'. $i . PHP_EOL;
    break;
  }
  $end = ( microtime( true ) - $start );
  echo 'ShmCache took '. $end .' s'. PHP_EOL;
  $totalSetTimeShm += $end;

  $start2 = microtime( true );
  if ( !$memcached->set( 'foobar'. $i, $valuePre .' '. $valuePost ) ) {
    echo 'ERROR: Failed setting Memcached value foobar'. $i . PHP_EOL;
    break;
  }
  $end2 = ( microtime( true ) - $start2 );
  echo 'Memcached took '. $end2 .' s'. PHP_EOL;
  $totalSetTimeMemcached += $end2;
}

$start = ( Crusse\ShmCache::MAX_ITEMS >= $itemsToCreate )
  ? 0
  : $itemsToCreate - Crusse\ShmCache::MAX_ITEMS;
$totalGetTimeShm = 0;
$totalGetTimeMemcached = 0;

for ( $i = $start; $i < $itemsToCreate; ++$i ) {

  echo 'Get '. $i . PHP_EOL;

  $start = microtime( true );
  if ( !$cache->get( 'foobar'. $i ) ) {
    echo 'ERROR: Failed getting ShmCache value foobar'. $i . PHP_EOL;
    break;
  }
  $end = ( microtime( true ) - $start );
  echo 'ShmCache took '. $end .' s'. PHP_EOL;
  $totalGetTimeShm += $end;

  $start2 = microtime( true );
  if ( !$memcached->get( 'foobar'. $i ) ) {
    echo 'ERROR: Failed getting Memcached value foobar'. $i . PHP_EOL;
    break;
  }
  $end2 = ( microtime( true ) - $start2 );
  echo 'Memcached took '. $end2 .' s'. PHP_EOL;
  $totalGetTimeMemcached += $end2;
}

echo PHP_EOL;
echo '----------------------------------------------'. PHP_EOL;
echo 'Total set:'. PHP_EOL;
echo 'ShmCache:  '. $totalSetTimeShm .' s'. PHP_EOL;
echo 'Memcached: '. $totalSetTimeMemcached .' s'. PHP_EOL . PHP_EOL;

echo 'Total get:'. PHP_EOL;
echo 'ShmCache:  '. $totalGetTimeShm .' s'. PHP_EOL;
echo 'Memcached: '. $totalGetTimeMemcached .' s'. PHP_EOL;
echo '----------------------------------------------'. PHP_EOL . PHP_EOL;

$value = $cache->get( 'foobar'. ( $itemsToCreate - 1 ) );
echo 'Old value: '. var_export( $value, true ) . PHP_EOL;
$num = ( $value ) ? intval( $value ) : 0;

if ( !$cache->set( 'foobar'. ( $itemsToCreate - 1 ), ( $num + 1 ) .' foo' ) )
  echo 'Failed setting value'. PHP_EOL;

$value = $cache->get( 'foobar'. ( $itemsToCreate - 1 ) );
echo 'New value: '. var_export( $value, true ) . PHP_EOL;

//echo '---------------------------------------'. PHP_EOL;
//echo 'Debug:'. PHP_EOL;
//$cache->dumpStats();
