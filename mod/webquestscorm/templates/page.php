<?php
/**
 * 
 * @author Julia Tejerina, Oscar Sanchez, Javier Gonzalez
 * @version $Id: page.php,v 2.0 2009/25/04
 * @package webquestscorm
 **/

 require_once('css/'.$this->webquestscorm->template)?>
<table border="0" width="100%">
<tr>

<?php
if (($this->webquestscorm->template!="topblue.css") && ($this->webquestscorm->template!="topgreen.css") && ($this->webquestscorm->template!="toporange.css")){
?>
<td width="12%" valign="top">
<?php
}
?>
<div id="wrapper">


<div id="navcontainer">
<ul id="navlist">
	<li
	<?php if ($element=='introduction') {
	          echo 'id="current"';
	      }
	?>
	><a href="editpreview.php?cmid=<?php echo $this->cm->id;?>&element=introduction" title="Introduction to the WebQuest."><?php echo get_string('introduction','webquestscorm');?></a></li>
	<li 
	<?php if ($element=='task') {
	          echo 'id="current"';
	      }
	?>
	 ><a href="editpreview.php?cmid=<?php echo $this->cm->id;?>&element=task" title="Description of the major task of the WebQuest."><?php echo get_string('task','webquestscorm');?></a></li>
	<li
	<?php if ($element=='process') {
	          echo 'id="current"';
	      }
	?>	
	><a href="editpreview.php?cmid=<?php echo $this->cm->id;?>&element=process" title="How you are going to go about completing the WebQuest."><?php echo get_string('process','webquestscorm');?></a></li>
	<li
	<?php if ($element=='evaluation') {
	          echo 'id="current"';
	      }
	?>
	><a href="editpreview.php?cmid=<?php echo $this->cm->id;?>&element=evaluation" title="How your teacher will evaluate your progress and performance."><?php echo get_string('evaluation','webquestscorm');?></a></li>
	<li
	<?php if ($element=='conclusion') {
	          echo 'id="current"';
	      }
	?>
	><a href="editpreview.php?cmid=<?php echo $this->cm->id;?>&element=conclusion" title="Final remarks about the WebQuest."><?php echo get_string('conclusion','webquestscorm');?></a></li>
	<li
	<?php if ($element=='credits') {
	          echo 'id="current"';
	      }
	?>
	><a href="editpreview.php?cmid=<?php echo $this->cm->id;?>&element=credits" title="Credits and references used through the WebQuest."><?php echo get_string('credits','webquestscorm');?></a></li>

</ul>
</div>

<?php
if (($this->webquestscorm->template!="topblue.css") && ($this->webquestscorm->template!="topgreen.css") && ($this->webquestscorm->template!="toporange.css")){
?>
</td><td valign="top">
<?php
}
?>

<h2><?php echo get_string($element,'webquestscorm');?></h2>

<p><?php echo $this->webquestscorm->$element;?></p>


</div>

</td></tr></table>

<div id="footer">
 <p>Based on the<a href="http://www.educationaltechnology.ca/webquestscorm"> Original webquestscorm template</a> design by <a href="http://www.educationaltechnology.ca/dan">Dan Schellenberg</a> using valid <a href="http://validator.w3.org/check/referer">XHTML</a> and <a href="http://jigsaw.w3.org/css-validator/validator?uri=http://www.educationaltechnology.ca/webquestscorm/css/basic.css">CSS</a>.</p>
</div>

	</body>
</html>
