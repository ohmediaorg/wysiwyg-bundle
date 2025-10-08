// function getRow() {
//   const row = document.createElement('div');
//   row.style.display = 'grid';
//   row.style.gridTemplateColumns = '50px 1fr';
//   row.style.alignItems = 'center';
//   row.style.gap = '10px';

//   row.onmouseenter = () => {
//     row.style.background = '#f0f0f0';
//   };

//   row.onmouseleave = () => {
//     row.style.background = '';
//   };

//   return row;
// }

// function getColumn() {
//   const column = document.createElement('div');
//   column.style.padding = '5px';

//   return column;
// }

// function getBackRow(onclick) {
//   const row = getRow();

//   const col1 = getColumnOne();
//   col1.innerHTML = '<i class="bi bi-arrow-up-left-square-fill"></i>';

//   row.append(col1);

//   const col2 = document.createElement('div');
//   col2.innerHTML = 'Back';

//   row.append(col2);

//   row.onclick = onclick;
//   row.style.cursor = 'pointer';

//   return row;
// }

// function getFolderRow(item, onclick) {
//   const row = getRow();

//   const col1 = getColumnOne();

//   if (item.locked) {
//     col1.innerHTML = '<i class="bi bi-folder-x text-secondary"></i>';
//   } else {
//     col1.innerHTML = '<i class="bi bi-folder-check"></i>';
//   }

//   row.append(col1);

//   const col2 = getColumn();
//   col2.innerHTML = item.name;

//   row.append(col2);

//   row.onclick = onclick;
//   row.style.cursor = 'pointer';

//   return row;
// }

// function getImageRow(item, onclick) {
//   const row = getRow();
//   row.classList.add('imagepicker-image-row');

//   const col1 = getColumnOne();
//   col1.innerHTML = item.image;

//   row.append(col1);

//   const col2 = getColumn();
//   col2.innerHTML = item.name + ' (ID:' + item.id + ')';

//   if (item.locked) {
//     col2.innerHTML += '<i class="bi bi-lock-fill text-secondary"></i>';
//   }

//   row.append(col2);

//   row.append(col3);

//   row.onclick = onclick;
//   row.style.cursor = 'pointer';

//   return row;
// }

// function getColumnOne() {
//   const column = getColumn();
//   column.style.fontSize = '1.5rem';
//   column.style.textAlign = 'center';

//   return column;
// }

// export default function (editor, filesUrl, callback, value, meta) {
//   let originalValue = value;
//   let originalMeta = meta;

//   const dialogConfig = {
//     title: 'Image Picker',
//     size: 'medium',
//     buttons: [
//       { type: 'cancel', text: 'Close' },
//       { type: 'submit', text: 'Select', primary: true },
//     ],
//     body: {
//       type: 'panel',
//       items: [
//         {
//           type: 'alertbanner',
//           text: 'Loading files...',
//           level: 'info',
//           icon: 'info',
//         },
//       ],
//     },
//     onClose() {
//       callback(originalValue, originalMeta);
//     },
//     onSubmit() {
//       callback(value, meta);
//     },
//   };

//   const dialog = editor.windowManager.open(dialogConfig);

//   const containerId = 'tinymce_imagepicker_rows';
//   let container = null;

//   async function populateFiles(url) {
//     localStorage.setItem('oh_media_wysiwyg_tinymce_imagepicker_url', url);

//     if (container) {
//       container.innerHTML = '';
//     }

//     dialog.setEnabled('insert_button', false);

//     dialogConfig.body = {
//       type: 'panel',
//       items: [
//         {
//           type: 'alertbanner',
//           text: 'Loading files...',
//           level: 'info',
//           icon: 'info',
//         },
//       ],
//     };

//     dialog.redial(dialogConfig);

//     try {
//       const response = await fetch(url);
//       const data = await response.json();

//       if (!data.items.length && !data.back_path) {
//         dialogConfig.body = {
//           type: 'panel',
//           items: [
//             {
//               type: 'alertbanner',
//               text: 'No files/folders found.',
//               level: 'info',
//               icon: 'info',
//             },
//           ],
//         };

//         dialog.redial(dialogConfig);

//         return;
//       }

//       dialogConfig.body = {
//         type: 'panel',
//         items: [
//           {
//             type: 'htmlpanel',
//             html: `<div id="${containerId}" style="display: grid; grid-auto-rows: 1fr;"></div`,
//           },
//         ],
//       };

//       dialog.redial(dialogConfig);

//       container = document.getElementById(containerId);

//       if (data.back_path) {
//         const back = getBackRow(populateFiles.bind(null, data.back_path));

//         container.append(back);
//       }

//       data.items.forEach(function (item) {
//         let row = null;

//         if ('folder' === item.type) {
//           row = getFolderRow(item, populateFiles.bind(null, item.url));
//         } else if ('image' === item.type) {
//           item.selected = item.path === value;

//           const onclickImage = (e) => {
//             if (item.selected) {
//               // de-select
//               value = null;
//               meta = null;
//             } else {
//               // flag as selected
//               value = item.path;
//               meta = {
//                 width: item.width,
//                 height: item.height,
//               };
//             }
//           };

//           row = getImageRow(item, onclickImage);
//         }

//         container.append(row);
//       });
//     } catch (e) {
//       console.log(e);
//       dialogConfig.body = {
//         type: 'panel',
//         items: [
//           {
//             type: 'alertbanner',
//             text: 'There was an issue loading the files.',
//             level: 'warn',
//             icon: 'warning',
//           },
//         ],
//       };

//       dialog.redial(dialogConfig);
//     }
//   }

//   const savedUrl = localStorage.getItem(
//     'oh_media_wysiwyg_tinymce_imagepicker_url'
//   );

//   populateFiles(savedUrl ?? filesUrl);
// }

export default function (imagepickerUrl) {
  async function open(editor, callback, value) {
    let originalValue = value;

    const dialogConfig = {
      title: 'Image Picker',
      size: 'medium',
      buttons: [
        { type: 'cancel', text: 'Close' },
        {
          type: 'submit',
          text: 'Select',
          buttonType: 'primary',
          name: 'select_button',
          enabled: false,
        },
      ],
      onClose: () => {
        callback(originalValue);
      },
      onSubmit: (api) => {
        callback(value);

        api.close();
      },
    };

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

    const dialog = editor.windowManager.open(dialogConfig);

    try {
      const response = await fetch(imagepickerUrl);
      const items = await response.json();

      dialogConfig.body = {
        type: 'panel',
        items: [
          {
            type: 'tree',
            items: items,
            onLeafAction(id) {
              value = id;

              dialog.setEnabled('select_button', true);
            },
          },
        ],
      };

      dialog.redial(dialogConfig);
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

  return { open };
}
