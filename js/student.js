// Dashboard functionality
document.addEventListener('DOMContentLoaded', function() {
  // Check if user is logged in
  const currentUser = JSON.parse(localStorage.getItem('currentUser'));
  if (!currentUser) {
    // Redirect to login page if not logged in
    window.location.href = '../index.html';
    return;
  }
  
  // Only run dashboard code if we're on the dashboard page
  if (window.location.pathname.includes('dashboard.html')) {
  // Set user information in the UI
  const userNameElements = document.querySelectorAll('#user-name');
  userNameElements.forEach(element => {
    element.textContent = currentUser.name;
  });
  
  // Profile dropdown toggle
  const profileToggle = document.querySelector('.profile-toggle');
  const dropdownMenu = document.querySelector('.dropdown-menu');
  
  if (profileToggle && dropdownMenu) {
    profileToggle.addEventListener('click', function(e) {
      e.stopPropagation();
      dropdownMenu.classList.toggle('active');
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function() {
      dropdownMenu.classList.remove('active');
    });
    
    // Prevent dropdown from closing when clicking inside it
    dropdownMenu.addEventListener('click', function(e) {
      e.stopPropagation();
    });
  }
  
  // Logout functionality
  const logoutBtn = document.getElementById('logout-btn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', function() {
      localStorage.removeItem('currentUser');
      window.location.href = '../index.html';
      });
    }

    // Load recent transactions
    loadRecentTransactions();

    // Set default profile image if not found
    const profileImages = document.querySelectorAll('#user-profile-img, #profile-preview');
    profileImages.forEach(img => {
      if (img) {
        img.onerror = function() {
          this.src = '../assets/images/default-avatar.png';
        };
      }
    });

    // Get current user from session
    if (currentUser) {
      updateUserProfile(currentUser);
    }
  }
  
  // Run transactions code if we're on the transactions page
  if (window.location.pathname.includes('transactions.html')) {
    // Load transactions
    loadTransactions();
    
    // Set user information in the UI
      const userNameElements = document.querySelectorAll('#user-name');
      userNameElements.forEach(element => {
        element.textContent = currentUser.name;
      });
      
    // Profile dropdown toggle
    const profileToggle = document.querySelector('.profile-toggle');
    const dropdownMenu = document.querySelector('.dropdown-menu');
    
    if (profileToggle && dropdownMenu) {
      profileToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdownMenu.classList.toggle('active');
      });
      
      // Close dropdown when clicking outside
      document.addEventListener('click', function() {
        dropdownMenu.classList.remove('active');
      });
      
      // Prevent dropdown from closing when clicking inside it
      dropdownMenu.addEventListener('click', function(e) {
        e.stopPropagation();
      });
    }

    // Logout functionality
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
      logoutBtn.addEventListener('click', function() {
        localStorage.removeItem('currentUser');
        window.location.href = '../index.html';
      });
    }
  }
});

// Book request page functionality
document.addEventListener('DOMContentLoaded', function() {
  // Only run book request code if we're on the book request page
  if (window.location.pathname.includes('book-request.html')) {
    const searchInput = document.querySelector('.search-container input');
    const locationButtons = document.querySelectorAll('.location-btn');
    const bookCards = document.querySelectorAll('.book-card');

    // Only initialize if we have the required elements
    if (searchInput && locationButtons.length > 0 && bookCards.length > 0) {
      // Search functionality
      searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        filterBooks(searchInput, bookCards);
      });

      // Location filter functionality
      locationButtons.forEach(button => {
        button.addEventListener('click', function() {
          locationButtons.forEach(btn => btn.classList.remove('active'));
          this.classList.add('active');
          filterBooks(searchInput, bookCards);
        });
      });

      // Initial filter
      filterBooks(searchInput, bookCards);
    }
  }
});

