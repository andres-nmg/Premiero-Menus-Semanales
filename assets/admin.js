(function ($) {
  "use strict";

  const fontStacks = {
    system: '-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif',
    serif: 'Georgia,"Times New Roman",serif',
    modern: '"Avenir Next",Avenir,Montserrat,Arial,sans-serif',
  };

  function updatePreview(key, value) {
    const preview = document.querySelector(".pms-preview");
    if (!preview || !key) return;

    const variables = {
      accent: "--pms-accent",
      accent_text: "--pms-accent-text",
      heading: "--pms-heading",
      text: "--pms-text",
      background: "--pms-background",
      panel_background: "--pms-panel-background",
      border: "--pms-border",
      body_size: "--pms-body-size",
      heading_size: "--pms-heading-size",
      radius: "--pms-radius",
      gap: "--pms-gap",
    };

    if (key === "font_family") {
      preview.style.setProperty("--pms-font", fontStacks[value] || fontStacks.system);
    } else if (variables[key]) {
      const suffix = ["body_size", "heading_size", "radius", "gap"].includes(key) ? "px" : "";
      preview.style.setProperty(variables[key], `${value}${suffix}`);
    }
  }

  $(".pms-color-field").each(function () {
    const input = this;
    $(input).wpColorPicker({
      change: function (_event, ui) {
        updatePreview(input.dataset.styleKey, ui.color.toString());
      },
      clear: function () {
        updatePreview(input.dataset.styleKey, "#ffffff");
      },
    });
  });

  document.querySelectorAll("[data-style-key]:not(.pms-color-field)").forEach((input) => {
    input.addEventListener("input", () => updatePreview(input.dataset.styleKey, input.value));
    input.addEventListener("change", () => updatePreview(input.dataset.styleKey, input.value));
  });

  document.querySelectorAll(".pms-copy-shortcode").forEach((button) => {
    button.addEventListener("click", async () => {
      try {
        await navigator.clipboard.writeText(button.dataset.shortcode || "");
        const original = button.textContent;
        button.textContent = "Copiado";
        window.setTimeout(() => {
          button.textContent = original;
        }, 1400);
      } catch (_error) {
        window.prompt("Copia este shortcode:", button.dataset.shortcode || "");
      }
    });
  });

  const form = document.getElementById("pms-menu-form");
  const table = document.getElementById("pms-menu-table");
  const jsonInput = document.getElementById("pms-menu-json");

  if (!form || !table || !jsonInput) return;

  form.addEventListener("submit", () => {
    const data = {};
    table.querySelectorAll("td[contenteditable]").forEach((cell) => {
      const { day, type, index } = cell.dataset;
      if (!data[day]) data[day] = {};
      if (!data[day][type]) data[day][type] = [];
      data[day][type][Number(index)] = cell.innerText.trim();
    });
    jsonInput.value = JSON.stringify(data);
  });

  document.getElementById("pms-clear-table")?.addEventListener("click", () => {
    if (!window.confirm("¿Quieres vaciar todas las celdas de este menú?")) return;
    table.querySelectorAll("td[contenteditable]").forEach((cell) => {
      cell.innerText = "";
    });
  });

  function csvEscape(value) {
    return `"${String(value).replace(/"/g, '""')}"`;
  }

  document.getElementById("pms-export-csv")?.addEventListener("click", () => {
    const rows = Array.from(table.querySelectorAll("tr")).map((row) =>
      Array.from(row.querySelectorAll("th,td")).map((cell) => csvEscape(cell.textContent.trim())).join(",")
    );
    const blob = new Blob(["\uFEFF" + rows.join("\r\n")], { type: "text/csv;charset=utf-8" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = "menu-semanal.csv";
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
  });

  function parseCsv(text) {
    const rows = [];
    let row = [];
    let field = "";
    let quoted = false;

    for (let index = 0; index < text.length; index += 1) {
      const char = text[index];
      const next = text[index + 1];
      if (char === '"' && quoted && next === '"') {
        field += '"';
        index += 1;
      } else if (char === '"') {
        quoted = !quoted;
      } else if (char === "," && !quoted) {
        row.push(field);
        field = "";
      } else if ((char === "\n" || char === "\r") && !quoted) {
        if (char === "\r" && next === "\n") index += 1;
        row.push(field);
        if (row.some((value) => value !== "")) rows.push(row);
        row = [];
        field = "";
      } else {
        field += char;
      }
    }
    row.push(field);
    if (row.some((value) => value !== "")) rows.push(row);
    return rows;
  }

  const csvInput = document.getElementById("pms-csv-input");
  document.getElementById("pms-import-csv")?.addEventListener("click", () => csvInput?.click());

  csvInput?.addEventListener("change", () => {
    const file = csvInput.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (event) => {
      const rows = parseCsv(String(event.target?.result || "").replace(/^\uFEFF/, ""));
      const bodyRows = table.querySelectorAll("tbody tr");
      bodyRows.forEach((tableRow, rowIndex) => {
        const values = rows[rowIndex + 1];
        if (!values) return;
        tableRow.querySelectorAll("td[contenteditable]").forEach((cell, cellIndex) => {
          cell.innerText = values[cellIndex + 1] || "";
        });
      });
      csvInput.value = "";
    };
    reader.readAsText(file);
  });

  table.addEventListener("paste", (event) => {
    const active = document.activeElement;
    if (!(active instanceof HTMLTableCellElement) || !active.hasAttribute("contenteditable")) return;

    const text = (event.clipboardData || window.clipboardData).getData("text");
    if (!text.includes("\t") && !text.includes("\n")) return;
    event.preventDefault();

    const rows = text.trimEnd().split(/\r?\n/).map((row) => row.split("\t"));
    let tableRow = active.parentElement;
    const startIndex = Array.from(tableRow.children).indexOf(active);

    rows.forEach((values) => {
      if (!tableRow) return;
      values.forEach((value, offset) => {
        const target = tableRow.children[startIndex + offset];
        if (target?.hasAttribute("contenteditable")) target.innerText = value;
      });
      tableRow = tableRow.nextElementSibling;
    });
  });
})(jQuery);
