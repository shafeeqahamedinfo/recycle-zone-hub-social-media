        </div>

        <!-- Floating Action Button -->
        <?php if (isLoggedIn()): ?>
       <!--  <button class="fab" onclick="openPostModal()">
            <i class="fas fa-plus"></i>
        </button> -->
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Like functionality with enhanced animations
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.like-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const postId = this.dataset.postId;
                    likePost(postId, this);
                });
            });

            // Add scroll animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Observe all cards for scroll animation
            document.querySelectorAll('.card').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = 'all 0.6s ease';
                observer.observe(card);
            });
        });

        function likePost(postId, button) {
            // Add loading animation
            const originalHTML = button.innerHTML;
            button.innerHTML = '<div class="loading"></div>';
            button.disabled = true;

            fetch('like_post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'post_id=' + postId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const likeCount = button.querySelector('.like-count');
                    likeCount.textContent = data.likes;
                    
                    if (data.liked) {
                        button.classList.add('liked');
                        // Add confetti effect
                        createConfetti(button);
                    } else {
                        button.classList.remove('liked');
                    }
                }
            })
            .finally(() => {
                button.innerHTML = originalHTML;
                button.disabled = false;
            });
        }

        function createConfetti(element) {
            const rect = element.getBoundingClientRect();
            const x = rect.left + rect.width / 2;
            const y = rect.top + rect.height / 2;

            for (let i = 0; i < 10; i++) {
                const confetti = document.createElement('div');
                confetti.innerHTML = '❤️';
                confetti.style.position = 'fixed';
                confetti.style.left = x + 'px';
                confetti.style.top = y + 'px';
                confetti.style.fontSize = '20px';
                confetti.style.zIndex = '1000';
                confetti.style.pointerEvents = 'none';
                confetti.style.animation = `confettiFall ${Math.random() * 1 + 0.5}s ease-out forwards`;
                
                document.body.appendChild(confetti);

                setTimeout(() => {
                    confetti.remove();
                }, 1000);
            }
        }

        // Add confetti animation to CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes confettiFall {
                0% {
                    transform: translate(0, 0) rotate(0deg);
                    opacity: 1;
                }
                100% {
                    transform: translate(${Math.random() * 100 - 50}px, 100px) rotate(360deg);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);

        function openPostModal() {
            // Create a modal for quick post
            const modal = document.createElement('div');
            modal.className = 'modal fade show d-block';
            modal.style.background = 'rgba(0,0,0,0.5)';
            modal.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title gradient-text">Create Post</h5>
                            <button type="button" class="btn-close" onclick="this.closest('.modal').remove()"></button>
                        </div>
                        <div class="modal-body">
                            <form id="quickPostForm">
                                <div class="mb-3">
                                    <textarea class="form-control" name="content" rows="4" placeholder="What's on your mind?" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Post</button>
                            </form>
                        </div>
                    </div>
                </div>
            `;

            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });

            document.getElementById('quickPostForm')?.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                // Submit form via AJAX
                fetch('index.php', {
                    method: 'POST',
                    body: formData
                }).then(() => {
                    location.reload();
                });
            });

            document.body.appendChild(modal);
        }

        // Typing indicator for messages
        function showTypingIndicator() {
            const indicator = document.createElement('div');
            indicator.className = 'message-bubble message-received typing-indicator';
            indicator.innerHTML = `
                <div class="typing-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            `;
            document.querySelector('.card-body').appendChild(indicator);
        }

        // Auto-scroll to bottom in messages
        function scrollToBottom() {
            const messageContainer = document.querySelector('.card-body');
            if (messageContainer) {
                messageContainer.scrollTop = messageContainer.scrollHeight;
            }
        }

        // Add typing indicator styles
        const typingStyles = document.createElement('style');
        typingStyles.textContent = `
            .typing-indicator {
                background: #f1f5f9;
                padding: 10px 15px;
            }
            .typing-dots {
                display: flex;
                gap: 4px;
            }
            .typing-dots span {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: var(--text-light);
                animation: typing 1.4s infinite ease-in-out;
            }
            .typing-dots span:nth-child(1) { animation-delay: -0.32s; }
            .typing-dots span:nth-child(2) { animation-delay: -0.16s; }
            @keyframes typing {
                0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
                40% { transform: scale(1); opacity: 1; }
            }
        `;
        document.head.appendChild(typingStyles);

        // Initialize scroll to bottom for messages page
        if (window.location.pathname.includes('messages.php')) {
            setTimeout(scrollToBottom, 100);
        }
    
