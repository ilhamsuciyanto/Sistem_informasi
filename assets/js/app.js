(() => {
  const sidebar = document.getElementById("sidebar");
  const scrim = document.getElementById("mobileScrim");
  const menuButton = document.getElementById("mobileMenu");
  const closeMenu = () => {
    sidebar?.classList.remove("open");
    scrim?.classList.remove("visible");
  };
  menuButton?.addEventListener("click", () => {
    sidebar?.classList.add("open");
    scrim?.classList.add("visible");
  });
  scrim?.addEventListener("click", closeMenu);
  document.querySelector(".flash-close")?.addEventListener("click", (event) => event.currentTarget.parentElement.remove());

  document.querySelectorAll("[data-confirm]").forEach((button) => {
    button.addEventListener("click", (event) => {
      if (!window.confirm(button.dataset.confirm)) event.preventDefault();
    });
  });

  const productType = document.getElementById("productType");
  const money = (value) => new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    maximumFractionDigits: 0,
  }).format(Number.isFinite(value) ? value : 0);
  const updateProductFields = () => {
    const retail = productType?.value === "retail";
    document.querySelectorAll(".retail-field").forEach((field) => field.classList.toggle("hidden", !retail));
    document.querySelector(".recipe-editor")?.classList.toggle("hidden", retail);
    updateProductHpp();
  };
  productType?.addEventListener("change", updateProductFields);
  const recipeRows = document.getElementById("recipeRows");
  const updateProductHpp = () => {
    const preview = document.getElementById("hppPreview");
    if (!preview || !productType) return;
    const retail = productType.value === "retail";
    const breakdown = document.getElementById("hppBreakdown");
    const note = document.getElementById("hppPreviewNote");
    let hpp = 0;
    let hasIngredients = false;
    let missingCost = false;
    breakdown.replaceChildren();

    const addBreakdown = (name, quantity, unit, unitCost, subtotal) => {
      const row = document.createElement("div");
      row.className = "hpp-breakdown-row";
      const label = document.createElement("span");
      label.textContent = `${name}: ${quantity} ${unit} × ${money(unitCost)}/${unit}`;
      const total = document.createElement("strong");
      total.textContent = money(subtotal);
      row.append(label, total);
      breakdown.append(row);
    };

    if (retail) {
      const inventorySelect = document.querySelector('[name="inventory_id"]');
      const option = inventorySelect?.selectedOptions[0];
      const quantityInput = document.querySelector('[name="stock_quantity"]');
      const quantity = Number(quantityInput?.value) || 0;
      const unitCost = Number(option?.dataset.cost) || 0;
      if (option && quantity > 0) {
        hasIngredients = true;
        hpp = quantity * unitCost;
        missingCost = unitCost <= 0;
        addBreakdown(option.textContent.trim().split(" (")[0], quantity, option.dataset.unit || "unit", unitCost, hpp);
      }
    } else {
      recipeRows?.querySelectorAll(".recipe-line").forEach((row) => {
        const select = row.querySelector('[name="recipe_inventory_id[]"]');
        const option = select?.selectedOptions[0];
        const quantity = Number(row.querySelector('[name="recipe_quantity[]"]')?.value) || 0;
        if (!option?.value || quantity <= 0) return;
        const unitCost = Number(option.dataset.cost) || 0;
        const subtotal = quantity * unitCost;
        hasIngredients = true;
        missingCost ||= unitCost <= 0;
        hpp += subtotal;
        addBreakdown(option.textContent.trim().split(" (")[0], quantity, option.dataset.unit || "unit", unitCost, subtotal);
      });
    }

    const price = Number(document.querySelector('[name="selling_price"]')?.value) || 0;
    const margin = price > 0 ? ((price - hpp) / price) * 100 : null;
    const target = Number(document.querySelector('[name="target_margin"]')?.value) || 0;
    const rounding = Number(preview.closest("#product-form")?.dataset.priceRounding) || 500;
    const suggested = target > 0 && target < 100 && hpp > 0
      ? Math.ceil((hpp / (1 - target / 100)) / rounding) * rounding
      : null;
    document.getElementById("previewHpp").textContent = money(hpp);
    document.getElementById("previewProfit").textContent = price > 0 ? money(price - hpp) : "Isi harga jual";
    document.getElementById("previewMargin").textContent = margin === null ? "Isi harga jual" : `${margin.toFixed(1).replace(".", ",")}%`;
    document.getElementById("previewPrice").textContent = suggested === null ? "—" : money(suggested);

    if (!hasIngredients) {
      note.textContent = retail
        ? "Pilih barang persediaan dan isi takaran per unit jual untuk menghitung HPP."
        : "Pilih minimal satu bahan resep dan isi takarannya untuk menghitung HPP.";
    } else if (missingCost) {
      note.textContent = "Ada bahan dengan biaya rata-rata Rp0. Isi biaya di Persediaan atau catat pembelian agar HPP akurat.";
    } else {
      note.textContent = retail
        ? "HPP produk ritel = takaran per unit jual × biaya rata-rata barang persediaan."
        : "HPP per porsi = jumlah seluruh takaran bahan × biaya rata-rata per satuan.";
    }
  };
  productType?.addEventListener("change", updateProductHpp);
  recipeRows?.addEventListener("input", updateProductHpp);
  recipeRows?.addEventListener("change", updateProductHpp);
  document.querySelector("#product-form")?.addEventListener("input", (event) => {
    if (event.target.matches('[name="selling_price"], [name="target_margin"], [name="stock_quantity"]')) updateProductHpp();
  });
  document.querySelector("#product-form")?.addEventListener("change", (event) => {
    if (event.target.matches('[name="inventory_id"]')) updateProductHpp();
  });
  document.getElementById("addRecipe")?.addEventListener("click", () => {
    const template = recipeRows?.querySelector(".recipe-line");
    if (!template) return;
    const row = template.cloneNode(true);
    row.querySelector("select").value = "";
    row.querySelector("input").value = "1";
    recipeRows.appendChild(row);
    updateProductHpp();
  });
  recipeRows?.addEventListener("click", (event) => {
    if (event.target.closest(".recipe-remove") && recipeRows.querySelectorAll(".recipe-line").length > 1) {
      event.target.closest(".recipe-line").remove();
      updateProductHpp();
    }
  });
  updateProductFields();

  const inventoryForm = document.getElementById("inventoryForm");
  const updateInventoryValue = () => {
    if (!inventoryForm) return;
    const stock = Number(inventoryForm.querySelector('[name="stock"]')?.value) || 0;
    const averageCost = Number(inventoryForm.querySelector('[name="average_cost"]')?.value) || 0;
    const unit = inventoryForm.querySelector('[name="unit"]')?.value || "unit";
    document.getElementById("inventoryValuePreview").textContent = money(stock * averageCost);
    document.getElementById("inventoryCostExplanation").textContent =
      `${stock} ${unit} × ${money(averageCost)}/${unit}. Ini nilai persediaan, bukan HPP produk.`;
  };
  inventoryForm?.addEventListener("input", updateInventoryValue);
  updateInventoryValue();

  const purchaseLines = document.getElementById("purchaseLines");
  const updatePurchaseTotal = () => {
    if (!purchaseLines) return;
    const total = [...purchaseLines.querySelectorAll(".purchase-line")].reduce((sum, row) => {
      const quantity = Number(row.querySelector('input[name="quantity[]"]').value) || 0;
      const unitCost = Number(row.querySelector('input[name="unit_cost[]"]').value) || 0;
      return sum + quantity * unitCost;
    }, 0);
    const label = document.getElementById("purchaseTotalPreview");
    if (label) label.textContent = money(total);
  };
  purchaseLines?.addEventListener("input", updatePurchaseTotal);
  document.getElementById("addPurchaseLine")?.addEventListener("click", () => {
    const template = purchaseLines?.querySelector(".purchase-line");
    if (!template) return;
    const row = template.cloneNode(true);
    row.querySelector('input[name="quantity[]"]').value = "1";
    row.querySelector('input[name="unit_cost[]"]').value = "";
    purchaseLines.appendChild(row);
    updatePurchaseTotal();
  });
  purchaseLines?.addEventListener("click", (event) => {
    if (event.target.closest(".purchase-remove") && purchaseLines.querySelectorAll(".purchase-line").length > 1) {
      event.target.closest(".purchase-line").remove();
      updatePurchaseTotal();
    }
  });
  updatePurchaseTotal();
})();
