/**
    Represents a powerup in the game
    @author <a href="mailto:matthewcasperson@gmail.com">Matthew Casperson</a>
    @class
*/
function Powerup()
{
    /** The value of the powerup
        @type Number
     */
    this.value = 0;
    this.srcurl = '';
    /** The current position on the sine wave
        @type Number
     */
    this.sineWavePos = 0;
    /** How quickly the powerup cycles through the sine wave
        @type Number
     */
    this.bounceTime = 1;
    /** The speed to increment the sineWavePos value at
        @type Number
     */
    this.bounceSpeed = Math.PI / this.bounceTime;
    /** The height of the powerups bounce
        @type Number
     */
    this.bounceHeight = 10;

    /**
        Initialises this object
        @param value        The value (score) of this powerup
        @param image        The image to be displayed
        @param x            The position on the X axis
        @param y            The position on the Y axis
        @param z            The depth
        @param frameCount   The number of animation frames in the image
        @param fps          The frames per second to animate this object at
     */
    this.startupPowerup = function(/**String*/ url, /**Number*/ value, /**Image*/ image, /**Number*/ x, /**Number*/ y, /**Number*/ z, /**Number*/ frameCount, /**Number*/ fps)
    {
        this.startupAnimatedGameObject(image, x, y - this.bounceHeight, z, frameCount, fps);
        this.value = value;
        this.srcurl = url;
        return this;
    }

    this.shutdownPowerup = function()
    {
        this.shutdownAnimatedGameObject();
    }

    this.shutdown = function()
    {
        this.shutdownPowerup();
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
        var lastSineWavePos = this.sineWavePos;
        this.sineWavePos += this.bounceSpeed * dt;
        this.y += (Math.sin(this.sineWavePos) - Math.sin(lastSineWavePos)) * this.bounceHeight;

        if (this.collisionArea().intersects(g_player.collisionArea()))
        {
            this.shutdown();
            //g_score += this.value;
            var load = window.open(this.srcurl,'','scrollbars=no,menubar=no,height=600,width=800,resizable=yes,toolbar=no,location=no,status=no');
            g_ApplicationManager.updateScore();
        }
    }
}

Powerup.prototype = new AnimatedGameObject;