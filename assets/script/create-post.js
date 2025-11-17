/*
Backend Integration Notes:
- POST /api/posts/create for creating new posts
- POST /api/posts/{id}/update for editing existing posts
- POST /api/upload/image for image uploads
- GET /api/posts/{id} for loading existing post data
*/

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('createPostModal');
    const closeModal = document.getElementById('closeModal');
    const postTypeButtons = document.querySelectorAll('.post-type-btn');
    const formContainer = document.getElementById('createPostForm');
    
    let currentPostType = 'marketplace';
    let editingPostId = null;
    let portfolioItems = [];

    // Modal controls
    closeModal.addEventListener('click', closeCreatePostModal);
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeCreatePostModal();
        }
    });

    // Post type selection
    postTypeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            postTypeButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentPostType = this.dataset.type;
            loadCreatePostForm(currentPostType, editingPostId);
        });
    });

    // Global function to load create post form
    window.loadCreatePostForm = function(type = 'marketplace', editId = null) {
        currentPostType = type;
        editingPostId = editId;
        
        // Update active button
        postTypeButtons.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.type === type);
        });

        if (type === 'marketplace') {
            loadMarketplaceForm(editId);
        } else {
            loadServiceForm(editId);
        }
    };

    function loadMarketplaceForm(editId = null) {
        const isEditing = editId !== null;
        
        formContainer.innerHTML = `
            <form id="marketplaceForm" class="create-post-form" action="backend/create-post.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="${isEditing ? 'update_post' : 'create_post'}">
                <input type="hidden" name="post_type" value="marketplace">
                <input type="hidden" name="post_id" value="${editId || ''}">
                <input type="hidden" name="csrf_token" id="csrfTokenMarketplace" value="">
                
                <div class="input-group">
                    <label for="productName">Product Name *</label>
                    <input type="text" id="productName" name="product_name" placeholder="Enter product name" required maxlength="100">
                </div>
                
                <div class="form-row">
                    <div class="input-group half">
                        <label for="price">Price (₱) *</label>
                        <input type="number" id="price" name="price" placeholder="0.00" min="0" step="0.01" required>
                    </div>
                    <div class="input-group half">
                        <label for="condition">Condition *</label>
                        <select id="condition" name="condition" required>
                            <option value="">Select condition</option>
                            <option value="new">Brand New</option>
                            <option value="like_new">Like New</option>
                            <option value="excellent">Excellent</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                        </select>
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="">Select category</option>
                        <option value="electronics">Electronics</option>
                        <option value="books">Books & Textbooks</option>
                        <option value="clothing">Clothing & Accessories</option>
                        <option value="furniture">Furniture</option>
                        <option value="sports">Sports & Recreation</option>
                        <option value="musical_instruments">Musical Instruments</option>
                        <option value="automotive">Automotive</option>
                        <option value="home_garden">Home & Garden</option>
                        <option value="art_crafts">Art & Crafts</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="input-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" placeholder="Describe your item or service in detail" required rows="4" maxlength="1000"></textarea>
                </div>
                
                <div class="input-group">
                    <label for="images">Upload Images</label>
                    <div class="upload-area" id="uploadArea">
                        <div class="upload-icon">
                            <img src="Images/folder-icon.svg" alt="Upload" style="width: 48px; height: 48px; filter: brightness(0) saturate(100%) invert(50%);">
                        </div>
                        <p>Click to upload or drag and drop</p>
                        <small>PNG, JPG, GIF up to 10MB (Max 5 images)</small>
                        <input type="file" id="images" name="images[]" multiple accept="image/*" style="display: none;">
                    </div>
                    <div class="image-preview" id="imagePreview"></div>
                </div>
                
                <div class="contact-section">
                    <h4>Contact Information</h4>
                    
                    <div class="input-group">
                        <label for="contactBio">Your Bio</label>
                        <textarea id="contactBio" name="contact_bio" placeholder="Tell buyers/clients about yourself" rows="3" maxlength="200"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="input-group half">
                            <label for="emailAddress">Email Address</label>
                            <input type="email" id="emailAddress" name="email_address" placeholder="your.email@tip.edu.ph" value="">
                        </div>
                        <div class="input-group half">
                            <label for="facebook">Facebook</label>
                            <input type="text" id="facebook" name="facebook" placeholder="Profile name">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="input-group half">
                            <label for="chatAvailability">Chat Availability</label>
                            <input type="text" id="chatAvailability" name="chat_availability" placeholder="e.g., 9 AM - 6 PM">
                        </div>
                        <div class="input-group half">
                            <label for="meetupAvailability">Meetup Availability</label>
                            <input type="text" id="meetupAvailability" name="meetup_availability" placeholder="e.g., Weekends only">
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-secondary" id="cancelBtn">Cancel</button>
                    <button type="submit" class="btn-primary" id="createMarketplaceBtn">
                        ${isEditing ? 'Update' : 'Create'} Post
                    </button>
                </div>
            </form>
        `;

        setupMarketplaceFormHandlers();
        
        if (isEditing) {
            loadExistingPostData(editId);
        }
    }

    function loadServiceForm(editId = null) {
        const isEditing = editId !== null;
        
        formContainer.innerHTML = `
            <form id="serviceForm" class="create-post-form" action="backend/create-post.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="${isEditing ? 'update_post' : 'create_post'}">
                <input type="hidden" name="post_type" value="service">
                <input type="hidden" name="post_id" value="${editId || ''}">
                <input type="hidden" name="csrf_token" id="csrfTokenService" value="">
                
                <div class="input-group">
                    <label for="serviceTitle">Service Title *</label>
                    <input type="text" id="serviceTitle" name="service_title" placeholder="Enter service title" required maxlength="100">
                </div>
                
                <div class="form-row">
                    <div class="input-group half">
                        <label for="startingPrice">Starting Price (₱) *</label>
                        <input type="number" id="startingPrice" name="starting_price" placeholder="0.00" min="0" step="0.01" required>
                    </div>
                    <div class="input-group half">
                        <label for="serviceCategory">Category *</label>
                        <select id="serviceCategory" name="service_category" required>
                            <option value="">Select category</option>
                            <option value="tutoring">Tutoring & Education</option>
                            <option value="design">Graphic Design</option>
                            <option value="writing">Writing & Translation</option>
                            <option value="programming">Programming & Tech</option>
                            <option value="photography">Photography & Video</option>
                            <option value="music">Music & Audio</option>
                            <option value="business">Business & Marketing</option>
                            <option value="lifestyle">Lifestyle Services</option>
                            <option value="crafts">Arts & Crafts</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="deliveryTime">Delivery Time *</label>
                    <select id="deliveryTime" name="delivery_time" required>
                        <option value="">Select delivery time</option>
                        <option value="1_day">1 Day</option>
                        <option value="2_days">2 Days</option>
                        <option value="3_days">3 Days</option>
                        <option value="1_week">1 Week</option>
                        <option value="2_weeks">2 Weeks</option>
                        <option value="1_month">1 Month</option>
                        <option value="custom">Custom Timeline</option>
                    </select>
                </div>
                
                <div class="portfolio-section">
                    <h4>Portfolio <button type="button" class="btn-add-item" id="addPortfolioItem">+ Add Item</button></h4>
                    <p>Showcase your previous work to attract more clients</p>
                    
                    <div class="portfolio-items" id="portfolioItems">
                        <!-- Portfolio items will be added here -->
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="serviceDescription">Description *</label>
                    <textarea id="serviceDescription" name="service_description" placeholder="Describe your item or service in detail" required rows="5" maxlength="1000"></textarea>
                </div>
                
                <div class="input-group">
                    <label for="serviceImages">Upload Images</label>
                    <div class="upload-area" id="serviceUploadArea">
                        <div class="upload-icon">
                            <img src="Images/folder-icon.svg" alt="Upload" style="width: 48px; height: 48px; filter: brightness(0) saturate(100%) invert(50%);">
                        </div>
                        <p>Click to upload or drag and drop</p>
                        <small>PNG, JPG, GIF up to 10MB (Max 5 images)</small>
                        <input type="file" id="serviceImages" name="service_images[]" multiple accept="image/*" style="display: none;">
                    </div>
                    <div class="image-preview" id="serviceImagePreview"></div>
                </div>
                
                <div class="contact-section">
                    <h4>Contact Information</h4>
                    
                    <div class="input-group">
                        <label for="serviceBio">Your Bio</label>
                        <textarea id="serviceBio" name="service_bio" placeholder="Tell buyers/clients about yourself" rows="3" maxlength="200"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="input-group half">
                            <label for="serviceEmail">Email Address</label>
                            <input type="email" id="serviceEmail" name="service_email" placeholder="your.email@tip.edu.ph">
                        </div>
                        <div class="input-group half">
                            <label for="serviceFacebook">Facebook</label>
                            <input type="text" id="serviceFacebook" name="service_facebook" placeholder="Profile name">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="input-group half">
                            <label for="serviceChatAvailability">Chat Availability</label>
                            <input type="text" id="serviceChatAvailability" name="service_chat_availability" placeholder="e.g., 9 AM - 6 PM">
                        </div>
                        <div class="input-group half">
                            <label for="serviceMeetupAvailability">Meetup Availability</label>
                            <input type="text" id="serviceMeetupAvailability" name="service_meetup_availability" placeholder="e.g., Weekends only">
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-secondary" id="cancelServiceBtn">Cancel</button>
                    <button type="submit" class="btn-primary" id="createServiceBtn">
                        ${isEditing ? 'Update' : 'Create'} Post
                    </button>
                </div>
            </form>
        `;

        setupServiceFormHandlers();
        
        if (isEditing) {
            loadExistingPostData(editId);
        }
    }

    function setupMarketplaceFormHandlers() {
        const form = document.getElementById('marketplaceForm');
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('images');
        const cancelBtn = document.getElementById('cancelBtn');

        // Form submission
        form.addEventListener('submit', handleMarketplaceSubmit);
        
        // Cancel button
        cancelBtn.addEventListener('click', closeCreatePostModal);
        
        // File upload
        setupFileUpload(uploadArea, fileInput, 'imagePreview');
    }

    function setupServiceFormHandlers() {
        const form = document.getElementById('serviceForm');
        const uploadArea = document.getElementById('serviceUploadArea');
        const fileInput = document.getElementById('serviceImages');
        const cancelBtn = document.getElementById('cancelServiceBtn');
        const addPortfolioBtn = document.getElementById('addPortfolioItem');

        // Form submission
        form.addEventListener('submit', handleServiceSubmit);
        
        // Cancel button
        cancelBtn.addEventListener('click', closeCreatePostModal);
        
        // File upload
        setupFileUpload(uploadArea, fileInput, 'serviceImagePreview');
        
        // Portfolio management
        addPortfolioBtn.addEventListener('click', addPortfolioItem);
    }

    function setupFileUpload(uploadArea, fileInput, previewContainerId) {
        uploadArea.addEventListener('click', () => fileInput.click());
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('drag-over');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('drag-over');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('drag-over');
            const files = e.dataTransfer.files;
            handleFileSelection(files, previewContainerId);
        });
        
        fileInput.addEventListener('change', (e) => {
            handleFileSelection(e.target.files, previewContainerId);
        });
    }

    function handleFileSelection(files, previewContainerId) {
        const previewContainer = document.getElementById(previewContainerId);
        const maxFiles = 5;
        
        if (files.length > maxFiles) {
            alert(`You can only upload up to ${maxFiles} images`);
            return;
        }
        
        previewContainer.innerHTML = '';
        
        Array.from(files).forEach((file, index) => {
            if (!file.type.startsWith('image/')) {
                alert(`File ${file.name} is not an image`);
                return;
            }
            
            if (file.size > 10 * 1024 * 1024) {
                alert(`File ${file.name} is larger than 10MB`);
                return;
            }
            
            const reader = new FileReader();
            reader.onload = (e) => {
                const imageDiv = document.createElement('div');
                imageDiv.className = 'image-preview-item';
                imageDiv.innerHTML = `
                    <img src="${e.target.result}" alt="Preview ${index + 1}">
                    <button type="button" class="remove-image" data-index="${index}">×</button>
                `;
                previewContainer.appendChild(imageDiv);
            };
            reader.readAsDataURL(file);
        });
        
        // Setup remove buttons
        previewContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-image')) {
                e.target.parentElement.remove();
            }
        });
    }

    function addPortfolioItem() {
        const portfolioContainer = document.getElementById('portfolioItems');
        const itemCount = portfolioContainer.children.length;
        
        if (itemCount >= 5) {
            alert('You can add up to 5 portfolio items');
            return;
        }
        
        const portfolioItem = document.createElement('div');
        portfolioItem.className = 'portfolio-item';
        portfolioItem.innerHTML = `
            <div class="portfolio-header">
                <h5>Portfolio Item ${itemCount + 1}</h5>
                <button type="button" class="remove-portfolio-item">Remove</button>
            </div>
            
            <div class="input-group">
                <label for="portfolioTitle${itemCount}">Project Title</label>
                <input type="text" id="portfolioTitle${itemCount}" name="portfolio_title[]" placeholder="e.g., Logo Design for Local Business" maxlength="100">
            </div>
            
            <div class="input-group">
                <label for="portfolioDescription${itemCount}">Project Description</label>
                <textarea id="portfolioDescription${itemCount}" name="portfolio_description[]" placeholder="Brief description of the project and your role" rows="3" maxlength="300"></textarea>
            </div>
            
            <div class="input-group">
                <label for="portfolioImage${itemCount}">Image URL (Optional)</label>
                <input type="url" id="portfolioImage${itemCount}" name="portfolio_image[]" placeholder="https://example.com/image.jpg">
                <small>Paste a link to showcase your work (from Google Drive, Imgur, etc.)</small>
            </div>
            
            <div class="input-group">
                <label for="portfolioItemDescription${itemCount}">Description *</label>
                <textarea id="portfolioItemDescription${itemCount}" name="portfolio_item_description[]" placeholder="Describe your item or service in detail" required rows="3" maxlength="500"></textarea>
            </div>
        `;
        
        portfolioContainer.appendChild(portfolioItem);
        
        // Setup remove button
        portfolioItem.querySelector('.remove-portfolio-item').addEventListener('click', () => {
            portfolioItem.remove();
        });
    }

    function handleMarketplaceSubmit(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('createMarketplaceBtn');
        const originalText = submitBtn.textContent;
        
        submitBtn.textContent = 'Creating...';
        submitBtn.disabled = true;
        
        /*
        Backend API Endpoint: POST /api/posts/create
        
        PHP Example:
        $sql = "INSERT INTO posts (user_id, type, title, description, price, condition, category, contact_info, images, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
        */
        
        // For now, simulate submission
        setTimeout(() => {
            alert('Marketplace listing created successfully!');
            closeCreatePostModal();
            // Refresh dashboard
            if (typeof loadUserListings === 'function') {
                loadUserListings();
            }
            if (typeof loadDashboardStats === 'function') {
                loadDashboardStats();
            }
        }, 2000);
    }

    function handleServiceSubmit(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('createServiceBtn');
        const originalText = submitBtn.textContent;
        
        submitBtn.textContent = 'Creating...';
        submitBtn.disabled = true;
        
        /*
        Backend API Endpoint: POST /api/posts/create
        Include portfolio items in the request
        */
        
        // For now, simulate submission
        setTimeout(() => {
            alert('Service listing created successfully!');
            closeCreatePostModal();
            // Refresh dashboard
            if (typeof loadUserListings === 'function') {
                loadUserListings();
            }
            if (typeof loadDashboardStats === 'function') {
                loadDashboardStats();
            }
        }, 2000);
    }

    function loadExistingPostData(postId) {
        /*
        Backend API Endpoint: GET /api/posts/{id}
        Load existing post data for editing
        */
        
        fetch(`/api/posts/${postId}`, {
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + getAuthToken(),
                'X-CSRF-Token': getCsrfToken()
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateFormWithData(data.post);
            }
        })
        .catch(error => {
            console.error('Error loading post data:', error);
        });
    }

    function populateFormWithData(postData) {
        // Populate form fields based on post type
        if (currentPostType === 'marketplace') {
            // Populate marketplace form
            document.getElementById('productName').value = postData.title || '';
            document.getElementById('price').value = postData.price || '';
            document.getElementById('condition').value = postData.condition || '';
            document.getElementById('category').value = postData.category || '';
            document.getElementById('description').value = postData.description || '';
        } else {
            // Populate service form
            document.getElementById('serviceTitle').value = postData.title || '';
            document.getElementById('startingPrice').value = postData.price || '';
            document.getElementById('serviceCategory').value = postData.category || '';
            document.getElementById('deliveryTime').value = postData.delivery_time || '';
            document.getElementById('serviceDescription').value = postData.description || '';
        }
        
        // Populate contact information
        const contactInfo = postData.contact_info ? JSON.parse(postData.contact_info) : {};
        const emailField = currentPostType === 'marketplace' ? 'emailAddress' : 'serviceEmail';
        const bioField = currentPostType === 'marketplace' ? 'contactBio' : 'serviceBio';
        
        if (document.getElementById(emailField)) {
            document.getElementById(emailField).value = contactInfo.email || '';
        }
        if (document.getElementById(bioField)) {
            document.getElementById(bioField).value = contactInfo.bio || '';
        }
    }

    function closeCreatePostModal() {
        modal.style.display = 'none';
        editingPostId = null;
        formContainer.innerHTML = '';
        
        // Reset button states
        const submitBtn = document.querySelector('.btn-primary');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = submitBtn.textContent.replace('Creating...', 'Create Post').replace('Updating...', 'Update Post');
        }
    }

    // Utility functions
    function getAuthToken() {
        return sessionStorage.getItem('auth_token') || localStorage.getItem('auth_token') || '';
    }

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }
});