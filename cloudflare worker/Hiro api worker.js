export default {
  async fetch(request, env, ctx) {
    // Create a new URL object so we can modify it
    const url = new URL(request.url);
    
    // Extract the inscription_id from the request path
    // e.g., https://your-worker.workers.dev/INSCRIPTION_ID_HERE
    const inscriptionId = url.pathname.slice(1);

    if (!inscriptionId) {
      return new Response('Inscription ID is missing in the path.', { status: 400 });
    }
    
    // The target Hiro API URL
    const targetUrl = `https://api.hiro.so/ordinals/v1/inscriptions/${inscriptionId}`;

    // Create a new request to forward to the Hiro API
    const newRequest = new Request(targetUrl, {
      method: request.method,
      headers: request.headers,
      body: request.body,
    });

    // Make the request and get the response
    const response = await fetch(newRequest);
    
    // Create new response headers and add CORS headers
    // This allows your WordPress site to call this Worker
    const headers = new Headers(response.headers);
    headers.set('Access-Control-Allow-Origin', '*'); // Or lock down to your domain
    headers.set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    headers.set('Access-Control-Allow-Headers', 'Content-Type, X-WP-Nonce');

    return new Response(response.body, {
      status: response.status,
      statusText: response.statusText,
      headers: headers,
    });
  },
};