<?php
/**
 * ============================================================================
 * PRIORITY QUEUE IMPLEMENTATION (MIN-HEAP)
 * ============================================================================
 * 
 * ALGORITHM: Binary Min-Heap for Priority Management
 * 
 * PURPOSE:
 * - Manage waitlist with VIP and early booker priority
 * - Ensure VIP customers get served first
 * - Maintain fairness: early bookers get priority over late bookers
 * 
 * DATA STRUCTURE:
 * - Binary Min-Heap stored as array
 * - Parent at index i, children at 2i+1 and 2i+2
 * - Root (index 0) always has highest priority (lowest score)
 * 
 * PRIORITY CALCULATION:
 * - VIP Platinum: 1000
 * - VIP Gold: 2000
 * - VIP Silver: 3000
 * - VIP Bronze: 4000
 * - Regular: 5000 + timestamp (early = lower number)
 * 
 * OPERATIONS:
 * - enqueue(data, isVip, vipLevel): O(log n)
 *   * Insert at end, heapify up to maintain heap property
 * 
 * - dequeue(): O(log n)
 *   * Remove root, move last to root, heapify down
 * 
 * - peek(): O(1)
 *   * View root without removing
 * 
 * - isEmpty(): O(1)
 *   * Check if size is 0
 * 
 * - getSize(): O(1)
 *   * Return current size
 * 
 * HEAP PROPERTY:
 * - Min-Heap: parent.priority <= children.priority
 * - If priorities equal, earlier timestamp wins
 * 
 * COMPLEXITY ANALYSIS:
 * - Insert: O(log n) - heapify up
 * - Remove: O(log n) - heapify down
 * - Peek: O(1) - access root
 * - Space: O(n) - array storage
 * 
 * PERSISTENCE:
 * - Saves to JSON file after each operation
 * - Loads from file on initialization
 * - File: app/assets/priority_waitlist.json
 * 
 * ADVANTAGES OVER FIFO QUEUE:
 * - VIPs get priority regardless of arrival time
 * - Early bookers still rewarded
 * - Fair system: no starvation
 * - Efficient: O(log n) vs O(1) but with priority support
 * 
 * @version 2.0
 * @author Sakura Sushi Development Team
 * ============================================================================
 */

class PriorityQueueNode {
    public $data;
    public $priority;
    public $timestamp;
    
    public function __construct($data, $priority, $timestamp) {
        $this->data = $data;
        $this->priority = $priority;
        $this->timestamp = $timestamp;
    }
}

class PriorityQueue {
    private $heap = [];
    private $size = 0;
    private static $storageFile = 'priority_waitlist.json';
    
    /**
     * Insert with priority - O(log n)
     * VIPs get lower priority numbers (higher priority)
     */
    public function enqueue($data, $isVip = false, $vipLevel = null) {
        $timestamp = microtime(true);
        $priority = $this->calculatePriority($isVip, $vipLevel, $timestamp);
        
        $node = new PriorityQueueNode($data, $priority, $timestamp);
        $this->heap[] = $node;
        $this->size++;
        $this->heapifyUp($this->size - 1);
        $this->save();
        
        return $priority;
    }
    
    /**
     * Remove highest priority item - O(log n)
     */
    public function dequeue() {
        if ($this->size === 0) return null;
        
        $root = $this->heap[0];
        $this->heap[0] = $this->heap[$this->size - 1];
        array_pop($this->heap);
        $this->size--;
        
        if ($this->size > 0) {
            $this->heapifyDown(0);
        }
        
        $this->save();
        return $root->data;
    }
    
    /**
     * View highest priority item - O(1)
     */
    public function peek() {
        return $this->size > 0 ? $this->heap[0]->data : null;
    }
    
    /**
     * Calculate priority score
     * Lower score = Higher priority
     */
    private function calculatePriority($isVip, $vipLevel, $timestamp) {
        if (!$isVip) {
            // Regular customer: base 5000 + timestamp (early bookers get lower number)
            return 5000 + intval($timestamp);
        }
        
        // VIP customers get priority based on level
        switch ($vipLevel) {
            case 'platinum': return 1000;
            case 'gold': return 2000;
            case 'silver': return 3000;
            case 'bronze': return 4000;
            default: return 5000 + intval($timestamp);
        }
    }
    
    /**
     * Heapify up - maintain min-heap property
     */
    private function heapifyUp($index) {
        while ($index > 0) {
            $parentIndex = intval(($index - 1) / 2);
            
            // Compare priority, if equal compare timestamp (earlier = higher priority)
            if ($this->comparePriority($this->heap[$index], $this->heap[$parentIndex]) >= 0) {
                break;
            }
            
            // Swap
            $temp = $this->heap[$index];
            $this->heap[$index] = $this->heap[$parentIndex];
            $this->heap[$parentIndex] = $temp;
            
            $index = $parentIndex;
        }
    }
    
    /**
     * Heapify down - maintain min-heap property
     */
    private function heapifyDown($index) {
        while (true) {
            $leftChild = 2 * $index + 1;
            $rightChild = 2 * $index + 2;
            $smallest = $index;
            
            if ($leftChild < $this->size && 
                $this->comparePriority($this->heap[$leftChild], $this->heap[$smallest]) < 0) {
                $smallest = $leftChild;
            }
            
            if ($rightChild < $this->size && 
                $this->comparePriority($this->heap[$rightChild], $this->heap[$smallest]) < 0) {
                $smallest = $rightChild;
            }
            
            if ($smallest === $index) break;
            
            // Swap
            $temp = $this->heap[$index];
            $this->heap[$index] = $this->heap[$smallest];
            $this->heap[$smallest] = $temp;
            
            $index = $smallest;
        }
    }
    
    /**
     * Compare two nodes
     * Returns: -1 if a has higher priority, 1 if b has higher priority, 0 if equal
     */
    private function comparePriority($a, $b) {
        if ($a->priority !== $b->priority) {
            return $a->priority - $b->priority;
        }
        // If priority equal, earlier timestamp wins
        return $a->timestamp - $b->timestamp;
    }
    
    /**
     * Get all items sorted by priority - O(n log n)
     */
    public function toArray() {
        $sorted = [];
        $tempHeap = $this->heap;
        $tempSize = $this->size;
        
        while ($this->size > 0) {
            $sorted[] = $this->dequeue();
        }
        
        $this->heap = $tempHeap;
        $this->size = $tempSize;
        
        return $sorted;
    }
    
    public function getSize() {
        return $this->size;
    }
    
    public function isEmpty() {
        return $this->size === 0;
    }
    
    /**
     * Persist to file
     */
    private function save() {
        $data = [
            'heap' => array_map(function($node) {
                return [
                    'data' => $node->data,
                    'priority' => $node->priority,
                    'timestamp' => $node->timestamp
                ];
            }, $this->heap),
            'size' => $this->size
        ];
        
        $path = dirname(__DIR__) . '/assets/' . self::$storageFile;
        file_put_contents($path, json_encode($data));
    }
    
    /**
     * Load from file
     */
    public function load() {
        $path = dirname(__DIR__) . '/assets/' . self::$storageFile;
        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);
            if (isset($data['heap']) && is_array($data['heap'])) {
                $this->heap = [];
                foreach ($data['heap'] as $item) {
                    $this->heap[] = new PriorityQueueNode(
                        $item['data'],
                        $item['priority'],
                        $item['timestamp']
                    );
                }
                $this->size = $data['size'] ?? count($this->heap);
            }
        }
    }
}
