<?php
// all config stuff

//this is used by the read plugin, if set to false then read will
//use the info 'title' in json when displaying the dirlists
//instead of the raw dir name

define('DISPLAY_DIRS','false');

define('TEMPLATE', 'my_template_name');

//this is the prefix used for the templates eg . fm_read.tmpl
define('DEFAULT_TEMPLATE','fm');

//this is not used
define('BLOG_MAX_POSTS','10');

//for this oyu need html tidy installed
define('USE_TIDY','true');

//this is where all the books go
define('BOOKI_DIR','_booki');

//this is where all the templates go
define('TEMPLATES_DIR','_templates');

//make sure the target is not with http:// or a trailing slash etc
define('BOOKI_SERVER_TARGET','fi-new.flossmanuals.net/kirjoita');

//make sure objavi has no trailing slash
define('OBJAVI_SERVER_URL','http://objavi.booki.cc');

define('INSTALLED_DIR', dirname(__FILE__));

// db stuff
define('DB_DSN', 'sqlite:' . INSTALLED_DIR . '/db/booki.sqlite');
define('DB_USER', null);
define('DB_PASSWORD', null);
define('DB_TABLE_USERS', 'users');
define('DB_TABLE_RSS', 'rssinfo');
define('DB_TABLE_BLOGS', 'blogs');
define('DB_TABLE_BOOKS', 'books');
define('DB_TABLE_LOG', 'log');

define('BOOKI_SALT', 'wwetr74vejhg73v47tyg7thf74g');
define('LOG_LIMIT', 50);
