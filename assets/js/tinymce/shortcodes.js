export default function (shortcodeUrl) {
  tinymce.PluginManager.add('ohshortcodes', (editor, url) => {
    async function openDialog() {
      let shortcode = null;

      const dialogConfig = {
        title: 'Shortcodes',
        buttons: [
          { type: 'cancel', text: 'Close' },
          { type: 'submit', text: 'Insert', buttonType: 'primary' },
        ],
        onTabChange: (api, details) => {
          const data = api.getData();

          shortcode = data[`${details.newTabName}_shortcode`];
        },
        onChange: (api, details) => {
          const data = api.getData();

          shortcode = data[details.name];
        },
        onSubmit: (api) => {
          if (shortcode) {
            editor.insertContent(`{{${shortcode}}}`);
          }

          api.close();
        },
      };

      dialogConfig.body = {
        type: 'panel',
        items: [
          {
            type: 'alertbanner',
            text: 'Loading shortcodes...',
            level: 'info',
            icon: 'info',
          },
        ],
      };

      const dialog = editor.windowManager.open(dialogConfig);

      try {
        const response = await fetch(shortcodeUrl);
        const shortcodes = await response.json();

        dialogConfig.body = {
          type: 'tabpanel',
          tabs: shortcodes,
        };

        dialog.redial(dialogConfig);

        shortcode = dialog.getData().tab_0_shortcode;
      } catch (e) {
        console.log(e);
        dialogConfig.body = {
          type: 'panel',
          items: [
            {
              type: 'alertbanner',
              text: 'There was an issue loading shortcodes.',
              level: 'warn',
              icon: 'warning',
            },
          ],
        };

        dialog.redial(dialogConfig);
      }
    }

    editor.ui.registry.addButton('ohshortcodes', {
      name: 'Shortcodes',
      icon: 'code-sample',
      tooltip: 'Shortcodes',
      onAction: openDialog,
    });

    editor.ui.registry.addMenuItem('ohshortcodes', {
      text: 'Shortcodes',
      icon: 'code-sample',
      onAction: openDialog,
    });

    return {
      getMetadata: () => ({
        name: 'Shortcodes',
        url: 'mailto:support@ohmedia.ca',
      }),
    };
  });
}
