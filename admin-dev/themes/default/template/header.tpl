{*
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
<!DOCTYPE html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7 lt-ie6 " lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8 ie7" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9 ie8" lang="en"> <![endif]-->
<!--[if gt IE 8]> <html lang="fr" class="no-js ie9" lang="en"> <![endif]-->
<html lang="{$iso}">
<head>
	<meta charset="utf-8">

	<meta name="viewport" content="width=device-width, initial-scale=0.75, maximum-scale=0.75, user-scalable=0">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<link rel="icon" type="image/x-icon" href="{$img_dir}favicon.ico" />
	<link rel="apple-touch-icon" href="{$img_dir}app_icon.png" />

	<meta name="robots" content="NOFOLLOW, NOINDEX">
	<title>{if $meta_title != ''}{$meta_title} • {/if}{$shop_name}</title>
	{if !isset($display_header_javascript) || $display_header_javascript}
	<script type="text/javascript">
		var help_class_name = '{$controller_name|@addcslashes:'\''}';
		var iso_user = '{$iso_user|@addcslashes:'\''}';
		var full_language_code = '{$full_language_code|@addcslashes:'\''}';
		var country_iso_code = '{$country_iso_code|@addcslashes:'\''}';
		var _PS_VERSION_ = '{$smarty.const._TB_VERSION_|@addcslashes:'\''}';
		var roundMode = {$round_mode|intval};
		var autorefresh_notifications = {$autorefresh_notifications|intval};
		var new_order_msg = '{l s='A new order has been placed on your shop.' js=1}';
		var order_number_msg = '{l s='Order number:' js=1} ';
		var total_msg = '{l s='Total:' js=1} ';
		var from_msg = '{l s='From:' js=1} ';
		var see_order_msg = '{l s='View this order' js=1}';
		var new_customer_msg = '{l s='A new customer registered on your shop.' js=1}';
		var customer_name_msg = '{l s='Customer name:' js=1} ';
		var new_msg = '{l s='A new message was posted on your shop.' js=1}';
		var see_msg = '{l s='Read this message' js=1}';
		var token = '{$token|addslashes}';
		var token_admin_orders = '{getAdminToken tab='AdminOrders'}';
		var token_admin_customers = '{getAdminToken tab='AdminCustomers'}';
		var token_admin_customer_threads = '{getAdminToken tab='AdminCustomerThreads'}';
		var currentIndex = '{$currentIndex|@addcslashes:'\''}';
		var employee_token = '{getAdminToken tab='AdminEmployees'}';
		var choose_language_translate = '{l s='Choose language' js=1}';
		var default_language = '{$default_language|intval}';
		var admin_modules_link = '{$link->getAdminLink("AdminModules")|addslashes}';
		var tab_modules_list = '{if isset($tab_modules_list) && $tab_modules_list}{$tab_modules_list|addslashes}{/if}';
		var update_success_msg = '{l s='Update successful' js=1}';
		var errorLogin = '{l s='thirty bees was unable to log in to Addons. Please check your credentials and your Internet connection.' js=1}';
		var search_product_msg = '{l s='Search for a product' js=1}';
	</script>
{/if}
{if isset($css_files)}
{foreach from=$css_files key=css_uri item=media}
	{if $css_uri == 'lteIE9'}
		<!--[if lte IE 9]>
		{foreach from=$css_files[$css_uri] key=css_uriie9 item=mediaie9}
		<link rel="stylesheet" href="{$css_uriie9|escape:'html':'UTF-8'}" type="text/css" media="{$mediaie9|escape:'html':'UTF-8'}" />
		{/foreach}
		<![endif]-->
	{else}
		<link rel="stylesheet" href="{$css_uri|escape:'html':'UTF-8'}" type="text/css" media="{$media|escape:'html':'UTF-8'}" />
	{/if}
{/foreach}
{/if}
	{if (isset($js_def) && count($js_def) || isset($js_files) && count($js_files))}
		{include file=$smarty.const._PS_ALL_THEMES_DIR_|cat:"javascript.tpl"}
	{/if}

	{if isset($displayBackOfficeHeader)}
		{$displayBackOfficeHeader}
	{/if}
	{if isset($brightness)}
	<!--
		// @todo: multishop color
		<style type="text/css">
			div#header_infos, div#header_infos a#header_shopname, div#header_infos a#header_logout, div#header_infos a#header_foaccess {ldelim}color:{$brightness}{rdelim}
		</style>
	-->
	{/if}
</head>

