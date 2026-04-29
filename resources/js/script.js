
  const radioExportAll = document.getElementById('exportAll');
  const radioExportFiltered = document.getElementById('exportFiltered');
  const filtersContainer = document.getElementById('exportFiltersContainer');

  document.querySelectorAll('input[name="exportOptions"]').forEach((radio) => {
    radio.addEventListener('change', function() {
      // Se o radio "Exportar com filtros" estiver selecionado, mostra o container de filtros
      if (radioExportFiltered.checked) {
        filtersContainer.style.display = 'block';
      } else {
        // Caso contrário, esconde
        filtersContainer.style.display = 'none';
      }
    });
  });



const productDetailModal = document.getElementById('productDetailModal');
productDetailModal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    const productTitle = button.getAttribute('data-product-title');
    const productPrice = button.getAttribute('data-product-price');
    const productImg = button.getAttribute('data-product-img');
    const productDesc = button.getAttribute('data-product-desc');

    const modalTitle = productDetailModal.querySelector('.modal-title');
    const modalImage = productDetailModal.querySelector('#modalProductImage');
    const modalPrice = productDetailModal.querySelector('#modalProductPrice');
    const modalDescription = productDetailModal.querySelector('#modalProductDescription');

    modalTitle.textContent = productTitle;
    modalImage.src = productImg;
    modalPrice.textContent = productPrice;
    modalDescription.textContent = productDesc;
});


