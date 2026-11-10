// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

contract NoteNotary {
    struct Record {
        address author;
        uint256 timestamp;
    }

    mapping(bytes32 => Record) public records;

    event NoteNotarized(bytes32 indexed noteHash, address indexed author, uint256 timestamp);

    function notarize(bytes32 noteHash) external {
        require(noteHash != bytes32(0), "Invalid hash");
        require(records[noteHash].timestamp == 0, "Already notarized");

        records[noteHash] = Record({
            author: msg.sender,
            timestamp: block.timestamp
        });

        emit NoteNotarized(noteHash, msg.sender, block.timestamp);
    }

    function verify(bytes32 noteHash) external view returns (address author, uint256 timestamp) {
        Record memory rec = records[noteHash];
        return (rec.author, rec.timestamp);
    }
}