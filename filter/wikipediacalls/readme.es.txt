Marcado simple para Wikipedia. El filtro usa la lengua corriente
para lazar al Wikipedia de la lengua de la sesi&oacute;n corriente.

Autor : Valéry Frémaux. 11/2006 (vf@eisti.fr)

Instalar el filtro :

    - copiar in <%%moodle_install%%>/filter
    - copiar los ficheros de lengua en el lugar
      adecuado in <%%moodle_install%%>/lang
    - activar el filtro desde "Administraci&oacute;n/filtros".
  
Usar el filtro :

    - Marcas directas : 
    	
    	Marcar una palabra con el sufijo [WP] provoca la creaci&oacute;n
    	del lazo directo hacia Wikipedia para esta palabra . Ejemplo :
    	
    	Etnometodologia[WP]
    	
    	Para marcar un grupo de palabras o una expresi&oacute;n, cambiar 
    	los espacios por espacios insecables (Ctrl+Maj+Esp en la maioria de las situaciones) 
    	entre los componentes de la expresi&oacute;n. Ejemplo :
    	
    	Yoshua{^s}Bar-Hillel[WP]
    	
    - Marcas indirectas
    
      Para llegar sobre un art&iacute;culo distincto de la palabra literal, solo 
      a&ntilde;ade a la marca [WP] un par&aacute;metro adicional. Usa el caracter | 
      (pipe) para separar. Ejemplo :
      
      Etnologico[WP|Etnologia]

      Nota : Wikipedia hace el mismo algunas translaciones semanticas.
      
    - Cambio de idioma
    
      Un tercero par&aacute;metro permite apuntar un art&iacute;culo escrito
      en otra idioma que la lengua corriente. Ejemplo :

      Ethnologia[WP|Ideology|en]

Par&aacute;metros

	   El filtro permite activar o desactivar un resumen de las claves recoltadas. 
	   a traves el contenido. Si est&aacute; activada, la lista de lazos Wikipedia 
	   esta visible al final de cada bloque de contenidos. Un lazo llega a una
	   pantalla para testar la realidad de las metas. En todos casos, el resumen
	   y las herramientas asociadas son visibles unicamente por los profesores del
	   curso.

Functiones adicionales

	 - Comprobar los lazos
	 
	 Para facilitar la prueba de los lazos, una funcci&oacute;n automatizada
	 estuvo implementada. Esta funcci&oacute;n permite comprobar, en un bloque
	 la presencia y la pertinencia de p&aacute;ginas Wikipedia por las marcas
	 depositadas. Cada resumen ofrece un lazo hasta esta pantalla de prueba. 
	 
	 Cliquear sobre "Empezar las pruebas" para activar el proceso de prueba.
	 
	 Atenci&oacute;n : La prueba esta procesada en el cliente (Ajax). Necesita
	 la posibilida de cargar contenidos a partir de dominios distinctos 
	 para utilizar esta funcionalidad. Es posible modificando las opciones 
	 de seguridad del navegador (IE -> Herramientas -> Opciones Internet -> Seguridad-> 
	 Personalizar el nivel -> Aceso a datos a traves dominios m&uacute;ltiples).
	 
	 Optar por el modo "Pedir".

