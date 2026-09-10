import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-daily-report',
  templateUrl: './daily-report.component.html',
  styleUrls: ['./daily-report.component.css']
})
export class DailyReportComponent implements OnInit {
  tasks;
  selectedTask;
  isShow = false;
  id;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getDailyTask();
  }

  getDailyTask() {
    this.service.get('hrDepartment.php?type=getDailyTask')
    .subscribe(response => {
      this.tasks = response;
    });
  }

  getTask(index) {
    this.selectedTask = this.tasks[index].task;
    this.id = this.tasks[index].id;
    this.isShow = true;
  }

  sendTaskReport(value, value1, value2) {
    this.service.get('hrDepartment.php?type=sendTaskReport&emp_remark='+value1 + '&emp_status='+value2+'&id='+value)
    .subscribe(response => {
      this.getDailyTask();
      let element = document.getElementById('task-'+value) as HTMLElement;
      element.style.display = 'none';
    });
  }

  submitFinalTaskReport(remark, instruction) {
    this.service.get('hrDepartment.php?type=submitFinalTaskReport&remark='+remark + '&instruction='+instruction+ '&id=' + this.id)
    .subscribe(response => {
      this.isShow = false;
      this.getDailyTask();
    });
  }
  

}
