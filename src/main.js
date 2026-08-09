const form = document.querySelector("[data-contact-form]");

if (form) {
  form.addEventListener("submit", (event) => {
    event.preventDefault();
    const data = new FormData(form);
    const subject = encodeURIComponent(`Project enquiry from ${data.get("name")}`);
    const body = encodeURIComponent(`${data.get("message")}\n\nFrom: ${data.get("name")} (${data.get("email")})`);
    document.querySelector("[data-form-status]").textContent = "Your email app is opening — I look forward to hearing from you.";
    window.location.href = `mailto:hello@alexmorrow.design?subject=${subject}&body=${body}`;
  });
}

document.querySelectorAll("[data-year]").forEach((node) => {
  node.textContent = new Date().getFullYear();
});
