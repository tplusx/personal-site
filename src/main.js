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

// Twenty subjects × ten punchlines provide a lightweight bucket of 200 jokes.
const jokeSubjects = [
  "designer", "developer", "pixel", "typeface", "wireframe", "browser", "keyboard",
  "website", "button", "cursor", "stylesheet", "server", "domain", "prototype",
  "portfolio", "grid", "logo", "brief", "laptop", "creative director",
];

const jokePunchlines = [
  "it needed more space to think.",
  "someone promised there would be cookies.",
  "the other side had better contrast.",
  "it was tired of being put in a box.",
  "its current position was only relative.",
  "it wanted to make a bold first impression.",
  "the deadline was approaching from behind.",
  "it heard the margins were generous over there.",
  "it was following a very persuasive call to action.",
  "apparently that was part of the user journey.",
];

const joke = document.querySelector("[data-joke]");
if (joke) {
  const subject = jokeSubjects[Math.floor(Math.random() * jokeSubjects.length)];
  const punchline = jokePunchlines[Math.floor(Math.random() * jokePunchlines.length)];
  joke.textContent = `Why did the ${subject} cross the road? Because ${punchline}`;
}

const form = document.querySelector("[data-contact-form]");
if (form) {
  form.addEventListener("submit", (event) => {
    event.preventDefault();
    const data = new FormData(form);
    const subject = encodeURIComponent(`Project enquiry from ${data.get("name")}`);
    const body = encodeURIComponent(`${data.get("message")}\n\nFrom: ${data.get("name")} (${data.get("email")})`);
    document.querySelector("[data-form-status]").textContent = "Your email app is opening — I look forward to hearing from you.";
    window.location.href = `mailto:hello@nnamdi.design?subject=${subject}&body=${body}`;
  });
}

document.querySelectorAll("[data-year]").forEach((node) => {
  node.textContent = new Date().getFullYear();
});
