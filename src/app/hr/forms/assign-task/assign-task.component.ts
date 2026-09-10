import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-assign-task',
  templateUrl: './assign-task.component.html',
  styleUrls: ['./assign-task.component.css']
})
export class AssignTaskComponent implements OnInit {
  employees;
  tasks = [];
  task;
  assignedTasks;
  selectedTask;
  isSelectedTask = false;
  isAssignTask = false;
  reporting_remark = '';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getAssignedTasks();
    this.getEmployees();
  }

  getAssignedTasks() {
    this.service.get('hrDepartment.php?type=getAssignedTasks')
    .subscribe(response => {
      this.assignedTasks = response;
    });
  }

  getEmployees() {
    this.service.get('hrDepartment.php?type=getReportingEmployees')
    .subscribe(response => {
      this.employees = response;
    });
  }

  addTask() {
    this.tasks[this.tasks.length] = this.task;
    this.task = "";
  }

  assignTask(data) {
    let temp = data.value;
    temp['task'] = this.tasks;
    this.service.post('hrDepartment.php?type=assignTask', JSON.stringify(temp))
    .subscribe(response => {
      if(response['status'] === 'success') {
        alert('Task Assign to Employess');
        data.reset();
        this.tasks = [];
        this.isAssignTask = false;
        this.getAssignedTasks();
      } else {
        alert(response['status']);
      }
      },
    (error: Response) => {
      if (error.status === 400) {
        alert('An error has occurred.');
      } else {
        alert('An error has occurred, http status:' + error.status);
      }
    });
  }

  showtask(index) {
    this.selectedTask = this.assignedTasks[index];
    this.isSelectedTask = true;
  }

  EmployeeTaskRemark() {
    this.service.get('hrDepartment.php?type=employeeTaskRemark&task_id=' + this.selectedTask['id'] + '&remark=' + this.reporting_remark).subscribe(response => {
      if (response['status'] == 'success') {
        alert('saved successfully');
        this.getAssignedTasks();
        this.isSelectedTask = false;
      }
    });
  }

}
