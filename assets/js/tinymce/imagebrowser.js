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

function getImageRow(item, onclickImage) {
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

  row.append(col3);

  return row;
}

function getColumnOne() {
  const column = getColumn();
  column.style.fontSize = '1.5rem';
  column.style.textAlign = 'center';

  return column;
}

function getButtonImage(onclick) {
  const button = getButton();
  button.dataset.mceTooltip = 'Select Image';
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

export default function (imagebrowserUrl) {
  console.log(imagebrowserUrl);
  async function open(editor, callback, value) {
    const dialogConfig = {
      title: 'Image Browser',
      size: 'medium',
      buttons: [{ type: 'cancel', text: 'Close' }],
      onClose() {
        callback(value);
      },
      body: {
        type: 'panel',
        items: [
          {
            type: 'alertbanner',
            text: 'Loading images...',
            level: 'info',
            icon: 'info',
          },
        ],
      },
    };

    const dialog = editor.windowManager.open(dialogConfig);

    const containerId = 'tinymce_imagebrowser_rows';
    let container = null;

    async function populate(url) {
      localStorage.setItem('oh_media_wysiwyg_tinymce_imagebrowser_url', url);

      if (container) {
        container.innerHTML = '';
      }

      dialog.setEnabled('insert_button', false);

      dialogConfig.body = {
        type: 'panel',
        items: [
          {
            type: 'alertbanner',
            text: 'Loading images...',
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
                text: 'No images/folders found.',
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
          const back = getBackRow(populate.bind(null, data.back_path));

          container.append(back);
        }

        data.items.forEach(function (item) {
          let row = null;

          if ('folder' === item.type) {
            row = getFolderRow(item, populate.bind(null, item.url));
          } else if ('image' === item.type) {
            const onclickImage = () => {
              callback(item.path);

              dialog.close();
            };

            row = getImageRow(item, onclickImage);
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
              text: 'There was an issue loading the images.',
              level: 'warn',
              icon: 'warning',
            },
          ],
        };

        dialog.redial(dialogConfig);
      }
    }

    const savedUrl = localStorage.getItem(
      'oh_media_wysiwyg_tinymce_imagebrowser_url'
    );

    populate(savedUrl ?? imagebrowserUrl);
  }

  return { open };
}
