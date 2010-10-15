<?php // $Id: permissions.class.php,v 1.1 2006/02/01 23:37:43 janne Exp $
/////////////////////////////////////////////////////////////////////////////////////
//                                                                                 //
// Copyright (C) 2004  Janne Mikkonen                                              //
//                                                                                 //
// This program is free software; you can redistribute it and/or                   //
// modify it under the terms of the GNU General Public License                     //
// as published by the Free Software Foundation; either version 2                  //
// of the License, or any later version.                                           //
//                                                                                 //
// This program is distributed in the hope that it will be useful,                 //
// but WITHOUT ANY WARRANTY; without even the implied warranty of                  //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                   //
// GNU General Public License for more details.                                    //
//                                                                                 //
// You should have received a copy of the GNU General Public License               //
// along with this program; if not, write to the Free Software                     //
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.     //
//                                                                                 //
// You can find more information about GPL licence at:                             //
// http://www.gnu.org/licenses/gpl.html                                            //
//                                                                                 //
/////////////////////////////////////////////////////////////////////////////////////

    define('PRM_READ',   0x01);
    define('PRM_WRITE',  0x02);
    define('PRM_REMOVE', 0x04);
    define('PRM_ADMIN',  0x08);

    /**
    * This class sets and gets permissions. The class it self uses
    * bitmask representation. The value that can be stored into database
    * is binary string. The class uses these constants
    * PRM_READ
    * PRM_WRITE
    * PRM_REMOVE
    * PRM_ADMIN
    * These constants must be defined first.
    *
    * @author Janne Mikkonen
    * @version 0.9
    * @since 29.12.2004
    */
    class Permissions {

        /**
        * This is an array that hold same values that constants have.
        * Only purpose of this array is to control that user input
        * isn't out of range.
        *
        * @type Associtive array
        */
        var $rights = array('PRM_READ'   => 0x01,
                            'PRM_WRITE'  => 0x02,
                            'PRM_REMOVE' => 0x04,
                            'PRM_ADMIN'  => 0x08);

        /**
        * Constructor for PHP version 4.
        *
        * @param none
        * @return
        */
        function Permissions () {
            $this->rights;
        }

        /**
        * Constructor for PHP version 5.
        *
        * @param none
        * @return
        */
        function __construct () {
            $this->rights;
        }

        /**
        * This function check the given value and returns
        * its value as bitmask in a string presentation
        *
        * @param $value
        * @return string
        */
        function _check_right ($value) {
            return strlen(decbin($value));
        }
        /**
        * This function assigns a requested value.
        * Values must be in the $rights array
        *
        * @param  $value
        * @return binary
        */
        function assign_right ($value) {

            // Don't accept any other value that isn't
            // in the $righs array.
            if (in_array($value, $this->rights, TRUE)) {
                return decbin(strlen(decbin($value)));
            } else {
                $this->_error();
            }
        }

        /**
        * This function returns bitmask of given value.
        *
        * @param integer $value
        * @return integer
        */
        function _get_right ($value) {

            for ($i = 0; $i < $value; $i++) {
                $mask = pow (2, $i);
            }

            if (empty($mask)) {
                $mask = 0;
            }

            switch($mask) {
                case PRM_READ:
                    return $i;
                    break;
                case PRM_WRITE:
                    return $i;
                    break;
                case PRM_REMOVE:
                    return $i;
                    break;
                case PRM_ADMIN:
                    return $i;
                    break;
            }
            // In case we'll somehow get here!
            return false;
        }

        /**
        *
        *
        */
        function can_read ($value) {
            $value = bindec($value);
            if ($this->_get_right($value) >= $this->_check_right(PRM_READ)) {
                return true;
            }

            return false;

        }

        function can_write ($value) {
            $value = bindec($value);
            if ($this->_get_right($value) >= $this->_check_right(PRM_WRITE)) {
                return true;
            }

            return false;

        }

        function can_remove ($value) {
            $value = bindec($value);
            if ($this->_get_right($value) >= $this->_check_right(PRM_REMOVE)) {
                return true;
            }

            return false;

        }

        function can_admin ($value) {
            $value = bindec($value);
            if ($this->_get_right($value) >= $this->_check_right(PRM_ADMIN)) {
                return true;
            }

            return false;

        }

        function _error () {

            echo "Only defined values are allowed!";
            exit;

        }
    }
?>