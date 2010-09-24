<?PHP // $Id: game.php,v 1.5 2008/07/23 11:26:48 bdaloukas Exp $ 
      // game.php - created with Moodle 1.9.1+ (Build: 20080702) (2007101514)
      // thanks to Wilmack Sanchez, Antonio Vicent

//students
$string[ 'cross_across'] = 'Horizontal';
$string[ 'cross_checkbutton'] = 'Revisar crucigrama';
$string[ 'cross_congratulations'] = 'Felicitaciones';
$string[ 'cross_corrects'] = 'correctas';
$string[ 'cross_correct'] = 'correcta';
$string[ 'cross_down'] = 'Abajo';
$string[ 'cross_error_containsbadchars'] = 'La palabra contiene caracteres no autorizados';
$string[ 'cross_errors'] = 'errores';
$string[ 'cross_error_wordlength1'] = 'La palabra correcta contiene ';
$string[ 'cross_error_wordlength2'] = ' letras.';
$string[ 'cross_error'] = 'error';
$string[ 'cross_found_many'] = 'Encontró';
$string[ 'cross_found_one'] = 'Encontró';
$string[ 'cross_incomplete_words'] = ' palabras no completadas';
$string[ 'cross_incomplete_word'] = ' palabra no completada';
$string[ 'cross_maxcols'] = 'Número máximo de columnas del crucigrama';
$string[ 'cross_maxwords'] = 'Máximo número de palabras del crucigrama';
$string[ 'cross_new'] = 'Nuevo juego';
$string[ 'cross_noerrors_but1'] = 'No se encontraron errores pero ';
$string[ 'cross_noerrors_but2_many'] = ' palabras no completadas';
$string[ 'cross_noerrors_but2_one'] = '  palabra no completada';
$string[ 'cross_nowords'] = 'No encontró ninguna palabra';
$string['cross_pleasewait'] = 'Por favor espere mientras se carga el crucigrama';
$string[ 'cross_spaces'] = 'espacios';
$string[ 'cross_space'] = 'espacio';
$string['cross_welcome'] = '<h3>¡Bienvenido!</h3><p>Haga clic en una palabra para comenzar.</p>';
$string['cross_win'] = '¡¡¡ Felicitaciones !!!';
$string[ 'cross_words'] = 'palabras';
$string[ 'cross_word'] = 'palabra';
$string[ 'hangman_correct_phrase'] = 'La frase correcta fué: ';
$string[ 'hangman_correct_word'] = 'La palabra correcta fué: ';
$string[ 'hangman_allowspaces'] = 'Permitir espacios en las palabras';
$string[ 'hangman_allowsub'] = 'Permitir símbolos en las palabras';
$string[ 'game_cross'] = 'Crucigrama';
$string[ 'game_cryptex'] = 'Sopa de Letras';
$string[ 'game'] = 'Juego';
$string[ 'game_hangman'] = 'Ahorcado';
$string[ 'game_millionaire'] = 'Millonario';
$string[ 'game_sudoku'] = 'Sudoku';
$string[ 'grade'] = 'Nivel';
$string[ 'game_bookquiz'] = 'Libro con preguntas';
$string[ 'cryptex_win'] = '¡¡¡ Felicitaciones !!!!';
$string[ 'cryptex_giveanswer'] = 'De la respuesta';
$string[ 'convertfrom1'] = '';
$string[ 'convertto1'] = '';
$string[ 'hangman_gradeinstance'] = 'Nivel en el juego completo';
$string[ 'hangman_grade'] = 'Nivel';
$string[ 'hangman_letters'] = 'Letras: ';
$string[ 'hangman_loose'] = '<BIG><B>Fin del juego</B></BIG>';
$string[ 'hangman_restletters_many'] = 'Ud. tiene <b>$a</b> intentos';
$string[ 'hangman_restletters_one'] = 'Ud. tienen <b>ÚNICAMENTE 1</b> intento';
$string[ 'hangman_restletters'] = 'Ud. tiene <b>$a</b> intentos';
$string[ 'lettersall1'] = "ABCDEFGHIJKLMNÑOPQRSTUVWXYZ ";
$string[ 'lettersall2'] = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ ';
$string[ 'letters'] = 'letras';
$string[ 'letter'] = 'letra';
$string[ 'millionaire_info_people'] = 'La gente dice';
$string[ 'millionaire_info_telephone'] = 'Yo pienso que la respuesta correcta es';
$string[ 'millionaire_info_wrong_answer'] = 'Su respuesta es incorrecta<br>La respuesta correcta es:';
$string[ 'millionaire_letters_answers'] = 'ABCD';
$string[ 'hangman_wrongnum'] = 'Malas: %%d de %%d';
$string[ 'millionaire_no_questions'] = 'No se encontraron preguntas ';
$string[ 'hangman_win'] = 'Felicitaciones';
$string[ 'millionaire_win'] = '¡¡¡ Felicitaciones !!!!';
$string[ 'modulename'] = 'Juego';
$string[ 'modulenameplural'] = 'Juegos';
$string[ 'nextgame'] = 'Siguiente juego';
$string[ 'nextword'] = 'Siguiente palabra';
$string[ 'sudoku_guessnumber'] = 'Adivine el número correcto';
$string[ 'sudoku_submit'] = 'Evaluar respuestas';
$string[ 'sudoku_win'] = '¡¡¡ Felicitaciones !!!';
$string[ 'timefinish'] = 'Fin del juego';
$string[ 'timelastattempt'] = 'Último intento';
$string[ 'timestart'] = 'Comienzo';
$string[ 'and'] = 'y';
$string[ 'snakes_background'] = 'Fondo';
$string['useupdategame'] = 'Use el boton  \"Actualizar este Juego\" para ver el detalle del juego';
$string[ 'cryptex_nowords'] = 'No encontró ninguna palabra';
$string[ 'millionaire_nowords'] = 'No encontró ninguna palabra';
$string[ 'tries'] = 'Intentos';
$string[ 'gradeaverage'] = 'Nota promedio';
$string[ 'gradehighest'] = 'Nota más alta';
$string[ 'grademethod'] = 'Método de calificación';
$string[ 'attemptfirst'] = 'Primer intento';
$string[ 'attemptlast'] = 'Último intento';
$string[ 'numattempts'] = '$a->studentnum $a->studentstring ha hecho  $a->attemptnum intentos';
$string[ 'timecompleted'] = 'Completado';
$string[ 'marks'] = 'Marcas';
$string[ 'feedback'] = 'Retroalimentación';
$string[ 'nomoreattempts'] = 'No se permiten más intentos';
$string[ 'gradesofar'] = '$a->method: $a->mygrade / $a->gamegrade.';
$string[ 'yourfinalgradeis'] = 'Su nota final en este juego es $a.';
$string[ 'continueattemptgame'] = 'Continue un intento previo de juego';
$string[ 'attemptgamenow'] = 'Intente jugar ahora';
$string[ 'temporaryblocked'] = 'Usted no puede reintentar el juego temporalmente.<br /> Ud. podrá realizar otro intento en:';
$string[ 'confirmstartattempttimelimit'] = 'Este juego tiene un tiempo límite y está limitado a $a intento(s). Ud. está por comenzar un nuevo intento. ¿Desea continuar?';
$string[ 'confirmstarttimelimit'] = 'EL juego tiene un timepo límite. ¿Está seguro que desea comenzar? ';
$string[ 'confirmstartattemptlimit'] = 'Este juego está limitado a $a intento(s). Ud. está por comenzar un nuevo intento.  ¿Desea continuar?';
$string[ 'noscript'] = '¡JavaScript debe estar habilitado para continuar!';
$string[ 'info'] = 'Info';
$string[ 'results'] = 'Resultados';
$string[ 'preview'] = 'Visualizar';
$string[ 'selectall'] = 'Seleccione todos';
$string[ 'regrade'] = 'Recalificar todos los intentos';
$string[ 'numattempts'] = '$a->studentnum $a->studentstring ha hecho $a->attemptnum intentos';
$string[ 'attempt'] = 'Intento $a';
$string[ 'allattempts'] = 'Mostrar todos los intentos';
$string[ 'attemptsonly'] = 'Mostrar únicamente estudiantes con intentos';
$string[ 'game_snakes'] = 'Serpientes y Escaleras';
$string[ 'snakes_win'] = 'Felicitaciones';
$string[ 'snakes_new'] = 'Nuevo juego';
$string[ 'formatdatetime'] = '%%d %%b %%Y, %%I:%%M %%p';
$string['gametimelimit'] = 'Tiempo límite: $a';
$string['gameclosed'] = 'El juego está cerrado $a';$string['timetaken'] = 'Tiempo utilizado';


