import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-phisicians',
  templateUrl: './phisicians.component.html',
  styleUrls: ['./phisicians.component.css']
})
export class PhisiciansComponent implements OnInit {
  phisicians;
  qualifications;
  isNewPhisician = false;
  constructor(private service: DataAccessService) {
   }

  ngOnInit() {
    this.getPhisicians();
    this.getApprovedQualifications();
  }

  getApprovedQualifications() {
    this.service.get('hrDepartment.php?type=getApprovedQualifications')
    .subscribe(response => {
      this.qualifications = response;
    });
  }

  getPhisicians() {
    this.service.get('hrDepartment.php?type=getPhisicians')
    .subscribe(response => {
      this.phisicians = response;
    });
  }

  addPhisician(phisicianForm) {
    this.isNewPhisician = false;
    this.service.post('hrDepartment.php?type=addPhisician', JSON.stringify(phisicianForm.value))
    .subscribe(response => {
      phisicianForm.reset();
      this.getPhisicians();
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
