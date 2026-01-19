document.addEventListener("DOMContentLoaded", function () {
  console.log("Profile page scripts loaded and DOM is ready.");

  // Elements for profile display
  const profileDisplayAvatar = document.getElementById(
    "profile-display-avatar"
  );
  const navbarUserAvatar = document.getElementById("navbar-user-avatar"); // Avatar di navbar
  const profileDisplayName = document.getElementById("profile-display-name");
  const profileDisplayTitle = document.getElementById("profile-display-title");
  const profileDisplayBio = document.getElementById("profile-display-bio");
  const displayEmail = document.getElementById("display-email");
  const displayPhone = document.getElementById("display-phone");
  const displayAddress = document.getElementById("display-address");
  const displayDob = document.getElementById("display-dob");
  const displayGender = document.getElementById("display-gender");
  const displayOccupation = document.getElementById("display-occupation");
  const displayEmailNotif = document.getElementById("display-email-notif");
  const display2fa = document.getElementById("display-2fa");
  const displayTimezone = document.getElementById("display-timezone");

  // Elements for global edit modal
  const globalEditProfileModal = document.getElementById(
    "global-edit-profile-modal"
  );
  const editProfileBtn = document.getElementById("edit-profile-btn");
  const globalProfileEditForm = document.getElementById(
    "global-profile-edit-form"
  );
  const globalEditName = document.getElementById("global-edit-name");
  const globalEditTitle = document.getElementById("global-edit-title");
  const globalEditBio = document.getElementById("global-edit-bio");
  const globalEditEmail = document.getElementById("global-edit-email");
  const globalEditPhone = document.getElementById("global-edit-phone");
  const globalEditAddress = document.getElementById("global-edit-address");
  const globalEditDob = document.getElementById("global-edit-dob");
  const globalEditGender = document.getElementById("global-edit-gender");
  const globalEditOccupation = document.getElementById(
    "global-edit-occupation"
  );
  const globalEditEmailNotif = document.getElementById(
    "global-edit-email-notif"
  );
  const globalEdit2fa = document.getElementById("global-edit-2fa");
  const globalEditTimezone = document.getElementById("global-edit-timezone");

  // Elements for inline edit forms
  const editSectionBtns = document.querySelectorAll(".edit-section-btn");
  const cancelInlineBtns = document.querySelectorAll(".btn-cancel-inline");
  const saveInlineForms = document.querySelectorAll(".profile-card .edit-mode"); // Select forms directly

  // Elements for password change modal
  const changePasswordModal = document.getElementById("change-password-modal");
  const changePasswordBtn = document.getElementById("change-password-btn");
  const changePasswordForm = document.getElementById("change-password-form");
  const currentPasswordInput = document.getElementById("current-password");
  const newPasswordInput = document.getElementById("new-password");
  const confirmNewPasswordInput = document.getElementById(
    "confirm-new-password"
  );
  const changePasswordErrorMessage = document.getElementById(
    "change-password-error-message"
  );
  const changePasswordSuccessMessage = document.getElementById(
    "change-password-success-message"
  );
  const togglePasswordButtons = document.querySelectorAll(".toggle-password");

  // Elements for delete account modal
  const deleteAccountModal = document.getElementById("delete-account-modal");
  const deleteAccountBtn = document.getElementById("delete-account-btn");
  const deleteConfirmInput = document.getElementById("delete-confirm-input");
  const confirmDeleteBtn = document.getElementById("confirm-delete-btn");

  const cancelModalBtns = document.querySelectorAll(".btn-cancel-modal"); // For all modals

  // Global elements for dashboard interaction
  const dashboardWrapper = document.querySelector(".dashboard-wrapper");
  const body = document.body;

  // --- Helper function untuk membuka modal ---
  function openModal(modalElement) {
    if (modalElement) {
      modalElement.classList.add("active");
      body.classList.add("modal-open");
      if (dashboardWrapper) {
        dashboardWrapper.classList.add("modal-active-bg");
      }
    }
  }

  // --- Helper function untuk menutup modal ---
  function closeModal(modalElement) {
    if (modalElement) {
      modalElement.classList.remove("active");
      // Hanya hapus modal-open dan modal-active-bg jika tidak ada modal lain yang aktif
      const activeModals = document.querySelectorAll(".modal-overlay.active");
      if (activeModals.length === 0) {
        body.classList.remove("modal-open");
        if (dashboardWrapper) {
          dashboardWrapper.classList.remove("modal-active-bg");
        }
      }
    }
  }

  // --- Helper function untuk AJAX ---
  async function fetchData(
    url,
    method = "GET",
    data = null,
    isFormData = false
  ) {
    const options = {
      method,
      headers: {
        // PENTING: Tambahkan header ini untuk memberitahu PHP bahwa ini adalah permintaan AJAX
        "X-Requested-With": "XMLHttpRequest",
      },
    };
    if (data) {
      if (isFormData) {
        // Untuk FormData, browser akan secara otomatis mengatur Content-Type: multipart/form-data
        // Jadi, jangan atur Content-Type secara manual di sini.
        // Pastikan Content-Type tidak ada jika sebelumnya sudah diatur.
        delete options.headers["Content-Type"];
        options.body = data;
      } else {
        options.headers["Content-Type"] = "application/json";
        options.body = JSON.stringify(data);
      }
    }
    try {
      const response = await fetch(url, options);
      if (!response.ok) {
        // Log respons mentah dari server untuk debugging lebih lanjut
        const errorText = await response.text();
        console.error("Raw server response on HTTP error:", errorText);
        throw new Error(
          `HTTP error! status: ${response.status} - ${errorText}`
        );
      }
      return await response.json();
    } catch (error) {
      console.error("Fetch error:", error);
      // Memberikan pesan error yang lebih spesifik jika respons bukan JSON
      if (error instanceof SyntaxError && error.message.includes("JSON")) {
        return {
          success: false,
          message:
            "Respons dari server bukan JSON yang valid. Silakan cek konsol untuk detail.",
        };
      }
      return {
        success: false,
        message: "Terjadi kesalahan jaringan atau server.",
      };
    }
  }

  // --- Helper untuk menampilkan pesan ---
  function showMessage(element, message, isSuccess) {
    element.textContent = message;
    element.classList.remove("hidden");
    if (isSuccess) {
      element.classList.remove("error-message");
      element.classList.add("success-message");
    } else {
      element.classList.remove("success-message");
      element.classList.add("error-message");
    }
  }

  function hideMessage(element) {
    element.classList.add("hidden");
    element.textContent = "";
  }

  // --- Load Profile Data from Server ---
  async function loadProfileData() {
    const response = await fetchData("profile.php", "POST", {
      action: "get_profile",
    });
    if (response.success) {
      const data = response.data;

      // Function to format date from YYYY-MM-DD to DD Month YYYY
      function formatDateDisplay(dateString) {
        if (!dateString || dateString === "0000-00-00") return "N/A";
        const date = new Date(dateString);
        const options = { year: "numeric", month: "long", day: "numeric" };
        return date.toLocaleDateString("id-ID", options);
      }

      // Update display elements
      if (profileDisplayAvatar) profileDisplayAvatar.src = data.avatar_url;
      if (navbarUserAvatar) navbarUserAvatar.src = data.avatar_url; // Update navbar avatar
      if (profileDisplayName)
        profileDisplayName.textContent = data.full_name || "Pengguna Metisys";
      if (profileDisplayTitle)
        profileDisplayTitle.textContent = data.title || "Belum Diatur";
      if (profileDisplayBio)
        profileDisplayBio.textContent = data.bio || "Belum ada bio.";
      if (displayEmail) displayEmail.textContent = data.email;
      if (displayPhone) displayPhone.textContent = data.phone || "N/A";
      if (displayAddress) displayAddress.textContent = data.address || "N/A";
      if (displayDob)
        displayDob.textContent = formatDateDisplay(data.date_of_birth);
      if (displayGender) displayGender.textContent = data.gender || "N/A";
      if (displayOccupation)
        displayOccupation.textContent = data.occupation || "N/A";
      if (displayEmailNotif)
        displayEmailNotif.textContent =
          data.email_notifications == 1 ? "Aktif" : "Tidak Aktif";
      if (display2fa)
        display2fa.textContent =
          data.two_factor_auth == 1 ? "Aktif" : "Tidak Aktif";
      if (displayTimezone) displayTimezone.textContent = data.timezone || "N/A";
    } else {
      console.error("Failed to load profile data:", response.message);
      alert("Gagal memuat data profil: " + response.message);
    }
  }

  // Panggil saat inisialisasi profil
  loadProfileData();

  // --- Global Edit Profile Modal ---
  if (editProfileBtn) {
    editProfileBtn.addEventListener("click", function () {
      openModal(globalEditProfileModal);

      // Populate the modal form with current display values
      if (globalEditName) globalEditName.value = profileDisplayName.textContent;
      if (globalEditTitle)
        globalEditTitle.value = profileDisplayTitle.textContent;
      if (globalEditBio) globalEditBio.value = profileDisplayBio.textContent;
      if (globalEditEmail) globalEditEmail.value = displayEmail.textContent; // Email readonly
      if (globalEditPhone)
        globalEditPhone.value =
          displayPhone.textContent === "N/A" ? "" : displayPhone.textContent;
      if (globalEditAddress)
        globalEditAddress.value =
          displayAddress.textContent === "N/A"
            ? ""
            : displayAddress.textContent;

      // Convert display date to YYYY-MM-DD for input type="date"
      if (globalEditDob) {
        const dobText = displayDob.textContent;
        if (dobText !== "N/A") {
          try {
            const parts = dobText.split(" ");
            const monthNames = [
              "Januari",
              "Februari",
              "Maret",
              "April",
              "Mei",
              "Juni",
              "Juli",
              "Agustus",
              "September",
              "Oktober",
              "November",
              "Desember",
            ];
            const monthIndex = monthNames.indexOf(parts[1]);
            if (monthIndex > -1) {
              // Perhatikan bahwa Date constructor bulan adalah 0-indexed
              const dobDate = new Date(parts[2], monthIndex, parts[0]);
              // Format ke YYYY-MM-DD
              const year = dobDate.getFullYear();
              const month = String(dobDate.getMonth() + 1).padStart(2, "0");
              const day = String(dobDate.getDate()).padStart(2, "0");
              globalEditDob.value = `${year}-${month}-${day}`;
            }
          } catch (e) {
            console.error("Error parsing date for global-edit-dob:", e);
            globalEditDob.value = "";
          }
        } else {
          globalEditDob.value = "";
        }
      }

      if (globalEditGender)
        globalEditGender.value =
          displayGender.textContent === "N/A" ? "" : displayGender.textContent;
      if (globalEditOccupation)
        globalEditOccupation.value =
          displayOccupation.textContent === "N/A"
            ? ""
            : displayOccupation.textContent;
      if (globalEditEmailNotif)
        globalEditEmailNotif.checked =
          displayEmailNotif.textContent === "Aktif";
      if (globalEdit2fa)
        globalEdit2fa.checked = display2fa.textContent === "Aktif";
      if (globalEditTimezone)
        globalEditTimezone.value =
          displayTimezone.textContent === "N/A"
            ? ""
            : displayTimezone.textContent;
    });
  }

  cancelModalBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      closeModal(globalEditProfileModal);
      closeModal(deleteAccountModal);
      closeModal(changePasswordModal);
      // Reset password form on cancel
      changePasswordForm.reset();
      hideMessage(changePasswordErrorMessage);
      hideMessage(changePasswordSuccessMessage);
    });
  });

  if (globalProfileEditForm) {
    globalProfileEditForm.addEventListener("submit", async function (e) {
      e.preventDefault();
      console.log("Menyimpan perubahan profil global...");

      const formData = new FormData(this);
      formData.append("action", "update_profile");

      const response = await fetchData("profile.php", "POST", formData, true); // true for FormData

      if (response.success) {
        alert(response.message);
        closeModal(globalEditProfileModal);
        loadProfileData(); // Reload data to update all displays
      } else {
        alert("Gagal memperbarui profil: " + response.message);
      }
    });
  }

  // --- Section-specific Edit Buttons ---
  editSectionBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      const card = this.closest(".profile-card");
      const viewMode = card.querySelector(".card-content.view-mode");
      const editMode = card.querySelector(
        `.card-content.edit-mode[data-section="${this.dataset.section}"]`
      );

      if (viewMode && editMode) {
        viewMode.classList.add("hidden");
        editMode.classList.remove("hidden");

        // Populate inline edit forms with current display values
        const section = this.dataset.section;
        if (section === "contact") {
          if (editMode.querySelector("#edit-email"))
            editMode.querySelector("#edit-email").value =
              displayEmail.textContent;
          if (editMode.querySelector("#edit-phone"))
            editMode.querySelector("#edit-phone").value =
              displayPhone.textContent === "N/A"
                ? ""
                : displayPhone.textContent;
          if (editMode.querySelector("#edit-address"))
            editMode.querySelector("#edit-address").value =
              displayAddress.textContent === "N/A"
                ? ""
                : displayAddress.textContent;
        } else if (section === "personal") {
          if (editMode.querySelector("#edit-dob")) {
            const dobText = displayDob.textContent;
            if (dobText !== "N/A") {
              try {
                const parts = dobText.split(" ");
                const monthNames = [
                  "Januari",
                  "Februari",
                  "Maret",
                  "April",
                  "Mei",
                  "Juni",
                  "Juli",
                  "Agustus",
                  "September",
                  "Oktober",
                  "November",
                  "Desember",
                ];
                const monthIndex = monthNames.indexOf(parts[1]);
                if (monthIndex > -1) {
                  const dobDate = new Date(parts[2], monthIndex, parts[0]);
                  const year = dobDate.getFullYear();
                  const month = String(dobDate.getMonth() + 1).padStart(2, "0");
                  const day = String(dobDate.getDate()).padStart(2, "0");
                  editMode.querySelector(
                    "#edit-dob"
                  ).value = `${year}-${month}-${day}`;
                }
              } catch (e) {
                console.error("Error parsing date for edit-dob:", e);
                editMode.querySelector("#edit-dob").value = "";
              }
            } else {
              editMode.querySelector("#edit-dob").value = "";
            }
          }
          if (editMode.querySelector("#edit-gender"))
            editMode.querySelector("#edit-gender").value =
              displayGender.textContent === "N/A"
                ? ""
                : displayGender.textContent;
          if (editMode.querySelector("#edit-occupation"))
            editMode.querySelector("#edit-occupation").value =
              displayOccupation.textContent === "N/A"
                ? ""
                : displayOccupation.textContent;
        } else if (section === "security") {
          if (editMode.querySelector("#edit-email-notif"))
            editMode.querySelector("#edit-email-notif").checked =
              displayEmailNotif.textContent === "Aktif";
          if (editMode.querySelector("#edit-2fa"))
            editMode.querySelector("#edit-2fa").checked =
              display2fa.textContent === "Aktif";
          if (editMode.querySelector("#edit-timezone"))
            editMode.querySelector("#edit-timezone").value =
              displayTimezone.textContent === "N/A"
                ? ""
                : displayTimezone.textContent;
        }
      }
    });
  });

  cancelInlineBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      const form = this.closest("form");
      const card = this.closest(".profile-card");
      const viewMode = card.querySelector(".card-content.view-mode");

      if (form && viewMode) {
        form.classList.add("hidden");
        viewMode.classList.remove("hidden");
      }
    });
  });

  saveInlineForms.forEach((form) => {
    // Attach listener to the form itself
    form.addEventListener("submit", async function (e) {
      e.preventDefault();
      const section = this.dataset.section;
      console.log(`Menyimpan perubahan untuk bagian ${section}...`);

      const formData = new FormData(this);
      formData.append("action", "update_profile");

      const response = await fetchData("profile.php", "POST", formData, true); // true for FormData

      if (response.success) {
        alert(response.message);
        const card = this.closest(".profile-card");
        const viewMode = card.querySelector(".card-content.view-mode");
        this.classList.add("hidden");
        viewMode.classList.remove("hidden");
        loadProfileData(); // Reload data to update all displays
      } else {
        alert(`Gagal memperbarui bagian ${section}: ` + response.message);
      }
    });
  });

  // --- Delete Account Modal ---
  if (deleteAccountBtn) {
    deleteAccountBtn.addEventListener("click", function () {
      openModal(deleteAccountModal);
      if (deleteConfirmInput) deleteConfirmInput.value = ""; // Reset input
      if (confirmDeleteBtn) confirmDeleteBtn.disabled = true; // Nonaktifkan tombol
    });
  }

  if (deleteConfirmInput) {
    deleteConfirmInput.addEventListener("input", function () {
      if (confirmDeleteBtn)
        confirmDeleteBtn.disabled =
          this.value.trim().toUpperCase() !== "HAPUS AKUN SAYA";
    });
  }

  if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener("click", async function () {
      if (
        deleteConfirmInput &&
        deleteConfirmInput.value.trim().toUpperCase() === "HAPUS AKUN SAYA"
      ) {
        const dataToSend = {
          action: "delete_account",
          confirmation_text: deleteConfirmInput.value.trim().toUpperCase(),
        };

        const response = await fetchData("profile.php", "POST", dataToSend);

        if (response.success) {
          alert(response.message);
          // Redirect to login page after successful deletion
          window.location.href = "login.php";
        } else {
          alert("Gagal menghapus akun: " + response.message);
          // Close modal even if there's an error, unless it's a critical error
          closeModal(deleteAccountModal);
        }
      }
    });
  }

  // --- Avatar Upload ---
  const editAvatarBtn = document.getElementById("edit-avatar-button");
  const avatarUploadInput = document.getElementById("avatar-upload-input");

  if (editAvatarBtn) {
    editAvatarBtn.addEventListener("click", function () {
      avatarUploadInput.click(); // Trigger the hidden file input
    });
  }

  if (avatarUploadInput) {
    avatarUploadInput.addEventListener("change", async function (e) {
      const file = e.target.files[0];
      if (file) {
        const formData = new FormData();
        formData.append("action", "upload_avatar");
        formData.append("avatar", file);

        const response = await fetchData("profile.php", "POST", formData, true); // true for FormData

        if (response.success) {
          alert(response.message);
          if (profileDisplayAvatar)
            profileDisplayAvatar.src = response.avatar_url;
          if (navbarUserAvatar) navbarUserAvatar.src = response.avatar_url; // Update navbar avatar
        } else {
          alert("Gagal mengunggah avatar: " + response.message);
        }
      }
    });
  }

  // --- Change Password Modal ---
  if (changePasswordBtn) {
    changePasswordBtn.addEventListener("click", function () {
      openModal(changePasswordModal);
      // Reset form dan sembunyikan pesan saat modal dibuka
      changePasswordForm.reset();
      hideMessage(changePasswordErrorMessage);
      hideMessage(changePasswordSuccessMessage);
    });
  }

  if (changePasswordForm) {
    changePasswordForm.addEventListener("submit", async function (e) {
      e.preventDefault();
      hideMessage(changePasswordErrorMessage);
      hideMessage(changePasswordSuccessMessage);

      const currentPassword = currentPasswordInput.value;
      const newPassword = newPasswordInput.value;
      const confirmNewPassword = confirmNewPasswordInput.value;

      if (newPassword.length < 6) {
        showMessage(
          changePasswordErrorMessage,
          "Kata sandi baru minimal 6 karakter.",
          false
        );
        return;
      }
      if (newPassword !== confirmNewPassword) {
        showMessage(
          changePasswordErrorMessage,
          "Konfirmasi kata sandi baru tidak cocok.",
          false
        );
        return;
      }

      const dataToSend = {
        action: "change_password",
        current_password: currentPassword,
        new_password: newPassword,
      };

      const response = await fetchData("profile.php", "POST", dataToSend); // Panggil AJAX ke profile.php

      if (response.success) {
        showMessage(changePasswordSuccessMessage, response.message, true);
        changePasswordForm.reset(); // Clear form on success
        // Optional: tutup modal setelah beberapa detik
        setTimeout(() => {
          closeModal(changePasswordModal);
          hideMessage(changePasswordSuccessMessage);
        }, 3000);
      } else {
        showMessage(changePasswordErrorMessage, response.message, false);
      }
    });
  }

  // --- Toggle Password Visibility ---
  togglePasswordButtons.forEach((btn) => {
    btn.addEventListener("click", function () {
      const targetId = this.dataset.target;
      const targetInput = document.getElementById(targetId);
      const icon = this.querySelector("i");

      if (targetInput.type === "password") {
        targetInput.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
      } else {
        targetInput.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
      }
    });
  });

  // --- Logout Modal Logic (Duplikasi atau refactor ke file terpisah jika ingin global) ---
  // Karena ini adalah halaman mandiri, kita perlu mengulang logika logout di sini
  const logoutTriggers = document.querySelectorAll(".logout-trigger");
  const logoutModal = document.getElementById("logoutModal");
  const confirmLogoutBtn = document.getElementById("confirmLogoutBtn");
  const cancelLogoutBtn = document.getElementById("cancelLogoutBtn");
  const userProfileDropdown = document.getElementById("userProfileDropdown");
  const userInfoTrigger = userProfileDropdown
    ? userProfileDropdown.querySelector(".user-info-trigger")
    : null;
  const dropdownMenu = userProfileDropdown
    ? userProfileDropdown.querySelector(".dropdown-menu")
    : null;

  if (logoutModal) {
    logoutTriggers.forEach((trigger) => {
      trigger.addEventListener("click", function (e) {
        e.preventDefault();
        logoutModal.classList.add("show");
        body.classList.add("modal-open"); // Pastikan body blur
        if (dashboardWrapper) {
          dashboardWrapper.classList.add("modal-active-bg"); // Pastikan dashboard blur
        }
        if (dropdownMenu) dropdownMenu.classList.remove("show");
        if (userInfoTrigger) {
          const arrowIcon = userInfoTrigger.querySelector(".dropdown-arrow");
          if (arrowIcon) {
            arrowIcon.classList.remove("fa-caret-down");
            arrowIcon.classList.add("fa-caret-up");
          }
        }
      });
    });

    cancelLogoutBtn.addEventListener("click", function () {
      logoutModal.classList.remove("show");
      // Hanya hapus modal-open dan modal-active-bg jika tidak ada modal lain yang aktif
      const activeModals = document.querySelectorAll(".modal-overlay.active");
      const logoutModalIsActive = logoutModal.classList.contains("show"); // Cek apakah modal logout masih aktif
      if (activeModals.length === 0 && !logoutModalIsActive) {
        body.classList.remove("modal-open");
        if (dashboardWrapper) {
          dashboardWrapper.classList.remove("modal-active-bg");
        }
      }
    });

    confirmLogoutBtn.addEventListener("click", function () {
      window.location.href = "logout_handler.php";
    });

    logoutModal.addEventListener("click", function (e) {
      if (e.target === logoutModal) {
        logoutModal.classList.remove("show");
        // Hanya hapus modal-open dan modal-active-bg jika tidak ada modal lain yang aktif
        const activeModals = document.querySelectorAll(".modal-overlay.active");
        const logoutModalIsActive = logoutModal.classList.contains("show"); // Cek apakah modal logout masih aktif
        if (activeModals.length === 0 && !logoutModalIsActive) {
          body.classList.remove("modal-open");
          if (dashboardWrapper) {
            dashboardWrapper.classList.remove("modal-active-bg");
          }
        }
      }
    });
  }
  // --- End Logout Modal Logic ---

  // --- Sidebar & Navbar Interaction (Duplikasi dari dashboard.js) ---
  const hamburgerBtn = document.getElementById("hamburgerBtn");
  const navbarPageTitle = document.getElementById("navbarPageTitle");
  const sidebar = document.querySelector(".sidebar");
  const sidebarNavLinks = document.querySelectorAll(".sidebar-nav .nav-link"); // Ambil lagi untuk profile-script.js

  if (hamburgerBtn) {
    hamburgerBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      dashboardWrapper.classList.toggle("sidebar-collapsed");
    });
  }

  if (userInfoTrigger) {
    userInfoTrigger.addEventListener("click", function (e) {
      e.stopPropagation();
      dropdownMenu.classList.toggle("show");
      const arrowIcon = userInfoTrigger.querySelector(".dropdown-arrow");
      if (arrowIcon) {
        if (dropdownMenu.classList.contains("show")) {
          arrowIcon.classList.remove("fa-caret-down");
          arrowIcon.classList.add("fa-caret-up");
        } else {
          arrowIcon.classList.remove("fa-caret-up");
          arrowIcon.classList.add("fa-caret-down");
        }
      }
    });
  }

  document.addEventListener("click", function (e) {
    if (userProfileDropdown && !userProfileDropdown.contains(e.target)) {
      if (dropdownMenu) dropdownMenu.classList.remove("show");
      if (userInfoTrigger) {
        const arrowIcon = userInfoTrigger.querySelector(".dropdown-arrow");
        if (arrowIcon) {
          arrowIcon.classList.remove("fa-caret-up");
          arrowIcon.classList.add("fa-caret-down");
        }
      }
    }

    // Tutup sidebar jika diklik di luar saat tidak collapsed
    if (
      !dashboardWrapper.classList.contains("sidebar-collapsed") &&
      sidebar &&
      !sidebar.contains(e.target) &&
      hamburgerBtn &&
      !hamburgerBtn.contains(e.target)
    ) {
      dashboardWrapper.classList.add("sidebar-collapsed");
    }
  });

  // --- PENTING: Revisi penanganan klik sidebar di profile-script.js ---
  // Di halaman profile.php, klik pada link sidebar (kecuali logout) harus mengarah ke dashboard.php
  sidebarNavLinks.forEach((link) => {
    // Jika link adalah logout, biarkan logika logout modal yang menangani (sudah di atas)
    if (link.classList.contains("logout-trigger")) {
      return;
    }

    // Jika link mengarah ke dashboard.php, biarkan browser melakukan navigasi penuh
    // Ini akan memuat ulang dashboard.php dan JavaScript di dashboard.php akan menangani
    // aktivasi section yang benar berdasarkan parameter URL.
    // TIDAK ADA e.preventDefault() di sini untuk link dashboard.php
    if (
      link.getAttribute("href") &&
      link.getAttribute("href").startsWith("dashboard.php")
    ) {
      // Biarkan perilaku default browser yang terjadi (navigasi halaman penuh)
      // Tidak perlu menambahkan event listener khusus di sini, cukup pastikan
      // tidak ada preventDefault yang menghentikannya.
      // Jika ada event listener yang mencegah default, kita harus menghapusnya atau memodifikasinya.
      // Karena kita mendefinisikan event listener di bawah, kita bisa pastikan ini.
      link.addEventListener("click", function (e) {
        // Jangan lakukan preventDefault untuk link ke dashboard.php
        // Biarkan browser melakukan navigasi penuh
        // Ini akan memuat dashboard.php dan JS di dashboard.php akan mengaktifkan section yang benar
      });
    } else if (link.getAttribute("href") === "profile.php") {
      // Jika link adalah ke halaman profile.php itu sendiri, biarkan default (reload halaman)
      // atau jika href="#", biarkan default (tidak terjadi apa-apa)
      link.addEventListener("click", function (e) {
        // Tidak ada preventDefault di sini
      });
    }
  });

  // Set judul navbar untuk halaman profil
  if (navbarPageTitle) {
    navbarPageTitle.textContent = "Profil User";
  }
  // --- End Sidebar & Navbar Interaction ---
});
