# Easy Digital Downloads - Software GitHub Sync
Sync GitHub releases with EDD downloads that use the [Software Licensing](https://easydigitaldownloads.com/downloads/software-licensing)
extension.

### Install

Install the plugin and edit your desired Download. In the **Software GitHub Sync** metabox that appears,
enable the sync and save the download. It is recommended you also generate a secret.

To setup the webhook on GitHub:

1. On your GitHub repository navigate to: **Settings > Webhooks & services > Add webhook**
1. Enter the generated Payload URL (found in the **Software GitHub Sync** metabox)
1. Enter the secret (if you have created one / found in the **Software GitHub Sync** metabox)
1. Select **Let me select individual events** and untick "Push" and tick "Release"
1. Add the webhook and check the ping is ok

### Usage

When creating a release in GitHub attach your ZIP using the "Attach binaries" upload box. The EDD download will
automatically be updated with this new file and the current version number will also be updated with the "Tag version" 
from the release.
