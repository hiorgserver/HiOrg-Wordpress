<?php
/*
  Plugin Name: HiOrg-Server Termine
  Plugin URI: http://www.klebsattel.de
  Description: Termine Ihres HiOrg-Server in einem Widget darstellen.
  Version: 1.2.2
  Author: Jörg Klebsattel
  Author URI: http://www.klebsattel.de
  License: GPL
 */

add_action('plugins_loaded', 'hiorgservertermine_init');

function hiorgservertermine_init() {
    register_sidebar_widget('HiOrg-Server Termine', 'hiorg_termine');
    register_widget_control('HiOrg-Server Termine', 'hiorg_termine_control', 280, 260);
}

function hiorg_termine() {
    $titel = 'Termine';
    $account = get_option("hiorg_account");
    $anzahl = get_option("hiorg_anzahl");
    $monate = get_option("hiorg_monate");
    $link = get_option("hiorg_link");
    date_default_timezone_set('Europe/Berlin');
    echo '<div class="sidebox">';
    echo '<h3 class="sidetitl">' . $titel . '</h3>';
	
    if (empty($account)) {
        echo "Bitte zuerst das Organisations-K&uuml;rzel in der Widget-Konfiguration eingeben";
		echo '</div>';
    } else {

        $url = 'https://www.hiorg-server.de/termine.php?json=1&ov=' . $account;
		
        if (is_numeric($anzahl)) {
            $url .= "&anzahl=" . $anzahl;
        }
        if (is_numeric($monate)) {
            $url .= "&monate=" . $monate;
        }

        $events = file_get_contents($url);
	    $events_objekt = json_decode($events);
		
        if ($events_objekt->{'success'} == 1) {
        
			$events = repairJSON($events);
            $events = str_replace("},", "};", $events);
            $events = explode(";", $events);
            $counter = 0;
			
			foreach ($events as $event) {
				$event= json_decode($events[$counter]);
				$hiorg_date = date('d.m.Y', $event->{'sortdate'});
				$hiorg_starttime = date("H:i", $event->{'sortdate'});
				$hiorg_endetime = date('H:i', $event->{'enddate'});
				$counter = $counter + 1;
				
				if ($hiorg_date != "01.01.1970") {
					echo '<div class="hiorgtermine">';
					echo '<p>';
					echo '<small>' . $hiorg_date . ' | ' . $hiorg_starttime;
					
					if ($hiorg_endetime !=0) {
						echo '-' . $hiorg_endetime . ' </small><br/>';
					} else {
						echo ' </small><br/>';
					}
					
					echo '<b>' . stripslashes($event->{'verbez'}) . '</b><br/>';
					if (strlen ($event->{'verort'}) != 0) {
						echo '<small>' . repairZeilenumbruch($event->{'verort'}) . '</small><br/>';
					}
					echo '</p>';
					echo '</div>';
					
				} else {
					echo '<div class="textwidget"><p>Keine Termine</p></div>';
				} /**Keine Termine*/
			}
        } else { /*Verbindung fehlgesschlagen*/
            echo '<div class="textwidget"><p>Keine Termine</p></div>';
        }
		echo '</div>'; /*Ende Terminwidget*/
		
		/*Linkwidget*/
		if ( $link == 'linkZeigen' ) {
			echo '<div class="sidebox">';
			echo '<h3 class="sidetitl">HiOrg-Server</h3>';
			echo '<p><a href="https://www.hiorg-server.de/index.php?ov='.$account.'" target="_blank" ><img src="/wp-content/plugins/hiorgserver-terminliste/images/hiorgserver.jpg" style="padding: 5px 0px;"/></a></p>';
			echo '</div>';
				
		}
    }
}

function hiorg_termine_control() {
    if ($_POST['hiorg-submit']) {
        $account = trim($_POST['hiorg-account']);
        $anzahl = trim($_POST['hiorg-anzahl']);
        $monate = trim($_POST['hiorg-monate']);
        $link = trim($_POST['hiorg_link']);
        update_option("hiorg_account", $account);
        update_option("hiorg_anzahl", $anzahl);
        update_option("hiorg_monate", $monate);
        update_option("hiorg_link", $link);
    }
	
    ?>
    	
	<p>
        <p><label for="hiorg-account"><b>Organisations-K&uuml;rzel:</b></label>
        <input type="text" id="hiorg-account" name="hiorg-account" value="<?= $account ?>" style="width:250px" /></p>
     
        <b>Weitere Parameter:</b> <small>(optional)</small><br />
        <p><label for="hiorg-anzahl">Anzahl der Termine:</label>
        <input type="text" id="hiorg-anzahl" name="hiorg-anzahl" value="<?= $anzahl ?>" style="width:50px" /></p>
        <p><label for="hiorg-monate">Zeitraum:</label>
        <input type="text" id="hiorg-monate" name="hiorg-monate" value="<?= $monate ?>" style="width:50px" /> Monate</p>
		<p><label><input type="checkbox" name="hiorg_link" value="linkZeigen">Link für Helfer auf Startseite anzeigen.</label></p>
        <input type="hidden" id="hiorg-submit" name="hiorg-submit" value="1" <?php checked($options['postlink'], 1); ?> />
    </p>
    <?php
}
function repairZeilenumbruch($str2repair){
//$str2repair = str_replace('\n', PHP_EOL, $str2repair); => würde keinen neuen Zeilenumbruch machen, aber dafür \n entfernen
	$str2repair = str_replace("\\n", "<br/>", $str2repair);
	return stripslashes($str2repair);
	}
	
function repairTime($datum) {
	/*	0= keine Sommerzeit (gmt+1) 
		1= Sommerzeit (gmt+2)
	*/
	if (date('I', strtotime($datum)) == 0) {
		$zeitzone = 1; //Winterzeit
	} else {
		$zeitzone = 2; //Sommerzeit
	}
	return $zeitzone; 
	}
	
function repairJSON($str2cut){
		$str2cut = substr ( $str2cut, 63, -2 );
		return $str2cut;
	}