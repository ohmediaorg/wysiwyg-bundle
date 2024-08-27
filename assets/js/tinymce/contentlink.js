export default function (contentlinkUrl) {
  tinymce.PluginManager.add('ohcontentlink', (editor, url) => {
    async function openDialog() {
      let data = null;

      const dialogConfig = {
        title: 'Content Link',
        size: 'medium',
        buttons: [
          { type: 'cancel', text: 'Close' },
          {
            type: 'submit',
            text: 'Insert',
            buttonType: 'primary',
            name: 'insert_button',
            enabled: false,
          },
        ],
        onTabChange: () => {
          data = null;
          dialog.setEnabled('insert_button', false);
        },
        onSubmit: (api) => {
          if (data) {
            editor.insertContent(
              `<a href="{{${data.href}}}" title="${data.title}">${data.text}</a>`
            );
          }

          api.close();
        },
      };

      dialogConfig.body = {
        type: 'panel',
        items: [
          {
            type: 'alertbanner',
            text: 'Loading content...',
            level: 'info',
            icon: 'info',
          },
        ],
      };

      const dialog = editor.windowManager.open(dialogConfig);

      try {
        const response = await fetch(contentlinkUrl);
        const tabs = await response.json();

        tabs.forEach((tab) => {
          tab.items[0].onLeafAction = (id) => {
            data = JSON.parse(id);
            dialog.setEnabled('insert_button', true);
          };
        });

        dialogConfig.body = {
          type: 'tabpanel',
          tabs: tabs,
        };

        dialog.redial(dialogConfig);

        data = null;
      } catch (e) {
        console.log(e);
        dialogConfig.body = {
          type: 'panel',
          items: [
            {
              type: 'alertbanner',
              text: 'There was an issue loading the content.',
              level: 'warn',
              icon: 'warning',
            },
          ],
        };

        dialog.redial(dialogConfig);
      }
    }

    editor.ui.registry.addButton('ohcontentlink', {
      name: 'Content Link',
      icon: 'link',
      tooltip: 'Content Link',
      onAction: openDialog,
    });

    editor.ui.registry.addMenuItem('ohcontentlink', {
      text: 'Content Link',
      icon: 'link',
      onAction: openDialog,
    });

    return {
      getMetadata: () => ({
        name: 'Content Link',
        url: 'mailto:support@ohmedia.ca',
      }),
    };
  });
}