// ... existing JavaScript ...

// Post Type Handling
document.addEventListener('DOMContentLoaded', function() {
    initializePostTypeSelector();
    initializeFileUploads();
    initializeLocationPicker();
    
    // ... existing code ...
});

function initializePostTypeSelector() {
    const typeButtons = document.querySelectorAll('.post-type-btn');
    const postTypeInput = document.getElementById('postType');
    const sections = document.querySelectorAll('.post-type-section');
    const contentTextarea = document.getElementById('postContent');
    const actionButtons = document.getElementById('actionButtons');
    
    typeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const type = this.dataset.type;
            
            // Update active button
            typeButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Update hidden input
            postTypeInput.value = type;
            
            // Show/hide sections
            sections.forEach(section => section.style.display = 'none');
            
            // Update content placeholder
            const placeholders = {
                text: "What's on your mind?",
                image: "Describe your image...",
                video: "Tell us about this video...",
                location: "Share your location experience..."
            };
            contentTextarea.placeholder = placeholders[type] + ', ' + document.querySelector('.flex-grow-1 h6').textContent + '?';
            
            // Show relevant section
            if (type !== 'text') {
                document.getElementById(type + 'Section').style.display = 'block';
            }
            
            // Update action buttons
            updateActionButtons(type);
            
            // Clear file inputs when switching types
            if (type !== 'image') {
                document.getElementById('imageFile').value = '';
                document.getElementById('imagePreview').innerHTML = '';
            }
            if (type !== 'video') {
                document.getElementById('videoFile').value = '';
                document.getElementById('videoPreview').innerHTML = '';
            }
        });
    });
}

function updateActionButtons(type) {
    const actionButtons = document.getElementById('actionButtons');
    const buttons = {
        text: `
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addEmoji()">
                <i class="far fa-smile"></i>
            </button>
            <button type="button" class="btn btn-outline-success btn-sm">
                <i class="fas fa-hashtag"></i>
            </button>
        `,
        image: `
            <button type="button" class="btn btn-outline-info btn-sm" onclick="openCamera()">
                <i class="fas fa-camera"></i> Camera
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="openGallery()">
                <i class="fas fa-images"></i> Gallery
            </button>
        `,
        video: `
            <button type="button" class="btn btn-outline-info btn-sm" onclick="openCamera('video')">
                <i class="fas fa-video"></i> Record
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="openGallery('video')">
                <i class="fas fa-film"></i> Library
            </button>
        `,
        location: `
            <button type="button" class="btn btn-outline-info btn-sm" onclick="getCurrentLocation()">
                <i class="fas fa-crosshairs"></i> Current Location
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="searchLocation()">
                <i class="fas fa-search"></i> Search
            </button>
        `
    };
    
    actionButtons.innerHTML = buttons[type] || '';
}

