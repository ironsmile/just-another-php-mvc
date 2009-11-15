<?php

if( !defined("__APP_CORE_FUNCTIONS") ):
define("__APP_CORE_FUNCTIONS", true);

//
//  depends on prototype.js
//
function remote_form_for( $location, $htmls = "" ){
  return "<form action='$location' method='post' onsubmit=\"new Ajax.Request('$location', {asynchronous:true, evalScripts:true, parameters:Form.serialize(this)}); return false;\" $htmls> ";
}

if( !function_exists("htmlize_string") ):
function htmlize_string($str){
  $str = htmlspecialchars(trim($str));
  $str = preg_replace('/([\w\d]{2,7}:\/\/[^<>\s]+)/', '<a href="${1}" target="_blank">${1}</a>', $str);
  $str = preg_replace('/\r\n/', "\n", $str);
  $str = preg_replace('/\r/', "\n", $str);
  return preg_replace('/\n/', "<br />", $str);
}
endif;

//
//  to be passed to js string with double quotes (")
//  Example:
//  
//  $('divy').update("The php says:\n<?= to_js_string($my_php_var) ? >");
//
function to_js_string($str){
  $str = str_replace('"', '\"', $str);
  return preg_replace('/((\r?\n)|\r)/', '\n', $str);
}

function show_exception_trace( &$e, $initial = "" ){
  if(DEV_ENVIROMENT){
    header("Content-type: text/plain");
    return $initial.$e->getMessage().
      "\n\nHappened at line ".$e->getLine()." of file `".$e->getFile()."`".
      "\n\nTrace:\n\n".$e->getTraceAsString();
  } else {
    return "";
  }
}

function url_for( $params, $query = "" ){
  if( !is_array($params) ){
    $params = preg_split("/\//", $params );
    $params["controller"] = $params[0] ;
    if( isset($params[1]) ) $params["action"] = $params[1];
    if( isset($params[2]) ) $params["id"] = $params[2];
    if( isset($params[3]) ) $params["query"] = $params[3].(empty($query)?"&".$query:"");
      else $params["query"] = $query;
  }
  
  parse_str( $params["query"], $q );
  if( isset($q['id']) ){
    $params["id"] = $q['id'];
    unset($q['id']);
  }
  $params["query"] = http_build_query($q);
  
  return SITE_ROOT."/".(isset($params['controller'])?$params['controller'].".php":"").
    "?action=".(isset($params['action'])?$params['action']:"index").
    (isset($params['id'])?"&id=".$params['id']:"").
    ((isset($params['query']) and !empty($params['query']))?"&".$params['query']:"");
}

//
//  will return something like "5 hours ago", "3 months from now" or "yesterday"
//
function time_ago_in_words($date){
  if(empty($date)) {
      return "No date provided";
  }
  
  $periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
  $lengths = array("60","60","24","7","4.35","12","10");
  
  $now = time();
  $unix_date = is_numeric($date) ? $date : strtotime($date);
  
      // check validity of date
  if(empty($unix_date)) {
    return "Bad date";
  }
  
  // is it future date or past date
  if($now > $unix_date) {
    $difference = $now - $unix_date;
    $tense = "ago";
  } else {
    $difference = $unix_date - $now;
    $tense = "from now";
  }
  
  for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
    $difference /= $lengths[$j];
  }
  
  $difference = round($difference);
  
  if($difference != 1) {
    $periods[$j].= "s";
  }
  
  $special = array( "ago" => "yesterday", "from now" => "tomorrow" );
  
  $words = ($difference==1 and $periods[$j]=="day") ? $special[$tense] : "$difference $periods[$j] {$tense}";
  if( $difference < 5 and preg_match( '/seconds?/' ,$periods[$j]) ) $words = "just now";
  return "<span title='".date("G:i j M Y",$unix_date)."' class='time_in_words'>$words</span>";
}

if( !function_exists("h") ):
	function h($s, $qs = ENT_COMPAT ){ return htmlspecialchars($s,$qs); }
endif;

define("JS_DEFAULT", 1);
define("JS_PROTOTYPE", 1 << 1);
define("JS_ALL", PHP_INT_MAX);

function javascript_include_tag($type){
  $scripts = array();
  if(is_string($type)){
    $scripts[] = INCLUDE_JS_DIR."/".$type.".js";
  } elseif(is_numeric($type)) {
    $type = intval($type);
    $valid_scripts = array(
      JS_DEFAULT => "application",
      JS_PROTOTYPE => "prototype",
    );
    foreach($valid_scripts as $const => $script_name){
      if($type & $const){
        $scripts[] = INCLUDE_JS_DIR."/".$script_name.".js";
      }
    }
  }
  $nocache = (DEV_ENVIROMENT) ? "?".substr(time(),-5) : "";
  $mapfunc = create_function('$v', 'return "<script type=\'text/javascript\' src=\'$v'.$nocache.'\'></script>";');
  return implode("\n  ", array_map($mapfunc, $scripts))."\n";
}

function css_include_tag($name, $media = "all"){
  $href= INCLUDE_CSS_DIR."/".$name.".css";
  if(DEV_ENVIROMENT) $href .= "?".substr(time(),-5);
  return "<link type='text/css' href='".$href."' media='$media' rel='stylesheet' />\n";
}

endif;
?>
