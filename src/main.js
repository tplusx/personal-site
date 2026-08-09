import { jokes } from "./jokes.js";

const root = document.documentElement;
const themeToggle = document.querySelector("[data-theme-toggle]");
const themeLabel = document.querySelector("[data-theme-label]");
const themeColor = document.querySelector('meta[name="theme-color"]');

const setTheme = (theme) => {
  const isLight = theme === "light";
  root.dataset.theme = theme;
  themeToggle?.setAttribute("aria-pressed", String(isLight));
  themeToggle?.setAttribute("aria-label", `Switch to ${isLight ? "dark" : "light"} mode`);
  if (themeLabel) themeLabel.textContent = isLight ? "Dark" : "Light";
  if (themeColor) themeColor.content = isLight ? "#f3f0e9" : "#151319";
};

setTheme(localStorage.getItem("theme") === "light" ? "light" : "dark");

themeToggle?.addEventListener("click", () => {
  const nextTheme = root.dataset.theme === "light" ? "dark" : "light";
  localStorage.setItem("theme", nextTheme);
  setTheme(nextTheme);
});

const joke = document.querySelector("[data-joke]");
if (joke) {
  joke.textContent = jokes[Math.floor(Math.random() * jokes.length)];
}

const form = document.querySelector("[data-contact-form]");
if (form) {
  const status = document.querySelector("[data-form-status]");
  const deliveryStatus = new URLSearchParams(window.location.search).get("status");
  if (deliveryStatus === "sent") status.textContent = "Thanks — your enquiry has been sent to Nnamdi.";
  if (deliveryStatus === "error") status.textContent = "Your enquiry could not be sent. Please email info@nnamdi.ng instead.";

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    const submit = form.querySelector("[type=submit]");
    status.textContent = "Sending your enquiry…";
    submit.disabled = true;

    try {
      const response = await fetch(form.action, {
        method: "POST",
        body: new FormData(form),
        headers: { Accept: "application/json" },
      });
      const result = await response.json();
      if (!response.ok) throw new Error(result.message || "Your enquiry could not be sent.");
      form.reset();
      status.textContent = "Thanks — your enquiry has been sent to Nnamdi.";
    } catch (error) {
      status.textContent = error.message || "Something went wrong. Please email info@nnamdi.ng instead.";
    } finally {
      submit.disabled = false;
    }
  });
}

document.querySelectorAll("[data-year]").forEach((node) => {
  node.textContent = new Date().getFullYear();
});
