+++
title="Automatically removing old images from DigitalOcean Container Registry"
date=2021-06-12T12:12:12
tags=["infrastructure", "docker", "digitalocean"]
type="post"
+++

### Leveraging DO client and jq for fun and profit

DigitalOcean's [private registry](https://www.digitalocean.com/products/container-registry/) is pretty nice solution to have a private registry to store docker images to deploy when needed.

We use it at work every time we deploy a new version of any of our web applications to staging, and we just retag when it's time to spin a new one in production.

But recently I found myself having to delete a few thousands unused images to free up space on the repository and avoid paying storage costs for images that we wouldn't be resonably use.

To begin with I had to design some sort of "retention policy". What should I and what to delete?

- Older than 3 days? Safe to delete
- But keep at least 5 images. In case there is a longer stretch without deployments for whatever reason, we don't want to remove the currently deployed image.

With our requirements ready, let's see what `doctl` can do for us.

With `doctl registry repository list-tags my-repository` I can get all the images. They are listed chrornologically, newer first. No more sorting or filtering options available. At least  we cn use  `-o json`option  to get the results as a JSON.

E.g. something like this:

```json
[
   {
      "registry_name":"megacorp",
      "repository":"web-api-http-prod",
      "tag":"91c3d12",
      "manifest_digest":"sha256:14dc6e2a51bdcbc2ad4d3ec2fefae5e6b7c1b95300e7ffec5dd76cfa4d93a916",
      "compressed_size_bytes":66246685,
      "size_bytes":105309944,
      "updated_at":"2021-06-09T08:28:02Z"
   },
   {
      "registry_name":"megacorp",
      "repository":"web-api-http-prod",
      "tag":"019a183",
      "manifest_digest":"sha256:1edef67c48285249ea35cf1741c731297847ac00d48f69cedc9c14e1e8baf70f",
      "compressed_size_bytes":30843020,
      "size_bytes":70232312,
      "updated_at":"2021-06-09T07:50:23Z"
   }
]
```

(A few hundreds of these).

We can use `jq` to work with that. Filtering according to our requirements it's pretty straight-forward:

```bash
jq ".[5:] | .[] | select ( .updated_at | fromdateiso8601 < $(date -v-3d +%s)) | .tag"
```

- `.[5:]` to get first 5 results
- `.[]` to iterate the results
- `select ( .updated_at | fromdateiso8601 < ...`to filter the results by `.updated_at`, converting the dates to a timestamp
- `$(date -v-3d +%s))`to get a timestamp from 3 days ago to compare with.
- `.tag` because that's only part we are interested on, the tags we'll want to delete

We are going to use `doctl registry repository delete-tag` to get rid of the unneeded tags. Since there is no complementary `-i`option to process JSON input, we'll need to convert output from above in something more appropriate as a command line argument.

The final version ends up being like this:

```bash
doctl registry repository delete-tag web-api-http-prod $(doctl registry repository list-tags web-api-http-prod --output json | jq ".[5:] | .[] | select ( .updated_at | fromdateiso8601 < $(date -v-5d +%s)) | .tag " -r | tr '\n' ' ') --force
```
We'll add the `-r` for "raw" output (no quotes), and pipe the result through `tr` to replace newlines with spaces, and use the whole thing as arguments for the `registry repository delete-tag` command, adding the `--force` option so it runs without asking for confirmation.

For convenience sake, we wrap everything into a single shells script that takes a couple parameters and we can use this safely in our daily cron for each of the application repositories:

```bash
#!/bin/bash

min_to_keep=${min_to_keep:-5}
delete_days_old=${delete_days_old:-4}
verbose=no
repository=''

while [ $# -gt 0 ]; do

  if [[ $1 == *"--"* ]]; then
    param="${1/--/}"
    declare "$param"="$2"
  fi

  shift
done

[[ -z "$repository" ]] && {
  echo "Error: Need to define '--repository'"
  exit 1
}

date_boundary=$(date -v-"$delete_days_old"d +%s)
deletable_tags=$(doctl registry repository list-tags "$repository" --output json | jq ".[5:] | .[] | select ( .updated_at | fromdateiso8601 < $date_boundary) | .tag " -r | tr '\n' ' ')

[[ -z "$deletable_tags" ]] && {
  [[ "$verbose" == "yes" ]] && echo "Nothing to delete"
  exit 0
}

[[ "$verbose" == "yes" ]] && {
  echo "We are going to DELETE images that are more than $delete_days_old days"
  echo "We are going to KEEP at least the newer $min_to_keep images"

  echo "Deleteable tags: $deletable_tags"
}
doctl registry repository delete-tag "$repository" $deletable_tags --force
```
