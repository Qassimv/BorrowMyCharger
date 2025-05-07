document.addEventListener('DOMContentLoaded', function () {
  const searchInput = document.getElementById('searchChargingPoints');
  const filterSelect = document.getElementById('filterChargingPoints');
  const resultsContainer = document.getElementById('chargingPointResults');

  function sendSearchRequest() {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'SearchChargingPoints.php', true); 
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    const data = `search=${encodeURIComponent(searchInput.value)}&filter=${encodeURIComponent(filterSelect.value)}`;

    xhr.onload = function () {
      if (xhr.status === 200) {
        resultsContainer.innerHTML = xhr.responseText;
      } else {
        resultsContainer.innerHTML = '<p class="text-white">An error occurred while loading charging points.</p>';
      }
    };

    xhr.send(data);
  }

  searchInput.addEventListener('input', sendSearchRequest);
  filterSelect.addEventListener('change', sendSearchRequest);
  sendSearchRequest(); 
});
