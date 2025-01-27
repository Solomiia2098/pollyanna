#!/usr/bin/perl -T

use strict;
use warnings;
use 5.010;
use utf8;

#todo this is the first version, and is sub-optimal

sub GetThreadListingDialog { # $fileHash ; get dialog listing all thread items
#todo refactor this to be more efficient
	my $fileHash = shift;
	chomp $fileHash;

	if ($fileHash = IsItem($fileHash)) {
		# ok
	} else {
		WriteLog('GetThreadListingDialog: warning: $fileHash failed sanity check; caller = ' . join(',', caller));
		return '';
	}

	WriteLog("GetThreadListingDialog($fileHash)");

	my $topLevelItem = DBGetTopLevelItem($fileHash);
	#if ($topLevelItem ne $file{'file_hash'}) {
	my $currentItem = $fileHash;

	WriteLog('GetThreadListingDialog: $topLevelItem = ' . $topLevelItem . '; $currentItem = ' . $currentItem);

	my @itemsInThreadListing;

	my $threadListing = GetThreadListing($topLevelItem, $currentItem, 0, \@itemsInThreadListing);

	if ($threadListing) {
		# sub GetThreadDialog {
		my $threadListingDialog .= '<span class=advanced>' . GetDialogX($threadListing, 'Thread', 'item_title,add_timestamp') . '</span>';
		return $threadListingDialog;
	} else {
		WriteLog('GetThreadListingDialog: warning: $threadListing is FALSE');
	}

	return '';
} # GetThreadListingDialog()

sub GetThreadListing { # $topLevel, $selectedItem, $indentLevel, $itemsListReference
# sub GetThreadDialog {

	WriteLog('GetThreadListing()');

	my $topLevel = shift; #todo sanity
	my $selectedItem = shift || '';
	my $indentLevel = shift || 0;
	my $itemsListReference = shift; # reference to array of all items included in thread listing

	WriteLog('GetThreadListing: $topLevel = ' . $topLevel);

	my @itemInfo = SqliteQueryHashRef("SELECT * FROM item_flat WHERE file_hash = '$topLevel' LIMIT 1");
	#todo config/template/query/...
	shift @itemInfo; # headers

	if (@itemInfo) {
		#most basic sanity check passed
	} else {
		# @itemInfo is false
		WriteLog('GetThreadListing: warning: @itemInfo is FALSE; $topLevel = ' . $topLevel . '; caller = ' . join(',', caller));
		return '';
	}

	my %topLevelItem = %{$itemInfo[0]}; # top level item, parent of all other items in this thread, also the first row

	WriteLog('GetThreadListing: $topLevelItem{file_hash} = ' . $topLevelItem{'file_hash'});

	if ($itemsListReference) {
		push @{$itemsListReference}, $topLevel;
	}

	my $itemTitle = $topLevelItem{'item_title'};
	my $itemTime = $topLevelItem{'add_timestamp'};

	my $listing = '';

	my @itemChildren;
	if (GetConfig('setting/html/item_page/include_notext_in_thread_list')) {
		@itemChildren = SqliteQueryHashRef("SELECT item_hash FROM item_parent WHERE parent_hash = '$topLevel'");
	} else {
		my @itemChildren = SqliteQueryHashRef("
			SELECT item_hash
			FROM item_parent
			WHERE
				parent_hash = '$topLevel' AND
				item_hash NOT IN (SELECT file_hash FROM item_flat WHERE labels_list LIKE '%,notext,%')
		");
	}
	shift @itemChildren;

	# if (@itemChildren) {
	# 	$listing .= '<details open><summary>';
	# }
	#
	if ($topLevel eq $selectedItem) {
		$listing .= '<tr bgcolor="' . GetThemeColor('highlight_alert') . '">';
	} else {
		$listing .= '<tr>';
	}
	$listing .= '<td>';

	$listing .= '&nbsp; &nbsp; ' x $indentLevel; # x operator means repeat

	$listing .= GetItemHtmlLink($topLevel, $itemTitle);

	if ($topLevel eq $selectedItem) {
		$listing .= ' (selected)';
	} else {
		$listing .= '';
	}

	$listing .= '</td>';

	$listing .= '<td>';
	if ($itemTime) {
		#$listing .= '; ';
		$listing .= GetTimestampWidget($itemTime);
	}
	#$listing .= "<br>";
	$listing .= '</td>';
	$listing .= '</tr>';

	#
	# if (@itemChildren) {
	# 	$listing .= '</summary>';
	# }

	#my %queryParams;
	#$queryParams{'where_clause'} = "WHERE file_hash != '$topLevel' AND file_hash IN (SELECT file_hash FROM item_parent WHERE parent_hash = '$topLevel')"; #todo sanity
	#my @itemChildren = DBGetItemList(\%queryParams);
	if (@itemChildren) {
		for my $refChild (@itemChildren) {
			my %itemChild = %{$refChild};
			my $itemHash = $itemChild{'item_hash'};
			if ($itemsListReference) {
				push @{$itemsListReference}, $itemHash;
			}

			# recursion
			$listing .= GetThreadListing($itemHash, $selectedItem, $indentLevel + 1, $itemsListReference);
			#$listing .= $itemHash;
		}
		# $listing .= '</details>';
	} else {
		if ($indentLevel == 0) {
			$listing = '';
		}
	}

	return $listing;
} # GetThreadListing()

1;