//teachers
$string[ 'after_add_can_change_game'] = 'ÚNICAMENTE despues de guardar, Ud. puede cambiar el tipo de juego.';
$string[ 'bookquiz_import_odt'] = 'Importar de archivo OpenOffice (odt)';
$string[ 'cryptex_maxcols'] = 'Máximo número de columnas/filas en Sopa de Letras';
$string[ 'cryptex_maxwords'] = 'Máximo número de palabras en Sopa de Letras';
$string[ 'hangman_countwords'] = '¿Cuántas palabras tiene cada juego?';
$string[ 'hangman_imageset'] = 'Seleccione las imágenes para el ahorcado';
$string[ 'hangman_maxtries'] = 'Número de palabras por juego';
$string[ 'hangman_nowords'] = 'Ninguna palabra encontrada';
$string[ 'hangman_showcorrectanswer'] = 'Mostrar la respuesta correcta después del final';
$string[ 'hangman_showfirst'] = 'Mostrar la primera letra de ahorcado';
$string[ 'hangman_showlast'] = 'Mostrar la última letra del ahorcado';
$string['hangman_showquestion'] = '¿ Mostrar las preguntas ?';
$string[ 'hangman_language'] = 'Idioma de las palabras';
$string[ 'millionaire_must_select_questioncategory'] = 'Usted debe seleccionar una categoría de preguntas';
$string[ 'millionaire_must_select_quiz'] = 'Usted debe seleccionar un cuestionario';
$string[ 'millionaire_sourcemodule_must_quiz_question'] = 'Para millonario la fuente debe ser $a o preguntas y no';
$string[ 'only_teachers'] = 'Sólo el profesor puede ver esta página';
$string[ 'preview'] = 'Visualizar';
$string[ 'selectgame'] = 'Seleccionar juego';
$string[ 'showsolution'] = 'solución';
$string[ 'sourcemodule_book'] = 'Seleccione un libro';
$string[ 'sourcemodule_glossarycategory'] = 'Seleccione una categoría del glosario.';
$string[ 'sourcemodule_glossary'] = 'Seleccione un glosario';
$string[ 'sourcemodule_question'] = 'Preguntas';
$string[ 'sourcemodule_quiz'] = 'Seleccione un cuestionario';
$string[ 'sourcemodule'] = 'Fuente de preguntas';
$string[ 'sudoku_emptydatabase'] = 'La base de datos de sudokus está vacia. Por favor ejecute $a para crearla';
$string[ 'sudoku_noentriesfound'] = 'Ninguna palabra encontrada en el glosario';
$string[ 'sudoku_no_questions'] = 'Ninguna pregunta encontrada';
$string[ 'must_select_questioncategory'] = 'Ud debe seleccionar una categoría de preguntas';
$string[ 'lastip'] = 'IP del estudiante';
$string[ 'sourcemodule_questioncategory'] = 'Seleccione una categoría de preguntas';
$string[ 'finish'] = 'Fin del juego';
$string[ 'bookquiz_not_select_book'] = 'Ud no ha seleccionado un libro';
$string[ 'bookquiz_subchapter'] = 'Subcapítulo';
$string[ 'bookquiz_subchapter'] = 'Cree subcapítulos';
$string[ 'bookquiz_questions'] = 'Asocie categorías de preguntas con capítulos del libro';
$string[ 'sudoku_maxquestions'] = 'Máximo número de preguntas';
$string[ 'bookquiz_empty'] = 'El libro está vacío';
$string[ 'bookquiz_chapters'] = 'Capítulos';
$string[ 'bookquiz_categories'] = 'Categorías';
$string[ 'bookquiz_numquestions'] = 'Preguntas';
$string[ 'showanswers'] = 'Mostrar respuestas';
$string[ 'sudoku_create'] = 'Crear una base de datos de nuevos sudokus';
$string[ 'sudoku_creating'] = 'Creando <b>$a</b> sudoku';
$string[ 'sudoku_create_count'] = 'Número de sudokus que serán creados';
$string[ 'sudoku_create_start'] = 'Comenzar creando sudokus';
$string['hideanswers'] = 'Ocultar respuestas';
$string[ 'must_select_glossary'] = 'Ud debe seleccionar un glosario';
$string[ 'bottomtext'] = 'Texto al final';
$string[ 'messagewin'] = 'Mensaje cuando el estudiante gane';
$string[ 'messageloose'] = 'Mensaje cuando el estudiante pierda';
$string[ 'feedbacks'] = 'Mensajes de respuesta correcta';
$string['cross_layout'] = 'Diseño';
$string['cross_layout0'] = 'Frases en la parte inferior del crucigrama';
$string['cross_layout1'] = 'Frases en la parte derecha del crucigrama';



