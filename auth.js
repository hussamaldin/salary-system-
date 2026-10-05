const page = window.location.pathname.split("/").pop().toLowerCase();

if (
    page !== "login.html" &&
    page !== "logout.html" &&
    localStorage.getItem("loggedIn") !== "true"
) {
    window.location.replace("login.html");
}