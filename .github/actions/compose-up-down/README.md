# Composer Up/Down GitHub Action
Provides a GitHub action that will run `docker compose up` with some supported arguments and at 
the end of the workflow shut down all the containers (regardless of the job outcome).

This action should be used instead of manually calling `docker compose up` to prevent our 
self-hosted GitHub runners from running into the cgroup limit for leftover files related to Docker.
You should only manually call `docker compose up` in case you also run `docker compose down` at 
some point (e.g. because you're starting many profiles in parallel in a bash loop).

The error you may see for jobs not following this practice may look something like
> Error response from daemon: failed to create task for container: failed to create shim task: OCI runtime create failed: runc create failed: unable to start container process: unable to apply cgroup configuration: mkdir /sys/fs/cgroup/docker/edf3cbe4a9395096fe12859fa6265ff21c0d7bb6f6efc8be79b12768936a7cf3: no space left on device: unknown

No action was created for `docker compose run` but this should always be called with the `docker 
compose --rm` flag to ensure cleanup.

## dist/ output
To run an action on GitHub a single JS file is required and there's no opportunity to run build
commands before the action is triggered. In order to prevent needing to commit the entirety of
node_modules, `@verce/ncc` has been used to generate a single JavaScript file that includes the
imports of the dependencies so it can be executed by GitHub directly.

The files in `dist/` are thus generated using `npm run build`.
