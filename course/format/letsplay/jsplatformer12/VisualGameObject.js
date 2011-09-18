/**
    The base class for all elements that appear in the game.
    @author <a href="mailto:matthewcasperson@gmail.com">Matthew Casperson</a>
    @class
*/
function VisualGameObject()
{
    /**
        The image that will be displayed by this object
        @type Image
    */
    this.image = null;
    
    /**
        Draws this element to the back buffer
        @param dt Time in seconds since the last frame
		@param context The context to draw to
		@param xScroll The global scrolling value of the x axis  
		@param yScroll The global scrolling value of the y axis  
    */
    this.draw = function(/**Number*/ dt, /**CanvasRenderingContext2D*/ context, /**Number*/ xScroll, /**Number*/ yScroll)
    {
        context.drawImage(this.image, this.x - xScroll, this.y - yScroll);
    }
    
    /**
        Initialises this object
        @param image The image to be displayed
		@param x The position on the X axis
        @param y The position on the Y axis
		@param z The depth
    */
    this.startupVisualGameObject = function(/**Image*/ image, /**Number*/ x, /**Number*/ y, /**Number*/ z)
    {
        this.startupGameObject(x, y, z);
        this.image = image;
        return this;
    }
    
    /**
        Clean this object up
    */
    this.shutdownVisualGameObject = function()
    {
        this.image = null;
        this.shutdownGameObject();
    }

    this.shutdown = function()
    {
        this.shutdownVisualGameObject();
    }

    this.collisionArea = function()
    {
        return new Rectangle().startupRectangle(this.x, this.y, this.image.width, this.image.height);
    }
}
VisualGameObject.prototype = new GameObject;