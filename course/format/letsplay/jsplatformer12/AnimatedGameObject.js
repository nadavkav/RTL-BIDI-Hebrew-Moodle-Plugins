/**
    Displays an animated Game Object
    @author <a href="mailto:matthewcasperson@gmail.com">Matthew Casperson</a>
    @class
*/
function AnimatedGameObject()
{
    /**
        Defines the current frame that is to be rendered
        @type Number
     */
    this.currentFrame = 0;
    /**
        Defines the frames per second of the animation
        @type Number
     */
    this.timeBetweenFrames = 0;
    /**
        The number of individual frames held in the image
        @type Number
     */
    /**
        Time until the next frame
        @type number
     */
    this.timeSinceLastFrame = 0;
    /**
        The width of each individual frame
        @type Number
     */
    this.frameWidth = 0;

    /**
        Initialises this object
        @param image The image to be displayed
		@param x The position on the X axis
        @param y The position on the Y axis
		@param z The depth
        @param frameCount The number of animation frames in the image
        @param fps The frames per second to animate this object at
    */
    this.startupAnimatedGameObject = function(/**Image*/ image, /**Number*/ x, /**Number*/ y, /**Number*/ z, /**Number*/ frameCount, /**Number*/ fps)
    {
        if (frameCount <= 0) throw "framecount can not be <= 0";
        if (fps <= 0) throw "fps can not be <= 0"

        this.startupVisualGameObject(image, x, y, z);
        this.currentFrame = 0;
        this.frameCount = frameCount;
        this.timeBetweenFrames = 1/fps;
        this.timeSinceLastFrame = this.timeBetweenFrames;
        this.frameWidth = this.image.width / this.frameCount;

        return this;
    }

    this.setAnimation = function(/**Image*/ image, /**Number*/ frameCount, /**Number*/ fps)
    {
        if (frameCount <= 0) throw "framecount can not be <= 0";
        if (fps <= 0) throw "fps can not be <= 0"

        this.image = image;
        this.currentFrame = 0;
        this.frameCount = frameCount;
        this.timeBetweenFrames = 1/fps;
        this.timeSinceLastFrame = this.timeBetweenFrames;
        this.frameWidth = this.image.width / this.frameCount;
    }

    /**
        Draws this element to the back buffer
        @param dt Time in seconds since the last frame
		@param context The context to draw to
		@param xScroll The global scrolling value of the x axis
		@param yScroll The global scrolling value of the y axis
    */
    this.draw = function(/**Number*/ dt, /**CanvasRenderingContext2D*/ context, /**Number*/ xScroll, /**Number*/ yScroll)
    {
        var sourceX = this.frameWidth * this.currentFrame;
        context.drawImage(this.image, sourceX, 0, this.frameWidth, this.image.height, this.x - xScroll, this.y - yScroll, this.frameWidth, this.image.height);

        this.timeSinceLastFrame -= dt;
        if (this.timeSinceLastFrame <= 0)
        {
           this.timeSinceLastFrame = this.timeBetweenFrames;
           ++this.currentFrame;
           this.currentFrame %= this.frameCount;
        }
    }

    this.shutdownAnimatedGameObject = function()
    {
        this.shutdownVisualGameObject();
    }

    this.shutdown = function()
    {
        this.shutdownAnimatedGameObject();
    }

    this.collisionArea = function()
    {
        return new Rectangle().startupRectangle(this.x, this.y, this.frameWidth, this.image.height);
    }
}

AnimatedGameObject.prototype = new VisualGameObject;