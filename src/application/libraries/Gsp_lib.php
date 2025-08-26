<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Simple GSP (sequential pattern mining) for single-item events per timestamp.
 * Input: $sequences = [ ['A','B','C'], ['A','C'], ... ]
 * Output: array of ['pattern' => ['A','B'], 'support' => 3]
 */
class Gsp_lib {

    public function __construct() {}

    public function mine($sequences, $min_support = 2, $max_len = 5) {
        // 1. Frequent 1-sequences
        $L = array(); // L[k] = frequent sequences length k
        $C1 = $this->generate_C1($sequences);
        $L1 = $this->filter_support($sequences, $C1, $min_support);
        $L[1] = $L1;

        $k = 2;
        while ($k <= $max_len && !empty($L[$k-1])) {
            // 2. Generate candidates Ck by self-join of L(k-1)
            $Ck = $this->generate_candidates($L[$k-1]);
            if (empty($Ck)) break;

            // 3. Prune candidates if any (k-1)-subsequence is not frequent
            $Ck = $this->prune_candidates($Ck, $L[$k-1]);

            // 4. Count support and filter
            $Lk = $this->filter_support($sequences, $Ck, $min_support);
            $L[$k] = $Lk;
            $k++;
        }

        // Flatten results
        $results = array();
        foreach ($L as $k => $patterns) {
            foreach ($patterns as $p) {
                $results[] = $p;
            }
        }
        // sort by support desc, then by length desc
        usort($results, function($a,$b){
            if ($a['support'] == $b['support']) {
                return count($b['pattern']) - count($a['pattern']);
            }
            return $b['support'] - $a['support'];
        });
        return $results;
    }

    private function generate_C1($sequences) {
        $items = array();
        foreach ($sequences as $seq) {
            // each item counted at most once per sequence
            $seen = array();
            foreach (array_unique($seq) as $item) {
                $seen[$item] = true;
            }
            foreach ($seen as $item => $_) {
                if (!isset($items[$item])) $items[$item] = 0;
                $items[$item] += 1;
            }
        }
        $C1 = array();
        foreach ($items as $item => $count) {
            $C1[] = array('pattern' => array($item), 'support' => 0);
        }
        return $C1;
    }

    private function filter_support($sequences, $candidates, $min_support) {
        $result = array();
        foreach ($candidates as $cand) {
            $pat = $cand['pattern'];
            $support = 0;
            foreach ($sequences as $seq) {
                if ($this->is_subsequence($seq, $pat)) {
                    $support++;
                }
            }
            if ($support >= $min_support) {
                $result[] = array('pattern' => $pat, 'support' => $support);
            }
        }
        return $result;
    }

    private function is_subsequence($seq, $pat) {
        // two-pointer scan
        $i = 0; $j = 0;
        $n = count($seq); $m = count($pat);
        while ($i < $n && $j < $m) {
            if ($seq[$i] === $pat[$j]) {
                $j++;
            }
            $i++;
        }
        return $j === $m;
    }

    private function generate_candidates($Lprev) {
        $cands = array();
        $n = count($Lprev);
        for ($i=0; $i<$n; $i++) {
            for ($j=0; $j<$n; $j++) {
                $A = $Lprev[$i]['pattern'];
                $B = $Lprev[$j]['pattern'];
                // join if suffix(A) == prefix(B) for length-1..k-2
                $len = count($A);
                $ok = true;
                for ($t=1; $t<$len; $t++) {
                    if ($A[$t] !== $B[$t-1]) { $ok = false; break; }
                }
                if ($ok) {
                    $cand = $A;
                    $cand[] = $B[count($B)-1];
                    $cands[] = array('pattern' => $cand, 'support' => 0);
                }
            }
        }
        // remove duplicates
        $uniq = array();
        $seen = array();
        foreach ($cands as $c) {
            $key = implode("\x1f", $c['pattern']);
            if (!isset($seen[$key])) {
                $uniq[] = $c; $seen[$key] = true;
            }
        }
        return $uniq;
    }

    private function prune_candidates($cands, $Lprev) {
        // build a set of strings for fast lookup
        $set = array();
        foreach ($Lprev as $p) {
            $set[implode("\x1f", $p['pattern'])] = true;
        }
        $pruned = array();
        foreach ($cands as $c) {
            $pat = $c['pattern'];
            $k = count($pat);
            $all_subs_frequent = true;
            // all (k-1) subsequences must be frequent
            for ($i=0; $i<$k; $i++) {
                $sub = $pat;
                array_splice($sub, $i, 1);
                $key = implode("\x1f", $sub);
                if (!isset($set[$key])) { $all_subs_frequent = false; break; }
            }
            if ($all_subs_frequent) $pruned[] = $c;
        }
        return $pruned;
    }
}
