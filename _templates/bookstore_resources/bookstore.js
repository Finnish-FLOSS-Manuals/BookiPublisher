var fm_data = {"title":"FLOSS Manuals's Storefront","url":"http:\/\/stores.lulu.com\/flossmanuals","items":[{"title":"Komentorivin perusteet","url":"http:\/\/www.lulu.com\/product\/paperback\/komentorivin-perusteet\/18883624","description":"FLOSS Manualsin komentorivin perusteet -opas suomeksi!","thumb":"http://www.lulu.com/items/volume_74/12561000/12561466/3/preview/detail_12561466.jpg?1333532999","date":"Wed, 08 Feb 2012 21:00:00"},{"title":"LibreOffice","url":"http:\/\/www.lulu.com\/product\/paperback\/libreoffice\/18883781","description":"LibreOffice-oppaassa käsitellään vapaan ja avoimen toimisto-ohjelman käyttö tekstinkäsittelyyn, taulukkolaskentaan ja presentaatioiden tekemiseen.","thumb":"http://www.lulu.com/items/volume_74/12573000/12573454/2/preview/detail_12573454.jpg?1333532901","date":"Wed, 08 Feb 2012 21:00:00"}]};


var FM = {
    'config': {
        'show': 'normal',  // normal, random
        'items': 2,
	'slideshow': false,
	'timeout': 5,
	'preferred': null,
	'paging': true,
	'next_desc': 'more books &gt;&gt;',
	"prev_desc": "&lt;&lt; back"

    },

    current_page: 0,
    first_run: true,

    'previous_page': function() {
	if(!(FM.first_run && FM.config.preferred))
	    FM.current_page -= 1;

	FM.redraw();
    },

    'next_page': function() {
	if(!(FM.first_run && FM.config.preferred))
	    FM.current_page += 1;

	FM.redraw();
    },

    'create_item': function(i) {
       var s = '<li class="lulu-item">';
	s += '<a class="lulu-item-buynow" href="' + fm_data.items[i].url + '">';
       s += '<img class="lulu-item-thumbnail" src="' + fm_data.items[i].thumb + '" />';
	s += '</a>';
       s += '<h2 class="lulu-item-title"><a href="' + fm_data.items[i].url + '">' + fm_data.items[i].title + '</a></h2>';
       s += '<div class="lulu-item-description">' + fm_data.items[i].description + '</div>';
       s += '<a class="lulu-item-buynow" href="' + fm_data.items[i].url + '">Osta nyt!</a>';
       s += '</li>';

       return s;
    },

    'show': function () {
	var s = '';

	if(FM.config.preferred != null && FM.first_run == true) {
	    for(var i = 0; i < FM.config.preferred.length; i++) {
		for(var k = 0; k < fm_data.items.length; k++ ) {
		    if(FM.config.preferred[i].toLowerCase() == fm_data.items[k]["title"].toLowerCase()) {
			s += FM.create_item(k);
		    }
		}
	    }
	} else {
	    for (var i = FM.current_page*FM.config.items+0; i < FM.current_page*FM.config.items+FM.config.items; i++) {
		if(fm_data.items[i])
		    s += FM.create_item(i);
	    }
	}
	
        if(FM.config.paging) {
	    s += '<div id="lulu-more">';
	    if(FM.current_page != 0)
   		s += '<a class="more" href="javascript:void(0)" onclick="FM.previous_page()">'+FM.config.prev_desc+'</a><br/>';
	    
	    if((1+FM.current_page)*FM.config.items < fm_data.items.length) 
   		s += '<a class="more" href="javascript:void(0)" onclick="FM.next_page()">'+FM.config.next_desc+'</a>';
	    s += '</div>';
	}

	return s;
    },

    'redraw': function() {
	FM.first_run = false;
	var _elem = document.getElementById("lulu-storefront-items");

	if(_elem) {
	    _elem.innerHTML = FM.show();
	}
    },

    'interval': function() {
	if(!FM.first_run) {
	    if((FM.current_page+1)*FM.config.items < fm_data.items.length) 
		FM.current_page += 1;
	    else
		FM.current_page = 0;
	}
	
	FM.redraw();
    },

    'init': function(config) {
        if(config) {
            for(var k in config) {
		FM.config[k] = config[k];
	    }
	}

	if(FM.config["show"] == "random") {
	    var new_list = new Array();

	    while(new_list.length != fm_data.items.length) {
		while(1) {
		    var _r     = Math.floor(Math.random()*fm_data.items.length);

		    if(fm_data.items[_r] != null) {
			new_list.push(fm_data.items[_r]);
			fm_data.items[_r] = null;
			break;
		    }
		}
	    }
	    fm_data.items = new_list;
	}

	FM.first_run = true;
        document.write('<div id="lulu-storefront">')
        document.write('<h1 id="lulu-storefront-title"><a href="' + fm_data.url + '">' + fm_data.title + '</a></h1>');
        document.write('<ul id="lulu-storefront-items">');

	document.write(FM.show());

        document.write('</ul>');
        document.write('</div>');

	if(FM.config.slideshow) {
	    setInterval('FM.interval()', FM.config.timeout*1000);
	}

	if(FM.config.paging) {
	    FM.current_page = 0;
	}
	
    }
};

