<? 
defined('C5_EXECUTE') or die(_("Access Denied."));
$valt = Loader::helper('validation/token');
$th = Loader::helper('text');
	?>

<h1><span><?=$title?></span></h1>
<div class="ccm-dashboard-inner">

	<form method="post" id="ccm-log-search"  action="<?=$pageBase?>">
	<?=$form->text('keywords', $keywords)?>
	<?=$form->submit('search',t('Search') )?>
	<input type="button" onclick="if (confirm('<?=t("Are you sure you want to clear this log?")?>')) { location.href='<?=$this->url('/dashboard/reports/logs', 'clear', $this->controller->getTask(), $valt->generate())?>'}" value="<?=t('Clear Log')?>" />
	</form>

	<table border="0" cellspacing="1" cellpadding="0" class="grid-list">
	<tr>
		<td class="subheaderActive"><?=t('Date/Time')?></td>
		<td class="subheader"><?=t('Log Type')?></td>
		<td class="subheader"><?=t('Text')?></td>
	</tr>
	<? foreach($entries as $ent) { ?>
	<tr>
		<td valign="top" style="white-space: nowrap" class="active"><?=date('g:i:s', strtotime($ent->getTimestamp()))?><? if (date('m-d-y') != date('m-d-y', strtotime($ent->getTimestamp()))) { ?>
			<?=date('m/d/y', strtotime($ent->getTimestamp()))?>
		<? } ?></td>
		<td valign="top"><strong><?=$ent->getType()?></strong></td>
		<td style="width: 100%"><?=$th->makenice($ent->getText())?></td>
	</tr>
	<? } ?>
	</table>	
	
	<br/>
	
	<? if($paginator && strlen($paginator->getPages())>0){ ?>	 
		 <div  class="pagination">
			 <div class="pageLeft"><?=$paginator->getPrevious()?></div>
			 <div class="pageRight"><?=$paginator->getNext()?></div>
			 <?=$paginator->getPages()?>
		 </div>		
	<? } ?>		

</div>