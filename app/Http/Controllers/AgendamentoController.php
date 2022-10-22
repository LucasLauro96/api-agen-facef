<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Models\AgendamentoModel;
use Carbon\Carbon;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class AgendamentoController extends Controller
{

    private function publicQueue($id){
        $connection = new AMQPStreamConnection('192.168.18.9', 5672, 'guest', 'guest');
        $channel = $connection->channel();

        $channel->queue_declare('notificationScheduleQueue', false, false, false, false);
        
        
        $msg = new AMQPMessage(intval($id));
        
        $channel->basic_publish($msg, '', 'notificationScheduleQueue');
        
        $channel->close();
        $connection->close();
    }

    public function Create(Request $request){

        $startTime = Carbon::parse($request->startedAt);
        $endTime = Carbon::parse($request->finishedAt);
        $today = date('Y-m-d H:i:s', strtotime('-3 hour'));

        if($endTime <= $startTime){
            return response(json_encode(
                array(
                    'message' => 'A data final tem que ser maior que a data inicial',
                    'status' => 403
                )
            ), 403);
        } else if ($startTime < $today){
            return response(json_encode(
                array(
                    'message' => 'A data do agendamento tem que ser maior que agora!',
                    'status' => 403
                )
            ), 403);
        } 
        
        //Define a quantidade de horas;
        $totalHours = $endTime->diffInHours($startTime);
        if($totalHours == 0){
            $totalHours = 1;
        }
        //Define o valor do aluguel;
        $amount = $totalHours * 80; 

        $data = DB::table('scheduler')->select()
        ->where(function ($query) use ($startTime) {
            $query->where('startedAt', '<=', $startTime)
                  ->Where('finishedAt', '>=', $startTime);
        })
        ->orwhere(function ($query) use ($endTime) {
            $query->where('startedAt', '<=', $endTime)
                  ->where('finishedAt', '>=', $endTime);
        })
        ->count('id');

        if($data != 0){
            return response(json_encode(
                array(
                    'message' => 'Já existe uma reserva nesse horario',
                    'status' => 403
                )
            ), 403);
        }
                
        DB::beginTransaction();
        try {

            $agendamento = new AgendamentoModel;
            $agendamento->userId = $request->userId;
            $agendamento->startedAt = $startTime;
            $agendamento->finishedAt = $endTime;
            $agendamento->amount = $amount;
            $agendamento->save();
            
            DB::commit();

            if(isset($agendamento->id) && !empty($agendamento->id)){
                $this->publicQueue($agendamento->id);
            }

            return response($agendamento, 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response(json_encode(
                array(
                    'message' => 'Ocorreu um erro: '. $e->getMessage(),
                    'status' => 500
                )
            ), 500);
        }

    }
}
