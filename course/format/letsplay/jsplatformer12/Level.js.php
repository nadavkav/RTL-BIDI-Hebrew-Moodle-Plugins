/**
    A class to represent the level
    @author <a href="mailto:matthewcasperson@gmail.com">Matthew Casperson</a>
    @class
*/
function Level()
{
    this.blocks = new Array();
    this.powerups = new Object;
    this.coursemodules = new Object;
    this.blockWidth = 64;
    this.blockHeight = 48;

    /**
        Initialises this object
    */
    this.startupLevel = function(canvasWidth, canvasHeight)
    {
        <?php

            $road = 77;
            for ($i = 1; $i <= $road ; $i++) {

                echo "this.blocks[$i] = ".rand(1,3).";";
            }

//        this.blocks[0] = 3;
//        this.blocks[1] = 2;
//        this.blocks[2] = 1;
//        this.blocks[3] = 1;
//        this.blocks[4] = 1;
//        this.blocks[5] = 1;
//        this.blocks[6] = 2;
//        this.blocks[7] = 3;
//        this.blocks[8] = 2;
//        this.blocks[9] = 1;
//        this.blocks[10] = 2;
//        this.blocks[11] = 3;
//        this.blocks[12] = 4;
//        this.blocks[13] = 5;
//        this.blocks[14] = 4;
//        this.blocks[15] = 3;

        ?>

        this.powerups = powerups;

//        this.powerups['1'] = 'Gem';
//        this.powerups['6'] = 'Gem';
//        this.powerups['10'] = 'Gem';
//        this.powerups['14'] = 'LevelEndPost';

        this.coursemodules = coursemodules;
        //this.coursemodules['1'] = coursemodules['1']; //'http://localhost/moodle-latest-stable/mod/data/view.php?id=578';
        //this.coursemodules['6'] = coursemodules['6']; //'http://localhost/moodle-latest-stable/mod/oublog/view.php?id=165';
        //this.coursemodules['10'] = coursemodules['10']; //'http://localhost/moodle-latest-stable/mod/jclic/view.php?id=166';

        this.addBlocks(canvasWidth, canvasHeight);
        this.addPowerups(canvasWidth, canvasHeight);

        return this;
    }

    /**
        Adds the blocks to the screen by creating VisualGameObjects
    */
    this.addBlocks = function(canvasWidth, canvasHeight)
    {
        for (var x = 0; x < this.blocks.length; ++x)
        {
            for (var y = 0; y < this.blocks[x]; ++y)
            {
                new VisualGameObject().startupVisualGameObject(g_ResourceManager.block, x * this.blockWidth, canvasHeight - (y + 1) * this.blockHeight, 4);
            }
        }
    }

    this.addPowerups = function(canvasWidth, canvasHeight)
    {
        for (var x = 0; x < this.blocks.length; ++x)
        {
            if (this.powerups[x])
            {
                var xPosition = x * this.blockWidth + this.blockWidth / 2;
                var yPosition = canvasHeight - this.groundHeight(x) - this.blockHeight;

                switch(this.powerups[x])
                 {
                    case 'Gem':
                        new Powerup().startupPowerup(this.coursemodules[x],10, g_ResourceManager.gem, xPosition - g_ResourceManager.gem.width / 2, yPosition - g_ResourceManager.gem.height, 4, 1, 1);
                        break;
                    case 'LevelEndPost':
                        new LevelEndPost().startupLevelEndPost(g_ResourceManager.portal, xPosition - g_ResourceManager.portal.width / 2 / 4, yPosition - g_ResourceManager.portal.height, 4);
                        break;
                 }
            }
        }
    }

    /**
        @return     The block under the specified x position
        @param x    The x position to test
    */
    this.currentBlock = function(x)
    {
        return parseInt( x / this.blockWidth);
    }

    /**
        @return             The hieght of the ground under the specified block
        @param blockIndex   The block number
    */
    this.groundHeight = function(blockIndex)
    {
        if (blockIndex < 0 || blockIndex > this.blocks.length) return 0;

        return this.blocks[blockIndex] *  this.blockHeight;
    }
}