{if isset($instagram_pics) && $instagram_pics|count > 0}
<div id="blockinstagram" class="block_home">
	<div class="page-heading">
		{l s='Follow-us on' mod='blockinstagram'}
		<a href="https://www.instagram.com/{$username}/" target="_blank" rel="nofollow">Instagram</a>
	</div>
	<div class="row">
		{foreach $instagram_pics as $pic}
			<div class="col-xs-4 col-sm-3 col-md-2">
				<a href="{$pic.link}embed" class="fancyframe" data-toggle="tooltip" title="{$pic.caption|escape:'html':'UTF-8'}" rel="nofollow">
					<img data-original="{$pic.image}" class="img-responsive lazy" height="{$size}" width="{$size}" />
				</a>
			</div>
		{/foreach}
	</div>
</div>
{/if}