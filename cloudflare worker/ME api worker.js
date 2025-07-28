export default {
  async fetch(request, env, ctx) {
    const url = new URL(request.url);
    // Extract the wallet address from the path
    // e.g., https://your-worker.workers.dev/WALLET_ADDRESS_HERE
    const walletAddress = url.pathname.slice(1);

    if (!walletAddress) {
      return new Response('Wallet address is missing in the path.', { status: 400 });
    }

    const targetUrl = `https://api-mainnet.magiceden.dev/v2/wallets/${walletAddress}`;
    
    // The ME API requires a specific 'accept' header
    const requestHeaders = new Headers(request.headers);
    requestHeaders.set('accept', 'application/json');

    const newRequest = new Request(targetUrl, {
      method: 'GET', // ME API is a GET request
      headers: requestHeaders,
    });

    const response = await fetch(newRequest);
    
    const headers = new Headers(response.headers);
    headers.set('Access-Control-Allow-Origin', '*'); // Or lock down to your domain
    headers.set('Access-Control-Allow-Methods', 'GET, OPTIONS');
    headers.set('Access-Control-Allow-Headers', 'Content-Type');

    return new Response(response.body, {
      status: response.status,
      statusText: response.statusText,
      headers: headers,
    });
  },
};