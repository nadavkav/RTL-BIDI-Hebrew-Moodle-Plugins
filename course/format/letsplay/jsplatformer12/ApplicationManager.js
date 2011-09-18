/**
    The ApplicationManager is used to manage the application itself.
    @author <a href="mailto:matthewcasperson@gmail.com">Matthew Casperson</a>
    @class
*/
function ApplicationManager()
{
    this.canvasWidth = 0;
    this.canvasHeight = 0;

    /**
        Initialises this object
        @param canvasWidth      The width of the canvas
        @param canvasHeight     The height of the canvas
        @return                 A reference to the initialised object

    */
    this.startupApplicationManager = function(canvasWidth, canvasHeight)
    {
        g_ApplicationManager = this;
        this.canvasWidth = canvasWidth;
        this.canvasHeight = canvasHeight;

        this.openMainMenu();

        return this;
    }

    this.startLevel = function()
    {
        g_GameObjectManager.shutdownAll();
        this.level = new Level().startupLevel(this.canvasWidth, this.canvasHeight);
        this.background3 = new RepeatingGameObject().startupRepeatingGameObject(g_ResourceManager.background2, 0, 100, 3, 600, 320, 0.75);
        this.background2 = new RepeatingGameObject().startupRepeatingGameObject(g_ResourceManager.background1, 0, 100, 2, 600, 320, 0.5);
        this.background = new RepeatingGameObject().startupRepeatingGameObject(g_ResourceManager.background0, 0, 0, 1, 600, 320, 0.25);
        g_player = new Player().startupPlayer(this.level);
        this.updateScore();
    }

    this.openMainMenu = function()
    {
        g_GameObjectManager.shutdownAll();
        g_GameObjectManager.xScroll = 0;
        g_GameObjectManager.yScroll = 0;
        g_score = 0;
        this.mainMenu = new MainMenu().startupMainMenu();
    }

    this.updateScore = function()
    {
        var score = document.getElementById("Score");
        score.innerHTML = String(g_score);
    }
}