//other


//1.4
$string['popupblockerwarning'] = 'Alerta de ventana emergente bloqueada';
$string['attemptsallowed'] = 'Intentos permitidos';
$string['gamenotavailable'] = 'Este cuestionario no estará disponible hasta : $a';



//from quiz_analysis.php
$string['attemptsall'] = 'todos los intentos';
$string['attemptsfirst'] = 'primer intento';
$string['attemptshighest'] = 'nota más alta';
$string['attemptslast'] = 'último intento';
$string['pagesize'] = 'Preguntas por página:';
$string['qidtitle'] = 'P#';
$string['qnametitle'] = 'Nombre de la pregunta';
$string['qtexttitle'] = 'Texto de la pregunta';
$string['rpercenttitle'] = 'R.%%';
$string['stddevtitle'] = 'SD';

//from quiz_overview
$string['overview'] = 'Listado';
$string['allstudents'] = 'Mostrar todos $a';

//English 
$string['attemptduration'] = 'Tiempo utilizado';
$string['startedon'] = 'Comenzó en'; 
$string['reportoverview'] = 'Listado';
$string['reportresponses'] = 'Respuestas detalladas';
$string['unfinished'] = 'abierto';  
$string['selectnone'] = 'Des-marcar todos';
$string['deleteattemptcheck'] = 'Esta absolútamente seguro de querer borrar completamente estos intentos?';
$string['selectnone'] = 'Des-marcar todos'; 
$string['withselected'] = 'Con seleccionados';
$string['displayoptions'] = 'Mostrar opciones';
$string['showdetailedmarks'] = 'Mostrar detalles de marca';
$string['analysis'] = 'Mostrar detalles de marca';
$string['showdetailedmarks'] = 'Mostrar detalles de marca';
$string['noattemptsonly'] = 'Mostrar $a unicamente sin intentos';
$string[ 'downloadods'] = 'Descargar en formato ODS';



