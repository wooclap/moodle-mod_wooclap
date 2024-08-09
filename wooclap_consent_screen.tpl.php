<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();
?>

<!DOCTYPE html>
<html class="consent-screen">
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="./css/consent-screen.css" rel="stylesheet" />
  </head>

  <body class="wrapper">
    <div class="modal">
      <img class="logo" src="./images/logo.jpg" />
      <p class="text">
        <?php echo get_string('consent-screen:description', 'wooclap'); ?>
      </p>
      <p class="text">
        <?php echo get_string('consent-screen:explanation', 'wooclap'); ?>
      </p>
      <div class="buttons-wrapper">
        <a class="button plain" href="<?php echo $noconsenturl ?>"
          ><?php echo get_string('consent-screen:disagree', 'wooclap'); ?></a
        >
        <a class="button" href="<?php echo $yesconsenturl ?>"
          ><?php echo get_string('consent-screen:agree', 'wooclap'); ?></a
        >
      </div>
    </div>
  </body>
</html>
