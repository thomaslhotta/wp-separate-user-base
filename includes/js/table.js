document.addEventListener('DOMContentLoaded', () => {
  document.addEventListener('click', (e) => {
    if (e.target.classList.contains('add_row')) {
      const select = e.target.parentNode.querySelector('select');
      const id = e.target.closest('.wrap').querySelector('tbody');
      const selectedElement = select.options[select.selectedIndex];
      if (selectedElement) {
        const newRow = id.insertRow();
        newRow.innerHTML = `<tr id="site-${selectedElement.value}" >`
            + `<td class="title column-title has-row-actions column-primary page-title" data-colname="Site">${
              selectedElement.innerHTML
            }</td>`
            + '<td class="action-remove-site" style="width: 80px;" >'
            + `<input class="input-text" type="hidden" value="${selectedElement.value}" name="${select.getAttribute('type')}[${selectedElement.value}]" />`
            + ' <button type="button" class="button action"> '
            + '<span class="btn-remove-site">Remove</span> '
            + '</button> '
            + '</td>'
            + '</tr>';
        select.remove(select.selectedIndex);
      }
    }
  }, false);
  document.addEventListener('click', (e) => {
    if (e.target.classList.contains('btn-remove-site')) {
      const deleteRow = e.target.closest('tr');
      const select = e.target.closest('.wrap').querySelector('select');
      const text = deleteRow.querySelector('.column-title').innerHTML;
      const { value } = deleteRow.querySelectorAll('.input-text')[0];
      select.options[select.options.length] = new Option(text, value);
      deleteRow.parentNode.removeChild(deleteRow);
    }
  }, false);
}, false);
