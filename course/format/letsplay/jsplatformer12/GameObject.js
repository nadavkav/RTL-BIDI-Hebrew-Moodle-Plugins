/**
    The base class for all elements that appear in the game.
    @author <a href="mailto:matthewcasperson@gmail.com">Matthew Casperson</a>
    @class
*/
function GameObject()
{
    /** Display depth order. A smaller zOrder means the element is rendered first, and therefor
        in the background.
        @type Number
    */
    this.zOrder = 0;
    /**
        The position on the X axis
        @type Number
    */
    this.x = 0;
    /**
        The position on the Y axis
        @type Number
    */
    this.y = 0;
    
    /**
        Initialises the object, and adds it to the list of objects held by the GameObjectManager.
        @param x        The position on the X axis
        @param y        The position on the Y axis
        @param z        The z order of the element (elements in the background have a lower z value)
    */
    this.startupGameObject = function(/**Number*/ x, /**Number*/ y, /**Number*/ z)
    {
        this.zOrder = z;
        this.x = x;
        this.y = y;
        g_GameObjectManager.addGameObject(this);
        return this;
    }
    
    /**
        Cleans up the object, and removes it from the list of objects held by the GameObjectManager.
    */
    this.shutdownGameObject = function()
    {
        g_GameObjectManager.removeGameObject(this);
    }

    this.shutdown = function()
    {
         this.shutdownGameObject();
    }
}