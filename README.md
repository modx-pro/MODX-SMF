### [MODX][1] + [SMF][2] two-way synchronization:

- Login and logout.
- Create users.
- Reset passwords.
- Update common profile fields (username, fullname, email, date of birth, gender, etc.)
- Delete users.
- Activate and deactivate users.

### Quick start

1. Install MODX 2.3+
2. Install SMF 2.0
3. Install this package to MODX, you will be prompted to specify path to SMF - "{base_path}forum/" by default.
4. That`s all, now you systems must be synchronized.

If you will move SMF to another directory, you will need to reinstall this package.

### Know issues

- If you will change username in MODX, his password will no longer work in SMF because of its hashing algorithm.
But you can reset user password in MODX manager right after changing username.
- You must specify password when create a new user in MODX, otherwise he will not be able to login in SMF without password reset.
- All was tested only with the SMF installed in a subdirectory.
When working with subdomains most likely will be a troubles with the session.
- All troubles of login\logout is associated with the user session. The first thing that you need to do is logout from MODX manager and clear a cookies.
The best way to test everything in anonymous mode of your browser.

### System settings

See description of SMF system settings in MODX manager.

---

sottwell supported the development of this package


[1]: https://modx.com
[2]: http://www.simplemachines.org