<?php
/*
 * services_rfc2136.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2013 BSD Perimeter
 * Copyright (c) 2013-2016 Electric Sheep Fencing
 * Copyright (c) 2014-2025 Rubicon Communications, LLC (Netgate)
 * All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

##|+PRIV
##|*IDENT=page-services-rfc2136clients
##|*NAME=Services: RFC 2136 Clients
##|*DESCR=Allow access to the 'Services: RFC 2136 Clients' page.
##|*MATCH=services_rfc2136.php*
##|-PRIV

require_once("guiconfig.inc");

if ($_POST['act'] == "del") {
	config_del_path("dnsupdates/dnsupdate/{$_POST['id']}");

	write_config("RFC 2136 client deleted");

	header("Location: services_rfc2136.php");
	exit;
} else if ($_POST['act'] == "toggle") {
	if (config_get_path("dnsupdates/dnsupdate/{$_POST['id']}")) {
		if (config_path_enabled("dnsupdates/dnsupdate/{$_POST['id']}")) {
			config_del_path("dnsupdates/dnsupdate/{$_POST['id']}/enable");
			$action = "disabled";
		} else {
			config_set_path("dnsupdates/dnsupdate/{$_POST['id']}/enable", true);
			$action = "enabled";
		}
		write_config("RFC 2136 {$action}");

		header("Location: services_rfc2136.php");
		exit;
	}
}

$pgtitle = array(gettext("Services"), gettext("Dynamic DNS"), gettext("RFC 2136 Clients"));
$pglinks = array("", "services_dyndns.php", "@self");
include("head.inc");

$tab_array = array();
$tab_array[] = array(gettext("Dynamic DNS Clients"), false, "services_dyndns.php");
$tab_array[] = array(gettext("RFC 2136 Clients"), true, "services_rfc2136.php");
$tab_array[] = array(gettext("Check IP Services"), false, "services_checkip.php");
display_top_tabs($tab_array);

if ($input_errors) {
	print_input_errors($input_errors);
}
?>

<form action="services_rfc2136.php" method="post" name="iform" id="iform">
	<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext('RFC2136 Clients')?></h2></div>
		<div class="panel-body">
			<div class="table-responsive">
				<table class="table table-striped table-hover table-condensed table-rowdblclickedit">
					<thead>
						<tr>
							<th><?=gettext("Status")?></th>
							<th><?=gettext("Interface")?></th>
							<th><?=gettext("Server")?></th>
							<th><?=gettext("Hostname")?></th>
							<th><?=gettext("Cached IP")?></th>
							<th><?=gettext("Description")?></th>
							<th><?=gettext("Actions")?></th>
						</tr>
					</thead>
					<tbody>
<?php


$iflist = get_configured_interface_with_descr();
$groupslist = return_gateway_groups_array();

$i = 0;
foreach (config_get_path('dnsupdates/dnsupdate', []) as $rfc2136):
	if (!is_array($rfc2136) || empty($rfc2136)) {
		continue;
	}
	$filename = "{$g['conf_path']}/dyndns_{$rfc2136['interface']}_rfc2136_" . escapeshellarg($rfc2136['host']) . "_{$rfc2136['server']}.cache";
	$filename_v6 = "{$g['conf_path']}/dyndns_{$rfc2136['interface']}_rfc2136_" . escapeshellarg($rfc2136['host']) . "_{$rfc2136['server']}_v6.cache";
	$if = get_failover_interface($rfc2136['interface']);

	if (file_exists($filename)) {
		if (isset($rfc2136['usepublicip'])) {
			$ipaddr = dyndnsCheckIP($if, null, AF_INET);
		} else {
			$ipaddr = get_interface_ip($if);
		}

		$cached_ip_s = explode("|", file_get_contents($filename));
		$cached_ip = $cached_ip_s[0];

		if ($ipaddr == $cached_ip) {
			$icon_class = "fa-solid fa-check-circle";
			$text_class = "text-success";
			$icon_title = "Updated";
		} else {
			$icon_class = "fa-solid fa-times-circle";
			$text_class = "text-danger";
			$icon_title = "Failed";
		}
	} elseif (file_exists($filename_v6)) {
		$ipv6addr = get_interface_ipv6($if);
		$cached_ipv6_s = explode("|", file_get_contents($filename_v6));
		$cached_ipv6 = $cached_ipv6_s[0];

		if ($ipv6addr == $cached_ipv6) {
			$icon_class = "fa-solid fa-check-circle";
			$text_class = "text-success";
			$icon_title = "Updated";
		} else {
			$icon_class = "fa-solid fa-times-circle";
			$text_class = "text-danger";
			$icon_title = "Failed";
		}
	}
?>
						<tr<?=(isset($rfc2136['enable']) ? '' : ' class="disabled"')?>>
							<td>
							<i class="<?=$icon_class?> <?=$text_class?>" title="<?=$icon_title?>"></i>
							</td>
							<td>
<?php
	foreach ($iflist as $ifname => $ifdesc) {
		if ($rfc2136['interface'] == $ifname) {
			print($ifdesc);
			break;
		}
	}
	foreach ($groupslist as $ifname => $group) {
		if ($rfc2136['interface'] == $ifname) {
			print($ifname);
			break;
		}
	}
?>
							</td>
							<td>
								<?=htmlspecialchars($rfc2136['server'])?>
							</td>
							<td>
								<?=htmlspecialchars($rfc2136['host'])?>
							</td>
							<td>
<?php
	if (file_exists($filename)) {
		print('IPv4: ');
		print("<span class='{$text_class}'>");
		print(htmlspecialchars($cached_ip));
		print('</span>');
	} else {
		print('IPv4: N/A');
	}

	print('<br />');

	if (file_exists($filename_v6)) {
		print('IPv6: ');
		$ipaddr = get_interface_ipv6($if);
		$cached_ip_s = explode("|", file_get_contents($filename_v6));
		$cached_ip = $cached_ip_s[0];

		if ($ipaddr != $cached_ip) {
			print('<span class="text-danger">');
		} else {
			print('<span class="text-success">');
		}

		print(htmlspecialchars($cached_ip));
		print('</span>');
	} else {
		print('IPv6: N/A');
	}

?>
					</td>
					<td>
						<?=htmlspecialchars($rfc2136['descr'])?>
					</td>
					<td>
						<a class="fa-solid fa-pencil" title="<?=gettext('Edit client')?>" href="services_rfc2136_edit.php?id=<?=$i?>"></a>
					<?php if (isset($rfc2136['enable'])) {
					?>
						<a	class="fa-solid fa-ban" title="<?=gettext('Disable client')?>" href="?act=toggle&amp;id=<?=$i?>" usepost></a>
					<?php } else {
					?>
						<a class="fa-regular fa-square-check" title="<?=gettext('Enable client')?>" href="?act=toggle&amp;id=<?=$i?>" usepost></a>
					<?php }
					?>
						<a class="fa-regular fa-clone" title="<?=gettext('Copy client')?>" href="services_rfc2136_edit.php?dup=<?=$i?>"></a>
						<a class="fa-solid fa-trash-can" title="<?=gettext('Delete client')?>" href="services_rfc2136.php?act=del&amp;id=<?=$i?>" usepost></a>
					</td>
					</tr>
<?php
	$i++;
endforeach; ?>

					</tbody>
				</table>
			</div>
		</div>
	</div>
</form>

<nav class="action-buttons">
	<a href="services_rfc2136_edit.php" class="btn btn-sm btn-success btn-sm">
		<i class="fa-solid fa-plus icon-embed-btn"></i>
		<?=gettext('Add')?>
	</a>
</nav>

<div>
	<?=sprintf(gettext('Entries with a %3$s status column icon and IP address appearing in %1$sgreen%2$s are up to date with Dynamic DNS provider. '), '<span class="text-success">', '</span>', '<i class="fa-solid fa-check-circle text-success"></i>')?>
	<?=gettext('An update can be forced on the edit page for an entry.')?>
</div>

<?php
include("foot.inc");
