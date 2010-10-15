Marques simples pour Wikipedia. Le filtre exploite la langue courante 
pour rediriger vers le Wikipedia dans la langue de la session courante.

Auteur : Valéry Frémaux. 11/2006 (vf@eisti.fr)

Pour l'installer:
    - copier dans <%%moodle_install%%>/filter
    - copier les fichiers de langues nécessaires aux emplacements 
      adéquats dans <%%moodle_install%%>/lang
    - Activez le filtre depuis "Administration/Filtres".
  
Pour l'utiliser :
    - Balisage direct : 
    	
    	Marquer un mot par la balise [WP] provoque la création du lien 
    	direct vers Wikipedia pour le mot. Exemple :
    	
    	Ethnométhodologie[WP]
    	
    	Pour marquer un groupe de mots ou une locution, il faut placer des 
    	espaces insécables (Ctrl+Maj+Esp dans la plupart des cas) entre les 
    	mots de la locution que précède le marqueur. Exemple :
    	
    	Yoshua[^s]Bar-Hillel[WP]
    	
    - Balisage indirect
    
      Pour atteindre un article différent du mot marqué, il suffit d'étendre 
      la balise [WP] par un paramètre complémentaire. Le séparateur est le | 
      (pipe). Exemple :
      
      Ethnologique[WP|Ethnologie]
      
    - Changement de la langue
    
      On peut accessoirement mentionner un troisième paramètre permettant 
      d'atteindre des articles dans une langue autre que celle de la session
      courante. Exemple :

      Ethnologique[WP|Ideology|en]

Paramétrage :

	   Le filtre permet d'activer ou de désactiver le compte rendu des clefs
	   collectées. Si elle est activée, la liste des liens Wikipedia est mentionnée
	   en compte-rendu de bloc de contenu. Un lien est présenté pour tester ces
	   liens. Dans tous les cas, seuls les professeurs du cours peuvent consulter
	   ce rapport et activer le test

Fonctions supplémentaires :

	 - Test automatique des liaisons
	 
	 Pour faciliter la vérification des liaisons, une fonction automatique de
	 test des liens générés a été implémentée. Cette fonction permet, dans un 
	 bloc de contenu donné, de tester la présence de pages Wikipedia pour les
	 clefs d'articles générés. La liste des clefs collectées (rapport) offre un
	 lien vers une popup de test. 
	 
	 Clickez sur "Démarrer le test" pour lancer la séquence de test des liaisons.
	 
	 Attention : Ce test fonctionne à partir du client (implémenté en Ajax). Vous
	 devez pouvoir charger des contenus multi-domaines pour pourvoir utiliser cette 
	 fonction. Cette possibilité est réglable dans les options de sécurité du 
	 navigateur (IE -> Outils -> Options Internet -> Sécurité -> Personnaliser le
	 Niveau -> Accès aux sources de données sur plusieurs domaines).
	 
	 Il est préférable de mettre cette option sur "Demander".

