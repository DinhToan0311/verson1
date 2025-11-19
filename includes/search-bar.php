<style>
  .search-box {
    display: flex;
    flex: 1;
    max-width: 600px;
    margin: 0 24px;
  }

  .search-input {
    flex: 1;
    height: 36px;
    padding: 0 12px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-right: none;
    border-radius: 18px 0 0 18px;
    outline: none;
  }

  .search-btn {
    width: 50px;
    height: 36px;
    background-color: #0066cc;
    border: none;
    border-radius: 0 18px 18px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
  }

  .search-btn i {
    color: white;
    font-size: 16px;
  }

  /* Responsive cho mobile */
  @media (max-width: 480px) {
    .search-container {
      padding: 8px 8px;
    }

    .search-input {
      font-size: 13px;
      padding: 0 10px;
    }

    .search-btn {
      width: 40px;
      height: 34px;
    }

    .search-btn i {
      font-size: 14px;
    }
  }
</style>
<!-- includes/search-bar.php -->

<head>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />

</head>
<form action="../main/search.php" method="GET" class="search-box">
  <input
    type="text"
    name="q"
    class="search-input"
    placeholder="Tìm kiếm video..."
    required />
  <button type="submit" class="search-btn">
    <i class="fas fa-search"></i>
  </button>
</form>