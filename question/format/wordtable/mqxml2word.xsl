<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet exclude-result-prefixes="htm o w"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:o="urn:schemas-microsoft-com:office:office"
	xmlns:w="urn:schemas-microsoft-com:office:word"
	xmlns:htm="http://www.w3.org/1999/xhtml"
	xmlns="http://www.w3.org/1999/xhtml"
	version="1.0">

<xsl:param name="htmltemplatefile" select="'mqwordq_template.xhtm'"/>
<xsl:param name="course_name"/>
<xsl:param name="course_id"/>
<xsl:param name="author_name"/>
<xsl:param name="author_id"/>
<xsl:param name="institution_name"/>
<xsl:param name="moodle_url"/>

<xsl:variable name="htmltemplate" select="document($htmltemplatefile)" />

<xsl:variable name="ucase" select="'ABCDEFGHIJKLMNOPQRSTUVWXYZ'" />
<xsl:variable name="lcase" select="'abcdefghijklmnopqrstuvwxyz'" />

<xsl:output method="xml" version="1.0" omit-xml-declaration="yes" encoding="ISO-8859-1" indent="yes" />


<!-- Read in the input XML into a variable, so that it can be processed -->
<xsl:variable name="data" select="/" />

<!-- Match document root node, and read in and process Word-compatible XHTML template -->
<xsl:template match="/">
    <xsl:apply-templates select="$htmltemplate/*" />
</xsl:template>


<!-- Place questions in XHTML template body -->
<xsl:template match="processing-instruction('replace')[.='insert-content']">
	<xsl:comment>HTML template parameter: <xsl:value-of select="$htmltemplatefile"/></xsl:comment>
	<xsl:comment>Institution: <xsl:value-of select="$institution_name"/></xsl:comment>
	<xsl:comment>Moodle URL: <xsl:value-of select="$moodle_url"/></xsl:comment>
	<xsl:comment>Course name: <xsl:value-of select="$course_name"/></xsl:comment>
	<xsl:comment>Course ID: <xsl:value-of select="$course_id"/></xsl:comment>
	<xsl:comment>Author name: <xsl:value-of select="$author_name"/></xsl:comment>
	<xsl:comment>Author ID: <xsl:value-of select="$author_id"/></xsl:comment>
	
	<!-- Put the course name in as the title -->
	<p class="MsoTitle"><xsl:value-of select="normalize-space($course_name)"/></p>
	<p class="MsoBodyText">&#160;</p>

	<!-- Handle the questions -->
	<xsl:apply-templates select="$data/quiz/question"/>
	
	<!-- Process the questions -->
<!-- 
	<xsl:apply-templates select="$data/quiz/question[1]" mode="category"/>
	<xsl:apply-templates select="$data/quiz/question[position() > 1]"/>
-->
</xsl:template>

<!-- Metadata -->
<!-- Set the title property (File->Properties... Summary tab) -->
<xsl:template match="processing-instruction('replace')[.='insert-title']">
	<xsl:variable name="category">
		<xsl:variable name="raw_category" select="normalize-space($data/quiz/question[1]/category)"/>
	
		<xsl:choose>
		<xsl:when test="contains($raw_category, '$course$/')">
			<xsl:value-of select="substring-after($raw_category, '$course$/')"/>
		</xsl:when>
		<xsl:otherwise><xsl:value-of select="$raw_category"/></xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
		
	<!-- Place category info and course name into document title -->
	<xsl:value-of select="concat($category, ' - ', $course_name)"/>
</xsl:template>

<!-- Set the author property -->
<xsl:template match="processing-instruction('replace')[.='insert-author']">
	<xsl:value-of select="$author_name"/>
</xsl:template>

<xsl:template match="processing-instruction('replace')[.='insert-meta']">
	<!-- Place category info and course name into document title -->
	<o:moodleCourseID><xsl:value-of select="$course_id"/></o:moodleCourseID>
	<o:moodleURL><xsl:value-of select="$moodle_url"/></o:moodleURL>
	<o:DC.Type><xsl:value-of select="'Question'"/></o:DC.Type>
	<o:moodleQuestionSeqNum><xsl:value-of select="count($data/quiz/question[@type != 'category']) + 1"/></o:moodleQuestionSeqNum>
	<o:yawcToolbarBehaviour><xsl:value-of select="'doNothing'"/></o:yawcToolbarBehaviour>
	
	
</xsl:template>

<xsl:template match="processing-instruction('replace')[.='insert-institution']">
	<!-- Place category info and course name into document title -->
	<xsl:value-of select="$institution_name"/>
