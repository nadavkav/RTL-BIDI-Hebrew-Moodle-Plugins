{* complete modified by Harry - beginn *}

{$yuilibs}

{$script1}

{section name=field loop=$formfields}
{$formfields[field]}
{/section}

{$question->questiontext}

<div id="border" style="border:solid medium black;">
<table>
    <tr><td>
            <div id="top">
                <div id="toprightbottom" style="padding:4px;">

                    <div style="height:{$background->height}px;width:{$background->width}px;">
                           {$background->mediatag}
                    </div>

                    <script type="text/javascript">
                    var backgroundId = "background{$background->id}";
                    </script>

                </div>
            </div>
    </td>

    {* placing the media below or beside the background *}
    {if $question->options->placemedia == 0}
        </tr>
        <tr>
    {/if}

    <td>
            <div id="bottom" style="">

            <table border="1">
            {* Number of columns per line *}
            {assign var="cols" value=$question->options->arrangemedia}
            {if $cols == 0}
                {assign var="cols" value="999"}
            {/if}

            {section name=img loop=$gapimages}

                {if $smarty.section.img.index % $cols == 0}
                    <tr>
                {/if}

                <td bgcolor="#CCCCCC" valign="top">
                <table width="100%">
                    <tr>
                        <td bgcolor="#CCCCCC">
                            {$gapimages[img].tag}

                            <script type="text/javascript">
                            arrOfGapimageObjects[{$smarty.section.img.index}]                   = new Object();
                            arrOfGapimageObjects[{$smarty.section.img.index}]["name"]           = "{$gapimages[img].name}";
                            arrOfGapimageObjects[{$smarty.section.img.index}]["responseFormId"] = "{$gapimages[img].responseFormId}";
                            arrOfGapimageObjects[{$smarty.section.img.index}]["backgroundId"]   = backgroundId;
                            arrOfGapimageObjects[{$smarty.section.img.index}]["width"]          = {$gapimages[img].width};
                            arrOfGapimageObjects[{$smarty.section.img.index}]["height"]         = {$gapimages[img].height};
                            </script>
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#CCCCCC">{$gapimages[img].text}</td>
                    </tr>
                </table>
                </td>

                {if $smarty.section.img.index % $cols == $cols - 1 || $cols == 1 || $smarty.section.img.last}
                    </tr>
                {/if}

            {/section}
            </table>

            </div>
    </td></tr>
</table>
</div>


{$script2}
{* complete modified by Harry - end *}

