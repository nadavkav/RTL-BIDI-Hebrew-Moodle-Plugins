/**
    A class to represent the player on the screen
    @author <a href="mailto:matthewcasperson@gmail.com">Matthew Casperson</a>
    @class
*/
function Player()
{
    /** The maximum height of the jump
        @type Number
     */
    this.jumpHeight = 64;
    /** The constant or half PI
        @type Number
     */
    this.halfPI = Math.PI / 2;
    /** The amount of time to spend in the air when jumping
        @type Number
     */
    this.jumpHangTime = 0.5;
    /** The speed to progress alone the sine wave that defines
        the jumping arc
        @type Number
     */
    this.jumpSinWaveSpeed = this.halfPI / this.jumpHangTime;
    /** The current position on the sine wave that defines the jump arc
        @type Number
     */
    this.jumpSinWavePos = 0;
    /** The rate to fall at
        @type Number
     */
    this.fallMultiplyer = 1.5;
    /** True when the player is on the ground, false otherwise
        @type Boolean
     */
    this.grounded = true;
    /** the players running speed
        @type Number
     */
    this.speed = 75;
    /** True if the player is moving left, false otherwise
        @type Boolean
     */
    this.left = false;
    /** True if the player is moving right, false otherwise
        @type Boolean
     */
    this.right = false;
    /** A reference to the level object
        @type Level
    */
    this.level = null;
    /** The distance between the player and the edge of the screen
        @type Number
     */
    this.screenBorder = 100;

    /**
        Initialises this object
    */
    this.startupPlayer = function(level)
    {
        this.startupAnimatedGameObject(g_ResourceManager.idleLeft, 300, 400 - 48 - 48, 4, 6, 20);
        this.level = level;
        return this;
    }

    /**
        Called when a key is pressed
        @param event Event Object
    */
    this.keyDown = function(event)
    {
        var updateRequired = false;

        // left
        if (event.keyCode == 37 && !this.left)
        {
            this.left = true;
            updateRequired = true;
        }
        // right
        if (event.keyCode == 39 && !this.right)
        {
            this.right = true;
            updateRequired = true;
        }
        if (event.keyCode == 32 && this.grounded)
        {
            this.grounded = false;
            this.jumpSinWavePos = 0;
        }

        if (updateRequired)
            this.updateAnimation();

    }

    /**
        Called when a key is pressed
        @param event Event Object
    */
    this.keyUp = function(event)
    {
        // left
        if (event.keyCode == 37)
        {
            this.left = false;
            this.setAnimation(g_ResourceManager.idleLeft, 6, 20);
        }
        // right
        if (event.keyCode == 39)
        {
            this.right = false;
            this.setAnimation(g_ResourceManager.idleRight, 6, 20);
        }

        this.updateAnimation();
    }

    /**
        Updates the current animation depending on the movement
        of the player. This accounts for the fact that both
        the left and right arrow keys can be pressed at the
        same time.
    */
    this.updateAnimation = function()
    {
       if (this.right && this.left)
            this.setAnimation(g_ResourceManager.idleLeft, 6, 20);
        else if (this.right)
            this.setAnimation(g_ResourceManager.runRight, 12, 20);
        else if (this.left)
            this.setAnimation(g_ResourceManager.runLeft, 12, 20);
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
        if (this.left)
            this.x -= this.speed * dt;
        if (this.right)
            this.x += this.speed * dt;

        // XOR operation (JavaScript does not have a native XOR operator)
        // only test for a collision if the player is moving left or right (and not trying to do both at
        // the same time)
        if ((this.right || this.left) && !(this.left && this.right))
        {
            // this will be true until the player is no longer colliding
            var collision = false;
            // the player may have to be pushed back through several block stacks (especially if the
            // frame rate is very slow)
            do
            {
                // the current position of the player (test the left side if running left
                // and the right side if running right)
                var xPos = this.left ? this.x : this.x + this.frameWidth;
                // the index of stack of blocks that the player is standing on/in
                var currentBlock = this.level.currentBlock(xPos);
                // the height of the stack of blocks that the player is standing on/in
                var groundHeight = this.level.groundHeight(currentBlock);
                // the height of the player (we need the height from the ground up,
                // whereas the this.y value represents the position of the player
                // from the "sky" down).
                var playerHeight = context.canvas.height - (this.y + this.image.height);
                // if the player is not higher than the stack of blocks, it must be colliding
                if (playerHeight  < groundHeight)
                {
                    collision = true;
                    // we are moving right, so push the player left
                    if (this.right)
                        this.x = this.level.blockWidth * currentBlock - this.frameWidth - 1;
                    // we are moving left, push the player right
                    else
                        this.x = this.level.blockWidth * (currentBlock + 1);
                }
                else
                {
                    collision = false;
                }
            }  while (collision)
        }

        // keep the player bound to the level
        if (this.x > this.level.blocks.length * this.level.blockWidth - this.frameWidth - 1)
            this.x = this.level.blocks.length * this.level.blockWidth - this.frameWidth - 1;
        if (this.x > context.canvas.width - this.frameWidth + xScroll -  this.screenBorder)
            g_GameObjectManager.xScroll = this.x - (context.canvas.width - this.frameWidth -  this.screenBorder);
        // modify the xScroll value to keep the player on the screen
        if (this.x < 0)
            this.x = 0;
        if (this.x -  this.screenBorder < xScroll)
            g_GameObjectManager.xScroll = this.x - this.screenBorder;

        // if the player is jumping or falling, move along the sine wave
        if (!this.grounded)
        {
            // the last position on the sine wave
            var lastHeight = this.jumpSinWavePos;
            // the new position on the sine wave
            this.jumpSinWavePos += this.jumpSinWaveSpeed * dt;

            // we have fallen off the bottom of the sine wave, so continue falling
            // at a predetermined speed
            if (this.jumpSinWavePos >= Math.PI)
                 this.y += this.jumpHeight / this.jumpHangTime * this.fallMultiplyer * dt;
            // otherwise move along the sine wave
            else
                this.y -= (Math.sin(this.jumpSinWavePos) - Math.sin(lastHeight)) * this.jumpHeight;
        }

        // now that the player has had it's y position changed we need to check for a collision
        // with the ground below the player. we have to check both the players left and right sides
        // for a collision with the ground

        // left side
        var currentBlock1 = this.level.currentBlock(this.x);
        // right side
        var currentBlock2 = this.level.currentBlock(this.x + this.frameWidth);
        // ground height below the left side
        var groundHeight1 = this.level.groundHeight(currentBlock1);
        // ground height below the right side
        var groundHeight2 = this.level.groundHeight(currentBlock2);
        // the heighest point under the player
        var maxGroundHeight = groundHeight1 > groundHeight2 ? groundHeight1 : groundHeight2;
        // the players height (relaitive to the bottom of the screen)
        var playerHeight = context.canvas.height - (this.y + this.image.height);

        // we have hit the ground
        if (maxGroundHeight >= playerHeight)
        {
            this.y = context.canvas.height - maxGroundHeight - this.image.height;
            this.grounded = true;
            this.jumpSinWavePos = 0;
        }
        // otherwise we are falling
        else if (this.grounded)
        {
            this.grounded = false;
            // starting falling down the sine wave (i.e. from the top)
            this.jumpSinWavePos = this.halfPI;
        }
    }
}

Player.prototype = new AnimatedGameObject;