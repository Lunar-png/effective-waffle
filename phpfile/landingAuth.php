<script>
  $(function () {
    const currentUser = <?php echo json_encode(
      $currentUserName,
      JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ); ?>;
    $('#welcomeUserHeading').text(`Welcome, ${currentUser}!`);
    $('#navUserDisplay').text(`Hi, ${currentUser}`);

    $('#logoutbutton').on('click', async function () {
      const button = this;
      button.disabled = true;
      try {
        const response = await fetch('phpfile/conditionlogin.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
          body: new URLSearchParams({ action: 'logout' })
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || 'Sign out failed.');
        window.location.href = result.redirect;
      } catch (error) {
        button.disabled = false;
        window.alert(error.message);
      }
    });
  });
</script>
