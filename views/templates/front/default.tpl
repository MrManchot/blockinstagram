<h1 class="page-heading-text">{l s='Instagram' mod='blockinstagram'}</h1>

{if isset($instagram_pics) && $instagram_pics|count > 0}
	<div class="row">
		{foreach $instagram_pics as $pic}
			<div class="col-xs-6 col-sm-3" style="margin-bottom:30px;">
				<a href="{$pic.link}" title="{$pic.caption|escape:'html':'UTF-8'}" target="_blank" rel="nofollow">
					<img src="{$pic.image}" class="img-responsive" />
				</a>
			</div>
		{/foreach}
	</div>
{/if}

<div class="text-center">
	<a href="https://www.instagram.com/{$username}/" class="btn btn-primary" target="_blank">
		{l s='See all our Instagram pics' mod='blockinstagram'}
	</a>
</div>