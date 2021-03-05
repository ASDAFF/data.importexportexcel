<?
$MESS["KDA_IE_HELP_TAB1"] = "Video instructions";
$MESS["KDA_IE_HELP_TAB1_ALT"] = "Tuning import video instructions";
$MESS["KDA_IE_HELP_TAB2"] = "FAQ";
$MESS["KDA_IE_HELP_TAB2_ALT"] = "Questions and answers on the operation of the module";
$MESS["KDA_IE_HELP_VIDEO_COMMON"] = "Basic instruction on imports";
$MESS["KDA_IE_HELP_VIDEO_ELEMENT_SECTIONS"] = "General instructions for importing sections (with the possibility of a binding element to multiple sections)";
$MESS["KDA_IE_HELP_VIDEO_SECTIONS_SEP_LINES"] = "Import files with sections on a separate line";
$MESS["KDA_IE_HELP_VIDEO_SECTIONS_WO_ELEMENTS"] = "Import sections without elements";
$MESS["KDA_IE_HELP_VIDEO_IMPORT_OFFERS"] = "Loading offers guide";
$MESS["KDA_IE_HELP_VIDEO_IMPORT_EXTERNAL_FIELDS"] = "Upload custom fields (which are not in the file)";
$MESS["KDA_IE_HELP_VIDEO_DIFFERENT_PRODUCERS"] = "Import price lists from different suppliers with goods deactivation or zeroing stock";
$MESS["KDA_IE_HELP_VIDEO_PRICE_AND_QUANTITIES"] = "Import prices and stock";
$MESS["KDA_IE_HELP_VIDEO_MULTIPLE_PROPERTIES"] = "Import multiple properties and descriptions of the properties";
$MESS["KDA_IE_HELP_VIDEO_IMAGES"] = "Import images";
$MESS["KDA_IE_HELP_VIDEO_PROPERTIES_SEPARATE"] = "Import properties from one cell of Excel-file";
$MESS["KDA_IE_HELP_VIDEO_EMAIL_FTP_CRON"] = "Imports from the E-mail-address and FTP by cron";
$MESS["KDA_IE_HELP_VIDEO_MASS_PROP_CREATE"] = "Mass creation of properties";
$MESS["KDA_IE_HELP_VIDEO_CLOUD_SERVISES"] = "Uploading files from cloud services";
$MESS["KDA_IE_HELP_VIDEO_PRICES_EXT"] = "Import prices in advanced management mode";
$MESS["KDA_IE_FAQ_PROLOG"] = "Attention! If you have not found the answer to your question, write to us at <a href=\"mailto:%s\">%s</a>.";
$MESS["KDA_IE_FAQ_QUEST_PICTURES"] = "How to import images?";
$MESS["KDA_IE_FAQ_ANS_PICTURES"] = "You can watch the video-manual import images <a href=\"https://www.youtube.com/watch?v=vkQQTlrJKN4\" target=\"_blank\">https://www.youtube.com/watch?v=vkQQTlrJKN4</a><br><br>
	Importing images is possible in 2 ways:
	<ul>
		<li>Loading an external link. For example: https://mdata.yandex.net/i?path=b0228152649_img_id6362633435892454257.jpeg</li>
		<li>Loading link from your site. For example: /upload/images/image.jpg. To this end, the picture pre-must be loaded into the folder /upload/images/ (or any other of your choice). Upload images to the site, you can either FTP, or via administrative panel site in the \"Content\" -> \"Site Structure\" -> \"Files and folders\". Download via the control panel, you can archive, followed by unpacking it.</li>
	</ul>";
