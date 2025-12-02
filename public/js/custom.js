

document.querySelectorAll('.toggleSwitch').forEach(toggle => {
  toggle.addEventListener('change', function() {
    const statusId = this.dataset.target;
    const mlGroupId = this.dataset.ml;
    const statusText = document.getElementById(statusId);

    if (this.checked) {
      statusText.textContent = 'ON';
      statusText.style.color = 'green';
      if (mlGroupId) document.getElementById(mlGroupId).style.display = 'flex';
    } else {
      statusText.textContent = 'OFF';
      statusText.style.color = 'red';
      if (mlGroupId) document.getElementById(mlGroupId).style.display = 'none';
    }
  });
});