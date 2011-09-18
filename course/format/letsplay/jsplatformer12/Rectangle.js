/**
    A rectangle
    @author <a href="mailto:matthewcasperson@gmail.com">Matthew Casperson</a>
    @class
*/
function Rectangle()
{
    this.left = 0;
    this.top = 0;
    this.width = 0;
    this.height = 0;

    /**
        Initialises the object
        @param left     Left position
        @param top      Top Position
        @param width    Width of rectangle
        @param height   Height of triangle
     */
    this.startupRectangle = function(/**Number*/ left, /**Number*/ top, /**Number*/ width, /**Number*/ height)
    {
        this.left = left;
        this.top = top;
        this.width = width;
        this.height = height;
        return this;
    }

    /**
        @return         true if there is an intersection, false otherwise
        @param other    The other rectangle to test against
     */
    this.intersects = function(/**Rectangle*/ other)
    {
        if (this.left + this.width < other.left)
            return false;
        if (this.top + this.height < other.top)
            return false;
        if (this.left > other.left + other.width)
            return false;
        if (this.top > other.top + other.height)
            return false;

        return true;
    }
}