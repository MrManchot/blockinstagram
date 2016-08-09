{if isset($instagram_pics) && $instagram_pics|count > 0}
<div id="blockinstagram" class="tab-content">
	<div class="page-heading">
		{l s='Follow-us on' mod='blockinstagram'}
		<a href="https://www.instagram.com/{$username}/" target="_blank" rel="nofollow">Instagram</a>
	</div>
	<div class="row">
		{foreach $instagram_pics as $pic}
			<div class="col-xs-6 col-sm-3" style="margin-bottom:30px;">
				<a href="{$pic.link}" title="{$pic.caption|escape:'html':'UTF-8'}" target="_blank" rel="nofollow">
					<img src="{$pic.image}" class="img-responsive" height="{$size}" width="{$size}" />
				</a>
			</div>
		{/foreach}
	</div>
</div>
{/if}