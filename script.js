document.addEventListener('DOMContentLoaded', function() {
    const uploadForm = document.getElementById('uploadForm');
    const uploadStatus = document.getElementById('uploadStatus');
    const fileInput = document.getElementById('imageUpload');
    const uploadButton = document.getElementById('uploadButton');
    
    // Handle file selection
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            const fileName = this.files[0].name;
            const fileLabel = document.querySelector('.file-input-wrapper label');
            fileLabel.textContent = fileName.length > 20 ? fileName.substring(0, 17) + '...' : fileName;
        }
    });
    
    // Handle form submission for real-time upload
    uploadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        // Check if file is selected
        if (fileInput.files.length === 0) {
            showUploadStatus('Please select an image file', 'error');
            return;
        }
        
        // Check file type
        const file = fileInput.files[0];
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!validTypes.includes(file.type)) {
            showUploadStatus('Only JPG, JPEG, and PNG files are allowed', 'error');
            return;
        }
        
        // Check file size (max 5MB)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            showUploadStatus('File size exceeds the maximum limit of 5MB', 'error');
            return;
        }
        
        // Disable the upload button and show loading state
        uploadButton.disabled = true;
        uploadButton.textContent = 'Uploading...';
        
        // Send AJAX request
        const xhr = new XMLHttpRequest();
        xhr.open('POST', uploadForm.action, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        showUploadStatus('Image uploaded successfully', 'success');
                        
                        // Create new image element to add to the album
                        const newImage = createImageElement(response.path, response.width, response.height);
                        
                        // Determine which column to add the image to
                        const firstColumn = document.querySelector('.book-spread:first-child .album-column:first-child');
                        const secondColumn = document.querySelector('.book-spread:first-child .album-column:last-child');
                        
                        // Count existing images and add to appropriate column
                        const firstColumnCount = firstColumn ? firstColumn.querySelectorAll('.album-item').length : 0;
                        
                        if (firstColumnCount < 3) {
                            if (firstColumn) {
                                firstColumn.prepend(newImage);
                            } else {
                                // Create new book spread if none exists
                                createNewBookSpread(newImage);
                            }
                        } else if (secondColumn && secondColumn.querySelectorAll('.album-item').length < 3) {
                            secondColumn.prepend(newImage);
                        } else {
                            // If both columns are full or don't exist, create a new spread
                            createNewBookSpread(newImage);
                        }
                        
                        // Reset the file input
                        resetFileInput();
                    } else {
                        showUploadStatus(response.message || 'Upload failed', 'error');
                    }
                } catch (e) {
                    showUploadStatus('An unexpected error occurred', 'error');
                }
            } else {
                showUploadStatus('Server error: ' + xhr.status, 'error');
            }
            
            // Re-enable upload button
            uploadButton.disabled = false;
            uploadButton.textContent = 'Upload';
        };
        
        xhr.onerror = function() {
            showUploadStatus('Network error occurred', 'error');
            uploadButton.disabled = false;
            uploadButton.textContent = 'Upload';
        };
        
        xhr.send(formData);
    });
    
    // Function to create a new image element
    function createImageElement(src, width, height) {
        const div = document.createElement('div');
        div.className = 'album-item ' + (height > width ? 'portrait' : 'landscape');
        
        const img = document.createElement('img');
        img.src = src;
        img.alt = 'Photo';
        
        div.appendChild(img);
        return div;
    }
    
    // Function to show upload status
    function showUploadStatus(message, type) {
        uploadStatus.textContent = message;
        uploadStatus.className = type === 'error' ? 'error' : 'success';
        
        // Clear status after 5 seconds
        setTimeout(() => {
            uploadStatus.textContent = '';
            uploadStatus.className = '';
        }, 5000);
    }
    
    // Function to create a new book spread
    function createNewBookSpread(imageElement) {
        const albumContainer = document.querySelector('.album-container');
        
        // Create new book spread
        const newSpread = document.createElement('div');
        newSpread.className = 'book-spread';
        
        // Create left column
        const leftColumn = document.createElement('div');
        leftColumn.className = 'album-column';
        leftColumn.appendChild(imageElement);
        
        // Create right column
        const rightColumn = document.createElement('div');
        rightColumn.className = 'album-column';
        
        // Add columns to spread
        newSpread.appendChild(leftColumn);
        newSpread.appendChild(rightColumn);
        
        // Add new spread to the beginning of album container
        if (albumContainer.firstChild) {
            albumContainer.insertBefore(newSpread, albumContainer.firstChild);
        } else {
            albumContainer.appendChild(newSpread);
        }
        
        // Add flip animation
        setTimeout(() => {
            newSpread.style.animation = 'flip-in 0.8s ease-out forwards';
        }, 10);
        
        return newSpread;
    }
    
    // Function to reset file input
    function resetFileInput() {
        fileInput.value = '';
        document.querySelector('.file-input-wrapper label').textContent = 'Choose an image';
    }
    
    // Add animation and page-turn effects
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('pagination-button') && !e.target.classList.contains('disabled')) {
            e.preventDefault();
            
            const bookSpreads = document.querySelectorAll('.book-spread');
            const direction = e.target.classList.contains('next') ? 'next' : 'prev';
            
            // Add flip animation to all book spreads
            bookSpreads.forEach(spread => {
                spread.style.animation = direction === 'next' ? 
                    'flip-out-left 0.5s ease-in forwards' : 
                    'flip-out-right 0.5s ease-in forwards';
            });
            
            // Navigate to the new page after animation completes
            setTimeout(() => {
                window.location.href = e.target.href;
            }, 500);
        }
    });
});
