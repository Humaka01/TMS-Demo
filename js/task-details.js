document.addEventListener("DOMContentLoaded", function () {
    fetchTaskDetails();
    const updateStatusButton = document.getElementById("updateStatusButton");
    updateStatusButton.addEventListener("click", updateTaskStatus);
    const addCommentButton = document.getElementById("addCommentButton");
    addCommentButton.addEventListener("click", handleAddComment);
    const addUpdateButton = document.getElementById("addUpdateButton");
    addUpdateButton.addEventListener("click", handleAddUpdate);
    const uploadButton = document.getElementById("uploadButton");
    uploadButton.addEventListener("click", handleUploadFile);
});

function fetchTaskDetails() {
    const urlParams = new URLSearchParams(window.location.search);
    const taskId = urlParams.get("task_id");

    fetch(`/php/update_task_details.php?task_id=${taskId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(data => {
            const parsedData = JSON.parse(data);
            const {
                id,
                title,
                description,
                status,
                due_date,
                assignee_id,
                comments,
                updates,
                last_edit_by,
                assignee_name,
            } = parsedData;

            document.querySelector('.task-title').textContent = title;
            document.querySelector('.task-description').textContent = `Description: ${description}`;
            document.querySelector('.task-id').textContent = `Task ID: ${id}`;
            document.querySelector('.task-due-date').textContent = `Due date: ${due_date}`;
            document.querySelector('.task-assignee-id').textContent = `Created by: ${assignee_id}: ${assignee_name}`;
            document.querySelector('.task-last-edit-by').textContent = `Last edited by: ${last_edit_by}`;

            const currentStatus = status;
            const statusSelect = document.getElementById('statusSelect');
            for (let i = 0; i < statusSelect.options.length; i++) {
                if (statusSelect.options[i].value === currentStatus) {
                    statusSelect.options[i].selected = true;
                    break;
                }
            }

            // Due date styling
            const taskDueDateElement = document.querySelector('.task-due-date');
            const currentDate = new Date();
            const taskDueDate = new Date(due_date);
            const daysUntilDue = Math.ceil((taskDueDate - currentDate) / (1000 * 60 * 60 * 24));
            taskDueDateElement.textContent = `Due Date: ${due_date}`;
            if (daysUntilDue <= 30 && daysUntilDue >= 7) {
                taskDueDateElement.style.color = '#FF7300';
            } else if (daysUntilDue < 7) {
                taskDueDateElement.style.color = '#ff4b5c';
            } else {
                taskDueDateElement.style.color = '#2ecc71';
            }

            fetchAttachments(id);
            populateComments(comments);
            populateUpdates(updates);
        })
        .catch(error => {
            console.error('Error fetching or parsing data:', error);
        });
}


function getTaskIDFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get("task_id");
}

// ---------------  UPDATE TASK STATUS --------------------------
function updateTaskStatus() {
    // Retrieve the selected status from the dropdown
    const statusSelect = document.getElementById('statusSelect');
    const newStatus = statusSelect.value;
    const taskId = getTaskIDFromURL();

    fetch('/php/update_task_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            task_id: taskId,
            new_status: newStatus
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        const updatedStatus = data.status;

        // Update the UI with the new status
        const taskStatusElement = document.querySelector('.task-status');
        taskStatusElement.textContent = `Status: ${updatedStatus}`;
        showNotification('Task status updated successfully!');
        fetchLastEditDetails();
    })
    .catch(error => {
        console.error('Error updating task status:', error);
    });
}

// ---------------  COMMENTS --------------------------
function fetchComments(taskId) {
    fetch(`/php/get_comments.php?task_id=${taskId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (Array.isArray(data.comments)) {
                populateComments(data.comments);
            } else {
                console.error('Invalid comments data:', data);
            }
        })
        .catch(error => console.error('Error fetching comments:', error));
}

function populateComments(comments) {
    const commentList = document.querySelector('.comment-list');
    commentList.innerHTML = "";

    if (comments === null || comments.length === 0) {
        const li = document.createElement('li');
        li.textContent = "No added comments yet.";
        commentList.appendChild(li);
    } else {
        comments.forEach(commentObj => {
            const li = document.createElement('li');
            li.textContent = `${commentObj.comment} [${commentObj.created_at}]`;
            commentList.appendChild(li);
        });
    }
}

