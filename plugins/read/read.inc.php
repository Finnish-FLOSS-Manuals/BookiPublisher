<?php

include_once('lib/json.inc.php');

function ratedOutput ($info2,$tablerows){
  $tablerows=$tablerows-1;
  $categories = Array();
  $categoryRating = Array();

  foreach ($info2 as $key => $value){
    $categories[] = $value["category"];
  }

  $categories=array_unique($categories);

  foreach ($info2 as $key=>$value){
        if (strtolower($info2[$key]["status"])=="ei tilaa"){
                        $info2[$key]["bookrating"]=5;
                $thisCategory=$info2[$key]["category"];
                $categoryRating[$thisCategory]+=5;
        }
        if (strtolower($info2[$key]["status"])=="uusi" || strtolower($info2[$key]["status"])=="päivitetty"|| strtolower($info2[$key]["status"])=="merkittävä päivitys") {
                $thisCategory=$info2[$key]["category"];
                $categoryRating[$thisCategory]+=5;
                $thisBook=$info2[$key]["title"];
                $bookRating[$thisBook]+=5;
                $daysSinceCreated = 90 + (strtotime($info2[$key]["date"]) - strtotime(strftime("%Y-%m-%d %H:%M"))) / (60 * 60 * 24);
                if ($daysSinceCreated <= 0) $daysSinceCreated=0;
                $categoryRating[$thisCategory]+=$daysSinceCreated;
                $bookRating[$thisBook]+=$daysSinceCreated;
                $daysSinceModified = 45 + (strtotime($info2[$key]["modified"]) - strtotime(strftime("%Y-%m-%d %H:%M"))) / (60 * 60 * 24);
                if ($daysSinceModified <= 0) $daysSinceModified=0;
                $categoryRating[$thisCategory]+=$daysSinceModified;
                $bookRating[$thisBook]+=$daysSinceModified;
                if (strtolower($info2[$key]["status"])==("merkittävä päivitys")) {
                        $bookRating[$thisBook]+=15;
                        $categoryRating[$thisCategory]+=5;
                }
                if (strtolower($info2[$key]["status"])==("uusi")) {
                        $bookRating[$thisBook]+=10;
                        $categoryRating[$thisCategory]+=20;
                }

                $info2[$key]["bookrating"]=$bookRating[$thisBook];
        }
}

  @arsort($categoryRating);

  $sorter = new Sorter();
  $sorter->numeric = true;
  $sorter->backwards = true;

  $info2=$sorter->sort($info2,'bookrating');


  $html.="<table width='650'><tr><td>";
  $tablecounter=1;
  foreach ($categoryRating as $key => $val) {
	$category=$key;
	$html.="<h2>".strtoupper($category)."</h2>";
	  foreach ($info2 as $info3){
	    $status="";
	    if ($info3['category']==$category){
	      if ($info3['visible']=='on'){
 		if ($info3['bookrating'] > 20) $status="(".$info3['status'].")";
		$html.= '<span class="name"><a href="/'.$info3['dir'] .'/">'. $info3['title'] .'</a> '. $status.'</span><br>'; 
	      }
	    }  
	  }
	if ($tablecounter==$tablerows) {
		$html.="</td></tr><tr><td>";
  		$tablecounter=1;
	} 
	else{
		$html.="</td><td>";
		$tablecounter++;
	}
  }
  $html.="</table>";
  return $html;
}


