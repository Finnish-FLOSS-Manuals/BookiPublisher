<?php

function getbookgears_admin() {
	$html='';
  if(!is_dir("tmp")) mkdir("tmp");
  $update = tempnam("tmp/", "update_");
  $updatetext = _("Processing...")."<br>"._("Requesting book");
  $book = array_value($_POST, 'book');

  // get users books
  $tableUsers = new Axon(DB_TABLE_USERS);
  $books = array();
  if ($username = array_value($_SESSION, 'username')) {
      $tableUsers->load("username='$username'");
      if (!$tableUsers->dry()) {
          $books = unserialize($tableUsers->books);
          $email = $tableUsers->email;
      }
  }

  //need to pass tmpfile for updates since the html is not updated until the end
  $tmpfilename = array_value($_GET, 'tmpfilename');

  //if no request then make a form
  if ($book==''){
  	$html ="<h1>"._("GetBook (GEARS) Plugin")."</h1>";
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
 	$html .= "<font color='red'>Warning</font> Only use this for updating books. If you are pushing abook for the first time then use the older plugin ('get book')<br><br>";
	$html.="<div id='bookform'><form method='POST' action='admin.php?plugin=getbookgears&tmpfilename=".basename($update)."'>";
	$html.="<input type='hidden' name='tmpfilename' value='".basename($update)."'>";
	$html.=_("Choose Book")."<br><select id='book' name='book'>";
    foreach ((array)$books as $key => $value) {
        $html .= "<option value=\"$key\">$value</option>";
    }
   	$html.= "</select><br><br>";
	//$html.="<table><tr><td>"._("Title")." : </td><td><input type=text name='title' style='background-color:#e1e1e1' READONLY></input></td></tr>";
	$html.="<tr><td>"._("Your Email")." : </td><td><input type=text name='notifyemail' value='$email' ></input> (for notification of completion)</td></tr></table>";
	$html.="<br><input type=CHECKBOX name='getBook' CHECKED>"._("get html")."</input>";
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

 	# make a directory for the book, dont make it if it exists
	if (!is_dir(BOOKI_DIR."/$book")) mkdir(BOOKI_DIR."/$book");

	# Create our client object.
	$gmclient= new GearmanClient();

	# Add default server (localhost)
   	# if you are having errors connecting with gears then make sure that :
	# 1. you are running the gears daemon
	# 2. the php extension *and* the daemon are using the same port - this is not always the case by default
	$gmclient->addServer();
	$email = $_POST["notifyemail"];
	#needs if statement

	$the_array = array( $book, $email );
	$serialized = serialize($the_array);

 	if (isset($_POST["getEpub"])){
		$result = $gmclient->doBackground("getepub", $serialized );
	}
 	if (isset($_POST["getPDF"])){
		$result = $gmclient->doBackground("getpdf", $serialized);
	}
 	if (isset($_POST["getBook"])){
		$result = $gmclient->doBackground("getbook", $serialized);
	}
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


	#return info
  	$html.="<h1>"._("GetBook (GEARS) Plugin")."</h1>";
  	$html.=_("Your request has been added to a job que. You will recieve an email when it is complete.");
  	$html.="<br>"._("Use the bookInfo plugin to edit the details.");
		$logger="$book added to que";
		file_put_contents("log/log.txt",$logger."\n",FILE_APPEND);
	return $html;
  }
}


function getbookgears_dispatcher($name) {
  if($name == "admin") return getbookgears_admin();
}


function getbookgears_afterdisplay() {
}

function getbookgears_adminnav() {
	return	createAdminNav("getbookgears","get booki gears");
}

function getbookgears_initialize() {
  add_hook("admin_nav", "getbookgears_adminnav");
}

function getbookgears_install() {

}

function getbookgears_uninstall() {

}


function getbookgears_plugin() {
  return Array("info" => Array("author" => "Adam Hyde",
			       "license" => "AGPL",
			       "description" => "Get books from booki using que manger (gearman).",
			       "requirements" => "For good results use html tidy (set in config).",
			       "version" => "1.0")
	       );
}

?>
