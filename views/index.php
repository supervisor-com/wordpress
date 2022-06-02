<script>
window.__supervisorcom = {}
window.__supervisorcom.request = (method, body) => {
  fetch('/wp-json/supervisorcom/v1/store', {
    method: method,
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': <?php echo json_encode(wp_create_nonce('wp_rest')); ?>
    },
    body: JSON.stringify(body),
  });
}

window.__supervisorcom.set = (key, value) => {
  window.postMessage(
    JSON.stringify(
      {
        type: `com.supervisor.v1.store`,
        key: key,
        value: value,
      }
    )
  );
}

window.__supervisorcom.delete = (key) => {
  window.postMessage(
    JSON.stringify(
      {
        type: `com.supervisor.v1.delete`,
        key: key,
      }
    )
  );
}

window.addEventListener("message", (event) => {
  const msg = JSON.parse(event.data);
  switch(msg.type) {
    case 'com.supervisor.v1.store':
      __supervisorcom.request("PUT", msg)
      break;
    case 'com.supervisor.v1.delete':
      __supervisorcom.request("DELETE", msg)
      break;
    default:
      return;
  }
})
</script>

<?php
  $supervisorcom_store = get_option('supervisorcom_v1_store');

  if (isset($supervisorcom_store['url'])) {
    $supervisorcom_url = $supervisorcom_store['url'];
  } else {
    $supervisorcom_secret = wp_generate_uuid4();
    update_option('supervisorcom_v1_secret', $supervisorcom_secret);

    $supervisorcom_url = add_query_arg(
      array(
        'secret' => $supervisorcom_secret,
        'url' => urlencode(get_site_url()),
        'channel_name' => 'wp-plugin',
        'channel_version' => '0.0.1'
      ),
      "https://my.supervisor.com/new",
    );
  }
?>
<iframe width="100%" height="1000" src="<?php echo $supervisorcom_url ?>"></iframe>
