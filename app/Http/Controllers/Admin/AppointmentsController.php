<?php

namespace App\Http\Controllers\Admin;

use App\Appointment;
use App\Client;
use App\Employee;
use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyAppointmentRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Service;
use App\Product;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use DateTime;
use DateTimeZone;

class AppointmentsController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Appointment::with(['client', 'employee', 'services', 'products'])->select(sprintf('%s.*', (new Appointment)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'appointment_show';
                $editGate      = 'appointment_edit';
                $deleteGate    = 'appointment_delete';
                $crudRoutePart = 'appointments';

                return view('partials.datatablesActions', compact(
                    'viewGate',
                    'editGate',
                    'deleteGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->editColumn('id', function ($row) {
                return $row->id ? $row->id : "";
            });
            $table->addColumn('client_name', function ($row) {
                return $row->client ? $row->client->name : '';
            });

            $table->addColumn('employee_name', function ($row) {
                return $row->employee ? $row->employee->name : '';
            });

            $table->editColumn('price', function ($row) {
                return $row->price ? $row->price : "";
            });
            $table->editColumn('comments', function ($row) {
                return $row->comments ? $row->comments : "";
            });
            $table->editColumn('services', function ($row) {
                $labels = [];

                foreach ($row->services as $service) {
                    $labels[] = sprintf('<span class="label label-info label-many">%s</span>', $service->name);
                }

                return implode(' ', $labels);
            });

            $table->editColumn('products', function ($row) {
                $labels = [];

                foreach ($row->products as $product) {
                    $labels[] = sprintf('<span class="label label-info label-many">%s</span>', $product->name);
                }

                return implode(' ', $labels);
            });

            $table->rawColumns(['actions', 'placeholder', 'client', 'employee', 'services', 'products']);

            return $table->make(true);
        }

        return view('admin.appointments.index');
    }

    public function create()
    {
        abort_if(Gate::denies('appointment_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $clients = Client::all()->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $employees = Employee::all()->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $services = Service::all()->pluck('name', 'id');

        $products = Product::all()->pluck('name', 'id');

        return view('admin.appointments.create', compact('clients', 'employees', 'services', 'products'));
    }

    public function store(StoreAppointmentRequest $request)
    {
        if(!empty($request)){

            $now = new DateTime('NOW', new DateTimeZone('Africa/Johannesburg'));
            $date1 = new DateTime($request->start_time, new DateTimeZone('Africa/Johannesburg'));
            $date2 = new DateTime($request->finish_time, new DateTimeZone('Africa/Johannesburg'));
            
            $date1 = $date1->format('Y-m-d H:i:s');
            $date2 = $date2->format('Y-m-d H:i:s');
            $now = $now->format('Y-m-d H:i:s');

            $validator = Validator::make($request->all(), $request->rules());

            if(!$this->isDoctorBooked($request->employee_id, $now, $date1, $date2)) {

                if($date1 < $now) {
                    $validator->errors()->add('start_time', 'Start Time Cannot be in the past!');
                }

                if($date1 >= $date2) {
                    $validator->errors()->add('start_time', 'Start time must be before the end date!');
                }

                $errors = $validator->errors();
                
                if($errors->any()) {
                    return redirect()->back()->withInput()->withErrors($errors);
                }

                
                $appointment = Appointment::create($request->all());
                $appointment->services()->sync($request->input('services', []));
                $appointment->products()->sync($request->input('products', []));

                return redirect()->route('admin.appointments.index');
            }else {
           
                $validator->errors()->add('start_time', 'Doctor is busy during this slot');
                $errors = $validator->errors();
                        
                if($errors->any()) {
                    return redirect()->back()->withInput()->withErrors($errors);
                }
            }
        
        } else {

            $appointment = Appointment::create($request->all());
            $appointment->services()->sync($request->input('services', []));
            $appointment->products()->sync($request->input('products', []));

            return redirect()->route('admin.appointments.index');
        }
    }

    public function edit(Appointment $appointment)
    {
        abort_if(Gate::denies('appointment_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $clients = Client::all()->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $employees = Employee::all()->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $services = Service::all()->pluck('name', 'id');

        $products = Product::all()->pluck('name', 'id');

        $appointment->load('client', 'employee', 'services', 'products');

        return view('admin.appointments.edit', compact('clients', 'employees', 'services', 'products', 'appointment'));
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $appointment->update($request->all());
        $appointment->services()->sync($request->input('services', []));
        $appointment->products()->sync($request->input('products', []));

        return redirect()->route('admin.appointments.index');
    }

    public function show(Appointment $appointment)
    {
        abort_if(Gate::denies('appointment_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $appointment->load('client', 'employee', 'services', 'products');

        return view('admin.appointments.show', compact('appointment'));
    }

    public function destroy(Appointment $appointment)
    {
        abort_if(Gate::denies('appointment_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $appointment->delete();

        return back();
    }

    public function massDestroy(MassDestroyAppointmentRequest $request)
    {
        Appointment::whereIn('id', request('ids'))->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function isDoctorBooked($employeeId, $now, $startTime, $finishTime) 
    {
        $appointments = Appointment::where('employee_id',$employeeId)->get();

          foreach ($appointments as $appointment) {
          
            if($startTime >= $appointment->start_time && $startTime <= $appointment->finish_time ) {
                return true;
            } elseif ($finishTime >= $appointment->start_time && $finishTime <= $appointment->finish_time) {
                return true;
            } 
        }

        return false;
        // ->where(function ($query) use($startTime, $finishTime){
        //         $query->whereBetween('start_time',[$startTime, $finishTime])
        //             ->orWhereBetween('finish_time',[$startTime, $finishTime]);
        //     })
        // ->Where(function($query) use($startTime, $finishTime){
        //     $query->where('start_time','<=',$startTime)
        //           ->where('finish_time','>=',$finishTime);
        // })->exists();

        // if($appointmentExists) {
        //     return true;
        // } else {
        //     return false;
        // }
      
        
        // $date1 = new DateTime($startDate);
        // $date2 = new DateTime($endDate);

        // $diff = $date2->diff($date1);
        // $hours = $diff->h;
        // $hours = $hours + ($diff->days*24);
        // var_dump($hours);
        // die;

     

    }
}
