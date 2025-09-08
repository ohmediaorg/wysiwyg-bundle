export default function (contentlinkUrl) {
  tinymce.PluginManager.add('ohcontentlinks', (editor, url) => {
    async function openDialog() {
      let data = null;

      const dialogConfig = {
        title: 'Content Links',
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
            const selectedText = editor.selection
              .getContent({ format: 'text' })
              .trim();

            const linkText = selectedText ? selectedText : data.text;

            editor.insertContent(
              `<a href="{{${data.href}}}" title="${data.title}">${linkText}</a>`
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

    editor.ui.registry.addButton('ohcontentlinks', {
      name: 'Content Links',
      icon: 'link',
      tooltip: 'Content Links',
      onAction: openDialog,
    });

    editor.ui.registry.addMenuItem('ohcontentlinks', {
      text: 'Content Links',
      icon: 'link',
      onAction: openDialog,
    });

    return {
      getMetadata: () => ({
        name: 'Content Links',
        url: 'mailto:support@ohmedia.ca',
      }),
    };
  });
}
