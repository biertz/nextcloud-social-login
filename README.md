# Social login (with OICD tokens)

This app is fork of zorn-v's [social login app](https://github.com/zorn-v/nextcloud-social-login) (their instructions apply). It adds an option to the custom OICD provider which, if toggled, saves access and refresh tokens for authenticated users in the local database.

This allows your Nextcloud to interact with other of your services on behalf of your users, provided those services use the same single-sign on. For instance, if you use a Keycloak, you can use the saved access token to get a Requesting Party Token (RPT) to send files on behalf of your users to other systems of yours.

As these changes include major code splits and cron logics, they are out-of-scope of the original app, so we maintain them separately.

## NB
### appid
We changed the appid to *socialtokens*. 

This avoids accidental updates of our fork via Nextclouds appstore. Please be aware, that this does NOT mean that you can install sociallogin and our fork together! They still use the same folder name and namespace.
