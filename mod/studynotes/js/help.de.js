/**
 * @author fabian
 */
mediabird = {};
mediabird.help = function() {
	var topics = [
		{
			title: "Arbeitsbereich für Lerngruppe einrichten",
			subtitle: "Für Studierende in einer Arbeitsgruppe",
			steps: [
				'<h3>Gemeinsame Notizmappe erstellen</h3><p style="text-align:left"><img src="'+config.imagePath+'desktop-button-mapview.png'+'" style="float:right;padding-left:10px">Klicke auf die Mappen-Übersicht und dann auf "neu...".</p>',
				'<h3>Neue Gruppe anlegen</h3><ul><li><img src="'+config.imagePath+'desktop-button-community.png'+'" style="float:right;padding-left:10px">Auf den Community-Button klicken</li><li>Auf "mehr..." neben "Freigabe" klicken</li><li>Auf "Gruppe erstellen" klicken</li><li>Namen der Arbeitsgruppe eingeben (zum Beispiel "Arbeitsgruppe Anatomie"), optional Stichwörter festlegen</li><li>Auf "Erstellen" klicken</li></ul>',
				'<h3>Neue Gruppe anlegen cont.</h3><ul><li>Bei "Freunde einladen" die Email-Adressen der Lernpartner eingeben</li><li>Klicke auf "OK" und dann "Schließen"</li><li>Notizmappe einen Namen geben wie "Nachbereitung Anatomie Termin 4"</li><li>Freigabe erstellen mit einem Klick auf "Erstellen"</li><li>Die Mitstreiter haben nun Zugriff auf die gemeinsame Notizmappe!</li></ul>',
				'<h3>Notizen anfangen</h3><p>Du kannst nun zum Beispiel:</p><ul><li>Euer letztes Arbeitstreffen zusammenfassen</li><li>Zu einem für eure Lerngruppe wichtigen Thema Notizen anlegen</li><li>Bereits existente Notizen einfügen</li></ul><p style="text-align:left">Im nächsten Schritt siehst du, wie sich Notizen mit Anmerkungen und Fragen versehen lassen</p>',
				'<h3>Notizen mit Anmerkungen und Fragen verknüpfen</h3><ul><li>Klicke rechts oben auf den Textmarker</li><li>Markiere einen Begriff oder einen Satz</li><li>Füge eine Frage mit "Frage stellen" ein und gib eine Frage ein</li></ul><p style="text-align:left">Deine Lernpartner können diese Frage nun auf ihrem Bildschirm sehen.</p>'
			]
		},
		{
			title: "Mit Arbeitsgruppe in Mediabird arbeiten",
			subtitle: "Wenn der Arbeitsbereich eingerichtet wurde",
			steps: [
				'<h3>Notizen finden</h3><ol><li><img src="'+config.imagePath+'desktop-button-community.png'+'" style="float:right;padding-left:10px">Klicke auf den Community-Button</li><li>Klicke auf "mehr..." neben "Gruppen suchen"</li><li>Wähle deine Lerngruppe aus</li><li>Klicke auf "In der Gruppe freigegebene Notizmappen"</li><li>Wähle die betreffende Notizmappe</li></ol>',
				'<h3>Notizen ergänzen</h3><p>Freigegebene Notizen können direkt ergänzt werden: Einfach auf die Notizen klicken und tippen!</p>',
				'<h3>Fragen finden</h3><ol><li><img src="'+config.imagePath+'desktop-button-community.png'+'" style="float:right;padding-left:10px">Klicke auf den Community-Button</li><li>Dann findest du unter "Neues von deinen Gruppen" die offenen Fragen, falls es welche gibt</li><li>Klicke auf die Zahl an Fragen, um zur Liste zu gelangen.</li></ol>',
				'<h3>Fragen stellen</h3><ol><li>Notizen öffnen, zu denen Unklarheiten bestehen oder auf einem leeren Notizzettel kurz den unklaren Begriff oder Zusammenhang schildern (<em>nicht die Frage auf den Notizblock schreiben!</em>)</li><li>Den Textmarker anklicken</li><li>Den unklaren Begriff oder Satz mit der Maus markieren</li><li>"Frage stellen" anklicken</li><li>Frage eingeben</li></ol>'

			]
		},
		{
			title: "Mit Notizen der Arbeitsgruppe auf Prüfung vorbereiten",
			subtitle: "Wenn ihr schon Notizen und Fragen verwendet habt",
			steps: [
				'<h3>Gelöste Fragen wiederholen</h3><ol><li>Auf Karteikasten klicken</li><li>Modus auswählen</li><li>Nacheinander Karteikarten durchgehen und beantworten</li></ol>',
				'<h3>Notizen weiterverwenden</h3><ul><li>Ihr könnte die gemeinsame Mitschrift anschließend zum Lernen oder als Basis für Vorträge, Hausarbeiten, Übungsabgaben oder Essays verwenden </li></ul>'
			]
		}
	];
	
	var helpBox;
	this.load = function(container) {
		helpBox = $(document.createElement("div")).prependTo(container);
		helpBox.css({
			"background": "#FAFAFA",
			"padding": "0 10px",
			"position": "absolute",
			"top": "10px",
			"right": "-200px",
			"height": "380px",
			"width": "180px",
			"border": "1px solid #bbb",
			"-moz-border-radius": "1px 8px 8px 1px",
			"-webkit-border-top-right-radius": "8px",
			"-webkit-border-bottom-right-radius": "8px",
			"-webkit-border-top-left-radius": "1px",
			"-webkit-border-bottom-left-radius": "1px"
		});
		
		setupToolbar();
		
		showHome();
		
	}
	var toolbar;
	function setupToolbar() {
		toolbar = utility.createToolbar().hide().css({"background-color":"transparent","margin-top":5}).appendTo(helpBox);
		
		lang.PREVIOUS="Previous";
		lang.NEXT="Next";
		
		toolbar.homeButton = utility.createButton("dialog-information.png",null,lang.HELP).appendTo(toolbar);
		
		toolbar.homeButton.click(showHome);
		
		utility.createButtonSeparator().appendTo(toolbar);
		
		toolbar.backButton = utility.createButton("go-previous.png",null,lang.PREVIOUS).appendTo(toolbar);
		toolbar.nextButton = utility.createButton("go-next.png",null,lang.NEXT).appendTo(toolbar);
		
		toolbar.backButton.click(function() {
			if(currentStep>0) {
				currentStep--;
				updateStep();
			}
		});
		
		toolbar.nextButton.click(function() {
			if(currentStep<currentTopic.steps.length-1) {
				currentStep++;
				updateStep();
			}
		})
	}
	
	function showHome(){
	
		toolbar.hide();
		
		helpBox.children().not(toolbar).remove();
		
		$(document.createElement("h2")).text("Schnelleinstieg").appendTo(helpBox);
		
		var list = $(document.createElement("ol")).appendTo(helpBox);
		
		$.fn.each.call(topics, function(){
			var item = $(document.createElement("li")).appendTo(list);
			var link = $(document.createElement("a")).css({
				"display": "block",
				"font-weight": "bold"
			}).attr("href", "javascript:void(0)").appendTo(item);
			
			link.text(this.title).bind("click", this, function(event){
				currentTopic = event.data;
				currentStep = 0;
				updateStep();
			});
			$(document.createElement("span")).text(this.subtitle).appendTo(item);
		});
	}
	
	var currentTopic;
	var currentStep;
	var titleBox;
	function updateStep() {
		toolbar.fadeIn();
	
		helpBox.children().not(toolbar).remove();
		
		titleBox=$(document.createElement("div")).appendTo(helpBox);
		
		$(document.createElement("p")).css({
			"position":"absolute",
			"top":5,
			"right":10
		}).text((currentStep+1).toString()+"/"+currentTopic.steps.length.toString()).appendTo(titleBox);
		
		var content = $(document.createElement("div")).appendTo(helpBox);
		
		content.html(currentTopic.steps[currentStep]);
	}
}