</xsl:template>


<!-- Category becomes a Heading 1 style -->
<!-- There can be lots of categories, but they can also be duplicated -->
<xsl:template match="question[@type = 'category']">
	<xsl:variable name="category">
		<xsl:variable name="raw_category" select="normalize-space(category)"/>
	
		<xsl:choose>
		<xsl:when test="contains($raw_category, '$course$/')">
			<xsl:value-of select="substring-after($raw_category, '$course$/')"/>
		</xsl:when>
		<xsl:otherwise><xsl:value-of select="$raw_category"/></xsl:otherwise>
		</xsl:choose>
	</xsl:variable>

	<h1 class="MsoHeading1"><xsl:value-of select="$category"/></h1>
	<p class="MsoBodyText">&#160;</p>
</xsl:template>


<!-- Handle the questions -->
<xsl:template match="question">
	<xsl:variable name="qtype">
		<xsl:choose>
		<xsl:when test="@type = 'truefalse'"><xsl:text>TF</xsl:text></xsl:when>
		<xsl:when test="@type = 'matching'"><xsl:text>MAT</xsl:text></xsl:when>
		<xsl:when test="@type = 'shortanswer'"><xsl:text>SA</xsl:text></xsl:when>
		<xsl:when test="@type = 'multichoice' and single = 'false'"><xsl:text>MA</xsl:text></xsl:when>
		<xsl:when test="@type = 'description'"><xsl:text>DE</xsl:text></xsl:when>
		<xsl:when test="@type = 'essay'"><xsl:text>ES</xsl:text></xsl:when>

		<!-- Not really supported as yet -->
		<xsl:when test="@type = 'cloze'"><xsl:text>CL</xsl:text></xsl:when>
		<xsl:when test="@type = 'numerical'"><xsl:text>NUM</xsl:text></xsl:when>

		<xsl:otherwise><xsl:text>MC</xsl:text></xsl:otherwise>
		</xsl:choose>
	</xsl:variable>

	
	<xsl:variable name="col2_body_label">
		<xsl:choose>
		<xsl:when test="$qtype = 'DE'">	<xsl:text></xsl:text></xsl:when>
		<xsl:when test="$qtype = 'ES'"><xsl:text></xsl:text></xsl:when>
		<xsl:when test="$qtype = 'CL'"><xsl:text></xsl:text></xsl:when>
		<xsl:when test="$qtype = 'MAT'">	<xsl:text>Item</xsl:text></xsl:when>
		<xsl:otherwise><xsl:text>Answers</xsl:text></xsl:otherwise>
		</xsl:choose>
	</xsl:variable>

	<xsl:comment>qtype = <xsl:value-of select="$qtype"/>; label = <xsl:value-of select="$col2_body_label"/></xsl:comment>
	<!-- Get the question stem and put it in the heading -->
	<h2 class="MsoHeading2">
			<xsl:value-of select="name"/>
	</h2>
	<p class="MsoBodyText"> </p>
