<?php
/*
  Plugin Name: HiOrg-Server Termine
  Plugin URI: http://www.klebsattel.de
  Description: Termine Ihres HiOrg-Server in einem Widget darstellen.
  Version: 0.4
  Author: Jörg Klebsattel
  Author URI: http://www.klebsattel.de
  License: GPL
 */

// TODO: Code umschreiben, damit er die Daten per JSON abruft - dann wird das Parsen des iCal unnötig

require 'class.iCalReader.php';

add_action('plugins_loaded', 'hiorgservertermine_init');

function hiorgservertermine_init() {
    register_sidebar_widget('HiOrg-Server Termine', 'hiorg_termine');
    register_widget_control('HiOrg-Server Termine', 'hiorg_termine_control', 280, 200);
}

function hiorg_termine() {
    $titel = 'Termine';
    $account = get_option("hiorg_account");
    $anzahl = get_option("hiorg_anzahl");
    if (!is_numeric($anzahl)) {
        $anzahl = 5;
    }

    echo '<div class="sidebox">';
    echo '<h3 class="sidetitl">' . $titel . '</h3>';

    if (empty($account)) {
        echo "Bitte zuerst das Organisations-Kürzel in der Widget-Konfiguration eingeben";
    } else {

        $ical = new ICal('https://www.hiorg-server.de/termine.php?ov=' . $account . '&anz=' . $anzahl . '&ical=1');
        $events = $ical->events();
        $date = $events[0]['DTSTART'];

        foreach ($events as $event) {
            $date = $ical->iCalDateToUnixTimestamp($event['DTSTART']) + get_offset_to_gmt_in_seconds();
            $date_ende = $ical->iCalDateToUnixTimestamp($event['DTEND']) + get_offset_to_gmt_in_seconds();
            $hiorg_date = date("d.m.Y", $date);
            $hiorg_starttime = date("H:i", $date);
            $hiorg_endetime = date("H:i", $date_ende);
            echo '<div class="textwidget">';
            echo '<p>';
            echo '<small>' . $hiorg_date . ' | ' . $hiorg_starttime . '-' . $hiorg_endetime . '</small><br/>';
            echo '<b>' . stripslashes($event['SUMMARY']) . '</b><br/>';
            echo '<small>' . $event['LOCATION'] . '</small><br/>';
            echo '</p>';
            echo '</div>';
        }
    }
    echo '</div>';
}

function hiorg_termine_control() {
    $account = get_option("hiorg_account");
    $anzahl = get_option("hiorg_anzahl");
    if ($_POST['hiorg-submit']) {
        $account = htmlspecialchars($_POST['hiorg-account']);
        $anzahl = htmlspecialchars($_POST['anzahl']);
        update_option("hiorg_account", $account);
        update_option("hiorg_anzahl", $anzahl);
    }
    ?>
    <p>
        <label>Organisations-Kürzel:</label>
        <input type="text" id="hiorg-account" name="hiorg-account" value="<?= $account ?>" style="width:250px" />
        <label>Anzahl der Termine:</label>
        <input type="text" id="anzahl" name="anzahl" value="<?= $anzahl ?>" style="width:250px" />
        <input type="hidden" id="hiorg-submit" name="hiorg-submit" value="1" />
    </p>
    <?php
}

function get_offset_to_gmt_in_seconds() {

    $current_timezone_offset = get_option('gmt_offset');
    $offset = $current_timezone_offset * 3600;

    return $offset;
}
