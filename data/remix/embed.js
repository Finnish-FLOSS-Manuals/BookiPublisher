var _config = { "width":           "854",
                "height":          "500px",
		"padding":         "3px", 
		"color":           "#ffe5cc",
		"indexwidth":      "180px",
		"indexbackground": "#ff7f00",
                "framewidth":      "670",
                "frameheight":     "500px",
		"skin":            "ajax"};

var _style = { "title":   "color: #ffe5cc;font-size:20px;font-family: Arial,verdana, sans-serif;font-weight: bold;margin-left:20px",
	       "frame":   "position:relative;left:-18px;top:0px;margin-left:0px;padding:0px;frame-border:0px;border-width:0px",
	       "index":   "position:relative;left:0px;top:0px;font-size: 10px;font-family: Arial,verdana, sans-serif;font-weight: bold; line-height:14px",
	       "embed":   "font-size: 10px;font-family: Arial,verdana, sans-serif;font-weight: bold; line-height:14px",
	       "topic":   "font-weight: bold",
	       "heading": "font-weight: bold; color:black;font-family: Arial,verdana, sans-serif;font-size:10px;padding-top: 4px;padding-bottom: 4px",
	       "list":    "list-style-type: none; padding-left: 20px; margin-left: 0px;margin-top:2px;",
	       "link":    "color: #ffe5cc; text-decoration: none; font-weight: bold"};

function createViewer() {
   var e = $("#flossembed");

   var embedStyle = "";
   var indexStyle = "";
   var frameStyle = "";
   var titleStyle = "";

   if(FLOSSConfig.style) {
       embedStyle = "width: "+_config["width"]+"; height: "+_config["height"]+"; "+_config["padding"]+"; color:"+_config["color"]+"; "+_style["embed"]; 
       indexStyle = "float: left; background-color: "+_config["indexbackground"]+"; width: "+_config["indexwidth"]+"; height: "+_config["height"]+"; "+_style["index"];
       frameStyle = "float: right; width: "+_config["framewidth"]+";  height: "+_config["frameheight"]+"; "+_style["frame"];
       titleStyle = _style["title"];
   }

   var viewerStructure = '<div style="'+embedStyle+'"><div style="'+indexStyle+'" id="flosstopics">';
   viewerStructure += '<div align="left"><br/><h1 style="'+titleStyle+'">'+FLOSSConfig.title+'</h1>';
   viewerStructure += '<hr color="#ffe5cc" height="1" width="140"></div></div>';	    
   viewerStructure += '<iframe id="flossframe" name="flossframe" style="'+frameStyle+'"/>';
   viewerStructure += '</div>';	  

   e.html(viewerStructure);
}


function initViewer() {
  // read user defined config 

  for(var elem in FLOSSConfig.config) {
     _config[elem] = FLOSSConfig.config[elem];
  };

  for(var elem in FLOSSConfig.style) {
     _style[elem] = FLOSSConfig.style[elem];
  }


  // create our viewer
  createViewer();

  // populate it with topic names
  var elems = "";

  var topicStyle   = "";
  var headingStyle = "";
  var linkStyle    = "";
  var listStyle    = "";

  if(FLOSSConfig.style) {
     topicStyle   = _style["topic"];
     headingStyle = _style["heading"];
     linkStyle    = _style["link"];
     listStyle    = _style["list"];
  }

  for(var i = 0; i < FLOSSConfig.pages.length; i++) {
    var page = FLOSSConfig.pages[i];

    if(page[3] == 1) {
       elems += '<li style="'+topicStyle+'"><a style="'+linkStyle+'" target="flossframe" href="http://en.flossmanuals.net/booki/'+page[0]+'/'+page[1]+'?skin='+_config["skin"]+'">'+page[2]+'</a>';
    } else {
       elems += '<li class="heading" style="'+headingStyle+'">'+page[2]+'</li>';
    }

  }
  elems = '<ul style="'+listStyle+'">'+elems+'</ul>';
  $("#flosstopics").html(elems);


}

/* initialize our viewer */

 $(document).ready(function(){ 
    initViewer();
 }); 
