# AuthRemoteUser
[![Packagist download count](https://poser.pugx.org/mediawiki/auth-remote-user/d/total.png)](https://packagist.org/packages/mediawiki/auth-remote-user)

This [MediaWiki] extension aims to provide authentication via the webserver's
REMOTE_AUTH (i.e. with kerberos) functionality.

It relies on the extension [PluggableAuth].

After you installed the extension, you have to configure your webserver for remote
authentication to match on the special page _AuthRemoteUser_.

## Apache configuration example for Kerberos 
```apacheconf
<LocationMatch ".*/index\.php">
    <If "%{QUERY_STRING} =~ /title=[^:]+:AuthRemoteUser/">
        SSLRequireSSL
        AuthType Kerberos
        AuthName "Kerberos Login"
        KrbMethodNegotiate On
        KrbMethodK5Passwd Off
        KrbAuthoritative on
        KrbAuthRealms <your krb realms>
        KrbVerifyKDC on
        Krb5KeyTab /etc/keytabs/krb5.keytab.HTTP
        require valid-user
    </If>
</LocationMatch>
```
Note: even, if you are using Short URL schema, you have to match against
*index.php?title=*, because the authentication special page is accessed
internally with the original linking schema.

## Contact
For bug reports and feature requests please see, if it is already reported on
the list of [open bugs]. If not, [report it][report bugs].

For general questions, comments, or suggestions you might use the [talk page
on MediaWiki.org][mw-talk]. For direct contact with the author
please use the [Email functionality on MediaWiki.org.][mw-mail]


[MediaWiki]: https://www.mediawiki.org/
[PluggableAuth]: https://www.mediawiki.org/wiki/Extension:PluggableAuth
[open bugs]: https://github.com/oetterer/AuthRemoteUser/issues
[report bugs]: https://github.com/oetterer/AuthRemoteUser/issues/new
[mw-talk]: https://www.mediawiki.org/wiki/Extension_talk:AuthRemoteUser
[mw-mail]: https://www.mediawiki.org/wiki/Special:EmailUser/oetterer
