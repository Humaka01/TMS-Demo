document.addEventListener("DOMContentLoaded", function () {
  const notificationBell = document.getElementById("notification-bell");
  const notificationDropdown = document.getElementById("notification-dropdown");

  notificationBell.addEventListener("click", function (event) {
    event.stopPropagation();
    if (notificationDropdown.style.display === "block") {
      notificationDropdown.style.display = "none";
    } else {
      fetchNotifications(notificationDropdown);
    }
  });
  document.addEventListener("click", function () {
    notificationDropdown.style.display = "none";
  });
});

function fetchNotifications(notificationDropdown) {
  fetch("/php/get_notifications.php")
    .then((response) => response.text())
    .then((data) => {
      if (data.trim() === "") {
        notificationDropdown.innerHTML = "<ul><li>No notifications</li></ul>";
      } else {
        notificationDropdown.innerHTML = data;
      }
      notificationDropdown.style.display = "block";
    })
    .catch((error) => {
      console.error("Error fetching notifications:", error);
    });
}

document.addEventListener("DOMContentLoaded", function () {
  function updateNotificationBell(unreadCount) {
    const notificationBellIcon = document.getElementById("notification-bell");
    const notificationBadge = document.getElementById("notification-badge");
    if (unreadCount > 0) {
      notificationBellIcon.style.color = "red";
      notificationBadge.textContent = unreadCount;
      notificationBadge.style.display = "block";
    } else {
      notificationBellIcon.style.color = "black";
      notificationBadge.style.display = "none";
    }
  }

  fetch("/php/check_notifications.php")
    .then((response) => response.json())
    .then((data) => {
      const unreadCount = data.unread_count;
      updateNotificationBell(unreadCount);
    })
    .catch((error) => {
      console.error("Error checking notifications:", error);
    });
});

function showNotification(message, color = "#00CC00D2") {
  const notification = document.getElementById(`notification`);
  notification.textContent = message;
  notification.style.backgroundColor = color;
  notification.style.display = "block";
  setTimeout(() => {
    notification.style.display = "none";
  }, 5000); // Hide after x000 milliseconds (5 seconds)
}
