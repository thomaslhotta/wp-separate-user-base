# WP Separate User Base Plugin

| Master Branch | Develop Branch | All Branches |
|:---:|:---:|:---:|
| ![Unit tests](https://github.com/thomaslhotta/wp-separate-user-base/actions/workflows/tests.yml/badge.svg?branch=master) | ![Unit tests](https://github.com/thomaslhotta/wp-separate-user-base/actions/workflows/tests.yml/badge.svg?branch=develop) | ![Unit tests](https://github.com/thomaslhotta/wp-separate-user-base/actions/workflows/tests.yml/badge.svg) |


The WP Separate User Base plugin enhances WordPress Multisite installations by enabling each site within the network to
maintain its own distinct user base. This flexibility allows for the creation of multiple user accounts with the same
email address, while ensuring that these accounts are restricted to specific sites or the entire network as defined by
the admin. This plugin is a powerful tool for administrators looking to customize their user management on a more
granular level.

## Features

- **Flexible User Bases:** Users can be restricted to individual sites, the entire network, or a combination of both.
Additional separation criteria can be implemented via filters.
- **Allows users to sign up to multiple sites with the same email address:** Allows the creation of multiple user i
accounts using the same email address, while ensuring that within the defined separation criteria, email addresses appear
unique to WordPress and most plugins.

## How It Works

- The plugin removes the global enforcement of unique `user_email` fields across the entire WordPress installation.
- Users are assigned meta user keys that specify the sites and networks they are associated with. This is used to inject
meta queries into all uses of `WP_User_Query` to filter user queries, ensuring users only appear where they are supposed to.
- This plugin  Overrides the `get_user_by` function to account for the assigned sites and networks, as it does not utilize `WP_User_Query`.
- Users are automatically associated with the site or network where they are created, based on the `wp_sub_add_users_to_network` network option.

## Intended Audience

This plugin is designed for advanced WordPress users and administrators familiar with WordPress Multisite environments and who require custom user management solutions.

## Limitations

- No frontend interface is provided for users to sign up for multiple sites.
- Users can still be retrieved by ID or `user_login` through methods that do not utilize `WP_User_Query` or `get_user_by`, potentially bypassing restrictions.
- Usernames must remain unique across the network.

## Installation

1. Download the plugin from the GitHub repository.
2. Upload the plugin files to your WordPress installation's `wp-content/plugins` or `wp-content/mu-plugins` directory.
3. Activate the plugin through the 'Plugins' screen in WordPress.

## Usage

This plugin offers extensions to the admin interface and WP-CLI commands for managing user availability across sites and networks. .

## Contributing

Contributions to the WP Separate User Base plugin are welcome.

## Support

For support, please open an issue on the GitHub repository. Please note that this plugin is provided "as is" without warranty of any kind, either expressed or implied.

## License

The WP Separate User Base plugin is open-source software licensed under the MIT license.
