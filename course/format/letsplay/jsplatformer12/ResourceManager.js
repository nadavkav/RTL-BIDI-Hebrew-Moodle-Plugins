/**
    A database for the external resources used by the game
    @author <a href="mailto:matthewcasperson@gmail.com">Matthew Casperson</a>
    @class
*/
function ResourceManager()
{
	/** An array of the names of the images supplied to the startupResourceManager
		function. Since the images are referenced by creating new properties
		of the ResourceManager class this collection allows a developer to
		know which of the ResourceManager properties are images, and (by
		elimination) those that are not
		@type Array
	*/
    this.imageProperties = null;

	/**
        Initialises this object
		@param images	An array of objects with the name and src properties
        @return 		A reference to the initialised object
    */
    this.startupResourceManager = function(/**Array*/ images)
    {
        // set the global variable
		g_ResourceManager = this;

        // initialize internal state.
        this.imageProperties = new Array();

        // for each image, call preload()
        for ( var i = 0; i < images.length; i++ )
		{
			// create new Image object and add to array
			var thisImage = new Image;
			this[images[i].name] = thisImage;
			this.imageProperties.push(images[i].name);

			// assign the .src property of the Image object
			thisImage.src = 'format/letsplay/jsplatformer12/' + images[i].src;
		}

        return this;
    }
}