<!--
	<h2 class="MsoHeading2"><xsl:value-of select="normalize-space($stem)" disable-output-escaping="yes"/></h2>
	<p class="MsoBodyText">&#160;</p>	-->
	
	<!-- Get the answers -->
	<div class="TableDiv">
	<table border="1">
	<thead>
		<xsl:text>&#x0a;</xsl:text>
		<tr>
			<td colspan="3" style="width: 12.0cm"><p class="Cell">
				<xsl:choose>
				<xsl:when test="$qtype = 'CL'">
					<!-- Put Cloze text into the first option table cell, and convert special markup too-->
					<xsl:apply-templates select="questiontext/text" mode="cloze"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:apply-templates select="questiontext/text"/>
				</xsl:otherwise>
				</xsl:choose>
			</p></td>
			<td style="width: 1.0cm"><p class="QFType"><xsl:value-of select="$qtype" /></p></td>
		</tr>
		<xsl:text>&#x0a;</xsl:text>
		<!--
		<tr>
			<td style="width: 1.0cm"><p class="Cell">&#160;</p></td>
			<td style="width: 5.0cm"><p class="TableRowHead">Name:</p></td>
			<td style="width: 6.0cm"><p class="QFName"><xsl:value-of select="name"/></p></td>
			<td style="width: 1.0cm"><p class="QFItemNumber">&#160;</p></td>
		</tr>
		-->
		<xsl:text>&#x0a;</xsl:text>
		<!-- Heading row for answers -->
		<tr>
			<td style="width: 1.0cm"><p class="TableHead">#</p></td>
			<td style="width: 5.0cm"><p class="QFOptionReset"><xsl:value-of select="$col2_body_label"/></p></td>
			<xsl:choose>
			<xsl:when test="$qtype = 'CL' or $qtype = 'DE' or $qtype = 'ES'">
				<td style="width: 6.0cm"><p class="TableHead">&#160;</p></td>
				<td style="width: 1.0cm"><p class="TableHead">&#160;</p></td>
			</xsl:when>
			<xsl:when test="$qtype = 'MAT'">
				<td style="width: 6.0cm"><p class="TableHead">Match</p></td>
				<td style="width: 1.0cm"><p class="TableHead">&#160;</p></td>
			</xsl:when>
			<xsl:otherwise>
				<td style="width: 6.0cm"><p class="TableHead">Hints/Feedback</p></td>
				<td style="width: 1.0cm"><p class="TableHead">Grade</p></td>
			</xsl:otherwise>
			</xsl:choose>
		</tr>
		<xsl:text>&#x0a;</xsl:text>
	</thead>
	<tbody>
	<xsl:text>&#x0a;</xsl:text>

	<!-- Handle the body, containing the options and feedback (for most questions) -->
	<xsl:choose>
	<xsl:when test="$qtype = 'DE' or $qtype = 'ES' or $qtype = 'CL'">
		<!-- Put in blank row  -->
		<tr>
			<td style="width: 1.0cm"><p class="Cell">&#160;</p></td>
			<td style="width: 5.0cm"><p class="Cell">&#160;</p></td>
			<td style="width: 6.0cm"><p class="Cell">&#160;</p></td>
			<td style="width: 1.0cm"><p class="Cell">&#160;</p></td>
		</tr>
	</xsl:when>
	<xsl:otherwise>
		<xsl:apply-templates select="answer|subquestion"/>
	</xsl:otherwise>
	</xsl:choose>
	<xsl:text>&#x0a;</xsl:text>

		
		<!-- Correct and Incorrect feedback for MC and MA questions only -->
		<xsl:if test="$qtype = 'MC' or $qtype = 'MA'">
			<tr>
				<td style="width: 1.0cm"><p class="Cell">&#160;</p></td>
				<th style="width: 5.0cm"><p class="TableRowHead">Correct Feedback:</p></th>
				<td style="width: 6.0cm"><p class="Cell"><xsl:apply-templates select="correctfeedback/text"/></p></td>
				<td style="width: 1.0cm"><p class="Cell">&#160;</p></td>
			</tr>
			<xsl:text>&#x0a;</xsl:text>
			<tr>
				<td style="width: 1.0cm"><p class="Cell">&#160;</p></td>
				<th style="width: 5.0cm"><p class="TableRowHead">Incorrect Feedback:</p></th>
				<td style="width: 6.0cm"><p class="Cell"><xsl:apply-templates select="incorrectfeedback/text"/></p></td>
				<td style="width: 1.0cm"><p class="Cell">&#160;</p></td>
			</tr>
			<xsl:text>&#x0a;</xsl:text>
		</xsl:if>
		<!-- Partially correct feedback for Multi-answer questions only -->
		<xsl:if test="$qtype = 'MA'">
			<tr>
				<td style="width: 1.0cm"><p class="Cell">&#160;</p></td>
				<th style="width: 5.0cm"><p class="TableRowHead">Partially Correct Feedback:</p></th>
				<td style="width: 6.0cm"><p class="Cell"><xsl:apply-templates select="partiallycorrectfeedback/text"/></p></td>
				<td style="width: 1.0cm"><p class="Cell">&#160;</p></td>
			</tr>
			<xsl:text>&#x0a;</xsl:text>
		</xsl:if>
		<!-- General feedback for all question types: MA, MAT, MC, TF, SA -->
		<xsl:if test="$qtype = 'MC' or $qtype = 'MA' or $qtype = 'MAT' or $qtype = 'SA' or $qtype = 'TF' or $qtype = 'CL'">
			<tr>
				<td style="width: 1.0cm"><p class="Cell">&#160;</p></td>
				<th style="width: 5.0cm"><p class="TableRowHead">General Feedback:</p></th>
				<td style="width: 6.0cm"><p class="Cell"><xsl:apply-templates select="generalfeedback/text"/></p></td>
				<td style="width: 1.0cm"><p class="Cell">&#160;</p></td>
			</tr>
		<xsl:text>&#x0a;</xsl:text>
		</xsl:if>
	</tbody>
	</table>
	</div>