{if $display_header}
	<body class="ps_back-office{if $employee->bo_menu} page-sidebar{if $collapse_menu} page-sidebar-closed{/if}{else} page-topbar{/if} {$smarty.get.controller|escape|strtolower}">
	{* begin  HEADER *}
	<header id="header" class="bootstrap">
		<nav id="header_infos" role="navigation">
			<div class="navbar-header">
				<button id="header_nav_toggle" type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse-primary">
					<i class="icon-reorder"></i>
				</button>
				<a id="header_shopversion" href="{$default_tab_link|escape:'html':'UTF-8'}">
					<span id="shop_version">{$version}</span>
				</a>
				{* Shop *}
				{if isset($is_multishop) && $is_multishop && $shop_list && (isset($multishop_context) && $multishop_context & Shop::CONTEXT_GROUP || $multishop_context & Shop::CONTEXT_SHOP)}
					<ul id="header_shop">
						<li class="dropdown">
							{$shop_list}
						</li>
					</ul>
				{else}
					<a id="header_shopname" href="{$default_tab_link|escape:'html':'UTF-8'}">{$shop_name}</a>
				{/if}

				{* Notifications *}
				<ul id="header_notifs_icon_wrapper">
					{foreach $notificationTypes as $notificationType}
						<li id="{$notificationType.type}_notif" class="dropdown" data-type="{$notificationType.type}" data-last-id="0">
							<a href="javascript:void(0);" class="dropdown-toggle notifs" data-toggle="dropdown">
								<i class="{$notificationType.icon}"></i>
								<span id="{$notificationType.type}_notif_number_wrapper" class="notifs_badge hide">
									<span id="{$notificationType.type}_notif_value">0</span>
								</span>
							</a>
							<div class="dropdown-menu notifs_dropdown">
								<section id="{$notificationType.type}_notif_wrapper" class="notifs_panel">
									<div class="notifs_panel_header">
										<h3>{$notificationType.header}</h3>
									</div>
									<div id="{$notificationType.type}_notif_list" class="list_notif">
										<span class="no_notifs">{$notificationType.emptyMessage}</span>
									</div>
									<div class="notifs_panel_footer">
										<a href="{$notificationType.showAllLink}">{$notificationType.showAll}</a>
									</div>
								</section>
							</div>
						</li>
					{/foreach}
				</ul>