function initializeFileUploads() {
    // Image upload
    const imageUploadArea = document.getElementById('imageUploadArea');
    const imageFileInput = document.getElementById('imageFile');
    const imagePreview = document.getElementById('imagePreview');
    
    imageUploadArea.addEventListener('click', () => imageFileInput.click());
    imageUploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        imageUploadArea.classList.add('dragover');
    });
    imageUploadArea.addEventListener('dragleave', () => {
        imageUploadArea.classList.remove('dragover');
    });
    imageUploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        imageUploadArea.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            imageFileInput.files = e.dataTransfer.files;
            handleImageUpload(e.dataTransfer.files[0]);
        }
    });
    
    imageFileInput.addEventListener('change', (e) => {
        if (e.target.files.length) {
            handleImageUpload(e.target.files[0]);
        }
    });
    
    // Video upload (similar to image)
    const videoUploadArea = document.getElementById('videoUploadArea');
    const videoFileInput = document.getElementById('videoFile');
    const videoPreview = document.getElementById('videoPreview');
    
    videoUploadArea.addEventListener('click', () => videoFileInput.click());
    videoUploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        videoUploadArea.classList.add('dragover');
    });
    videoUploadArea.addEventListener('dragleave', () => {
        videoUploadArea.classList.remove('dragover');
    });
    videoUploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        videoUploadArea.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            videoFileInput.files = e.dataTransfer.files;
            handleVideoUpload(e.dataTransfer.files[0]);
        }
    });
    
    videoFileInput.addEventListener('change', (e) => {
        if (e.target.files.length) {
            handleVideoUpload(e.target.files[0]);
        }
    });
}

function handleImageUpload(file) {
    if (!file.type.startsWith('image/')) {
        alert('Please select an image file');
        return;
    }
    
    const reader = new FileReader();
    reader.onload = (e) => {
        const preview = document.createElement('div');
        preview.className = 'preview-item';
        preview.innerHTML = `
            <style>
            @keyframes popIn {
                0% { opacity: 0; transform: scale(0.92) translateY(8px); }
                100% { opacity: 1; transform: scale(1) translateY(0); }
            }
            @keyframes floatBtn {
                0% { opacity: 0; transform: translateY(6px) scale(0.95); }
                100% { opacity: 1; transform: translateY(0) scale(1); }
            }
            .preview-img { 
                max-width: 100%; 
                border-radius: 8px; 
                display: block; 
                box-shadow: 0 6px 18px rgba(0,0,0,0.12);
                animation: popIn .28s cubic-bezier(.2,.8,.2,1) both;
            }
            .remove-media {
                position: absolute;
                top: 8px;
                right: 8px;
                background: rgba(0,0,0,0.6);
                color: #fff;
                border: none;
                border-radius: 50%;
                width: 34px;
                height: 34px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                animation: floatBtn .26s ease both;
            }
            </style>

            <div style="position:relative; display:inline-block;">
                    <img src="${e.target.result}" alt="Preview" class="preview-img">
                    <button type="button" class="remove-media" onclick="removePreview(this)">
                            <i class="fas fa-times"></i>
                    </button>
            </div>
        `;
        document.getElementById('imagePreview').innerHTML = '';
        document.getElementById('imagePreview').appendChild(preview);
    };
    reader.readAsDataURL(file);
}

function handleVideoUpload(file) {
    if (!file.type.startsWith('video/')) {
        alert('Please select a video file');
        return;
    }
    
    const reader = new FileReader();
    reader.onload = (e) => {
        const preview = document.createElement('div');
        preview.className = 'preview-item';
        preview.innerHTML = `
            <video src="${e.target.result}" muted></video>
            <button type="button" class="remove-media" onclick="removePreview(this)">
                <i class="fas fa-times"></i>
            </button>
        `;
        document.getElementById('videoPreview').innerHTML = '';
        document.getElementById('videoPreview').appendChild(preview);
        
        // Auto-play preview
        const video = preview.querySelector('video');
        video.play();
    };
    reader.readAsDataURL(file);
}

function removePreview(button) {
    button.closest('.preview-item').remove();
    // Clear the file input
    if (button.closest('#imagePreview')) {
        document.getElementById('imageFile').value = '';
    } else if (button.closest('#videoPreview')) {
        document.getElementById('videoFile').value = '';
    }
}

// Location Picker
let map;
let marker;

function initializeLocationPicker() {
    // Initialize map (using Leaflet.js - include in header)
    map = L.map('location-map').setView([0, 0], 2);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Add click event to map
    map.on('click', function(e) {
        updateLocation(e.latlng.lat, e.latlng.lng);
    });
    
    // Try to get user's current location
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            map.setView([lat, lng], 13);
            updateLocation(lat, lng);
        });
    }
}

