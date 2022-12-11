+++
title="Quick setup steps a MacOs PHP Development box"
tags=["macos", "PHP"]
date=2022-12-11T15:15:01
type="post"
draft=false
+++

Reinstalling from scratch happily does not happen often, but ocassionally it does happen.

Attempting to make the process less painful, some quick notes to streamline installing most of the usual tools.

These are highly personal choices, just a starting point to copy-paste and begin the process. Choose your weapons wisely.

### 1st: Old settings

If possible, in the old machine copy some files often forgotten:

- [Firefox Profile](https://support.mozilla.org/en-US/kb/profiles-where-firefox-stores-user-data)
- Export and copy personal certificates from FF (these are NOT included in the above step)
- Copy personal SSH keys/config from `~/.ssh/`

### Unbrewed

Thanks to Homebrew, most things can be installed comfortably from the CLI.

My only exception so far is a clipboard manager, for that I install Paste (from the App Store)[https://apps.apple.com/us/app/paste-clipboard-manager/id967805235].

And logically, [Homebrew](https://brew.sh/) should be installed before anything else.

```shell
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
```

### iTerm 2

There are some newer terminal apps, but I haven't still found reason to move away from [iTerm](https://iterm2.com/).

```shell
brew install --cask \
  iterm2
```

### Powerlevel 10k

Having a prettier and more functional shell does wonders for my sanity. In comes (Powerlevel10k)[https://github.com/romkatv/powerlevel10k].

```shell
brew install romkatv/powerlevel10k/powerlevel10k
```

Powerlevel10k has its own installation wizard to wrap up after this step.

### Some general command line tools

```shell
brew install \
  httpie \
  bat \
  colordiff \
  ncdu \
  mas \
  exa \
  fzf \
  jq \
```

[fzf](https://github.com/junegunn/fzf) also includes its own post-install wizard.

### Some dev tools

Node, Yarn, a couple of PHP versions and PHP switcher to use them all.

GitHub, AWS, and Digital Ocean command line tools.

```shell
brew isntall gh doctl awscli node yarn
brew install brew-php-switcher php@8.1 php@8.0 php@7.4
```

### Desktop Casks, dev related

* [Bitwarden](https://bitwarden.com). Passwords need managing. 
* [Discord](https://discord.com). Or whatever team chat software you are cursed with
* [Simplenote](https://simplenote.com/). Cross-platform note-taking FTW
* [Docker](https://www.docker.com/). Put those apps in containes.
* [PhpStorm](https://www.jetbrains.com/phpstorm/). What would we do without thee.

```shell
brew install --cask \
    bitwarden \  
    discord \ 
    disk-inventory-x \ 
    phpstorm \
    cleanshot \
    rectangle \
    docker \
    simplenote 
```

### Desktop Casks, general.
* [Signal](https://signal.org/en/). Communication poison of choice.
* Whatsapp. Tried to get rid of it, but couldn't so far.
* [iina](https://iina.io/). Video player.
* [VLC](https://www.videolan.org/vlc/). Video everything.
* [Disk-Inventory-X](https://www.derlien.com/). These laptop hardrives aren't as big as they used to be.
* [Cleanshot](https://cleanshot.com/). A very nice screen capture tool.
* [Rectangle](https://rectangleapp.com/). Manage those windows around.
* [Mimestream](https://mimestream.com/). Gmail without Gmail.
* [Devolo Cockpit](https://www.devolo.es/devolo-cockpit). Basement router signal does not reach my office without some help. 

```shell
brew install --cask \
    signal \
    whatsapp \
    iina \
    vlc \
    mimestream \
    cleanshot \
    rectangle \
    disk-inventory-x \
    devolo-cockpit \
    airbuddy
```
