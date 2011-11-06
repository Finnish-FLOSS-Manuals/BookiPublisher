<?php

function getbook_admin() {
  if(!is_dir("tmp")) mkdir("tmp");
  $update = tempnam("tmp/", "update_");
  $updatetext = _("Processing...")."<br>"._("Requesting book");
  $book = array_value($_POST, 'book');
  $bookupdate = null;

  // get users books
  $tableUsers = new Axon(DB_TABLE_USERS);
  $books = array();
  if ($username = array_value($_SESSION, 'username')) {
      $tableUsers->load("username='$username'");
      if (!$tableUsers->dry()) {
          $books = unserialize($tableUsers->books);
      }
  }

  //need to pass tmpfile for updates since the html is not updated until the end
  $tmpfilename = array_value($_GET, 'tmpfilename');

  //if no request then make a form
  if ($book == ''){
  	$html="<h1>"._("GetBook Plugin")."</h1>";
	//div for displaying feedback
  	$html.="<div id='contentArea'> </div>";
	//feedback javascript
	$html.='
		<script type="text/javascript" src="templates/jquery/js/jquery-1.6.1.min.js"></script>
		<script type="text/javascript">
			function contentDisp()
        			{
                			$.ajax({
        				url : "tmp/'.basename($update).'",
        				success : function (data) {
                				$("#contentArea").html(data);
                        	}
                			});
        			}
			function updater ()
        			{
					var txt=document.getElementById("contentArea")
  					txt.innerHTML="'._('Processing...').'";
        				var id = setInterval("contentDisp()", 5000);
					document.getElementById(\'bookform\').style.display = "none";
				}
		</script>';

	$html.="<div id='bookform'><form method='POST' action='admin.php?plugin=getbook&tmpfilename=".basename($update)."'>";
	$html.="<input type='hidden' name='tmpfilename' value='".basename($update)."'>";
	$html.=_("Choose Book")."<br><select id='book' name='book'>";
    foreach ((array)$books as $key => $value) {
        $html .= "<option value=\"$key\">$value</option>";
    }
    $html.="</select><br>";
	$html.="<br>"._("Title")." : <input type=text name='title'></input>";
	$html.="<br><input type=CHECKBOX name='getEpub' CHECKED>"._("get epub")."</input>";
	$html.="<br><input type=CHECKBOX name='getPDF' CHECKED>"._("get screen formatted PDF")."</input>";
	$html.="<br><br><button id='submit' onClick='updater();'>"._("Get Book!")."</button><br>";
	$html.="</form></div><div id='contentArea'> </div>";
  	return $html;
  }
  else {

    // check book access
    if (!array_value($books, $book)) {
        return 'You have no permissions to get ' . $book;
    }

  	//does the book exist already? - will effect other functions
	$logger="\n\n[".date('Y-m-di h:i:s A')."]" ." starting to get book - ". $book;
	file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
  	if (is_dir(BOOKI_DIR."/$book")) {
		$bookupdate = "TRUE";
		$logger="book already exists";
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
	}
	$update="tmp/".$tmpfilename;
	$logger="tmp file name is ". $tmpfilename;
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
	//we dont use templates from objavi we handlethem locally so we just need raw html
	//... would be nice to fetch a toc.txt too
	$template=rawurlencode("<content-goes-here /><menu-goes-here/>");

	$gotit = tempnam("tmp/", "book_");
		$logger="new temp name is $gotit";
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);

	//create objavi url to create the book
	$url=OBJAVI_SERVER_URL."/?book=".$book."&server=".BOOKI_SERVER_TARGET."&mode=templated_html&html_template=".$template;
		$logger="requesting book to be created. Request is: " . $url;
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);

	//this is the update routine for browser feedback via jquery
	$updatetext.= "<br>"._("Asking objavi for the raw files without templates");
	file_put_contents($update,$updatetext);

	//tell objavi to create the book -
	//it returns a html file (stored in the temp file $gotit)
	//with info that needs to be parsed
		//$logger="getting book";
		//file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
	try {
		file_put_contents($gotit, file_get_contents($url));
	} catch (Exception $e) {
		$html.=_("you are not online");
		$logger="failed getting book url from objavi";
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
	}

	//find the location of the published html on the objavi server
	//...could be done better (using basename()?)
	$file = file_get_contents($gotit);
	if(strpos($file, "books/")) {
        	$start=strpos($file, "books/");
        	$end=strpos($file, "\"",$start);
		$bookurl= substr($file,$start,$end-$start);
		$logger="Parsing book url, it seems to be $bookurl";
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
	}

	$updatetext.= "<br>"._("Book currently being created by Objavi at")." : ".OBJAVI_SERVER_URL."/".$bookurl;
	$updatetext.= "<br>"._("(this will take a few minutes)");
	file_put_contents($update,$updatetext);

	//stupid timeout...should instead be polling

//poll url is http://objavi.flossmanuals.net/progress/bookurl.txt
	sleep(180);

	$updatetext.= "<br>"._("Fetching and saving tar.gz");
	file_put_contents($update,$updatetext);
		$logger="fetching tar from objavi: ".OBJAVI_SERVER_URL."/$bookurl".".tar.gz";
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);

	//finally...get the tar.gz
	//file_put_contents is using too much memory...try freeing it using wget
	$file = file_get_contents(OBJAVI_SERVER_URL."/".$bookurl.".tar.gz");
		$logger="creating tar " . $book . ".tar.gz";
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
	file_put_contents("tmp/$book.tar.gz",$file);
	//$file = "http://objavi.flossmanuals.net/".$bookurl.".tar.gz";
	//shell_exec("wget \"".$file."\" tmp/$book.tar.gz");

	$updatetext.= "<br>"._("Untarring archive");
	file_put_contents($update,$updatetext);

	//untar the dir in tmp/ and work out the filename
	//unix specific...bad luck windozers
		$logger="unpacking archive";
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
	$untarDir = preg_split("[/]",trim(shell_exec("cd tmp/; tar -ztf $book.tar.gz")));
	shell_exec("tar -zxvf tmp/$book.tar.gz --directory tmp");

	$updatetext.= "<br>"._("Moving file to booki dir");
	file_put_contents($update,$updatetext);

	//move the untarred dir to booki/
	//and rename it to $book
	//first remove the old one if it exists
		$logger="moving the dir " . $untarDir[0];;
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
	if (is_dir("tmp/$untarDir[0]")) {
		if ($bookupdate=="TRUE") {
			delete_directory(BOOKI_DIR."/$book");
			$logger="deleting book/" . $book;
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
		}
		$logger="renaming tmp/" . $untarDir[0] ." to ".BOOKI_DIR."/". $book;
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
		rename("tmp/$untarDir[0]", BOOKI_DIR."/$book");
	}

	$updatetext.= "<br>"._("Renaming files");
	file_put_contents($update,$updatetext);
		$logger="renaming files";
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);

 //rename all the files from .html to .txt
        //...it would also be good if they came as clean .txt from objavi
        //use tidy (must be installed) to clean html if set in the config
	$filelist = array();
        if ($dh = opendir(BOOKI_DIR."/$book")) {
                while (($file = readdir($dh)) !== false) {
			$filelist[] = $file;
		}
		closedir($dh);
	}
	foreach ($filelist as $file) {
                if($file != "." && $file != ".." && $file!="static") {
                        $newfile=substr($file,0,-4)."txt";
                        $page = file_get_contents(BOOKI_DIR."/$book/$file");
                        $page = preg_replace("[href=\"([\w!\/-]*).html\"]", "href=\"/$book/\\1\"", $page);
                        $page = preg_replace("[\"(static/[^\"]*)\"]", "\"".BOOKI_DIR."/$book/\\1\"", $page);
                        $page = preg_replace("[<html dir=\"LTR\"><body>]", "", $page);
                        $page = preg_replace("[</body></html>]", "", $page);
                        file_put_contents(BOOKI_DIR."/$book/$file",$page);
                        rename(BOOKI_DIR."/$book/$file",BOOKI_DIR."/$book/$newfile");
                        $logger="rename ".BOOKI_DIR."/$book/$file to ".BOOKI_DIR."/$book/$newfile";
                        file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
                        if (USE_TIDY=="true")
                                $logger="trying tidy";
                        file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
                        shell_exec("tidy -m -config data/tidy.config ".BOOKI_DIR."/$book/$newfile");
                }
        }


