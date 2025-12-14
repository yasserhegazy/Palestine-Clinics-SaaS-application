<?php 
namespace App\Helpers;

class TimeSlotGenerator{

    public array $working_time;
    public array $booking_slots;
    public int $duration;

    public function __construct($working_time, $duration, $booking_slots)
    {
        $this->working_time = $working_time;
        $this->duration = $duration;
        $this->booking_slots = $booking_slots;
    }


    public function convert_time_to_minutes(string $time)
    {
        // Split the time into two parts, hours, and minutes
        $parts = explode(":", $time);
        $hours = (int)$parts[0]; 
        $minutes = (int)$parts[1];
        return $hours * 60 + $minutes;
    }

    private function convert_minutes_to_time(int $minutes)
    {
        $hours = intdiv($minutes, 60);
        $time_minutes = $minutes % 60;
        
        if($hours < 10)
            $hours = "0".$hours;
        
        if($time_minutes < 10){
            $time_minutes = "0".$time_minutes;
        }
        return $hours.":".$time_minutes;
    }

    private function sorting_existing_appintements()
    { 
        usort($this->booking_slots, function($a, $b) { 
            $startA = $this->convert_time_to_minutes($a['start']); 
            $startB = $this->convert_time_to_minutes($b['start']); 
            // Sort based on the differences between the start times
            return $startA <=> $startB;
        });
    }


    private function merge_overlapping_intervals()
    {
        $merged = [];

        foreach($this->booking_slots as $slot){
            $start = $this->convert_time_to_minutes($slot['start']);
            $end = $this->convert_time_to_minutes($slot['end']);
            // Check if there is an overlap, or the array is empty
            if(empty($merged) || $merged[count($merged) - 1]['end'] < $start){
                $merged[] = ['start' => $start, 'end' => $end];
            }
            else{
                // Overlap, so we merge the intervals
                $merged[count($merged) - 1]['end'] = max($merged[count($merged) - 1]['end'], $end);
            }
        }
        $this->booking_slots = $merged;
    }

    // In minutes
    private function generate_all_time_slots(): array
    {
        $start_in_minutes = $this->convert_time_to_minutes($this->working_time['start']);
        $end_in_minutes   = $this->convert_time_to_minutes($this->working_time['end']);

        $duration = $this->duration;

        $available_slots = [];
        $current = $start_in_minutes;

        while ($current + $duration <= $end_in_minutes) {
            $available_slots[] = $this->convert_minutes_to_time($current);
            $current += $duration;
        }

        return $available_slots;
    }
    
    // Base code to check if there is a conflict between two time slots
    private function is_conflict($slot_start, $slot_end, $new_slot_start, $new_slot_end): bool
    {
        return $slot_start < $new_slot_end && $new_slot_start < $slot_end;
    }

    private function is_conflict_with_the_working_day($new_slot_time):bool
    {
        $new_slot_start_in_minutes = $this->convert_time_to_minutes($new_slot_time[0]);
        $new_slot_end_in_minutes = $this->convert_time_to_minutes($new_slot_time[1]);
        
        $left = 0;
        $right = count($this->booking_slots) - 1;

        while($left <= $right){
            $mid = intdiv($left + $right, 2);

            // They are represented as integers, from the merging method
            $slot_start_in_minutes = $this->booking_slots[$mid]['start']; 
            $slot_end_in_minutes = $this->booking_slots[$mid]['end']; 
            
            // To check if there is direct overlap
            if($this->is_conflict(
                $slot_start_in_minutes,
                $slot_end_in_minutes, 
                $new_slot_start_in_minutes, 
                $new_slot_end_in_minutes
            )){
                return true;
            } 
            // Applying binary search branching
            if ($new_slot_end_in_minutes <= $slot_start_in_minutes) {
                $right = $mid - 1;
            }
            else if ($new_slot_start_in_minutes >= $slot_end_in_minutes) {
                $left = $mid + 1;
            }
        }
        return false;
    }

    
    public function get_final_available_slots()
    {
        
        $this->sorting_existing_appintements();

        $this->merge_overlapping_intervals();

        $available_slots = $this->generate_all_time_slots();

        $final_available_slots = [];

        foreach ($available_slots as $slot_start) {

            $slot_start_min = $this->convert_time_to_minutes($slot_start);
        
            $slot_end_min = $slot_start_min + $this->duration;
            
            $slot_end = $this->convert_minutes_to_time($slot_end_min);

            // if it's not conflicting with any booking, add it to final available slots
            if (!$this->is_conflict_with_the_working_day([$slot_start, $slot_end])) {
                $final_available_slots[] = $slot_start;
            }
        }

        return $final_available_slots;
    }

    // For back-to-back services
    public function set_services(array $services)
    {
        // We just make the duration larger
        $this->duration = array_sum($services);
    }
}