$Id: README_de.txt,v 1.2 2008/03/21 19:10:51 dlnsk Exp $

INFORMATION
    Filter "HiddenText" entwickelt für Moodle.
    Version 1.0
    Getestet unter Moodle 1.8.x
    Entwickelt von Dmitry Pupinin (dlnsk[at]ngs[dot]ru)
    Der Filter wird unter den Bestimmungen der General Public License veröffentlicht
        (siehe http://www.gnu.org/licenses/gpl.txt für weitere Details)


Wofür diesen Filter?
    - Der Filter "HiddenText" ermöglicht es Lehrenden sog. "hints" (Hints, vielleicht bekannt von HotPotatoes, ein Text wird durch einen Klick erst sichtbar) für Studierende zu hinterlegen um anzuzeigen, wo sich ein versteckter Text (eine zusätzliche Information) befindet. Der versteckte Text (hints) kann beliebig oft von den Studierenden aufgerufen werden.

Die Installation:
    - Kopien Sie den Ordner "hiddentext" in das Verzeichnis "filter" im Moodleverzeichnis.
    - Kopien Sie die Sprachdatei in den Ordner "lang".
    - Aktivieren Sie den Filter über "Administration/Filter".
  
Die Benutzung:
    - Erstellen Sie einen Inhalt.
    - Schließen Sie den Teil des Textes ein, der versteckt werden soll:
        <span filter="hiddentext">versteckter_text_hier</span>
        oder
        <div filter="hiddentext">versteckter_text_mehrere_Zeilen_hier</div>
    - Testen und überprüfen Sie das Ergebnis.

Wie funktioniert der Filter:
    - Nach dem abspeichern oder aktualisieren der Seite ist der Text zwischen den Tags >< unsichtbar. Anstelle des Textes wird ein grafisches Symbol (Fragezeichen oder Icon) dargestellt.
    - Klicken Studierende auf das Symbol, wird der versteckte Text angezeigt.

Zusätzliche Informationen zum Filter:
    - Nutzen Sie "span" um einen Begriff, eine Zeile zwischen den Tags (Klammern) >< unsichtbar zu machen und "div" um einen größeren Textabschnitt unsichtbar zu machen.
    - Sie können zwei optionale Parameter nutzen: "class" and "desc" (Beschreibung):
        "class" lässt Sie den Style des versteckten Textes verändern
        "desc" lässt Sie die Beschreibung hinter dem Symbol verändern. 
        Wird nichts angegeben, nutzt der Filter die Beschreibung ("desc") aus der Sprachdatei (gilt nur für den "div" tag)
    - Verfügbare Styles: hinline, htext, hcode und Styles die Sie in der css-Datei selbst definiert haben 
    - Sie mögen keine eingebetteten Styels? Senden Sie mir Ihre Vorschläge oder fertige css-Dateien :-)

Beispiel No1:
    - Der nachstehende Text:
        Dieser Text ist <span filter="hiddentext">sehr, sehr, sehr</span> lang.

    - Dies wird angezeigt als:
        Dieser Text ist (?) lang. Anstatt des (?) Symbols ggf. Ihr eigenes Icon

Beispiel No2:
    - Dieser Text:
        Der amerikanische Feiertag Thanksgiving wird gefeiert 
        am <span filter="hiddentext" desc="Wann?">vierten</span> Donnerstag im November.

    - Dies wird angezeigt als:
        Der amerikanische Feiertag Thanksgiving wird gefeiert 
        am (?)Wann? Donnerstag im November.

Beispiel No3:
    - Dieser Text:
        Aufgeführte Hauptstädte der folgenden Länder: Kanada, Italien, Japan
        <div filter="hiddentext" class="htext" desc="Antwort hier">Kanada - Ottawa
        Italien - Rom
        Japan - Tokio</div>

    - Wird angezeigt als:
        Aufgeführte Hauptstädte der folgenden Länder: Kanada, Italien, Japan
        (?)Antwort hier
    
    - Nach dem Klick auf das Symbol:
        Aufgeführte Hauptstädte der folgenden Länder: Kanada, Italien, Japan
        (?) Antwort hier
           -------------------
           | Kanada - Ottawa | 
           | Italien - Rom    | 
           | Japan - Tokio   |
           ------------------- 

Danke das Sie den Filter nutzen!