</xsl:template>

<!-- Handle standard question rows -->
<xsl:template match="answer|subquestion">
	<tr>
		<td style="width: 1.0cm"><p class="QFOption">&#160;</p></td>
		<td style="width: 5.0cm"><p class="Cell"><xsl:apply-templates select="text"/></p></td>
		
		<xsl:choose>
		<xsl:when test="contains(name(), 'subquestion')">
			<td style="width: 6.0cm"><p class="Cell"><xsl:apply-templates select="answer/text"/></p></td>
			<td style="width: 1.0cm"><p class="QFGrade">&#160;</p></td>
		</xsl:when>
		<xsl:otherwise>
			<td style="width: 6.0cm"><p class="QFFeedback"><xsl:apply-templates select="feedback/text"/></p></td>
			<td style="width: 1.0cm"><p class="QFGrade"><xsl:value-of select="@fraction"/></p></td>
		</xsl:otherwise>
		</xsl:choose>
	</tr>
</xsl:template>

<xsl:template match="answer/text">
	<xsl:apply-templates/>
</xsl:template>

<xsl:template match="p[not(@class)]">
	<p class="Cell">
		<xsl:apply-templates/>
	</p>
</xsl:template>

<xsl:template match="text" mode="fbtext">
	<xsl:variable name="text_string">
		<xsl:variable name="raw_text" select="normalize-space(.)"/>
		
		<xsl:choose>
		<!-- If the string is wrapped in <p>...</p>, get rid of it -->
		<xsl:when test="starts-with($raw_text, '&lt;p&gt;') and substring($raw_text, -4) = '&lt;/p&gt;'">
			<!-- 7 = string-length('<p>') + string-length('</p>') </p> -->
			<xsl:value-of select="substring($raw_text, 4, string-length($raw_text) - 7)"/>
		</xsl:when>
		<xsl:when test="$raw_text = ''"><xsl:text>&#160;</xsl:text></xsl:when>
		<xsl:otherwise><xsl:value-of select="$raw_text"/></xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	
</xsl:template>

<xsl:template match="feedback/text">
	<xsl:variable name="feedback" select="normalize-space(.)" />
	
	<xsl:choose>
	<xsl:when test="$feedback = ''"><xsl:value-of select="'&#160;'"/></xsl:when>
	<xsl:otherwise><xsl:apply-templates/></xsl:otherwise>
	</xsl:choose>
	
</xsl:template>

<!-- Handle CDATA-encoded text -->
<xsl:template match="text">
	<xsl:variable name="text_string">
		<xsl:variable name="raw_text" select="normalize-space(.)"/>
		
		<xsl:choose>
		<!-- If the string is wrapped in <p>...</p>, get rid of it -->
		<xsl:when test="starts-with($raw_text, '&lt;p&gt;') and substring($raw_text, -4) = '&lt;/p&gt;'">
			<!-- 7 = string-length('<p>') + string-length('</p>') </p> -->
			<xsl:value-of select="substring($raw_text, 4, string-length($raw_text) - 7)"/>
		</xsl:when>
		<xsl:when test="$raw_text = ''"><xsl:text>&#160;</xsl:text></xsl:when>
		<xsl:otherwise><xsl:value-of select="$raw_text"/></xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	
	<xsl:value-of select="$text_string" disable-output-escaping="yes"/>
</xsl:template>

<!-- Handle Cloze text -->
<xsl:template match="text" mode="cloze">
	<xsl:variable name="text_string">
		<xsl:variable name="raw_text" select="normalize-space(.)"/>
		
		<xsl:choose>
		<!-- If the string is wrapped in <p>...</p>, get rid of it -->
		<xsl:when test="starts-with($raw_text, '&lt;p&gt;') and substring($raw_text, -4) = '&lt;/p&gt;'">
			<!-- 7 = string-length('<p>') + string-length('</p>') </p> -->
			<xsl:value-of select="substring($raw_text, 4, string-length($raw_text) - 7)"/>
		</xsl:when>
		<xsl:when test="$raw_text = ''"><xsl:text>&#160;</xsl:text></xsl:when>
		<xsl:otherwise><xsl:value-of select="$raw_text"/></xsl:otherwise>
		</xsl:choose>
	</xsl:variable>

	<xsl:call-template name="convert_cloze_string">
		<xsl:with-param name="cloze_string" select="$text_string"/>
	</xsl:call-template>
	
	<!--<xsl:value-of select="$text_string" disable-output-escaping="yes"/>-->
</xsl:template>

