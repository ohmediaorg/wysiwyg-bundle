function getRow() {
  const row = document.createElement('div');
  row.style.display = 'grid';
  row.style.gridTemplateColumns = '50px 1fr auto';
  row.style.alignItems = 'center';
  row.style.gap = '10px';

  row.onmouseenter = () => {
    row.style.background = '#f0f0f0';
  };

  row.onmouseleave = () => {
    row.style.background = '';
  };

  return row;
}

function getColumn() {
  const column = document.createElement('div');
  column.style.padding = '5px';

  return column;
}

function getBackRow(onclick) {
  const row = getRow();

  const col1 = getColumnOne();
  col1.innerHTML = '<i class="bi bi-arrow-up-left-square-fill"></i>';

  row.append(col1);

  const col2 = document.createElement('div');
  col2.innerHTML = 'Back';

  row.append(col2);

  const col3 = document.createElement('div');
  col3.innerHTML = '&nbsp;';

  row.append(col3);

  row.onclick = onclick;
  row.style.cursor = 'pointer';

  return row;
}

function getFolderRow(item, onclick) {
  const row = getRow();

  const col1 = getColumnOne();

  if (item.locked) {
    col1.innerHTML = '<i class="bi bi-folder-x text-secondary"></i>';
  } else {
    col1.innerHTML = '<i class="bi bi-folder-check"></i>';
  }

  row.append(col1);

  const col2 = getColumn();
  col2.innerHTML = item.name;

  row.append(col2);

  const col3 = getColumn();
  col3.innerHTML = '&nbsp;';

  row.append(col3);

  row.onclick = onclick;
  row.style.cursor = 'pointer';

  return row;
}

function getImageRow(item, onclickImage, onclickLink) {
  const row = getRow();

  const col1 = getColumnOne();
  col1.innerHTML = item.image;

  row.append(col1);

  const col2 = getColumn();
  col2.innerHTML = item.name + ' (ID:' + item.id + ')';

  if (item.locked) {
    col2.innerHTML += '<i class="bi bi-lock-fill text-secondary"></i>';
  }

  row.append(col2);

  const col3 = getColumn();
  col3.className = 'tox-toolbar__group';
  col3.style.textAlign = 'right';

  col3.append(getButtonImage(onclickImage));
  col3.append(getButtonLink(onclickLink));

  row.append(col3);

  return row;
}

function getFileRow(item, onclickLink) {
  const row = getRow();

  const col1 = getColumnOne();

  if (item.locked) {
    col1.innerHTML =
      '<i class="bi bi-file-earmark-lock2-fill text-secondary"></i>';
  } else {
    col1.innerHTML = '<i class="bi bi-file-earmark-fill"></i>';
  }

  row.append(col1);

  const col2 = getColumn();
  col2.innerHTML = item.name + ' (ID:' + item.id + ')';

  row.append(col2);

  const col3 = getColumn();
  col3.className = 'tox-toolbar__group';
  col3.style.textAlign = 'right';

  col3.append(getButtonLink(onclickLink));

  row.append(col3);

  return row;
}

function getColumnOne() {
  const column = getColumn();
  column.style.fontSize = '1.5rem';
  column.style.textAlign = 'center';

  return column;
}

function getButtonLink(onclick) {
  const button = getButton();
  button.dataset.mceTooltip = 'Insert Link';
  button.innerHTML = '<i class="bi bi-link-45deg"></i>';
  button.onclick = onclick;

  return button;
}

function getButtonImage(onclick) {
  const button = getButton();
  button.dataset.mceTooltip = 'Insert Image';
  button.innerHTML = '<i class="bi bi-image"></i>';
  button.onclick = onclick;

  return button;
}

function getButton() {
  const button = document.createElement('button');
  button.type = 'button';
  button.className = 'tox-tbtn';
  button.style.background = 'transparent';

  button.onmouseenter = () => {
    button.style.background = '#fff';
  };

  button.onmouseleave = () => {
    button.style.background = 'transparent';
  };

  return button;
}

export default function (filesUrl) {
  tinymce.PluginManager.add('ohfilebrowser', (editor, url) => {
    async function openDialog() {
      const dialogConfig = {
        title: 'File Browser',
        size: 'medium',
        buttons: [{ type: 'cancel', text: 'Close' }],
        body: {
          type: 'panel',
          items: [
            {
              type: 'alertbanner',
              text: 'Loading files...',
              level: 'info',
              icon: 'info',
            },
          ],
        },
      };

      const dialog = editor.windowManager.open(dialogConfig);

      const containerId = 'tinymce_filebrowser_rows';
      let container = null;

      function onclickFile(item) {
        editor.insertContent(
          `<a href="{{file_href(${item.id})}}" title="${item.name}" target="_blank">${item.name}</a>`
        );

        dialog.close();
      }

      async function populateFiles(url) {
        localStorage.setItem('tinymce_filebrowser_url', url);

        if (container) {
          container.innerHTML = '';
        }

        dialog.setEnabled('insert_button', false);

        dialogConfig.body = {
          type: 'panel',
          items: [
            {
              type: 'alertbanner',
              text: 'Loading files...',
              level: 'info',
              icon: 'info',
            },
          ],
        };

        dialog.redial(dialogConfig);

        try {
          const response = await fetch(url);
          const data = await response.json();

          if (!data.items.length && !data.back_path) {
            dialogConfig.body = {
              type: 'panel',
              items: [
                {
                  type: 'alertbanner',
                  text: 'No files/folders found.',
                  level: 'info',
                  icon: 'info',
                },
              ],
            };

            dialog.redial(dialogConfig);

            return;
          }

          dialogConfig.body = {
            type: 'panel',
            items: [
              {
                type: 'htmlpanel',
                html: `<div id="${containerId}" style="display: grid; grid-auto-rows: 1fr;"></div`,
              },
            ],
          };

          dialog.redial(dialogConfig);

          container = document.getElementById(containerId);

          if (data.back_path) {
            const back = getBackRow(populateFiles.bind(null, data.back_path));

            container.append(back);
          }

          data.items.forEach(function (item) {
            let row = null;

            if ('folder' === item.type) {
              row = getFolderRow(item, populateFiles.bind(null, item.url));
            } else if ('image' === item.type) {
              const onclickImage = () => {
                editor.insertContent(`{{image(${item.id})}}`);

                dialog.close();
              };

              row = getImageRow(
                item,
                onclickImage,
                onclickFile.bind(null, item)
              );
            } else if ('file' === item.type) {
              row = getFileRow(item, onclickFile.bind(null, item));
            }

            container.append(row);
          });
        } catch (e) {
          console.log(e);
          dialogConfig.body = {
            type: 'panel',
            items: [
              {
                type: 'alertbanner',
                text: 'There was an issue loading the files.',
                level: 'warn',
                icon: 'warning',
              },
            ],
          };

          dialog.redial(dialogConfig);
        }
      }

      const savedUrl = localStorage.getItem('tinymce_filebrowser_url');

      populateFiles(savedUrl ?? filesUrl);
    }

    editor.ui.registry.addButton('ohfilebrowser', {
      name: 'File Browser',
      icon: 'image',
      tooltip: 'File Browser',
      onAction: openDialog,
    });

    editor.ui.registry.addMenuItem('ohfilebrowser', {
      text: 'File Browser',
      icon: 'image',
      onAction: openDialog,
    });

    return {
      getMetadata: () => ({
        name: 'File Browser',
        url: 'mailto:support@ohmedia.ca',
      }),
    };
  });
}