// Function to load recent transactions
function loadRecentTransactions() {
  const currentUser = JSON.parse(localStorage.getItem('currentUser'));
  if (!currentUser) return;

  const transactionTable = document.querySelector('.data-table tbody');
  if (!transactionTable) return;

  // Show loading state
  transactionTable.innerHTML = `
    <tr>
      <td colspan="5" class="text-center">
        <div class="loading-spinner"></div>
        Loading transactions...
              </td>
            </tr>
  `;

  // Fetch user's room reservations
  fetch(`/LIB/api/rooms/user_reservations.php?user_id=${currentUser.user_id}`)
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then(data => {
      if (data.status === 'success') {
        // Sort reservations by date (newest first)
        const reservations = data.reservations.sort((a, b) => {
          const dateA = new Date(a.reservation_date + ' ' + a.start_time);
          const dateB = new Date(b.reservation_date + ' ' + b.start_time);
          return dateB - dateA;
        });

        // Clear loading state
        transactionTable.innerHTML = '';

        if (reservations.length === 0) {
          transactionTable.innerHTML = `
            <tr>
              <td colspan="5" class="text-center">
                No recent transactions found.
              </td>
            </tr>
          `;
          return;
        }

        // Display up to 5 most recent reservations
        const recentReservations = reservations.slice(0, 5);
        recentReservations.forEach(reservation => {
          const row = document.createElement('tr');
          row.dataset.id = reservation.reservation_id;
          row.dataset.time = `${reservation.start_time} - ${reservation.end_time}`;

          // Format date
          const date = new Date(reservation.reservation_date);
          const formattedDate = date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
          });

          // Status badge class
          let statusClass = 'pending';
          if (reservation.status === 'approved') {
            statusClass = 'approved';
          } else if (reservation.status === 'rejected') {
            statusClass = 'rejected';
          } else if (reservation.status === 'completed') {
            statusClass = 'completed';
          }

          row.innerHTML = `
            <td><span class="badge room-badge">Room</span></td>
            <td>${reservation.room_name || 'Pending Assignment'}</td>
            <td>${formattedDate}</td>
            <td><span class="status-badge ${statusClass}">${reservation.status}</span></td>
            <td>
              <button class="btn-icon" title="View Details"><i class="fas fa-eye"></i></button>
              ${reservation.status === 'pending' ? `
                <button class="btn-icon" title="Cancel"><i class="fas fa-times"></i></button>
              ` : ''}
          </td>
        `;
        
          transactionTable.appendChild(row);
        });

        // Update stats
        updateDashboardStats(reservations);

        // Attach event listeners to the new buttons
        attachTransactionEventListeners();
      } else {
        transactionTable.innerHTML = `
          <tr>
            <td colspan="5" class="text-center text-danger">
              Failed to load transactions. Please try again later.
            </td>
          </tr>
        `;
      }
    })
    .catch(error => {
      console.error('Error fetching transactions:', error);
      transactionTable.innerHTML = `
        <tr>
          <td colspan="5" class="text-center text-danger">
            Could not connect to the server. Please try again later.
          </td>
        </tr>
      `;
    });
}

// Function to update dashboard stats
function updateDashboardStats(reservations) {
  // Count room reservations
  const roomReservations = reservations.filter(r => r.status !== 'rejected').length;
  
  // Update room reservations count
  const roomStatsElement = document.querySelector('.stat-card:nth-child(2) .stat-details h3');
  if (roomStatsElement) {
    roomStatsElement.textContent = roomReservations;
  }

  // Count pending requests
  const pendingRequests = reservations.filter(r => r.status === 'pending').length;
  
  // Update pending requests count
  const pendingStatsElement = document.querySelector('.stat-card:nth-child(3) .stat-details h3');
  if (pendingStatsElement) {
    pendingStatsElement.textContent = pendingRequests;
  }
}