function updateLocation(lat, lng) {
    // Update coordinates display
    document.getElementById('latDisplay').value = lat.toFixed(6);
    document.getElementById('lngDisplay').value = lng.toFixed(6);
    
    // Update hidden inputs
    document.getElementById('latitude').value = lat;
    document.getElementById('longitude').value = lng;
    
    // Remove existing marker
    if (marker) {
        map.removeLayer(marker);
    }
    
    // Add new marker
    marker = L.marker([lat, lng]).addTo(map);
    
    // Get location name
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18`)
        .then(response => response.json())
        .then(data => {
            const locationName = data.display_name || `Location (${lat.toFixed(4)}, ${lng.toFixed(4)})`;
            document.getElementById('locationName').value = locationName;
            document.getElementById('locationNameInput').value = locationName;
            
            // Update marker popup
            marker.bindPopup(locationName).openPopup();
        });
}

function getCurrentLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            map.setView([lat, lng], 15);
            updateLocation(lat, lng);
        });
    } else {
        alert('Geolocation is not supported by this browser.');
    }
}

function searchLocation() {
    const query = prompt('Enter location to search:');
    if (query) {
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lng = parseFloat(data[0].lon);
                    map.setView([lat, lng], 15);
                    updateLocation(lat, lng);
                } else {
                    alert('Location not found');
                }
            });
    }
}

// Media Modal
function openMediaModal(src, type) {
    const modal = new bootstrap.Modal(document.getElementById('mediaModal'));
    const modalImage = document.getElementById('modalImage');
    const modalVideo = document.getElementById('modalVideo');
    
    modalImage.style.display = 'none';
    modalVideo.style.display = 'none';
    
    if (type === 'image') {
        modalImage.src = 'uploads/' + src;
        modalImage.style.display = 'block';
    } else if (type === 'video') {
        modalVideo.src = 'uploads/' + src;
        modalVideo.style.display = 'block';
    }
    
    modal.show();
}

function copyLocation(lat, lng) {
    navigator.clipboard.writeText(`${lat}, ${lng}`).then(() => {
        alert('Coordinates copied to clipboard!');
    });
}

// Additional utility functions
function addEmoji() {
    const emoji = prompt('Enter emoji:');
    if (emoji) {
        const textarea = document.getElementById('postContent');
        textarea.value += emoji;
    }
}

function openCamera(type = 'image') {
    alert('Camera functionality would open here. In a real app, this would access the device camera.');
}

function openGallery(type = 'image') {
    alert('Gallery would open here. In a real app, this would access the device gallery.');
}
</script>
<script>
// Profile Image Upload and Preview
function previewProfileImage(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validate file type
        if (!file.type.startsWith('image/')) {
            alert('Please select an image file');
            input.value = '';
            return;
        }
        
        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('File too large. Maximum size is 5MB.');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        
        reader.onload = function(e) {
            // Update preview image
            document.getElementById('profileImagePreview').src = e.target.result;
            
            // Show in modal for better preview
            document.getElementById('modalProfileImage').src = e.target.result;
            const modal = new bootstrap.Modal(document.getElementById('profileImageModal'));
            modal.show();
        }
        
        reader.readAsDataURL(file);
        
        // Show upload progress
        showUploadProgress();
    }
}

function previewCoverImage(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        if (!file.type.startsWith('image/')) {
            alert('Please select an image file');
            input.value = '';
            return;
        }
        
        if (file.size > 5 * 1024 * 1024) {
            alert('File too large. Maximum size is 5MB.');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        
        reader.onload = function(e) {
            // Update cover photo preview
            document.querySelector('.cover-photo').style.backgroundImage = `url(${e.target.result})`;
            
            // Show success message
            showNotification('Cover image preview updated. Click "Update Images" to save.', 'success');
        }
        
        reader.readAsDataURL(file);
    }
}

function showUploadProgress() {
    const progressBar = document.createElement('div');
    progressBar.className = 'upload-progress';
    progressBar.innerHTML = '<div class="upload-progress-bar" style="width: 0%"></div>';
    
    const form = document.querySelector('form');
    form.appendChild(progressBar);
    
    // Simulate progress (in real app, this would be actual upload progress)
    let progress = 0;
    const interval = setInterval(() => {
        progress += 10;
        progressBar.querySelector('.upload-progress-bar').style.width = progress + '%';
        
        if (progress >= 100) {
            clearInterval(interval);
            setTimeout(() => {
                progressBar.remove();
            }, 1000);
        }
    }, 100);
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show`;
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.querySelector('.card-body').insertBefore(notification, document.querySelector('.card-body').firstChild);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

function downloadProfileData() {
    // In a real application, this would generate a PDF or JSON file
    // For now, we'll show a confirmation message
    showNotification('Profile data export started. You will receive a download link shortly.', 'info');
    
    // Simulate export process
    setTimeout(() => {
        showNotification('Your profile data has been exported successfully!', 'success');
    }, 2000);
}

// Image cropping functionality (basic implementation)
function initializeImageCropper() {
    // This would integrate with a library like Cropper.js in a real application
    console.log('Image cropper initialized');
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeImageCropper();
    
    // Add click event to profile image for modal view
    document.getElementById('profileImagePreview').addEventListener('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('profileImageModal'));
        modal.show();
    });
});