{if count($quick_access) >= 0}
				<ul id="header_quick">
					<li class="dropdown">
						<a href="javascript:void(0)" id="quick_select" class="dropdown-toggle" data-toggle="dropdown">{l s='Quick Access'} <i class="icon-caret-down"></i></a>
						<ul class="dropdown-menu">
							{foreach $quick_access as $quick}
								<li {if $link->matchQuickLink({$quick.link})}{assign "matchQuickLink" $quick.id_quick_access}class="active"{/if}>
									<a href="{$quick.link|escape:'html':'UTF-8'}"{if $quick.new_window} class="_blank"{/if}>
										{if isset($quick.icon)}
											<i class="icon-{$quick.icon} icon-fw"></i>
										{else}
											<i class="icon-chevron-right icon-fw"></i>
										{/if}
										{$quick.name}
									</a>
								</li>
							{/foreach}
							<li class="divider"></li>
							{if isset($matchQuickLink)}
								<li>
									<a href="javascript:void(0);" class="ajax-quick-link" data-method="remove" data-quicklink-id="{$matchQuickLink}">
										<i class="icon-minus-circle"></i>
										{l s='Remove from QuickAccess'}
									</a>
								</li>
							{/if}
							<li {if isset($matchQuickLink)}class="hide"{/if}>
								<a href="javascript:void(0);" class="ajax-quick-link" data-method="add">
									<i class="icon-plus-circle"></i>
									{l s='Add current page to QuickAccess'}
								</a>
							</li>
						</ul>
					</li>
				</ul>
				{$quick_access_current_link_name = " - "|explode:$quick_access_current_link_name}
				<script>
					$(function() {
						$('.ajax-quick-link').on('click', function(e){
							e.preventDefault();

							var method = $(this).data('method');

							if(method == 'add')
								var name = prompt('{l s='Please name this shortcut:' js=1}', '{$quick_access_current_link_name.0|escape:'javascript'|truncate:32}');

							if(method == 'add' && name || method == 'remove')
							{
								$.ajax({
									type: 'POST',
									headers: { "cache-control": "no-cache" },
									async: false,
									url: "{$link->getAdminLink('AdminQuickAccesses')}" + "&action=GetUrl" + "&rand={'1'|rand:200}" + "&ajax=1" + "&method=" + method + ( $(this).data('quicklink-id') ? "&id_quick_access=" + $(this).data('quicklink-id') : ""),
									data: {
										"url": "{$link->getQuickLink($smarty.server['REQUEST_URI'])}",
										"name": name,
										"icon": "{$quick_access_current_link_icon}"
									},
									dataType: "json",
									success: function(data) {
										var quicklink_list ='';
										$.each(data, function(index,value){
											if (typeof data[index]['name'] !== 'undefined')
												quicklink_list += '<li><a href="' + data[index]['link'] + '&token=' + data[index]['token'] + '"><i class="icon-chevron-right"></i> ' + data[index]['name'] + '</a></li>';
										});

										if (typeof data['has_errors'] !== 'undefined' && data['has_errors'])
											$.each(data, function(index, value)
											{
												if (typeof data[index] == 'string')
													$.growl.error({ title: "", message: data[index]});
											});
										else if (quicklink_list)
										{
											$("#header_quick ul.dropdown-menu").html(quicklink_list);
											showSuccessMessage(update_success_msg);
										}
									}
								});
							}
						});
					});
				</script>
{/if}
				<ul id="header_employee_box">
{if {$base_url}}
					<li>
						<a href="{if isset($base_url_tc)}{$base_url_tc|escape:'html':'UTF-8'}{else}{$base_url|escape:'html':'UTF-8'}{/if}" id="header_foaccess" class="_blank" title="{l s='View my shop'}">
							<span class="string-long">{l s='My shop'}</span>
							<span class="string-short">{l s='Shop'}</span>
						</a>
						{if isset($maintenance_mode) && $maintenance_mode == true}
							<span class="maintenance-mode">
								&mdash;
								<a href="{$link->getAdminLink('AdminMaintenance')|escape:'html':'UTF-8'}">
									<span class="label-tooltip"
										  data-toggle="tooltip"
										  data-placement="bottom"
										  data-html="true"
										  title="<p class='text-left text-nowrap'><strong>{l s='Your shop is in maintenance.'}</strong></p><p class='text-left'>{l s='Your visitors and customers cannot access your shop while in maintenance mode.%s To manage the maintenance settings, go to Preferences > Maintenance.' sprintf='<br />'}</p>">
										 {l s='Maintenance mode'}
									</span>
								</a>
							</span>
						{/if}
					</li>
{/if}
					<li id="employee_infos" class="dropdown">
						<a href="{$link->getAdminLink('AdminEmployees')|escape:'html':'UTF-8'}&amp;id_employee={$employee->id|intval}&amp;updateemployee" class="employee_name dropdown-toggle" data-toggle="dropdown">
							<span class="employee_avatar_small">
								{if isset($employee)}
								<img class="imgm img-thumbnail" alt="" src="{$employee->getImage()}" width="32" height="32" />
								{/if}
							</span>
							<span class="string-long">{$employee->firstname}&nbsp;{$employee->lastname}</span>
							<span class="string-short">{l s='Me'}</span>
							<i class="caret"></i>
						</a>
						<ul id="employee_links" class="dropdown-menu">
							<li>
								<span class="employee_avatar">
									<img class="imgm img-thumbnail" alt="" src="{$employee->getImage()}" width="96" height="96" />
								</span>
							</li>
							<li class="text-center text-nowrap">{$employee->firstname} {$employee->lastname}</li>
							<li class="divider"></li>
							<li><a href="{$link->getAdminLink('AdminEmployees')|escape:'html':'UTF-8'}&amp;id_employee={$employee->id|intval}&amp;updateemployee"><i class="icon-wrench"></i> {l s='My preferences'}</a></li>
							<li class="divider"></li>
							<li><a id="header_logout" href="{$login_link|escape:'html':'UTF-8'}&amp;logout"><i class="icon-signout"></i> {l s='Sign out'}</a></li>
						</ul>
					</li>
				</ul>

				<span id="ajax_running">
					<i class="icon-refresh icon-spin icon-fw"></i>
				</span>

	{if isset($displayBackOfficeTop)}{$displayBackOfficeTop}{/if}
			</div>
		</nav>{* end header_infos*}
	</header>

	<div id="main">
		{include file='nav.tpl'}

		<div id="content" class="{if !$bootstrap}nobootstrap{else}bootstrap{/if}">
			{if isset($page_header_toolbar)}{$page_header_toolbar}{/if}
			{if isset($modal_module_list)}{$modal_module_list}{/if}

{if $install_dir_exists}
			<div class="alert alert-warning">
				{l s='For security reasons, you must also delete the /install folder.'}
			</div>
{/if}

			{hook h='displayAdminAfterHeader'}


{* end display_header*}

{else}
	<body{if isset($lite_display) && $lite_display} class="ps_back-office display-modal"{/if}>
		<div id="main">
			<div id="content" class="{if !$bootstrap}nobootstrap{else}bootstrap{/if}">
{/if}
