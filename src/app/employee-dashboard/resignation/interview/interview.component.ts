import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-interview',
  templateUrl: './interview.component.html',
  styleUrls: ['./interview.component.css']
})
export class InterviewComponent implements OnInit {
  designations;
  resignations;
  selectedResignation;
  departments;
  isShow = false;
  id;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingExitInterview();
    this.getDepartments();
  }

  getPendingExitInterview() {
    this.service.get('hr/resignation.php?type=getPendingExitInterview')
    .subscribe(response => {
      this.resignations = response;
    });
  }

  showResignation(index) {
    this.selectedResignation = this.resignations[index];
    this.departments = this.selectedResignation.dues;
    this.id = this.selectedResignation['id'];
    this.isShow = true;
  }
  getDepartments() {
    this.service.get('common.php?type=getNonTechnicalDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  saveExitInterview(data) {
    let temp = data.value;
    temp['id'] = this.id;
    this.service.post('hr/resignation.php?type=saveExitInterview', JSON.stringify(temp))
    .subscribe(response => {
      if(response['status'] === 'success') {
        alert('Interview Completed');
        data.reset();
        this.isShow = false;
        this.getPendingExitInterview();
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