// Drag and drop for profile image
document.addEventListener('DOMContentLoaded', function() {
    const profileImageContainer = document.querySelector('.profile-image-container');
    const profileImageInput = document.getElementById('profileImage');
    
    profileImageContainer.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.borderColor = 'var(--primary)';
        this.style.backgroundColor = 'rgba(99, 102, 241, 0.1)';
    });
    
    profileImageContainer.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.style.borderColor = '';
        this.style.backgroundColor = '';
    });
    
    profileImageContainer.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.borderColor = '';
        this.style.backgroundColor = '';
        
        if (e.dataTransfer.files.length) {
            profileImageInput.files = e.dataTransfer.files;
            previewProfileImage(profileImageInput);
        }
    });
});
</script>
<script>
// Profile Image Upload and Preview
function previewProfileImage(input) {
    console.log('Profile image change detected'); // Debug
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        console.log('File selected:', file.name, file.size, file.type); // Debug
        
        // Validate file type
        if (!file.type.startsWith('image/')) {
            alert('Please select an image file (JPG, PNG, GIF, or WebP)');
            input.value = '';
            return;
        }
        
        // Validate file size (5MB)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('File too large. Maximum size is 5MB. Your file: ' + (file.size / 1024 / 1024).toFixed(2) + 'MB');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        
        reader.onload = function(e) {
            console.log('File read successfully'); // Debug
            // Update preview image
            document.getElementById('profileImagePreview').src = e.target.result;
            
            // Show in modal for better preview
            document.getElementById('modalProfileImage').src = e.target.result;
            const modal = new bootstrap.Modal(document.getElementById('profileImageModal'));
            modal.show();
            
            // Auto-submit the form after preview
            setTimeout(() => {
                document.getElementById('imageUploadForm').submit();
            }, 2000);
        }
        
        reader.onerror = function(e) {
            console.error('Error reading file:', e); // Debug
            alert('Error reading file. Please try another image.');
        }
        
        reader.readAsDataURL(file);
    } else {
        console.log('No file selected'); // Debug
    }
}

function previewCoverImage(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        if (!file.type.startsWith('image/')) {
            alert('Please select an image file');
            input.value = '';
            return;
        }
        
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('File too large. Maximum size is 5MB.');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        
        reader.onload = function(e) {
            // Update cover photo preview immediately
            document.querySelector('.cover-photo').style.backgroundImage = `url(${e.target.result})`;
            
            // Show success message
            showNotification('Cover image preview updated. Click "Update Images" to save.', 'success');
            
            // Auto-submit form
            setTimeout(() => {
                document.getElementById('imageUploadForm').submit();
            }, 2000);
        }
        
        reader.readAsDataURL(file);
    }
}

