# External Repo Workflow

The deployment workspace described here contains two separate Git repositories:

- Backend POS repo: `/var/www/pos`
- Mobile app repo: `/var/www/pos/.external/Pos_app`

Keep edits in the repo that owns the code:

- Edit Laravel, Blade, modules, assets, and server scripts in `/var/www/pos`
- Edit Android app code in `/var/www/pos/.external/Pos_app`

The `.external/` directory is excluded through `/var/www/pos/.git/info/exclude`, so the backend repo does not treat the nested mobile checkout as an untracked change.

## Common commands

Check both repos:

```bash
bash scripts/sync_repos.sh status all
```

Pull backend only:

```bash
bash scripts/sync_repos.sh pull backend
```

Pull mobile app only:

```bash
bash scripts/sync_repos.sh pull mobile
```

Push mobile app changes:

```bash
bash scripts/sync_repos.sh push mobile
```

Direct Git usage also works:

```bash
git -C /var/www/pos status
git -C /var/www/pos/.external/Pos_app status
```

## Mobile app note

Backend deployment and signed Android distribution are independent: pushing a
repository does not deploy the server, create a GitHub Release, or update
installed Android apps. Deploy compatible backend changes before app rollout.

For the current Windows development workspace, the repositories are separate
siblings at `D:\Myapps\pos_src` and `D:\Myapps\pos_app`; the Linux paths above
apply only to the deployment workspace. Android signing material belongs
outside both repositories and must not be included in Git synchronization.

See [MOBILE_API.md](MOBILE_API.md) for system usage and deployment requirements,
and the [Android README](https://github.com/Machogu001/pos_app/blob/main/README.md)
for builds, signed artifacts and release-key maintenance.

The Android app expects the POS website base URL and calls the Mobile API at `/api/mobile/v1` itself. It now also normalizes a pasted full API URL such as `https://example.com/api/mobile/v1` back to the website base URL.