<?php

$data = array();

$html = file_get_contents('java_coursrea_html.txt');

$chapterPreg = '#<ul class="course-item-list-section-list">(.+?)</ul>#s';

preg_match_all($chapterPreg, $html, $chapterMatche);


$chapterHtmlArr = $chapterMatche[1];

foreach ($chapterHtmlArr as $k => $chapterHtml) {

	$sectionPreg = '#<div class="course-lecture-item-resource">(.+?)</a>\s+</div>#s';
	preg_match_all($sectionPreg, $chapterHtml, $sectionMatche);

	$listHtmlArr = $sectionMatche[1];

	foreach ($listHtmlArr as $listHtml) {

		$itemPreg = '#<a target="_new" href="([^"]+)".+?<div class="hidden">([^<]+)</div>#s';
		preg_match_all($itemPreg, $listHtml, $itemMatche);

		$title = explode(' for ', $itemMatche[2][0])[1];
		$data[$k][$title] = $itemMatche[1];
		
	}

	echo '<pre>';
print_r($data);
echo '</pre>';
exit;

}