$MESS["KDA_IE_FAQ_QUEST_SLOW_IMPORT"] = "Why import is very slow?";
$MESS["KDA_IE_FAQ_ANS_SLOW_IMPORT"] = "There are several reasons for the slow imports:
	<ul>
		<li>Because the file is imported images. If the download of pictures going to an external link or image is superimposed architectural sign, it can greatly slow down imports. Try disabling images import and check the speed of the import.</li>
		<li>With a large volume of goods imported to the site speed drops sharply if the identification of goods or trade offers made on properties (eg on the article). In this case, we recommend to transfer the properties to identify in the \"External code\". If you use multiple property to identify, you can transfer them to an external code in this form \"[Property 1] _ [Property 2]\". When identifying external code imports take place much faster, because \"The external code\" element is contained in the table, while the properties are contained in a separate table.</li>
		<li>Loading very large file in xls or xslx format. There may be a situation when in one step import module manages only profitat file and load the data is not enough time, because the import script is limited. In this case, we recommend you to convert the file into csv before importing. On reading the csv-file, requires a minimum of resources and a minimum of time.</li>
	</ul>";
$MESS["KDA_IE_FAQ_QUEST_MULTI_PICTURES"] = "How to upload multiple images in one field?";
$MESS["KDA_IE_FAQ_ANS_MULTI_PICTURES"] = "Import multiple images is possible only in the multiple properties of type \"File\". The file import images must be listed in a single cell with a separator multiple properties (like all other multiple properties). for example: \"/upload/images/image1.jpg; /upload/images/image2.jpg; /upload/images/image3.jpg\".";
$MESS["KDA_IE_FAQ_QUEST_MULTI_SECTIONS"] = "How to import a product in multiple sections?";
$MESS["KDA_IE_FAQ_ANS_MULTI_SECTIONS"] = "This issue is discussed in detail in the video instructions <a href=\"https://www.youtube.com/watch?v=jfgaadkLQGU\" target=\"_blank\">https://www.youtube.com/watch?v=jfgaadkLQGU</a>";
$MESS["KDA_IE_FAQ_QUEST_CRON"] = "How to set up the import on the cron?";
$MESS["KDA_IE_FAQ_ANS_CRON"] = "On the pages of the module in the upper right corner there is a green button \"Configure cron\". <br>
	This button opens a form in which you can select a profile and start time krona. <br>
	Also, there are the \"Path to php\", which depends on your hosting settings, but in most cases it will be the \"/usr/bin/php\".<br>
	After creating the task koruna will be recorded in the file \"/bitrix/crontab/crontab.cfg\" You will see a line like <br>
	<b><i>0 2 * * * /usr/bin/php -f /home/bitrix/yoursite.com/bitrix/php_interface/include/esol.importexportexcel/cron_frame.php 0 >/home/bitrix/yoursite.com/bitrix/php_interface/include/esol.importexportexcel/logs/0.txt</i></b><br><br>
	The command you will need to spend in the setting of Cron hosting control panel.
	The command consists of the following components:<br>
	<b><i>0 2 * * *</i></b> - time of start script<br>
	<b><i>/usr/bin/php</i></b> - path to php<br>
	<b><i>/home/bitrix/yoursite.com/bitrix/php_interface/include/esol.importexportexcel/cron_frame.php 0</i></b> - directly executable script. If you have hosting in nastroykh item type \"Run php script\", in line with the script will need to insert only that part of the line. Here, \"0\" at the end - this import profile identifier.<br>
	<b><i>>/home/bitrix/yoursite.com/bitrix/php_interface/include/esol.importexportexcel/logs/0.txt</i></b> - the path to the import logs. Without specifying this file, it will be difficult for you to further track the results of imports by cron.<br><br>				
	
	The \"Set automatically\" allows you to automatically create a job in the crown, but provided mainly for virtual Bitrix, because drugh on most web hosts will not work. Note that the \"Install automatically\" prezapishet all your tasks in the crown of the file \"/bitrix/crontab/crontab.cfg\".";
$MESS["KDA_IE_FAQ_QUEST_BOOL"] = "How to download a boolean value?";
$MESS["KDA_IE_FAQ_ANS_BOOL"] = "To transmit boolean fields using the following meanings:
	<ul>
		<li>\"1\", \"yes\", \"y\" - true</li>
		<li>\"0\", \"not\", \"n\" - false</li>
	</ul>
	Case does not matter. ";
?>