/*
    	$content = preg_replace("[href=\"([\w!\/]*).html\"]", "href=\"\\1\"", $content);
    	$content = preg_replace("[\"(static/.*)\"]", "\"booki/$book/\\1\"", $content);
    	$content = preg_replace("[<html dir=\"LTR\"><body>]", "", $content);
    	$content = preg_replace("[</body></html>]", "", $content);
*/
	$updatetext.= "<br>"._("Adding meta info");
	file_put_contents($update,$updatetext);
		$logger="Adding meta data to db";
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);

	//begin constructing info for db
	$tableBooks = new Axon(DB_TABLE_BOOKS);
	$tableBooks->load("dir='$book'");
	if ($tableBooks->dry()) {
	    $tableBooks->reset();
        $tableBooks->dir = $book;
	    $tableBooks->title = array_value($_POST, 'title') ? array_value($_POST, 'title') : $book;
	    $tableBooks->date = strftime("%Y-%m-%d %H:%M");
    	$tableBooks->description = "";
    	$tableBooks->status = _("New");
    	$tableBooks->visible = "on";
    	$tableBooks->category = _("New");
	}
	if (isset($_POST["getPDF"])) $tableBooks->pdf = BOOKI_DIR."/$book/$book.pdf";
	if (isset($_POST["getEpub"])) $tableBooks->epub = BOOKI_DIR."/$book/$book.epub";
	$tableBooks->modified = strftime("%Y-%m-%d %H:%M");
    $tableBooks->save();
	$logger="db updated";
	file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);

	//did they ask for a pdf?...go get it
	if (isset($_POST["getPDF"])){
		$logger="[".date('Y-m-di h:i:s A')."]" ." starting to get PDF";
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
		$updatetext.= "<br>"._("Getting PDF");
		file_put_contents($update,$updatetext);
		$pdfurl=OBJAVI_SERVER_URL."/?book=".$book."&server=".BOOKI_SERVER_TARGET."&mode=book&toc_header=Sisällysluettelo&license=GPLv2";
		$logger="pdf url is $pdfurl";
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
		$gotit = tempnam("tmp/", "pdf_");
		$logger="tempnam is $gotit";
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
		try {
			file_put_contents($gotit, file_get_contents($pdfurl));
		} catch (Exception $e) {
			$html.=_("you are not online");
		$logger="failed (no connection?)";
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
		}

		//find the location of the published epub on the objavi server
		$file = file_get_contents($gotit);
		if(strpos($file, "books/")) {
        		$start=strpos($file, "books/");
        		$end=strpos($file, "\"",$start);
			$pdf_location= OBJAVI_SERVER_URL."/".substr($file,$start,$end-$start);
		$logger="getting pdf from here $pdf_location";
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
		}
		$pdf = file_get_contents($pdf_location);

		file_put_contents("tmp/$book.pdf",$pdf);
		rename("tmp/$book.pdf", BOOKI_DIR."/$book/$book.pdf");
		$logger="pdf is stored here tmp/$book.pdf and being moved to here ".BOOKI_DIR."/$book/$book.pdf";
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
	}

	//did they ask for an epub?...go get it
	if (isset($_POST["getEpub"])){
		$logger="[".date('Y-m-di h:i:s A')."]" ." starting to get EPUB";
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
		$updatetext.= "<br>"._("Getting epub");
		file_put_contents($update,$updatetext);
		$epuburl=OBJAVI_SERVER_URL."/?book=".$book."&server=".BOOKI_SERVER_TARGET."&mode=epub";
		$logger="epub url is $epuburl";
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
		$gotit = tempnam("tmp/", "epub_");
		$logger="tempnam is $gotit";
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
		try {
			file_put_contents($gotit, file_get_contents($epuburl));
		} catch (Exception $e) {
			$html.=_("you are not online");
		$logger="failed (not online?)";
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
		}

		//find the location of the published epub on the objavi server
		$file = file_get_contents($gotit);
		if(strpos($file, "books/")) {
        		$start=strpos($file, "books/");
        		$end=strpos($file, "\"",$start);
			$epub_location= OBJAVI_SERVER_URL."/".substr($file,$start,$end-$start);
		$logger="getting epub from here  $epub_location";
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
		}
		$epub = file_get_contents($epub_location);

		file_put_contents("tmp/$book.epub",$epub);
		rename("tmp/$book.epub", BOOKI_DIR."/$book/$book.epub");
		$logger="moving from tmp/$book.epub to ".BOOKI_DIR."/$book/$book.epub";
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
	}

	//return done page
  	$html ="<h1>"._("GetBook Plugin")."</h1>";
  	$html.=_("Done. Check the book here") . " <a href='index.php?book=$book&chapter=index'>$book</a>.";
  	$html.="<br>"._("Use the bookInfo plugin to edit the details.");
		$logger="done";
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
	return $html;
  }
}


function getbook_dispatcher($name) {
  if($name == "admin") return getbook_admin();
}


function getbook_afterdisplay() {
}

function getbook_adminnav() {
	return	createAdminNav("getbook","get booki");
}

function getbook_initialize() {
  add_hook("admin_nav", "getbook_adminnav");
}

function getbook_install() {

}

function getbook_uninstall() {

}


function getbook_plugin() {
  return Array("info" => Array("author" => "Adam Hyde",
			       "license" => "AGPL",
			       "description" => "Get books from booki.",
			       "requirements" => "For good results use html tidy (set in config).",
			       "version" => "1.0")
	       );
}

?>
