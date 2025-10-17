@php
    use App\Database\Criteria;use App\Repositories\User;use App\Services\AppService; use App\Services\MessageService;
    $idUser = AppService::getCurrentIdUser();
    $user = User::byId($idUser);
    $isManager = User::isManager($user);
    $messages = MessageService::getMessagesToUser($idUser);
    $tasks = Criteria::table("view_usertask as ut")
        ->join("view_task_manager as tm","ut.idTask","=","tm.idTask")
        ->select("ut.projectName","ut.taskName","ut.taskGroupName")
        ->selectRaw("GROUP_CONCAT(DISTINCT tm.userName SEPARATOR ',') as manager")
        ->groupByRaw("ut.projectName,ut.taskName,ut.taskGroupName")
        ->where("ut.idUser",$idUser)
        ->where("ut.idProject","<>", 1)
        ->all();
    $tasksForManager =  Criteria::table("view_task_manager as tm")
        ->select("tm.projectName","tm.taskName","tm.taskGroupName")
        ->where("tm.idUser",$idUser)
        ->orderBy("tm.projectName")
        ->orderBy("tm.taskName")
        ->all();

@endphp
<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['','Home']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container">
                <div class="page-content">
                    @include("App.messages")
                    @if(count($tasksForManager) > 0)
                        <div class="segment">
                            <h2 class="ui header">Managed project/tasks</h2>
                            <table class="ui striped compact table">
                                <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Task</th>
                                    <th>Task group</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($tasksForManager as $task)
                                    <tr>
                                        <td>{{$task->projectName}}</td>
                                        <td>{{$task->taskName}}</td>
                                        <td>{{$task->taskGroupName}}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if(!$isManager)
                        <div class="segment">
                            <h2 class="ui header">My tasks</h2>
                            <table class="ui striped compact table">
                                <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Task</th>
                                    <th>Task group</th>
                                    <th>Manager(s)</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($tasks as $task)
                                    <tr>
                                        <td>{{$task->projectName}}</td>
                                        <td>{{$task->taskName}}</td>
                                        <td>{{$task->taskGroupName}}</td>
                                        <td>{{$task->manager}}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </main>
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout::index>

