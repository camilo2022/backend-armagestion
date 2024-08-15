<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AccountsManagement;
use App\Models\Assignment;
use App\Models\AssignmentAccounts;
use App\Models\Campaign;
use App\Models\Configuration;
use App\Models\Payments;
use App\Models\Cycle;
use App\Models\exclusionManagerFija;
use App\Models\exclusionManagerMovil;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ManagementController extends Controller
{

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            ini_set('max_execution_time', 7200);
            ini_set('memory_limit', '4096M');
            /* ini_set('max_execution_time', 7200); */
            /* ini_set('memory_limit', '4096M'); */
            return $next($request);
        });
    }

    use ApiResponser;
    private $success = 'Consulta Exitosa.';
    private $error = 'Algo salió mal.';

    public function download(Request $request)
    {
        try {
            //Exclusiones moviles de ejecutivos
            $exclusionMovil = exclusionManagerMovil::all();
            //Exclusiones fijas de ejecutivos
            $exclusionFija = exclusionManagerFija::all();

            $settings = Configuration::with('assignments.model', 'campaign.model', 'focus.model', 'users', 'time_patterns')->findOrFail($request->id);
            $cycles = Cycle::whereIn('id', $settings->cycle_code)->get();

            $request->date = Carbon::parse($request->date)->format('Y-m-d');

            $management = AccountsManagement::selectRaw('(ROW_NUMBER() OVER ()) AS id, acma_start_datecomplete, acma_start_date, acma_start_time,
                acma_end_date_complete, acma_end_date, acma_end_time, peop_dni_c, peop_name_c, peop_dni_g, peop_name_g, acco_code_account, typi_name,
                typi_effective, stra_name, tyal_name, acma_minutes, acma_date_call_back, acma_time_call_back, date_part, renp_name, data_value, date as payment_date,
                fees_payment_value as payment_value, pafe_fees_type, acma_observation, assi_name, assi_id, camp_name, camp_id, acco_payment_reference, acco_code_client,
                acco_quantity_products, acco_portfolio_type, acco_leaflet, acco_segment, acco_cycle, acco_number_obligations, asac_day_past_due, asac_age,
                foal_name, prty_name, asac_balance, asac_discount as payment_discount, asac_discount_start_date, asac_discount_end_date, min, alli_name, alli_id, acma_iseffective,
                acma_contact_name')
                ->from(DB::raw('accounts_management'))
                ->where('camp_name', $settings->campaign->model->camp_name)
                ->whereIn('assi_name', $settings->assignments->pluck('model')->pluck('assi_name'))
                ->whereIn('foal_name', $settings->focus->pluck('model')->pluck('foal_name'))
                ->where('acma_start_date', $request->date)
                ->whereNotIn('peop_dni_g', ['12345678974', '124557865635'])
                ->when($settings->confirmation_block_fija == true && $request->checkBoxValue == true, function ($query) use ($exclusionFija) {
                    //Exclusion Fija
                    $query->whereNotIn('peop_dni_g', $exclusionFija->where('status', true)->pluck('document_id'));
                    //Excluimos de igual forma las cedulas de bloqueos fijas q por algun motivo no quieran tener presente pero sin eliminarlas con un (false)
                })
                ->when($settings->confirmation_block_movil == true && $request->checkBoxValue == true, function ($query) use ($exclusionMovil) {
                    //Exclusion Movil
                    $query->whereNotIn('peop_dni_g', $exclusionMovil->where('status', true)->pluck('document_id'));
                    //Excluimos de igual forma las cedulas de bloqueos moviles q por algun motivo no quieran tener presente pero sin eliminarlas con un (false)
                })
                ->get();

            if ($management->isEmpty()) {
                $date = Carbon::parse($request->date)->format('Y-m-d');
                $rawQuery = "
                    SELECT * FROM (
                        SELECT NULL as id, NULL as acma_start_datecomplete, ? as acma_start_date, NULL as acma_start_time,
                        NULL as acma_end_date_complete, ? as acma_end_date, NULL as acma_end_time, NULL as peop_dni_c, NULL as peop_name_c, NULL as peop_dni_g, NULL as peop_name_g, NULL as acco_code_account, NULL as typi_name,
                        NULL as typi_effective, NULL as stra_name, NULL as tyal_name, NULL as acma_minutes, NULL as acma_date_call_back, NULL as acma_time_call_back, NULL as date_part, NULL as renp_name, NULL as data_value, NULL as payment_date,
                        NULL as payment_value, NULL as pafe_fees_type, NULL as acma_observation, NULL as assi_name, NULL as assi_id, NULL as camp_name, NULL as camp_id, NULL as acco_payment_reference, NULL as acco_code_client,
                        NULL as acco_quantity_products, NULL as acco_portfolio_type, NULL as acco_leaflet, NULL as acco_segment, NULL as acco_cycle, NULL as acco_number_obligations, NULL as asac_day_past_due, NULL as asac_age,
                        NULL as foal_name, NULL as prty_name, NULL as asac_balance, NULL as payment_discount, NULL as asac_discount_start_date, NULL as asac_discount_end_date, NULL as min, NULL as alli_name, NULL as alli_id, NULL as acma_iseffective,
                        NULL as acma_contact_name
                    ) AS dummy_row
                    LIMIT 1
                ";

                $management = collect(DB::select(DB::raw($rawQuery), [$date, $date]));
            }

            $users = $this->users($settings->users);
            $assignValues = $this->assignValues($settings, $users, $management);

            $paymentNotAgreement = $this->paymentNotAgreement($assignValues, $settings, $management, $request);

            $datos = collect([]);
            foreach ($paymentNotAgreement->ejecutivos as $ejecutivo) {
                $datos = $datos->concat($ejecutivo->gets_no_efectivas_con_pago->concat($ejecutivo->gets_no_efectivas_sin_pago->concat($ejecutivo->gets_efectivas_con_pago->concat($ejecutivo->gets_efectivas_sin_pago->concat($ejecutivo->gets_promesas_con_pago->concat($ejecutivo->gets_promesas_sin_pago))))));
            }

            $paymentAgreement = $this->paymentAgreement($paymentNotAgreement, $settings, $datos, $request);

            $datos = collect([]);
            foreach ($paymentAgreement->ejecutivos as $ejecutivo) {
                $datos = $datos->concat($ejecutivo->gets_no_efectivas_con_pago->concat($ejecutivo->gets_no_efectivas_sin_pago->concat($ejecutivo->gets_efectivas_con_pago->concat($ejecutivo->gets_efectivas_sin_pago->concat($ejecutivo->gets_promesas_con_pago->concat($ejecutivo->gets_promesas_sin_pago))))));
            }

            $paymentExtraAgreement = $this->paymentExtraAgreement($assignValues, $settings, $datos, $request);

            $datos = collect([]);
            foreach ($paymentExtraAgreement->ejecutivos as $ejecutivo) {
                $rows = $ejecutivo->gets_no_efectivas_con_pago->concat($ejecutivo->gets_no_efectivas_sin_pago->concat($ejecutivo->gets_efectivas_con_pago->concat($ejecutivo->gets_efectivas_sin_pago->concat($ejecutivo->gets_promesas_con_pago->concat($ejecutivo->gets_promesas_sin_pago)))));
                foreach ($rows as $row) {
                    $datos->push((object) [
                        'acma_start_date' => $row->acma_start_date,
                        'acma_end_date' => $row->acma_end_date,
                        'acma_observation' => $row->acma_observation,
                        'acma_start_time' => $row->acma_start_time,
                        'acma_end_time' => $row->acma_end_time,
                        'assi_name' => $row->assi_name,
                        'acco_code_account' => $row->acco_code_account,
                        'data_value' => $row->data_value,
                        'renp_name' => $row->renp_name,
                        'payment_date' => $row->payment_date,
                        'payment_value' => $row->payment_value,
                        'payment_discount' => $row->payment_discount,
                        'acma_contact_name' => $row->acma_contact_name,
                        'acma_iseffective' => $row->acma_iseffective,
                        'camp_name' => $row->camp_name,
                        'stra_name' => $row->stra_name,
                        'alli_name' => $row->alli_name,
                        'typi_name' => $row->typi_name,
                        'promesas' =>  $row->typi_name == 'promesa de pago' ? 1 : '',
                        'typi_effective' => $row->typi_effective,
                        'asac_balance' => $row->asac_balance,
                        'foal_name' => $row->foal_name,
                        'peop_name_c' => $row->peop_name_c,
                        'peop_dni_c' => $row->peop_dni_c,
                        'ejec_name' => $row->peop_name_g,
                        'ejec_cc' => $row->peop_dni_g
                    ]);
                }
            }

            $downloadToCSV = $this->downloadToCSV($datos->unique()->toArray());

            $headers = [
                'Content-type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename=gestion.csv',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];
            return response()->download($downloadToCSV, 'gestion.csv', $headers);
        } catch (Exception $e) {
            return $this->errorResponse(
                [
                    'message' => $this->error,
                    'error' => $e->getMessage()
                ],
                500
            );
        }
    }

    private function users($settings)
    {
        // Mapeo de nombres completos para los usuarios en la configuración
        $users = $settings->map(function ($item) {
            return (object) [
                'ejec_name' => trim($item['name'] . ' ' . $item['lastname']),
                'ejec_cc' => $item['document_number']
            ];
        });

        return $users;
    }

    private function assignValues($settings, $ejecutivos, $effectiveness)
    {
        $settingUsers = (object) [
            'total_gestiones' => 0,
            'cant_no_efectivas_con_pago' => 0,
            'cant_no_efectivas_sin_pago' => 0,
            'cant_efectivas_con_pago' => 0,
            'cant_efectivas_sin_pago' => 0,
            'cant_promesas_con_pago' => 0,
            'cant_promesas_sin_pago' => 0,
            'ejecutivos' => collect([]),
        ];

        foreach ($ejecutivos as $ejecutivo) {

            // Meta a alcanzar ramdom entre el minimo de gestiones y maximo sumando no efectivas, efectivas sin acuerdo y efectivas con acuerdo
            $totalInteractions = rand($settings->user_interactions_min_count, $settings->user_interactions_max_count);

            // Cantidad de efectivas de acuerdo con la meta ramdom
            $effectiveInteractionsWithPay = ceil(($settings->effectiveness_percentage * $totalInteractions) / 100);
            $effectiveInteractionsWithPay = rand($effectiveInteractionsWithPay - 6, $effectiveInteractionsWithPay);

            $effectiveInteractionsWithTruePay = ceil($effectiveInteractionsWithPay * ($settings->payment_agreement_true_percentage / 100));

            $effectiveInteractionsWithoutPay = $effectiveInteractionsWithPay - $effectiveInteractionsWithTruePay;

            // Cantidad de efectivas de acuerdo con la meta ramdom
            $paymentInteractionsWithPay = ceil(($settings->payment_agreement_percentage * $effectiveInteractionsWithPay) / 100);
            $paymentInteractionsWithPay = rand($paymentInteractionsWithPay - 3,  $paymentInteractionsWithPay + 1);

            // Cantidad de promesas con pago efectivo
            $paymentInteractionsWithTruePay = ceil($paymentInteractionsWithPay * ($settings->payment_agreement_true_percentage / 100));

            // Cantidad de promesas sin pago
            $paymentInteractionsWithoutPay = $paymentInteractionsWithPay - $paymentInteractionsWithTruePay;

            // Ahora $paymentInteractionsWithTruePay tiene la cantidad de promesas con pago
            // y $paymentInteractionsWithoutPay tiene la cantidad de promesas sin pago

            $noEffectiveInteractions = $totalInteractions - $paymentInteractionsWithPay - $effectiveInteractionsWithPay;
            $noEffectiveInteractionsWithTruePay = ceil(($settings->payment_agreement_true_percentage * $noEffectiveInteractions) / 100);

            $noEffectiveInteractionsWithoutPay = $noEffectiveInteractions - $noEffectiveInteractionsWithTruePay;

            $array = (object) [
                'ejec_name' => $ejecutivo->ejec_name,
                'ejec_cc' => $ejecutivo->ejec_cc,

                'total_gestiones' => $totalInteractions,

                'cant_no_efectivas_con_pago' => $noEffectiveInteractionsWithTruePay,
                'gets_no_efectivas_con_pago' => collect([]),
                'cant_no_efectivas_sin_pago' => $noEffectiveInteractionsWithoutPay,
                'gets_no_efectivas_sin_pago' => collect([]),
                'cant_efectivas_con_pago' => $effectiveInteractionsWithTruePay,
                'gets_efectivas_con_pago' => collect([]),
                'cant_efectivas_sin_pago' => $effectiveInteractionsWithoutPay,
                'gets_efectivas_sin_pago' => collect([]),
                'cant_promesas_con_pago' => $paymentInteractionsWithTruePay,
                'gets_promesas_con_pago' => collect([]),
                'cant_promesas_sin_pago' => $paymentInteractionsWithoutPay,
                'gets_promesas_sin_pago' => collect([]),
            ];

            $settingUsers->ejecutivos->push($array);
        };

        $settingUsers->total_gestiones = collect($settingUsers->ejecutivos)->pluck('total_gestiones')->sum();

        $settingUsers->cant_no_efectivas_con_pago = collect($settingUsers->ejecutivos)->pluck('cant_no_efectivas_con_pago')->sum();

        $settingUsers->cant_no_efectivas_sin_pago = collect($settingUsers->ejecutivos)->pluck('cant_no_efectivas_sin_pago')->sum();

        $settingUsers->cant_efectivas_con_pago = collect($settingUsers->ejecutivos)->pluck('cant_efectivas_con_pago')->sum();

        $settingUsers->cant_efectivas_sin_pago = collect($settingUsers->ejecutivos)->pluck('cant_efectivas_sin_pago')->sum();

        $settingUsers->cant_promesas_con_pago = collect($settingUsers->ejecutivos)->pluck('cant_promesas_con_pago')->sum();

        $settingUsers->cant_promesas_sin_pago = collect($settingUsers->ejecutivos)->pluck('cant_promesas_sin_pago')->sum();

        $groupedEffectiveness = $effectiveness->groupBy('acco_code_account')->values();

        // Inicializamos un índice para los usuarios
        $userIndex = 0;

        // Iteramos sobre cada grupo de $effectiveness
        foreach ($groupedEffectiveness as $accoCode => $group) {

            $managements = $group->where('typi_effective', false)->values();
            foreach ($managements as $management) {
                $management->peop_name_g = $settingUsers->ejecutivos[$userIndex]->ejec_name;
                $management->peop_dni_g = $settingUsers->ejecutivos[$userIndex]->ejec_cc;

                $settingUsers->ejecutivos[$userIndex]->gets_no_efectivas_con_pago = $settingUsers->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->push($management);
            }

            $managements = $group->where('typi_effective', true)->whereNotIn('typi_name', ['promesa de pago', 'cliente al dia'])->values();
            foreach ($managements as $management) {
                $management->peop_name_g = $settingUsers->ejecutivos[$userIndex]->ejec_name;
                $management->peop_dni_g = $settingUsers->ejecutivos[$userIndex]->ejec_cc;

                $settingUsers->ejecutivos[$userIndex]->gets_efectivas_con_pago = $settingUsers->ejecutivos[$userIndex]->gets_efectivas_con_pago->push($management);
            }

            $managements = $group->where('typi_effective', true)->whereIn('typi_name', ['promesa de pago', 'cliente al dia'])->values();
            foreach ($managements as $management) {
                $management->peop_name_g = $settingUsers->ejecutivos[$userIndex]->ejec_name;
                $management->peop_dni_g = $settingUsers->ejecutivos[$userIndex]->ejec_cc;
                if ($settingUsers->ejecutivos[$userIndex]->gets_promesas_sin_pago->count() < $settingUsers->ejecutivos[$userIndex]->cant_promesas_sin_pago) {
                    $settingUsers->ejecutivos[$userIndex]->gets_promesas_sin_pago = $settingUsers->ejecutivos[$userIndex]->gets_promesas_sin_pago->push($management);
                } else {
                    $settingUsers->ejecutivos[$userIndex]->gets_promesas_con_pago = $settingUsers->ejecutivos[$userIndex]->gets_promesas_con_pago->push($management);
                }
            }
            // Asignamos el grupo al usuario actual

            // Incrementamos el índice del usuario y reiniciamos si es necesario
            $userIndex = ($userIndex + 1) % count($ejecutivos);
        }

        return $settingUsers;
    }

    private function paymentNotAgreement($assignValues, $settings, $managements, $request)
    {
        $payments = Payments::when($settings->assignments->isNotEmpty(), function ($query) use ($settings) {
            $query->whereHasMorph('model', [Assignment::class], function ($query) use ($settings) {
                $query->whereIn('model_id', $settings->assignments->pluck('model_id'));
            });
        })
            ->when($settings->assignments->isEmpty(), function ($query) use ($settings) {
                $query->whereHasMorph('model', [Campaign::class], function ($query) use ($settings) {
                    $query->whereIn('model_id', $settings->campaign->pluck('model_id'));
                });
            })
            ->whereNotIn('pay_account', $managements->whereNotNull('acco_code_account')->pluck('acco_code_account')->unique()->values())
            ->where('pay_date', $request->date)
            ->where('real_payment', false)
            ->whereIn('focus_id', $settings->focus->pluck('model_id'))
            ->get();

        $assignments_accounts = AssignmentAccounts::with('alli', 'assi', 'camp', 'foal')
            ->where('camp_id', $settings->campaign->model_id)
            ->when($settings->assignments->isNotEmpty(), function ($query) use ($settings) {
                $query->whereIn('assi_id', $settings->assignments->pluck('model_id'));
            })
            ->whereIn('foal_id', $settings->focus->pluck('model_id'))
            /* ->whereNotNull('data_value') */
            ->whereNotIn('peop_dni', $managements->whereNotNull('peop_dni_c')->pluck('peop_dni_c')->unique())
            ->whereIn('assi_id', $settings->assignments->pluck('model_id'))
            ->whereIn('acco_code_account', $payments->pluck('pay_account')->unique())
            ->get();

        $patrongHorarioFuction = $settings->time_patterns()->where('id_function', 1)->get();

        $timePatterns = $patrongHorarioFuction->map(function ($pattern) {
            return [
                'objects_8_in_10' => json_decode($pattern->objects_8_in_10, true),
                'objects_16_in_17' => json_decode($pattern->objects_16_in_17, true),
                'objects_12_in_13' => json_decode($pattern->objects_12_in_13, true),
                'objects_15_in_16' => json_decode($pattern->objects_15_in_16, true),
                'objects_11_in_13' => json_decode($pattern->objects_11_in_13, true),
                'objects_08_in_14' => json_decode($pattern->objects_08_in_14, true),
                'objects_15_in_18' => json_decode($pattern->objects_15_in_18, true),
                'objects_13_in_17' => json_decode($pattern->objects_13_in_17, true),
                'objects_08_in_13' => json_decode($pattern->objects_08_in_13, true),
                'objects_16_in_17_50' => json_decode($pattern->objects_16_in_17_50, true),
            ];
        });

        $patronHorario = [];

        foreach ($timePatterns as $hora) {
            $patronHorario = [
                (object) [
                    'time_start' => Carbon::parse('08:00:00'),
                    'time_end' => Carbon::parse('10:30:00'),
                    'no_efectiva' => random_int($hora['objects_8_in_10']['no_efectiva_1'], $hora['objects_8_in_10']['no_efectiva_2']),
                    'efectiva' => $hora['objects_8_in_10']['efectiva'],
                    'promesa' => $hora['objects_8_in_10']['promesa'],
                ],
                (object) [
                    'time_start' => Carbon::parse('16:30:00'),
                    'time_end' => Carbon::parse('17:50:00'),
                    'no_efectiva' => random_int($hora['objects_16_in_17']['no_efectiva_1'], $hora['objects_16_in_17']['no_efectiva_2']),
                    'efectiva' => $hora['objects_16_in_17']['efectiva'],
                    'promesa' => $hora['objects_16_in_17']['promesa'],
                ],
                (object) [
                    'time_start' => Carbon::parse('12:30:00'),
                    'time_end' => Carbon::parse('13:30:00'),
                    'no_efectiva' => random_int($hora['objects_12_in_13']['no_efectiva_1'], $hora['objects_12_in_13']['no_efectiva_2']),
                    'efectiva' => $hora['objects_12_in_13']['efectiva'],
                    'promesa' => $hora['objects_12_in_13']['promesa'],
                ],
                (object) [
                    'time_start' => Carbon::parse('15:50:00'),
                    'time_end' => Carbon::parse('16:50:00'),
                    'no_efectiva' => random_int($hora['objects_15_in_16']['no_efectiva_1'], $hora['objects_15_in_16']['no_efectiva_2']),
                    'efectiva' => $hora['objects_15_in_16']['efectiva'],
                    'promesa' => $hora['objects_15_in_16']['promesa'],
                ],
                (object) [
                    'time_start' => Carbon::parse('11:20:00'),
                    'time_end' => Carbon::parse('13:50:00'),
                    'no_efectiva' => random_int($hora['objects_11_in_13']['no_efectiva_1'], $hora['objects_11_in_13']['no_efectiva_2']),
                    'efectiva' => $hora['objects_11_in_13']['efectiva'],
                    'promesa' => $hora['objects_11_in_13']['promesa'],
                ],
                (object) [
                    'time_start' => Carbon::parse('08:00:00'),
                    'time_end' => Carbon::parse('14:50:00'),
                    'no_efectiva' => random_int($hora['objects_08_in_14']['no_efectiva_1'], $hora['objects_08_in_14']['no_efectiva_2']),
                    'efectiva' => $hora['objects_08_in_14']['efectiva'],
                    'promesa' => $hora['objects_08_in_14']['promesa'],
                ],
                (object) [
                    'time_start' => Carbon::parse('15:30:00'),
                    'time_end' => Carbon::parse('18:20:00'),
                    'no_efectiva' => random_int($hora['objects_15_in_18']['no_efectiva_1'], $hora['objects_15_in_18']['no_efectiva_2']),
                    'efectiva' => $hora['objects_15_in_18']['efectiva'],
                    'promesa' => $hora['objects_15_in_18']['promesa'],
                ],
                (object) [
                    'time_start' => Carbon::parse('13:25:00'),
                    'time_end' => Carbon::parse('17:35:00'),
                    'no_efectiva' => random_int($hora['objects_13_in_17']['no_efectiva_1'], $hora['objects_13_in_17']['no_efectiva_2']),
                    'efectiva' => $hora['objects_13_in_17']['efectiva'],
                    'promesa' => $hora['objects_13_in_17']['promesa'],
                ],
                (object) [
                    'time_start' => Carbon::parse('08:50:00'),
                    'time_end' => Carbon::parse('13:20:00'),
                    'no_efectiva' => random_int($hora['objects_08_in_13']['no_efectiva_1'], $hora['objects_08_in_13']['no_efectiva_2']),
                    'efectiva' => $hora['objects_08_in_13']['efectiva'],
                    'promesa' => $hora['objects_08_in_13']['promesa'],
                ],
                (object) [
                    'time_start' => Carbon::parse('16:10:00'),
                    'time_end' => Carbon::parse('17:10:00'),
                    'no_efectiva' => random_int($hora['objects_16_in_17_50']['no_efectiva_1'], $hora['objects_16_in_17_50']['no_efectiva_2']),
                    'efectiva' => $hora['objects_16_in_17_50']['efectiva'],
                    'promesa' => $hora['objects_16_in_17_50']['promesa'],
                ],
            ];
        }

        $id = $managements->count() + 1;

        $userIndex = 0;
        // Iteramos sobre cada grupo de $effectiveness
        $cuentas_gestionadas = $managements->pluck('acco_code_account')->unique()->values()->toArray();
        $clientes_gestionados = $managements->pluck('peop_dni_c')->unique()->values()->toArray();
        $telefonos_gestionados = $managements->pluck('data_value')->unique()->values()->toArray();

        foreach ($assignments_accounts as $indice => $assignment_account) {
            if (in_array($assignment_account->acco_code_account, $cuentas_gestionadas) || in_array($assignment_account->peop_dni, $clientes_gestionados) || in_array($assignment_account->data_value, $telefonos_gestionados)) {
                continue;
            } else {
                $cuentas_gestionadas[] = $assignment_account->acco_code_account;
                $clientes_gestionados[] = $assignment_account->peop_dni;
                if (!is_null($assignment_account->data_value)) {
                    $telefonos_gestionados[] = $assignment_account->data_value;
                }
            }
            $horario = $patronHorario[random_int(0, 9)];

            $payment = $payments->where('pay_account', $assignment_account->acco_code_account)->random(1)->first();

            if ($payment->pay_recaudation_date == $payment->pay_date) {
                $cuenta = $assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->where('acco_code_account', $assignment_account->acco_code_account)->first();
                if ($cuenta && $cuenta->typi_name == 'mensaje con tercero/razon') {
                    continue;
                }

                if (!$cuenta) {
                    $cuenta = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->where('acco_code_account', $assignment_account->acco_code_account)->first();
                }

                if ($assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago->count() < $assignValues->ejecutivos[$userIndex]->cant_promesas_sin_pago) {
                    $typi_promesa = (object) [
                        'typi_effective' => true,
                        'typi_name' => 'cliente al dia',
                        'acma_observation' => 'se verifica en el sistema y el titular realizo el pago el dia DD/MM/AAAA por valor de $$$ quedando el cliente al dia con la casa de cobro',
                        'time_min' => 45,
                        'time_max' => 70
                    ];

                    $management = clone $managements->first();

                    $isEfectiva = true;

                    if ($cuenta) {
                        $random_start = Carbon::parse($cuenta->acma_end_time);
                        $random_end = Carbon::parse('18:50:00')->diffInSeconds($random_start);

                        $random_int = random_int(60, 90);

                        $time_start = Carbon::parse($cuenta->acma_end_time)->addSeconds(random_int($random_int, $random_end));
                        $time_end = Carbon::parse($time_start)->addSeconds(random_int($typi_promesa->time_min, $typi_promesa->time_max));

                        $allExistingRanges = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago)))));

                        if ($this->checkTimeRanges($time_start, $time_end, $allExistingRanges)) {
                            // Buscar nuevo horario disponible
                            $newRange = $this->findNextAvailableTimeRangeEffective($time_start, $time_end, $allExistingRanges, 90, $random_start, $random_end);

                            // Actualizamos time_start y time_end si se encontró un nuevo rango
                            if ($newRange) {
                                $time_start = $newRange['start'];
                                $time_end = $newRange['end'];
                            } else {
                                $isEfectiva = false;
                            }
                        }

                        $time_start = $time_start->format('H:i:s');
                        $time_end = $time_end->format('H:i:s');
                    } else {
                        $time_start = Carbon::parse($horario->time_start)->addSeconds(random_int(0, $horario->time_start->diffInSeconds($horario->time_end)));
                        $time_end = Carbon::parse($time_start)->addSeconds(random_int($typi_promesa->time_min, $typi_promesa->time_max));

                        $allExistingRanges = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago)))));

                        if ($this->checkTimeRanges($time_start, $time_end, $allExistingRanges)) {
                            $time_start = $time_start->format('H:i:s');
                            $time_end = $time_end->format('H:i:s');
                        } else {
                            $rangeOverlap = $this->findNextAvailableTimeRange($time_start, $time_end, $allExistingRanges);
                            $time_start = $rangeOverlap['start']->format('H:i:s');
                            $time_end = $rangeOverlap['end']->format('H:i:s');
                        }
                    }

                    if ($isEfectiva) {

                        $payment_date = Carbon::parse($payment->pay_date)->format('Y-m-d');

                        $typi_promesa->acma_observation = str_replace('XXX-XXX-XXXX', is_null($assignment_account->data_value) ? 'xxxxxxxxxx' : $assignment_account->data_value, $typi_promesa->acma_observation);
                        $typi_promesa->acma_observation = str_replace('X|X|X', $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname, $typi_promesa->acma_observation);
                        $typi_promesa->acma_observation = str_replace('DD/MM/AAAA', $payment_date, $typi_promesa->acma_observation);
                        $typi_promesa->acma_observation = str_replace('$$$', $payment->pay_value, $typi_promesa->acma_observation);

                        $management->id = $id;
                        $management->acma_start_date = $management->acma_start_date;
                        $management->acma_end_date = $management->acma_end_date;
                        $management->acma_start_time = $time_start;
                        $management->acma_end_time = $time_end;
                        $management->acco_code_account = $assignment_account->acco_code_account;
                        $management->data_value = is_null($assignment_account->data_value) ? 'xxxxxxxxxx' : $assignment_account->data_value;
                        $management->renp_name = '';
                        $management->payment_date = $payment_date;
                        $management->payment_value = $payment->pay_value;
                        $management->payment_discount = $payment->pay_discount_rate;
                        $management->acma_contact_name = $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname;
                        $management->acma_iseffective = $typi_promesa->typi_effective;
                        $management->acma_observation = $typi_promesa->acma_observation;
                        $management->assi_name = $assignment_account->assi->assi_name;
                        $management->camp_name = $assignment_account->camp->camp_name;
                        $management->alli_name = $assignment_account->alli->alli_name;
                        $management->typi_name = $typi_promesa->typi_name;
                        $management->typi_effective = $typi_promesa->typi_effective;
                        $management->asac_balance = $payment->pay_value;
                        $management->peop_name_g = $assignValues->ejecutivos[$userIndex]->ejec_name;
                        $management->peop_dni_g = $assignValues->ejecutivos[$userIndex]->ejec_cc;
                        $management->peop_name_c = $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname;
                        $management->peop_dni_c = $assignment_account->peop_dni;
                        $management->foal_name = $payment->focus->foal_name;

                        $id++;

                        $assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago = $assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->push($management);
                    }
                }
                continue;
            }

            if ($horario->no_efectiva > 0) {

                $timeStart = Carbon::parse($horario->time_start);
                $timeEnd = Carbon::parse($timeStart)->addSeconds(random_int(0, $timeStart->diffInSeconds($horario->time_end)))->addSeconds(random_int(0, 59))->format('H:i:s');
                if ($assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->count() < $assignValues->ejecutivos[$userIndex]->cant_no_efectivas_sin_pago) {
                    for ($i = 0; $i < $horario->no_efectiva; $i++) {
                        $typi_no_efectivas = [
                            (object) [
                                'typi_effective' => false,
                                'typi_name' => 'mensaje en buzon',
                                'acma_observation' => 'se llama a la linea movil XXX-XXX-XXXX repica en repetidas ocasiones y pasa a una grabacion de buzon de mensajes.',
                                'time_min' => 12,
                                'time_max' => 20
                            ],
                            (object) [
                                'typi_effective' => false,
                                'typi_name' => 'no contestan',
                                'acma_observation' => 'se llama a la linea XXX-XXX-XXXX repica en repetidas ocasiones pero no contestan.',
                                'time_min' => 12,
                                'time_max' => 30
                            ],
                            (object) [
                                'typi_effective' => false,
                                'typi_name' => 'telefono apagado',
                                'acma_observation' => 'se llama a la linea movil XXX-XXX-XXXX grabacion informa que el telefono esta apagado.',
                                'time_min' => 12,
                                'time_max' => 27
                            ]
                        ];

                        $typi_no_efectiva = $typi_no_efectivas[random_int(0, 2)];

                        $time_start = Carbon::parse($timeStart)->addSeconds(random_int(0, $timeStart->diffInSeconds($timeEnd)))->addSeconds(random_int(0, 59));
                        $time_end = Carbon::parse($time_start)->addSeconds(random_int($typi_no_efectiva->time_min, $typi_no_efectiva->time_max));

                        $allExistingRanges = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->concat($assignValues->ejecutivos
                        [$userIndex]->gets_no_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->concat($assignValues->ejecutivos
                        [$userIndex]->gets_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->concat($assignValues->ejecutivos
                        [$userIndex]->gets_promesas_sin_pago)))));

                        if ($this->checkTimeRanges($time_start, $time_end, $allExistingRanges)) {
                            $time_start = $time_start->format('H:i:s');
                            $time_end = $time_end->format('H:i:s');
                        } else {
                            $rangeOverlap = $this->findNextAvailableTimeRange($time_start, $time_end, $allExistingRanges);
                            $time_start = $rangeOverlap['start']->format('H:i:s');
                            $time_end = $rangeOverlap['end']->format('H:i:s');
                        }


                        $typi_no_efectiva->acma_observation = str_replace('XXX-XXX-XXXX', is_null($assignment_account->data_value) ? 'xxxxxxxxxx' : $assignment_account->data_value, $typi_no_efectiva->acma_observation);

                        $management = clone $managements->first();

                        $management->id = $id;
                        $management->acma_start_date = $management->acma_start_date;
                        $management->acma_end_date = $management->acma_end_date;
                        $management->acma_start_time = $time_start;
                        $management->acma_end_time = $time_end;
                        $management->acco_code_account = $assignment_account->acco_code_account;
                        $management->data_value = is_null($assignment_account->data_value) ? 'xxxxxxxxxx' : $assignment_account->data_value;
                        $management->renp_name = 'n/a';
                        $management->payment_date = 'n/a';
                        $management->payment_value = 'n/a';
                        $management->payment_discount = 'n/a';
                        $management->acma_contact_name = $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname;
                        $management->acma_iseffective = $typi_no_efectiva->typi_effective;
                        $management->acma_observation = $typi_no_efectiva->acma_observation;
                        $management->assi_name = $assignment_account->assi->assi_name;
                        $management->camp_name = $assignment_account->camp->camp_name;
                        $management->alli_name = $assignment_account->alli->alli_name;
                        $management->typi_name = $typi_no_efectiva->typi_name;
                        $management->typi_effective = $typi_no_efectiva->typi_effective;
                        $management->asac_balance = $assignment_account->asac_balance;
                        $management->peop_name_g = $assignValues->ejecutivos[$userIndex]->ejec_name;
                        $management->peop_dni_g = $assignValues->ejecutivos[$userIndex]->ejec_cc;
                        $management->peop_name_c = $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname;
                        $management->peop_dni_c = $assignment_account->peop_dni;
                        $management->foal_name = is_null($assignment_account->foal) ? $management->foal_name : $assignment_account->foal->foal_name;

                        $id++;

                        $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->push($management);

                        $timeStart = Carbon::parse($horario->time_start);
                        $timeEnd = Carbon::parse($timeStart)->addSeconds(random_int(0, $timeStart->diffInSeconds($horario->time_end)))->addSeconds(random_int(0, 59))->format('H:i:s');
                    }
                }
            }

            if ($horario->efectiva > 0) {

                $cuenta = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->where('acco_code_account', $assignment_account->acco_code_account)->first();

                if ($assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->count() < $assignValues->ejecutivos[$userIndex]->cant_efectivas_sin_pago) {
                    $typi_efectivas = [
                        (object) [
                            'typi_effective' => true,
                            'typi_name' => 'cuelga la llamada',
                            'acma_observation' => 'se llama al XXX-XXX-XXXX la persona que contesta no se identifica y termina la llamada.',
                            'time_min' => 20,
                            'time_max' => 35
                        ],
                        (object) [
                            'typi_effective' => true,
                            'typi_name' => 'agendado',
                            'acma_observation' => 'se llama al XXX-XXX-XXXX se habla con el sr X|X|X titular / encargado de pago informa que en el momento no puede antender la llamada solicita que se comuniquen nuevamente.',
                            'time_min' => 60,
                            'time_max' => 90
                        ],
                        (object) [
                            'typi_effective' => true,
                            'typi_name' => 'cuelga la llamada',
                            'acma_observation' => 'se llama al XXX-XXX-XXXX la persona que contesta no se identifica y termina la llamada.',
                            'time_min' => 20,
                            'time_max' => 35
                        ],
                        (object) [
                            'typi_effective' => true,
                            'typi_name' => 'agendado',
                            'acma_observation' => 'se llama al XXX-XXX-XXXX se habla con el sr X|X|X titular / encargado de pago informa que en el momento no puede antender la llamada solicita que se comuniquen nuevamente.',
                            'time_min' => 60,
                            'time_max' => 90
                        ],
                        (object) [
                            'typi_effective' => true,
                            'typi_name' => 'mensaje con tercero/razon',
                            'acma_observation' => 'se llama al XXX-XXX-XXXX contesta tercero no indica nombre se brinda datos de casa de cobro.',
                            'time_min' => 40,
                            'time_max' => 80
                        ],
                    ];

                    $management = clone $managements->first();

                    $isEfectiva = true;
                    $typi_efectiva = $typi_efectivas[random_int(0, 4)];

                    if ($cuenta) {
                        $random_start = Carbon::parse($horario->promesa == 0 ? '18:40:00' : '17:10:00');
                        $random_end = Carbon::parse($cuenta->acma_end_time)->diffInSeconds($random_start);

                        $random_int = random_int(60, 90);

                        $time_start = Carbon::parse($cuenta->acma_end_time)->addSeconds(random_int($random_int, $random_end));
                        $time_end = Carbon::parse($time_start)->addSeconds(random_int($typi_efectiva->time_min, $typi_efectiva->time_max));

                        $allExistingRanges = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago)))));

                        if ($this->checkTimeRanges($time_start, $time_end, $allExistingRanges)) {
                            // Buscar nuevo horario disponible
                            $newRange = $this->findNextAvailableTimeRangeEffective($time_start, $time_end, $allExistingRanges, 90, $random_start, $random_end);

                            // Actualizamos time_start y time_end si se encontró un nuevo rango
                            if ($newRange) {
                                $time_start = $newRange['start'];
                                $time_end = $newRange['end'];
                            } else {
                                $isEfectiva = false;
                            }
                        }

                        $time_start = $time_start->format('H:i:s');
                        $time_end = $time_end->format('H:i:s');
                    } else {
                        $time_start = Carbon::parse($horario->time_start)->addSeconds(random_int(0, $horario->time_start->diffInSeconds($horario->time_end)));
                        $time_end = Carbon::parse($time_start)->addSeconds(random_int($typi_efectiva->time_min, $typi_efectiva->time_max));

                        $allExistingRanges = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago)))));

                        if ($this->checkTimeRanges($time_start, $time_end, $allExistingRanges)) {
                            $time_start = $time_start->format('H:i:s');
                            $time_end = $time_end->format('H:i:s');
                        } else {
                            $rangeOverlap = $this->findNextAvailableTimeRange($time_start, $time_end, $allExistingRanges);
                            $time_start = $rangeOverlap['start']->format('H:i:s');
                            $time_end = $rangeOverlap['end']->format('H:i:s');
                        }
                    }

                    if ($isEfectiva) {

                        $typi_efectiva->acma_observation = str_replace('XXX-XXX-XXXX', is_null($assignment_account->data_value) ? 'xxxxxxxxxx' : $assignment_account->data_value, $typi_efectiva->acma_observation);
                        $typi_efectiva->acma_observation = str_replace('X|X|X', $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname, $typi_efectiva->acma_observation);

                        $management->id = $id;
                        $management->acma_start_date = $management->acma_start_date;
                        $management->acma_end_date = $management->acma_end_date;
                        $management->acma_start_time = $time_start;
                        $management->acma_end_time = $time_end;
                        $management->acco_code_account = $assignment_account->acco_code_account;
                        $management->data_value = is_null($assignment_account->data_value) ? 'xxxxxxxxxx' : $assignment_account->data_value;
                        $management->renp_name = 'n/a';
                        $management->payment_date = 'n/a';
                        $management->payment_value = 'n/a';
                        $management->payment_discount = 'n/a';
                        $management->acma_contact_name = $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname;
                        $management->acma_iseffective = $typi_efectiva->typi_effective;
                        $management->acma_observation = $typi_efectiva->acma_observation;
                        $management->assi_name = $assignment_account->assi->assi_name;
                        $management->camp_name = $assignment_account->camp->camp_name;
                        $management->alli_name = $assignment_account->alli->alli_name;
                        $management->typi_name = $typi_efectiva->typi_name;
                        $management->typi_effective = $typi_efectiva->typi_effective;
                        $management->asac_balance = $assignment_account->asac_balance;
                        $management->peop_name_g = $assignValues->ejecutivos[$userIndex]->ejec_name;
                        $management->peop_dni_g = $assignValues->ejecutivos[$userIndex]->ejec_cc;
                        $management->peop_name_c = $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname;
                        $management->peop_dni_c = $assignment_account->peop_dni;
                        $management->foal_name = is_null($assignment_account->foal) ? $management->foal_name : $assignment_account->foal->foal_name;

                        $id++;

                        $assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago = $assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->push($management);
                    }
                }
            }

            if ($horario->promesa > 0) {

                $cuenta = $assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->where('acco_code_account', $assignment_account->acco_code_account)->first();
                if ($cuenta && $cuenta->typi_name == 'mensaje con tercero/razon') {
                    continue;
                }

                if (!$cuenta) {
                    $cuenta = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->where('acco_code_account', $assignment_account->acco_code_account)->first();
                }

                if ($assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago->count() < $assignValues->ejecutivos[$userIndex]->cant_promesas_sin_pago) {
                    $typi_promesa = (object) [
                        'typi_effective' => true,
                        'typi_name' => 'promesa de pago',
                        'acma_observation' => 'se llama al XXX-XXX-XXXX se habla con el sr X|X|X titular / encargado de pago informa que no habia cancelado debido a no responde se genera compromiso de pago para el DD/MM/AAAA por valor de $$$ y no indica datos adicionales',
                        'time_min' => 240,
                        'time_max' => 360
                    ];

                    $management = clone $managements->first();

                    $isEfectiva = true;

                    if ($cuenta) {
                        $random_start = Carbon::parse($cuenta->acma_end_time);
                        $random_end = Carbon::parse('18:50:00')->diffInSeconds($random_start);

                        $random_int = random_int(60, 90);

                        $time_start = Carbon::parse($cuenta->acma_end_time)->addSeconds(random_int($random_int, $random_end));
                        $time_end = Carbon::parse($time_start)->addSeconds(random_int($typi_promesa->time_min, $typi_promesa->time_max));

                        $allExistingRanges = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago)))));

                        if ($this->checkTimeRanges($time_start, $time_end, $allExistingRanges)) {
                            // Buscar nuevo horario disponible
                            $newRange = $this->findNextAvailableTimeRangeEffective($time_start, $time_end, $allExistingRanges, 90, $random_start, $random_end);

                            // Actualizamos time_start y time_end si se encontró un nuevo rango
                            if ($newRange) {
                                $time_start = $newRange['start'];
                                $time_end = $newRange['end'];
                            } else {
                                $isEfectiva = false;
                            }
                        }

                        $time_start = $time_start->format('H:i:s');
                        $time_end = $time_end->format('H:i:s');
                    } else {
                        $time_start = Carbon::parse($horario->time_start)->addSeconds(random_int(0, $horario->time_start->diffInSeconds($horario->time_end)));
                        $time_end = Carbon::parse($time_start)->addSeconds(random_int($typi_promesa->time_min, $typi_promesa->time_max));

                        $allExistingRanges = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago)))));

                        if ($this->checkTimeRanges($time_start, $time_end, $allExistingRanges)) {
                            $time_start = $time_start->format('H:i:s');
                            $time_end = $time_end->format('H:i:s');
                        } else {
                            $rangeOverlap = $this->findNextAvailableTimeRange($time_start, $time_end, $allExistingRanges);
                            $time_start = $rangeOverlap['start']->format('H:i:s');
                            $time_end = $rangeOverlap['end']->format('H:i:s');
                        }
                    }

                    if ($isEfectiva) {

                        $payment = $payments->where('pay_account', $assignment_account->acco_code_account)->random(1)->first();
                        $payment_date = Carbon::parse($payment->pay_date)->addDays(random_int(0, random_int(2, 3)))->format('Y-m-d');

                        $typi_promesa->acma_observation = str_replace('XXX-XXX-XXXX', is_null($assignment_account->data_value) ? 'xxxxxxxxxx' : $assignment_account->data_value, $typi_promesa->acma_observation);
                        $typi_promesa->acma_observation = str_replace('X|X|X', $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname, $typi_promesa->acma_observation);
                        $typi_promesa->acma_observation = str_replace('DD/MM/AAAA', $payment_date, $typi_promesa->acma_observation);
                        $typi_promesa->acma_observation = str_replace('$$$', $payment->pay_value, $typi_promesa->acma_observation);

                        $management->id = $id;
                        $management->acma_start_date = $management->acma_start_date;
                        $management->acma_end_date = $management->acma_end_date;
                        $management->acma_start_time = $time_start;
                        $management->acma_end_time = $time_end;
                        $management->acco_code_account = $assignment_account->acco_code_account;
                        $management->data_value = is_null($assignment_account->data_value) ? 'xxxxxxxxxx' : $assignment_account->data_value;
                        $management->renp_name = '';
                        $management->payment_date = $payment_date;
                        $management->payment_value = $payment->pay_value;
                        $management->payment_discount = $payment->pay_discount_rate;
                        $management->acma_contact_name = $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname;
                        $management->acma_iseffective = $typi_promesa->typi_effective;
                        $management->acma_observation = $typi_promesa->acma_observation;
                        $management->assi_name = $assignment_account->assi->assi_name;
                        $management->camp_name = $assignment_account->camp->camp_name;
                        $management->alli_name = $assignment_account->alli->alli_name;
                        $management->typi_name = $typi_promesa->typi_name;
                        $management->typi_effective = $typi_promesa->typi_effective;
                        $management->asac_balance = $payment->pay_value;
                        $management->peop_name_g = $assignValues->ejecutivos[$userIndex]->ejec_name;
                        $management->peop_dni_g = $assignValues->ejecutivos[$userIndex]->ejec_cc;
                        $management->peop_name_c = $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname;
                        $management->peop_dni_c = $assignment_account->peop_dni;
                        $management->foal_name = $payment->focus->foal_name;

                        $id++;

                        $assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago = $assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago->push($management);
                    }
                }
            }

            $userIndex = ($userIndex + 1) % count($assignValues->ejecutivos);
        }

        return $assignValues;
    }

    private function paymentAgreement($assignValues, $settings, $managements, $request)
    {
        $payments = Payments::with('focus')
            ->when($settings->assignments->isNotEmpty(), function ($query) use ($settings) {
                $query->whereHasMorph('model', [Assignment::class], function ($query) use ($settings) {
                    $query->whereIn('model_id', $settings->assignments->pluck('model_id'));
                });
            })
            ->when($settings->assignments->isEmpty(), function ($query) use ($settings) {
                $query->whereHasMorph('model', [Campaign::class], function ($query) use ($settings) {
                    $query->whereIn('model_id', $settings->campaign->pluck('model_id'));
                });
            })
            ->whereNotIn('pay_account', $managements->whereNotNull('acco_code_account')->pluck('acco_code_account')->unique()->values())
            ->where('pay_date', $request->date)
            ->where('real_payment', true)
            ->whereIn('focus_id', $settings->focus->pluck('model_id'))
            ->get();

        $assignments_accounts = AssignmentAccounts::with('alli', 'assi', 'camp', 'foal')
            ->where('camp_id', $settings->campaign->model_id)
            ->when($settings->assignments->isNotEmpty(), function ($query) use ($settings) {
                $query->whereIn('assi_id', $settings->assignments->pluck('model_id'));
            })
            ->whereIn('foal_id', $settings->focus->pluck('model_id'))
            /* ->whereNotNull('data_value') */
            ->whereNotIn('peop_dni', $managements->whereNotNull('peop_dni_c')->pluck('peop_dni_c')->unique())
            ->whereIn('assi_id', $settings->assignments->pluck('model_id'))
            ->whereIn('acco_code_account', $payments->pluck('pay_account')->unique())
            ->get();

        $patrongHorarioFuction = $settings->time_patterns()->where('id_function', 2)->get();

        $timePatterns = $patrongHorarioFuction->map(function ($pattern) {
            return [
                'objects_8_in_10' => json_decode($pattern->objects_8_in_10, true),
                'objects_16_in_17' => json_decode($pattern->objects_16_in_17, true),
                'objects_12_in_13' => json_decode($pattern->objects_12_in_13, true),
                'objects_15_in_16' => json_decode($pattern->objects_15_in_16, true),
                'objects_11_in_13' => json_decode($pattern->objects_11_in_13, true),
                'objects_08_in_14' => json_decode($pattern->objects_08_in_14, true),
                'objects_15_in_18' => json_decode($pattern->objects_15_in_18, true),
                'objects_13_in_17' => json_decode($pattern->objects_13_in_17, true),
                'objects_08_in_13' => json_decode($pattern->objects_08_in_13, true),
                'objects_16_in_17_50' => json_decode($pattern->objects_16_in_17_50, true),
            ];
        });

        $patronHorario = [];

        foreach ($timePatterns as $hora) {
            $patronHorario = [
                (object) [
                    'time_start' => Carbon::parse('08:00:00'),
                    'time_end' => Carbon::parse('10:30:00'),
                    'no_efectiva' => random_int($hora['objects_8_in_10']['no_efectiva_1'], $hora['objects_8_in_10']['no_efectiva_2']),
                    'efectiva' => $hora['objects_8_in_10']['efectiva'],
                    'promesa' => $hora['objects_8_in_10']['promesa'],
                ],
                (object) [
                    'time_start' => Carbon::parse('16:30:00'),
                    'time_end' => Carbon::parse('17:50:00'),
                    'no_efectiva' => random_int($hora['objects_16_in_17']['no_efectiva_1'], $hora['objects_16_in_17']['no_efectiva_2']),
                    'efectiva' => $hora['objects_16_in_17']['efectiva'],
                    'promesa' => $hora['objects_16_in_17']['promesa'],
                ],
                (object) [
                    'time_start' => Carbon::parse('12:30:00'),
                    'time_end' => Carbon::parse('13:30:00'),
                    'no_efectiva' => random_int($hora['objects_12_in_13']['no_efectiva_1'], $hora['objects_12_in_13']['no_efectiva_2']),
                    'efectiva' => $hora['objects_12_in_13']['efectiva'],
                    'promesa' => $hora['objects_12_in_13']['promesa'],
                ],
                (object) [
                    'time_start' => Carbon::parse('15:50:00'),
                    'time_end' => Carbon::parse('16:50:00'),
                    'no_efectiva' => random_int($hora['objects_15_in_16']['no_efectiva_1'], $hora['objects_15_in_16']['no_efectiva_2']),
                    'efectiva' => $hora['objects_15_in_16']['efectiva'],
                    'promesa' => $hora['objects_15_in_16']['promesa'],
                ],
                (object) [
                    'time_start' => Carbon::parse('11:20:00'),
                    'time_end' => Carbon::parse('13:50:00'),
                    'no_efectiva' => random_int($hora['objects_11_in_13']['no_efectiva_1'], $hora['objects_11_in_13']['no_efectiva_2']),
                    'efectiva' => $hora['objects_11_in_13']['efectiva'],
                    'promesa' => $hora['objects_11_in_13']['promesa'],
                ],
                (object) [
                    'time_start' => Carbon::parse('08:00:00'),
                    'time_end' => Carbon::parse('14:50:00'),
                    'no_efectiva' => random_int($hora['objects_08_in_14']['no_efectiva_1'], $hora['objects_08_in_14']['no_efectiva_2']),
                    'efectiva' => $hora['objects_08_in_14']['efectiva'],
                    'promesa' => $hora['objects_08_in_14']['promesa'],
                ],
                (object) [
                    'time_start' => Carbon::parse('15:30:00'),
                    'time_end' => Carbon::parse('18:20:00'),
                    'no_efectiva' => random_int($hora['objects_15_in_18']['no_efectiva_1'], $hora['objects_15_in_18']['no_efectiva_2']),
                    'efectiva' => $hora['objects_15_in_18']['efectiva'],
                    'promesa' => $hora['objects_15_in_18']['promesa'],
                ],
                (object) [
                    'time_start' => Carbon::parse('13:25:00'),
                    'time_end' => Carbon::parse('17:35:00'),
                    'no_efectiva' => random_int($hora['objects_13_in_17']['no_efectiva_1'], $hora['objects_13_in_17']['no_efectiva_2']),
                    'efectiva' => $hora['objects_13_in_17']['efectiva'],
                    'promesa' => $hora['objects_13_in_17']['promesa'],
                ],
                (object) [
                    'time_start' => Carbon::parse('08:50:00'),
                    'time_end' => Carbon::parse('13:20:00'),
                    'no_efectiva' => random_int($hora['objects_08_in_13']['no_efectiva_1'], $hora['objects_08_in_13']['no_efectiva_2']),
                    'efectiva' => $hora['objects_08_in_13']['efectiva'],
                    'promesa' => $hora['objects_08_in_13']['promesa'],
                ],
                (object) [
                    'time_start' => Carbon::parse('16:10:00'),
                    'time_end' => Carbon::parse('17:10:00'),
                    'no_efectiva' => random_int($hora['objects_16_in_17_50']['no_efectiva_1'], $hora['objects_16_in_17_50']['no_efectiva_2']),
                    'efectiva' => $hora['objects_16_in_17_50']['efectiva'],
                    'promesa' => $hora['objects_16_in_17_50']['promesa'],
                ],
            ];
        }

        $id = $managements->count() + 1;

        $userIndex = 0;
        // Iteramos sobre cada grupo de $effectiveness
        $cuentas_gestionadas = $managements->pluck('acco_code_account')->unique()->values()->toArray();
        $clientes_gestionados = $managements->pluck('peop_dni_c')->unique()->values()->toArray();
        $telefonos_gestionados = $managements->pluck('data_value')->unique()->values()->toArray();

        foreach ($assignments_accounts as $indice => $assignment_account) {
            if (in_array($assignment_account->acco_code_account, $cuentas_gestionadas) || in_array($assignment_account->peop_dni, $clientes_gestionados) || in_array($assignment_account->data_value, $telefonos_gestionados)) {
                continue;
            } else {
                $cuentas_gestionadas[] = $assignment_account->acco_code_account;
                $clientes_gestionados[] = $assignment_account->peop_dni;
                if (!is_null($assignment_account->data_value)) {
                    $telefonos_gestionados[] = $assignment_account->data_value;
                }
            }
            $horario = $patronHorario[random_int(0, 9)];

            $payment = $payments->where('pay_account', $assignment_account->acco_code_account)->random(1)->first();

            if ($payment->pay_recaudation_date == $payment->pay_date) {
                $cuenta = $assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->where('acco_code_account', $assignment_account->acco_code_account)->first();
                if ($cuenta && $cuenta->typi_name == 'mensaje con tercero/razon') {
                    continue;
                }

                if (!$cuenta) {
                    $cuenta = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->where('acco_code_account', $assignment_account->acco_code_account)->first();
                }

                if ($assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->count() < $assignValues->ejecutivos[$userIndex]->cant_promesas_con_pago) {
                    $typi_promesa = (object) [
                        'typi_effective' => true,
                        'typi_name' => 'cliente al dia',
                        'acma_observation' => 'se verifica en el sistema y el titular realizo el pago el dia DD/MM/AAAA por valor de $$$ quedando el cliente al dia con la casa de cobro',
                        'time_min' => 45,
                        'time_max' => 70
                    ];

                    $management = clone $managements->first();

                    $isEfectiva = true;

                    if ($cuenta) {
                        $random_start = Carbon::parse($cuenta->acma_end_time);
                        $random_end = Carbon::parse('18:50:00')->diffInSeconds($random_start);

                        $random_int = random_int(60, 90);

                        $time_start = Carbon::parse($cuenta->acma_end_time)->addSeconds(random_int($random_int, $random_end));
                        $time_end = Carbon::parse($time_start)->addSeconds(random_int($typi_promesa->time_min, $typi_promesa->time_max));

                        $allExistingRanges = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago)))));

                        if ($this->checkTimeRanges($time_start, $time_end, $allExistingRanges)) {
                            // Buscar nuevo horario disponible
                            $newRange = $this->findNextAvailableTimeRangeEffective($time_start, $time_end, $allExistingRanges, 90, $random_start, $random_end);

                            // Actualizamos time_start y time_end si se encontró un nuevo rango
                            if ($newRange) {
                                $time_start = $newRange['start'];
                                $time_end = $newRange['end'];
                            } else {
                                $isEfectiva = false;
                            }
                        }

                        $time_start = $time_start->format('H:i:s');
                        $time_end = $time_end->format('H:i:s');
                    } else {
                        $time_start = Carbon::parse($horario->time_start)->addSeconds(random_int(0, $horario->time_start->diffInSeconds($horario->time_end)));
                        $time_end = Carbon::parse($time_start)->addSeconds(random_int($typi_promesa->time_min, $typi_promesa->time_max));

                        $allExistingRanges = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago)))));

                        if ($this->checkTimeRanges($time_start, $time_end, $allExistingRanges)) {
                            $time_start = $time_start->format('H:i:s');
                            $time_end = $time_end->format('H:i:s');
                        } else {
                            $rangeOverlap = $this->findNextAvailableTimeRange($time_start, $time_end, $allExistingRanges);
                            $time_start = $rangeOverlap['start']->format('H:i:s');
                            $time_end = $rangeOverlap['end']->format('H:i:s');
                        }
                    }

                    if ($isEfectiva) {

                        $payment_date = Carbon::parse($payment->pay_date)->format('Y-m-d');

                        $typi_promesa->acma_observation = str_replace('XXX-XXX-XXXX', is_null($assignment_account->data_value) ? 'xxxxxxxxxx' : $assignment_account->data_value, $typi_promesa->acma_observation);
                        $typi_promesa->acma_observation = str_replace('X|X|X', $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname, $typi_promesa->acma_observation);
                        $typi_promesa->acma_observation = str_replace('DD/MM/AAAA', $payment_date, $typi_promesa->acma_observation);
                        $typi_promesa->acma_observation = str_replace('$$$', $payment->pay_value, $typi_promesa->acma_observation);

                        $management->id = $id;
                        $management->acma_start_date = $management->acma_start_date;
                        $management->acma_end_date = $management->acma_end_date;
                        $management->acma_start_time = $time_start;
                        $management->acma_end_time = $time_end;
                        $management->acco_code_account = $assignment_account->acco_code_account;
                        $management->data_value = is_null($assignment_account->data_value) ? 'xxxxxxxxxx' : $assignment_account->data_value;
                        $management->renp_name = '';
                        $management->payment_date = $payment_date;
                        $management->payment_value = $payment->pay_value;
                        $management->payment_discount = $payment->pay_discount_rate;
                        $management->acma_contact_name = $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname;
                        $management->acma_iseffective = $typi_promesa->typi_effective;
                        $management->acma_observation = $typi_promesa->acma_observation;
                        $management->assi_name = $assignment_account->assi->assi_name;
                        $management->camp_name = $assignment_account->camp->camp_name;
                        $management->alli_name = $assignment_account->alli->alli_name;
                        $management->typi_name = $typi_promesa->typi_name;
                        $management->typi_effective = $typi_promesa->typi_effective;
                        $management->asac_balance = $payment->pay_value;
                        $management->peop_name_g = $assignValues->ejecutivos[$userIndex]->ejec_name;
                        $management->peop_dni_g = $assignValues->ejecutivos[$userIndex]->ejec_cc;
                        $management->peop_name_c = $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname;
                        $management->peop_dni_c = $assignment_account->peop_dni;
                        $management->foal_name = $payment->focus->foal_name;

                        $id++;

                        $assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago = $assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->push($management);
                    }
                }
                continue;
            }

            if ($horario->no_efectiva > 0) {

                $timeStart = Carbon::parse($horario->time_start);
                $timeEnd = Carbon::parse($timeStart)->addSeconds(random_int(0, $timeStart->diffInSeconds($horario->time_end)))->addSeconds(random_int(0, 59))->format('H:i:s');

                for ($i = 0; $i < $horario->no_efectiva; $i++) {
                    $typi_no_efectivas = [
                        (object) [
                            'typi_effective' => false,
                            'typi_name' => 'mensaje en buzon',
                            'acma_observation' => 'se llama a la linea movil XXX-XXX-XXXX repica en repetidas ocasiones y pasa a una grabacion de buzon de mensajes',
                            'time_min' => 12,
                            'time_max' => 20
                        ],
                        (object) [
                            'typi_effective' => false,
                            'typi_name' => 'no contestan',
                            'acma_observation' => 'se llama a la linea XXX-XXX-XXXX repica en repetidas ocasiones pero no contestan',
                            'time_min' => 12,
                            'time_max' => 30
                        ],
                        (object) [
                            'typi_effective' => false,
                            'typi_name' => 'telefono apagado',
                            'acma_observation' => 'se llama a la linea movil XXX-XXX-XXXX grabacion informa que el telefono esta apagado',
                            'time_min' => 12,
                            'time_max' => 27
                        ]
                    ];

                    $typi_no_efectiva = $typi_no_efectivas[random_int(0, 2)];

                    $time_start = Carbon::parse($timeStart)->addSeconds(random_int(0, $timeStart->diffInSeconds($timeEnd)))->addSeconds(random_int(0, 59));
                    $time_end = Carbon::parse($time_start)->addSeconds(random_int($typi_no_efectiva->time_min, $typi_no_efectiva->time_max));

                    $allExistingRanges = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago)))));

                    if ($this->checkTimeRanges($time_start, $time_end, $allExistingRanges)) {
                        $time_start = $time_start->format('H:i:s');
                        $time_end = $time_end->format('H:i:s');
                    } else {
                        $rangeOverlap = $this->findNextAvailableTimeRange($time_start, $time_end, $allExistingRanges);
                        $time_start = $rangeOverlap['start']->format('H:i:s');
                        $time_end = $rangeOverlap['end']->format('H:i:s');
                    }

                    if (($assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->count() + $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->count()) < ($assignValues->ejecutivos[$userIndex]->cant_no_efectivas_con_pago + $assignValues->ejecutivos[$userIndex]->cant_no_efectivas_sin_pago)) {
                        $typi_no_efectiva->acma_observation = str_replace('XXX-XXX-XXXX', is_null($assignment_account->data_value) ? 'xxxxxxxxxx' : $assignment_account->data_value, $typi_no_efectiva->acma_observation);

                        $management = clone $managements->first();

                        $management->id = $id;
                        $management->acma_start_date = $management->acma_start_date;
                        $management->acma_end_date = $management->acma_end_date;
                        $management->acma_start_time = $time_start;
                        $management->acma_end_time = $time_end;
                        $management->acco_code_account = $assignment_account->acco_code_account;
                        $management->data_value = is_null($assignment_account->data_value) ? 'xxxxxxxxxx' : $assignment_account->data_value;
                        $management->renp_name = 'n/a';
                        $management->payment_date = 'n/a';
                        $management->payment_value = 'n/a';
                        $management->payment_discount = 'n/a';
                        $management->acma_contact_name = $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname;
                        $management->acma_iseffective = $typi_no_efectiva->typi_effective;
                        $management->acma_observation = $typi_no_efectiva->acma_observation;
                        $management->assi_name = $assignment_account->assi->assi_name;
                        $management->camp_name = $assignment_account->camp->camp_name;
                        $management->alli_name = $assignment_account->alli->alli_name;
                        $management->typi_name = $typi_no_efectiva->typi_name;
                        $management->typi_effective = $typi_no_efectiva->typi_effective;
                        $management->asac_balance = $assignment_account->asac_balance;
                        $management->peop_name_g = $assignValues->ejecutivos[$userIndex]->ejec_name;
                        $management->peop_dni_g = $assignValues->ejecutivos[$userIndex]->ejec_cc;
                        $management->peop_name_c = $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname;
                        $management->peop_dni_c = $assignment_account->peop_dni;
                        $management->foal_name = is_null($assignment_account->foal) ? $management->foal_name : $assignment_account->foal->foal_name;

                        $id++;

                        $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->push($management);

                        $timeStart = Carbon::parse($horario->time_start);
                        $timeEnd = Carbon::parse($timeStart)->addSeconds(random_int(0, $timeStart->diffInSeconds($horario->time_end)))->addSeconds(random_int(0, 59))->format('H:i:s');
                    }
                }
            }

            if ($horario->efectiva > 0) {

                $cuenta = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->where('acco_code_account', $assignment_account->acco_code_account)->first();

                if (($assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->count() + $assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->count()) < ($assignValues->ejecutivos[$userIndex]->cant_efectivas_con_pago + $assignValues->ejecutivos[$userIndex]->cant_efectivas_sin_pago)) {
                    $typi_efectivas = [
                        (object) [
                            'typi_effective' => true,
                            'typi_name' => 'cuelga la llamada',
                            'acma_observation' => 'se llama al XXX-XXX-XXXX la persona que contesta no se identifica y termina la llamada',
                            'time_min' => 20,
                            'time_max' => 35
                        ],
                        (object) [
                            'typi_effective' => true,
                            'typi_name' => 'agendado',
                            'acma_observation' => 'se llama al XXX-XXX-XXXX se habla con el sr X|X|X titular / encargado de pago informa que en el momento no puede antender la llamada solicita que se comuniquen nuevamente',
                            'time_min' => 60,
                            'time_max' => 90
                        ],
                        (object) [
                            'typi_effective' => true,
                            'typi_name' => 'cuelga la llamada',
                            'acma_observation' => 'se llama al XXX-XXX-XXXX la persona que contesta no se identifica y termina la llamada',
                            'time_min' => 20,
                            'time_max' => 35
                        ],
                        (object) [
                            'typi_effective' => true,
                            'typi_name' => 'agendado',
                            'acma_observation' => 'se llama al XXX-XXX-XXXX se habla con el sr X|X|X titular / encargado de pago informa que en el momento no puede antender la llamada solicita que se comuniquen nuevamente',
                            'time_min' => 60,
                            'time_max' => 90
                        ],
                        (object) [
                            'typi_effective' => true,
                            'typi_name' => 'mensaje con tercero/razon',
                            'acma_observation' => 'se llama al XXX-XXX-XXXX contesta tercero no indica nombre se brinda datos de casa de cobro',
                            'time_min' => 40,
                            'time_max' => 80
                        ],
                    ];

                    $management = clone $managements->first();

                    $isEfectiva = true;
                    $typi_efectiva = $typi_efectivas[random_int(0, 4)];

                    if ($cuenta) {
                        $random_start = Carbon::parse($horario->promesa == 0 ? '18:40:00' : '17:10:00');
                        $random_end = Carbon::parse($cuenta->acma_end_time)->diffInSeconds($random_start);

                        $random_int = random_int(60, 90);

                        $time_start = Carbon::parse($cuenta->acma_end_time)->addSeconds(random_int($random_int, $random_end));
                        $time_end = Carbon::parse($time_start)->addSeconds(random_int($typi_efectiva->time_min, $typi_efectiva->time_max));

                        $allExistingRanges = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago)))));

                        if ($this->checkTimeRanges($time_start, $time_end, $allExistingRanges)) {
                            // Buscar nuevo horario disponible
                            $newRange = $this->findNextAvailableTimeRangeEffective($time_start, $time_end, $allExistingRanges, 90, $random_start, $random_end);

                            // Actualizamos time_start y time_end si se encontró un nuevo rango
                            if ($newRange) {
                                $time_start = $newRange['start'];
                                $time_end = $newRange['end'];
                            } else {
                                $isEfectiva = false;
                            }
                        }

                        $time_start = $time_start->format('H:i:s');
                        $time_end = $time_end->format('H:i:s');
                    } else {
                        $time_start = Carbon::parse($horario->time_start)->addSeconds(random_int(0, $horario->time_start->diffInSeconds($horario->time_end)));
                        $time_end = Carbon::parse($time_start)->addSeconds(random_int($typi_efectiva->time_min, $typi_efectiva->time_max));

                        $allExistingRanges = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago)))));

                        if ($this->checkTimeRanges($time_start, $time_end, $allExistingRanges)) {
                            $time_start = $time_start->format('H:i:s');
                            $time_end = $time_end->format('H:i:s');
                        } else {
                            $rangeOverlap = $this->findNextAvailableTimeRange($time_start, $time_end, $allExistingRanges);
                            $time_start = $rangeOverlap['start']->format('H:i:s');
                            $time_end = $rangeOverlap['end']->format('H:i:s');
                        }
                    }

                    if ($isEfectiva) {

                        $typi_efectiva->acma_observation = str_replace('XXX-XXX-XXXX', is_null($assignment_account->data_value) ? 'xxxxxxxxxx' : $assignment_account->data_value, $typi_efectiva->acma_observation);
                        $typi_efectiva->acma_observation = str_replace('X|X|X', $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname, $typi_efectiva->acma_observation);

                        $management->id = $id;
                        $management->acma_start_date = $management->acma_start_date;
                        $management->acma_end_date = $management->acma_end_date;
                        $management->acma_start_time = $time_start;
                        $management->acma_end_time = $time_end;
                        $management->acco_code_account = $assignment_account->acco_code_account;
                        $management->data_value = is_null($assignment_account->data_value) ? 'xxxxxxxxxx' : $assignment_account->data_value;
                        $management->renp_name = 'n/a';
                        $management->payment_date = 'n/a';
                        $management->payment_value = 'n/a';
                        $management->payment_discount = 'n/a';
                        $management->acma_contact_name = $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname;
                        $management->acma_iseffective = $typi_efectiva->typi_effective;
                        $management->acma_observation = $typi_efectiva->acma_observation;
                        $management->assi_name = $assignment_account->assi->assi_name;
                        $management->camp_name = $assignment_account->camp->camp_name;
                        $management->alli_name = $assignment_account->alli->alli_name;
                        $management->typi_name = $typi_efectiva->typi_name;
                        $management->typi_effective = $typi_efectiva->typi_effective;
                        $management->asac_balance = $assignment_account->asac_balance;
                        $management->peop_name_g = $assignValues->ejecutivos[$userIndex]->ejec_name;
                        $management->peop_dni_g = $assignValues->ejecutivos[$userIndex]->ejec_cc;
                        $management->peop_name_c = $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname;
                        $management->peop_dni_c = $assignment_account->peop_dni;
                        $management->foal_name = is_null($assignment_account->foal) ? $management->foal_name : $assignment_account->foal->foal_name;

                        $id++;

                        $assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago = $assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->push($management);
                    }
                }
            }

            if ($horario->promesa > 0) {

                $cuenta = $assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->where('acco_code_account', $assignment_account->acco_code_account)->first();
                if ($cuenta && $cuenta->typi_name == 'mensaje con tercero/razon') {
                    continue;
                }

                if (!$cuenta) {
                    $cuenta = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->where('acco_code_account', $assignment_account->acco_code_account)->first();
                }

                if (($assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->count() + $assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago->count()) < ($assignValues->ejecutivos[$userIndex]->cant_promesas_con_pago + $assignValues->ejecutivos[$userIndex]->cant_promesas_sin_pago)) {
                    $typi_promesa = (object) [
                        'typi_effective' => true,
                        'typi_name' => 'promesa de pago',
                        'acma_observation' => 'se llama al XXX-XXX-XXXX se habla con el sr X|X|X titular / encargado de pago informa que no habia cancelado debido a no responde se genera compromiso de pago para el DD/MM/AAAA por valor de $$$ y no indica datos adicionales',
                        'time_min' => 240,
                        'time_max' => 360
                    ];

                    $management = clone $managements->first();

                    $isEfectiva = true;

                    if ($cuenta) {
                        $random_start = Carbon::parse($cuenta->acma_end_time);
                        $random_end = Carbon::parse('17:50:00')->diffInSeconds($random_start);

                        $random_int = random_int(60, 90);

                        $time_start = Carbon::parse($cuenta->acma_end_time)->addSeconds(random_int($random_int, $random_end));
                        $time_end = Carbon::parse($time_start)->addSeconds(random_int($typi_promesa->time_min, $typi_promesa->time_max));

                        $allExistingRanges = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago)))));

                        if ($this->checkTimeRanges($time_start, $time_end, $allExistingRanges)) {
                            // Buscar nuevo horario disponible
                            $newRange = $this->findNextAvailableTimeRangeEffective($time_start, $time_end, $allExistingRanges, 90, $random_start, $random_end);

                            // Actualizamos time_start y time_end si se encontró un nuevo rango
                            if ($newRange) {
                                $time_start = $newRange['start'];
                                $time_end = $newRange['end'];
                            } else {
                                $isEfectiva = false;
                            }
                        }

                        $time_start = $time_start->format('H:i:s');
                        $time_end = $time_end->format('H:i:s');
                    } else {
                        $time_start = Carbon::parse($horario->time_start)->addSeconds(random_int(0, $horario->time_start->diffInSeconds($horario->time_end)));
                        $time_end = Carbon::parse($time_start)->addSeconds(random_int($typi_promesa->time_min, $typi_promesa->time_max));

                        $allExistingRanges = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago)))));

                        if ($this->checkTimeRanges($time_start, $time_end, $allExistingRanges)) {
                            $time_start = $time_start->format('H:i:s');
                            $time_end = $time_end->format('H:i:s');
                        } else {
                            $rangeOverlap = $this->findNextAvailableTimeRange($time_start, $time_end, $allExistingRanges);
                            $time_start = $rangeOverlap['start']->format('H:i:s');
                            $time_end = $rangeOverlap['end']->format('H:i:s');
                        }
                    }

                    if ($isEfectiva) {

                        $payment = $payments->where('pay_account', $assignment_account->acco_code_account)->random(1)->first();
                        $payment_date = Carbon::parse($payment->pay_date)->addDays(random_int(0, random_int(2, 3)))->format('Y-m-d');

                        $typi_promesa->acma_observation = str_replace('XXX-XXX-XXXX', is_null($assignment_account->data_value) ? 'xxxxxxxxxx' : $assignment_account->data_value, $typi_promesa->acma_observation);
                        $typi_promesa->acma_observation = str_replace('X|X|X', $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname, $typi_promesa->acma_observation);
                        $typi_promesa->acma_observation = str_replace('DD/MM/AAAA', $payment_date, $typi_promesa->acma_observation);
                        $typi_promesa->acma_observation = str_replace('$$$', $payment->pay_value, $typi_promesa->acma_observation);

                        $management->id = $id;
                        $management->acma_start_date = $management->acma_start_date;
                        $management->acma_end_date = $management->acma_end_date;
                        $management->acma_start_time = $time_start;
                        $management->acma_end_time = $time_end;
                        $management->acco_code_account = $assignment_account->acco_code_account;
                        $management->data_value = is_null($assignment_account->data_value) ? 'xxxxxxxxxx' : $assignment_account->data_value;
                        $management->renp_name = '';
                        $management->payment_date = $payment_date;
                        $management->payment_value = $payment->pay_value;
                        $management->payment_discount = $payment->pay_discount_rate;
                        $management->acma_contact_name = $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname;
                        $management->acma_iseffective = $typi_promesa->typi_effective;
                        $management->acma_observation = $typi_promesa->acma_observation;
                        $management->assi_name = $assignment_account->assi->assi_name;
                        $management->camp_name = $assignment_account->camp->camp_name;
                        $management->alli_name = $assignment_account->alli->alli_name;
                        $management->typi_name = $typi_promesa->typi_name;
                        $management->typi_effective = $typi_promesa->typi_effective;
                        $management->asac_balance = $payment->pay_value;
                        $management->peop_name_g = $assignValues->ejecutivos[$userIndex]->ejec_name;
                        $management->peop_dni_g = $assignValues->ejecutivos[$userIndex]->ejec_cc;
                        $management->peop_name_c = $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname;
                        $management->peop_dni_c = $assignment_account->peop_dni;
                        $management->foal_name = $payment->focus->foal_name;

                        $id++;

                        $assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago = $assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->push($management);
                    }
                }
            }

            $userIndex = ($userIndex + 1) % count($assignValues->ejecutivos);
        }

        return $assignValues;
    }

    private function paymentExtraAgreement($assignValues, $settings, $managements, $request)
    {
        $payments = Payments::with('focus')
            ->when($settings->assignments->isNotEmpty(), function ($query) use ($settings) {
                $query->whereHasMorph('model', [Assignment::class], function ($query) use ($settings) {
                    $query->whereIn('model_id', $settings->assignments->pluck('model_id'));
                });
            })
            ->when($settings->assignments->isEmpty(), function ($query) use ($settings) {
                $query->whereHasMorph('model', [Campaign::class], function ($query) use ($settings) {
                    $query->whereIn('model_id', $settings->campaign->pluck('model_id'));
                });
            })
            ->whereNotIn('pay_account', $managements->whereNotNull('acco_code_account')->pluck('acco_code_account')->unique()->values())
            ->where('pay_date', $request->date)
            ->whereIn('real_payment', [false, true])
            ->orderBy('real_payment', 'asc')
            ->whereIn('focus_id', $settings->focus->pluck('model_id'))
            ->get();

        $assignments_accounts = AssignmentAccounts::with('alli', 'assi', 'camp', 'foal')
            ->where('camp_id', $settings->campaign->model_id)
            ->when($settings->assignments->isNotEmpty(), function ($query) use ($settings) {
                $query->whereIn('assi_id', $settings->assignments->pluck('model_id'));
            })
            /* ->whereNotNull('data_value') */
            ->whereNotIn('peop_dni', $managements->whereNotNull('peop_dni_c')->pluck('peop_dni_c')->unique())
            /* ->whereIn('assi_id', $settings->assignments->pluck('model_id')) */
            ->whereIn('acco_code_account', $payments->pluck('pay_account')->unique())
            ->get();

            $patrongHorarioFuction = $settings->time_patterns()->where('id_function', 3)->get();

            $timePatterns = $patrongHorarioFuction->map(function ($pattern) {
                return [
                    'objects_8_in_10' => json_decode($pattern->objects_8_in_10, true),
                    'objects_16_in_17' => json_decode($pattern->objects_16_in_17, true),
                    'objects_12_in_13' => json_decode($pattern->objects_12_in_13, true),
                    'objects_15_in_16' => json_decode($pattern->objects_15_in_16, true),
                    'objects_11_in_13' => json_decode($pattern->objects_11_in_13, true),
                    'objects_08_in_14' => json_decode($pattern->objects_08_in_14, true),
                    'objects_15_in_18' => json_decode($pattern->objects_15_in_18, true),
                    'objects_13_in_17' => json_decode($pattern->objects_13_in_17, true),
                    'objects_08_in_13' => json_decode($pattern->objects_08_in_13, true),
                    'objects_16_in_17_50' => json_decode($pattern->objects_16_in_17_50, true),
                ];
            });

            $patronHorario = [];

            foreach ($timePatterns as $hora) {
                $patronHorario = [
                    (object) [
                        'time_start' => Carbon::parse('08:00:00'),
                        'time_end' => Carbon::parse('10:30:00'),
                        'no_efectiva' => random_int($hora['objects_8_in_10']['no_efectiva_1'], $hora['objects_8_in_10']['no_efectiva_2']),
                        'efectiva' => $hora['objects_8_in_10']['efectiva'],
                        'promesa' => $hora['objects_8_in_10']['promesa'],
                    ],
                    (object) [
                        'time_start' => Carbon::parse('16:30:00'),
                        'time_end' => Carbon::parse('17:50:00'),
                        'no_efectiva' => random_int($hora['objects_16_in_17']['no_efectiva_1'], $hora['objects_16_in_17']['no_efectiva_2']),
                        'efectiva' => $hora['objects_16_in_17']['efectiva'],
                        'promesa' => $hora['objects_16_in_17']['promesa'],
                    ],
                    (object) [
                        'time_start' => Carbon::parse('12:30:00'),
                        'time_end' => Carbon::parse('13:30:00'),
                        'no_efectiva' => random_int($hora['objects_12_in_13']['no_efectiva_1'], $hora['objects_12_in_13']['no_efectiva_2']),
                        'efectiva' => $hora['objects_12_in_13']['efectiva'],
                        'promesa' => $hora['objects_12_in_13']['promesa'],
                    ],
                    (object) [
                        'time_start' => Carbon::parse('15:50:00'),
                        'time_end' => Carbon::parse('16:50:00'),
                        'no_efectiva' => random_int($hora['objects_15_in_16']['no_efectiva_1'], $hora['objects_15_in_16']['no_efectiva_2']),
                        'efectiva' => $hora['objects_15_in_16']['efectiva'],
                        'promesa' => $hora['objects_15_in_16']['promesa'],
                    ],
                    (object) [
                        'time_start' => Carbon::parse('11:20:00'),
                        'time_end' => Carbon::parse('13:50:00'),
                        'no_efectiva' => random_int($hora['objects_11_in_13']['no_efectiva_1'], $hora['objects_11_in_13']['no_efectiva_2']),
                        'efectiva' => $hora['objects_11_in_13']['efectiva'],
                        'promesa' => $hora['objects_11_in_13']['promesa'],
                    ],
                    (object) [
                        'time_start' => Carbon::parse('08:00:00'),
                        'time_end' => Carbon::parse('14:50:00'),
                        'no_efectiva' => random_int($hora['objects_08_in_14']['no_efectiva_1'], $hora['objects_08_in_14']['no_efectiva_2']),
                        'efectiva' => $hora['objects_08_in_14']['efectiva'],
                        'promesa' => $hora['objects_08_in_14']['promesa'],
                    ],
                    (object) [
                        'time_start' => Carbon::parse('15:30:00'),
                        'time_end' => Carbon::parse('18:20:00'),
                        'no_efectiva' => random_int($hora['objects_15_in_18']['no_efectiva_1'], $hora['objects_15_in_18']['no_efectiva_2']),
                        'efectiva' => $hora['objects_15_in_18']['efectiva'],
                        'promesa' => $hora['objects_15_in_18']['promesa'],
                    ],
                    (object) [
                        'time_start' => Carbon::parse('13:25:00'),
                        'time_end' => Carbon::parse('17:35:00'),
                        'no_efectiva' => random_int($hora['objects_13_in_17']['no_efectiva_1'], $hora['objects_13_in_17']['no_efectiva_2']),
                        'efectiva' => $hora['objects_13_in_17']['efectiva'],
                        'promesa' => $hora['objects_13_in_17']['promesa'],
                    ],
                    (object) [
                        'time_start' => Carbon::parse('08:50:00'),
                        'time_end' => Carbon::parse('13:20:00'),
                        'no_efectiva' => random_int($hora['objects_08_in_13']['no_efectiva_1'], $hora['objects_08_in_13']['no_efectiva_2']),
                        'efectiva' => $hora['objects_08_in_13']['efectiva'],
                        'promesa' => $hora['objects_08_in_13']['promesa'],
                    ],
                    (object) [
                        'time_start' => Carbon::parse('16:10:00'),
                        'time_end' => Carbon::parse('17:10:00'),
                        'no_efectiva' => random_int($hora['objects_16_in_17_50']['no_efectiva_1'], $hora['objects_16_in_17_50']['no_efectiva_2']),
                        'efectiva' => $hora['objects_16_in_17_50']['efectiva'],
                        'promesa' => $hora['objects_16_in_17_50']['promesa'],
                    ],
                ];
            }

        $id = $managements->count() + 1;

        $userIndex = 0;
        // Iteramos sobre cada grupo de $effectiveness
        $cuentas_gestionadas = $managements->pluck('acco_code_account')->unique()->values()->toArray();
        $clientes_gestionados = $managements->pluck('peop_dni_c')->unique()->values()->toArray();
        $telefonos_gestionados = $managements->pluck('data_value')->unique()->values()->toArray();

        foreach ($assignments_accounts as $indice => $assignment_account) {
            if (in_array($assignment_account->acco_code_account, $cuentas_gestionadas) || in_array($assignment_account->peop_dni, $clientes_gestionados) || in_array($assignment_account->data_value, $telefonos_gestionados)) {
                continue;
            } else {
                $cuentas_gestionadas[] = $assignment_account->acco_code_account;
                $clientes_gestionados[] = $assignment_account->peop_dni;
                if (!is_null($assignment_account->data_value)) {
                    $telefonos_gestionados[] = $assignment_account->data_value;
                }
            }
            $horario = $patronHorario[random_int(0, 9)];

            $payment = $payments->where('pay_account', $assignment_account->acco_code_account)->random(1)->first();

            if ($payment->pay_recaudation_date == $payment->pay_date) {
                $cuenta = $assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->where('acco_code_account', $assignment_account->acco_code_account)->first();
                if ($cuenta && $cuenta->typi_name == 'mensaje con tercero/razon') {
                    continue;
                }

                if (!$cuenta) {
                    $cuenta = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->where('acco_code_account', $assignment_account->acco_code_account)->first();
                }

                if (($assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->count() + $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->count() + $assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->count() + $assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->count() + $assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->count() + $assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago->count()) < ($assignValues->ejecutivos[$userIndex]->cant_no_efectivas_con_pago + $assignValues->ejecutivos[$userIndex]->cant_no_efectivas_sin_pago + $assignValues->ejecutivos[$userIndex]->cant_efectivas_con_pago + $assignValues->ejecutivos[$userIndex]->cant_efectivas_sin_pago + $assignValues->ejecutivos[$userIndex]->cant_promesas_con_pago + $assignValues->ejecutivos[$userIndex]->cant_promesas_sin_pago)) {
                    $typi_promesa = (object) [
                        'typi_effective' => true,
                        'typi_name' => 'cliente al dia',
                        'acma_observation' => 'se verifica en el sistema y el titular realizo el pago el dia DD/MM/AAAA por valor de $$$ quedando el cliente al dia con la casa de cobro',
                        'time_min' => 45,
                        'time_max' => 70
                    ];

                    $management = clone $managements->first();

                    $isEfectiva = true;

                    if ($cuenta) {
                        $random_start = Carbon::parse($cuenta->acma_end_time);
                        $random_end = Carbon::parse('18:50:00')->diffInSeconds($random_start);

                        $random_int = random_int(60, 90);

                        $time_start = Carbon::parse($cuenta->acma_end_time)->addSeconds(random_int($random_int, $random_end));
                        $time_end = Carbon::parse($time_start)->addSeconds(random_int($typi_promesa->time_min, $typi_promesa->time_max));

                        $allExistingRanges = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago)))));

                        if ($this->checkTimeRanges($time_start, $time_end, $allExistingRanges)) {
                            // Buscar nuevo horario disponible
                            $newRange = $this->findNextAvailableTimeRangeEffective($time_start, $time_end, $allExistingRanges, 90, $random_start, $random_end);

                            // Actualizamos time_start y time_end si se encontró un nuevo rango
                            if ($newRange) {
                                $time_start = $newRange['start'];
                                $time_end = $newRange['end'];
                            } else {
                                $isEfectiva = false;
                            }
                        }

                        $time_start = $time_start->format('H:i:s');
                        $time_end = $time_end->format('H:i:s');
                    } else {
                        $time_start = Carbon::parse($horario->time_start)->addSeconds(random_int(0, $horario->time_start->diffInSeconds($horario->time_end)));
                        $time_end = Carbon::parse($time_start)->addSeconds(random_int($typi_promesa->time_min, $typi_promesa->time_max));

                        $allExistingRanges = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago)))));

                        if ($this->checkTimeRanges($time_start, $time_end, $allExistingRanges)) {
                            $time_start = $time_start->format('H:i:s');
                            $time_end = $time_end->format('H:i:s');
                        } else {
                            $rangeOverlap = $this->findNextAvailableTimeRange($time_start, $time_end, $allExistingRanges);
                            $time_start = $rangeOverlap['start']->format('H:i:s');
                            $time_end = $rangeOverlap['end']->format('H:i:s');
                        }
                    }

                    if ($isEfectiva) {

                        $payment_date = Carbon::parse($payment->pay_date)->format('Y-m-d');;

                        $typi_promesa->acma_observation = str_replace('XXX-XXX-XXXX', is_null($assignment_account->data_value) ? 'xxxxxxxxxx' : $assignment_account->data_value, $typi_promesa->acma_observation);
                        $typi_promesa->acma_observation = str_replace('X|X|X', $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname, $typi_promesa->acma_observation);
                        $typi_promesa->acma_observation = str_replace('DD/MM/AAAA', $payment_date, $typi_promesa->acma_observation);
                        $typi_promesa->acma_observation = str_replace('$$$', $payment->pay_value, $typi_promesa->acma_observation);

                        $management->id = $id;
                        $management->acma_start_date = $management->acma_start_date;
                        $management->acma_end_date = $management->acma_end_date;
                        $management->acma_start_time = $time_start;
                        $management->acma_end_time = $time_end;
                        $management->acco_code_account = $assignment_account->acco_code_account;
                        $management->data_value = is_null($assignment_account->data_value) ? 'xxxxxxxxxx' : $assignment_account->data_value;
                        $management->renp_name = '';
                        $management->payment_date = $payment_date;
                        $management->payment_value = $payment->pay_value;
                        $management->payment_discount = $payment->pay_discount_rate;
                        $management->acma_contact_name = $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname;
                        $management->acma_iseffective = $typi_promesa->typi_effective;
                        $management->acma_observation = $typi_promesa->acma_observation;
                        $management->assi_name = $assignment_account->assi->assi_name;
                        $management->camp_name = $assignment_account->camp->camp_name;
                        $management->alli_name = $assignment_account->alli->alli_name;
                        $management->typi_name = $typi_promesa->typi_name;
                        $management->typi_effective = $typi_promesa->typi_effective;
                        $management->asac_balance = $payment->pay_value;
                        $management->peop_name_g = $assignValues->ejecutivos[$userIndex]->ejec_name;
                        $management->peop_dni_g = $assignValues->ejecutivos[$userIndex]->ejec_cc;
                        $management->peop_name_c = $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname;
                        $management->peop_dni_c = $assignment_account->peop_dni;
                        $management->foal_name = $payment->focus->foal_name;

                        $id++;

                        $assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago = $assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->push($management);
                    }
                }
                continue;
            }

            if ($horario->no_efectiva > 0) {

                $timeStart = Carbon::parse($horario->time_start);
                $timeEnd = Carbon::parse($timeStart)->addSeconds(random_int(0, $timeStart->diffInSeconds($horario->time_end)))->addSeconds(random_int(0, 59))->format('H:i:s');

                for ($i = 0; $i < $horario->no_efectiva; $i++) {
                    $typi_no_efectivas = [
                        (object) [
                            'typi_effective' => false,
                            'typi_name' => 'mensaje en buzon',
                            'acma_observation' => 'se llama a la linea movil XXX-XXX-XXXX repica en repetidas ocasiones y pasa a una grabacion de buzon de mensajes',
                            'time_min' => 12,
                            'time_max' => 20
                        ],
                        (object) [
                            'typi_effective' => false,
                            'typi_name' => 'no contestan',
                            'acma_observation' => 'se llama a la linea XXX-XXX-XXXX repica en repetidas ocasiones pero no contestan',
                            'time_min' => 12,
                            'time_max' => 30
                        ],
                        (object) [
                            'typi_effective' => false,
                            'typi_name' => 'telefono apagado',
                            'acma_observation' => 'se llama a la linea movil XXX-XXX-XXXX grabacion informa que el telefono esta apagado',
                            'time_min' => 12,
                            'time_max' => 27
                        ]
                    ];

                    $typi_no_efectiva = $typi_no_efectivas[random_int(0, 2)];

                    $time_start = Carbon::parse($timeStart)->addSeconds(random_int(0, $timeStart->diffInSeconds($timeEnd)))->addSeconds(random_int(0, 59));
                    $time_end = Carbon::parse($time_start)->addSeconds(random_int($typi_no_efectiva->time_min, $typi_no_efectiva->time_max));

                    $allExistingRanges = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago)))));

                    if ($this->checkTimeRanges($time_start, $time_end, $allExistingRanges)) {
                        $time_start = $time_start->format('H:i:s');
                        $time_end = $time_end->format('H:i:s');
                    } else {
                        $rangeOverlap = $this->findNextAvailableTimeRange($time_start, $time_end, $allExistingRanges);
                        $time_start = $rangeOverlap['start']->format('H:i:s');
                        $time_end = $rangeOverlap['end']->format('H:i:s');
                    }

                    if (($assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->count() + $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->count() + $assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->count() + $assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->count() + $assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->count() + $assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago->count()) < ($assignValues->ejecutivos[$userIndex]->cant_no_efectivas_con_pago + $assignValues->ejecutivos[$userIndex]->cant_no_efectivas_sin_pago + $assignValues->ejecutivos[$userIndex]->cant_efectivas_con_pago + $assignValues->ejecutivos[$userIndex]->cant_efectivas_sin_pago + $assignValues->ejecutivos[$userIndex]->cant_promesas_con_pago + $assignValues->ejecutivos[$userIndex]->cant_promesas_sin_pago)) {
                        $typi_no_efectiva->acma_observation = str_replace('XXX-XXX-XXXX', is_null($assignment_account->data_value) ? 'xxxxxxxxxx' : $assignment_account->data_value, $typi_no_efectiva->acma_observation);

                        $management = clone $managements->first();

                        $management->id = $id;
                        $management->acma_start_date = $management->acma_start_date;
                        $management->acma_end_date = $management->acma_end_date;
                        $management->acma_start_time = $time_start;
                        $management->acma_end_time = $time_end;
                        $management->acco_code_account = $assignment_account->acco_code_account;
                        $management->data_value = is_null($assignment_account->data_value) ? 'xxxxxxxxxx' : $assignment_account->data_value;
                        $management->renp_name = 'n/a';
                        $management->payment_date = 'n/a';
                        $management->payment_value = 'n/a';
                        $management->payment_discount = 'n/a';
                        $management->acma_contact_name = $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname;
                        $management->acma_iseffective = $typi_no_efectiva->typi_effective;
                        $management->acma_observation = $typi_no_efectiva->acma_observation;
                        $management->assi_name = $assignment_account->assi->assi_name;
                        $management->camp_name = $assignment_account->camp->camp_name;
                        $management->alli_name = $assignment_account->alli->alli_name;
                        $management->typi_name = $typi_no_efectiva->typi_name;
                        $management->typi_effective = $typi_no_efectiva->typi_effective;
                        $management->asac_balance = $assignment_account->asac_balance;
                        $management->peop_name_g = $assignValues->ejecutivos[$userIndex]->ejec_name;
                        $management->peop_dni_g = $assignValues->ejecutivos[$userIndex]->ejec_cc;
                        $management->peop_name_c = $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname;
                        $management->peop_dni_c = $assignment_account->peop_dni;
                        $management->foal_name = is_null($assignment_account->foal) ? $management->foal_name : $assignment_account->foal->foal_name;

                        $id++;

                        $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->push($management);

                        $timeStart = Carbon::parse($horario->time_start);
                        $timeEnd = Carbon::parse($timeStart)->addSeconds(random_int(0, $timeStart->diffInSeconds($horario->time_end)))->addSeconds(random_int(0, 59))->format('H:i:s');
                    }
                }
            }

            if ($horario->efectiva > 0) {

                $cuenta = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->where('acco_code_account', $assignment_account->acco_code_account)->first();

                if (!$cuenta) {
                    $cuenta = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->where('acco_code_account', $assignment_account->acco_code_account)->first();
                }

                if (($assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->count() + $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->count() + $assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->count() + $assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->count() + $assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->count() + $assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago->count()) < ($assignValues->ejecutivos[$userIndex]->cant_no_efectivas_con_pago + $assignValues->ejecutivos[$userIndex]->cant_no_efectivas_sin_pago + $assignValues->ejecutivos[$userIndex]->cant_efectivas_con_pago + $assignValues->ejecutivos[$userIndex]->cant_efectivas_sin_pago + $assignValues->ejecutivos[$userIndex]->cant_promesas_con_pago + $assignValues->ejecutivos[$userIndex]->cant_promesas_sin_pago)) {
                    $typi_efectivas = [
                        (object) [
                            'typi_effective' => true,
                            'typi_name' => 'cuelga la llamada',
                            'acma_observation' => 'se llama al XXX-XXX-XXXX la persona que contesta no se identifica y termina la llamada',
                            'time_min' => 20,
                            'time_max' => 35
                        ],
                        (object) [
                            'typi_effective' => true,
                            'typi_name' => 'agendado',
                            'acma_observation' => 'se llama al XXX-XXX-XXXX se habla con el sr X|X|X titular / encargado de pago informa que en el momento no puede antender la llamada solicita que se comuniquen nuevamente',
                            'time_min' => 60,
                            'time_max' => 90
                        ],
                        (object) [
                            'typi_effective' => true,
                            'typi_name' => 'cuelga la llamada',
                            'acma_observation' => 'se llama al XXX-XXX-XXXX la persona que contesta no se identifica y termina la llamada',
                            'time_min' => 20,
                            'time_max' => 35
                        ],
                        (object) [
                            'typi_effective' => true,
                            'typi_name' => 'agendado',
                            'acma_observation' => 'se llama al XXX-XXX-XXXX se habla con el sr X|X|X titular / encargado de pago informa que en el momento no puede antender la llamada solicita que se comuniquen nuevamente',
                            'time_min' => 60,
                            'time_max' => 90
                        ],
                        (object) [
                            'typi_effective' => true,
                            'typi_name' => 'mensaje con tercero/razon',
                            'acma_observation' => 'se llama al XXX-XXX-XXXX contesta tercero no indica nombre se brinda datos de casa de cobro',
                            'time_min' => 40,
                            'time_max' => 80
                        ],
                    ];

                    $management = clone $managements->first();

                    $isEfectiva = true;
                    $typi_efectiva = $typi_efectivas[random_int(0, 4)];

                    if ($cuenta) {
                        $random_start = Carbon::parse($horario->promesa == 0 ? '18:40:00' : '17:10:00');
                        $random_end = Carbon::parse($cuenta->acma_end_time)->diffInSeconds($random_start);

                        $random_int = random_int(60, 90);

                        $time_start = Carbon::parse($cuenta->acma_end_time)->addSeconds(random_int($random_int, $random_end));
                        $time_end = Carbon::parse($time_start)->addSeconds(random_int($typi_efectiva->time_min, $typi_efectiva->time_max));

                        $allExistingRanges = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago)))));

                        if ($this->checkTimeRanges($time_start, $time_end, $allExistingRanges)) {
                            // Buscar nuevo horario disponible
                            $newRange = $this->findNextAvailableTimeRangeEffective($time_start, $time_end, $allExistingRanges, 90, $random_start, $random_end);

                            // Actualizamos time_start y time_end si se encontró un nuevo rango
                            if ($newRange) {
                                $time_start = $newRange['start'];
                                $time_end = $newRange['end'];
                            } else {
                                $isEfectiva = false;
                            }
                        }

                        $time_start = $time_start->format('H:i:s');
                        $time_end = $time_end->format('H:i:s');
                    } else {
                        $time_start = Carbon::parse($horario->time_start)->addSeconds(random_int(0, $horario->time_start->diffInSeconds($horario->time_end)));
                        $time_end = Carbon::parse($time_start)->addSeconds(random_int($typi_efectiva->time_min, $typi_efectiva->time_max));

                        $allExistingRanges = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago)))));

                        if ($this->checkTimeRanges($time_start, $time_end, $allExistingRanges)) {
                            $time_start = $time_start->format('H:i:s');
                            $time_end = $time_end->format('H:i:s');
                        } else {
                            $rangeOverlap = $this->findNextAvailableTimeRange($time_start, $time_end, $allExistingRanges);
                            $time_start = $rangeOverlap['start']->format('H:i:s');
                            $time_end = $rangeOverlap['end']->format('H:i:s');
                        }
                    }

                    if ($isEfectiva) {

                        $typi_efectiva->acma_observation = str_replace('XXX-XXX-XXXX', is_null($assignment_account->data_value) ? 'xxxxxxxxxx' : $assignment_account->data_value, $typi_efectiva->acma_observation);
                        $typi_efectiva->acma_observation = str_replace('X|X|X', $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname, $typi_efectiva->acma_observation);

                        $management->id = $id;
                        $management->acma_start_date = $management->acma_start_date;
                        $management->acma_end_date = $management->acma_end_date;
                        $management->acma_start_time = $time_start;
                        $management->acma_end_time = $time_end;
                        $management->acco_code_account = $assignment_account->acco_code_account;
                        $management->data_value = is_null($assignment_account->data_value) ? 'xxxxxxxxxx' : $assignment_account->data_value;
                        $management->renp_name = 'n/a';
                        $management->payment_date = 'n/a';
                        $management->payment_value = 'n/a';
                        $management->payment_discount = 'n/a';
                        $management->acma_contact_name = $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname;
                        $management->acma_iseffective = $typi_efectiva->typi_effective;
                        $management->acma_observation = $typi_efectiva->acma_observation;
                        $management->assi_name = $assignment_account->assi->assi_name;
                        $management->camp_name = $assignment_account->camp->camp_name;
                        $management->alli_name = $assignment_account->alli->alli_name;
                        $management->typi_name = $typi_efectiva->typi_name;
                        $management->typi_effective = $typi_efectiva->typi_effective;
                        $management->asac_balance = $assignment_account->asac_balance;
                        $management->peop_name_g = $assignValues->ejecutivos[$userIndex]->ejec_name;
                        $management->peop_dni_g = $assignValues->ejecutivos[$userIndex]->ejec_cc;
                        $management->peop_name_c = $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname;
                        $management->peop_dni_c = $assignment_account->peop_dni;
                        $management->foal_name = is_null($assignment_account->foal) ? $management->foal_name : $assignment_account->foal->foal_name;

                        $id++;

                        $assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago = $assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->push($management);
                    }
                }
            }

            if ($horario->promesa > 0) {

                $cuenta = $assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->where('acco_code_account', $assignment_account->acco_code_account)->first();

                if (!$cuenta) {
                    $cuenta = $assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->where('acco_code_account', $assignment_account->acco_code_account)->first();
                }

                if ($cuenta && $cuenta->typi_name == 'mensaje con tercero/razon') {
                    continue;
                }

                if (!$cuenta) {
                    $cuenta = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->where('acco_code_account', $assignment_account->acco_code_account)->first();
                }

                if (!$cuenta) {
                    $cuenta = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->where('acco_code_account', $assignment_account->acco_code_account)->first();
                }

                if (($assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->count() + $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->count() + $assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->count() + $assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->count() + $assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->count() + $assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago->count()) < ($assignValues->ejecutivos[$userIndex]->cant_no_efectivas_con_pago + $assignValues->ejecutivos[$userIndex]->cant_no_efectivas_sin_pago + $assignValues->ejecutivos[$userIndex]->cant_efectivas_con_pago + $assignValues->ejecutivos[$userIndex]->cant_efectivas_sin_pago + $assignValues->ejecutivos[$userIndex]->cant_promesas_con_pago + $assignValues->ejecutivos[$userIndex]->cant_promesas_sin_pago)) {
                    $typi_promesa = (object) [
                        'typi_effective' => true,
                        'typi_name' => 'promesa de pago',
                        'acma_observation' => 'se llama al XXX-XXX-XXXX se habla con el sr X|X|X titular / encargado de pago informa que no habia cancelado debido a no responde se genera compromiso de pago para el DD/MM/AAAA por valor de $$$ y no indica datos adicionales',
                        'time_min' => 240,
                        'time_max' => 360
                    ];

                    $management = clone $managements->first();

                    $isEfectiva = true;

                    if ($cuenta) {
                        $random_start = Carbon::parse($cuenta->acma_end_time);
                        $random_end = Carbon::parse('17:50:00')->diffInSeconds($random_start);

                        $random_int = random_int(60, 90);

                        $time_start = Carbon::parse($cuenta->acma_end_time)->addSeconds(random_int($random_int, $random_end));
                        $time_end = Carbon::parse($time_start)->addSeconds(random_int($typi_promesa->time_min, $typi_promesa->time_max));

                        $allExistingRanges = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago)))));

                        if ($this->checkTimeRanges($time_start, $time_end, $allExistingRanges)) {
                            // Buscar nuevo horario disponible
                            $newRange = $this->findNextAvailableTimeRangeEffective($time_start, $time_end, $allExistingRanges, 90, $random_start, $random_end);

                            // Actualizamos time_start y time_end si se encontró un nuevo rango
                            if ($newRange) {
                                $time_start = $newRange['start'];
                                $time_end = $newRange['end'];
                            } else {
                                $isEfectiva = false;
                            }
                        }

                        $time_start = $time_start->format('H:i:s');
                        $time_end = $time_end->format('H:i:s');
                    } else {
                        $time_start = Carbon::parse($horario->time_start)->addSeconds(random_int(0, $horario->time_start->diffInSeconds($horario->time_end)));
                        $time_end = Carbon::parse($time_start)->addSeconds(random_int($typi_promesa->time_min, $typi_promesa->time_max));

                        $allExistingRanges = $assignValues->ejecutivos[$userIndex]->gets_no_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_no_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_sin_pago->concat($assignValues->ejecutivos[$userIndex]->gets_efectivas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->concat($assignValues->ejecutivos[$userIndex]->gets_promesas_sin_pago)))));

                        if ($this->checkTimeRanges($time_start, $time_end, $allExistingRanges)) {
                            $time_start = $time_start->format('H:i:s');
                            $time_end = $time_end->format('H:i:s');
                        } else {
                            $rangeOverlap = $this->findNextAvailableTimeRange($time_start, $time_end, $allExistingRanges);
                            $time_start = $rangeOverlap['start']->format('H:i:s');
                            $time_end = $rangeOverlap['end']->format('H:i:s');
                        }
                    }

                    if ($isEfectiva) {

                        $payment = $payments->where('pay_account', $assignment_account->acco_code_account)->random(1)->first();
                        $payment_date = Carbon::parse($payment->pay_date)->addDays(random_int(0, random_int(2, 3)))->format('Y-m-d');

                        $typi_promesa->acma_observation = str_replace('XXX-XXX-XXXX', is_null($assignment_account->data_value) ? 'xxxxxxxxxx' : $assignment_account->data_value, $typi_promesa->acma_observation);
                        $typi_promesa->acma_observation = str_replace('X|X|X', $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname, $typi_promesa->acma_observation);
                        $typi_promesa->acma_observation = str_replace('DD/MM/AAAA', $payment_date, $typi_promesa->acma_observation);
                        $typi_promesa->acma_observation = str_replace('$$$', $payment->pay_value, $typi_promesa->acma_observation);

                        $management->id = $id;
                        $management->acma_start_date = $management->acma_start_date;
                        $management->acma_end_date = $management->acma_end_date;
                        $management->acma_start_time = $time_start;
                        $management->acma_end_time = $time_end;
                        $management->acco_code_account = $assignment_account->acco_code_account;
                        $management->data_value = is_null($assignment_account->data_value) ? 'xxxxxxxxxx' : $assignment_account->data_value;
                        $management->renp_name = '';
                        $management->payment_date = $payment_date;
                        $management->payment_value = $payment->pay_value;
                        $management->payment_discount = $payment->pay_discount_rate;
                        $management->acma_contact_name = $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname;
                        $management->acma_iseffective = $typi_promesa->typi_effective;
                        $management->acma_observation = $typi_promesa->acma_observation;
                        $management->assi_name = $assignment_account->assi->assi_name;
                        $management->camp_name = $assignment_account->camp->camp_name;
                        $management->alli_name = $assignment_account->alli->alli_name;
                        $management->typi_name = $typi_promesa->typi_name;
                        $management->typi_effective = $typi_promesa->typi_effective;
                        $management->asac_balance = $payment->pay_value;
                        $management->peop_name_g = $assignValues->ejecutivos[$userIndex]->ejec_name;
                        $management->peop_dni_g = $assignValues->ejecutivos[$userIndex]->ejec_cc;
                        $management->peop_name_c = $assignment_account->peop_name . ' ' . $assignment_account->peop_lastname;
                        $management->peop_dni_c = $assignment_account->peop_dni;
                        $management->foal_name = $payment->focus->foal_name;

                        $id++;

                        $assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago = $assignValues->ejecutivos[$userIndex]->gets_promesas_con_pago->push($management);
                    }
                }
            }

            $userIndex = ($userIndex + 1) % count($assignValues->ejecutivos);
        }

        return $assignValues;
    }

    function isTimeInAnyRangeOrEqual($time, $ranges)
    {
        foreach ($ranges as $range) {
            $rangeStart = Carbon::parse($range->acma_start_time);
            $rangeEnd = Carbon::parse($range->acma_end_time);

            if ($time->equalTo($rangeStart) || $time->equalTo($rangeEnd) || $time->between($rangeStart, $rangeEnd)) {
                return true;
            }
        }
        return false;
    }

    function checkTimeRanges($start, $end, $ranges)
    {
        // Verificar si las horas originales tienen superposición o coincidencia
        if ($this->isTimeInAnyRangeOrEqual($start, $ranges) || $this->isTimeInAnyRangeOrEqual($end, $ranges)) {
            return false;
        }

        // Ajustar las horas
        $random_int = random_int(40, 60);
        $adjustedStart = $start->copy()->subSeconds($random_int);
        $adjustedEnd = $end->copy()->addSeconds($random_int);

        // Verificar si las horas ajustadas tienen superposición o coincidencia
        if ($this->isTimeInAnyRangeOrEqual($adjustedStart, $ranges) || $this->isTimeInAnyRangeOrEqual($adjustedEnd, $ranges)) {
            return false;
        }

        return true;
    }

    function findNextAvailableTimeRange($start, $end, $existingRanges, $adjustmentInterval = null)
    {
        $adjustmentInterval = random_int(61, 90);
        $newStart = Carbon::parse($end)->addSeconds($adjustmentInterval);
        $newEnd = Carbon::parse($end)->addSeconds($start->diffInSeconds($end))->addSeconds($adjustmentInterval);

        $workDayStart = Carbon::today()->setTime(7, 30);
        $workDayEnd = Carbon::today()->setTime(18, 55);

        while (!$this->checkTimeRanges($newStart, $newEnd, $existingRanges) || $newStart->lessThan($workDayStart)) {
            // Ajustamos el inicio y fin del rango
            $newStart = Carbon::parse($newStart)->addSeconds($adjustmentInterval);
            $newEnd = Carbon::parse($newEnd)->addSeconds($adjustmentInterval);

            if ($newEnd->greaterThan($workDayEnd) || $newStart->lessThan($workDayStart)) {
                $newStart = $workDayStart->copy();
                $newEnd = $workDayStart->copy()->addSeconds($start->diffInSeconds($end));
            }
        }
        return ['start' => $newStart, 'end' => $newEnd];
    }

    function findNextAvailableTimeRangeEffective($start, $end, $existingRanges, $adjustmentInterval, $startRange, $endRange)
    {
        $newStart = $start->copy();
        $newEnd = $end->copy();

        $startRangeCarbon = Carbon::parse($startRange);
        $endRangeCarbon = Carbon::parse($endRange);
        while (!$this->checkTimeRanges($newStart, $newEnd, $existingRanges) || $newStart->lessThan($startRangeCarbon)) {
            // Ajustamos el inicio y fin del rango
            $newStart = Carbon::parse($newStart)->addSeconds($adjustmentInterval);
            $newEnd = Carbon::parse($newEnd)->addSeconds($adjustmentInterval);

            // Verificar si el nuevo rango de tiempo está dentro de los límites permitidos
            if ($newEnd->greaterThan($endRangeCarbon)) {
                // Si no hay espacio disponible en el rango, no podemos programar la gestión
                return null;
            }
        }

        return ['start' => $newStart, 'end' => $newEnd];
    }

    private function downloadToCSV($accountsManagement)
    {
        // Generar un nombre de archivo aleatorio
        $nombreAleatorio = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'), 0, 20);

        // Crear la ruta del archivo en el sistema de almacenamiento
        $rutaArchivo = 'ReporteGestionProcesado/' . $nombreAleatorio . '.csv';

        // Crear un archivo CSV vacío en el sistema de almacenamiento
        Storage::put($rutaArchivo, '');

        // Obtener la ruta completa del archivo
        $rutaArchivo = Storage::path($rutaArchivo);

        // Abrir el archivo en modo escritura
        $file = fopen($rutaArchivo, 'w');

        // Verificar si hay datos en la colección de cuentas de gestión
        if (!empty($accountsManagement)) {

            // Obtener los encabezados del archivo CSV
            $headers = array_keys((array)$accountsManagement[0]);

            // Limpiar los encabezados de caracteres no deseados
            $headers = array_map(function ($header) {
                return preg_replace('/[\r\n\t]/', '', $header);
            }, $headers);

            // Escribir los encabezados en el archivo CSV
            fputcsv($file, $headers, ';');

            // Recorrer las filas de datos y limpiar los valores de las celdas
            foreach ($accountsManagement as $row) {
                $rowArray = (array)$row;
                $rowArray = array_map(function ($value) {
                    $value = str_replace(',', ' ', $value);
                    return '"' . preg_replace('/[\r\n\t]/', ' ', $value) . '"';
                }, $rowArray);

                // Escribir la fila en el archivo CSV
                $line = implode(';', $rowArray);
                fwrite($file, $line . "\n");
            }
        }
        // Cerrar el archivo CSV
        fclose($file);

        return $rutaArchivo;
    }
}
