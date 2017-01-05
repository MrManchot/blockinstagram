<h1 class="page-heading-text">{$instagram_user.full_name} {l s='Instagram' mod='blockinstagram'}</h1>

<div class="row">
	<div class="col-xs-3">
		<img src="{$instagram_user.profile_pic}" class="img-responsive"/>
	</div>
	<div class="col-xs-9">
		<p>
			<strong>{$instagram_user.posts}</strong> {l s='post' mod='blockinstagram'} /
			<strong>{$instagram_user.followed_by}</strong> {l s='followed by' mod='blockinstagram'} /
			<strong>{$instagram_user.follows}</strong> {l s='follows' mod='blockinstagram'}
		</p>
		<p>{$instagram_user.biography}</p>
	</div>
</div>

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
	<a href="https://www.instagram.com/{$instagram_user.username}/" class="btn btn-primary" target="_blank">
		{l s='See all our Instagram pics' mod='blockinstagram'}
	</a>
</div>