<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>

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

window.__supervisorcom.requestWithTimeoutAndRetry = async (timeout, method, body, retryDelay) => {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve();
    }, retryDelay);
  }).then(() => {
    let abortController = new AbortController();
    setTimeout(() => abortController.abort(), timeout);

    let requestOptions = {
      method: method,
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': <?php echo json_encode(wp_create_nonce('wp_rest')); ?>
      },
      body: JSON.stringify(body),
      signal: abortController.signal,
      credentials: 'include'
    };

    return fetch('/wp-json/supervisorcom/v1/store', requestOptions).then((response) => {
      return response.text();
    }).then(responseBody => {
      if (responseBody && responseBody.length) {
        return JSON.parse(responseBody)
      }
    }).then((data) => {
      return data;
    }).catch((err) => {
      if (err.name == "AbortError") {
        console.warn('Fetch timed out: ' + endpoint)
      } else {
        console.warn("Request error: ", err.message)
      }
      return __supervisorcom.requestWithTimeoutAndRetry(timeout, method, body, 1000);
    })
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

window.__supervisorcom.frameReloadInterval = setInterval(() => {
  document.getElementById('supervisor').src += '';
}, 15000)

window.addEventListener("message", (event) => {
  const msg = JSON.parse(event.data);
  switch(msg.type) {
    case 'com.supervisor.v1.store':
      __supervisorcom.requestWithTimeoutAndRetry(5000, "PUT", msg)
      break;
    case 'com.supervisor.v1.delete':
      __supervisorcom.requestWithTimeoutAndRetry(5000, "DELETE", msg)
      break;
    case 'com.supervisor.v1.load':
      clearInterval(__supervisorcom.frameReloadInterval)
      break;
    default:
      return;
  }
})

document.addEventListener("DOMContentLoaded", () => {
  document.getElementById('wpcontent').style = "padding: 0 !important;"
})
</script>

<?php
  $supervisorcom_store = get_option('supervisorcom_v1_store');

  if (isset($supervisorcom_store['url'])) {
    $supervisorcom_url = $supervisorcom_store['url'];
  } else {
    $supervisorcom_secret = wp_generate_uuid4();
    update_option('supervisorcom_v1_secret', $supervisorcom_secret);
    $channel_user = wp_get_current_user()->user_email;
    $channel_stats_url = add_query_arg(
      array(
        'secret' => $supervisorcom_secret,
      ),
      get_site_url()."/wp-json/supervisorcom/v2/cpus"
    );

    $supervisorcom_url = add_query_arg(
      array(
        'secret' => $supervisorcom_secret,
        'url' => urlencode(get_site_url()),
        'channel_name' => 'wordpress-plugin',
        'channel_version' => '0.0.2',
        'channel_user' => $channel_user,
        'channel_stats_url' => $channel_stats_url
      ),
      "https://my.supervisor.com/new",
    );
  }
?>
<iframe id='supervisor' width="100%" style='height: 640vh; position: absolute; left: 0; top: 0; right: 0; z-index: 10;' src="<?php echo(esc_attr($supervisorcom_url)); ?>"></iframe>
