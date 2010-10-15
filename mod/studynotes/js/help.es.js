/**
 * @author fabian
 */
mediabird = {};
mediabird.help = function() {
	var topics = [
		{
			title: "Configurando espacios de trabajo grupales",
			subtitle: "Para alumnos que colaboran juntos en grupo",
			steps: [
				'<h3>Creando una carpeta de apuntes compartida</h3><p style="text-align:left"><img src="'+config.imagePath+'desktop-button-mapview.png'+'" style="float:right;padding-left:10px">Pulse en el mapa de fichas en el enlace "nuevo...".</p>',
				'<h3>Creando un nuevo grupo</h3><ul><li><img src="'+config.imagePath+'desktop-button-community.png'+'" style="float:right;padding-left:10px">Pulse en el botón comunidad</li><li>Pulse en "más..." junto a "Compartir"</li><li>Pulse en "Crear grupo"</li><li>Introduzca el nombre de su grupo de estudio (e.g. "Grupo de estudio de Historia"), introduciendo opcionalmente algunas palabras clave</li><li>Pulse sobre "Crear"</li></ul>',
				'<h3>Creando un nuevo grupo (cont.)</h3><ul><li>Introduzca la dirección de correo de sus compañeros en "Invitar a amigos"</li><li>Pulse en "OK" y luengo en "Cerrar"</li><li>Introduzca el nombre de la carpeta compartida, como "Repaso del Barroco español 4"</li><li>Compártalo pulsando en "Crear"</li><li>Los miembros de su grupo podrán entonces acceder a los apuntes comparidos.</li></ul>',
				'<h3>Tomando apuntes</h3><p>Por ejemplo, puede:</p><ul><li>Resumir las conclusiones de su último encuentro</li><li>Tomar apuntes de un tema importante</li><li>Copiar y pegar apuntes existentes</li></ul><p style="text-align:left">El siguiente paso es mostrarle cómo apuntar notas y plantear dudas o preguntas</p>',
				'<h3>Enlazando apuntes con anotaciones y preguntas</h3><ul><li>Pulse en el rotulador</li><li>Destaque un término o frase</li><li>Inserte una pregunta utilizando "Hacer pregunta"</li></ul><p style="text-align:left">Sus compañeros de estudio podrán responder a esas dudas o preguntas desde sus cuentas de Mediabird.</p>'
			]
		},
		{
			title: "Trabajando con su grupo de estudio",
			subtitle: "Una vez configurada la carpeta de apuntes compartida",
			steps: [
				'<h3>Encontrando apuntes compartidos</h3><ol><li><img src="'+config.imagePath+'desktop-button-community.png'+'" style="float:right;padding-left:10px">Pulse en el botón comunidad</li><li>Pulse en "más..." junto a "Buscar grupos"</li><li>Pulse sobre su grupo de estudio en la lista</li><li>Pulse en "Carpetas de apuntes compartidas con este grupo"</li><li>Seleccione la carpeta de apuntes que está buscando</li></ol>',
				'<h3>Ampliando apuntes o notas</h3><p>Los apuntes compartidos pueden ser fácilmente ampliados: ¡Basta con pulsar sobre los apuntes y comenzar a escribir!</p>',
				'<h3>Encontrando preguntas</h3><ol><li><img src="'+config.imagePath+'desktop-button-community.png'+'" style="float:right;padding-left:10px">Pulse sobre el botón comunidad</li><li>Podrá encontrar dudas no resultas en "Noticias de mis grupos", siempre y cuando haya alguna.</li><li>Pulse en el número de las preguntas abiertas para abrirlas y ver la lista completa.</ol>',
				'<h3>Planteando dudas o preguntas</h3><ol><li>Abra los apuntes en los que tiene dudas o bien abra una hoja vacía y escriba la duda.</li><li>Pulse sobre el rotulador</li><li>Seleccione el término o frase sobre la que quiere referir la pregunta</li><li>Pulse en "Hacer pregunta"</li><li>Ahora introduzca su duda o pregunta</li></ol>'

			]
		},
		{
			title: "Preparando exámenes utilizando los apuntes de tu grupo de estudio",
			subtitle: "Una vez que has acumulado apuntes y preguntas",
			steps: [
				'<h3>Repasando dudas resueltas</h3><ol><li>Pulse sobre la caja de fichas</li><li>Seleccione el modo de repaso</li><li>Desplácese por las preguntas e intente responderlas</li></ol>',
				'<h3>Yendo más allá en el uso de los apuntes</h3><ul><li>Puede utilizar los apuntes para crear presentaciones o para responder preguntas y tareas de un seminario.</li></ul>'
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
		
		$(document.createElement("h2")).text("Quick Start").appendTo(helpBox);
		
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

