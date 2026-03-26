<?php

namespace App\Console\Commands;

use App\Http\Controllers\GeneralAttendanceController;
use App\Models\School;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ProcessDailyAbsences extends Command
{
    protected $signature = 'attendance:process-absences {--school-id=} {--date=} {--force}';
    protected $description = 'Process absences for schools after entry time ends';

    public function handle()
    {
        $schoolId = $this->option('school-id');
        $date = $this->option('date') ?? Carbon::now()->toDateString();
        $force = $this->option('force');
        
        $controller = new GeneralAttendanceController();
        
        if ($schoolId) {
            // Procesar una escuela específica
            $request = new \Illuminate\Http\Request();
            $request->merge([
                'school_id' => $schoolId,
                'date' => $date,
                'force' => $force,
            ]);
            
            $response = $controller->processAbsences($request);
            $result = $response->getData();
            
            if ($result->success) {
                $this->info($result->message);
            } else {
                $this->error($result->message);
            }
        } else {
            // Procesar todas las escuelas
            $schools = School::all();
            
            foreach ($schools as $school) {
                $this->info("Procesando escuela: {$school->name}");
                
                $request = new \Illuminate\Http\Request();
                $request->merge([
                    'school_id' => $school->id,
                    'date' => $date,
                    'force' => $force,
                ]);
                
                $response = $controller->processAbsences($request);
                $result = $response->getData();
                
                if ($result->success) {
                    $this->line($result->message);
                } else {
                    $this->error($result->message);
                }
            }
        }
        
        return Command::SUCCESS;
    }
}