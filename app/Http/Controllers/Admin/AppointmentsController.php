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
            $query = Appointment::with(['client', 'employee', 'services'])->select(sprintf('%s.*', (new Appointment)->table));
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

            $table->rawColumns(['actions', 'placeholder', 'client', 'employee', 'services']);

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

        return view('admin.appointments.create', compact('clients', 'employees', 'services'));
    }

    public function store(StoreAppointmentRequest $request)
    {
        if (!empty($request)) {

            $now = new DateTime('NOW', new DateTimeZone('Africa/Johannesburg'));
            $date1 = new DateTime($request->start_time, new DateTimeZone('Africa/Johannesburg'));
            $date2 = new DateTime($request->finish_time, new DateTimeZone('Africa/Johannesburg'));

            $date1 = $date1->getTimestamp();
            $date2 = $date2->getTimestamp();
            $now = $now->getTimestamp();

            $validator = Validator::make($request->all(), $request->rules());
            
            if($date1 < $now) {
                $validator->errors()->add('start_date', 'Start date Cannot be in the past!');
            }

            if($date1 > $date2) {
                $validator->errors()->add('start_date', 'Start date must be before the end date!');
            }
            $errors = $validator->errors();
            
            if($errors->any()) {
                return redirect()->back()->withInput()->withErrors($errors);
            }
            
            $appointment = Appointment::create($request->all());
            $appointment->services()->sync($request->input('services', []));

            return redirect()->route('admin.appointments.index');
        }
        
        $appointment = Appointment::create($request->all());
        $appointment->services()->sync($request->input('services', []));

        return redirect()->route('admin.appointments.index');
    }

    public function edit(Appointment $appointment)
    {
        abort_if(Gate::denies('appointment_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $clients = Client::all()->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $employees = Employee::all()->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $services = Service::all()->pluck('name', 'id');

        $appointment->load('client', 'employee', 'services');

        return view('admin.appointments.edit', compact('clients', 'employees', 'services', 'appointment'));
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $appointment->update($request->all());
        $appointment->services()->sync($request->input('services', []));

        return redirect()->route('admin.appointments.index');
    }

    public function show(Appointment $appointment)
    {
        abort_if(Gate::denies('appointment_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $appointment->load('client', 'employee', 'services');

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

    public function somethingElseIsInvalid($startDate, $endDate) 
    {
        return true;
        // $date1 = new DateTime($startDate);
        // $date2 = new DateTime($endDate);

        // $diff = $date2->diff($date1);
        // $hours = $diff->h;
        // $hours = $hours + ($diff->days*24);
        // var_dump($hours);
        // die;

        if ($startDate >= $endDate){
            return false;
        }

    }
}