// Function to attach event listeners to transaction buttons
function attachTransactionEventListeners() {
  // View Details buttons
  const viewButtons = document.querySelectorAll('.btn-icon[title="View Details"]');
  viewButtons.forEach(button => {
    button.addEventListener('click', function() {
      const row = this.closest('tr');
      if (!row) return;

      const type = row.querySelector('.badge')?.textContent || '';
      const title = row.querySelector('td:nth-child(2)')?.textContent || '';
      const date = row.querySelector('td:nth-child(3)')?.textContent || '';
      const status = row.querySelector('.status-badge')?.textContent || '';
      const time = row.dataset.time || '';

      const modal = document.getElementById('viewDetailsModal');
      if (!modal) return;

      const detailType = modal.querySelector('#detail-type');
      const detailTitle = modal.querySelector('#detail-title');
      const detailDate = modal.querySelector('#detail-date');
      const detailStatus = modal.querySelector('#detail-status');
      const detailTime = modal.querySelector('#detail-time');

      if (detailType) detailType.textContent = type;
      if (detailTitle) detailTitle.textContent = title;
      if (detailDate) detailDate.textContent = date;
      if (detailStatus) detailStatus.textContent = status;
      if (detailTime) detailTime.textContent = time;

      // Show/hide type-specific details
      const bookDetails = modal.querySelectorAll('.book-details');
      const roomDetails = modal.querySelectorAll('.room-details');
      
      if (type === 'Room') {
        bookDetails.forEach(el => el.style.display = 'none');
        roomDetails.forEach(el => el.style.display = 'block');
      } else {
        bookDetails.forEach(el => el.style.display = 'block');
        roomDetails.forEach(el => el.style.display = 'none');
      }

      modal.classList.add('active');
    });
  });

  // Cancel buttons
  const cancelButtons = document.querySelectorAll('.btn-icon[title="Cancel"]');
  cancelButtons.forEach(button => {
    button.addEventListener('click', function() {
      const row = this.closest('tr');
      if (!row) return;

      const reservationId = row.dataset.id;
      const type = row.querySelector('.badge')?.textContent || '';
      const title = row.querySelector('td:nth-child(2)')?.textContent || '';
      const date = row.querySelector('td:nth-child(3)')?.textContent || '';

      const modal = document.getElementById('cancelConfirmationModal');
      if (!modal) return;

      const cancelType = modal.querySelector('#cancel-type');
      const cancelTitle = modal.querySelector('#cancel-title');
      const cancelDate = modal.querySelector('#cancel-date');
      const confirmCancel = modal.querySelector('#confirm-cancel');

      if (cancelType) cancelType.textContent = type;
      if (cancelTitle) cancelTitle.textContent = title;
      if (cancelDate) cancelDate.textContent = date;
      if (confirmCancel) confirmCancel.dataset.reservationId = reservationId;

      modal.classList.add('active');
    });
  });
}

function filterBooks(searchInput, bookCards) {
  if (!searchInput || !bookCards.length) return;

  const searchTerm = searchInput.value.toLowerCase().trim();
  const activeLocationBtn = document.querySelector('.location-btn.active');
  const selectedLocation = activeLocationBtn ? activeLocationBtn.dataset.location : 'all';

  bookCards.forEach(card => {
    const title = card.querySelector('h3')?.textContent.toLowerCase() || '';
    const author = card.querySelector('.book-author')?.textContent.toLowerCase() || '';
    const location = card.dataset.location || '';

    const matchesSearch = searchTerm === '' || 
      title.includes(searchTerm) || 
      author.includes(searchTerm);

    const matchesLocation = selectedLocation === 'all' || location === selectedLocation;

    if (matchesSearch && matchesLocation) {
      card.style.display = 'block';
      card.style.animation = 'fadeIn 0.3s ease-in-out';
    } else {
      card.style.display = 'none';
    }
  });
}

// Function to create avatar with initials
function createInitialsAvatar(firstName) {
    const initial = firstName ? firstName.charAt(0).toUpperCase() : '?';
    const colors = ['#1abc9c', '#3498db', '#9b59b6', '#e67e22', '#e74c3c', '#2ecc71'];
    const color = colors[Math.floor(Math.random() * colors.length)];
    
    // Create SVG avatar
    const svg = `
        <svg width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
            <circle cx="20" cy="20" r="20" fill="${color}"/>
            <text x="20" y="28" font-family="Arial" font-size="20" fill="white" text-anchor="middle">${initial}</text>
        </svg>
    `;
    
    return `data:image/svg+xml;base64,${btoa(svg)}`;
}

// Function to update user profile display
function updateUserProfile(user) {
    if (!user) return;
    
    const userNameElement = document.getElementById('user-name');
    const profileImgElement = document.getElementById('user-profile-img');
    const profilePreviewElement = document.getElementById('profile-preview');
    
    if (userNameElement) {
        userNameElement.textContent = `${user.first_name} ${user.last_name}`;
    }
    
    // Create and set avatar for both profile image and preview
    const avatarUrl = createInitialsAvatar(user.first_name);
    if (profileImgElement) {
        profileImgElement.src = avatarUrl;
        profileImgElement.onerror = function() {
            this.src = createInitialsAvatar(user.first_name);
        };
    }
    if (profilePreviewElement) {
        profilePreviewElement.src = avatarUrl;
        profilePreviewElement.onerror = function() {
            this.src = createInitialsAvatar(user.first_name);
        };
    }
}

