# Mail to LINE Notify

This tool is used to forward the message received by email to LINE Notify.

## Installation

### The case of XSERVER

- Get your access token on [LINE Notify](https://notify-bot.line.me/login).
- Download [mail2linenotify.zip](https://github.com/ko31/mail2linenotify/releases/latest) and extract it.
- Upload `mail2linenotify` folder to any path on the server.
- If you want to set up a transfer to `sample@example.com`, you can edit `/home/[Your XSERVER account name]/example.com/mail/.filter`

```
if ( /^To: .*sample.*/:h )
{
	cc "| /usr/bin/php7.4 /path/to/mail2linenotify/mail2linenotify.php [Your LINE Access Token]"
}

# If you want to send forwarded emails as well, you can use the following instead of the above.
if ( /^To: .*sample.*/:h )
{
	cc "| /usr/bin/php7.4 /path/to/mail2linenotify/mail2linenotify.php [Your LINE Access Token] 1"
}
```

You can also set up the above from the XSERVER ServerPanel.
