/**
    The main menu screen
    @author <a href="mailto:matthewcasperson@gmail.com">Matthew Casperson</a>
    @class
*/
function MainMenu()
{
    this.startupMainMenu = function()
    {
        this.startupVisualGameObject(g_ResourceManager.mainmenu, 0, 0, 1);
        return this;
    }

    /**
        Called when a key is pressed
        @param event Event Object
    */
    this.keyDown = function(event)
    {
        g_ApplicationManager.startLevel();
    }
}
MainMenu.prototype = new VisualGameObject;