// Function to load transactions for the transactions page
function loadTransactions() {
  const currentUser = JSON.parse(localStorage.getItem('currentUser'));
  if (!currentUser) return;

  // Get all transaction tables
  const allTable = document.querySelector('#all-tab .data-table tbody');
  const booksTable = document.querySelector('#books-tab .data-table tbody');
  const roomsTable = document.querySelector('#rooms-tab .data-table tbody');

  if (!allTable || !booksTable || !roomsTable) return;

  // Show loading state in all tables
  const loadingRow = `
    <tr>
      <td colspan="7" class="text-center">
        <div class="loading-spinner"></div>
        Loading transactions...
      </td>
    </tr>
  `;
  allTable.innerHTML = loadingRow;
  booksTable.innerHTML = loadingRow;
  roomsTable.innerHTML = loadingRow;

  // Fetch user's room reservations
  fetch(`/LIB/api/rooms/user_reservations.php?user_id=${currentUser.user_id}`)
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then(data => {
      if (data.status === 'success') {
        // Debug log the first reservation to see its structure
        if (data.reservations.length > 0) {
          console.log('Sample reservation data:', data.reservations[0]);
        }

        // Sort reservations by date (newest first)
        const reservations = data.reservations.sort((a, b) => {
          const dateA = new Date(a.reservation_date + ' ' + a.start_time);
          const dateB = new Date(b.reservation_date + ' ' + b.start_time);
          return dateB - dateA;
        });

        // Clear loading state
        allTable.innerHTML = '';
        booksTable.innerHTML = '';
        roomsTable.innerHTML = '';

        if (reservations.length === 0) {
          const noDataRow = `
            <tr>
              <td colspan="7" class="text-center">
                No transactions found.
              </td>
            </tr>
          `;
          allTable.innerHTML = noDataRow;
          booksTable.innerHTML = noDataRow;
          roomsTable.innerHTML = noDataRow;
          return;
        }

        // Process each reservation
        reservations.forEach(reservation => {
          // Format date
          const date = new Date(reservation.reservation_date);
          const formattedDate = date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
          });

          // Status badge class
          let statusClass = 'pending';
          if (reservation.status === 'approved') {
            statusClass = 'approved';
          } else if (reservation.status === 'rejected') {
            statusClass = 'rejected';
          } else if (reservation.status === 'completed') {
            statusClass = 'completed';
          }

          // Debug log for member count calculation
          console.log('Reservation ID:', reservation.reservation_id);
          console.log('Member count from API:', reservation.member_count);
          console.log('Members array:', reservation.members);
          console.log('Additional members:', reservation.additional_members);

          // Calculate total members based on the actual API response structure
          let totalMembers = 1; // Start with the main user
          
          // Add additional members if they exist in the API response
          if (reservation.additional_members) {
            totalMembers += parseInt(reservation.additional_members) || 0;
          } else if (reservation.member_count) {
            // Fallback to member_count if additional_members is not available
            totalMembers = parseInt(reservation.member_count) || 1;
          }

          console.log('Calculated total members:', totalMembers);

          // Create row for "All" tab
          const allRow = document.createElement('tr');
          allRow.dataset.id = reservation.reservation_id;
          allRow.dataset.time = `${reservation.start_time} - ${reservation.end_time}`;
          allRow.innerHTML = `
            <td><span class="badge room-badge">Room</span></td>
            <td>${reservation.room_name || 'Pending Assignment'}</td>
            <td>${formattedDate}</td>
            <td>${formattedDate}</td>
            <td><span class="status-badge ${statusClass}">${reservation.status}</span></td>
            <td>-</td>
            <td>
              <button class="btn-icon" title="View Details"><i class="fas fa-eye"></i></button>
              ${reservation.status === 'pending' ? `
                <button class="btn-icon" title="Cancel"><i class="fas fa-times"></i></button>
              ` : ''}
            </td>
          `;
          allTable.appendChild(allRow);

          // Create row for "Rooms" tab
          const roomRow = document.createElement('tr');
          roomRow.dataset.id = reservation.reservation_id;
          roomRow.dataset.time = `${reservation.start_time} - ${reservation.end_time}`;
          roomRow.innerHTML = `
            <td>${reservation.room_name || 'Pending Assignment'}</td>
            <td>${formattedDate}</td>
            <td>${reservation.start_time} - ${reservation.end_time}</td>
            <td>${reservation.purpose || 'Not specified'}</td>
            <td>${totalMembers}</td>
            <td><span class="status-badge ${statusClass}">${reservation.status}</span></td>
            <td>
              <button class="btn-icon" title="View Details"><i class="fas fa-eye"></i></button>
              ${reservation.status === 'pending' ? `
                <button class="btn-icon" title="Cancel"><i class="fas fa-times"></i></button>
              ` : ''}
            </td>
          `;
          roomsTable.appendChild(roomRow);
        });

        // Attach event listeners to the new buttons
        attachTransactionEventListeners();

        // Initialize filtering after data is loaded
        initializeFiltering();
        } else {
        const errorRow = `
          <tr>
            <td colspan="7" class="text-center text-danger">
              Failed to load transactions. Please try again later.
            </td>
          </tr>
        `;
        allTable.innerHTML = errorRow;
        booksTable.innerHTML = errorRow;
        roomsTable.innerHTML = errorRow;
      }
    })
    .catch(error => {
      console.error('Error fetching transactions:', error);
      const errorRow = `
        <tr>
          <td colspan="7" class="text-center text-danger">
            Could not connect to the server. Please try again later.
          </td>
        </tr>
      `;
      allTable.innerHTML = errorRow;
      booksTable.innerHTML = errorRow;
      roomsTable.innerHTML = errorRow;
    });
}

