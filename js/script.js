document
  .getElementById("startDateFilter")
  .addEventListener("input", applyFilters);
document
  .getElementById("endDateFilter")
  .addEventListener("input", applyFilters);
document
  .getElementById("statusFilter")
  .addEventListener("change", applyFilters);
let tasks = [];

fetch("../php/dashboard.php")
  .then((response) => response.json())
  .then((data) => {
    tasks = data.tasks;
    const user = data.user;
    const id = user.id;
    const userName = user.name;
    const idElement = document.getElementById("id");
    const userNameElement = document.getElementById("name");
    const emailElement = document.getElementById("email");
    idElement.textContent = id;
    userNameElement.textContent = data.user.name;
    emailElement.textContent = data.user.email;
    populateAssignees();
    generateTaskCards(tasks);
    const taskStatistics = calculateTaskStatistics(tasks);
    displayTaskStatistics(taskStatistics);
  })
  .catch((error) => console.error("Error fetching user data:", error));

function calculateTaskStatistics(tasks) {
  const totalTasks = tasks.length;
  const tasksToDo = tasks.filter((task) => task.status === "To do").length;
  const tasksInProgress = tasks.filter(
    (task) => task.status === "In Progress"
  ).length;
  const tasksDone = tasks.filter((task) => task.status === "Completed").length;
  const completionPercentage =
    totalTasks > 0 ? ((tasksDone / totalTasks) * 100).toFixed(1) + "%" : "N/A";
  return {
    totalTasks,
    tasksToDo,
    tasksInProgress,
    tasksDone,
    completionPercentage,
  };
}

function displayTaskStatistics(statistics) {
  const totalTasksElement = document.getElementById("totalTasks");
  const tasksToDoElement = document.getElementById("tasksToDo");
  const tasksInProgressElement = document.getElementById("tasksInProgress");
  const tasksDoneElement = document.getElementById("tasksDone");
  const completionPercentageElement = document.getElementById(
    "completionPercentage"
  );
  totalTasksElement.textContent = statistics.totalTasks;
  tasksToDoElement.textContent = statistics.tasksToDo;
  tasksInProgressElement.textContent = statistics.tasksInProgress;
  tasksDoneElement.textContent = statistics.tasksDone;
  completionPercentageElement.textContent = statistics.completionPercentage;
}

function applyFilters() {
  const startDate = document.getElementById("startDateFilter").value;
  const endDate = document.getElementById("endDateFilter").value;
  const status = document.getElementById("statusFilter").value;
  const filteredTasks = tasks.filter((task) => {
    const taskDueDate = new Date(task.due_date);
    const isDueDateInRange =
      (!startDate || taskDueDate >= new Date(startDate)) &&
      (!endDate || taskDueDate <= new Date(endDate));
    const isStatusMatching = !status || task.status === status;
    return isDueDateInRange && isStatusMatching;
  });
  updateTaskCards(filteredTasks);
}

function resetFilters() {
  document.getElementById("startDateFilter").value = "";
  document.getElementById("endDateFilter").value = "";
  document.getElementById("statusFilter").value = "";
  updateTaskCards(tasks);
}

function updateTaskCards(filteredTasks) {
  const taskList = document.querySelector(".task-list");
  taskList.innerHTML = ""; // Clear the existing task cards

  if (filteredTasks.length === 0) {
    const noTasksMessage = document.createElement("p");
    noTasksMessage.textContent = "No tasks match the selected filters.";
    taskList.appendChild(noTasksMessage);
  } else {
    filteredTasks.forEach((task) => {
      const taskCard = document.createElement("div");
      taskCard.className = "task-card";

      const title = document.createElement("h3");
      title.textContent = task.title;

      const description = document.createElement("p");
      description.textContent = task.description;

      const dueDate = document.createElement("p");
      dueDate.innerHTML = `Due Date: <span class="due-date">${task.due_date}</span>`;

      const status = document.createElement("p");
      status.innerHTML = `Status: <span class="task-status">${task.status}</span>`;

      const editButton = document.createElement("button");
      editButton.className = "edit-button";
      editButton.textContent = "Edit Task";
      editButton.onclick = () => redirectTaskDetailsPage(task.id);

      taskCard.appendChild(title);
      taskCard.appendChild(description);
      taskCard.appendChild(dueDate);
      taskCard.appendChild(status);
      taskCard.appendChild(editButton);
      taskList.appendChild(taskCard);
    });
  }
}

function populateAssignees() {
  const assigneeSelect = document.getElementById("assigneeSelect");
  fetch("../php/get_assignees.php")
    .then((response) => response.json())
    .then((data) => {
      assigneeSelect.innerHTML = "";
      data.forEach((assignee) => {
        const option = document.createElement("option");
        option.value = assignee.id;
        option.textContent = `${assignee.id}: ${assignee.name}`;
        assigneeSelect.appendChild(option);
      });
    })
    .catch((error) => console.error("Error fetching assignees:", error));
}

function createTask(event) {
  event.preventDefault();
  const taskTitle = document.querySelector('input[name="taskTitle"]').value;
  const taskDescription = document.querySelector(
    'textarea[name="taskDescription"]'
  ).value;
  const dueDate = document.querySelector('input[name="dueDate"]').value;
  const userNameElement = document.getElementById("name");
  const userName = userNameElement.textContent;
  const assigneeSelect = document.getElementById("assigneeSelect");
  const selectedAssigneeId = assigneeSelect.value;
  const taskData = {
    title: taskTitle,
    description: taskDescription,
    due_date: dueDate,
    status: "To do",
    assignee_id: selectedAssigneeId,
    last_edit_by: `${id.innerHTML}:${userName}`,
  };

  // Send the data to the server using Fetch API
  fetch("../php/create_task.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(taskData),
  })
    .then((result) => {
      if (result.status === 200) {
        showNotification("Task created successfully!");
        clearFormFields();
      } else {
        console.error("Error creating task:", result.status);
      }
    })
    .catch((error) => {
      console.error("Error creating task:", JSON.stringify(error));
    });
}

function generateTaskCards(tasks) {
  const taskList = document.querySelector(".task-list");

  tasks.forEach((task) => {
    const taskCard = document.createElement("div");
    taskCard.className = "task-card";

    const title = document.createElement("h3");
    title.textContent = task.title;

    const description = document.createElement("p");
    description.textContent = task.description;

    const dueDate = document.createElement("p");
    dueDate.innerHTML = `Due Date: <span class="due-date">${task.due_date}</span>`;

    const status = document.createElement("p");
    status.innerHTML = `Status: <span class="task-status">${task.status}</span>`;

    const editButton = document.createElement("button");
    editButton.className = "edit-button";
    editButton.textContent = "Edit Task";
    editButton.onclick = () => redirectTaskDetailsPage(task.id);

    taskCard.appendChild(title);
    taskCard.appendChild(description);
    taskCard.appendChild(dueDate);
    taskCard.appendChild(status);
    taskCard.appendChild(editButton);
    taskList.appendChild(taskCard);
  });
}

function clearFormFields() {
  const taskTitleInput = document.querySelector('input[name="taskTitle"]');
  const taskDescriptionInput = document.querySelector(
    'textarea[name="taskDescription"]'
  );
  const dueDateInput = document.querySelector('input[name="dueDate"]');
  taskTitleInput.value = "";
  taskDescriptionInput.value = "";
  dueDateInput.value = "";
}

function redirectTaskDetailsPage(taskId) {
  window.location.href = `task-details.html?task_id=${taskId}`;
}

function logout() {
  window.location.href = "/php/logout.php";
}
