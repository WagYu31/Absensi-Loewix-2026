// Ambil referensi elemen-elemen yang diperlukan
const signInForm = document.getElementById('sign-in-form');
const signUpForm = document.getElementById('sign-up-form');
const signInButton = document.getElementById('sign-in-button');
const signUpButton = document.getElementById('sign-up-button');
const nipInput = document.getElementById('nip');
const usernameInput = document.getElementById('username');
const newPasswordInput = document.getElementById('new-password');
const signUpSubmitButton = document.getElementById('sign-up-submit');
const toggleButton = document.querySelector('.toggle-button');

// Fungsi untuk menampilkan form sign in
function showSignInForm() {
  signUpForm.style.animation = 'fadeOut 0.3s forwards';
  signInForm.style.animation = 'fadeIn 0.3s forwards';
  setTimeout(function() {
    signUpForm.style.display = 'none';
    signInForm.style.display = 'block';
  }, 300);
}

// Fungsi untuk menampilkan form sign up
function showSignUpForm() {
  signInForm.style.animation = 'fadeOut 0.3s forwards';
  signUpForm.style.animation = 'fadeIn 0.3s forwards';
  setTimeout(function() {
    signInForm.style.display = 'none';
    signUpForm.style.display = 'block';
  }, 300);
}

// Fungsi untuk bergantian menampilkan form sign in dan sign up
function toggleForm() {
    if (signInForm.style.display === 'none') {
        showSignInForm();
    } else {
        showSignUpForm();
    }
}

// Event listener untuk tombol sign in
signInButton.addEventListener('click', showSignInForm);

// Event listener untuk tombol sign up
signUpButton.addEventListener('click', showSignUpForm);

// Event listener untuk tombol toggle
toggleButton.addEventListener('click', toggleForm);

// Validasi NIP pada form sign up
signUpSubmitButton.addEventListener('click', function(event) {
  event.preventDefault();

  const nip = nipInput.value;
  const username = usernameInput.value;
  const newPassword = newPasswordInput.value;

  // Lakukan validasi NIP di sisi server menggunakan AJAX request
  validateNIP(nip)
    .then(() => {
      // Jika NIP valid, kirim data form ke server untuk proses sign up
      sendSignUpData(nip, username, newPassword);
    })
    .catch((error) => {
      // Jika NIP tidak valid, tampilkan pesan error
      alert(error);
    });
});

// Fungsi untuk melakukan validasi NIP pada sisi server
function validateNIP(nip) {
  return new Promise((resolve, reject) => {
    // Lakukan validasi NIP dengan AJAX request ke server
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `validate_nip.php?nip=${nip}`, true);
    xhr.onload = function() {
      if (xhr.status === 200) {
        const response = JSON.parse(xhr.responseText);
        if (response.valid) {
          resolve();
        } else {
          reject('Invalid NIP. Please enter a valid NIP.');
        }
      } else {
        reject('An error occurred while validating NIP. Please try again later.');
      }
    };
    xhr.onerror = function() {
      reject('An error occurred while validating NIP. Please try again later.');
    };
    xhr.send();
  });
}

// Fungsi untuk mengirim data sign up ke server
function sendSignUpData(nip, username, newPassword) {
  // Lakukan pengiriman data sign up ke server menggunakan AJAX request atau teknik yang sesuai
  // Di sini, kita menggunakan contoh sederhana
  console.log('Sign up successful');
}
