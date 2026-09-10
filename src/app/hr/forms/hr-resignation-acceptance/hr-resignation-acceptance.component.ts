import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-hr-resignation-acceptance',
  templateUrl: './hr-resignation-acceptance.component.html',
  styleUrls: ['./hr-resignation-acceptance.component.css']
})
export class HrResignationAcceptanceComponent implements OnInit {
  resignations;
  selectedResignation;
  departments;
  isSelectedResignation = false;
  id;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingResignations();
  }

  getPendingResignations() {
    this.service.get('hrDepartment.php?type=getHRPendingResignations')
    .subscribe(response => {
      this.resignations = response;
    });
  }

  showDetails(index) {
    this.selectedResignation = this.resignations[index];
    this.id = this.selectedResignation['id'];
    this.isSelectedResignation = true;
  }

  submitResignation(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['id'] = this.id;
    this.service.post('hrDepartment.php?type=submitHRResignationReport', JSON.stringify(temp))
    .subscribe(response => {
      if(response['status'] === 'success') {
        data.reset();
        this.isSelectedResignation = false;
        this.getPendingResignations();
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