$string[ 'reattemptgame'] = 'Juego de reintento';
$string[ 'grade'] = 'Nota';
$string[ 'startagain'] = 'Comenzar de nuevo';
$string[ 'attempts'] = 'Intentos';
$string[ 'completedon'] = 'Completado en';
$string[ 'outof'] = '$a->grade out of a maximum of $a->maxgrade';
$string[ 'review'] = 'Revisar';
$string[ 'noattempts'] = 'Ningún intento ha sido hecho en este cuestionario';
$string[ 'cross_endofgamebutton'] = 'Fin del juego de crucigrama';
$string[ 'reviewofattempt'] = 'Revisar intentos $a';
$string[ 'score'] = 'Puntaje';
$string[ 'sudoku_finishattemptbutton'] = 'Fin del juego de sudoku';
$string[ 'must_select_quiz'] = 'Ud debe seleccionar un cuestionario';
$string[ 'createbottomtext'] = 'Establezca el texto en la parte inferior';
$string[ 'game_hiddenpicture'] = 'Imagen oculta';
$string[ 'hiddenpicture_across'] = 'Celdas horizontales';
$string[ 'hiddenpicture_down'] = 'Celdas verticales';
$string[ 'hiddenpicture_finishattemptbutton'] = 'Fin del juego de la imagen oculta';
$string[ 'hiddenpicture_grade'] = 'Calificación';
$string[ 'hiddenpicture_height'] = 'Establecer la altura de la imagen en';
$string[ 'hiddenpicture_mainsubmit'] = 'Calificación de la pregunta principal';
$string[ 'hiddenpicture_nocols'] = 'Tiene que especificar el número de filas horizontales';
$string[ 'hiddenpicture_nomainquestion'] = 'No hay entradas en el glosario glosario $a->name con una imagen adjunta';
$string[ 'hiddenpicture_norows'] = 'Tiene que especificar el número de columnas verticales';
$string[ 'hiddenpicture_pictureglossary'] = 'El glosario para la cuestión principal';
$string[ 'hiddenpicture_pictureglossarycategories'] = 'La categoría de glosario para la cuestión principal';
$string[ 'hiddenpicture_submit'] = 'Calificar respuestas';
$string[ 'hiddenpicture_width'] = 'Establecer el ancho de la imagen en';
$string[ 'hiddenpicture_win'] = 'Felicidades';
$string[ 'useeditgame'] = 'Utilice la Edición para establecer los detalles del juego';
$string[ 'export'] = 'Exporta a móvil';

$string[ 'javame_createdby'] = 'Creado por:';
$string[ 'javame_description'] = 'Descripción:';
$string[ 'javame_filename'] = 'Nombe del archivo:';
$string[ 'javame_icon'] = 'Icono:';
$string[ 'javame_name'] = 'Nombre:';
$string[ 'javame_vendor'] = 'Vendedor:';
$string[ 'javame_version'] = 'Versión';
?>