function showNotification(message, type = 'info') {
    // Remove any existing notifications
    const existingAlerts = document.querySelectorAll('.alert-dismissible');
    existingAlerts.forEach(alert => alert.remove());
    
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Add to both forms
    const imageCard = document.querySelector('.card .card-body');
    if (imageCard) {
        imageCard.insertBefore(notification, imageCard.firstChild);
    }
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('Profile page loaded'); // Debug
    
    // Add click event to profile image for modal view
    document.getElementById('profileImagePreview').addEventListener('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('profileImageModal'));
        document.getElementById('modalProfileImage').src = this.src;
        modal.show();
    });
    
    // Drag and drop for profile image
    const profileImageContainer = document.querySelector('.profile-image-container');
    const profileImageInput = document.getElementById('profileImage');
    
    if (profileImageContainer) {
        profileImageContainer.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = 'var(--primary)';
            this.style.backgroundColor = 'rgba(99, 102, 241, 0.1)';
        });
        
        profileImageContainer.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '';
            this.style.backgroundColor = '';
        });
        
        profileImageContainer.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '';
            this.style.backgroundColor = '';
            
            if (e.dataTransfer.files.length) {
                profileImageInput.files = e.dataTransfer.files;
                previewProfileImage(profileImageInput);
            }
        });
    }
    
    // Form submission handlers
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            console.log('Form submitted:', this.id); // Debug
            // You can add loading indicators here
        });
    });
});
</script>
<script>
// Delete Post Functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeDeleteFunctionality();
});

function initializeDeleteFunctionality() {
    // Post deletion
    document.querySelectorAll('.delete-post-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const postId = this.dataset.postId;
            showDeletePostModal(postId);
        });
    });
    
    // Comment deletion
    document.querySelectorAll('.delete-comment-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const commentId = this.dataset.commentId;
            showDeleteCommentModal(commentId);
        });
    });
    
    // Confirm post deletion
    document.getElementById('confirmDeletePost').addEventListener('click', function() {
        const postId = this.dataset.postId;
        const hardDelete = document.getElementById('hardDeletePost').checked;
        deletePost(postId, hardDelete);
    });
    
    // Confirm comment deletion
    document.getElementById('confirmDeleteComment').addEventListener('click', function() {
        const commentId = this.dataset.commentId;
        const hardDelete = document.getElementById('hardDeleteComment').checked;
        deleteComment(commentId, hardDelete);
    });
}

function showDeletePostModal(postId) {
    const modal = new bootstrap.Modal(document.getElementById('deletePostModal'));
    document.getElementById('confirmDeletePost').dataset.postId = postId;
    document.getElementById('hardDeletePost').checked = false;
    modal.show();
}

function showDeleteCommentModal(commentId) {
    const modal = new bootstrap.Modal(document.getElementById('deleteCommentModal'));
    document.getElementById('confirmDeleteComment').dataset.commentId = commentId;
    document.getElementById('hardDeleteComment').checked = false;
    modal.show();
}

function deletePost(postId, hardDelete = false) {
    const postElement = document.querySelector(`[data-post-id="${postId}"]`).closest('.post-card, .post-item');
    
    // Add deleting animation
    if (postElement) {
        postElement.classList.add('post-deleting');
    }
    
    // Send delete request
    fetch('delete_post.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `post_id=${postId}&hard_delete=${hardDelete}&redirect=${encodeURIComponent(window.location.href)}`
    })
    .then(response => response.text())
    .then(() => {
        // Remove post from DOM after animation
        setTimeout(() => {
            if (postElement) {
                postElement.remove();
            }
            showNotification('Post deleted successfully', 'success');
        }, 500);
        
        // Close modal
        bootstrap.Modal.getInstance(document.getElementById('deletePostModal')).hide();
    })
    .catch(error => {
        console.error('Error deleting post:', error);
        showNotification('Error deleting post', 'error');
        if (postElement) {
            postElement.classList.remove('post-deleting');
        }
    });
}

