import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-shiftallocation',
  templateUrl: './shiftallocation.component.html',
  styleUrls: ['./shiftallocation.component.css']
})
export class ShiftallocationComponent implements OnInit {
  employees;
  selected;
  fromdate;
  todate;
  constructor(private service: DataAccessService) {
    this.fromdate = this.getTodaysDate();
   }

  ngOnInit() {
    this.getApprovedEmployees();
  }

  getApprovedEmployees() {
    this.service.get('hrDepartment.php?type=getReportingEmployees')
    .subscribe(response => {
      this.employees = response;
    });
  }

  allocateShift(data) {
    this.service.post('hrDepartment.php?type=allocateShift', JSON.stringify(data.value))
    .subscribe(response => {
      data.reset();
      this.getApprovedEmployees();
      },
    (error: Response) => {
      if (error.status === 400) {
        alert('An error has occurred.');
      } else {
        alert('An error has occurred, http status:' + error.status);
      }
    });
  }

  getTodaysDate() {
    var today = new Date();
    var dd = today.getDate();
    var mm = today.getMonth() + 1; //January is 0!
    var yyyy = today.getFullYear();

    return dd + '-' + mm + '-' + yyyy;
  }

}