function read_index() {

  $book=$_GET['book'];
  $chapter="index"; //default
  if(isset($_GET["chapter"])) $chapter = $_GET["chapter"];

  if (!isset($book)){
	if (DISPLAY_DIRS=='true'){

  		if ($dh = opendir(BOOKI_DIR."/")) {
  			while (($file = readdir($dh)) !== false) {
     				if($file != '.' && $file != '..') {
					$content.= "<a href='index.php?book=$file'>$file</a><br>"; 
				}
        		}			
        		closedir($dh);
  		}
  	} else {
  		$info_file = "data/bookInfo.json";
  		if (file_exists($info_file)) {
        	$info = file_get_contents($info_file);
        	$info = json_decode($info);
	
	$info=objectToArray($info);
	$content.=ratedOutput($info,3);

	}
    }
  
    $content = addTemplate('read', $content);
    return $content;
  } else if ($chapter == '_all') {
    $bookdir = BOOKI_DIR."/$book/";
    $content = @file_get_contents("$bookdir/index.html");
    foreach (glob("$bookdir/*.html") as $chapterfile) {
      if ($chapterfile == "$bookdir/contents.html" || $chapterfile == "$bookdir/index.html") {
        continue;
      }
      $chaptercontent=@file_get_contents($chapterfile);
      $content .= $chaptercontent;
    }
$content = '<html lang="fi"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><meta http-equiv="Content-Language" content="fi" /><style type="text/css">.menu-goes-here { display: none }</style><script type="text/javascript">var _gaq = _gaq || [];_gaq.push(["_setAccount", "UA-27919770-1"]);_gaq.push(["_trackPageview"]);(function() {var ga = document.createElement("script"); ga.type = "text/javascript"; ga.async =true;ga.src = ("https:" == document.location.protocol ? "https://ssl" : "http://www") + ".google-analytics.com/ga.js";var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(ga, s);})();</script><META NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW"></head><body><div style="width: 600px;">'.$content.'</div><img src="http://fi.flossmanuals.net/piwik/piwik/piwik.php?idsite=1&amp;rec=1" style="border:0" alt="" />';
    echo $content;

echo "</body></html>";

  } else {
    if ($chapter === '')
      $chapter = "index";
    $filename=BOOKI_DIR."/$book/$chapter.html";
    $content=@file_get_contents($filename);

    if ($content == "") {
	$content =  "<br>This page does not exist ".$filename;
    	$content = addTemplate('error', $content);
     } else {
    	//$content = preg_replace("[href=\"([\w!\/]*).html\"]", "href=\"\\1\"", $content);
    	//$content = preg_replace("[\"(static/.*)\"]", "\"booki/$book/\\1\"", $content);
    	//$content = preg_replace("[<html dir=\"LTR\"><body>]", "", $content);
    	//$content = preg_replace("[</body></html>]", "", $content);
    	$content = addTemplate('book', $content);
    	$content = render_widgets($book, $chapter, $content);
    }  
    echo $content;
  }
}


function read_dispatcher($name) {
  if($name == "index") return read_index();
}


function read_beforedisplay() {
}

function read_tagreplace($hook_args) {
	$book=$_GET['book'];
        $chapter = $_GET["chapter"];
  	$info_file = "data/bookInfo.json";
  	if (file_exists($info_file)) {
        	$info = file_get_contents($info_file);
        	$info = json_decode($info);
	$info=objectToArray($info);
	$thisBook = searchArray($info,'dir',$book);
	} 
        $title= $thisBook[0]['title'];
        $pdf= $thisBook[0]['pdf'];
        $epub= $thisBook[0]['epub'];
        $modified= $thisBook[0]['modified'];
        $published= $thisBook[0]['date'];
        $description= $thisBook[0]['description'];
        $category= $thisBook[0]['category'];




        $output = preg_replace("[<book-title/>]",$title,$hook_args);

        $output = preg_replace("[<pdf-location/>]",$pdf,$output);
        $output = preg_replace("[<epub-location/>]",$epub,$output);

        $chapternamed=$output;
  
        $pattern = "/<h1>(.*?)<\/h1>/";

        $chaptername = preg_match($pattern, $chapternamed, $matches);

        $chaptername=($matches[1]);


        $output = preg_replace("[<chapter-name/>]",$chaptername,$output);
        $output = preg_replace("[<book-name/>]",$book,$output);
        $output = preg_replace("[<file-name/>]",$chapter,$output);
        $output = preg_replace("[<modified/>]",$modified,$output);
        $output = preg_replace("[<description/>]",$description,$output);
        $output = preg_replace("[<published/>]",$published,$output);
        $output = preg_replace("[<category/>]",$category,$output);
        return $output;
}

function read_initialize() {
  add_hook("before_display", "read_beforedisplay");
  add_hook("tag_replace", "read_tagreplace");
}

function read_install() {

}

function read_uninstall() {

}


function read_plugin() {
  return Array("info" => Array("author" => "Adam Hyde",
			       "license" => "AGPL",
			       "description" => "Basic plugin for displaying books.",
			       "version" => "1.0")
	       );
}

?>
