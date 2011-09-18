/**
    An object that causes the level to end when it it touched
    @author <a href="mailto:matthewcasperson@gmail.com">Matthew Casperson</a>
    @class
*/
function LevelEndPost()
{

    this.startupLevelEndPost = function(/**Image*/ image, /**Number*/ x, /**Number*/ y, /**Number*/ z)
    {
        this.startupAnimatedGameObject(image, x, y, z, 4, 10);
        return this;
    }

    this.shutdown = function()
    {
        this.shutdownLevelEndPost();
    }

    this.shutdownLevelEndPost = function()
    {
        this.shutdownAnimatedGameObject();
    }

    /**
        Updates the object
        @param dt The time since the last frame in seconds
        @param context The drawing context
        @param xScroll The global scrolling value of the x axis
        @param yScroll The global scrolling value of the y axis
    */
	this.update = function (/**Number*/ dt, /**CanvasRenderingContext2D*/context, /**Number*/ xScroll, /**Number*/ yScroll)
    {
        if (this.collisionArea().intersects(g_player.collisionArea()))
        {
            g_ApplicationManager.openMainMenu();
            this.shutdown();            
        }
    }
}
LevelEndPost.prototype = new AnimatedGameObject;