function deleteComment(commentId, hardDelete = false) {
    const commentElement = document.querySelector(`[data-comment-id="${commentId}"]`).closest('.comment-item');
    
    // Add deleting animation
    if (commentElement) {
        commentElement.classList.add('comment-deleting');
    }
    
    // Send delete request
    fetch('delete_comment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `comment_id=${commentId}&hard_delete=${hardDelete}&redirect=${encodeURIComponent(window.location.href)}`
    })
    .then(response => response.text())
    .then(() => {
        // Remove comment from DOM after animation
        setTimeout(() => {
            if (commentElement) {
                commentElement.remove();
            }
            showNotification('Comment deleted successfully', 'success');
            
            // Update comment count
            updateCommentCount(commentElement);
        }, 300);
        
        // Close modal
        bootstrap.Modal.getInstance(document.getElementById('deleteCommentModal')).hide();
    })
    .catch(error => {
        console.error('Error deleting comment:', error);
        showNotification('Error deleting comment', 'error');
        if (commentElement) {
            commentElement.classList.remove('comment-deleting');
        }
    });
}

function updateCommentCount(commentElement) {
    // Find the post container and update comment count
    const postContainer = commentElement.closest('.card-body');
    if (postContainer) {
        const commentCountElement = postContainer.querySelector('.post-stats small:last-child');
        if (commentCountElement) {
            const currentText = commentCountElement.textContent;
            const currentCount = parseInt(currentText.match(/\d+/)[0]) || 0;
            const newCount = Math.max(0, currentCount - 1);
            commentCountElement.textContent = currentText.replace(/\d+/, newCount);
        }
    }
}

function showNotification(message, type = 'info') {
    // Remove any existing notifications
    const existingAlerts = document.querySelectorAll('.custom-notification');
    existingAlerts.forEach(alert => alert.remove());
    
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show custom-notification position-fixed`;
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: var(--shadow-lg);
    `;
    
    const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
    
    notification.innerHTML = `
        <i class="fas ${icon} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Enhanced: Add right-click context menu for posts
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.post-card, .post-item').forEach(post => {
        post.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            
            const postId = this.querySelector('[data-post-id]')?.dataset.postId;
            if (postId) {
                showContextMenu(e, postId, 'post');
            }
        });
    });
});

function showContextMenu(event, id, type) {
    // Remove any existing context menus
    const existingMenu = document.querySelector('.context-menu');
    if (existingMenu) {
        existingMenu.remove();
    }
    
    const menu = document.createElement('div');
    menu.className = 'context-menu shadow';
    menu.style.cssText = `
        position: fixed;
        left: ${event.pageX}px;
        top: ${event.pageY}px;
        background: white;
        border-radius: 10px;
        padding: 10px 0;
        min-width: 200px;
        z-index: 10000;
        border: 1px solid #dee2e6;
    `;
    
    if (type === 'post') {
        menu.innerHTML = `
            <a href="#" class="context-menu-item delete-post-btn" data-post-id="${id}">
                <i class="fas fa-trash me-2 text-danger"></i>Delete Post
            </a>
        `;
    }
    
    document.body.appendChild(menu);
    
    // Close menu when clicking elsewhere
    setTimeout(() => {
        document.addEventListener('click', function closeMenu() {
            menu.remove();
            document.removeEventListener('click', closeMenu);
        });
    }, 100);
    
    // Initialize delete functionality for context menu item
    menu.querySelector('.delete-post-btn').addEventListener('click', function(e) {
        e.preventDefault();
        showDeletePostModal(id);
    });
}

// Add context menu styles
const contextMenuStyles = document.createElement('style');
contextMenuStyles.textContent = `
    .context-menu-item {
        display: block;
        padding: 8px 15px;
        text-decoration: none;
        color: var(--text);
        transition: all 0.2s ease;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
    }
    
    .context-menu-item:hover {
        background: var(--primary);
        color: white;
    }
    
    .custom-notification {
        animation: slideInRight 0.3s ease;
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(contextMenuStyles);
</script>
</body>
</html>