+++
title = "Break in case of emergency: getting out of \"out of disk space\" situations"
date = 2021-10-09T08:47:11+01:00
draft = false
tags = ['linux', 'troubleshooting', 'infrastructure']
type = "post"
aliases = [
"/break-in-case-of-emergency-getting-out-of-out-of-disk-space-situations/"
]
+++

This happened to me once or twice: you get an alert that something is starting to go wrong with a server, but by the time someone can finally log in to the machine you discover a runaway process has filled the disk completely.

Not only that, but the runaway process is still trying to claim more space. The machine with 0 bytes available is close to non-responsive, and you start deleting temp files and old logs with the hopes of recovering enough space to be able to work on the machine for enough consecutive minutes to fix the issue.

It's not fun.

Since then, I usually keep a 512Mb-1Gb file of empty space laying around that I can just delete in case of need:

{{< cmd >}}
head -c 1G </dev/urandom > space.tmp
{{< /cmd >}}

Now in case of emergencies I can simply delete `space.tmp` and gain enough working space to be able to fix things without having to resort to desperate measures.

Of course, installing ahead of time something like  [ncdu](https://dev.yorhel.nl/ncdu) is always a wonderful idea. 

![ncdu Output](/images/ncdu_output.png)

But even then, the "delete in case of emergency" approach has made my life easier more than once. And using a spare gigabyte for this is very cheap.  