function handleAddComment() {
    const commentTextarea = document.getElementById("comments");
    const comment = commentTextarea.value.trim();
    if (comment !== "") {
        const taskId = getTaskIDFromURL();
        const formData = new FormData();
        formData.append('task_id', taskId);
        formData.append('comment', comment);

        fetch('/php/add_comment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            commentTextarea.value = "";
            showNotification('Comment added successfully!');
            // Fetch the updated comments and update the UI
            fetchComments(taskId);
            fetchLastEditDetails();
        })
        .catch(error => console.error('Error adding comment:', error));
    }
}

// ---------------  UPDATES --------------------------
function fetchUpdates(taskId) {
    fetch(`/php/get_updates.php?task_id=${taskId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            const updates = data.updates;
            populateUpdates(updates);
        })
        .catch(error => console.error('Error fetching updates:', error));
}

function populateUpdates(updates) {
    const updateList = document.querySelector('.update-list');
    updateList.innerHTML = "";

    if (updates === null || updates.length === 0) {
        const li = document.createElement('li');
        li.textContent = "No updates on this task yet.";
        updateList.appendChild(li);
    } else {
        updates.forEach(update => {
            const li = document.createElement('li');
            li.textContent = `${update.update_text} [${update.created_at}]`;
            updateList.appendChild(li);
        });
    }
}

function handleAddUpdate() {
    const updateTextarea = document.getElementById("updates");
    const updateText = updateTextarea.value.trim();
    if (updateText !== "") {
        const taskId = getTaskIDFromURL();
        const formData = new FormData();
        formData.append('task_id', taskId);
        formData.append('update_text', updateText);

        fetch('/php/add_update.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            updateTextarea.value = "";
            showNotification('Update added successfully!');
            fetchUpdates(taskId);
            fetchLastEditDetails();
        })
        .catch(error => console.error('Error adding update:', error));
    }
}

// ---------------  ATTACHMENTS --------------------------
function handleUploadFile() {
    const fileInput = document.getElementById("attachments");
    const taskId = getTaskIDFromURL();
    const file = fileInput.files[0];

    if (file) {
        const formData = new FormData();
        formData.append('task_id', taskId);
        formData.append('attachment', file);

        fetch('/php/upload_file.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            fileInput.value = "";
            return response.json();
        })
        .then(data => {
            showNotification('File uploaded successfully!');
            fetchLastEditDetails();
            fetchAttachments(taskId);
        })
        .catch(error => {
            showNotification('Failed to upload file!', '#CC0000D2');
            console.error('Error uploading file:', error)
        });
    }
}

async function fetchAttachments(taskId) {
    const attachmentList = document.querySelector('.attachment-list');
    attachmentList.innerHTML = "";

    fetch(`/php/get_attachments.php?task_id=${taskId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(attachmentData => {
            const attachments = attachmentData.attachments;

            if (attachments === null || attachments.length === 0) {
                const li = document.createElement('li');
                li.textContent = "No attached files have been added to this task.";
                attachmentList.appendChild(li);
            } else {
                attachments.forEach(attachment => {
                    const li = document.createElement('li');
                    const link = document.createElement('a');
                    link.href = `/php/download_attachment.php?attachment_id=${attachment.id}`;
                    link.textContent = attachment.file_name;
                    li.appendChild(link);
                    attachmentList.appendChild(li);
                });
            }
        })
        .catch(error => {
            console.error('Error fetching attachments:', error);
        });
}

function fetchLastEditDetails() {
    const urlParams = new URLSearchParams(window.location.search);
    const taskId = urlParams.get("task_id");

    fetch(`/php/update_task_details.php?task_id=${taskId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(data => {
            const parsedData = JSON.parse(data);
            const {
                last_edit_by
            } = parsedData;

            document.querySelector('.task-last-edit-by').textContent = `Last edited by: ${last_edit_by}`;
        })
        .catch(error => {
            console.error('Error fetching or parsing data:', error);
        });
}
