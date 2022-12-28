<?php

function anonUserFilter(&$data, $kID='ID', $kSteamID='SteamID', $kName='DisplayName', $kAvatar='AvatarURL', $kAnonymity='Anonymity') {
	if ($data[$kAnonymity]>=2) {
		$data[$kAvatar] = '';
		$data[$kSteamID] = null;
	}
	if ($data[$kAnonymity]==3) {
		$data[$kName] = 'Anonymous';
	}
	unset($data[$kAnonymity]);
}