// Function to initialize filtering
function initializeFiltering() {
  const searchInput = document.getElementById('transactionSearch');
  const statusFilter = document.getElementById('status-filter');
  const dateFilter = document.getElementById('date-filter');
  const applyFiltersBtn = document.getElementById('apply-filters');
  const resetFiltersBtn = document.getElementById('reset-filters');
  const tabButtons = document.querySelectorAll('.tab-btn');

  if (!searchInput || !statusFilter || !dateFilter || !applyFiltersBtn || !resetFiltersBtn) return;

  // Function to perform search and filtering
  function filterTransactions() {
    const searchTerm = searchInput.value.toLowerCase().trim();
    const selectedStatus = statusFilter.value.toLowerCase();
    const selectedDate = dateFilter.value;
    const activeTab = document.querySelector('.tab-btn.active')?.dataset.tab;
    const activeTable = document.querySelector(`#${activeTab}-tab table tbody`);
    
    if (!activeTable) return;

    const rows = activeTable.querySelectorAll('tr:not(.no-results-row)');
    let hasResults = false;

    rows.forEach(row => {
      let matchesSearch = false;
      let matchesStatus = true;
      let matchesDate = true;

      // Search term matching
      const cells = row.querySelectorAll('td');
      cells.forEach(cell => {
        if (cell.textContent.toLowerCase().includes(searchTerm)) {
          matchesSearch = true;
        }
      });

      // Status matching
      if (selectedStatus !== 'all') {
        const statusCell = row.querySelector('.status-badge');
        if (statusCell) {
          const statusText = statusCell.textContent.toLowerCase().replace(/\s+/g, '-');
          matchesStatus = statusText === selectedStatus;
          
          // Special handling for "in-use" status
          if (selectedStatus === 'in-use' && statusText === 'in use') {
            matchesStatus = true;
          }
        }
      }

      // Date matching
      if (selectedDate) {
        const dateCell = row.querySelector('td:nth-child(3)');
        if (dateCell) {
          matchesDate = dateCell.textContent === selectedDate;
        }
      }

      // Show/hide row based on all filters
      if (matchesSearch && matchesStatus && matchesDate) {
        row.style.display = '';
        hasResults = true;
      } else {
        row.style.display = 'none';
      }
    });

    // Show/hide no results message
    let noResultsRow = activeTable.querySelector('.no-results-row');
    if (!noResultsRow) {
      noResultsRow = document.createElement('tr');
      noResultsRow.className = 'no-results-row';
      noResultsRow.innerHTML = `
        <td colspan="7" class="text-center">
          No matching transactions found.
        </td>
      `;
      activeTable.appendChild(noResultsRow);
    }
    noResultsRow.style.display = hasResults ? 'none' : 'table-row';
  }

  // Event Listeners
  searchInput.addEventListener('input', filterTransactions);
  statusFilter.addEventListener('change', filterTransactions);
  dateFilter.addEventListener('change', filterTransactions);
  applyFiltersBtn.addEventListener('click', filterTransactions);

  resetFiltersBtn.addEventListener('click', function() {
    searchInput.value = '';
    statusFilter.value = 'all';
    dateFilter.value = '';
    filterTransactions();
  });

  // Tab switching with filter preservation
  tabButtons.forEach(button => {
    button.addEventListener('click', function() {
      tabButtons.forEach(btn => btn.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
      
      this.classList.add('active');
      const tabId = `${this.dataset.tab}-tab`;
      document.getElementById(tabId).classList.add('active');
      
      filterTransactions();
    });
  });

  // Initial filter application
  filterTransactions();
}

