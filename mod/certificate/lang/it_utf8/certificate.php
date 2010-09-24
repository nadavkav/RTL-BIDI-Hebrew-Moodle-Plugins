<?PHP // $Id: admin.php,v 1.18.2.7 2006/08/07 14:48:51 skodak Exp $ 
      // admin.php - created with Moodle 1.6 development (2005053000)


$string['modulename'] = 'Attestato';
$string['modulenameplural'] = 'Attestati';

$string['certificatetype'] = 'Tipo di Attestato';
$string['emailteachers'] = 'Email al docente';
$string['savecertificate'] = 'Salva Attestati';
$string['deliver'] = 'Consegna';
$string['download'] = 'Forza download';
$string['openbrowser'] = 'Apri in nuova finestra';
$string['emailcertificate'] = 'Email (Bisogna anche scegliere di salvarli!)';
$string['emailstudenttext'] = 'In allegato c\'è il tuo attestato per $a->course.';
$string['awarded'] = 'Concesso';
$string['emailteachermail'] = '
Lo studente $a->student ha ricevuto il suo attestato: \'$a->certificate\'
per $a->course.

Puoi verificarlo qui:

    $a->url';
$string['emailteachermailhtml'] = '
Lo studente $a->student ha ricevuto il suo attestato: \'<i>$a->certificate</i>\'
per $a->course.

Puoi verificarlo qui:

    <a href=\"$a->url\">Rapporto Attestati</a>.';
$string['border'] = 'Bordo';
$string['borderstyle'] = 'Stile del Bordo';
$string['bordernone'] = 'Senza bordo';
$string['borderlines'] = 'Linee';
$string['bordercolor'] = 'Colore del Bordo';
$string['borderblack'] = 'Nero';
$string['borderbrown'] = 'Marron';
$string['borderblue'] = 'Blu';
$string['bordergreen'] = 'Verde';
$string['printwmark'] = 'Stampa Filigrana';

$string['datehelp'] = 'Data';
$string['dateformat'] = 'Formato Data';
$string['receiveddate'] = "Data Ricezione";
$string['courseenddate'] = 'Data Fine Corso (Deve esistere!)';

$string['printcode'] = 'Stampa il Codice';

$string['printgrade'] = 'Stampa il Voto';
$string['grade'] = 'Voto';
$string['coursegrade'] = 'Voto del Corso';
$string['nogrades'] = 'Voti non Disponibili';
$string['gradeformat'] = 'Formato del Voto';
$string['gradepercent'] = 'Percentuale';
$string['gradepoints'] = 'Punteggio';
$string['gradeletter'] = 'Letterale';

$string['printsignature'] = 'Stampa Firma';
$string['sigline'] = 'linea';

$string['printteacher'] = 'Stampa Docente';
$string['printdate'] = 'Stampa Data';
$string['printseal'] = 'Stampa Timbro';

$string['code'] = 'Codice';
$string['issued'] = 'Emesso';
$string['notissued'] = 'Non Emesso';
$string['notissuedyet'] = 'Non ancora emesso';
$string['notreceived'] = 'Non hai ricevuto questo attestato';
$string['getcertificate'] = 'Ottieni il tuo attestato';
$string['report'] = 'Rapporto';
$string['viewed'] = 'Hai ricevuto il tuo attestato il:';
$string['viewcertificateviews'] = 'Mostra gli attestati assegnati a: $a->';
$string['reviewcertificate'] = 'Visualizza il tuo attestato';
$string['openwindow'] = 'Clicca il bottone qui sotto per aprire il tuo attestato
in una nuova finestra.';
$string['download'] = 'Download';
$string['opendownload'] = 'Clicca il bottone qui sotto per salvare l\'attestato
sul tuo computer.';
$string['openemail'] = 'Clicca il bottone qui sotto e l\'attestato
ti sarà inviato come allegato ad un\'  email.';
$string['receivedcerts'] = 'Attestati Ricevuti';
$string['certificate:view'] = 'Visualizza l\' attestato';
$string['certificate:manage'] = 'Gestisci l\' attestato';
$string['certificate:teacher'] = 'Stampa Docente';
$string['certificate:student'] = 'Ottieni l\' Attestato';
$string['gsettings'] = 'IMPOSTAZIONI';
$string['gradesettings'] = 'IMPOSTAZIONI VOTO';
$string['format'] = 'FORMATO';
$string['unenrol'] = 'Cancella iscrizione';

//names of type folders
$string['typeportrait'] = 'Verticale (A4)';
$string['typeletter_portrait'] = 'Verticale (letter)';
$string['typelandscape'] = 'Orizzontale (A4)';
$string['typeletter_landscape'] = 'Orizzontale (letter)';
$string['typeunicode_landscape'] = 'Unicode (Orizzontale)';

//strings for verification 
$string['configcontent'] = 'Configura contenuto';
$string['validate'] = 'Verifica';
$string['certificate'] = 'Verifica del codice attestato:';
$string['verifycertificate'] = 'Verifica dell\' Attestato';
$string['dontallowall'] = 'Non Consentire';
$string['cert'] = '#';
$string['notfound'] = 'Il numero dell\' attestato non può essere convalidato.';
$string['back'] = 'Indietro';
$string['to'] = 'Concesso a';
$string['course'] = 'Per';
$string['date'] = 'Il';
$string['alert1'] = 'Questa attivit&agrave; genera un\' attestato. Dopo la prima emissione gli attestati non possono essere sostituiti in nessun caso,dati come il voto, nome e data rimarranno sempre gli stessi dopo l\'emissione di questo attestato. Se desideri modificare i tuoi dati o completare qualche attivit&agrave;, fai queste modifiche prima di continuare.';
$string['attention'] = 'ATTENZIONE';
$string['alert2'] = 'L\' emissione di questo attestato cancella la tua iscrizione da questo corso, se desideri compiere ancora qualche attivit&agrave;, fallo prima di generare questo attestato.';
$string['backbutton'] = 'Torna Indietro';

//strings for certificates
$string['titlelandscape'] = 'ATTESTATO';
$string['introlandscape'] = 'Si certifica che';
$string['statementlandscape'] = 'ha completato il corso';

$string['titleletterlandscape'] = 'ATTESTATO';
$string['introletterlandscape'] = 'Si certifica che';
$string['statementletterlandscape'] = 'ha completato il corso';

$string['titleportrait'] = 'ATTESTATO';
$string['introportrait'] = 'Si certifica che';
$string['statementportrait'] = 'ha completato il corso';
$string['ondayportrait'] = 'in questo giorno';

$string['titleletterportrait'] = 'ATTESTATO';
$string['introletterportrait'] = 'Si certifica che';
$string['statementletterportrait'] = 'ha completato il corso';

//Conditional certificate
$string['conditional'] = "Condizionato dal voto";
$string['notpossible'] = 'Il tuo voto in questo corso non è sufficiente per ottenere questo attestato. </br> Per ottenere questo attestato devi: ';
$string['condeactivationd'] = 'Disabilita';
$string['condeactivationa'] = 'Abilita';
$string['referencegrade'] = 'Voto da usare';
$string['gradeformat'] = 'Formato del voto';
$string['on'] = 'a';

//Credit hours
$string['printcredithours'] = 'Stampa ore di credito';
$string['credithours'] = 'Ore di credito';
$string['hours'] = 'ore';
?>
