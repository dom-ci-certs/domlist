<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <title>domlist</title>
		
		
		<!-- http://developer.yahoo.com/yui/articles/hosting/?connectioncore&json&tabview&MIN -->
		<!-- Combo-handled YUI CSS files: -->
		<link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/combo?2.8.0r4/build/reset-fonts/reset-fonts.css&2.8.0r4/build/tabview/assets/skins/sam/tabview.css"> 
		<!-- Combo-handled YUI JS files: -->
		<script type="text/javascript" src="http://yui.yahooapis.com/combo?2.8.0r4/build/yahoo-dom-event/yahoo-dom-event.js&2.8.0r4/build/connection/connection_core-min.js&2.8.0r4/build/json/json-min.js&2.8.0r4/build/element/element-min.js&2.8.0r4/build/tabview/tabview-min.js"></script>

		<style type="text/css">
			body {
				background-color:#fff;
				margin:10px;
			}
		</style>
	</head>

<body class="yui-skin-sam">
	
	<div id="tab-container"></div>
	
	
	<script type="text/javascript">
        //<![CDATA[
		
		YAHOO.util.Event.onDOMReady(function() {
			var Dom = YAHOO.util.Dom,
				Event = YAHOO.util.Event,
				Lang = YAHOO.lang,
				Con = YAHOO.util.Connect; 
			
			var BASE_URL = 'http://www.dom.de/domlist/index.php?format=json'
			
			
			//-- alle listen auslesen
			Con.asyncRequest('GET', BASE_URL + '&rawaction=lists', {
				success: function(o) {
					try { data = Lang.JSON.parse(o.responseText); } 
					catch (x) { alert('Datenfehler'); return; }
					
					if (data.response.state == 0) { alert(data.response.error); return; }
					
					//-- fuer jede liste einen tab anlegen
					var tabView = new YAHOO.widget.TabView(); 
					
					var lists = data.response.lists.list;
					var len = lists.length;
					
					for (var i=0; i<len; i++) {
						tabView.addTab( new YAHOO.widget.Tab({
							label: lists[i].title,
							content: ''
						}));

					}
					
					tabView.appendTo('tab-container');
					
					tabView.on('beforeActiveTabChange', function(ev) {
						var oldTab = ev.prevValue,
							newTab = ev.newValue;
						
						
						console.log(newTab._configs.label.value);
						return;					
						if (newTab === tabView.get('tabs')[1]) {
							//HTML tab
							myEditor.saveHTML();
							myEditor.hide();
						} else {
							//Editor tab
							myEditor.show();
							myEditor.setEditorHTML(myEditor.get('textarea').value);
						}
					});
					
					
					// myTabs.set('activeIndex', i);
				}

			}, null);
			
		});
		
		//]]>
    </script>

</body>


</html>