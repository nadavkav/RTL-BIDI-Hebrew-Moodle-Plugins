/** target frames per second
    @type Number
*/
var FPS = 30;
/** time between frames
    @type Number
*/
var SECONDS_BETWEEN_FRAMES = 1 / FPS;
/** A global reference to the GameObjectManager instance
    @type GameObjectManager
*/
var g_GameObjectManager = null;
/** A global reference to the ApplicationManager instance
    @type ApplicationManager
*/
var g_ApplicationManager = null;
/** A global reference to the ResourceManager instance
    @type ResourceManager
*/
var g_ResourceManager = null;
/** The players score
    @type Number
 */
var g_score = 0;
/** A reference to the player
    @type Player
 */
var g_player = null;
/** An image to be used by the application
    @type Image
*/

// The entry point of the application is set to the init function
window.onload = init_letsplay;

/**
    Application entry point
*/
function init_letsplay()
{
    new GameObjectManager().startupGameObjectManager();
}