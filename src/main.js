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
  form.addEventListener("submit", (event) => {
    event.preventDefault();
    const data = new FormData(form);
    const subject = encodeURIComponent(`Project enquiry from ${data.get("name")}`);
    const body = encodeURIComponent(`${data.get("message")}\n\nFrom: ${data.get("name")} (${data.get("email")})`);
    document.querySelector("[data-form-status]").textContent = "Your email app is opening — I look forward to hearing from you.";
    window.location.href = `mailto:info@nnamdi.ng?subject=${subject}&body=${body}`;
  });
}

document.querySelectorAll("[data-year]").forEach((node) => {
  node.textContent = new Date().getFullYear();
});
