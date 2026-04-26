document.addEventListener('click', e => {
  if (e.target.matches('[data-confirm]') && !confirm(e.target.dataset.confirm)) e.preventDefault();
});

function addOptionField() {
  const box = document.querySelector('#optionsBox');
  if (!box) return;
  const input = document.createElement('input');
  input.name = 'options[]';
  input.placeholder = 'Option label';
  input.style.marginTop = '8px';
  box.appendChild(input);
}
