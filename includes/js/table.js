document.addEventListener('DOMContentLoaded', () => {
  const buttonAdd = document.querySelector('#add_site');
  buttonAdd.addEventListener('click', (e) => {
    if (e.target.id === 'add_site') {
      const select = document.querySelector('#action-selector-site');
      const id = document.querySelector('#list_site').querySelectorAll('tbody')[0];
      const newRow = id.insertRow();
      const selectedElement = select.options[select.selectedIndex];
      newRow.innerHTML = `<tr id="site-${selectedElement.value}" >`
                + `<td class="title column-title has-row-actions column-primary page-title" data-colname="Site">${
                  selectedElement.innerHTML
                }</td>`
                + '<td class="action-remove-site" style="width: 80px;" >'
                + `<input class="input-text" type="hidden" value="${selectedElement.value}" name="site_id[${selectedElement.value}]" />`
                + ' <button type="button" class="button action"> '
                + '<span class="btn-remove-site">Remove</span> '
                + '</button> '
                + '</td>'
                + '</tr>';
      select.remove(select.selectedIndex);
    }
  }, false);
  document.addEventListener('click', (e) => {
    if (e.target.classList && e.target.classList[0] === 'btn-remove-site') {
      const deleteRow = e.target.parentNode.parentNode.parentNode;
      const select = document.querySelector('#action-selector-site');
      const text = deleteRow.querySelectorAll('.column-title')[0].innerHTML;
      const { value } = deleteRow.querySelectorAll('.input-text')[0];
      select.options[select.options.length] = new Option(text, value);
      deleteRow.parentNode.removeChild(deleteRow);
    }
  }, false);
}, false);
