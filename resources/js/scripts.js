const productDetailModal = document.getElementById("productDetailModal");
      productDetailModal.addEventListener("show.bs.modal", function (event) {
        // Botão que acionou o modal
        const button = event.relatedTarget;

        // Extrai informações dos atributos data-*
        const title = button.getAttribute("data-product-title");
        const price = button.getAttribute("data-product-price");
        const imgSrc = button.getAttribute("data-product-img");
        const description = button.getAttribute("data-product-desc");

        // Atualiza o conteúdo do modal
        const modalTitle = productDetailModal.querySelector(".modal-title");
        const modalImage =
          productDetailModal.querySelector("#modalProductImage");
        const modalPrice =
          productDetailModal.querySelector("#modalProductPrice");
        const modalDescription = productDetailModal.querySelector(
          "#modalProductDescription"
        );

        modalTitle.textContent = title;
        modalImage.src = imgSrc;
        modalPrice.textContent = price;
        modalDescription.textContent = description;
      });