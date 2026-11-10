import { createPublicClient, createWalletClient, custom, http, parseAbi } from 'viem';
import { foundry } from 'viem/chains';

const CONTRACT_ADDRESS = import.meta.env.VITE_NOTE_NOTARY_ADDRESS;
const RPC_URL = import.meta.env.VITE_BLOCKCHAIN_RPC_URL || 'http://127.0.0.1:8545';

const NOTE_NOTARY_ABI = parseAbi([
  'function notarize(bytes32 noteHash) external',
  'function verify(bytes32 noteHash) external view returns (address author, uint256 timestamp)',
  'event NoteNotarized(bytes32 indexed noteHash, address indexed author, uint256 timestamp)',
]);

export const publicClient = createPublicClient({
  chain: foundry,
  transport: http(RPC_URL),
});

export async function connectWallet() {
  if (!window.ethereum) {
    throw new Error('MetaMask is not installed in this browser.');
  }

  const walletClient = createWalletClient({
    chain: foundry,
    transport: custom(window.ethereum),
  });

  const [address] = await walletClient.requestAddresses();

  // Ensure MetaMask is on Localhost 8545 (Chain ID 31337 / 0x7a69)
  try {
    await window.ethereum.request({
      method: 'wallet_switchEthereumChain',
      params: [{ chainId: '0x7a69' }],
    });
  } catch (switchError) {
    if (switchError.code === 4902) {
      await window.ethereum.request({
        method: 'wallet_addEthereumChain',
        params: [
          {
            chainId: '0x7a69',
            chainName: 'Anvil Localhost',
            rpcUrls: [RPC_URL],
            nativeCurrency: { name: 'ETH', symbol: 'ETH', decimals: 18 },
          },
        ],
      });
    }
  }

  return { walletClient, address };
}

export async function notarizeHashOnChain(noteHash) {
  const { walletClient, address } = await connectWallet();

  // 1. Send transaction via MetaMask
  // Check if this hash is already notarized on-chain
  const [existingAuthor, existingTimestamp] = await publicClient.readContract({
    address: CONTRACT_ADDRESS,
    abi: NOTE_NOTARY_ABI,
    functionName: 'verify',
    args: [noteHash],
  });
  if (existingTimestamp > 0n) {
    return {
      txHash: '0x' + '0'.repeat(64),
      address: existingAuthor,
    };
  }
  // 1. Send transaction via MetaMask
  const txHash = await walletClient.writeContract({
    address: CONTRACT_ADDRESS,
    abi: NOTE_NOTARY_ABI,
    functionName: 'notarize',
    args: [noteHash],
    account: address,
  });

  // 2. Wait for block confirmation
  await publicClient.waitForTransactionReceipt({ hash: txHash });

  return { txHash, address };
}
