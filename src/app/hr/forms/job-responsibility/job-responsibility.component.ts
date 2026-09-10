import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-job-responsibility',
  templateUrl: './job-responsibility.component.html',
  styleUrls: ['./job-responsibility.component.css']
})
export class JobResponsibilityComponent implements OnInit {
  responsibilities;
  selectedResponsibilities;
  list = [];
  list1 = [];
  index = 0;
  responsibility;
  isFirst = false;
  isSecond = false;
  emp_id;
  data = {};
  constructor(private service: DataAccessService) {
   }

  ngOnInit() {
    this.getResponsibilities();
  }

  getResponsibilities() {
    this.service.get('hrDepartment.php?type=getResponsibilities')
    .subscribe(response => {
      this.responsibilities = response;
    });
  }

  addResponsibility() {
    this.list[this.index] = this.responsibility;
    this.index = this.index + 1;
    this.responsibility = '';

    const element1 = document.getElementById('responsibility') as HTMLElement;
    element1.focus();
  }

  viewResponsibility(index) {
    this.selectedResponsibilities = this.responsibilities[index];
    this.emp_id = this.selectedResponsibilities['emp_id'];
    if(this.selectedResponsibilities['isresponsibility'] == 'active') {
      this.list1 = this.selectedResponsibilities['responsiblities'];
      this.isSecond = true;
    } else {
      this.isFirst = true;
    }
  }

  saveResponsibility() {
    if (this.list.length == 0) {
      alert('At least 1 responsibility is required');
      return;
    }
    this.data["emp_id"] = this.emp_id;
    this.data["responsibility"] = this.list;
    this.service.post('hrDepartment.php?type=saveResponsibility', JSON.stringify(this.data))
    .subscribe(response => {
      if(response['status'] === 'success') {
        this.list = [];
        this.data = {};
        this.index = 0;
        this.isFirst = false;
        this.getResponsibilities();
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

}