<!-- Convert Cloze text strings -->
<xsl:template name="convert_cloze_string">
	<xsl:param name="cloze_string"/>
	
	<xsl:choose>
	<xsl:when test="contains($cloze_string, '{')">
		<!-- Copy the text prior to the embedded question -->
		<xsl:value-of select="substring-before($cloze_string, '{')" disable-output-escaping="yes"/>
		
		<!-- PRocess the embedded cloze -->
		<xsl:call-template name="convert_cloze_item">
			<xsl:with-param name="cloze_item" 
				select="substring-before(substring-after($cloze_string, '{'), '}')"/>
		</xsl:call-template>
		<!-- Recurse through the string again -->
		<xsl:call-template name="convert_cloze_string">
			<xsl:with-param name="cloze_item" select="substring-after($cloze_string, '}')"/>
		</xsl:call-template>
	</xsl:when>
	<xsl:otherwise>
		<xsl:value-of select="$cloze_string" disable-output-escaping="yes"/>
	</xsl:otherwise>
	</xsl:choose>
</xsl:template>

<!-- Convert embedded NUMERICAL, SHORTANSWER or MULTICHOICE into markup-->
<xsl:template name="convert_cloze_item">
	<xsl:param name="cloze_item"/>
	
	<xsl:choose>
	<xsl:when test="contains($cloze_item, 'NUMERICAL')">
		<u>
			<xsl:call-template name="format_cloze_item">
				<xsl:with-param name="cloze_item" select="substring-after($cloze_item, 'NUMERICAL:')"/>
			</xsl:call-template>
		</u>
	</xsl:when>
	<xsl:when test="contains($cloze_item, 'SHORTANSWER')">
		<i>
			<xsl:call-template name="format_cloze_item">
				<xsl:with-param name="cloze_item" select="substring-after($cloze_item, 'SHORTANSWER:')"/>
			</xsl:call-template>
		</i>
	</xsl:when>
	<xsl:when test="contains($cloze_item, 'MULTICHOICE')">
		<b>
			<xsl:call-template name="format_cloze_item">
				<xsl:with-param name="cloze_item" select="substring-after($cloze_item, 'MULTICHOICE:')"/>
			</xsl:call-template>
		</b>
	</xsl:when>
	</xsl:choose>
</xsl:template>

<!-- Separate items with newlines -->
<xsl:template name="format_cloze_item">
	<xsl:param name="cloze_item"/>
	
	<xsl:choose>
	<xsl:when test="contains($cloze_item, '~')">
		<xsl:value-of select="substring-before($cloze_item, '~')"/>
		<xsl:value-of select="'|&lt;br/&gt;'" disable-output-escaping="yes"/>
		<xsl:call-template name="format_cloze_item">
			<xsl:with-param name="cloze_item" select="substring-after($cloze_item, '~')"/>
		</xsl:call-template>
	</xsl:when>
	<xsl:otherwise>
		<xsl:value-of select="$cloze_item"/>
	</xsl:otherwise>
	</xsl:choose>
</xsl:template>

<!-- Read in the template and copy it to the output -->
<xsl:template match="/html | htm:html">
	<html
		xmlns:o="urn:schemas-microsoft-com:office:office"
		xmlns:w="urn:schemas-microsoft-com:office:word">
		
		<!-- 
		<xsl:comment>Inserted VML namespace</xsl:comment>
		<xsl:comment>Language: <xsl:value-of select="$lang"/></xsl:comment>
		<xsl:comment>Translation Map: <xsl:value-of select="$translation_mapping"/></xsl:comment>
		<xsl:comment>Contents: <xsl:value-of select="$translation_mapping/ol/li[@id='Contents']"/></xsl:comment>
		<xsl:comment>table_of_contents_label: <xsl:value-of select="$table_of_contents_label"/></xsl:comment>
		 -->
		<xsl:apply-templates select="*" />
	</html>
</xsl:template>



<!-- got to preserve comments for style definitions -->
<xsl:template match="comment()">
	<xsl:comment><xsl:value-of select="."  /></xsl:comment>
</xsl:template>

<!-- Identity transformations -->
<xsl:template match="*">
	<xsl:element name="{name()}">
		<xsl:call-template name="copyAttributes" />
		<xsl:apply-templates select="node()"/>
	</xsl:element>
</xsl:template>



<xsl:template name="copyAttributes">
	<xsl:for-each select="@*">
		<xsl:attribute name="{name()}"><xsl:value-of select="."/></xsl:attribute>
	</xsl:for-each>
</xsl:template>

</xsl:stylesheet>
