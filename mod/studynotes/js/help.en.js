/**
 * @author fabian
 */
mediabird = {};
mediabird.help = function() {
	var topics = [
		{
			title: "Setting up study group workspace",
			subtitle: "For students working together in a group",
			steps: [
				'<h3>Creating shared note folder</h3><p style="text-align:left"><img src="'+config.imagePath+'desktop-button-mapview.png'+'" style="float:right;padding-left:10px">Click on the map overview and on "new...".</p>',
				'<h3>Creating a new group</h3><ul><li><img src="'+config.imagePath+'desktop-button-community.png'+'" style="float:right;padding-left:10px">Click on the community button</li><li>Click on "more..." next to "Sharing"</li><li>Click on "Create group"</li><li>Enter the name of your study group (e.g. "Study Group Anatomy"), optionally enter some keywords</li><li>Click on "Create"</li></ul>',
				'<h3>Creating a new group cont.</h3><ul><li>Enter the email addresses of your study mates at "Invite friends"</li><li>Click on "OK" and then "Close"</li><li>Enter a name for the shared note folder such as "Review Anatomy Meeting 4"</li><li>Create the share with a click on "Create"</li><li>The members of your group can now access the shared notes!</li></ul>',
				'<h3>Taking notes</h3><p>For example, you can now:</p><ul><li>Sum up the results of your last meeting</li><li>Take notes on an important subject</li><li>Copy and paste existing notes</li></ul><p style="text-align:left">The next step is to show you how to annotate notes and ask questions</p>',
				'<h3>Linking notes with annotations and questions</h3><ul><li>Click on the text marker</li><li>Highlight a term or sentence</li><li>Insert a question using "Ask question"</li></ul><p style="text-align:left">Your study fellows can now answer that question in their Mediabird account.</p>'
			]
		},
		{
			title: "Working with your study group",
			subtitle: "Having set up the shared note folder",
			steps: [
				'<h3>Finding shared notes</h3><ol><li><img src="'+config.imagePath+'desktop-button-community.png'+'" style="float:right;padding-left:10px">Click on the community button</li><li>Click on "more..." next to "Search groups"</li><li>Click on your study group in the list</li><li>Click on "Note folders shared with this group"</li><li>Select the note folder you\'re looking for</li></ol>',
				'<h3>Extending notes</h3><p>Shared notes can be easily extended: Just click on the notes and start typing!</p>',
				'<h3>Finding questions</h3><ol><li><img src="'+config.imagePath+'desktop-button-community.png'+'" style="float:right;padding-left:10px">Click on the community button</li><li>You will now find unsolved questions under "News from my groups" in case there are any.</li><li>Click on the number of open questions to view the complete list.</ol>',
				'<h3>Asking questions</h3><ol><li>Open the notes you\'ve got a qestion to or explain the term or connection you don\'t get on an empty note sheet (<em>do not just write down the question itself!</em>)</li><li>Click on the text marker</li><li>Select the term or sentence you want to relate a question to</li><li>Click on "Ask question"</li><li>Now enter your question</li></ol>'

			]
		},
		{
			title: "Preparing exams using the notes of your study group",
			subtitle: "Once you have collected notes and questions",
			steps: [
				'<h3>Revising solved questions</h3><ol><li>Click on the card box</li><li>Select a revision mode</li><li>Go through the questions and try answering them</li></ol>',
				'<h3>Making further use of the notes</h3><ul><li>You can use the notes to create presentations and handouts or to answer tutorial questions and assignments.</li></ul>'
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

