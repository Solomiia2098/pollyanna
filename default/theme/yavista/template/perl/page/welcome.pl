#!/usr/bin/perl -T

use strict;
use warnings;
use 5.010;

sub MakePollyannaPages {
	#hack
	my $pollyannaPageTemplate = GetTemplate('html/page/pollyanna/index.html');
	if ($pollyannaPageTemplate) {
		PutFile(GetDir('html') . '/pollyanna/index.html', $pollyannaPageTemplate);

		my $pollyannaScreenshotPageTemplate = GetTemplate('html/page/pollyanna/screenshot.html');

		if ($pollyannaScreenshotPageTemplate) {
			PutFile(GetDir('html') . '/pollyanna/screenshot.html', GetTemplate('html/page/pollyanna/screenshot.html'));
		}

		my @filesToCopy = qw(
			doc/screenshot/16964/netscape3.png
			doc/screenshot/16964/lynx.png
			doc/screenshot/16964/firefox.png
			doc/screenshot/16964/brave.png
			doc/screenshot/16964/ie6.png
			doc/screenshot/16964/floorp.png
			doc/screenshot/16964/safari5.png
			doc/screenshot/16964/seamonkey.png
			doc/screenshot/16964/tor.png
			doc/screenshot/16964/opera12-presto.png
			doc/screenshot/16964/msedge.png
			doc/screenshot/16964/netscape4.png
			doc/screenshot/16964/chrome.png
			doc/screenshot/16964/palemoon.png
			doc/screenshot/16964/w3m.png
			doc/screenshot/16964/netscape9.png
			doc/screenshot/16964/links2.png
			doc/screenshot/16964/links.png
			doc/screenshot/16964/yandex.png
			doc/screenshot/16964/vivaldi.png
			doc/screenshot/pollyanna/github.png
			doc/screenshot/pollyanna/screenshot.png
			doc/screenshot/pollyanna/whitepaper.png
			doc/whitepaper-pollyanna.pdf
		);

		for my $file (@filesToCopy) {
			my $targetName = TrimPathLeaveExtension($file);
			copy($file, GetDir('html') . '/pollyanna/' . $targetName);
		}
	}
}

sub GetWelcomePage {
	MakePollyannaPages(); #hack

	my $html =
		GetPageHeader('welcome') .
		GetDialogX(GetTemplate('html/page/welcome.template'), 'Welcome') .
		# #GetDialogX(GetTemplate('html/page/content.template'), 'Please Share') .
		# #GetDialogX(GetTemplate('html/page/rules.template'), 'Ground Rules') .
		# #GetDialogX(GetTemplate('html/page/privacy.template'), 'Privacy') .
		# #GetDialogX(GetTemplate('html/form/emergency.template'), 'Emergency Contact Form') .
		# GetWriteDialog('Contribute', 'Write something here, please:') .
		GetDialogX(GetTemplate('html/form/guest.template'), 'Guest') .
		GetDialogX(GetTemplate('html/form/enter.template'), 'Membership') .
		GetPageFooter('welcome')
	;

	if (GetConfig('admin/js/enable')) {
		my @js = qw(utils profile write puzzle clock easyreg settings);

		if (GetConfig('admin/php/enable')) {
			push @js, 'write_php';
		}

		if (GetConfig('setting/html/reply_cart')) {
			push @js, 'reply_cart';
		}

		$html = InjectJs($html, @js);

		$html = AddAttributeToTag($html, 'input id=member', 'onclick', "if (window.EasyMember) { this.value = 'Meditate...'; setTimeout('EasyMember()', 50); return false; }");
	}

	return $html;
} # GetWelcomePage()

1;